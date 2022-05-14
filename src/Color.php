<?php
declare(strict_types=1);

namespace Mgrn\Tfpdf;

class Color
{
    public readonly float $r;
    public readonly float $g;
    public readonly float $b;

    public function __construct(float $r = 0, float $g = -1, float $b = -1)
    {
        $this->r = $r;
        $this->g = $g;
        $this->b = $b;
    }

    /**
     * Create color object from rgb hexadecimal value. Shortened values not allowed, aka no 'FFF' or 'fff' allowed
     *
     * @param string $string
     * @return Color
     */
    public static function fromRGBHex(string $string): Color
    {
        $r = 0;
        $g = -1;
        $b = -1;
        if (preg_match('/^([0-9A-Fa-f]{2})([0-9A-Fa-f]{2})([0-9A-Fa-f]{2})$/i', $string, $matches)) {
            $red = strtolower($matches[1]);
            $green = strtolower($matches[2]);
            $blue = strtolower($matches[3]);
            $r = hexdec($red);
            $g = hexdec($green);
            $b = hexdec($blue);
        }
        return new Color($r, $g, $b);
    }

    /**
     * Create a color from the color enum object
     *
     * @param ColorsEnum $color
     * @return Color
     */
    public static function fromColor(ColorsEnum $color): Color
    {
        $r = 0;
        $g = -1;
        $b = -1;
        switch ($color) {
            case ColorsEnum::AQUA:
                $r = 0;
                $g = 255;
                $b = 255;
                break;
            case ColorsEnum::BLACK:
                $r = 0;
                $g = 0;
                $b = 0;
                break;
            case ColorsEnum::BLUE:
                $r = 0;
                $g = 0;
                $b = 255;
                break;
            case ColorsEnum::DIM_GRAY:
                $r = 105;
                $g = 105;
                $b = 105;
                break;
            case ColorsEnum::FUCHSIA:
                $r = 255;
                $g = 0;
                $b = 255;
                break;
            case ColorsEnum::GRAY:
                $r = 128;
                $g = 128;
                $b = 128;
                break;
            case ColorsEnum::GREEN:
                $r = 0;
                $g = 128;
                $b = 0;
                break;
            case ColorsEnum::LIGHT_BLUE:
                $r = 173;
                $g = 216;
                $b = 230;
                break;
            case ColorsEnum::LIGHT_CORAL:
                $r = 240;
                $g = 128;
                $b = 128;
                break;
            case ColorsEnum::LIGHT_GRAY :
                $r = 211;
                $g = 211;
                $b = 211;
                break;
            case ColorsEnum::LIME:
                $r = 0;
                $g = 255;
                $b = 0;
                break;
            case ColorsEnum::MAROON:
                $r = 128;
                $g = 0;
                $b = 0;
                break;
            case ColorsEnum::NAVY:
                $r = 0;
                $g = 0;
                $b = 128;
                break;
            case ColorsEnum::OLIVE:
                $r = 128;
                $g = 128;
                $b = 0;
                break;
            case ColorsEnum::RED:
                $r = 255;
                $g = 0;
                $b = 0;
                break;
            case ColorsEnum::PURPLE:
                $r = 128;
                $g = 0;
                $b = 128;
                break;
            case ColorsEnum::SILVER:
                $r = 192;
                $g = 192;
                $b = 192;
                break;
            case ColorsEnum::SKY_BLUE:
                $r = 135;
                $g = 206;
                $b = 235;
                break;
            case ColorsEnum::TEAL:
                $r = 0;
                $g = 128;
                $b = 128;
                break;
            case ColorsEnum::WHITE:
                $r = 255;
                $g = 255;
                $b = 255;
                break;
            case ColorsEnum::WHITE_SMOKE :
                $r = 245;
                $g = 245;
                $b = 245;
                break;
            case ColorsEnum::YELLOW:
                $r = 255;
                $g = 255;
                $b = 0;
                break;
        }
        return new Color($r, $g, $b);
    }

    /**
     * Returns a color object, primarily from FPDF's internal Drawcolor property
     *
     * @param string $string
     * @return Color
     */
    public static function fromString(string $string): Color
    {
        $r = 0;
        $g = -1;
        $b = -1;
        if (preg_match('/^([-]?[\d](.[\d]{3})?) ([-]?[\d](.[\d]{3})?) ([-]?[\d](.[\d]{3})?) rg$/i', $string, $matches)) {
            $r = floor($matches[1] * 255);
            $g = floor($matches[3] * 255);
            $b = floor($matches[5] * 255);
        }
        if (preg_match('/^([-]?[\d](.[\d]{3})?) g$/i', $string, $matches)) {
            $r = floor($matches[1] * 255);
        }
        return new Color($r, $g, $b);
    }
}