<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Facades\Excel;
use App\Imports\EmailListImport;
use Illuminate\Support\Facades\Storage;

class FileParserService
{
    /**
     * Parse an uploaded file and return headers + rows.
     * (Used for mapping preview - limited to first few rows)
     */
    public function parse(UploadedFile $file): array
    {
        $extension = strtolower($file->getClientOriginalExtension());

        return match ($extension) {
            'csv'  => $this->parseCsv($file),
            'xlsx' => $this->parseXlsx($file),
            default => throw new \InvalidArgumentException("Unsupported file type: {$extension}"),
        };
    }

    protected function parseCsv(UploadedFile $file): array
    {
        $path = $file->getRealPath();
        $handle = fopen($path, 'r');
        $allRows = [];
        
        $count = 0;
        while (($row = fgetcsv($handle)) !== false && $count < 100) {
            $allRows[] = $row;
            $count++;
        }
        fclose($handle);

        if (empty($allRows)) throw new \RuntimeException('CSV file is empty.');

        $headerIndex = $this->detectHeaderIndex($allRows);
        $headers = array_map(fn($h) => trim(preg_replace('/[\x{FEFF}]/u', '', $h)), $allRows[$headerIndex]);
        $rows = [];

        for ($i = $headerIndex + 1; $i < count($allRows); $i++) {
            $row = $allRows[$i];
            if (count($row) === count($headers)) {
                $rows[] = array_combine($headers, $row);
            }
        }

        return [
            'headers' => $headers,
            'rows'    => $rows,
            'count'   => count($rows),
        ];
    }

    protected function parseXlsx(UploadedFile $file): array
    {
        $path = $file->getRealPath();
        $reader = new \PhpOffice\PhpSpreadsheet\Reader\Xlsx();
        $reader->setReadDataOnly(true);
        $spreadsheet = $reader->load($path);

        $bestWorksheet = null;
        $bestScore = -1;
        $bestRows = [];

        foreach ($spreadsheet->getWorksheetIterator() as $worksheet) {
            $allRows = [];
            $count = 0;
            $hasEmail = false;

            foreach ($worksheet->getRowIterator() as $row) {
                if ($count >= 100) break;
                $cellIterator = $row->getCellIterator();
                $cellIterator->setIterateOnlyExistingCells(false);
                $rowData = [];
                foreach ($cellIterator as $cell) {
                    $rowData[] = trim((string) $cell->getValue());
                }

                // Trim trailing empty cells
                while (!empty($rowData) && end($rowData) === '') {
                    array_pop($rowData);
                }

                if (!empty(array_filter($rowData))) {
                    $allRows[] = $rowData;
                    if (!$hasEmail && str_contains(strtolower(implode(' ', $rowData)), 'email')) {
                        $hasEmail = true;
                    }
                }
                $count++;
            }

            $score = count($allRows) + ($hasEmail ? 1000 : 0);
            if ($score > $bestScore) {
                $bestScore = $score;
                $bestRows = $allRows;
                $bestWorksheet = $worksheet;
            }
        }

        if (empty($bestRows)) throw new \RuntimeException('Excel file is empty or has no valid data.');

        $headerIndex = $this->detectHeaderIndex($bestRows);
        $rawHeaders = $bestRows[$headerIndex];
        
        $headers = [];
        foreach ($rawHeaders as $idx => $h) {
            $h = trim($h);
            if ($h === '') $h = 'Column_' . ($idx + 1);
            
            $original = $h;
            $c = 1;
            while (in_array($h, $headers)) {
                $h = $original . '_' . $c;
                $c++;
            }
            $headers[] = $h;
        }

        $rows = [];
        for ($i = $headerIndex + 1; $i < count($bestRows); $i++) {
            $rowData = $bestRows[$i];
            
            if (count($rowData) > count($headers)) {
                $rowData = array_slice($rowData, 0, count($headers));
            } elseif (count($rowData) < count($headers)) {
                $rowData = array_pad($rowData, count($headers), '');
            }
            
            $rows[] = array_combine($headers, $rowData);
        }

        return [
            'headers' => $headers,
            'rows'    => $rows,
            'count'   => count($rows),
        ];
    }

    /**
     * STREAMING PARSER: Yields mapped data row by row to prevent OOM errors.
     */
    public function streamStoredFile(string $filePath, array $mapping): \Generator
    {
        $extension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
        $fullPath = Storage::disk('local')->path($filePath);

        if (!file_exists($fullPath)) {
            throw new \RuntimeException("File not found: {$fullPath}");
        }

        if ($extension === 'csv') {
            yield from $this->streamCsv($fullPath, $mapping);
        } else {
            yield from $this->streamXlsx($fullPath, $mapping);
        }
    }

