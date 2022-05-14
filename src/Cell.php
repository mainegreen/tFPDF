<?php
declare(strict_types=1);

namespace Mg\Tfpdf;

/**
 * Cells behaviour is as follows:
 * <ul>
 * <li>The pdf has a number of built in current properties, that will not be changed by writing a cell, unlike
 *   using some of the lower level writing methods. These properties are:
 *  <ul>
 *   <li>draw color, fill color, text color</li>
 *   <li>font size, font style</li>
 *  </ul>
 * </li>
 * <li>If you don't specify cell width then it will be as wide as remaining writable amount</li>
 * <li>If you don't specify cell height then it will be set to the font size we will write cell at, unless
 *  autowrap is set to true</li>
 * <li>If you don't specify a font size, then we write at whatever the pdf's set font size is</li>
 * <li>AutoShrink is ignored if we are doing autowrap, even if we have limited number of rows</li>
 * <li>Ln will prevent cells from overlapping (including border) unless lnOverlaps is set to false</li>
 * </ul>
 */
class Cell
{

    public AlignEnum $align = AlignEnum::LEFT;
    public bool $allowAutoWrap = false;
    public ?float $autoWrapLineHeight = null;
    public ?int $autoWrapMaxLines = null;
    public bool $autoShrink = false;
    public ?int $autoShrinkMinFontSize = null;
    public string $border = '';
    public ?Color $drawColor = null;
    public bool $fill = true;
    public ?Color $fillColor = null;
    public ?int $fontSize = null;
    public bool $fontBold = false;
    public bool $fontItalic = false;
    public bool $fontUnderline = false;
    public ?string $fontFamily = null;
    public ?Color $fontColor = null;
    public ?float $height = null;
    public string $link = '';
    public bool $ln = false;
    public bool $lnOverlaps = false;
    public string $text = '';
    public float $width = 0;
    public bool $widthIsPercentage = false;
    public ?float $x = null;
    public bool $xIsPercentage = false;

    public function border(int $l, int $r, int $t, int $b): Cell
    {
        $this->border = '';
        if ($l) {
            $this->border .= 'L';
        }
        if ($r) {
            $this->border .= 'R';
        }
        if ($t) {
            $this->border .= 'T';
        }
        if ($b) {
            $this->border .= 'B';
        }
        if ($this->border == '') {
            $this->border = '0';
        }
        return $this;
    }

    public function borderFrame(bool $bool): Cell
    {
        $this->border = $bool ? '1' : '0';
        return $this;
    }

}