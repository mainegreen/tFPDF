# tFPDF
Extended version of the tFPDF library with more features like rollback and enhanced cell writing call

This library is meant to provide a better interface for using the tFPDF libray, which while being
a great library, suffers from a somewhat difficult to use api. By introducing a Cell object, 
which is a collection of all the properties you might wish to set when writing a cell, it can
be significantly easier to code consistent cell calls as well as understanding why a cell 
looks like it does when output. Additionally color management has been improved with the Color
class and a ColorsEnum object providing some base colors to use. Additionally this targets PHP 8.1
and so is strongly typed uses enums and class constants to reduce data type exceptions.
Currently the library focuses on text enhancements only, and I would expect it's primary usage
would be in report building, though anything tFPDF can do, this can do.

Consider the following using the old library, where $pdf is an instance of \tfpdf:

    $pdf->SetFont('Arial', 'B', 12);
    $pdf->SetFillColor(211,211,211);
    $pdf->SetTextColor(255,0,0);
    $pdf->SetDrawColor(255,0,0);
    $pdf->Cell(100, 0, 'Some Text', 'TB', 0, 'center', true);

Vs in this library, where $pdf is an instance of Pdf:

    $cell = new Cell();
    $cell->fontFamily = 'Arial';
    $cell->fontBold = true;
    $cell->fontSize = 12;
    $cell->fillColor = Color::fromColor(ColorsEnum::LIGHT_GRAY);
    $cell->fontColor = Color::fromColor(ColorsEnum::RED);
    $cell->drawColor = Color::fromColor(ColorsEnum::RED);
    $cell->width = 100;
    $cell->text = 'Some Text';
    $cell->border(0,0,1,1); 
    $cell->align = AlignEnum::CENTER;
    $cell->ln = false;
    $cell->fill = true;

    $pdf->writeCell($cell);

Right away it's clearer what you intend the cell to output, what the style will be. The use
of class methods and enums removes the need to know what tfpdf expects it's string parameters
to be. While the newer method may seem longer to set up, in a large PDF this actually becomes
less. Like the following:

    $cell = new Cell();
    $cell->fontFamily = 'Arial';
    $cell->fontSize = 12;
    $cell->fillColor = Color::fromColor(ColorsEnum::LIGHT_GRAY);
    $cell->align = AlignEnum::LEFT;
    $cell->ln = false;
    $cell->fill = true;
    
    $writeCell = clone $cell;
    $writeCell->width = 100;
    $writeCell->text = 'Some Text';
    $pdf->writeCell($writeCell);

    $writeCell = clone $cell;
    $writeCell->width = 50;
    $writeCell->align = AlignEnum::RIGHT;
    $writeCell->text = '$25.32';
    $pdf->writeCell($writeCell);

We can set up a template cell this way, to set some basic style properties and then clone it, 
and only modify what changes from cell to cell. This allows clarity of code as well as making
it possible to set up a template style at the beginning, and easily change it should you need
to without having to change lots of Set[property] methods or Cell or MultiCell calls.  One 
call performs both the function of Cell and MultiCell, and performs it 
in a manner more consistantly like one would expect.

Some features include:

* By setting the allowAutoWrap property on a Cell, you can make the output perform more like
multicell. Additionally you can set the properties autoWrapLineHeight to set the line hieght
for each wrapped line, and autoWrapMaxLines or cell height to set the maximum number of lines.

* When not doing a wrapping text call and setting autoShrink to true, the font
size will be reduced to try and fit the text in a cell, down to autoShrinkMinFontSize. 

* Rollback points using setRollbackPoint, rollbackTo, and clearRollbackPoints.

To use, create an instance of the \Mg\Tfpdf\Pdf object or an instance of a class extending
\Mg\Tfpdf\Pdf. See the test for an example of how to use this library as well as what
you can expect as outputs.