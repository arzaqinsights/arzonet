<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Facades\Excel;
use App\Imports\EmailListImport;

class FileParserService
{
    /**
     * Parse an uploaded file and return headers + rows.
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

    /**
     * Parse CSV file with smart header detection.
     */
    protected function parseCsv(UploadedFile $file): array
    {
        $path = $file->getRealPath();
        $handle = fopen($path, 'r');
        $allRows = [];
        
        while (($row = fgetcsv($handle)) !== false) {
            $allRows[] = $row;
        }
        fclose($handle);

        if (empty($allRows)) throw new \RuntimeException('CSV file is empty.');

        // Find the header row (skip "Title" rows)
        $headerIndex = 0;
        foreach ($allRows as $index => $row) {
            $nonEmptyCount = count(array_filter($row, fn($c) => !empty(trim($c))));
            $rowString = strtolower(implode(' ', $row));
            
            // If row has "email" or more than 2 columns, it's likely the header
            if (str_contains($rowString, 'email') || $nonEmptyCount >= 3 || ($index > 0 && $nonEmptyCount > 1)) {
                $headerIndex = $index;
                break;
            }
        }

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

    /**
     * Parse XLSX file with smart header detection.
     */
    protected function parseXlsx(UploadedFile $file): array
    {
        $path = $file->getRealPath();
        $reader = new \PhpOffice\PhpSpreadsheet\Reader\Xlsx();
        $reader->setReadDataOnly(true);
        $spreadsheet = $reader->load($path);
        $worksheet = $spreadsheet->getActiveSheet();

        $allRows = [];
        foreach ($worksheet->getRowIterator() as $row) {
            $cellIterator = $row->getCellIterator();
            $cellIterator->setIterateOnlyExistingCells(false);
            $rowData = [];
            foreach ($cellIterator as $cell) {
                $rowData[] = trim((string) $cell->getValue());
            }
            $allRows[] = $rowData;
        }

        if (empty($allRows)) throw new \RuntimeException('Excel file is empty.');

        // Find the header row
        $headerIndex = 0;
        foreach ($allRows as $index => $row) {
            $nonEmptyCount = count(array_filter($row, fn($c) => !empty(trim($c))));
            $rowString = strtolower(implode(' ', $row));
            
            if (str_contains($rowString, 'email') || $nonEmptyCount >= 3 || ($index > 0 && $nonEmptyCount > 1)) {
                $headerIndex = $index;
                break;
            }
        }

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
     * Parse a stored file (by path) with a given mapping.
     */
    public function parseStoredFile(string $filePath, array $mapping): array
    {
        $extension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
        $fullPath = \Illuminate\Support\Facades\Storage::disk('local')->path($filePath);

        if (!file_exists($fullPath)) {
            throw new \RuntimeException("File not found: {$fullPath}");
        }

        $allRows = [];

        if ($extension === 'csv') {
            $handle = fopen($fullPath, 'r');
            while (($row = fgetcsv($handle)) !== false) {
                $allRows[] = $row;
            }
            fclose($handle);
        } else {
            $reader = new \PhpOffice\PhpSpreadsheet\Reader\Xlsx();
            $reader->setReadDataOnly(true);
            $spreadsheet = $reader->load($fullPath);
            $worksheet = $spreadsheet->getActiveSheet();

            foreach ($worksheet->getRowIterator() as $row) {
                $cellIterator = $row->getCellIterator();
                $cellIterator->setIterateOnlyExistingCells(false);
                $rowData = [];
                foreach ($cellIterator as $cell) {
                    $rowData[] = trim((string) $cell->getValue());
                }
                $allRows[] = $rowData;
            }
        }

        if (empty($allRows)) return [];

        // Smart Header Detection
        $headerIndex = 0;
        foreach ($allRows as $index => $row) {
            $nonEmptyCount = count(array_filter($row, fn($c) => !empty(trim($c))));
            $rowString = strtolower(implode(' ', $row));
            if (str_contains($rowString, 'email') || $nonEmptyCount >= 3 || ($index > 0 && $nonEmptyCount > 1)) {
                $headerIndex = $index;
                break;
            }
        }

        $headers = $allRows[$headerIndex];
        $rows = [];
        for ($i = $headerIndex + 1; $i < count($allRows); $i++) {
            $rowData = $allRows[$i];
            if (count($rowData) === count($headers)) {
                $rows[] = array_combine($headers, $rowData);
            }
        }

        return $this->applyMapping($rows, $headers, $mapping);
    }

    /**
     * Apply column mapping to extracted rows.
     */
    protected function applyMapping(array $rows, array $headers, array $mapping): array
    {
        $emailColumn = $mapping['email'] ?? null;
        if (!$emailColumn) {
            throw new \InvalidArgumentException('Email column mapping is required.');
        }

        $result = [];
        foreach ($rows as $row) {
            $emailRaw = trim($row[$emailColumn] ?? '');
            if (empty($emailRaw)) continue;

            // Support multiple emails in one cell (separated by comma)
            $emails = array_map('trim', explode(',', $emailRaw));

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
                $result[] = $data;
            }
        }

        return $result;
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

            // 1. Keyword Check
            foreach ($fieldKeywords as $field => $keywords) {
                if (in_array($cleanHeader, $keywords)) {
                    $suggestions[$header] = $field;
                    $matched = true;
                    break;
                }
            }
            if ($matched) continue;

            // 2. Sample Data Check (Fuzzy match)
            if (!empty($sampleRows)) {
                $sampleValue = strtolower(trim($sampleRows[0][$header] ?? ''));
                if (str_contains($sampleValue, '@') && str_contains($sampleValue, '.')) {
                    $suggestions[$header] = 'email';
                } elseif (preg_match('/^\+?[0-9\s\-]{8,15}$/', $sampleValue)) {
                    $suggestions[$header] = 'phone';
                } elseif (str_starts_with($sampleValue, 'http')) {
                    if (str_contains($sampleValue, 'linkedin')) $suggestions[$header] = 'linkedin';
                    else $suggestions[$header] = 'website';
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
