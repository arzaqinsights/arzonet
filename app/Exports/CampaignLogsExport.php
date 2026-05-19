<?php

namespace App\Exports;

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
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class CampaignLogsExport implements FromQuery, WithHeadings, WithMapping, ShouldAutoSize, WithStyles, WithColumnFormatting, WithDrawings, WithCustomStartCell, WithEvents
{
    protected $query;
    protected array $metaKeys;

    public function __construct($query, array $metaKeys = [])
    {
        $this->query = $query;
        $this->metaKeys = $metaKeys;
    }

    public function query()
    {
        return $this->query;
    }

    public function headings(): array
    {
        $base = ['Full Name', 'Email Address', 'Status', 'Opens', 'Clicks', 'Segment', 'Tags', 'Sent At'];
        $metaHeaders = array_map(fn($f) => ucwords(str_replace('_', ' ', $f)), $this->metaKeys);

        return array_merge($base, $metaHeaders);
    }

    public function map($log): array
    {
        $email = $log->email;
        $meta = $email ? ($email->meta ?? []) : [];

        $base = [
            $email->name ?? '—',
            $log->email_address,
            strtoupper($log->status),
            $log->open_count,
            $log->click_count,
            $email->segment_name ?? '—',
            is_array($email->tags) ? implode(', ', $email->tags) : ($email->tags ?? '—'),
            $log->created_at?->format('d M Y H:i:s') ?? '',
        ];

        $extra = array_map(fn($f) => $meta[$f] ?? '—', $this->metaKeys);

        return array_merge($base, $extra);
    }

    public function styles(Worksheet $sheet): array
    {
        $sheet->getStyle('A1:L2')->getFill()
            ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
            ->getStartColor()->setARGB('FFFFFFFF');

        $sheet->setCellValue('G1', 'Export Date:');
        $sheet->setCellValue('H1', now()->format('d M Y'));
        $sheet->setCellValue('G2', 'Total Logs:');
        $sheet->setCellValue('H2', (clone $this->query)->reorder()->count());
        $sheet->getStyle('G1:H2')->getFont()->setBold(true)->setSize(9);

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

        $path = public_path('images/logo/logo.png');
        if (file_exists($path)) {
            $drawing->setPath($path);
        } else {
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

                // Apply Zebra Striping
                for ($i = 4; $i <= $highestRow; $i++) {
                    if ($i % 2 == 0) {
                        $sheet->getStyle("A{$i}:{$highestColumn}{$i}")
                            ->getFill()
                            ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                            ->getStartColor()->setARGB('FFFFF2E6');
                    }
                }

                // Add borders
                $sheet->getStyle("A3:{$highestColumn}{$highestRow}")
                    ->getBorders()->getAllBorders()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);
                $sheet->getStyle("A3:{$highestColumn}{$highestRow}")
                    ->getBorders()->getAllBorders()->getColor()->setARGB('FFD1D5DB');

                $sheet->getStyle("A3:{$highestColumn}3")
                    ->getBorders()->getAllBorders()->getColor()->setARGB('FF111827');

                $sheet->getRowDimension(1)->setRowHeight(20);
                $sheet->getRowDimension(2)->setRowHeight(20);
            },
        ];
    }

    public function columnFormats(): array
    {
        return [];
    }
}
