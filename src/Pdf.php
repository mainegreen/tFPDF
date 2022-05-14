<?php
declare(strict_types=1);

namespace Mg\Tfpdf;

use JetBrains\PhpStorm\Pure;

class Pdf extends \tFPDF
{

    const ORIENTATION_LANDSCAPE = 'L';
    const ORIENTATION_PORTRAIT = 'P';

    const UNIT_MM = 'mm';
    const UNIT_PT = 'pt';
    const UNIT_CM = 'cm';
    const UNIT_IN = 'in';

    const SIZE_A3 = 'a3';
    const SIZE_A4 = 'a4';
    const SIZE_A5 = 'a5';
    const SIZE_LETTER = 'letter';
    const SIZE_LEGAL = 'legal';
    const SIZE_LEDGER = [792,1224];

    protected array $rollbackPoints = [];
    protected mixed $header; // header closure or nothing.
    protected mixed $footer; // footer closure or nothing.

    final public function __construct($orientation = 'P', $unit = 'mm', $size = 'A4')
    {
        parent::__construct($orientation, $unit, $size);
        $this->_init();
    }

    protected function _init()
    {
    }

    /**
     * Erase any stored rollback points
     *
     * @return Pdf
     */
    public function clearRollbackPoints(): Pdf
    {
        $this->rollbackPoints = [];
        return $this;
    }

    /**
     * An attempt at getting how high a text block will be if autowrap was on for a particular string
     *
     * @param string $txt
     * @param float $writableWidth
     * @return float
     */
    public function getTextHeight(string $txt = '', float $writableWidth = 0): float
    {
        $totalWidth = $this->GetStringWidth($txt) + $this->cMargin * 2;
        return ceil($totalWidth / $writableWidth) * $this->FontSize;
    }

    /**
     * Rollback to a particular point, or if $point is set to true, rollback to last set point
     *
     * @param $point mixed Point to rollback to, or true if last set point
     */
    public function rollbackTo(mixed $point = true): bool
    {
        if ($point === true) {
            end($this->rollbackPoints);
            $point = key($this->rollbackPoints);
        }
        if (isset($this->rollbackPoints[$point])) {
            $rollbackObject = $this->rollbackPoints[$point];
            $props = get_object_vars($rollbackObject);
            foreach ($props as $k => $v) {
                $this->{$k} = $v;
            }
            foreach ($this->rollbackPoints as $k => $v) {
                if ($k >= $point) {
                    unset($this->rollbackPoints[$k]);
                }
            }
            return true;
        }
        return false;
    }

    /**
     * @param string $filename
     * @param bool $additionTimestampFormat Set to true to add a timestamp to filename
     * @return void
     */
    public function sendToBrowser(string $filename, bool $additionTimestampFormat = false): void
    {
        if ($additionTimestampFormat !== false) {
            $filename .= '.' . date("mdYhms");
        }
        $filename .= '.pdf';
        header("Cache-Control: no-cache, no-store, must-revalidate");
        $this->Output($filename, "D");
    }

    /**
     * @return string
     */
    public function sendToString(): string
    {
        return $this->Output(false, 'S');
    }

    public function setFontFamily(string $family): Pdf
    {
        $fontSize = $this->FontSizePt;
        $fontStyle = $this->FontStyle;
        $this->SetFont($family, $fontStyle, $fontSize);
        return $this;
    }

    public function SetFont($family, $style = '', $size = 0): Pdf
    {
        parent::SetFont($family, $style, $size);
        return $this;
    }

    /**
     * Allows setting the footer as a closure, instead of requiring the footer be a method in an overriden base class
     *
     * @param $closure
     * @return $this
     * @throws \ReflectionException
     */
    public function setFooterClosure($closure): Pdf
    {
        if ($closure === false) {
            $this->footer = null;
            return $this;
        }
        if (!is_a($closure, 'Closure')) {
            throw new \Exception("closure be a lambda (anonymous) function");
        }
        $reflection = new \ReflectionFunction($closure);
        $numberOfParams = $reflection->getNumberOfParameters();
        if ($numberOfParams != 1) {
            throw new \Exception('closure function must implement 1 parameter: (Pdf) pdf');
        }
        $parameters = $reflection->getParameters();
        if ($parameters[0]->name != 'pdf') {
            throw new \Exception('closure function\'s first parameter must be \'pdf\' which will an instance of the pdf object');
        }
        $this->footer = $closure;
        return $this;
    }

