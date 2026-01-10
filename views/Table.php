<?php
/**
 * Classe Table - Composant générique pour afficher des tableaux
 */
class Table {
    private $id; // ✅ Ajout de la propriété ID
    private $headers;
    private $data;
    private $columns;
    private $actions;
    private $customClass;
    private $emptyMessage;
    
    /**
     * Constructeur
     */
    public function __construct($options = []) {
        $this->id = $options['id'] ?? ''; // ✅ Récupération de l'ID
        $this->headers = $options['headers'] ?? [];
        $this->data = $options['data'] ?? [];
        $this->columns = $options['columns'] ?? [];
        $this->actions = $options['actions'] ?? [];
        $this->customClass = $options['class'] ?? 'table';
        $this->emptyMessage = $options['emptyMessage'] ?? 'Aucune donnée disponible';
    }
    
    /**
     * Générer le HTML des en-têtes
     */
    private function renderHeaders() {
        if (empty($this->headers)) return '';
        
        $headerHtml = '';
        foreach ($this->headers as $header) {
            $headerHtml .= '<th>' . htmlspecialchars($header) . '</th>';
        }
        
        if (!empty($this->actions)) {
            $headerHtml .= '<th>Actions</th>';
        }
        
        return <<<HTML
        <thead>
            <tr>
                {$headerHtml}
            </tr>
        </thead>
HTML;
    }
    
    /**
     * Formater une valeur selon son type
     */
    private function formatValue($value, $format = null) {
        if ($format === null) {
            // On retourne la valeur brute pour permettre le HTML généré par les fonctions de callback
            return ($value ?? '');
        }
        
        // Format personnalisé (callable)
        if (is_callable($format)) {
            return $format($value);
        }
        
        // Format badge
        if (is_array($format) && isset($format['type']) && $format['type'] === 'badge') {
            $badgeClass = $format['class'] ?? 'badge';
            if (isset($format['conditions'])) {
                foreach ($format['conditions'] as $condition => $class) {
                    if ($value == $condition) {
                        $badgeClass .= ' ' . $class;
                        break;
                    }
                }
            }
            // Ici, on échappe la valeur du texte pour la sécurité
            return '<span class="' . $badgeClass . '">' . htmlspecialchars(ucfirst($value)) . '</span>';
        }
        
        return htmlspecialchars($value ?? '');
    }
    
    /**
     * Générer le HTML des actions
     */
    private function renderActions($row) {
        if (empty($this->actions)) return '';
        
        $actionsHtml = '<div class="action-btns">';
        
        foreach ($this->actions as $action) {
            $label = $action['label'] ?? '';
            
            // Gestion des icônes dynamiques
            if (is_callable($action['icon'])) {
                $icon = $action['icon']($row);
            } else {
                $icon = $action['icon'] ?? '';
            }
            
            // Gestion des classes dynamiques
            if (isset($action['class']) && is_callable($action['class'])) {
                $class = $action['class']($row);
            } else {
                $class = $action['class'] ?? 'btn-sm';
            }
            
            $onclick = '';
            
            // Gestion onclick
            if (isset($action['onclick'])) {
                if (is_callable($action['onclick'])) {
                    $onclick = $action['onclick']($row);
                } else {
                    $onclick = str_replace('{id}', $row['id'] ?? '', $action['onclick']);
                }
                $onclick = 'onclick="' . htmlspecialchars($onclick) . '"';
            }
            
            // Gestion href
            $href = '#';
            if (isset($action['href'])) {
                if (is_callable($action['href'])) {
                    $href = $action['href']($row);
                } else {
                    $href = str_replace('{id}', $row['id'] ?? '', $action['href']);
                }
            }
            
            // Rendu Bouton ou Lien
            if (isset($action['href'])) {
                $actionsHtml .= '<a href="' . htmlspecialchars($href) . '" class="' . htmlspecialchars($class) . '">' . $icon . $label . '</a>';
            } else {
                $actionsHtml .= '<button class="' . htmlspecialchars($class) . '" ' . $onclick . '>' . $icon . $label . '</button> ';
            }
        }
        
        $actionsHtml .= '</div>';
        return $actionsHtml;
    }
    
    /**
     * Générer le HTML des lignes de données
     */
    private function renderBody() {
        if (empty($this->data)) {
            $colSpan = count($this->headers) + (!empty($this->actions) ? 1 : 0);
            return <<<HTML
            <tbody>
                <tr>
                    <td colspan="{$colSpan}" style="text-align: center; padding: 20px; color: #666;">
                        {$this->emptyMessage}
                    </td>
                </tr>
            </tbody>
HTML;
        }
        
        $bodyHtml = '<tbody>';
        
        foreach ($this->data as $row) {
            $bodyHtml .= '<tr>';
            
            foreach ($this->columns as $column) {
                $key = $column['key'] ?? '';
                $format = $column['format'] ?? null;
                
                if (is_callable($key)) {
                    $value = $key($row);
                } else {
                    $value = $row[$key] ?? '';
                }
                
                $bodyHtml .= '<td>' . $this->formatValue($value, $format) . '</td>';
            }
            
            if (!empty($this->actions)) {
                $bodyHtml .= '<td>' . $this->renderActions($row) . '</td>';
            }
            
            $bodyHtml .= '</tr>';
        }
        
        $bodyHtml .= '</tbody>';
        return $bodyHtml;
    }
    
    /**
     * Générer le HTML complet du tableau
     */
    public function render() {
        $headersHtml = $this->renderHeaders();
        $bodyHtml = $this->renderBody();
        
        // ✅ Ajout de l'attribut ID ici
        $idAttr = !empty($this->id) ? 'id="' . htmlspecialchars($this->id) . '"' : '';
        
        return <<<HTML
        <table {$idAttr} class="{$this->customClass}">
            {$headersHtml}
            {$bodyHtml}
        </table>
HTML;
    }
    
    /**
     * Afficher directement le tableau
     */
    public function display() {
        echo $this->render();
    }
    
    public static function create($options) {
        $table = new self($options);
        return $table->render();
    }
}
?>