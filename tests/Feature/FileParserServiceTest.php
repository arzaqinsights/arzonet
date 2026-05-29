<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Services\FileParserService;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class FileParserServiceTest extends TestCase
{
    public function test_xlsx_sheet_selection_tie_breaker_chooses_largest_sheet()
    {
        // Create a new Spreadsheet
        $spreadsheet = new Spreadsheet();
        
        // Sheet 1 (smaller index/reference sheet) - active sheet by default
        $sheet1 = $spreadsheet->getActiveSheet();
        $sheet1->setTitle('Intro Sheet');
        $sheet1->setCellValue('A1', 'email');
        $sheet1->setCellValue('B1', 'name');
        for ($i = 2; $i <= 5; $i++) {
            $sheet1->setCellValue('A' . $i, 'intro' . $i . '@example.com');
            $sheet1->setCellValue('B' . $i, 'Intro User ' . $i);
        }

        // Sheet 2 (main sheet - larger, has more rows)
        $sheet2 = $spreadsheet->createSheet();
        $sheet2->setTitle('Main Contacts');
        $sheet2->setCellValue('A1', 'email');
        $sheet2->setCellValue('B1', 'name');
        for ($i = 2; $i <= 15; $i++) {
            $sheet2->setCellValue('A' . $i, 'main' . $i . '@example.com');
            $sheet2->setCellValue('B' . $i, 'Main User ' . $i);
        }

        // Save Spreadsheet to a temporary file path
        $tempPath = tempnam(sys_get_temp_dir(), 'test_import') . '.xlsx';
        $writer = new Xlsx($spreadsheet);
        $writer->save($tempPath);

        // 1. Test parse() - should choose Sheet 2 because of tie-breaker (15 rows > 5 rows)
        $uploadedFile = new UploadedFile($tempPath, 'test_import.xlsx', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', null, true);
        
        $parser = new FileParserService();
        $parsed = $parser->parse($uploadedFile);

        // Assert that the parsed rows match the main sheet (should have 14 rows, excluding header)
        $this->assertEquals(14, $parsed['count']);
        $this->assertEquals('main2@example.com', $parsed['rows'][0]['email']);

        // 2. Test streamXlsx()
        // Save to Storage local disk to stream
        $storagePath = 'email-lists/test_stream.xlsx';
        Storage::disk('local')->put($storagePath, file_get_contents($tempPath));

        $mapping = [
            'email' => 'email',
            'name' => 'name',
        ];

        $streamed = iterator_to_array($parser->streamStoredFile($storagePath, $mapping));

        // Clean up
        @unlink($tempPath);
        Storage::disk('local')->delete($storagePath);

        // Assert that the streamed rows match the main sheet
        $this->assertCount(14, $streamed);
        $this->assertEquals('main2@example.com', $streamed[0]['email']);
    }
}
