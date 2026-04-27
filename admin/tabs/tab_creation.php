<!-- ========================================================== -->
<!-- ONGLET : CRÉATION DE MESSAGE (Bac à sable WYSIWYG)         -->
<!-- ========================================================== -->
<div class="tab-content <?php echo ($activeTab === 'creation') ? 'active' : ''; ?>" id="creation">
    <div class="section" style="padding: 20px;">
        <h3 style="margin-top: 0;">🎨 Atelier de Création de Message</h3>
        <p style="color: #555; margin-bottom: 20px;">
            Utilisez cet éditeur visuel pour composer votre message. L'aperçu à droite se met à jour en temps réel avec les couleurs de votre thème actuel.<br>
            Une fois terminé, cliquez sur le bouton en bas pour obtenir le code HTML à copier dans vos énigmes !
        </p>

        <?php 
        // On récupère le thème actuel pour coloriser l'aperçu de droite
        $theme_msg = $datas['theme_messages'] ?? [
            'background' => '#f9f9f9',
            'container_bg' => '#b5ceEE',
            'border_color' => '#2b7cff',
            'title_color' => '#1a4f9b',
            'text_color' => '#333'
        ];
        ?>

        <div style="display: flex; gap: 20px; flex-wrap: wrap;">

            <!-- COLONNE GAUCHE : L'Éditeur WYSIWYG -->
            <div style="flex: 1; min-width: 400px;">
                <h4 style="margin-top: 0;">1. Composez votre message</h4>

                <!-- C'est ce textarea qui sera transformé par TinyMCE -->
                <textarea id="wysiwyg-editor"><h2>Mon super titre !</h2><p>Voici un texte d'exemple...</p></textarea>

                <div style="margin-top: 20px; background: #e8f0ff; padding: 15px; border-radius: 8px; border: 1px solid #b5ceEE;">
                    <h4 style="margin-top: 0; color: #1a4f9b;">2. Récupérez le code HTML</h4>
                    <p style="font-size: 13px; color: #555; margin-bottom: 10px;">Copiez ce code et collez-le dans les champs textes des autres onglets.</p>
                    <textarea id="html-output" rows="5" style="width: 100%; font-family: monospace; font-size: 13px; background: #fff; padding: 10px; border-radius: 4px; border: 1px solid #ccc; resize: vertical;" readonly></textarea>

                    <button type="button" id="btn-copy-html" style="width: 100%; margin-top: 10px; background: #28a745;">📋 Copier le code HTML</button>
                </div>
            </div>

            <!-- COLONNE DROITE : L'Aperçu en direct (Live Preview) -->
            <div style="flex: 1; min-width: 300px; background: #d9d9d9; padding: 20px; border-radius: 8px;">
                <h4 style="margin-top: 0; margin-bottom: 15px;">Aperçu en situation</h4>

                <div style="padding: 20px; border-radius: 12px; display:flex; justify-content:center; background-color: <?php echo $theme_msg['background']; ?>;">

                    <!-- Le faux cadre du joueur -->
                    <div style="width: 100%; max-width: 500px; padding: 20px; border-radius: 12px; text-align: center; 
                                border: 2px solid <?php echo $theme_msg['border_color']; ?>; 
                                background-color: <?php echo $theme_msg['container_bg']; ?>; 
                                color: <?php echo $theme_msg['text_color']; ?>; 
                                box-shadow: 0 4px 15px rgba(0,0,0,0.1);">

                        <!-- C'est ici que le JavaScript va injecter le contenu tapé à gauche -->
                        <div id="live-preview-content" style="color: <?php echo $theme_msg['text_color']; ?>;">
                            <!-- Par défaut, on s'assure que les titres à l'intérieur prennent la bonne couleur via CSS inliné plus bas -->
                        </div>

                    </div>
                </div>
            </div>

        </div>
    </div>
</div>

<!-- On force le style généré dans l'aperçu pour respecter le thème ET l'affichage des listes -->
<style>
    /* Force la couleur des titres générés par l'éditeur */
    #live-preview-content h1, 
    #live-preview-content h2, 
    #live-preview-content h3 {
        color: <?php echo $theme_msg['title_color']; ?> !important;
    }

    /* Reproduction exacte du style des listes du jeu public */
    #live-preview-content ul, 
    #live-preview-content ol {
        display: inline-block; /* Permet au bloc entier de se centrer */
        text-align: left;      /* Mais garde le texte et les puces alignés à gauche à l'intérieur */
        margin: 15px auto;     /* Espacement haut/bas */
        padding-left: 25px;    /* Espace pour afficher les puces */
    }

    #live-preview-content li {
        margin-bottom: 8px;    /* Espace entre chaque ligne de la liste */
    }
