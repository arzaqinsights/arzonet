<?php

namespace App\Exports;

use App\Models\Email;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithDrawings;
use Maatwebsite\Excel\Concerns\WithCustomStartCell;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Illuminate\Database\Eloquent\Builder;

class ContactsExport implements FromQuery, WithHeadings, WithMapping, ShouldAutoSize, WithStyles, WithColumnFormatting, WithDrawings, WithCustomStartCell, WithEvents
{
    protected $query;
    protected array $extraFields;

    public function __construct($query, array $extraFields = [])
    {
        $this->query = $query;
        $this->extraFields = $extraFields;
    }

    public function query()
    {
        return $this->query;
    }

    public function headings(): array
    {
        // Order: Name, Email, Phone, Tags, Health, Valid Reason, [Extras], Joined
        $base = ['Full Name', 'Email Address', 'Phone', 'Tags', 'Health', 'Valid Reason'];
        $extra = array_map(fn($f) => ucwords(str_replace('_', ' ', $f)), $this->extraFields);
        $tail = ['Joined'];

        return array_merge($base, $extra, $tail);
    }

    public function map($email): array
    {
        $meta = $email->meta ?? [];

        // Use whatsapp_number, fallback to meta['phone'] if exists
        $phoneValue = $email->whatsapp_number ?: ($meta['phone'] ?? '');
        $tagsValue = is_array($email->tags) ? implode(', ', $email->tags) : ($email->tags ?? '');
        $healthValue = match($email->email_status ?? $email->status) {
            'clean', 'valid' => 'Clean',
            'risky', 'suspicious' => 'Risky',
            'role_based' => 'Role',
            'disposable' => 'Temp',
            'invalid', 'hard_bounce' => 'Dead',
            'complaint' => 'Spam',
            'blocked' => 'Banned',
            default => 'Unknown',
        };
        // $validReasonValue = $email->validation_reason ?: ($email->reason ?? '');

        $base = [
            $email->name ?? '',
            $email->email,
            $phoneValue,
            $tagsValue,
            $healthValue,
            // $validReasonValue,
        ];

        $extra = array_map(fn($f) => $meta[$f] ?? '', $this->extraFields);

        $tail = [
            $email->created_at?->format('d M Y') ?? '',
        ];

        return array_merge($base, $extra, $tail);
    }

    public function styles(Worksheet $sheet): array
    {
        // Ensure the entire top area is clean white
        $sheet->getStyle('A1:S2')->getFill()
            ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
            ->getStartColor()->setARGB('FFFFFFFF');

        // Add Title and Info at the top
        // $sheet->mergeCells('C1:F2');
        // $sheet->setCellValue('C1', 'Official Arzonet Export: Verified & Optimized Intelligence Data (Multi-layer Validation).');
        // $sheet->getStyle('C1')->getFont()->setBold(true)->setSize(12)->getColor()->setARGB('FF111827');
        // $sheet->getStyle('C1')->getAlignment()->setHorizontal('left')->setVertical('center')->setWrapText(true);

        $sheet->setCellValue('H1', 'Export Date:');
        $sheet->setCellValue('I1', now()->format('d M Y'));
        $sheet->setCellValue('H2', 'Total Records:');
        $sheet->setCellValue('I2', (clone $this->query)->reorder()->count());
        $sheet->getStyle('H1:I2')->getFont()->setBold(true)->setSize(9);

        // Style the Data Header Row (Starts at Row 3) - BRAND ORANGE
        return [
            3 => [
                'font' => ['bold' => true, 'color' => ['argb' => 'FFFFFFFF']],
                'fill' => [
                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'startColor' => ['argb' => 'FFFF6B00'], // Arzonet Brand Orange
                ]
            ],
        ];
    }

    public function drawings()
    {
        $drawing = new Drawing();
        $drawing->setName('Arzonet Logo');
        $drawing->setDescription('Official Arzonet Logo');

        // Use the long width logo for the header
        $path = public_path('images/logo/logo.png');
        if (file_exists($path)) {
            $drawing->setPath($path);
        } else {
            // Fallback to square if main logo not found
            $drawing->setPath(public_path('images/logo/square-logo.png'));
        }

        $drawing->setHeight(35);
        $drawing->setCoordinates('A1');
        $drawing->setOffsetX(4);
        $drawing->setOffsetY(10);

        return $drawing;
    }

    public function startCell(): string
    {
        return 'A3';
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                $highestRow = $sheet->getHighestRow();
                $highestColumn = $sheet->getHighestColumn();

                // Apply Branded Zebra Striping (10% Orange) from row 6 onwards
                for ($i = 4; $i <= $highestRow; $i++) {
                    if ($i % 2 == 0) {
                        $sheet->getStyle("A{$i}:{$highestColumn}{$i}")
                            ->getFill()
                            ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                            ->getStartColor()->setARGB('FFFFF2E6'); // 10% Arzonet Orange opacity approx
                    }
                }

                // Add borders to everything from row 3 down
                $sheet->getStyle("A3:{$highestColumn}{$highestRow}")
                    ->getBorders()->getAllBorders()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);
                $sheet->getStyle("A3:{$highestColumn}{$highestRow}")
                    ->getBorders()->getAllBorders()->getColor()->setARGB('FFD1D5DB'); // Light grey borders
    
                // Style header specifically
                $sheet->getStyle("A3:{$highestColumn}3")
                    ->getBorders()->getAllBorders()->getColor()->setARGB('FF111827');

                // Set row heights
                $sheet->getRowDimension(1)->setRowHeight(20);
                $sheet->getRowDimension(2)->setRowHeight(20);
                // $sheet->getRowDimension(3)->setRowHeight(20);
                // $sheet->getRowDimension(4)->setRowHeight(20);
            },
        ];
    }

    public function columnFormats(): array
    {
        return [
            'C' => '0', // Phone is now Column C
        ];
    }
}
