<?php
/**
 * Created by IntelliJ IDEA.
 * User: edgar
 * Date: 02/09/18
 * Time: 19:27
 */

namespace App\Util;

use Cake\Filesystem\Folder;
use Setasign\Fpdf;

class PDF extends PDF_Rotate
{
// Page header
    function Header()
    {
        $dir = new Folder(WWW_ROOT);
        // Logo
        $this->Image(WWW_ROOT  . '/img/al_logo.png',155,20,30);
        // Arial bold 15
        $this->SetFont('Arial','B',16);
        // Move to the right
        //$this->Cell(40);
        // Title
        $this->Cell(130,10,'RECIBO DE PAGO',0,0,'L');
        //$this->Cell(50,10,'FOLIO: ' . $this->folio,0,0,'R');
        // Line break
        //$this->Ln(10);
    }

// Page footer
    function Footer()
    {
        // Position at 1.5 cm from bottom
        $this->SetY(-25);
        $this->SetFont('Arial','B',12);
        $this->Cell(140,7,utf8_decode('CONDÓMINO') ,0,1,'C',false);
        $this->SetFont('Arial','',8);
        $this->Cell(140,10,'EL PAGO DE ESTE RECIBO NO EXIME DE ADEUDOS ANTERIORES' ,0,1,'C',false);
        // Arial italic 8
        //$this->SetFont('Arial','I',8);
        // Page number
        //$this->Cell(0,10,'Page '.$this->PageNo().'/{nb}',0,0,'C');
    }

    function RotatedText($x,$y,$txt,$angle)
    {
        //Text rotated around its origin
        $this->Rotate($angle,$x,$y);
        $this->Text($x,$y,$txt);
        $this->Rotate(0);
    }

}
