// assets/js/CrudManager.js

class CrudManager {
    constructor(config) {
        this.entityName = config.entityName; // ex: 'Project'
        this.apiEndpoint = config.apiEndpoint; // ex: '../controllers/api.php'
        this.modalId = config.modalId;
        this.formId = config.formId;
        this.alertContainer = config.alertContainer || 'alertContainer';
        
        // Hooks pour personnaliser le comportement
        this.onLoadData = config.onLoadData || null; 
        this.onSaveSuccess = config.onSaveSuccess || (() => location.reload());
    }

    // Gestion des Alertes (plus besoin de la copier-coller partout)
    showAlert(message, type = 'info') {
        const container = document.getElementById(this.alertContainer);
        if (!container) return alert(message);
        
        const div = document.createElement('div');
        div.className = `alert alert-${type} show`;
        div.innerHTML = message;
        container.appendChild(div);
        
        setTimeout(() => div.classList.add('visible'), 10);
        setTimeout(() => {
            div.classList.remove('visible');
            setTimeout(() => div.remove(), 300);
        }, 5000);
    }

    // Suppression générique
    async delete(id) {
        if (!confirm(`Voulez-vous vraiment supprimer cet élément (${this.entityName}) ?`)) return;

        try {
            const res = await fetch(`${this.apiEndpoint}?action=delete${this.entityName}&id=${id}`, { method: 'POST' });
            const json = await res.json();
            
            if (json.success) {
                this.showAlert('✅ Supprimé avec succès', 'success');
                setTimeout(this.onSaveSuccess, 1000);
            } else {
                this.showAlert('❌ ' + json.message, 'error');
            }
        } catch (e) {
            console.error(e);
            this.showAlert('❌ Erreur serveur', 'error');
        }
    }

    // Sauvegarde générique (Create / Update)
    async save() {
        const form = document.getElementById(this.formId);
        if (!form.checkValidity()) {
            form.reportValidity();
            return;
        }

        const formData = new FormData(form);
        // Transformation FormData -> JSON
        const data = Object.fromEntries(formData.entries());
        
        // Détection automatique ID (si présent = update, sinon = create)
        const id = formData.get('id'); 
        const action = id ? `update${this.entityName}&id=${id}` : `create${this.entityName}`;

        try {
            const res = await fetch(`${this.apiEndpoint}?action=${action}`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(data)
            });
            const json = await res.json();

            if (json.success) {
                this.showAlert('✅ Enregistré !', 'success');
                this.closeModal();
                setTimeout(this.onSaveSuccess, 1000);
            } else {
                // Gestion des erreurs (tableau ou string)
                const msg = Array.isArray(json.errors) ? json.errors.join('<br>') : json.message;
                this.showAlert('❌ ' + msg, 'error');
            }
        } catch (e) {
            console.error(e);
            this.showAlert('❌ Erreur technique', 'error');
        }
    }

    // Remplissage automatique du formulaire pour l'édition
    async loadForEdit(id) {
        try {
            const res = await fetch(`${this.apiEndpoint}?action=get${this.entityName}&id=${id}`);
            const json = await res.json();
            
            if (json.success && json.data) {
                const data = json.data;
                // Boucle magique : remplit les inputs dont l'ID correspond aux clés JSON
                for (const key in data) {
                    const input = document.getElementById(key); // Assure-toi que les ID HTML = clés JSON
                    if (input) {
                        input.value = data[key];
                    }
                }
                
                // Appel d'un hook spécifique si besoin (ex: pour des selects complexes)
                if (this.onLoadData) this.onLoadData(data);

                this.openModal(true);
            }
        } catch (e) { console.error(e); }
    }

    openModal(isEdit = false) {
        document.getElementById('modalTitle').innerText = isEdit ? `Modifier ${this.entityName}` : `Ajouter ${this.entityName}`;
        document.getElementById(this.modalId).classList.add('active');
        if (!isEdit) {
            document.getElementById(this.formId).reset();
            document.querySelector(`#${this.formId} input[name="id"]`).value = "";
        }
    }

    closeModal() {
        document.getElementById(this.modalId).classList.remove('active');
    }
    
}
class TableRenderer {
    constructor(tableBodyId, config) {
        this.tbody = document.getElementById(tableBodyId);
        this.config = config; // { columns: [], actions: [] }
    }

    render(data) {
        this.tbody.innerHTML = '';

        if (!data || data.length === 0) {
            const colCount = this.config.columns.length + (this.config.actions ? 1 : 0);
            this.tbody.innerHTML = `<tr><td colspan="${colCount}" style="text-align:center; padding:20px; color:#666;">Aucune donnée trouvée</td></tr>`;
            return;
        }

        data.forEach(item => {
            const tr = document.createElement('tr');

            // 1. Génération des Colonnes
            this.config.columns.forEach(col => {
                const td = document.createElement('td');
                
                // Récupération de la valeur (supporte "user.nom" ou fonction custom)
                let value = item[col.key];
                
                // Formateur personnalisé (ex: pour les dates ou les badges)
                if (col.render) {
                    td.innerHTML = col.render(item); // On passe l'objet entier pour plus de flexibilité
                } else {
                    td.textContent = value || '-';
                }
                
                tr.appendChild(td);
            });

            // 2. Génération des Actions
            if (this.config.actions) {
                const tdActions = document.createElement('td');
                this.config.actions.forEach(action => {
                    const btn = document.createElement('button');
                    btn.className = action.class || 'btn-sm';
                    btn.innerHTML = action.icon || '•';
                    btn.title = action.title || '';
                    if (action.style) btn.style.cssText = action.style;
                    
                    // Gestion du clic
                    btn.onclick = () => action.onClick(item);
                    
                    tdActions.appendChild(btn);
                    tdActions.appendChild(document.createTextNode(' ')); // Espace
                });
                tr.appendChild(tdActions);
            }

            this.tbody.appendChild(tr);
        });
    }
}