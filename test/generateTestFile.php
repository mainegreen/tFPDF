<?php
declare(strict_types=1);
require_once ('bootstrap.php');

use \Mg\Tfpdf\Pdf;
use \Mg\Tfpdf\Cell;
use \Mg\Tfpdf\Color;
use \Mg\Tfpdf\ColorsEnum;
use \Mg\Tfpdf\AlignEnum;

class TestReport extends Pdf
{

    public function _init()
    {
        $this->AliasNbPages();
        $this->SetMargins(15, 15);
        // region Let's set up some defaults
        $this->SetFont('Arial', '', 10);
        $this->SetFillColor(255, 255, 255);
        $this->SetDrawColor(0, 0, 0);

        // Demonstrate how to set up a footer or header as a closure.
        $footer = function (Pdf $pdf) {
            $pdf->SetY(-15);
            $cell = new Cell();
            $cell->fontSize = 8;
            $cell->fontItalic = true;
            $cell->align = AlignEnum::CENTER;
            $cell->text = 'Page ' . $pdf->PageNo() . '/{nb}';
            $pdf->writeCell($cell);
        };
        $this->setFooterClosure($footer);
    }

    // Also you can set headers or footers old-school style
    public function Header()
    {
        // We'll test a center aligned header that is 80% of width, with center aligned text
        // The cell is filled and should be exactly as tall as text
        $cell = new Cell();
        $cell->align = AlignEnum::CENTER;
        $cell->fontBold = true;
        $cell->fontItalic = true;
        $cell->fontSize = 16;
        $cell->xIsPercentage = true;
        $cell->x = 10;
        $cell->ln = true;
        $cell->widthIsPercentage = true;
        $cell->width = 80;
        $cell->fillColor = Color::fromColor(ColorsEnum::LIGHT_BLUE);
        $cell->text = 'header, 80% width, %10 margins, center aligned text';
        $this->writeCell($cell);
    }

    public function generate()
    {
        $this->AddPage();

        $cell = new Cell();
        $cell->fontSize = 12;
        $cell->height = 14;
        $cell->x = 75;
        $cell->ln = true;

        $loggingX = 10;

        $startingY = $this->GetY();

        // region Basic Text performance testing
        for($i=0;$i<2;$i++) {
            // region Test fixed width with no-auto shrink. Text should cut off and not flow out of cell
            $this->markLogReturnedAtY();
            $testCell = clone $cell;
            $testCell->drawColor = $this->testingColor();
            $testCell->fontColor = $this->testingColor();
            $testCell->width = 200;
            $testCell->autoShrink = false;
            $testCell->borderFrame(true); // slap a border around whole cell
            $testString = 'Text is cut off: ' . $this->getStringOfLength(100);
            $testCell->text = $testString;
            $this->logReturnedY($this->writeCell($testCell), $loggingX);
            $this->markPostBox($loggingX);
            // endregion
            // region Now test auto-shrink
            $this->markLogReturnedAtY();
            $testCell = clone $cell;
            $testCell->drawColor = $this->testingColor();
            $testCell->fontColor = $this->testingColor();
            $testCell->width = 200;
            $testCell->autoShrink = true;
            $testCell->borderFrame(true); // slap a border around whole cell
            $testString = 'Smaller & cut-off: ' . $this->getStringOfLength(100);
            $testCell->text = $testString;
            $this->logReturnedY($this->writeCell($testCell), $loggingX);
            $this->markPostBox($loggingX);
            // endregion

            $cell->height = null; // set to null to allow auto-wraps to flow to full height

            // region This should wrap text without shrinking, and try and break words up as little as possible.
            // Note that words wider than the width will be broken up!
            $this->markLogReturnedAtY();
            $testCell = clone $cell;
            $testCell->drawColor = $this->testingColor();
            $testCell->fontColor = $this->testingColor();
            $testCell->width = 200;
            $testCell->allowAutoWrap = true;
            $testCell->borderFrame(true); // slap a border around whole cell
            $testString = 'This wraps & breaks up big words: ' . $this->getStringOfLength(100);
            $testCell->text = $testString;
            $this->logReturnedY($this->writeCell($testCell), $loggingX);
            $this->markPostBox($loggingX);
            // endregion
            // region This should wrap text without shrinking, and try and break words up as little as possible.
            // Note that words wider than the width will be broken up!
            $this->markLogReturnedAtY();
            $testCell = clone $cell;
            $testCell->drawColor = $this->testingColor();
            $testCell->fontColor = $this->testingColor();
            $testCell->width = 200;
            $testCell->allowAutoWrap = true;
            $testCell->autoWrapMaxLines = 3;
            $testCell->borderFrame(true); // slap a border around whole cell
            $testString = 'Wraps, 3 lines max: ' . $this->getStringOfLength(100);
            $testCell->text = $testString;
            $this->logReturnedY($this->writeCell($testCell), $loggingX);
            $this->markPostBox($loggingX);
            // endregion
            $loggingX = 550;
            $cell->x = 320;
            $cell->lnOverlaps = true;
            if ($i==0)
                $this->SetY($startingY);
        }
        // endregion

        // region Align Testing and Color Control
        $this->SetY($this->GetY()+10);
        $testCell = clone $cell;
        $testCell->ln = false;
        $testCell->x = 75;
        $testCell->border(1,0,0,0);
        $testCell->width = 100;
        $testCell->text = 'left';
        $testCell->align = AlignEnum::LEFT;
        $testCell->drawColor = Color::fromRGBHex("000000");
        $testCell->fontColor = Color::fromRGBHex('FF0000');
        $testCell->fillColor = Color::fromRGBHex('acd7e5');
        $this->writeCell($testCell);

        $testCell->x = $testCell->x + $testCell->width + 30;
        $testCell->text = 'center';
        $testCell->border(0,0,1,1);
        $testCell->align = AlignEnum::CENTER;
        $testCell->drawColor = Color::fromColor(ColorsEnum::RED);
        $testCell->fontColor = Color::fromColor(ColorsEnum::BLACK);
        $testCell->fillColor = Color::fromColor(ColorsEnum::LIGHT_BLUE);
        $this->writeCell($testCell);

        $testCell->x = $testCell->x + $testCell->width + 30;
        $testCell->text = 'right';
        $testCell->border(0,1,0,0);
        $testCell->align = AlignEnum::RIGHT;
        $testCell->drawColor = Color::fromColor(ColorsEnum::BLUE);
        $testCell->fontColor = Color::fromColor(ColorsEnum::RED);
        $testCell->fillColor = Color::fromColor(ColorsEnum::LIGHT_GRAY); // black is too dark and reduces visibility of border naturally
        $testCell->ln = true;
        $this->writeCell($testCell);
        // endregion

        // region test some autowrapping

        $this->SetY($this->GetY()+10);
        $wrapCell = clone $cell;
        $wrapCell->height = 52;
        $wrapCell->width = 200;
        $wrapCell->ln = false;
        $wrapCell->allowAutoWrap = true;
        $wrapCell->text = $this->getLorumIpsum();
        $wrapCell->borderFrame(true);

        $testCell = clone $wrapCell;
        $testCell->x = 75;
        $testCell->drawColor = new Color(0,0,128);
        $testCell->text = 'Should autowrap to 3 lines: 52 cell height / 15 autowrap height = 3 '.$wrapCell->text;
        $testCell->autoWrapLineHeight = 15;
        $this->writeCell($testCell);

        $testCell = clone $wrapCell;
        $testCell->x = 300;
        $testCell->drawColor = Color::fromColor(ColorsEnum::NAVY);
        $testCell->ln =true;
        $testCell->text = 'Should autowrap to 4 lines: 52 cell height / 12 font size = 4 '.$wrapCell->text;
        $this->writeCell($testCell);

        $this->SetY($this->GetY()+10);
        $testCell = clone $cell;
        $testCell->autoWrapLineHeight = 15;
        $testCell->align = AlignEnum::CENTER;
        $testCell->x = 75;
        $testCell->text = substr("Prove border is height, not text height: ".$this->getLorumIpsum(),0,100);
        $testCell->width = 200;
        $testCell->height = 150;
        $testCell->allowAutoWrap = true;
        $testCell->borderFrame(true);
        $this->SetY($this->writeCell($testCell)+5);



        // endregion
    }

