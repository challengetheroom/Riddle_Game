<?php
// ========================================================================
// INCLUSIONS ET CONFIGURATION DE BASE
// ========================================================================
// Charge les variables globales (clés de cryptage, mots réservés, etc.)
require_once "config.php";
// Charge les fonctions d'affichage pour l'onglet "Données"
require_once __DIR__ . '/view_datas.php';
// Définit le fuseau horaire par défaut pour la date de création des fichiers (si besoin)
date_default_timezone_set('Europe/Paris');

// ========================================================================
// SYSTÈME DE DÉCONNEXION
// ========================================================================
// Comme l'accès est protégé par un .htaccess (Auth Basic), il est impossible de détruire la session côté PHP.
// L'astuce consiste à forcer une erreur 401 (Unauthorized) pour invalider la page courante et demander
// à l'utilisateur de fermer son navigateur pour vider le cache du mot de passe.
if (isset($_GET['logout'])) {
    header('HTTP/1.0 401 Unauthorized');
    echo '<!DOCTYPE html><html lang="fr"><head><meta charset="UTF-8"><title>Déconnexion</title></head><body style="background:#f4f4f4; text-align:center; padding:50px; font-family:sans-serif;">';
    echo '<div style="background:#fff; padding:30px; border-radius:8px; box-shadow:0 2px 10px rgba(0,0,0,0.1); display:inline-block; text-align:left;">';
    echo '<h2 style="color:#dc3545; margin-top:0;">🚪 Déconnexion</h2>';
    echo '<p>La demande de déconnexion a bien été prise en compte.</p>';
    echo '<p style="background: #fff3cd; color: #856404; padding: 10px; border-radius: 5px; border-left: 4px solid #ffeeba;">';
    echo '⚠️ <b>Important :</b> Votre système utilise une protection <i>.htaccess</i>.<br>Pour être <b>totalement déconnecté</b> et vider le cache du mot de passe, <br><b>vous devez impérativement fermer cet onglet ou votre navigateur</b>.</p>';
    echo '<div style="text-align:center; margin-top:20px;"><a href="index.php" style="padding:10px 20px; background:#007bff; color:#fff; text-decoration:none; border-radius:5px; display:inline-block;">Se reconnecter</a></div>';
    echo '</div></body></html>';
    exit;
}

// Nom du fichier contenant les configurations et les énigmes
$datasFile = 'datas.txt';

// ========================================================================
// FONCTIONS UTILITAIRES
// ========================================================================

/**
 * Génère un identifiant unique (token) alphanumérique aléatoire.
 * Utilisé pour créer les URL uniques de chaque énigme.
 */
function generateToken($len = 12) {
    $bytes = random_bytes(ceil($len/2));
    return substr(bin2hex($bytes), 0, $len);
}

/**
 * Normalise une chaîne de caractères pour faciliter la comparaison :
 * - Retire les espaces superflus
 * - Retire les accents (diacritiques)
 * - Met tout en MAJUSCULES
 */
function normalizeString($str) {
    if ($str === null || $str === '') {
        return '';
    }
    $str = trim($str);
    if (class_exists('Normalizer')) {
        $str = Normalizer::normalize($str, Normalizer::FORM_D);
    }
    $str = preg_replace('/[\x{0300}-\x{036f}]/u', '', $str);
    $str = mb_strtoupper($str, 'UTF-8');
    return $str;
}

// ========================================================================
// LECTURE ET DÉCHIFFREMENT DES FICHIERS DE DONNÉES
// ========================================================================

// 1. Lecture de datas.txt (Énigmes + Configurations)
$datas = [];
if (file_exists($datasFile)) {
    $enc = file_get_contents($datasFile);
    $dec = decryptData($enc, $ENCRYPT_KEY, $ENCRYPT_IV);
    $datas = json_decode($dec, true) ?: [];
}

// 2. Lecture de received.txt (Résultats des joueurs)
$received = [];
if (file_exists('received.txt')) {
    $enc = file_get_contents('received.txt');
    $dec = decryptData($enc, $ENCRYPT_KEY, $ENCRYPT_IV);
    $received = json_decode($dec, true) ?: [];
}

