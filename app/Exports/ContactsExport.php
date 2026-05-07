<?php

namespace App\Exports;

use App\Models\Email;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Illuminate\Database\Eloquent\Builder;

class ContactsExport implements FromQuery, WithHeadings, WithMapping, ShouldAutoSize, WithStyles
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
        $base = ['Email Address', 'Full Name', 'Status', 'Subscription', 'Segment', 'Source', 'Joined'];
        $extra = array_map(fn($f) => ucwords(str_replace('_', ' ', $f)), $this->extraFields);
        return array_merge($base, $extra);
    }

    public function map($email): array
    {
        $meta = $email->meta ?? [];
        $base = [
            $email->email,
            $email->name ?? '',
            $email->status,
            $email->subscription_status ?? 'subscribed',
            $email->segment_name ?? '',
            $email->signup_source ?? '',
            $email->created_at?->format('d M Y') ?? '',
        ];
        $extra = array_map(fn($f) => $meta[$f] ?? '', $this->extraFields);
        return array_merge($base, $extra);
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => [
                'font' => ['bold' => true, 'color' => ['argb' => 'FFFFFFFF']],
                'fill' => ['fillType' => 'solid', 'startColor' => ['argb' => 'FF111827']],
            ],
        ];
    }
}