    /**
     * Allows setting the header as a closure, instead of requiring the footer be a method in an overriden base class
     *
     * @param $closure
     * @return $this
     * @throws \ReflectionException
     */
    public function setHeaderClosure($closure): Pdf
    {
        if ($closure === false) {
            $this->header = null;
            return $this;
        }
        if (!is_a($closure, 'Closure')) {
            throw new \Exception("closure be a lambda (anonymous) function");
        }
        $reflection = new \ReflectionFunction($closure);
        $numberOfParams = $reflection->getNumberOfParameters();
        if ($numberOfParams != 1) {
            throw new \Exception('closure function must implement 1 parameter: (Pdf) pdf');
        }
        $parameters = $reflection->getParameters();
        if ($parameters[0]->name != 'pdf') {
            throw new \Exception('closure function\'s first parameter must be \'pdf\' which will an instance of the pdf object');
        }
        $this->header = $closure;
        return $this;
    }

    /**
     * Sets a rollback point to the current PDF state.
     * Returns an int id that can be used to rollback to the current state.
     *
     * @return int
     */
    public function setRollbackPoint(): int
    {
        $o = $this->getStateObject();
        $id = $this->uniqueId();
        $this->rollbackPoints[$id] = $o;
        return $id;
    }

    #[Pure] protected function getStateObject(): \stdClass
    {
        $o = new \stdClass();
        $props = get_object_vars($this);
        foreach ($props as $k => $v) {
            if (!in_array($k, ['rollbackPoints', 'header', 'footer'])) {
                $o->{$k} = $v;
            }
        }
        return $o;
    }