// ========================================================================
// GESTION DES REQUÊTES POST (ENREGISTREMENT DES FORMULAIRES)
// ========================================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Récupération de l'action demandée par le formulaire soumis
    $action = $_POST['action'] ?? '';
    // Mémorise l'onglet depuis lequel l'action a été faite pour y retourner
    $activeTab = $_POST['active_tab'] ?? 'resultats';

    // --- CRÉER UNE NOUVELLE ÉNIGME ---
    if ($action === 'add_enigme') {
        $nom = trim($_POST['nouvelle_enigme'] ?? '');
        if ($nom === '') $message = "Nom d'énigme vide.";
        elseif (isset($datas[$nom])) $message = "Erreur : cette énigme existe déjà.";
        else {
            // Initialisation avec des données vides et un nouveau token
            $datas[$nom] = [
                "meta" => ["token" => generateToken(12)],
                "texte" => "Nouveau texte",
                "reponse_correcte" => ""
            ];
            $message = "Nouvelle énigme '$nom' ajoutée avec token.";
        }
    }
    // --- METTRE À JOUR LES COULEURS DES ÉNIGMES ---
    elseif ($action === 'update_theme') {
        $theme = $_POST['theme_enigmes'] ?? [];
        if (!empty($theme)) {
            $datas['theme_enigmes'] = $theme;
            // Nettoyage de l'ancienne clé "theme" si elle existe encore
            if (isset($datas['theme'])) unset($datas['theme']); 
        }
        $message = "Thème mis à jour avec succès.";
    }
    // --- METTRE À JOUR LES OPTIONS GLOBALES DU JEU ---
    elseif ($action === 'update_options') {
        // Enregistre si la case "1 seule tentative" a été cochée (true) ou non (false)
        $datas['options']['une_seule_tentative'] = isset($_POST['une_seule_tentative']) ? true : false;
        $message = "Options globales mises à jour.";
    }
    // --- MODIFIER UNE ÉNIGME EXISTANTE ---
    elseif ($action === 'update_enigme') {
        $nom = $_POST['enigme'] ?? '';
        if ($nom === '' || !isset($datas[$nom])) $message = "Énigme introuvable pour mise à jour.";
        else {
            $texte = $_POST['texte'] ?? '';
            $raw_reponses = trim($_POST['reponse_correcte'] ?? '');

            // Traitement des réponses multiples : si un point-virgule est présent, on convertit la chaîne en tableau
            if (strpos($raw_reponses, ';') !== false) {
                $array_reponses = array_map('trim', explode(';', $raw_reponses));
                $array_reponses = array_filter($array_reponses, function($val) { return $val !== ''; }); // Retire les valeurs vides
                $datas[$nom]['reponse_correcte'] = array_values($array_reponses);
            } else {
                // Sinon on stocke la réponse en tant que chaîne simple
                $datas[$nom]['reponse_correcte'] = $raw_reponses;
            }

            $datas[$nom]['texte'] = $texte;
            $message = "Énigme '$nom' mise à jour.";
        }
    }
    // --- SUPPRIMER UNE ÉNIGME ---
    elseif ($action === 'delete_enigme') {
        $nom = $_POST['enigme'] ?? '';
        if ($nom === '' || !isset($datas[$nom])) $message = "Énigme introuvable pour suppression.";
        else {
            unset($datas[$nom]); // Supprime la clé du tableau JSON
            $message = "Énigme '$nom' supprimée.";
        }
    }
    // --- VIDER INTÉGRALEMENT UN FICHIER TXT ---
    elseif ($action === 'reset_file') {
        $fileType = $_POST['file_type'] ?? ''; // 'datas' ou 'received'
        $pwd = $_POST['password'] ?? '';

        // Tente de récupérer le mot de passe du .htaccess via les variables du serveur PHP
        $adminPassword = $_SERVER['PHP_AUTH_PW'] ?? '';

        // Si vide (fréquent sur serveur de production FastCGI), on va fouiller dans les en-têtes HTTP bruts
        if ($adminPassword === '') {
            $authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? $_SERVER['REDIRECT_HTTP_AUTHORIZATION'] ?? '';
            if (!$authHeader && function_exists('apache_request_headers')) {
                $headers = apache_request_headers();
                $authHeader = $headers['Authorization'] ?? $headers['authorization'] ?? '';
            }
            // Décryptage de l'en-tête "Basic Base64"
            if ($authHeader && preg_match('/Basic\s+(.*)$/i', $authHeader, $matches)) {
                $decoded = base64_decode($matches[1]);
                if (strpos($decoded, ':') !== false) {
                    list($authUser, $authPw) = explode(':', $decoded, 2);
                    $adminPassword = $authPw;
                }
            }
        }

        // Vérifie si le mot de passe tapé par l'utilisateur correspond à celui trouvé dans le serveur
        if ($adminPassword === '' || $pwd !== $adminPassword) {
            $message = "❌ Mot de passe incorrect. Le fichier n'a pas été vidé.";
        } else {
            // Création d'un JSON vide et cryptage
            $emptyData = json_encode([], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
            $enc = encryptData($emptyData, $ENCRYPT_KEY, $ENCRYPT_IV);

            // Écrasement du fichier demandé
            if ($fileType === 'datas') {
                file_put_contents($datasFile, $enc, LOCK_EX);
                $message = "✅ Le fichier datas.txt a été entièrement vidé !";
            } elseif ($fileType === 'received') {
                file_put_contents('received.txt', $enc, LOCK_EX);
                $message = "✅ Le fichier received.txt a été entièrement vidé !";
            } else {
                $message = "Erreur : Type de fichier invalide.";
            }
        }

        // Redirection immédiate pour éviter le renvoi du formulaire au rafraîchissement
        header("Location: index.php?tab=$activeTab&msg=" . urlencode($message));
        exit;
    }
    // --- METTRE À JOUR LES MESSAGES DE RÉSULTATS ---
    elseif ($action === 'update_messages') {
        // Enregistre les textes (HTML autorisé)
        $datas['messages'] = [
            'bonne_reponse' => $_POST['msg_bonne_reponse'] ?? '',
            'mauvaise_reponse' => $_POST['msg_mauvaise_reponse'] ?? '',
            'deja_repondu' => $_POST['msg_deja_repondu'] ?? ''
        ];
        // Enregistre les couleurs de la page de résultats
        $datas['theme_messages'] = $_POST['theme_messages'] ?? [];
        $message = "Messages et couleurs mis à jour.";
    }

    // ========================================================================
    // SAUVEGARDE FINALE DE DATAS.TXT
    // ========================================================================
    // Si l'action exécutée fait partie de cette liste blanche, on chiffre et on sauvegarde $datas.
    if (in_array($action, ['add_enigme','update_enigme','delete_enigme','update_theme','update_options','update_messages', 'reset_file'])) {
        // Trie le tableau par ordre alphabétique naturel (Enigme 1, Enigme 2, Enigme 10...)
        ksort($datas, SORT_NATURAL | SORT_FLAG_CASE);
        // Conversion en JSON lisible
        $plaintext = json_encode($datas, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        // Cryptage
        $enc = encryptData($plaintext, $ENCRYPT_KEY, $ENCRYPT_IV);
        // Écriture sécurisée (LOCK_EX empêche qu'un autre processus lise le fichier pendant l'écriture)
        file_put_contents($datasFile, $enc, LOCK_EX);
        
        // Redirection (empêche le comportement F5 = renvoi du formulaire POST)
        header("Location: index.php?tab=$activeTab&msg=" . urlencode($message));
        exit;
    }
}

// Récupération des variables passées dans l'URL (si on vient d'être redirigé)
$message = $_GET['msg'] ?? '';
$activeTab = $_GET['tab'] ?? 'resultats';
?>

<!DOCTYPE html>
<html lang="fr">
    <head>
        <meta charset="UTF-8">
        <title>Admin - Gestion des énigmes</title>
        <!-- Feuille de style globale du projet -->
        <link rel="stylesheet" href="../style.css">
        <!-- CSS de DataTables (pour le tableau des résultats triable/cherchable) -->
        <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">
        <!-- Scripts jQuery et DataTables -->
        <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
        <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    </head>
    <body>
        <div class="container">
            
            <!-- En-tête avec bouton de déconnexion -->
            <div style="display: flex; justify-content: space-between; align-items: center;">
                <h1>Admin - Gestion des énigmes</h1>
                <a href="?logout=1" style="background: #dc3545; color: white; text-decoration: none; padding: 8px 16px; border-radius: 4px; font-weight: bold; font-size: 14px; transition: background 0.2s;" onmouseover="this.style.background='#c82333'" onmouseout="this.style.background='#dc3545'">
                    🚪 Déconnexion
                </a>
            </div>

            <!-- Affichage des messages de notification (succès/erreur) -->
            <?php if($message): ?>
            <div class="message"><?php echo htmlspecialchars($message,ENT_QUOTES); ?></div>
            <?php endif; ?>

            <!-- Menu de navigation par onglets -->
            <div class="tabs">
                <div class="tab <?php echo ($activeTab === 'resultats') ? 'active' : ''; ?>" data-tab="resultats">Résultats</div>
                <div class="tab <?php echo ($activeTab === 'edition') ? 'active' : ''; ?>" data-tab="edition">Édition des énigmes</div>
                <div class="tab <?php echo ($activeTab === 'messages') ? 'active' : ''; ?>" data-tab="messages">Édition des messages</div>
                <div class="tab <?php echo ($activeTab === 'datas') ? 'active' : ''; ?>" data-tab="datas">Données</div>
            </div>

            <!-- ========================================================== -->
            <!-- ONGLET 1 : RÉSULTATS DES JOUEURS                           -->
            <!-- ========================================================== -->
            <div class="tab-content <?php echo ($activeTab === 'resultats') ? 'active' : ''; ?>" id="resultats">
                
                <!-- Bouton d'exportation vers Excel (export.php) -->
                <div style="margin-bottom:10px;">
                    <form method="POST" action="export.php"><button type="submit">Exporter les résultats</button></form>
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

            <!-- ========================================================== -->
            <!-- ONGLET 2 : ÉDITION DES ÉNIGMES                             -->
            <!-- ========================================================== -->
            <div class="tab-content <?php echo ($activeTab === 'edition') ? 'active' : ''; ?>" id="edition">

                <!-- 2.A : GESTION DES COULEURS DES ÉNIGMES (Joueur) -->
                <div class="section" style="padding: 20px;">
                    <!-- En-tête dépliable -->
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
                        <h3 style="margin: 0;">🎨 Personnalisation des couleurs</h3>
                        <button type="button" id="toggle-theme" style="background: #007BFF; border: none; font-size: 18px; cursor: pointer; padding: 8px 12px; transition: transform 0.3s; color: white; border-radius: 6px;" title="Plier/Déplier">
                            ▼
                        </button>
                    </div>

                    <div id="theme-panel">
                        <form method="POST" id="theme-form">
                            <!-- Champs cachés pour diriger la requête vers la bonne action PHP -->
                            <input type="hidden" name="action" value="update_theme">
                            <input type="hidden" name="active_tab" value="edition">

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
                                    <br>
                                    <button type="submit" style="margin-top: 20px; width: 100%;">💾 Enregistrer les couleurs</button>
                                </div>

                                <!-- Panneau droit : Aperçu visuel géré par JavaScript -->
                                <div style="flex: 1; background: #d9d9d9; padding: 20px; border-radius: 8px;">
                                    <h4 style="margin-top: 0;">Aperçu en direct</h4>

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
                
                <!-- 2.B : OPTIONS GLOBALES DU JEU (ex: 1 seule tentative) -->
                <form method="POST" class="section" style="padding: 20px; background: #fff3cd; border-left: 4px solid #ffc107;">
                    <input type="hidden" name="action" value="update_options">
                    <input type="hidden" name="active_tab" value="edition">
                    
                    <h3 style="margin-top: 0; color: #856404;">⚙️ Options globales du jeu</h3>
                    
                    <?php 
                    $is_single_attempt = $datas['options']['une_seule_tentative'] ?? false; 
                    ?>
                    
                    <label style="display: flex; align-items: center; gap: 10px; cursor: pointer; font-size: 15px; font-weight: bold; color: #dc3545;">
                        <input type="checkbox" name="une_seule_tentative" value="1" <?php echo $is_single_attempt ? 'checked' : ''; ?> style="width: 18px; height: 18px; cursor: pointer;">
                        Bloquer les joueurs après 1 seule tentative (même fausse)
                    </label>
                    <div style="font-size: 13px; color: #666; margin-top: 5px; margin-left: 28px; margin-bottom: 15px;">
                        Si décoché, les joueurs peuvent retenter l'énigme autant de fois qu'ils le souhaitent jusqu'à trouver la bonne réponse.
                    </div>
                    
                    <button type="submit" style="background: #ffc107; color: #333; font-weight: bold;">💾 Enregistrer l'option</button>
                </form>

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

                        <div style="margin-top:10px;">
                            <button type="submit" name="action" value="update_enigme">Mettre à jour</button>
                            <!-- Bouton suppression avec sécurité JavaScript -->
                            <button type="submit" name="action" value="delete_enigme" onclick="return confirm('Voulez-vous vraiment supprimer cette énigme ?');">Supprimer</button>
                        </div>
                    </form>
                    <?php endforeach; ?>
                </div>
            </div>

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
            </div>

            <!-- ========================================================== -->
            <!-- ONGLET 4 : AFFICHAGE DES DONNÉES BRUTES (DEBUG/SÉCURITÉ)   -->
            <!-- ========================================================== -->
            <div class="tab-content <?php echo ($activeTab === 'datas') ? 'active' : ''; ?>" id="datas">
                <div class="donnees-box">
                    
                    <!-- Fichier de configuration -->
                    <div class="donnees-left">
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px;">
                            <h2 style="margin: 0;">datas.txt</h2>
                            <!-- Bouton pour vider intégralement le fichier avec sécurité (JS) -->
                            <button type="button" onclick="confirmReset('datas')" style="background: #dc3545; color: white; border: none; padding: 6px 12px; border-radius: 4px; cursor: pointer; font-weight: bold;">
                                ⚠️ Vider datas
                            </button>
                        </div>
                        <div class="donnees-pre">
                            <?php echo nl2br(safePrint(getDatasContent())); ?>
                        </div>
                    </div>

                    <!-- Fichier des joueurs -->
                    <div class="donnees-right">
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px;">
                            <h2 style="margin: 0;">received.txt</h2>
                            <button type="button" onclick="confirmReset('received')" style="background: #dc3545; color: white; border: none; padding: 6px 12px; border-radius: 4px; cursor: pointer; font-weight: bold;">
                                ⚠️ Vider received
                            </button>
                        </div>
                        <div class="donnees-pre">
                            <?php echo nl2br(safePrint(getReceivedContent())); ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- ========================================================== -->
        <!-- SCRIPTS JAVASCRIPT GÉRANT L'INTERFACE                      -->
        <!-- ========================================================== -->
        <script>
            $(document).ready(function(){
                
                // 1. GESTION DES ONGLETS
                // Alterne l'affichage des onglets en modifiant les classes 'active'
                $('.tab').click(function(){
                    $('.tab').removeClass('active');
                    $(this).addClass('active');
                    $('.tab-content').removeClass('active');
                    const tabName = $(this).data('tab');
                    $('#' + tabName).addClass('active');
                    
                    // Modifie l'URL sans recharger la page pour mémoriser l'onglet actif (utile pour la touche F5)
                    const url = new URL(window.location);
                    url.searchParams.set('tab', tabName);
                    url.searchParams.delete('msg'); // Enlève le message de succès de l'URL
                    window.history.replaceState({}, '', url);
                });

                // 2. INITIALISATION DATATABLES (Onglet 1)
                $('#table-results').DataTable({ pageLength:10, lengthMenu:[5,10,20,50], order:[] });

                // 3. NETTOYAGE URL
                // Enlève visuellement le paramètre '?msg=xxx' de la barre d'adresse pour éviter sa réapparition au rafraîchissement
                <?php if($message): ?>
                setTimeout(function() {
                    const url = new URL(window.location);
                    url.searchParams.delete('msg');
                    window.history.replaceState({}, '', url);
                }, 100);
                <?php endif; ?>

                // 4. SAUVEGARDE ÉTAT DU PANNEAU COULEURS
                // Vérifie dans la mémoire du navigateur (localStorage) si l'admin avait replié le panneau
                const isCollapsed = localStorage.getItem('theme-panel-collapsed') === 'true';
                if (isCollapsed) {
                    $('#theme-panel').hide();
                    $('#toggle-theme').text('▲');
                } else {
                    $('#theme-panel').show();
                    $('#toggle-theme').text('▼');
                }

                // Initialisation visuelle
                updatePreview();

                // 5. SYNCHRONISATION COLOR-PICKER <-> CHAMP TEXTE HEXA (Onglet 2)
                
                // Quand on clique sur la couleur, ça met à jour le texte
                $('input[type="color"]').on('input', function() {
                    $(this).next('.color-hex').val($(this).val().toUpperCase());
                    updatePreview();
                });

                // Quand on tape du texte (ex: #FFFFFF), ça met à jour le sélecteur de couleur
                $('.color-hex').on('input', function() {
                    const hexValue = $(this).val().toUpperCase();
                    $(this).val(hexValue); // Force majuscule

                    // Vérifie via Regex si c'est un format Hexadécimal valide (# + 6 caractères)
                    if (/^#[0-9A-F]{6}$/i.test(hexValue)) {
                        $(this).prev('input[type="color"]').val(hexValue);
                        updatePreview();
                    }
                });

                // Force l'affichage en majuscule au chargement
                $('input[type="color"]').each(function() {
                    $(this).next('.color-hex').val($(this).val().toUpperCase());
                });

                // 6. GESTION DU HOVER SUR LE BOUTON D'APERÇU (Onglet 2)
                $('#preview-button').hover(
                    function() {
                        const hoverColor = $('input[name="theme_enigmes[button_hover]"]').val();
                        $(this).css('background', hoverColor);
                    },
                    function() {
                        const normalColor = $('input[name="theme_enigmes[button_bg]"]').val();
                        $(this).css('background', normalColor);
                    }
                );
            });

            // 7. ANIMATION DU PLIAGE/DÉPLIAGE DU PANNEAU COULEURS
            $('#toggle-theme').click(function() {
                const isVisible = $('#theme-panel').is(':visible');

                if (isVisible) {
                    $('#theme-panel').slideUp(300); // Animation vers le haut
                    $(this).text('▲');
                    localStorage.setItem('theme-panel-collapsed', 'true');
                } else {
                    $('#theme-panel').slideDown(300); // Animation vers le bas
                    $(this).text('▼');
                    localStorage.setItem('theme-panel-collapsed', 'false');
                }
            });

            // 8. FONCTION DE MISE À JOUR DE L'APERÇU DES ÉNIGMES (Onglet 2)
            function updatePreview() {
                // Récupère toutes les valeurs actuelles des sélecteurs
                const background = $('input[name="theme_enigmes[background]"]').val();
                const containerBg = $('input[name="theme_enigmes[container_bg]"]').val();
                const borderColor = $('input[name="theme_enigmes[border_color]"]').val();
                const titleColor = $('input[name="theme_enigmes[title_color]"]').val();
                const textColor = $('input[name="theme_enigmes[text_color]"]').val();
                const formBg = $('input[name="theme_enigmes[form_bg]"]').val();
                const buttonBg = $('input[name="theme_enigmes[button_bg]"]').val();

                // Applique les styles CSS en direct
                $('#preview').css('background', background);
                $('#preview-container').css({
                    'background': containerBg,
                    'border-color': borderColor,
                    'color': textColor
                });
                $('#preview-title').css('color', titleColor);
                $('#preview-text').css('color', textColor);
                $('#preview-form-box').css('background', formBg);
                $('#preview-input').css('border-color', borderColor);
                $('#preview-button').css('background', buttonBg);
            }

            // 9. SÉCURITÉ DE SUPPRESSION (Onglet 4)
            // Demande une double vérification (Dialogue + Mot de passe) avant de formater un fichier JSON
            function confirmReset(fileType) {
                // 1ère étape : Boîte de dialogue JS classique
                if (confirm("⚠️ ATTENTION : Voulez-vous vraiment vider totalement le fichier " + fileType + ".txt ?\n\nToutes les données seront perdues. Cette action est IRRÉVERSIBLE.")) {

                    // 2ème étape : Fenêtre de saisie (Prompt)
                    let pwd = prompt("Veuillez entrer le mot de passe administrateur pour confirmer la suppression de " + fileType + ".txt :");

                    if (pwd !== null && pwd.trim() !== "") {
                        // Génère un formulaire caché en HTML pour poster les données vers PHP
                        let form = $('<form>', {
                            method: 'POST',
                            action: 'index.php'
                        });
                        form.append($('<input>', { type: 'hidden', name: 'action', value: 'reset_file' }));
                        form.append($('<input>', { type: 'hidden', name: 'file_type', value: fileType }));
                        form.append($('<input>', { type: 'hidden', name: 'password', value: pwd }));
                        form.append($('<input>', { type: 'hidden', name: 'active_tab', value: 'datas' }));

                        // Ajoute le formulaire au corps de la page et l'envoie automatiquement
                        $('body').append(form);
                        form.submit();
                    } else if (pwd !== null) {
                        alert("Mot de passe vide, action annulée.");
                    }
                }
            }

            // 10. FONCTION DE MISE À JOUR DE L'APERÇU DES MESSAGES (Onglet 3)
            function updateMsgPreview() {
                const bg = $('input[name="theme_messages[background]"]').val();
                const container = $('input[name="theme_messages[container_bg]"]').val();
                const border = $('input[name="theme_messages[border_color]"]').val();
                const title = $('input[name="theme_messages[title_color]"]').val();
                const text = $('input[name="theme_messages[text_color]"]').val();

                // Applique l'arrière-plan global
                $('#preview-msg-bg').css('background', bg);
                
                // Applique le style aux 3 blocs de messages
                $('.preview-msg-container').css({
                    'background': container,
                    'border-color': border,
                    'color': text
                });

                // Copie le texte (HTML) tapé dans les <textarea> vers les blocs d'aperçu
                $('#preview-bonne-reponse').html($('textarea[name="msg_bonne_reponse"]').val());
                $('#preview-mauvaise-reponse').html($('textarea[name="msg_mauvaise_reponse"]').val());
                $('#preview-deja-repondu').html($('textarea[name="msg_deja_repondu"]').val());

                // Colorise le texte standard
                $('.preview-msg-content').css('color', text);
                // Cible spécifiquement les balises de titre pour leur donner la bonne couleur
                $('.preview-msg-content').find('h2, h3').css('color', title);
            }

            // 11. ÉCOUTEURS D'ÉVÉNEMENTS POUR L'ONGLET 3 (MESSAGES)
            // Relie les champs de couleur, les champs hexa et les zones de texte à la fonction d'aperçu
            $('.msg-color').on('input', function() {
                $(this).next('.msg-hex').val($(this).val().toUpperCase());
                updateMsgPreview();
            });
            $('.msg-hex').on('input', function() {
                const hexValue = $(this).val().toUpperCase();
                $(this).val(hexValue);
                if (/^#[0-9A-F]{6}$/i.test(hexValue)) {
                    $(this).prev('input[type="color"]').val(hexValue);
                    updateMsgPreview();
                }
            });

            $('.msg-input').on('input', function() {
                updateMsgPreview();
            });

            // Lancement initial de l'aperçu
            updateMsgPreview();
        </script>
    </body>
</html>