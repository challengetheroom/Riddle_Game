<!-- ========================================================== -->
<!-- ONGLET 3 : ÉDITION DES MESSAGES DE RÉSULTAT                -->
<!-- ========================================================== -->
<div class="tab-content <?php echo ($activeTab === 'messages') ? 'active' : ''; ?>" id="messages">
    <div class="section" style="padding: 20px;">
        <h3 style="margin-top: 0;">💬 Messages de résultat et couleurs</h3>

        <?php 
        // Chargement des données existantes (ou par défaut)
        $theme_msg = $datas['theme_messages'] ?? [
            'background' => '#f9f9f9',
            'container_bg' => '#b5ceEE',
            'border_color' => '#2b7cff',
            'title_color' => '#1a4f9b',
            'text_color' => '#333'
        ];
        $msgs = $datas['messages'] ?? [
            'bonne_reponse' => "<h2>Félicitations !</h2>\n<h3>C'est la bonne réponse !</h3>\n<br>\n<p>Tu as bien été inscrit au tirage au sort...</p>",
            'mauvaise_reponse' => "<h2>Oh noooon !</h2>\n<h3>Désolé, ce n'est pas la bonne réponse...</h3>",
            'deja_repondu' => "<h2>Tu as déjà répondu à cette énigme.</h2>\n<h3>Rendez-vous chez les autres partenaires...</h3>"
        ];
        ?>

        <form method="POST">
            <input type="hidden" name="action" value="update_messages">
            <input type="hidden" name="active_tab" value="messages">

            <div style="display: flex; gap: 20px; flex-wrap: wrap;">

                <!-- 3.A : Colonne de gauche - Formulaire (Couleurs et Textes) -->
                <div style="flex: 1; min-width: 300px; background: #d9d9d9; padding: 20px; border-radius: 8px;">
                    <h4>Couleurs de la page de résultat</h4>

                    <!-- Génération dynamique de la grille des couleurs via une boucle PHP (code plus propre que dans le 1er onglet) -->
                    <div class="color-grid" style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 20px;">
                        <?php foreach (['background' => 'Fond de page', 'container_bg' => 'Fond conteneur', 'border_color' => 'Couleur bordures', 'title_color' => 'Couleur titre', 'text_color' => 'Couleur texte'] as $key => $label): ?>
                        <label style="display: flex; align-items: center; gap: 10px;">
                            <span style="width: 140px;"><?php echo $label; ?> :</span>
                            <div style="display: flex; align-items: center; gap: 5px;">
                                <input type="color" name="theme_messages[<?php echo $key; ?>]" value="<?php echo $theme_msg[$key]; ?>" class="color-picker msg-color" style="width: 40px; height: 40px; border: none; cursor: pointer; border-radius: 4px;">
                                <input type="text" class="color-hex msg-hex" value="<?php echo strtoupper($theme_msg[$key]); ?>" style="width: 75px; padding: 5px; text-align: center; font-family: monospace; font-size: 11px; border: 1px solid #ccc; border-radius: 4px; background: #fff;">
                            </div>
                        </label>
                        <?php endforeach; ?>
                    </div>

                    <h5><i style="color: green;">Textes (HTML autorisé)</i></h5>
                    <label><b>Bonne réponse :</b></label>
                    <textarea name="msg_bonne_reponse" class="msg-input" rows="4" style="width: 100%; margin-bottom: 10px; box-sizing:border-box; padding:8px;"><?php echo htmlspecialchars($msgs['bonne_reponse']); ?></textarea>

                    <label><b>Mauvaise réponse :</b></label>
                    <textarea name="msg_mauvaise_reponse" class="msg-input" rows="4" style="width: 100%; margin-bottom: 10px; box-sizing:border-box; padding:8px;"><?php echo htmlspecialchars($msgs['mauvaise_reponse']); ?></textarea>

                    <label><b>Déjà répondu :</b></label>
                    <textarea name="msg_deja_repondu" class="msg-input" rows="4" style="width: 100%; margin-bottom: 15px; box-sizing:border-box; padding:8px;"><?php echo htmlspecialchars($msgs['deja_repondu']); ?></textarea>

                    <button type="submit" style="width: 100%;">💾 Enregistrer textes et couleurs</button>
                </div>

                <!-- 3.B : Colonne de droite - Aperçu en direct géré par JS -->
                <div style="flex: 1; min-width: 300px; background: #d9d9d9; padding: 20px; border-radius: 8px;">
                    <h4 style="margin-top: 0; margin-bottom: 15px;">Aperçu en direct</h4>

                    <div id="preview-msg-bg" style="padding: 20px; border-radius: 12px; display:flex; flex-direction: column; gap: 20px; align-items:center;">
                        <!-- Cadre 1 -->
                        <div style="width: 100%; max-width: 500px;">
                            <div style="font-size: 12px; color: #555; margin-bottom: 5px; text-transform: uppercase; font-weight: bold;">Bonne réponse</div>
                            <div class="preview-msg-container" style="padding: 20px; border-radius: 12px; border: 2px solid; box-shadow: 0 4px 15px rgba(0,0,0,0.1); text-align: center;">
                                <div id="preview-bonne-reponse" class="preview-msg-content"></div>
                            </div>
                        </div>
                        <!-- Cadre 2 -->
                        <div style="width: 100%; max-width: 500px;">
                            <div style="font-size: 12px; color: #555; margin-bottom: 5px; text-transform: uppercase; font-weight: bold;">Mauvaise réponse</div>
                            <div class="preview-msg-container" style="padding: 20px; border-radius: 12px; border: 2px solid; box-shadow: 0 4px 15px rgba(0,0,0,0.1); text-align: center;">
                                <div id="preview-mauvaise-reponse" class="preview-msg-content"></div>
                            </div>
                        </div>
                        <!-- Cadre 3 -->
                        <div style="width: 100%; max-width: 500px;">
                            <div style="font-size: 12px; color: #555; margin-bottom: 5px; text-transform: uppercase; font-weight: bold;">Déjà répondu</div>
                            <div class="preview-msg-container" style="padding: 20px; border-radius: 12px; border: 2px solid; box-shadow: 0 4px 15px rgba(0,0,0,0.1); text-align: center;">
                                <div id="preview-deja-repondu" class="preview-msg-content"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>

    <!-- ======================================================================== -->
    <!-- GUIDE DES BALISES HTML (MÉMO DÉPLIANT)                                   -->
    <!-- ======================================================================== -->
    <details class="section" style="padding: 20px; background-color: #fcfcfc; border: 1px solid #ddd; border-radius: 5px; margin-bottom: 20px;">

        <summary style="font-size: 16px; font-weight: bold; color: #333; cursor: pointer; outline: none; list-style-position: inside;">
            💡 Mémo de formatage (Balises HTML) - Cliquez pour déplier
        </summary>

        <div style="margin-top: 15px;">
            <p style="margin-bottom: 15px; color: #555; font-size: 14px;">
                Comme les textes des messages acceptent le code HTML, voici un rappel des balises utiles que vous pouvez utiliser pour mettre en forme vos textes :
            </p>

            <div style="overflow-x: auto;">
                <table class="dataTable" style="text-align: left; width: 100%;">
                    <thead>
                        <tr>
                            <th scope="col" style="text-align: left; width: 120px;">Balise</th>
                            <th scope="col" style="text-align: left;">Description visuelle</th>
                            <th scope="col" style="text-align: left;">Rendu visuel typique</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td><code>&lt;h1&gt;</code> à <code>&lt;h6&gt;</code></td>
                            <td>Définissent des titres, de l’intitulé principal aux sous-titres.</td>
                            <td>Texte affiché comme titre.</td>
                        </tr>
                        <tr>
                            <td><code>&lt;p&gt;</code></td>
                            <td>Crée un paragraphe de texte autonome.</td>
                            <td>Bloc de texte.</td>
                        </tr>
                        <tr>
                            <td><code>&lt;br&gt;</code></td>
                            <td>Insère un saut de ligne.</td>
                            <td>Retour à la ligne.</td>
                        </tr>
                        <tr>
                            <td><code>&lt;hr&gt;</code></td>
                            <td>Ajoute une ligne de séparation.</td>
                            <td>Ligne horizontale.</td>
                        </tr>
                        <tr>
                            <td><code>&lt;b&gt;</code></td>
                            <td>Met du texte en gras.</td>
                            <td><b>Gras</b>.</td>
                        </tr>
                        <tr>
                            <td><code>&lt;i&gt;</code></td>
                            <td>Met du texte en italique.</td>
                            <td><i>Italique</i>.</td>
                        </tr>
                        <tr>
                            <td><code>&lt;u&gt;</code></td>
                            <td>Souligne visuellement le texte.</td>
                            <td><u>Souligné</u>.</td>
                        </tr>
                        <tr>
                            <td><code>&lt;mark&gt;</code></td>
                            <td>Met en évidence une portion de texte.</td>
                            <td>Texte <mark>surligné</mark>.</td>
                        </tr>
                        <tr>
                            <td><code>&lt;del&gt;</code></td>
                            <td>Signale un contenu supprimé ou obsolète.</td>
                            <td>Texte <del>barré</del>.</td>
                        </tr>
                        <tr>
                            <td><code>&lt;sub&gt;</code></td>
                            <td>Place le texte en indice.</td>
                            <td>Exemple : H<sub>2</sub>O</td>
                        </tr>
                        <tr>
                            <td><code>&lt;sup&gt;</code></td>
                            <td>Place le texte en exposant.</td>
                            <td>Exemple : x<sup>2</sup></td>
                        </tr>
                        <tr>
                            <td><code>&lt;blockquote&gt;</code></td>
                            <td>Encadre une citation longue en bloc.</td>
                            <td>Bloc souvent indenté.</td>
                        </tr>
                        <tr>
                            <td><code>&lt;q&gt;</code></td>
                            <td>Insère une citation courte.</td>
                            <td>Citation avec guillemets.</td>
                        </tr>
                        <tr>
                            <td><code>&lt;ul&gt;</code></td>
                            <td>Crée une liste non ordonnée.</td>
                            <td>Liste à puces.</td>
                        </tr>
                        <tr>
                            <td><code>&lt;ol&gt;</code></td>
                            <td>Crée une liste ordonnée.</td>
                            <td>Liste numérotée.</td>
                        </tr>
                        <tr>
                            <td><code>&lt;li&gt;</code></td>
                            <td>Représente un élément de liste.</td>
                            <td>Ligne avec puce/numéro.</td>
                        </tr>
                        <tr>
                            <td><code>&lt;span style="color: #80FFB7;"&gt;</code></td>
                            <td>Span ne fait rien, mais permet d'appliquer le parametre style avec une couleur en hexadécimal (#1F87A2) ou par defaut avec style="color: red/green/blue/black...".</td>
                            <td>Le parametre "style" permet <span style="color: red;">d'appliquer une couleur</span>.</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </details>
</div>