    protected function streamCsv(string $path, array $mapping): \Generator
    {
        $handle = fopen($path, 'r');
        
        // 1. Detect Headers
        $firstRows = [];
        for ($i = 0; $i < 10; $i++) {
            if (($row = fgetcsv($handle, 0, ',')) !== false) {
                $firstRows[] = $row;
            }
        }
        
        if (empty($firstRows)) return;

        $headerIndex = $this->detectHeaderIndex($firstRows);
        $rawHeaders = $firstRows[$headerIndex];
        $headers = [];
        foreach ($rawHeaders as $idx => $h) {
            $h = trim(preg_replace('/[\x{FEFF}]/u', '', $h ?? ''));
            if ($h === '') $h = 'Column_' . ($idx + 1);
            $original = $h;
            $c = 1;
            while (in_array($h, $headers)) {
                $h = $original . '_' . $c;
                $c++;
            }
            $headers[] = $h;
        }
        $headerCount = count($headers);
        
        // 2. Reset and skip to data
        rewind($handle);
        for ($i = 0; $i <= $headerIndex; $i++) {
            fgetcsv($handle, 0, ',');
        }

        // 3. Stream Rows
        $rowNumber = $headerIndex + 1;
        while (($row = fgetcsv($handle, 0, ',')) !== false) {
            $rowNumber++;
            $columnCount = count($row);

            if ($columnCount !== $headerCount) {
                if ($columnCount < $headerCount) {
                    $row = array_pad($row, $headerCount, null);
                } else {
                    $row = array_slice($row, 0, $headerCount);
                }
            }

            try {
                $combined = array_combine($headers, $row);
                $mapped = $this->mapSingleRow($combined, $mapping);
                foreach ($mapped as $item) yield $item;
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::error("Failed to map CSV row {$rowNumber}: " . $e->getMessage());
                continue;
            }
        }
        fclose($handle);
    }

    protected function streamXlsx(string $path, array $mapping): \Generator
    {
        $reader = new \PhpOffice\PhpSpreadsheet\Reader\Xlsx();
        $reader->setReadDataOnly(true);
        $spreadsheet = $reader->load($path);

        $bestWorksheet = null;
        $bestScore = -1;

        // 1. Find the best worksheet (same logic as parseXlsx)
        foreach ($spreadsheet->getWorksheetIterator() as $worksheet) {
            $count = 0;
            $hasEmail = false;
            foreach ($worksheet->getRowIterator() as $row) {
                if ($count >= 10) break;
                $cellIterator = $row->getCellIterator();
                $cellIterator->setIterateOnlyExistingCells(false);
                $rowData = [];
                foreach ($cellIterator as $cell) {
                    $rowData[] = trim((string) $cell->getValue());
                }
                while (!empty($rowData) && end($rowData) === '') array_pop($rowData);
                
                if (!empty(array_filter($rowData))) {
                    $count++;
                    if (!$hasEmail && str_contains(strtolower(implode(' ', $rowData)), 'email')) {
                        $hasEmail = true;
                    }
                }
            }
            $score = $count + ($hasEmail ? 1000 : 0);
            if ($score > $bestScore) {
                $bestScore = $score;
                $bestWorksheet = $worksheet;
            }
        }

        if (!$bestWorksheet) return;

        $headerIndex = -1;
        $headers = [];

        foreach ($bestWorksheet->getRowIterator() as $index => $row) {
            $cellIterator = $row->getCellIterator();
            $cellIterator->setIterateOnlyExistingCells(false);
            $rowData = [];
            foreach ($cellIterator as $cell) {
                $rowData[] = trim((string) $cell->getValue());
            }
            while (!empty($rowData) && end($rowData) === '') array_pop($rowData);

            if (empty(array_filter($rowData))) continue;

            if ($headerIndex === -1) {
                $rowString = strtolower(implode(' ', $rowData));
                $nonEmptyCount = count(array_filter($rowData, fn($c) => $c !== ''));
                if (str_contains($rowString, 'email') || $nonEmptyCount >= 3) {
                    $headerIndex = $index;
                    
                    foreach ($rowData as $idx => $h) {
                        $h = trim($h);
                        if ($h === '') $h = 'Column_' . ($idx + 1);
                        $original = $h;
                        $c = 1;
                        while (in_array($h, $headers)) {
                            $h = $original . '_' . $c;
                            $c++;
                        }
                        $headers[] = $h;
                    }
                }
                continue;
            }

            if (count($rowData) > count($headers)) {
                $rowData = array_slice($rowData, 0, count($headers));
            } elseif (count($rowData) < count($headers)) {
                $rowData = array_pad($rowData, count($headers), '');
            }

            $mapped = $this->mapSingleRow(array_combine($headers, $rowData), $mapping);
            foreach ($mapped as $item) yield $item;
        }
    }

