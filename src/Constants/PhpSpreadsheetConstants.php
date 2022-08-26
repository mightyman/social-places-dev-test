<?php

namespace App\Constants;

use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class PhpSpreadsheetConstants
{
    public const CSV_FILE_TYPE = "csv";
    public const XLSX_FILE_TYPE = "xlsx";
    public const HTML_FILE_TYPE = "html";
    public const EXTENSIONS = [
        '.' . self::CSV_FILE_TYPE,
        '.' . self::XLSX_FILE_TYPE,
        '.' . self::HTML_FILE_TYPE
    ];
    public const DEFAULT_FONT = ['font' => ['size' => 9, 'name' => 'Arial']];
    public const HEADING = ['alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER], 'font' => ['bold' => true]];
    public const BORDER_LEFT = [
        'borders' => [
            'left' => [
                'borderStyle' => Border::BORDER_THIN,
                'color' => ['rgb' => '000000']
            ]
        ]
    ];
    public const BORDER_RIGHT = [
        'borders' => [
            'right' => [
                'borderStyle' => Border::BORDER_THIN,
                'color' => ['rgb' => '000000']
            ]
        ]
    ];
    public const BORDER_BOTTOM = [
        'borders' => [
            'bottom' => [
                'borderStyle' => Border::BORDER_THIN,
                'color' => ['rgb' => '000000']
            ]
        ]
    ];
    public const BORDER_TOP = ['borders' => ['top' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => '000000']]]];
    public const BORDER_SIDES = [
        'borders' => [
            'left' => [
                'borderStyle' => Border::BORDER_THIN,
                'color' => ['rgb' => '000000']
            ],
            'right' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => '000000']]
        ]
    ];
    public const BORDER = [
        'borders' => [
            'allBorders' => [
                'borderStyle' => Border::BORDER_THIN,
                'color' => ['rgb' => '000000']
            ]
        ]
    ];
    public const GO_REVIEW_RATING = [
        'fill' => ['fillType' => Fill::FILL_SOLID, 'color' => ['rgb' => 'FFCC99']],
        'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        'font' => ['bold' => true]
    ];
    public const TEXT_ALIGN_LEFT = ['alignment' => ['horizontal' => Alignment::HORIZONTAL_LEFT]];
    public const HEADING_FILL = ['fill' => ['fillType' => Fill::FILL_SOLID, 'color' => ['rgb' => 'cccccc']]];
    public const SUB_HEADING_FILL = ['fill' => ['fillType' => Fill::FILL_SOLID, 'color' => ['rgb' => 'f5f5f5']]];
    public const NEUTRAL_FILL = ['fill' => ['fillType' => Fill::FILL_SOLID, 'color' => ['rgb' => 'ffffcc']]];
    public const POSITIVE_FILL = ['fill' => ['fillType' => Fill::FILL_SOLID, 'color' => ['rgb' => 'ccffcc']]];
    public const NEGATIVE_FILL = ['fill' => ['fillType' => Fill::FILL_SOLID, 'color' => ['rgb' => 'ff8989']]];
    public const DEFAULT_MAX_COLUMN_WIDTH = 100;

    public const ERROR_STYLE = [
        'fill' => ['fillType' => Fill::FILL_SOLID, 'color' => ['rgb' => 'ffc6c4']],
        'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        'font' => ['bold' => true],
    ];

    public const MAX_ROWS_WITH_CONTENT = 100;

}
