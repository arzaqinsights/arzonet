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
        
        // Only load first 100 rows for preview/mapping
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
        $worksheet = $spreadsheet->getActiveSheet();

        $allRows = [];
        $count = 0;
        foreach ($worksheet->getRowIterator() as $row) {
            if ($count >= 100) break;
            $cellIterator = $row->getCellIterator();
            $cellIterator->setIterateOnlyExistingCells(false);
            $rowData = [];
            foreach ($cellIterator as $cell) {
                $rowData[] = trim((string) $cell->getValue());
            }
            $allRows[] = $rowData;
            $count++;
        }

        if (empty($allRows)) throw new \RuntimeException('Excel file is empty.');

        $headerIndex = $this->detectHeaderIndex($allRows);
        $headers = $allRows[$headerIndex];
        $rows = [];

        for ($i = $headerIndex + 1; $i < count($allRows); $i++) {
            $rowData = $allRows[$i];
            if (count($rowData) === count($headers)) {
                $rows[] = array_combine($headers, $rowData);
            }
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
            if (($row = fgetcsv($handle)) !== false) {
                $firstRows[] = $row;
            }
        }
        
        $headerIndex = $this->detectHeaderIndex($firstRows);
        $headers = array_map(fn($h) => trim(preg_replace('/[\x{FEFF}]/u', '', $h)), $firstRows[$headerIndex]);
        
        // 2. Reset and skip to data
        rewind($handle);
        for ($i = 0; $i <= $headerIndex; $i++) {
            fgetcsv($handle);
        }

        // 3. Stream Rows
        while (($row = fgetcsv($handle)) !== false) {
            if (count($row) === count($headers)) {
                $mapped = $this->mapSingleRow(array_combine($headers, $row), $mapping);
                foreach ($mapped as $item) yield $item;
            }
        }
        fclose($handle);
    }

    protected function streamXlsx(string $path, array $mapping): \Generator
    {
        $reader = new \PhpOffice\PhpSpreadsheet\Reader\Xlsx();
        $reader->setReadDataOnly(true);
        $spreadsheet = $reader->load($path);
        $worksheet = $spreadsheet->getActiveSheet();

        $headerIndex = -1;
        $headers = [];

        foreach ($worksheet->getRowIterator() as $index => $row) {
            $cellIterator = $row->getCellIterator();
            $cellIterator->setIterateOnlyExistingCells(false);
            $rowData = [];
            foreach ($cellIterator as $cell) {
                $rowData[] = trim((string) $cell->getValue());
            }

            if ($headerIndex === -1) {
                // Simplified header detection for streaming
                if (str_contains(strtolower(implode(' ', $rowData)), 'email')) {
                    $headerIndex = $index;
                    $headers = $rowData;
                }
                continue;
            }

            if (count($rowData) === count($headers)) {
                $mapped = $this->mapSingleRow(array_combine($headers, $rowData), $mapping);
                foreach ($mapped as $item) yield $item;
            }
        }
    }

    protected function mapSingleRow(array $row, array $mapping): array
    {
        $emailColumn = $mapping['email'] ?? null;
        if (!$emailColumn) return [];

        $emailRaw = trim($row[$emailColumn] ?? '');
        if (empty($emailRaw)) return [];

        $emails = array_map('trim', explode(',', $emailRaw));
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

    /**
     * Auto-detect all mapping fields.
     */
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
