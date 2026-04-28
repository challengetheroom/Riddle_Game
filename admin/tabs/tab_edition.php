<!-- ========================================================== -->
<!-- ONGLET 2 : ÉDITION DES ÉNIGMES                             -->
<!-- ========================================================== -->
<div class="tab-content <?php echo ($activeTab === 'edition') ? 'active' : ''; ?>" id="edition">

    <!-- 2.A : GESTION DES COULEURS DES ÉNIGMES (Joueur) -->
    <div class="section" style="padding: 20px;">
        <!-- En-tête dépliable -->
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
            <h3 style="margin: 0;">🎨 Personnalisation du design</h3>
            <button type="button" id="toggle-theme" style="background: #007BFF; border: none; font-size: 18px; cursor: pointer; padding: 8px 12px; transition: background 0.2s, transform 0.3s; color: white; border-radius: 6px;" title="Plier/Déplier" onmouseover="this.style.background='#0056b3'" onmouseout="this.style.background='#007BFF'">▼</button>
        </div>

        <div id="theme-panel">
            <form method="POST" id="theme-form">
                <!-- Champs cachés pour diriger la requête vers la bonne action PHP -->
                <input type="hidden" name="action" value="update_theme">
                <input type="hidden" name="active_tab" value="edition">
                <!-- Champ caché pour conserver le nom du profil -->
                <input type="hidden" name="theme_enigmes[profile_name]" id="profile_name_input" value="<?php echo htmlspecialchars($currentProfileName, ENT_QUOTES); ?>">

                <div style="display: flex; gap: 20px;">

                    <!-- Panneau gauche : Sélecteurs de couleurs -->
                    <div style="flex: 1; background: #d9d9d9; padding: 20px; border-radius: 8px;">
                        <h4 style="margin-top: 0;">Réglages des couleurs</h4>
                        <br>

                        <?php 
                        // Charge les couleurs existantes ou utilise des couleurs par défaut
                        $theme = $datas['theme_enigmes'] ?? $datas['theme'] ?? [
                            'background' => '#f9f9f9',
                            'container_bg' => '#b5ceEE',
                            'border_color' => '#2b7cff',
                            'title_color' => '#1a4f9b',
                            'text_color' => '#333',
                            'form_bg' => '#e8f0ff',
                            'button_bg' => '#2b7cff',
                            'button_hover' => '#1a5fd1'
                        ];
                        ?>

                        <!-- Grille à 2 colonnes générée en HTML dur (pour cibler spécifiquement certains boutons via JS) -->
                        <div class="color-grid" style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
                            <label style="display: flex; align-items: center; gap: 10px;">
                                <span style="width: 140px;">Fond de page :</span>
                                <div style="display: flex; align-items: center; gap: 5px;">
                                    <input type="color" name="theme_enigmes[background]" value="<?php echo $theme['background']; ?>" class="color-picker" style="width: 40px; height: 40px; border: none; cursor: pointer; border-radius: 4px;">
                                    <input type="text" class="color-hex" value="<?php echo strtoupper($theme['background']); ?>" style="width: 75px; padding: 5px; text-align: center; font-family: monospace; font-size: 11px; border: 1px solid #ccc; border-radius: 4px; background: #fff;">
                                </div>
                            </label>

                            <label style="display: flex; align-items: center; gap: 10px;">
                                <span style="width: 140px;">Fond conteneur :</span>
                                <div style="display: flex; align-items: center; gap: 5px;">
                                    <input type="color" name="theme_enigmes[container_bg]" value="<?php echo $theme['container_bg']; ?>" class="color-picker" style="width: 40px; height: 40px; border: none; cursor: pointer; border-radius: 4px;">
                                    <input type="text" class="color-hex" value="<?php echo strtoupper($theme['container_bg']); ?>" style="width: 75px; padding: 5px; text-align: center; font-family: monospace; font-size: 11px; border: 1px solid #ccc; border-radius: 4px; background: #fff;">
                                </div>
                            </label>

                            <label style="display: flex; align-items: center; gap: 10px;">
                                <span style="width: 140px;">Couleur bordures :</span>
                                <div style="display: flex; align-items: center; gap: 5px;">
                                    <input type="color" name="theme_enigmes[border_color]" value="<?php echo $theme['border_color']; ?>" class="color-picker" style="width: 40px; height: 40px; border: none; cursor: pointer; border-radius: 4px;">
                                    <input type="text" class="color-hex" value="<?php echo strtoupper($theme['border_color']); ?>" style="width: 75px; padding: 5px; text-align: center; font-family: monospace; font-size: 11px; border: 1px solid #ccc; border-radius: 4px; background: #fff;">
                                </div>
                            </label>

                            <label style="display: flex; align-items: center; gap: 10px;">
                                <span style="width: 140px;">Couleur titre :</span>
                                <div style="display: flex; align-items: center; gap: 5px;">
                                    <input type="color" name="theme_enigmes[title_color]" value="<?php echo $theme['title_color']; ?>" class="color-picker" style="width: 40px; height: 40px; border: none; cursor: pointer; border-radius: 4px;">
                                    <input type="text" class="color-hex" value="<?php echo strtoupper($theme['title_color']); ?>" style="width: 75px; padding: 5px; text-align: center; font-family: monospace; font-size: 11px; border: 1px solid #ccc; border-radius: 4px; background: #fff;">
                                </div>
                            </label>

                            <label style="display: flex; align-items: center; gap: 10px;">
                                <span style="width: 140px;">Couleur texte :</span>
                                <div style="display: flex; align-items: center; gap: 5px;">
                                    <input type="color" name="theme_enigmes[text_color]" value="<?php echo $theme['text_color']; ?>" class="color-picker" style="width: 40px; height: 40px; border: none; cursor: pointer; border-radius: 4px;">
                                    <input type="text" class="color-hex" value="<?php echo strtoupper($theme['text_color']); ?>" style="width: 75px; padding: 5px; text-align: center; font-family: monospace; font-size: 11px; border: 1px solid #ccc; border-radius: 4px; background: #fff;">
                                </div>
                            </label>

                            <label style="display: flex; align-items: center; gap: 10px;">
                                <span style="width: 140px;">Fond formulaire :</span>
                                <div style="display: flex; align-items: center; gap: 5px;">
                                    <input type="color" name="theme_enigmes[form_bg]" value="<?php echo $theme['form_bg']; ?>" class="color-picker" style="width: 40px; height: 40px; border: none; cursor: pointer; border-radius: 4px;">
                                    <input type="text" class="color-hex" value="<?php echo strtoupper($theme['form_bg']); ?>" style="width: 75px; padding: 5px; text-align: center; font-family: monospace; font-size: 11px; border: 1px solid #ccc; border-radius: 4px; background: #fff;">
                                </div>
                            </label>

                            <label style="display: flex; align-items: center; gap: 10px;">
                                <span style="width: 140px;">Fond bouton :</span>
                                <div style="display: flex; align-items: center; gap: 5px;">
                                    <input type="color" name="theme_enigmes[button_bg]" value="<?php echo $theme['button_bg']; ?>" class="color-picker" style="width: 40px; height: 40px; border: none; cursor: pointer; border-radius: 4px;">
                                    <input type="text" class="color-hex" value="<?php echo strtoupper($theme['button_bg']); ?>" style="width: 75px; padding: 5px; text-align: center; font-family: monospace; font-size: 11px; border: 1px solid #ccc; border-radius: 4px; background: #fff;">
                                </div>
                            </label>

                            <label style="display: flex; align-items: center; gap: 10px;">
                                <span style="width: 140px;">Survol bouton :</span>
                                <div style="display: flex; align-items: center; gap: 5px;">
                                    <input type="color" name="theme_enigmes[button_hover]" value="<?php echo $theme['button_hover']; ?>" class="color-picker" style="width: 40px; height: 40px; border: none; cursor: pointer; border-radius: 4px;">
                                    <input type="text" class="color-hex" value="<?php echo strtoupper($theme['button_hover']); ?>" style="width: 75px; padding: 5px; text-align: center; font-family: monospace; font-size: 11px; border: 1px solid #ccc; border-radius: 4px; background: #fff;">
                                </div>
                            </label>
                        </div>
                        <br>
                        <!-- ========================================================= -->
                        <!-- 🆕 NOUVEAU BLOC : IMAGE DE FOND ET MIXAGE AVANCÉ          -->
                        <!-- ========================================================= -->
                        <div style="grid-column: 1 / -1; margin-top: 15px; padding-top: 15px; border-top: 1px solid #ccc;">
                            <h5 style="margin-top: 0; margin-bottom: 15px; color: #1a4f9b;">🖼️ Image de fond du conteneur</h5>

                            <!-- Champ caché pour stocker l'image en Base64 dans le JSON -->
                            <input type="hidden" name="theme_enigmes[bg_image]" id="bg_image_data" value="<?php echo htmlspecialchars($theme['bg_image'] ?? ''); ?>">

                            <div style="display: flex; gap: 15px; align-items: center; margin-bottom: 15px;">
                                <!-- Boutons d'Upload et de Suppression -->
                                <input type="file" id="bg_image_upload" accept="image/*" style="display: none;">
                                <button type="button" id="btn_upload_bg" style="background: #28a745; width: auto; padding: 8px 15px; font-size: 13px;">📁 Choisir une image</button>
                                <button type="button" id="btn_remove_bg" style="background: #dc3545; width: auto; padding: 8px 15px; font-size: 13px; <?php echo empty($theme['bg_image']) ? 'display:none;' : ''; ?>">🗑️ Retirer</button>
                                <span id="bg_image_status" style="font-size: 12px; color: #666;"><?php echo empty($theme['bg_image']) ? 'Aucune image' : 'Image chargée ✓'; ?></span>
                            </div>

                            <!-- Grille des réglages avancés (Scale, Pos X, Pos Y, Opacité) -->
                            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; background: #e9ecef; padding: 15px; border-radius: 8px;">

                                <!-- Opacité de la couleur (Mixage) -->
                                <label style="display: flex; justify-content: space-between; align-items: center;">
                                    <span style="font-size: 13px; color: #333;" title="Opacité de la couleur 'Fond conteneur' (0% = Transparent, 100% = Opaque)">Mixage (Opacité %) :</span>
                                    <input type="number" name="theme_enigmes[bg_opacity]" id="bg_opacity" class="drag-input" step="1" min="0" max="100" value="<?php echo $theme['bg_opacity'] ?? '100'; ?>" style="width: 70px; padding: 5px; text-align: center; border: 1px solid #ccc; border-radius: 4px; cursor: ew-resize;">
                                </label>

                                <!-- Échelle de l'image (Scale) -->
                                <label style="display: flex; justify-content: space-between; align-items: center;">
                                    <span style="font-size: 13px; color: #333;">Scale / Taille (%) :</span>
                                    <input type="number" name="theme_enigmes[bg_scale]" id="bg_scale" class="drag-input" step="5" min="10" max="500" value="<?php echo $theme['bg_scale'] ?? '100'; ?>" style="width: 70px; padding: 5px; text-align: center; border: 1px solid #ccc; border-radius: 4px; cursor: ew-resize;">
                                </label>

                                <!-- Position X -->
                                <label style="display: flex; justify-content: space-between; align-items: center;">
                                    <span style="font-size: 13px; color: #333;">Position Horiz. X (%) :</span>
                                    <input type="number" name="theme_enigmes[bg_pos_x]" id="bg_pos_x" class="drag-input" step="1" value="<?php echo $theme['bg_pos_x'] ?? '50'; ?>" style="width: 70px; padding: 5px; text-align: center; border: 1px solid #ccc; border-radius: 4px; cursor: ew-resize;">
                                </label>

                                <!-- Position Y -->
                                <label style="display: flex; justify-content: space-between; align-items: center;">
                                    <span style="font-size: 13px; color: #333;">Position Verti. Y (%) :</span>
                                    <input type="number" name="theme_enigmes[bg_pos_y]" id="bg_pos_y" class="drag-input" step="1" value="<?php echo $theme['bg_pos_y'] ?? '50'; ?>" style="width: 70px; padding: 5px; text-align: center; border: 1px solid #ccc; border-radius: 4px; cursor: ew-resize;">
                                </label>

                            </div>

                            <div style="font-size: 11px; color: #777; margin-top: 8px; text-align: center;">
                                💡 <i>Astuce : Cliquez sur un nombre et glissez la souris de gauche à droite pour l'ajuster rapidement !</i>
                            </div>

                            <!-- BOUTON RESET (Nouveau) -->
                            <div style="text-align: center; margin-top: 15px;">
                                <button type="button" id="reset-bg-settings" style="background: #6c757d; color: white; padding: 6px 12px; font-size: 13px; border-radius: 4px; border: none; cursor: pointer; transition: background 0.2s;" onmouseover="this.style.background='#5a6268'" onmouseout="this.style.background='#6c757d'">
                                    ↻ Réinitialiser les réglages de l'image
                                </button>
                            </div>
                        </div>
                        <!-- ========================================================= -->
                        <!-- ========================================================= -->
                        <br>
                        <button type="submit" style="margin-top: 20px; width: 100%;">💾 Enregistrer le design</button>
                    </div>

                    <!-- Panneau droit : Aperçu visuel géré par JavaScript -->
                    <div style="flex: 1; background: #d9d9d9; padding: 20px; border-radius: 8px;">
                        <h4 style="margin-top: 0;">Aperçu en direct</h4>
                        <br>

                        <div id="preview" style="padding: 20px; border-radius: 12px; min-height: 300px;">
                            <div id="preview-container" style="padding: 30px; border-radius: 12px; border: 2px solid; box-shadow: 0 4px 20px rgba(0,0,0,0.1);">
                                <h2 id="preview-title" style="margin: 0 0 15px 0; text-align: center;">Titre exemple</h2>
                                <p id="preview-text" style="margin: 0 0 20px 0; text-align: center;">Ceci est le texte d'exemple.</p>

                                <div id="preview-form-box" style="padding: 15px; border-radius: 12px; box-shadow: 0 3px 6px rgba(0,0,0,0.1);">
                                    <div style="margin-bottom: 15px;">
                                        <label style="display: block; margin-bottom: 5px; font-weight: normal; text-align: center;">Champ texte exemple :</label>
                                        <input id="preview-input" type="text" placeholder="Exemple" style="width: 100%; padding: 10px; border-radius: 8px; border: 1px solid; font-size: 16px; box-sizing: border-box;">
                                    </div>
                                    <button id="preview-button" type="button" style="width: 100%; padding: 12px 20px; border: none; border-radius: 8px; cursor: pointer; font-size: 16px; color: white; transition: 0.2s;">Bouton exemple</button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
    <br>

    <!-- 2.B : OPTIONS GLOBALES -->
    <!-- ======================================================================== -->
    <!-- BLOC REGROUPÉ : OPTIONS GLOBALES (GAUCHE) ET ZONE DE DANGER (DROITE)     -->
    <!-- ======================================================================== -->
    <div class="section" style="padding: 20px; background: #f4f4f4; border-radius: 8px; border-left: 4px solid #6c757d; box-shadow: 0 2px 10px rgba(0,0,0,0.05);">

        <!-- En-tête avec bouton Plier/Déplier -->
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
            <h3 style="margin: 0; color: #333;">⚙️ Gestion globale du jeu</h3>
            <button type="button" id="toggle-global" style="background: #007BFF; border: none; font-size: 18px; cursor: pointer; padding: 8px 12px; transition: background 0.2s, transform 0.3s; color: white; border-radius: 6px;" title="Plier/Déplier" onmouseover="this.style.background='#0056b3'" onmouseout="this.style.background='#007BFF'">
                ▼
            </button>
        </div>

        <!-- Conteneur pliable -->
        <div id="global-panel">
            <div style="display: flex; gap: 20px; flex-wrap: wrap;">

                <!-- MOITIÉ GAUCHE : Options Thème Jaune -->
                <div style="flex: 1; min-width: 300px; background: #fff3cd; padding: 20px; border-radius: 8px; border-left: 4px solid #ffc107; display: flex; flex-direction: column; box-shadow: 0 2px 5px rgba(0,0,0,0.05);">
                    <form method="POST" style="display: flex; flex-direction: column; height: 100%;">
                        <input type="hidden" name="action" value="update_options">
                        <input type="hidden" name="active_tab" value="edition">

                        <h4 style="margin-top: 0; color: #856404; margin-bottom: 15px;">Règles du jeu</h4>

                        <?php $is_single_attempt = $datas['options']['une_seule_tentative'] ?? false; ?>

                        <div style="flex-grow: 1;">
                            <label style="display: flex; align-items: center; gap: 10px; cursor: pointer; font-size: 14px; font-weight: bold; color: #856404;">
                                <input type="checkbox" name="une_seule_tentative" value="1" <?php echo $is_single_attempt ? 'checked' : ''; ?> style="width: 18px; height: 18px; cursor: pointer;">
                                1 seule tentative par joueur
                            </label>
                            <div style="font-size: 13px; color: #856404; margin-top: 5px; margin-left: 28px; margin-bottom: 15px; opacity: 0.9;">
                                Si coché, les joueurs sont bloqués après une erreur. Sinon, les essais sont illimités jusqu'à trouver la bonne réponse.
                            </div>

                            <!-- ========================================================= -->
                            <!-- 🆕 NOUVEAU : CASE À COCHER POUR L'ALERTE                  -->
                            <!-- ========================================================= -->
                            <label style="display: flex; align-items: center; gap: 10px; cursor: pointer; font-size: 14px; font-weight: bold; color: #856404; margin-top: 15px; border-top: 1px solid #ffeeba; padding-top: 15px;">
                                <!-- Pas d'attribut 'name' car c'est géré 100% en JS localement -->
                                <input type="checkbox" id="warn_unsaved" style="width: 18px; height: 18px; cursor: pointer;" checked>
                                Alerte "Modifications non sauvegardées"
                            </label>
                            <div style="font-size: 13px; color: #856404; margin-top: 5px; margin-left: 28px; margin-bottom: 15px; opacity: 0.9;">
                                Affiche une popup si vous tentez de recharger (F5) ou quitter la page alors que vous avez modifié une couleur ou une énigme sans cliquer sur "Mettre à jour".
                            </div>
                            <!-- ========================================================= -->

                        </div>
                        <button type="submit" style="background: #ffc107; color: #333; font-weight: bold; width: 100%; border: 1px solid #d39e00; transition: background 0.2s;" onmouseover="this.style.background='#e0a800'" onmouseout="this.style.background='#ffc107'">
                            Enregistrer les règles
                        </button>
                    </form>
                </div>

                <!-- MOITIÉ DROITE : Zone de danger (Rouge) -->
                <div style="flex: 1; min-width: 300px; background: #fff5f5; padding: 20px; border-radius: 8px; border-left: 4px solid #dc3545; display: flex; flex-direction: column; box-shadow: 0 2px 5px rgba(0,0,0,0.05);">
                    <form method="POST" style="display: flex; flex-direction: column; height: 100%;">
                        <input type="hidden" name="action" value="delete_all_enigmes">
                        <input type="hidden" name="active_tab" value="edition">

                        <h4 style="margin-top: 0; color: #dc3545; margin-bottom: 15px;">⚠️ Zone de danger</h4>

                        <div style="flex-grow: 1;">
                            <div style="font-size: 13px; color: #a71d2a; margin-bottom: 15px;">
                                Cette action supprimera <b>absolument toutes les énigmes</b> de la grille ci-dessous en un seul clic. <br><br>
                                <i>Note : Vos configurations (couleurs, options globales, messages) seront conservées.</i>
                            </div>
                        </div>

                        <button type="submit" style="background: #dc3545; color: white; font-weight: bold; width: 100%; border: 1px solid #c82333; transition: background 0.2s;" onclick="return confirm('🛑 ATTENTION : Êtes-vous absolument certain de vouloir supprimer TOUTES les énigmes ?\\n\\nCette action est IRRÉVERSIBLE !');" onmouseover="this.style.background='#c82333'" onmouseout="this.style.background='#dc3545'">
                            🗑️ Supprimer TOUTES les énigmes
                        </button>
                    </form>
                </div>

            </div>
        </div>
    </div>
    <br>

    <!-- 2.C : FORMULAIRE D'AJOUT D'UNE NOUVELLE ÉNIGME -->
    <form method="POST" class="section">
        <input type="hidden" name="action" value="add_enigme">
        <input type="hidden" name="active_tab" value="edition">
        <label>Nom de la nouvelle énigme</label>
        <input type="text" name="nouvelle_enigme" placeholder="Enigme01" required>
        <button type="submit">Ajouter énigme</button>
    </form>

    <br>

    <!-- 2.D : GRILLE DES ÉNIGMES EXISTANTES -->
    <div class="enigme-grid">
        <?php foreach($datas as $nom=>$e):
        // On ne boucle que sur les vraies énigmes, pas sur les configurations
        if (in_array($nom, $RESERVED_KEYS)) continue;

        $token = $e['meta']['token'] ?? '';
        $scheme = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https://' : 'http://');

        // Calcule l'URL de base selon l'environnement (Dev local vs Serveur de production)
        if ($environment === 'dev') {
            $baseURL = $scheme . 'riddlegame';
        } else {
            $baseURL = $scheme . 'ctr.komotion.fr/Projets/Riddle_Game';
        }

        // Génère le lien complet avec le token pour le joueur
        $url = $token ? $baseURL . '/index.php?k=' . urlencode($token) : '';
        ?>
        <form method="POST" class="section">
            <input type="hidden" name="enigme" value="<?php echo htmlspecialchars($nom,ENT_QUOTES); ?>">
            <input type="hidden" name="active_tab" value="edition">

            <!-- MINIATURE DU QR CODE -->
            <?php
            $safeName = preg_replace('/[^a-zA-Z0-9_-]/', '_', $nom);
            $qrPath = 'QRCodes/' . $safeName . '.png';
            if (file_exists(__DIR__ . '/../' . $qrPath)):
            ?>
            <div style="float: right; margin-left: 15px; margin-bottom: 10px; text-align: center;">
                <img src="<?php echo $qrPath; ?>?t=<?php echo time(); ?>" 
                     style="width: 80px; height: 80px; border: 1px solid #ccc; border-radius: 8px; cursor: pointer; box-shadow: 0 2px 5px rgba(0,0,0,0.1); transition: 0.2s;" 
                     onclick="openQrPopup('<?php echo $qrPath; ?>', '<?php echo htmlspecialchars($nom, ENT_QUOTES); ?>')" 
                     onmouseover="this.style.transform='scale(1.05)'" onmouseout="this.style.transform='scale(1)'"
                     title="Voir et télécharger le QR Code">
            </div>
            <?php endif; ?>

            <h3><?php echo htmlspecialchars($nom,ENT_QUOTES); ?></h3>

            <?php if($token): ?>
            <p><strong>Token : </strong><?php echo htmlspecialchars($token,ENT_QUOTES); ?></p>
            <p><strong>URL :</strong><br><a href="<?php echo htmlspecialchars($url,ENT_QUOTES); ?>" target="_blank"><?php echo htmlspecialchars($url,ENT_QUOTES); ?></a></p>
            <?php else: ?>
            <p><em>Pas de token associé</em></p>
            <?php endif; ?>

            <br>
            <label>Texte de l'énigme :</label>
            <textarea name="texte"><?php echo htmlspecialchars($e['texte'] ?? '',ENT_QUOTES); ?></textarea>

            <label>Réponse(s) correcte(s) :</label>
            <div style="font-size: 0.85em; color: #666; margin-bottom: 3px;">
                Pour plusieurs réponses possibles, séparez-les par un point-virgule (;). Exemple : VELO; BICYCLETTE
            </div>
            <?php
            // Si plusieurs réponses sont stockées sous forme de tableau, on les recolle avec un ";" pour l'affichage
            $val_reponse = $e['reponse_correcte'] ?? '';
            if (is_array($val_reponse)) {
                $val_reponse = implode('; ', $val_reponse);
            }
            ?>
            <input type="text" name="reponse_correcte" value="<?php echo htmlspecialchars($val_reponse,ENT_QUOTES); ?>" placeholder="Réponse attendue">

            <br><br>

            <!-- ========================================== -->
            <!-- MESSAGES UNIQUES POUR CETTE ÉNIGME         -->
            <!-- ========================================== -->
            <div style="background: #f9f9f9; padding: 15px; border-radius: 8px; border: 1px solid #ddd; margin-bottom: 10px;">
                <h4 style="margin-top: 0; margin-bottom: 15px; color: #555; font-size: 14px;">MESSAGES DE RÉSULTAT SPÉCIFIQUES :</h4>

                <?php 
                // Récupère les messages uniques s'ils existent
                $hasUniqueBonne = isset($e['messages_uniques']['bonne_reponse']);
                $uniqueBonneMsg = $hasUniqueBonne ? $e['messages_uniques']['bonne_reponse'] : '';

                $hasUniqueMauvaise = isset($e['messages_uniques']['mauvaise_reponse']);
                $uniqueMauvaiseMsg = $hasUniqueMauvaise ? $e['messages_uniques']['mauvaise_reponse'] : '';

                $hasUniqueDeja = isset($e['messages_uniques']['deja_repondu']);
                $uniqueDejaMsg = $hasUniqueDeja ? $e['messages_uniques']['deja_repondu'] : '';

                // Génère un ID unique pour le JavaScript car il y a plusieurs énigmes sur la page
                $safeId = preg_replace('/[^a-zA-Z0-9]/', '', $nom); 
                ?>

                <!-- 1. BONNE RÉPONSE UNIQUE -->
                <div style="margin-bottom: 10px;">
                    <label style="display: flex; align-items: center; gap: 8px; cursor: pointer; font-weight: bold; color: #28a745;">
                        <input type="checkbox" name="use_unique_bonne" value="1" <?php echo $hasUniqueBonne ? 'checked' : ''; ?> onchange="document.getElementById('div_unique_bonne_<?php echo $safeId; ?>').style.display = this.checked ? 'block' : 'none';">
                        UNIQUE - Bonne réponse
                    </label>
                    <div id="div_unique_bonne_<?php echo $safeId; ?>" style="display: <?php echo $hasUniqueBonne ? 'block' : 'none'; ?>; margin-top: 5px;">
                        <textarea name="unique_bonne_reponse" rows="3" style="width: 100%; border: 1px solid #28a745; box-sizing: border-box;"><?php echo htmlspecialchars($uniqueBonneMsg, ENT_QUOTES); ?></textarea>
                    </div>
                </div>

                <!-- 2. MAUVAISE RÉPONSE UNIQUE -->
                <div style="margin-bottom: 10px;">
                    <label style="display: flex; align-items: center; gap: 8px; cursor: pointer; font-weight: bold; color: #dc3545;">
                        <input type="checkbox" name="use_unique_mauvaise" value="1" <?php echo $hasUniqueMauvaise ? 'checked' : ''; ?> onchange="document.getElementById('div_unique_mauvaise_<?php echo $safeId; ?>').style.display = this.checked ? 'block' : 'none';">
                        UNIQUE - Mauvaise réponse
                    </label>
                    <div id="div_unique_mauvaise_<?php echo $safeId; ?>" style="display: <?php echo $hasUniqueMauvaise ? 'block' : 'none'; ?>; margin-top: 5px;">
                        <textarea name="unique_mauvaise_reponse" rows="3" style="width: 100%; border: 1px solid #dc3545; box-sizing: border-box;"><?php echo htmlspecialchars($uniqueMauvaiseMsg, ENT_QUOTES); ?></textarea>
                    </div>
                </div>

                <!-- 3. DÉJÀ RÉPONDU UNIQUE -->
                <div>
                    <label style="display: flex; align-items: center; gap: 8px; cursor: pointer; font-weight: bold; color: #fd7e14;">
                        <input type="checkbox" name="use_unique_deja" value="1" <?php echo $hasUniqueDeja ? 'checked' : ''; ?> onchange="document.getElementById('div_unique_deja_<?php echo $safeId; ?>').style.display = this.checked ? 'block' : 'none';">
                        UNIQUE - Déjà répondu
                    </label>
                    <div id="div_unique_deja_<?php echo $safeId; ?>" style="display: <?php echo $hasUniqueDeja ? 'block' : 'none'; ?>; margin-top: 5px;">
                        <textarea name="unique_deja_repondu" rows="3" style="width: 100%; border: 1px solid #fd7e14; box-sizing: border-box;"><?php echo htmlspecialchars($uniqueDejaMsg, ENT_QUOTES); ?></textarea>
                    </div>
                </div>
            </div>
            <!-- ========================================== -->

            <div style="margin-top:10px;">
                <button type="submit" name="action" value="update_enigme">Mettre à jour</button>
                <!-- Bouton suppression avec sécurité JavaScript -->
                <button type="submit" name="action" value="delete_enigme" onclick="return confirm('Voulez-vous vraiment supprimer cette énigme ?');">Supprimer</button>
            </div>
        </form>
        <?php endforeach; ?>
    </div>
</div>