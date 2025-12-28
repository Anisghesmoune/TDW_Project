<?php
/**
 * Classe Table - Composant générique pour afficher des tableaux
 * Peut être utilisée pour n'importe quel type de données
 */
class Table {
    private $headers;
    private $data;
    private $columns;
    private $actions;
    private $customClass;
    private $emptyMessage;
    
    /**
     * Constructeur
     * @param array $options - Configuration du tableau
     */
    public function __construct($options = []) {
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
        
        // Ajouter colonne Actions si des actions sont définies
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
            return htmlspecialchars($value ?? '');
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
            return '<span class="' . $badgeClass . '">' . htmlspecialchars(ucfirst($value)) . '</span>';
        }
        
        return htmlspecialchars($value ?? '');
    }
    
    /**
     * Générer le HTML des actions
     */
  private function renderActions($row) {
    // Si aucune action n'est définie, ne rien retourner
    if (empty($this->actions)) return '';
    
    // Conteneur pour les boutons d'action
    $actionsHtml = '<div class="action-btns">';
    
    // Parcourir chaque action définie
    foreach ($this->actions as $action) {
        $label = $action['label'] ?? '';
        
        // ✅ CORRECTION : Gérer les callables pour icon
        if (is_callable($action['icon'])) {
            $icon = $action['icon']($row); // Appeler la fonction avec $row
        } else {
            $icon = $action['icon'] ?? '';
        }
        
        // ✅ CORRECTION : Gérer les callables pour class
        if (is_callable($action['class'])) {
            $class = $action['class']($row); // Appeler la fonction avec $row
        } else {
            $class = $action['class'] ?? 'btn-sm';
        }
        
        $onclick = '';
        
        // Gérer l'événement onclick
        if (isset($action['onclick'])) {
            if (is_callable($action['onclick'])) {
                // Si c'est une fonction, l'appeler avec $row
                $onclick = $action['onclick']($row);
            } else {
                // Si c'est une chaîne, remplacer {id} par la vraie valeur
                $onclick = str_replace('{id}', $row['id'] ?? '', $action['onclick']);
            }
            // Ajouter l'attribut onclick avec échappement de sécurité
            $onclick = 'onclick="' . htmlspecialchars($onclick) . '"';
        }
        
        // Gérer le href pour les liens
        $href = '#';
        if (isset($action['href'])) {
            if (is_callable($action['href'])) {
                $href = $action['href']($row);
            } else {
                $href = str_replace('{id}', $row['id'] ?? '', $action['href']);
            }
        }
        
        // Générer le bouton ou le lien
        if (isset($action['href'])) {
            // Si href est défini, créer un lien <a>
            $actionsHtml .= '<a href="' . htmlspecialchars($href) . '" class="' . htmlspecialchars($class) . '">' . $icon . $label . '</a>';
        } else {
            // Sinon, créer un bouton <button>
            $actionsHtml .= '<button class="' . htmlspecialchars($class) . '" ' . $onclick . '>' . $icon . $label . '</button>';
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
                    <td colspan="{$colSpan}" style="text-align: center; padding: 20px;">
                        {$this->emptyMessage}
                    </td>
                </tr>
            </tbody>
HTML;
        }
        
        $bodyHtml = '<tbody>';
        
        foreach ($this->data as $row) {
            $bodyHtml .= '<tr>';
            
            // Parcourir chaque colonne définie
            foreach ($this->columns as $column) {
                $key = $column['key'] ?? '';
                $format = $column['format'] ?? null;
                
                // Si c'est une fonction callback
                if (is_callable($key)) {
                    $value = $key($row);
                } else {
                    $value = $row[$key] ?? '';
                }
                
                $bodyHtml .= '<td>' . $this->formatValue($value, $format) . '</td>';
            }
            
            // Ajouter la colonne Actions
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
        
        return <<<HTML
        <table class="{$this->customClass}">
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
    
    /**
     * Méthode statique pour créer et afficher rapidement un tableau
     */
    public static function create($options) {
        $table = new self($options);
        return $table->render();
    }
}