</style>

<!-- ========================================================== -->
<!-- SCRIPT INITIALISATION TINYMCE & LIVE PREVIEW               -->
<!-- ========================================================== -->
<script>
    document.addEventListener('DOMContentLoaded', function() {

        // Fonction qui met à jour l'aperçu à droite et la boîte de code source
        function updateLivePreview(content) {
            document.getElementById('live-preview-content').innerHTML = content;
            document.getElementById('html-output').value = content;
        }

        // Initialisation de l'éditeur TinyMCE
        tinymce.init({
            selector: '#wysiwyg-editor',
            height: 400,
            menubar: false, 
            promotion: false, 
            branding: false,  
            language: 'fr_FR', 

            plugins: 'lists link image media table code wordcount',
            // NOUVEAU : J'ai ajouté notre bouton "customborder" juste après les couleurs de texte
            toolbar: 'undo redo | blocks | bold italic underline strikethrough | forecolor backcolor customborder | alignleft aligncenter alignright | bullist numlist | link image | removeformat',

            image_advtab: true,
            image_dimensions: false, 
            object_resizing: true,

            setup: function(editor) {

                // =================================================================
                // CRÉATION DE NOTRE BOUTON PERSONNALISÉ "BORDURE"
                // =================================================================
                editor.ui.registry.addButton('customborder', {
                    text: 'Bordure',
                    tooltip: 'Ajouter une bordure colorée',
                    onAction: function (_) {
                        editor.windowManager.open({
                            title: 'Ajouter une bordure',
                            body: {
                                type: 'panel',
                                items: [
                                    {
                                        type: 'input',
                                        name: 'bordercolor',
                                        label: 'Couleur (ex: #2b7cff, red, black...)'
                                    },
                                    {
                                        type: 'input',
                                        name: 'borderwidth',
                                        label: 'Épaisseur (ex: 2px, 5px...)'
                                    }
                                ]
                            },
                            initialData: {
                                bordercolor: '#2b7cff',
                                borderwidth: '2px'
                            },
                            // NOUVEAU : On déclare les boutons d'action !
                            buttons: [
                                {
                                    type: 'cancel',
                                    text: 'Annuler'
                                },
                                {
                                    type: 'submit',
                                    text: 'Appliquer',
                                    primary: true // Le met en surbrillance bleue
                                }
                            ],
                            onSubmit: function (api) {
                                var data = api.getData();
                                var node = editor.selection.getNode();

                                // On vérifie que les deux champs sont remplis
                                if (data.bordercolor && data.borderwidth) {
                                    editor.dom.setStyle(node, 'border', data.borderwidth + ' solid ' + data.bordercolor);
                                    editor.dom.setStyle(node, 'border-radius', '8px');
                                    editor.fire('change');
                                }
                                api.close();
                            }
                        });
                    }
                });

                // =================================================================
                // GESTION DU LIVE PREVIEW
                // =================================================================
                editor.on('init', function() {
                    updateLivePreview(editor.getContent());
                });
                editor.on('keyup change execCommand', function() {
                    updateLivePreview(editor.getContent());
                });
            }
        });

        // =================================================================
        // GESTION DU BOUTON DE COPIE (Compatible HTTP)
        // =================================================================
        document.getElementById('btn-copy-html').addEventListener('click', function() {
            var copyText = document.getElementById('html-output');

            // Sélectionne le texte
            copyText.select();
            copyText.setSelectionRange(0, 99999); // Pour les mobiles

            try {
                // Utilise l'ancienne méthode compatible avec les sites non sécurisés (HTTP)
                document.execCommand('copy');

                // Effet visuel de succès
                var btn = document.getElementById('btn-copy-html');
                var originalText = btn.innerHTML;
                var originalBg = btn.style.background;

                btn.innerHTML = "✅ Code copié !";
                btn.style.background = "#17a2b8";

                setTimeout(function() {
                    btn.innerHTML = originalText;
                    btn.style.background = originalBg;
                }, 2000);
            } catch (err) {
                alert("Votre navigateur empêche la copie automatique. Veuillez faire un clic-droit > Copier.");
            }
        });
    });
</script>