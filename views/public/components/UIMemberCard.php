<?php
require_once 'Component.php';

class UIMemberCard extends Component {
    // $data attend : nom, prenom, photo_profil, grade, poste, id
    
    public function render() {
        $nom = htmlspecialchars($this->data['nom']);
        $prenom = htmlspecialchars($this->data['prenom']);
        $photo = !empty($this->data['photo_profil']) ? '../' . $this->data['photo_profil'] : '../assets/img/default-avatar.png';
        $grade = htmlspecialchars($this->data['grade'] ?? 'Membre');
        $poste = htmlspecialchars($this->data['poste'] ?? $this->data['role'] ?? '');
        $id = $this->data['id'];

        // Si le poste est défini, on l'affiche, sinon on affiche le grade
        $subText = !empty($poste) ? "<div class='member-post'>$poste</div>" : "<div class='member-grade'>$grade</div>";

        return <<<HTML
        <div class="member-card filter-item" data-name="$nom $prenom" data-grade="$grade">
            <div class="member-photo">
                <img src="$photo" alt="$nom">
            </div>
            <div class="member-info">
                <h3>$nom $prenom</h3>
                $subText
                <div class="member-actions">
                    <a href="member-bio.php?id=$id" class="btn-text">Biographie</a>
                    <a href="publications.php?author=$id" class="btn-text">Publications</a>
                </div>
            </div>
        </div>
HTML;
    }
}
?>