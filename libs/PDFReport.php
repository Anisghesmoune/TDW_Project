<?php
require_once 'fpdf/fpdf.php';

class PDFReport extends FPDF {
    private $reportTitle;
    private $filterInfo='';
    
    public function setReportTitle($t) {
        $this->reportTitle = $t;
    }
    public function setFilterInfo($info) {
        $this->filterInfo = $info;
    }
    
    // Better UTF-8 conversion
    public function convert($str) {
        if (empty($str)) return '';
        return iconv('UTF-8', 'windows-1252//TRANSLIT//IGNORE', $str);
    }
    
    function Header() {
        // Logo (optional)
        // $this->Image('../assets/img/logo.png', 10, 6, 30);
        
        $this->SetFont('Arial', 'B', 15);
        $this->Cell(0, 10, $this->convert('Rapport du Laboratoire'), 0, 1, 'C');
        $this->Ln(5);
        
        $this->SetFont('Arial', 'I', 12);
        $this->Cell(0, 10, $this->convert($this->reportTitle), 0, 1, 'C');
        $this->Ln(10);
        
        // Table headers
        $this->SetFont('Arial', 'B', 10);
        $this->SetFillColor(78, 115, 223);
        $this->SetTextColor(255);
        
        $this->Cell(70, 10, $this->convert('Titre du Projet'), 1, 0, 'C', true);
        $this->Cell(45, 10, $this->convert('Responsable'), 1, 0, 'C', true);
        $this->Cell(25, 10, $this->convert('Début'), 1, 0, 'C', true);
        $this->Cell(25, 10, 'Statut', 1, 0, 'C', true);
        $this->Cell(25, 10, 'Finan.', 1, 1, 'C', true);
    }
    
    function Footer() {
        $this->SetY(-15);
        $this->SetFont('Arial', 'I', 8);
        $this->SetTextColor(0);
        
        $this->Cell(95, 10, 'Page ' . $this->PageNo() . '/{nb}', 0, 0, 'L');
        $this->Cell(95, 10, $this->convert('Généré le ' . date('d/m/Y H:i')), 0, 0, 'R');
    }
    
    function ProjectTable($data) {
        $this->SetFont('Arial', '', 9);
        $this->SetTextColor(0);
        $this->SetFillColor(245, 245, 245);
        $fill = false;
        
        foreach($data as $row) {
            // Check if we need a new page
            if ($this->GetY() > 260) {
                $this->AddPage();
            }
            
            // Title (truncate if too long)
            $titre = $row['titre'] ?? 'N/A';
            $titre = $this->convert($titre);
            $titre = (strlen($titre) > 40) ? substr($titre, 0, 37) . '...' : $titre;
            
            // Responsable
            $responsable = $row['responsable_name'] ?? $row['responsable'] ?? 'N/A';
            $responsable = $this->convert($responsable);
            $responsable = (strlen($responsable) > 25) ? substr($responsable, 0, 22) . '...' : $responsable;
            
            // Date
            $date = $row['date_debut'] ?? 'N/A';
            if ($date && $date !== 'N/A') {
                $date = date('d/m/Y', strtotime($date));
            }
            
            // Status
            $statutMap = [
                'soumis' => 'Soumis',
                'en_cours' => 'En cours',
                'termine' => 'Terminé',
                'annule' => 'Annulé'
            ];
            $statut = $statutMap[$row['statut']] ?? $row['statut'];
            $statut = $this->convert($statut);
            
            // Financing
            $financement = $row['type_financement'] ?? 'Interne';
            $financement = $this->convert($financement);
            
            // Draw cells
            $this->Cell(70, 8, $titre, 1, 0, 'L', $fill);
            $this->Cell(45, 8, $responsable, 1, 0, 'L', $fill);
            $this->Cell(25, 8, $date, 1, 0, 'C', $fill);
            $this->Cell(25, 8, $statut, 1, 0, 'C', $fill);
            $this->Cell(25, 8, $financement, 1, 1, 'C', $fill);
            
            $fill = !$fill;
        }
    }
    // À ajouter dans la classe PDFReport

    function EquipmentReportTable($header, $data, $colWidths) {
        // Couleurs, épaisseur du trait et police grasse
        $this->SetFillColor(78, 115, 223);
        $this->SetTextColor(255);
        $this->SetDrawColor(50, 50, 100);
        $this->SetLineWidth(.3);
        $this->SetFont('Arial', 'B', 10);

        // En-tête
        for($i=0; $i<count($header); $i++)
            $this->Cell($colWidths[$i], 7, $this->convert($header[$i]), 1, 0, 'C', true);
        $this->Ln();

        // Restauration des couleurs et de la police
        $this->SetFillColor(224, 235, 255);
        $this->SetTextColor(0);
        $this->SetFont('Arial', '', 9);
        
        $fill = false;
        foreach($data as $row) {
            $i = 0;
            foreach($row as $col) {
                // Alignement : Texte à gauche pour le nom, Centré pour les chiffres
                $align = is_numeric(str_replace(['%', ' '], '', $col)) ? 'C' : 'L';
                $this->Cell($colWidths[$i], 6, $this->convert($col), 'LR', 0, $align, $fill);
                $i++;
            }
            $this->Ln();
            $fill = !$fill;
        }
        $this->Cell(array_sum($colWidths), 0, '', 'T');
        $this->Ln(10); // Espace après le tableau
    }
    // --- AJOUTS POUR LE RAPPORT DE PUBLICATIONS ---

    /**
     * Affiche un en-tête de catégorie (ex: "ARTICLES", "THÈSES")
     */
    function CategoryHeader($label) {
        $this->Ln(6);
        $this->SetFont('Arial', 'B', 11);
        $this->SetFillColor(230, 230, 230); // Gris clair
        $this->SetTextColor(50, 50, 50);
        $this->Cell(0, 8, $this->convert(strtoupper($label)), 0, 1, 'L', true);
        $this->Ln(2);
    }

    /**
     * Méthode avancée pour dessiner une ligne de tableau avec hauteur dynamique
     * Indispensable pour les titres longs et les listes d'auteurs
     */
    function PublicationRow($data, $widths) {
        // Calcul de la hauteur maximale de la ligne
        $nb = 0;
        for($i=0;$i<count($data);$i++) {
            $nb = max($nb, $this->NbLines($widths[$i], $this->convert($data[$i])));
        }
        $h = 6 * $nb; // 6 = hauteur d'une ligne de texte

        // Vérification saut de page
        if($this->GetY() + $h > 275) {
            $this->AddPage();
        }

        // Dessin des cellules
        for($i=0;$i<count($data);$i++) {
            $w = $widths[$i];
            $x = $this->GetX();
            $y = $this->GetY();
            
            // Cadre
            $this->Rect($x, $y, $w, $h);
            
            // Texte (MultiCell gère le retour à la ligne)
            // Alignement : Centre (C) pour la date (colonne 2), Gauche (L) pour le reste
            $align = ($i == 2) ? 'C' : 'L'; 
            
            $this->MultiCell($w, 6, $this->convert($data[$i]), 0, $align);
            
            // Repositionnement à droite pour la prochaine cellule
            $this->SetXY($x + $w, $y);
        }
        $this->Ln($h);
    }

    /**
     * Utilitaire : Calcule le nombre de lignes qu'un texte va prendre dans une colonne
     */
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
}
?>