    // region Some basic overrides, as well as more methods, with better returns
    /**
     * Outputs the cell as defined, and returns the y where the next write will default to
     *
     * @param Cell $cell
     * @return float
     */
    public function writeCell(Cell $cell): float
    {
        $aStartingY = $this->GetY();
        $originalUniFontSubset = $this->unifontSubset;
        $cellState = $this->getCellState();
        $cell = clone $cell; // To avoid changing the original
        // region Clean Up Cell Properties
        switch ($cell->align) {
            case AlignEnum::LEFT:
                $align = "L";
                break;
            case AlignEnum::CENTER:
                $align = "C";
                break;
            case AlignEnum::RIGHT:
                $align = "R";
                break;
        }
        if (is_null($cell->fontFamily)) {
            $cell->fontFamily = $cellState->fontFamily;
        }
        $this->unifontSubset = is_null($cell->fontFamily) ? false : ($this->fonts[$cell->fontFamily]['type']=='TTF' ? true : false);
        if (!is_null($cell->drawColor)) {
            $this->setColorForDrawing($cell->drawColor);
        }
        if (!is_null($cell->fillColor)) {
            $this->setColorForFilling($cell->fillColor);
        }
        if (!is_null($cell->fontColor)) {
            $this->setColorForFont($cell->fontColor);
        }
        if (!is_null($cell->width)) {
            if ($cell->widthIsPercentage) {
                $cell->width = (int)floor($cell->width * $this->getWritableWidth() / 100);
            }
        }
        else {
            $cell->width=$this->getRemainingWidth();
        }
        // We always have a width below this point. FYI
        if (is_null($cell->height) && !$cell->allowAutoWrap) {
            if ($cell->fontSize) {
                $cell->height = $cell->fontSize;
            } else {
                $cell->height = $this->FontSizePt;
            }
        }
        if ($cell->fontBold !== false || $cell->fontItalic !== false || $cell->fontUnderline !== false) {
            $style = '';
            $style .= $cell->fontBold !== false ? 'B' : '';
            $style .= $cell->fontItalic !== false ? 'I' : '';
            $style .= $cell->fontUnderline !== false ? 'U' : '';
            $this->setFontStyle($style);
        }
        if ($cell->autoShrink && !$cell->allowAutoWrap) {
            $currentFontSize = !is_null($cell->fontSize) ? $cell->fontSize : $cellState->fontSize;
            if (is_null($cell->height)) { // fix height because otherwise we might get mixed height rows!
                $cell->height = $currentFontSize;
            }
            $foundSize = false;
            $minFontSize = is_numeric($cell->autoShrinkMinFontSize) ? (int)$cell->autoShrinkMinFontSize : 6;
            $minFontSize = $minFontSize < 6 ? 6 : $minFontSize;
            while (!$foundSize) {
                $this->setFontSize($currentFontSize);
                $tryWidth = $this->GetStringWidth($cell->text);
                if ($tryWidth > ($cell->width - (2 * $this->cMargin)) && $currentFontSize > $minFontSize) {
                    $currentFontSize -= 1;
                } else {
                    $foundSize = true;
                    $cell->fontSize = $currentFontSize;
                }
            }
        }
        if (!is_null($cell->fontSize)) {
            $this->setFontSize($cell->fontSize);
        }
        if (!is_null($cell->x)) {
            if ($cell->xIsPercentage) {
                $writableWidth = $this->getWritableWidth();
                $cell->x = floor(($cell->x / 100) * $writableWidth + $this->lMargin);
            }
            $this->SetX($cell->x);
        }
        // endregion
        // region Output when there is no autowrap
        if (!$cell->allowAutoWrap) {
            $cell->text = $this->trimTextToWidth($cell->text, $cell->width - (2 * $this->cMargin));
            $y = $this->xCell(
                $cell->width,
                $cell->height,
                $cell->text,
                $cell->border,
                $align,
                $cell->fill
            );
        }
        // endregion
        // region We are in autowrap mode
        else {
            // region Figure out how many lines of text are allowed, and get lines
            if (is_null($cell->autoWrapLineHeight)) {
                $cell->autoWrapLineHeight = $cell->fontSize;
            }
            // First, get the upper limit of allowed lines
            if (!is_null($cell->autoWrapMaxLines)) {
                $allowedLines = $cell->autoWrapMaxLines; // an upper limit
            }
            elseif (!is_null($cell->height)) {
                $allowedLines = (int)floor($cell->height / $cell->autoWrapLineHeight);
            }
            else {
                $allowedLines = -1; // special case - infinite lines allowed
            }
            if (!is_null($cell->height) && !is_null($cell->autoWrapMaxLines)) { // we may need to cut back max
                $cellHeightAllowedLines = (int)floor($cell->height / $cell->autoWrapLineHeight);
                $allowedLines = $allowedLines < $cellHeightAllowedLines ? $allowedLines : $cellHeightAllowedLines; // use the lesser
            }
            $lines = $this->getChoppedString($cell->text,$allowedLines,$cell->width - (2 * $this->cMargin));
            // endregion

            if (is_null($cell->height)) {
                $cell->height = count($lines) * $cell->autoWrapLineHeight;
            }

            if (count($lines) > 1) {
                // first a big empty cell
                $x = $this->getX();
                $originY = $this->GetY();
                $y = $this->xCell(
                    $cell->width,
                    $cell->height,
                    '',
                    $cell->border,
                    $align,
                    $cell->fill
                );
                // Now the inside cells
                if ($cell->border === '1' || stripos($cell->border, 'b') !== false) {
                    $x = $x + 1;
                }
                $lineCounter = 0;
                foreach ($lines as $line) {
                    $this->SetX($x);
                    $this->SetY($originY + ($cell->autoWrapLineHeight * $lineCounter));
                    $this->xCell(
                        $cell->width,
                        $cell->autoWrapLineHeight,
                        $line,
                        0,
                        $align,
                        false
                    );
                    $lineCounter++;
                }
                $this->SetY($originY);
            }
            else {
                $y = $this->xCell(
                    $cell->width,
                    $cell->height,
                    $cell->text,
                    $cell->border,
                    $align,
                    $cell->fill
                );
            }
        }
        // endregion
        if ($cell->ln) {
            if ($cell->border === '1' || stripos($cell->border, 'b') !== false) {
                $this->Ln($cell->height + 1);
                $y = $this->GetY();
            } else {
                $this->Ln($cell->height);
                $y = $this->GetY();
            }
            if ($cell->lnOverlaps) {
                $y = $y - 1;
                $this->SetY($y);
            }
        }
        $this->SetDrawColor($cellState->drawColor->r, $cellState->drawColor->g, $cellState->drawColor->b);
        $this->SetFillColor($cellState->fillColor->r, $cellState->fillColor->g, $cellState->fillColor->b);
        $this->SetTextColor($cellState->fontColor->r, $cellState->fontColor->g, $cellState->fontColor->b);
        $this->setFontSize($cellState->fontSize);
        $style = '';
        $style .= $cellState->fontBold !== false ? 'B' : '';
        $style .= $cellState->fontItalic !== false ? 'I' : '';
        $style .= $cellState->fontUnderline !== false ? 'U' : '';
        $this->setFontStyle($style);
        $this->unifontSubset = $originalUniFontSubset;
        return (float)$y;
    }

