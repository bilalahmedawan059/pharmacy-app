<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;

class RecordsExport implements FromArray, WithHeadings, WithTitle
{
    private $headings;
    private $rows;
    private $sheetTitle;

    public function __construct(array $headings, array $rows, $sheetTitle = 'Export')
    {
        $this->headings = $headings;
        $this->rows = $rows;
        $this->sheetTitle = substr($sheetTitle, 0, 31);
    }

    public function headings(): array
    {
        return $this->headings;
    }

    public function array(): array
    {
        return array_map(function ($row) {
            return array_map(function ($value) {
                if (is_string($value) && preg_match('/^[\s]*[=+\-@]/', $value)) {
                    return "'" . $value;
                }

                return $value;
            }, $row);
        }, $this->rows);
    }

    public function title(): string
    {
        return $this->sheetTitle;
    }
}