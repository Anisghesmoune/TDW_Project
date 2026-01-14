<?php
require_once __DIR__ . '/fpdf/fpdf.php';

class PDFReport extends FPDF {
    private $reportTitle;
    private $filterInfo='';
    
    public function setReportTitle($t) {
        $this->reportTitle = $t;
    }
    public function setFilterInfo($info) {
        $this->filterInfo = $info;
    }
    
    public function convert($str) {
        if (empty($str)) return '';
        return iconv('UTF-8', 'windows-1252//TRANSLIT//IGNORE', $str);
    }
    
    function Header() {
        
        
        $this->SetFont('Arial', 'B', 15);
        $this->Cell(0, 10, $this->convert('Rapport du Laboratoire'), 0, 1, 'C');
        $this->Ln(5);
        
        $this->SetFont('Arial', 'I', 12);
        $this->Cell(0, 10, $this->convert($this->reportTitle), 0, 1, 'C');
        $this->Ln(10);
        
        $this->SetFont('Arial', 'B', 10);
        $this->SetFillColor(78, 115, 223);
        $this->SetTextColor(255);
        
       
    }
    
    function Footer() {
        $this->SetY(-15);
        $this->SetFont('Arial', 'I', 8);
        $this->SetTextColor(0);
        
        $this->Cell(95, 10, 'Page ' . $this->PageNo() . '/{nb}', 0, 0, 'L');
        $this->Cell(95, 10, $this->convert('Généré le ' . date('d/m/Y H:i')), 0, 0, 'R');
    }
    
    

    function EquipmentReportTable($header, $data, $colWidths) {
        $this->SetFillColor(78, 115, 223);
        $this->SetTextColor(255);
        $this->SetDrawColor(50, 50, 100);
        $this->SetLineWidth(.3);
        $this->SetFont('Arial', 'B', 10);

        for($i=0; $i<count($header); $i++)
            $this->Cell($colWidths[$i], 7, $this->convert($header[$i]), 1, 0, 'C', true);
        $this->Ln();

        $this->SetFillColor(224, 235, 255);
        $this->SetTextColor(0);
        $this->SetFont('Arial', '', 9);
        
        $fill = false;
        foreach($data as $row) {
            $i = 0;
            foreach($row as $col) {
                $align = is_numeric(str_replace(['%', ' '], '', $col)) ? 'C' : 'L';
                $this->Cell($colWidths[$i], 6, $this->convert($col), 'LR', 0, $align, $fill);
                $i++;
            }
            $this->Ln();
            $fill = !$fill;
        }
        $this->Cell(array_sum($colWidths), 0, '', 'T');
        $this->Ln(10); 
    }

   
    function CategoryHeader($label) {
        $this->Ln(6);
        $this->SetFont('Arial', 'B', 11);
        $this->SetFillColor(230, 230, 230); 
        $this->SetTextColor(50, 50, 50);
        $this->Cell(0, 8, $this->convert(strtoupper($label)), 0, 1, 'L', true);
        $this->Ln(2);
    }

    function PublicationRow($data, $widths) {
        $nb = 0;
        for($i=0;$i<count($data);$i++) {
            $nb = max($nb, $this->NbLines($widths[$i], $this->convert($data[$i])));
        }
        $h = 6 * $nb; 

        if($this->GetY() + $h > 275) {
            $this->AddPage();
        }

        for($i=0;$i<count($data);$i++) {
            $w = $widths[$i];
            $x = $this->GetX();
            $y = $this->GetY();
            
            $this->Rect($x, $y, $w, $h);
            
           $align = ($i == 2) ? 'C' : 'L'; 
            
            $this->MultiCell($w, 6, $this->convert($data[$i]), 0, $align);
            
            $this->SetXY($x + $w, $y);
        }
        $this->Ln($h);
    }

    
    function NbLines($w, $txt) {
        $cw = &$this->CurrentFont['cw'];
        if($w==0) $w = $this->w-$this->rMargin-$this->x;
        $wmax = ($w-2*$this->cMargin)*1000/$this->FontSize;
        $s = str_replace("\r", '', $txt);
        $nb = strlen($s);
        if($nb>0 && $s[$nb-1]=="\n") $nb--;
        $sep = -1;
        $i = 0;
        $j = 0;
        $l = 0;
        $nl = 1;
        while($i<$nb) {
            $c = $s[$i];
            if($c=="\n") {
                $i++; $sep = -1; $j = $i; $l = 0; $nl++; continue;
            }
            if($c==' ') $sep = $i;
            $l += $cw[$c];
            if($l>$wmax) {
                if($sep==-1) {
                    if($i==$j) $i++;
                } else $i = $sep+1;
                $sep = -1; $j = $i; $l = 0; $nl++;
            } else $i++;
        }
        return $nl;
    }
     public function ProjectTable($data) {
      $w = [85, 55, 25, 25];
        
        $header = ['Titre du Projet', 'Responsable', 'Début', 'Statut'];
        
        $this->SetFont('Arial', 'B', 10);
        $this->SetFillColor(78, 115, 223); 
        $this->SetTextColor(255);          
        $this->SetLineWidth(.3);
        
        foreach($header as $i => $col) {
            $texte = iconv('UTF-8', 'windows-1252//TRANSLIT', $col);
            $this->Cell($w[$i], 8, $texte, 1, 0, 'C', true);
        }
        $this->Ln();
        
        $this->SetFont('Arial', '', 9);
        $this->SetTextColor(0); 
        $this->SetFillColor(245, 245, 245); 
        $fill = false;
        
        foreach($data as $row) {
            
            $titreRaw = $row['titre'] ?? 'Sans titre';
            $titre = substr($titreRaw, 0, 55) . (strlen($titreRaw) > 55 ? '...' : '');

            
            $respRaw = 'Non défini';
            
            if (!empty($row['resp_nom'])) {
                $respRaw = $row['resp_nom'];
                if (!empty($row['resp_prenom'])) {
                    $respRaw .= ' ' . $row['resp_prenom'];
                }
            } elseif (!empty($row['responsable'])) {
                $respRaw = $row['responsable'];
            }
            $resp = substr($respRaw, 0, 30); 

           
            $date = '-';
            if (!empty($row['date_debut'])) {
                $date = date('d/m/Y', strtotime($row['date_debut']));
            }

            $statutRaw = $row['statut'] ?? '';
            $statut = ucfirst(str_replace('_', ' ', $statutRaw));

            
            $this->Cell($w[0], 7, iconv('UTF-8', 'windows-1252//TRANSLIT', $titre), 1, 0, 'L', $fill);
            
            $this->Cell($w[1], 7, iconv('UTF-8', 'windows-1252//TRANSLIT', $resp), 1, 0, 'L', $fill);
            
            $this->Cell($w[2], 7, $date, 1, 0, 'C', $fill);
            
            $this->Cell($w[3], 7, iconv('UTF-8', 'windows-1252//TRANSLIT', $statut), 1, 0, 'C', $fill);
            
            $this->Ln();
            
            $fill = !$fill;
        }
        
        $this->Cell(array_sum($w), 0, '', 'T');
    }

}
?>