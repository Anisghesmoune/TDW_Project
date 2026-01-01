<?php
require_once 'fpdf/fpdf.php';

class PDFReport extends FPDF {
    private $reportTitle;
    
    public function setReportTitle($t) {
        $this->reportTitle = $t;
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
}
?>