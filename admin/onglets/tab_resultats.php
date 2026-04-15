<!-- ========================================================== -->
<!-- ONGLET 1 : RÉSULTATS DES JOUEURS                           -->
<!-- ========================================================== -->
<div class="tab-content <?php echo ($activeTab === 'resultats') ? 'active' : ''; ?>" id="resultats">

    <!-- Bouton d'exportation vers Excel (export.php) -->
    <div style="margin-bottom:10px;">
        <form method="POST" action="core/export.php"><button type="submit">Exporter les résultats</button></form>
    </div>

    <?php if(empty($received)): ?>
    <p>Aucun résultat enregistré.</p>
    <?php else: ?>

    <!-- Tableau des résultats géré par DataTables -->
    <div style="overflow-x: auto; max-width: 100%;">
        <table id="table-results" class="dataTable">
            <thead>
                <tr>
                    <th>Email</th>
                    <th>Prénom</th>
                    <th>Nom</th>
                    <?php 
                    // Génération dynamique des colonnes en fonction des énigmes existantes
                    foreach(array_keys($datas) as $e) {
                        // Ignore les clés réservées à la configuration définies dans config.php
                        if (in_array($e, $RESERVED_KEYS)) continue;
                        echo '<th>'.htmlspecialchars($e,ENT_QUOTES).'</th>';
                    }
                    ?>
                </tr>
            </thead>
            <tbody>
                <?php foreach($received as $email=>$data): ?>
                <tr>
                    <td><?php echo htmlspecialchars($email,ENT_QUOTES); ?></td>
                    <td><?php echo htmlspecialchars($data['prenom'] ?? '',ENT_QUOTES); ?></td>
                    <td><?php echo htmlspecialchars($data['nom'] ?? '',ENT_QUOTES); ?></td>

                    <?php foreach(array_keys($datas) as $e):
                    if (in_array($e, $RESERVED_KEYS)) continue;

                    $val = $data['reponses'][$e]['reponse'] ?? '';

                    // Logique de coloration des réponses (vert = juste, rouge = faux)
                    $correct = $datas[$e]['reponse_correcte'] ?? null;
                    $is_correct_display = false;

                    if ($correct !== null && $val !== '') {
                        $val_normalized = normalizeString($val);
                        if (is_array($correct)) {
                            // Vérifie si la réponse tapée correspond à l'une des réponses possibles
                            foreach ($correct as $c) {
                                if ($val_normalized === normalizeString($c)) {
                                    $is_correct_display = true;
                                    break;
                                }
                            }
                        } else {
                            // Vérification simple
                            if ($val_normalized === normalizeString($correct)) {
                                $is_correct_display = true;
                            }
                        }
                        // Définit la classe CSS correspondante
                        $class = $is_correct_display ? 'correct' : 'incorrect';
                    } else {
                        $class = '';
                    }
                    ?>
                    <td class="<?php echo $class; ?>">
                        <?php echo htmlspecialchars($val,ENT_QUOTES); ?>
                    </td>
                    <?php endforeach; ?>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>
</div>