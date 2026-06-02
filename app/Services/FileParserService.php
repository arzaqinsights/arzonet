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
            'csv', 'txt' => $this->parseCsv($file),
            'xlsx'       => $this->parseXlsx($file),
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
        
        if ($headerIndex === -1) {
            $columnCount = count($allRows[0]);
            $headers = array_map(fn($i) => "Column_" . ($i + 1), range(0, $columnCount - 1));
            $startIndex = 0;
        } else {
            $headers = array_map(fn($h) => $this->normalizeValue($h), $allRows[$headerIndex]);
            $startIndex = $headerIndex + 1;
        }

        $rows = [];

        for ($i = $startIndex; $i < count($allRows); $i++) {
            $row = array_map(fn($v) => $this->normalizeValue($v), $allRows[$i]);
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
                    $rowData[] = $this->normalizeValue($cell->getValue());
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

            $totalRows = (int) $worksheet->getHighestRow();
            $score = count($allRows) + ($hasEmail ? 1000 : 0) + ($totalRows / 1000000.0);
            if ($score > $bestScore) {
                $bestScore = $score;
                $bestRows = $allRows;
                $bestWorksheet = $worksheet;
            }
        }

        if (empty($bestRows)) throw new \RuntimeException('Excel file is empty or has no valid data.');

        $headerIndex = $this->detectHeaderIndex($bestRows);
        
        if ($headerIndex === -1) {
            $columnCount = count($bestRows[0]);
            $headers = array_map(fn($i) => "Column_" . ($i + 1), range(0, $columnCount - 1));
            $startIndex = 0;
        } else {
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
            $startIndex = $headerIndex + 1;
        }

        $rows = [];
        for ($i = $startIndex; $i < count($bestRows); $i++) {
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
    public function streamStoredFile(string $filePath, array $mapping, string $listType = 'email'): \Generator
    {
        $extension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
        $fullPath = Storage::disk('local')->path($filePath);

        if (!file_exists($fullPath)) {
            throw new \RuntimeException("File not found: {$fullPath}");
        }

        if ($extension === 'csv' || $extension === 'txt') {
            yield from $this->streamCsv($fullPath, $mapping, $listType);
        } else {
            yield from $this->streamXlsx($fullPath, $mapping, $listType);
        }
    }

    protected function streamCsv(string $path, array $mapping, string $listType = 'email'): \Generator
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
        
        if ($headerIndex === -1) {
            $columnCount = count($firstRows[0]);
            $headers = array_map(fn($i) => "Column_" . ($i + 1), range(0, $columnCount - 1));
            $skipRows = 0;
        } else {
            $rawHeaders = $firstRows[$headerIndex];
            $headers = [];
            foreach ($rawHeaders as $idx => $h) {
                $h = $this->normalizeValue($h);
                if ($h === '') $h = 'Column_' . ($idx + 1);
                $original = $h;
                $c = 1;
                while (in_array($h, $headers)) {
                    $h = $original . '_' . $c;
                    $c++;
                }
                $headers[] = $h;
            }
            $skipRows = $headerIndex + 1;
        }
        $headerCount = count($headers);
        
        // 2. Reset and skip to data
        rewind($handle);
        for ($i = 0; $i < $skipRows; $i++) {
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
                $originalRowId = \Illuminate\Support\Str::uuid()->toString();
                $combined = array_combine($headers, $row);
                $mapped = $this->mapSingleRow($combined, $mapping, $listType, $originalRowId);
                foreach ($mapped as $item) yield $item;
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::error("Failed to map CSV row {$rowNumber}: " . $e->getMessage());
                continue;
            }
        }
        fclose($handle);
    }

    protected function streamXlsx(string $path, array $mapping, string $listType = 'email'): \Generator
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
                    $rowData[] = $this->normalizeValue($cell->getValue());
                }
                while (!empty($rowData) && end($rowData) === '') array_pop($rowData);
                
                if (!empty(array_filter($rowData))) {
                    $count++;
                    if (!$hasEmail && str_contains(strtolower(implode(' ', $rowData)), 'email')) {
                        $hasEmail = true;
                    }
                }
            }
            $totalRows = (int) $worksheet->getHighestRow();
            $score = $count + ($hasEmail ? 1000 : 0) + ($totalRows / 1000000.0);
            if ($score > $bestScore) {
                $bestScore = $score;
                $bestWorksheet = $worksheet;
            }
        }

        if (!$bestWorksheet) return;

        // 2. Read first 15 non-empty rows to detect header — using SAME detectHeaderIndex() as parseXlsx
        $firstRows = [];
        foreach ($bestWorksheet->getRowIterator() as $row) {
            $cellIterator = $row->getCellIterator();
            $cellIterator->setIterateOnlyExistingCells(false);
            $rowData = [];
            foreach ($cellIterator as $cell) {
                $rowData[] = $this->normalizeValue($cell->getValue());
            }
            while (!empty($rowData) && end($rowData) === '') array_pop($rowData);
            if (!empty(array_filter($rowData))) {
                $firstRows[] = $rowData;
            }
            if (count($firstRows) >= 15) break;
        }

        if (empty($firstRows)) return;

        $headerIndex = $this->detectHeaderIndex($firstRows);

        $headers = [];
        $dataStartOffset = 0; // how many non-empty rows to skip before yielding

        if ($headerIndex === -1) {
            // No header found — all rows are data
            $columnCount = count($firstRows[0]);
            $headers = array_map(fn($i) => "Column_" . ($i + 1), range(0, $columnCount - 1));
            $dataStartOffset = 0;
        } else {
            $rawHeaders = $firstRows[$headerIndex];
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
            // Skip all rows up to and including the header row
            $dataStartOffset = $headerIndex + 1;
        }

        if (empty($headers)) return;

        // 3. Stream all rows. Count non-empty rows seen to skip header rows.
        $nonEmptyRowsSeen = 0;
        foreach ($bestWorksheet->getRowIterator() as $row) {
            $cellIterator = $row->getCellIterator();
            $cellIterator->setIterateOnlyExistingCells(false);
            $rowData = [];
            foreach ($cellIterator as $cell) {
                $rowData[] = $this->normalizeValue($cell->getValue());
            }
            while (!empty($rowData) && end($rowData) === '') array_pop($rowData);

            // Skip entirely empty rows
            if (empty(array_filter($rowData))) continue;

            $nonEmptyRowsSeen++;

            // Skip header row(s) and any rows before data starts
            if ($nonEmptyRowsSeen <= $dataStartOffset) continue;

            if (count($rowData) > count($headers)) {
                $rowData = array_slice($rowData, 0, count($headers));
            } elseif (count($rowData) < count($headers)) {
                $rowData = array_pad($rowData, count($headers), '');
            }

            $originalRowId = \Illuminate\Support\Str::uuid()->toString();
            $mapped = $this->mapSingleRow(array_combine($headers, $rowData), $mapping, $listType, $originalRowId);
            foreach ($mapped as $item) yield $item;
        }
    }

    protected function mapSingleRow(array $row, array $mapping, string $listType = 'dual', ?string $originalRowId = null): array
    {
        // --- Separator Regex (comma, semicolon, pipe, slash, newlines, tabs) ---
        $emailSep = '/[,|;\/\n\r\t ]+/';
        $phoneSep = '/[,|;\/\n\r\t]+/';

        $emailRaw = $this->resolveValue($row, $mapping, 'email');
        $phoneRaw = $this->resolveValue($row, $mapping, 'whatsapp_number');

        if (empty($phoneRaw)) {
            $phoneRaw = $this->resolveValue($row, $mapping, 'phone') ?: 
                        $this->resolveValue($row, $mapping, 'whatsapp') ?: 
                        $this->resolveValue($row, $mapping, 'contact');
        }

        $emails = !empty($emailRaw) ? array_filter(array_map('trim', preg_split($emailSep, $emailRaw, -1, PREG_SPLIT_NO_EMPTY))) : [];
        $phonesRawList = !empty($phoneRaw) ? array_filter(array_map('trim', preg_split($phoneSep, $phoneRaw, -1, PREG_SPLIT_NO_EMPTY))) : [];

        // --- WhatsApp Pre-Validation ---
        $waValidator = new \App\Services\WhatsAppValidationService();
        $validPhones = [];
        $invalidPhones = [];

        foreach ($phonesRawList as $p) {
            $res = $waValidator->validate($p);
            if ($res['is_valid']) {
                $validPhones[] = $res['formatted'];
            } else {
                $invalidPhones[] = $p;
            }
        }

        $phones = $validPhones;
        $invalidPhonesStr = !empty($invalidPhones) ? implode(', ', $invalidPhones) : null;

        // If both are empty, and no invalid phones to store, skip
        if (empty($emails) && empty($phones) && empty($invalidPhonesStr)) return [];

        // Total rows = max of both (Sparse Pairing)
        $total = max(count($emails), count($phones));
        if ($total === 0 && !empty($invalidPhonesStr)) $total = 1; // Create at least one row to store the record

        $results = [];

        for ($i = 0; $i < $total; $i++) {
            $email = $emails[$i] ?? null;
            $phone = $phones[$i]  ?? null;

            if ($email === null && $phone === null && $i > 0) continue;

            $data = $this->buildBaseRow($row, $mapping, null, null);
            $data['email'] = $email ? strtolower($email) : null;
            $data['whatsapp_number'] = $phone ?? null;
            $data['original_row_id'] = $originalRowId;

            // Attach invalid phones to the first created row's metadata
            if ($i === 0 && !empty($invalidPhonesStr)) {
                $data['meta'] = array_merge((array)($data['meta'] ?? []), [
                    'phone' => $invalidPhonesStr,
                    'validation_note' => 'Invalid WhatsApp numbers stored in meta'
                ]);
            }

            $results[] = $data;
        }

        return $results;
    }

    /**
     * Build the common base row (name, meta) for a single record.
     * Optionally sets email or phone from a split value.
     */
    protected function buildBaseRow(array $row, array $mapping, ?string $primaryField, ?string $primaryValue): array
    {
        $data = [
            'email' => null,
            'whatsapp_number' => null,
            'name'  => null,
            'meta'  => [],
        ];

        if ($primaryField === 'email' && $primaryValue) {
            $data['email'] = strtolower($primaryValue);
        } elseif ($primaryField === 'whatsapp_number' && $primaryValue) {
            $data['whatsapp_number'] = $primaryValue;
        }

        $emailColumn = $mapping['email'] ?? null;
        $phoneColumn  = $mapping['whatsapp_number'] ?? $mapping['phone'] ?? $mapping['whatsapp'] ?? null;

        // Columns explicitly marked as "Save as Custom Field" by user
        $customColumns = $mapping['_custom_columns'] ?? [];

        $processedExcelColumns = [];

        // 1. Process system-mapped columns (email → name → job_title etc.)
        foreach ($mapping as $systemField => $excelColumn) {
            if (is_array($excelColumn) || str_starts_with($systemField, '_')) continue;
            
            $processedExcelColumns[$excelColumn] = true;

            // Skip the primary split column (already handled above)
            if ($excelColumn === $emailColumn && $systemField === 'email') continue;
            if ($excelColumn === $phoneColumn && in_array($systemField, ['whatsapp_number', 'phone', 'whatsapp'])) continue;

            $value = $this->resolveValue($row, $mapping, $systemField);

            if ($systemField === 'name') {
                $data['name'] = $value ?: null;
            } else {
                $data['meta'][$systemField] = $value;
            }
        }

        // 2. Process columns explicitly marked as "Save as Custom Field"
        foreach ($customColumns as $excelColumn) {
            $processedExcelColumns[$excelColumn] = true;
            if ($excelColumn === $emailColumn || $excelColumn === $phoneColumn) continue;

            $value = trim((string)($row[$excelColumn] ?? ''));
            if ($value !== '') {
                // Use the original Excel column name as the meta key (cleaned up)
                $metaKey = strtolower(trim(preg_replace('/[^a-zA-Z0-9_]/', '_', $excelColumn)));
                $metaKey = preg_replace('/__+/', '_', $metaKey);
                $metaKey = trim($metaKey, '_');
                if (!empty($metaKey)) {
                    $data['meta'][$metaKey] = $value;
                }
            }
        }

        // 3. Capture any remaining Excel columns NOT explicitly skipped and NOT already processed
        // This handles edge cases where Excel has extra columns not shown on mapping page
        foreach ($row as $excelColumn => $value) {
            $excelColumnStr = (string)$excelColumn;
            if (isset($processedExcelColumns[$excelColumnStr]) || $excelColumnStr === '') continue;
            if ($excelColumnStr === $emailColumn || $excelColumnStr === $phoneColumn) continue;

            $valStr = trim((string)$value);
            if ($valStr !== '') {
                $metaKey = strtolower(trim(preg_replace('/[^a-zA-Z0-9_]/', '_', $excelColumnStr)));
                $metaKey = preg_replace('/__+/', '_', $metaKey);
                $metaKey = trim($metaKey, '_');
                if (!empty($metaKey)) {
                    $data['meta'][$metaKey] = $valStr;
                }
            }
        }

        if (empty($data['meta'])) $data['meta'] = null;

        return $data;
    }

    protected function resolveValue(array $row, array $mapping, string $field): string
    {
        $key = $mapping[$field] ?? null;
        if ($key === null) return '';

        // 1. Try as direct key (associative lookup)
        if (isset($row[$key])) return trim((string)$row[$key]);

        // 2. Try as numeric index
        if (is_numeric($key)) {
            $values = array_values($row);
            return trim((string)($values[(int)$key] ?? ''));
        }

        return '';
    }

    protected function detectHeaderIndex(array $rows): int
    {
        $headerKeywords = ['email', 'phone', 'whatsapp', 'name', 'contact', 'joined', 'company', 'title'];
        
        foreach ($rows as $index => $row) {
            $rowString = strtolower(implode(' ', $row));
            $nonEmptyCount = count(array_filter($row, fn($c) => !empty(trim($c))));
            
            if ($nonEmptyCount === 0) continue;

            // 1. Explicitly skip Arzonet Branding
            if (str_contains($rowString, 'arzonet') && str_contains($rowString, 'export')) continue;

            // 2. Data Check: If it looks like actual data (contains '@' or many numbers), it's NOT a header
            $hasEmailPattern = false;
            foreach ($row as $cell) {
                if (str_contains($cell, '@') && str_contains($cell, '.')) {
                    $hasEmailPattern = true;
                    break;
                }
            }
            if ($hasEmailPattern) continue; // This is a data row, keep looking for header

            // 3. Strong Header Match: Contains 'email' word or multiple header keywords
            $matchCount = 0;
            $words = explode(' ', preg_replace('/[^a-z ]/', ' ', $rowString));
            foreach ($headerKeywords as $kw) {
                if (in_array($kw, $words)) $matchCount++;
            }

            if ($matchCount >= 1 && in_array('email', $words)) return $index;
            if ($matchCount >= 2) return $index;

            // 4. Strict density check (only if it doesn't look like data)
            if ($nonEmptyCount >= 4 && $index < 3 && $matchCount >= 1) return $index;
        }
        return -1; // No header found
    }

    /**
     * Clean and normalize data values (handles scientific notation, BOMs, etc.)
     */
    protected function normalizeValue($val): string
    {
        if ($val === null) return '';
        $val = (string) $val;

        // 1. Remove UTF-8 BOM
        $val = preg_replace('/[\x{FEFF}]/u', '', $val);

        // 2. Handle Scientific Notation (e.g., 9.123E+11)
        if (preg_match('/^[0-9\.]+[Ee]\+[0-9]+$/', $val)) {
            $val = sprintf('%.0f', (float)$val);
        }

        return trim($val);
    }

    public function autoDetectMappings(array $headers, array $sampleRows = []): array
    {
        $suggestions = [];
        $fieldKeywords = [
            'email'     => ['email', 'e-mail', 'mail', 'email_address', 'primary_email'],
            'name'      => ['name', 'full_name', 'fullname', 'contact_name', 'customer_name'],
            'first_name'=> ['first_name', 'firstname', 'fname', 'given_name'],
            'last_name' => ['last_name', 'lastname', 'lname', 'surname'],
            'whatsapp_number'=> ['whatsapp', 'wa_number', 'phone', 'mobile', 'cell', 'telephone', 'contact_no', 'ph_no', 'number', 'contact'],
            'company'   => ['company', 'company_name', 'organization', 'business', 'firm', 'employer'],
            'job_title' => ['job_title', 'designation', 'position', 'role', 'title', 'DG'],
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

            // Content-based detection if header didn't match
            if (!empty($sampleRows)) {
                // Skip content detection for obvious non-phone/non-email columns
                $blacklist = ['sr no', 'sr_no', 'id', 'sno', 'sl no', 'serial', 'index', 'status', 'joined', 'created_at'];
                if (in_array($cleanHeader, $blacklist)) continue;

                foreach ($sampleRows as $sampleRow) {
                    $sampleValue = strtolower(trim($sampleRow[$header] ?? ''));
                    if (empty($sampleValue)) continue;

                    // 1. Email Check
                    if (str_contains($sampleValue, '@') && str_contains($sampleValue, '.')) {
                        $suggestions[$header] = 'email';
                        break;
                    } 
                    
                    // 2. Phone/WhatsApp Check (Improved: Min 8 digits, handles common separators)
                    // We remove all non-numeric chars to check the true digit count
                    $digitsOnly = preg_replace('/[^0-9]/', '', $sampleValue);
                    if (strlen($digitsOnly) >= 8 && strlen($digitsOnly) <= 15) {
                        $suggestions[$header] = 'whatsapp_number';
                        break;
                    }
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
