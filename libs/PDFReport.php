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
        
       
    }
    
    function Footer() {
        $this->SetY(-15);
        $this->SetFont('Arial', 'I', 8);
        $this->SetTextColor(0);
        
        $this->Cell(95, 10, 'Page ' . $this->PageNo() . '/{nb}', 0, 0, 'L');
        $this->Cell(95, 10, $this->convert('Généré le ' . date('d/m/Y H:i')), 0, 0, 'R');
    }
    
    

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
     public function ProjectTable($data) {
        // 1. Définition des largeurs de colonnes
        // Total = 190 (A4 standard avec marges)
        $w = [85, 55, 25, 25]; // Titre, Responsable, Début, Statut
        
        // 2. En-têtes du tableau
        $header = ['Titre du Projet', 'Responsable', 'Début', 'Statut'];
        
        // Style des en-têtes
        $this->SetFont('Arial', 'B', 10);
        $this->SetFillColor(78, 115, 223); // Bleu Admin
        $this->SetTextColor(255);          // Blanc
        $this->SetLineWidth(.3);
        
        // Affichage des en-têtes
        foreach($header as $i => $col) {
            // Conversion utf-8 -> windows-1252 pour les accents
            $texte = iconv('UTF-8', 'windows-1252//TRANSLIT', $col);
            $this->Cell($w[$i], 8, $texte, 1, 0, 'C', true);
        }
        $this->Ln();
        
        // 3. Style des données
        $this->SetFont('Arial', '', 9);
        $this->SetTextColor(0); // Noir
        $this->SetFillColor(245, 245, 245); // Gris très clair pour l'alternance
        $fill = false;
        
        // 4. Boucle sur les données
        foreach($data as $row) {
            
            // --- A. Traitement du Titre ---
            $titreRaw = $row['titre'] ?? 'Sans titre';
            // On coupe si trop long pour éviter de casser la mise en page
            $titre = substr($titreRaw, 0, 55) . (strlen($titreRaw) > 55 ? '...' : '');

            // --- B. Traitement du Responsable ---
            // Priorité : 1. Nom injecté par le contrôleur (filtre) ou jointure SQL
            //            2. Champ 'responsable' simple
            //            3. 'Non défini'
            $respRaw = 'Non défini';
            
            if (!empty($row['resp_nom'])) {
                // Cas : Jointure SQL ou Injection via le filtre
                $respRaw = $row['resp_nom'];
                if (!empty($row['resp_prenom'])) {
                    $respRaw .= ' ' . $row['resp_prenom'];
                }
            } elseif (!empty($row['responsable'])) {
                // Cas : Champ simple
                $respRaw = $row['responsable'];
            }
            $resp = substr($respRaw, 0, 30); // Tronquer si trop long

            // --- C. Traitement de la Date ---
            $date = '-';
            if (!empty($row['date_debut'])) {
                $date = date('d/m/Y', strtotime($row['date_debut']));
            }

            // --- D. Traitement du Statut ---
            // On remplace les "_" par des espaces et on met la 1ère lettre en majuscule
            $statutRaw = $row['statut'] ?? '';
            $statut = ucfirst(str_replace('_', ' ', $statutRaw));

            // --- E. Affichage de la ligne ---
            
            // TITRE (Aligné à gauche)
            $this->Cell($w[0], 7, iconv('UTF-8', 'windows-1252//TRANSLIT', $titre), 1, 0, 'L', $fill);
            
            // RESPONSABLE (Aligné à gauche)
            $this->Cell($w[1], 7, iconv('UTF-8', 'windows-1252//TRANSLIT', $resp), 1, 0, 'L', $fill);
            
            // DATE (Centré)
            $this->Cell($w[2], 7, $date, 1, 0, 'C', $fill);
            
            // STATUT (Centré)
            $this->Cell($w[3], 7, iconv('UTF-8', 'windows-1252//TRANSLIT', $statut), 1, 0, 'C', $fill);
            
            $this->Ln();
            
            // Alternance de couleur
            $fill = !$fill;
        }
        
        // Trait de fin de tableau
        $this->Cell(array_sum($w), 0, '', 'T');
    }

}
?>