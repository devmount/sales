<?php

namespace App\Enums;

enum DocumentColor: int
{
    case MAIN   = 0;
    case ACCENT = 1;
    case TEXT   = 2;
    case GRAY   = 3;
    case DARK   = 4;
    case LIGHT  = 5;
    case LINE   = 6;
    case LINE2  = 7;
    case LINE3  = 8;
    case LINE4  = 9;
    case COL1   = 10;
    case COL2   = 11;
    case COL3   = 12;
    case COL4   = 13;

    /**
     * HEX definition
     *
     * @return string
     */
    public function hex(): string
    {
        return match ($this) {
            self::MAIN   => '#002033',
            self::ACCENT => '#3c88b8',
            self::TEXT   => '#c5d6e0',
            self::GRAY   => '#5c666d',
            self::DARK   => '#222222',
            self::LIGHT  => '#ffffff',
            self::LINE   => '#eeeeee',
            self::LINE2  => '#265d7f',
            self::LINE3  => '#66808e',
            self::LINE4  => '#062D42',
            self::COL1   => '#cccccc',
            self::COL2   => '#dddddd',
            self::COL3   => '#eeeeee',
            self::COL4   => '#bbbbbb',
        };
    }

    /**
     * RGB representation
     *
     * @return int[] [r, g, b]
     */
    public function rgb(): array
    {
        return sscanf($this->hex(), "#%02x%02x%02x");
    }
}