    protected function testingColor(bool $advance = false): Color
    {
        static $colors = [];
        static $colorPosition = 0;
        if (empty($colors)) {
            $colors[] = Color::fromColor(ColorsEnum::BLACK);
            $colors[] = Color::fromColor(ColorsEnum::BLUE);
            $colors[] = Color::fromColor(ColorsEnum::RED);
            $colors[] = Color::fromColor(ColorsEnum::GREEN);
            $colors[] = Color::fromColor(ColorsEnum::PURPLE);
            $colors[] = Color::fromColor(ColorsEnum::YELLOW);
        }
        $returnColor = $colors[$colorPosition];
        if ($advance) {
            $colorPosition++;
            if ($colorPosition>=count($colors)) {
                $colorPosition = 0;
            }
        }
        return $returnColor;
    }

    protected function markPostBox(float $writeAtX): void
    {
        $cell = new Cell();
        $cell->fillColor = $this->testingColor(true);
        $cell->x = $writeAtX;
        $cell->width = 3;
        $cell->height = 3;
        $this->writeCell($cell);
    }

    protected function markLogReturnedAtY(): void
    {
        $this->logReturnedYAt($this->GetY());
    }

    protected function logReturnedYAt(?float $y=null): float
    {
        static $atY=0;
        if (!is_null($y)) {
            $atY = $y;
        }
        return $atY;
    }

    protected function logReturnedY(float $y, float $writeAtX)
    {
        $pageY = $this->GetY();
        $this->SetY($this->logReturnedYAt());
        $cell = new Cell();
        $cell->fontSize = 8;
        $cell->height = 10;
        $cell->fill = false;
        $cell->fontColor = $this->testingColor();
        $cell->x = $writeAtX;
        $cell->text = $pageY.' == '.$y;
        $this->writeCell($cell);
        $this->SetY($pageY,false);
    }

    protected function getStringOfLength(int $len): string
    {
        $ordPosition = 0;
        $aPos = 65;
        $returnStr = '';
        for ($i=0;$i<$len;$i++)
        {
            $returnStr .= chr($aPos+$ordPosition);
            $ordPosition++;
            if ($ordPosition>25)
                $ordPosition = 0;
        }
        return $returnStr;
    }

    protected function getLorumIpsum()
    {
        return 'Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat. Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat cupidatat non proident, sunt in culpa qui officia deserunt mollit anim id est laborum.';
    }

}

$pdf = new TestReport(Pdf::ORIENTATION_PORTRAIT, Pdf::UNIT_PT, Pdf::SIZE_LEGAL );

$pdf->generate();
$output = $pdf->Output('', 'S');
$target = __DIR__.DIRECTORY_SEPARATOR.'testOutput'.DIRECTORY_SEPARATOR.'testfile.pdf';
if (file_exists($target)) {
    unlink($target);
}
file_put_contents($target,$output);