    protected function uniqueId(): int
    {
        static $i = 0;
        $i += 1;
        return $i;
    }

    /**
     * Returns a cell with state set to PDF's current drawing state.
     * Consists mostly of color and style. Does not represent things like was last write have a line break
     *
     * @return Cell
     */
    public function getCellState(): Cell
    {
        $cell = new Cell();
        $cell->drawColor = Color::fromString($this->DrawColor);
        $cell->fillColor = Color::fromString($this->FillColor);
        $cell->fontSize = (int)$this->FontSizePt;
        $cell->fontBold = (str_contains($this->FontStyle, 'B'));
        $cell->fontItalic = (str_contains($this->FontStyle, 'I'));
        $cell->fontUnderline = (bool)$this->underline;
        $cell->fontFamily = empty($this->FontFamily) ? null : (string)$this->FontFamily;
        $cell->fontColor = Color::fromString($this->TextColor);
        return $cell;
    }

    public function setColorForDrawing(Color $color): Pdf
    {
        $this->SetDrawColor($color->r, $color->g, $color->b);
        return $this;
    }

    // endregion

    public function setColorForFilling(Color $color): Pdf
    {
        $this->SetFillColor($color->r, $color->g, $color->b);
        return $this;
    }

    public function setColorForFont(Color $color): Pdf
    {
        $this->SetTextColor($color->r, $color->g, $color->b);
        return $this;
    }

    /**
     * How much width is on page to write on, in total, with margins subtracted
     *
     * @return float
     */
    public function getWritableWidth(): float
    {
        return (float)($this->w - $this->rMargin - $this->lMargin);
    }

    public function setFontStyle($style = ''): Pdf
    {
        $fontSize = $this->FontSizePt;
        $fontFamily = $this->FontFamily;
        $this->SetFont($fontFamily, $style, $fontSize);
        return $this;
    }

    /**
     * How much width is left in page to write on, even accounting for margins
     *
     * @return float
     */
    public function getRemainingWidth(): float
    {
        return (float)$this->w - $this->rMargin - $this->x;
    }

    public function setFontSize($size = 0): Pdf
    {
        $fontFamily = $this->FontFamily;
        $fontStyle = $this->FontStyle;
        $this->SetFont($fontFamily, $fontStyle, $size);
        return $this;
    }

    /**
     * @param mixed $x
     * @return void
     */
    public function SetX($x)
    {
        $x = (float)$x;
        parent::SetX($x);
    }