    protected function mapSingleRow(array $row, array $mapping): array
    {
        $emailColumn = $mapping['email'] ?? null;
        if (!$emailColumn) return [];

        $emailRaw = trim($row[$emailColumn] ?? '');
        if (empty($emailRaw)) return [];

        // Smart Split: Support comma, semicolon, pipe, slash (/), and whitespace/newlines
        $emails = preg_split('/[,\s;|\/]+/', $emailRaw, -1, PREG_SPLIT_NO_EMPTY);
        $results = [];

        foreach ($emails as $email) {
            if (empty($email)) continue;

            $data = [
                'email' => strtolower($email),
                'name'  => null,
                'meta'  => []
            ];

            foreach ($mapping as $systemField => $excelColumn) {
                if ($excelColumn === $emailColumn || str_starts_with($systemField, '_')) continue;
                if (is_array($excelColumn)) continue;

                $value = trim($row[$excelColumn] ?? '');
                
                if ($systemField === 'name') {
                    $data['name'] = $value;
                } else {
                    $data['meta'][$systemField] = $value;
                }
            }

            if (empty($data['meta'])) $data['meta'] = null;
            $results[] = $data;
        }

        return $results;
    }

    protected function detectHeaderIndex(array $rows): int
    {
        foreach ($rows as $index => $row) {
            $nonEmptyCount = count(array_filter($row, fn($c) => !empty(trim($c))));
            $rowString = strtolower(implode(' ', $row));
            
            if (str_contains($rowString, 'email') || $nonEmptyCount >= 3 || ($index > 0 && $nonEmptyCount > 1)) {
                return $index;
            }
        }
        return 0;
    }

    public function autoDetectMappings(array $headers, array $sampleRows = []): array
    {
        $suggestions = [];
        $fieldKeywords = [
            'email'     => ['email', 'e-mail', 'mail', 'email_address', 'primary_email'],
            'name'      => ['name', 'full_name', 'fullname', 'contact_name', 'customer_name'],
            'first_name'=> ['first_name', 'firstname', 'fname', 'given_name'],
            'last_name' => ['last_name', 'lastname', 'lname', 'surname'],
            'phone'     => ['phone', 'mobile', 'cell', 'telephone', 'contact_no', 'ph_no'],
            'company'   => ['company', 'organization', 'business', 'firm', 'employer'],
            'job_title' => ['job_title', 'designation', 'position', 'role', 'title'],
            'city'      => ['city', 'town', 'location'],
            'state'     => ['state', 'province', 'region'],
            'country'   => ['country', 'nation'],
            'zip'       => ['zip', 'pincode', 'postal_code', 'zip_code'],
            'website'   => ['website', 'url', 'site', 'web_link'],
            'linkedin'  => ['linkedin', 'linkedin_url'],
        ];

        foreach ($headers as $header) {
            $cleanHeader = strtolower(trim($header));
            $matched = false;

            foreach ($fieldKeywords as $field => $keywords) {
                if (in_array($cleanHeader, $keywords)) {
                    $suggestions[$header] = $field;
                    $matched = true;
                    break;
                }
            }
            if ($matched) continue;

            if (!empty($sampleRows)) {
                $sampleValue = strtolower(trim($sampleRows[0][$header] ?? ''));
                if (str_contains($sampleValue, '@') && str_contains($sampleValue, '.')) {
                    $suggestions[$header] = 'email';
                } elseif (preg_match('/^\+?[0-9\s\-]{8,15}$/', $sampleValue)) {
                    $suggestions[$header] = 'phone';
                }
            }
        }

        return $suggestions;
    }

    public function autoDetectEmailColumn(array $headers, array $sampleRows = []): ?string
    {
        $mapping = $this->autoDetectMappings($headers, $sampleRows);
        return array_search('email', $mapping) ?: null;
    }

    public function autoDetectNameColumn(array $headers): ?string
    {
        $mapping = $this->autoDetectMappings($headers);
        return array_search('name', $mapping) ?: null;
    }
}