    protected function getChoppedString(string $text, int $maxLines, float $containedWidth): array
    {
        // This function expects unifontSubset to be correct for whatever text we're working with
        // This logic is mostly ripped from base class's MultiCell method
        $s = str_replace("\r", '', (string)$text);
        if ($this->unifontSubset) {
            $strLen = mb_strlen($s, 'utf-8');
            while ($strLen > 0 && mb_substr($s, $strLen - 1, 1, 'utf-8') == "\n") $strLen--;
        } else {
            $strLen = strlen($s);
            if ($strLen > 0 && $s[$strLen - 1] == "\n")
                $strLen--;
        }

        $getChunkClosure = function (int $start, int $end) use ($s)
        {
            $length = $end-$start+1;
            if ($this->unifontSubset) {
                return mb_substr($s,$start,$length,'UTF-8');
            }
            return substr($s,$start,$length);
        };

        $returnChunks = [];
        $currentChunkLength = 0;
        $currentPosition = 0;
        $chunkStartPosition = 0;
        $lastSpacePosition = 0;

        while ($currentPosition<$strLen && ($maxLines == -1 || count($returnChunks)<$maxLines)) {
            // Get next character
            if ($this->unifontSubset) {
                $c = mb_substr($s,$currentPosition,1,'UTF-8');
            }
            else {
                $c=$s[$currentPosition];
            }
            if($c=="\n") { // explicit line break
                $returnChunks[] = $getChunkClosure($chunkStartPosition,$currentPosition-1);
                $currentChunkLength = 0;
                $currentPosition++;
                $chunkStartPosition = $currentPosition;
                continue;
            }
            if ($c==" ") { // we found a space, aka a word break
                $lastSpacePosition = $currentPosition;
            }
            $characterLength = $this->GetStringWidth($c);
            if ($currentChunkLength + $characterLength >= $containedWidth) { // overflowed. Store chunk, move on
                if ($lastSpacePosition == $currentPosition) { // we are at a natural break. Awesome! So easy!
                    $returnChunks[] = $getChunkClosure($chunkStartPosition,$currentPosition-1);
                    $currentChunkLength = 0;
                    $currentPosition++;
                    $chunkStartPosition = $currentPosition;
                    continue;
                }
                if ($lastSpacePosition>$chunkStartPosition) { // break at the last space
                    $returnChunks[] = $getChunkClosure($chunkStartPosition,$lastSpacePosition-1);
                    $currentChunkLength = 0;
                    $currentPosition = $lastSpacePosition+1;
                    $chunkStartPosition = $currentPosition;
                    continue;
                }
                // no natural break. Store an up-to. Do not advance current position (we start with overflowed character)
                $returnChunks[] = $getChunkClosure($chunkStartPosition,$currentPosition-1);
                $currentChunkLength = 0;
                $chunkStartPosition = $currentPosition;
                continue;
            }
            $currentChunkLength += $characterLength;
            $currentPosition++;
        }
        if ($chunkStartPosition<=$strLen-1) { // handle leftover chunks
            if ($maxLines == -1 || count($returnChunks)<$maxLines) {
                $returnChunks[] = $getChunkClosure($chunkStartPosition,$strLen-1);
            }
        }

        return $returnChunks;
    }

    protected function trimTextToWidth(string $text, float $width): string
    {
        if ($width < 0 || $width === 0 || $width === '0' || $width === '') {
            return $text;
        }
        $foundSize = false;
        while (!$foundSize && strlen($text) > 0) {
            $tryWidth = $this->GetStringWidth($text);
            if ($tryWidth > $width) {
                $text = substr($text, 0, strlen($text) - 1);
            } else {
                return $text;
            }
        }
        return '';
    }

    // The below are overrides to force width, x and y into ints to avoid issues with strong data types

    /**
     * Like MultiCell, but returns to original y
     * Returns lowest Y reached. Useful for clearing line at a later point
     */
    public function xCell($w, $h = 0, $txt = '', $border = 0, $align = '', $fill = 0, $link = ''): float
    {
        if ($this->y + $h > $this->PageBreakTrigger && !$this->InHeader && !$this->InFooter && $this->AcceptPageBreak()) {
            // Automatic page break
            $x = $this->x;
            $ws = $this->ws;
            if ($ws > 0) {
                $this->ws = 0;
                $this->_out('0 Tw');
            }
            $this->AddPage($this->CurOrientation, $this->CurPageSize);
            $this->x = $x;
            if ($ws > 0) {
                $this->ws = $ws;
                $this->_out(sprintf('%.3F Tw', $ws));
            }
        }
        $startY = $this->GetY();
        $startX = $this->GetX();
        $this->MultiCell($w, $h, $txt, $border, $align, $fill);
        $y = $this->GetY();
        $this->SetX($startX + $w);
        $this->SetY($startY);
        return (float)$y;
    }

    /**
     * Functionality differs from base in that resetX is ignored. Param is kept to keep signatures matching.
     *
     * @param $y
     * @param $resetX
     */
    public function SetY($y, $resetX = true)
    {
        $y = (float)$y;
        $x = $this->GetX();
        parent::SetY($y);
        $this->setX($x);
    }

    public function Cell($w, $h = 0, $txt = '', $border = 0, $ln = 0, $align = '', $fill = false, $link = '')
    {
        $w = (float)$w;
        $h = (float)$h;
        parent::Cell($w, $h, $txt, $border, $ln, $align, $fill, $link);
    }

}