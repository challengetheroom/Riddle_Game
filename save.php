<?php
// ========================================================================
// 1. INITIALISATION ET VÉRIFICATION DE LA REQUÊTE
// ========================================================================
require_once __DIR__ . "/admin/core/config.php"; // Charge les clés de cryptage
date_default_timezone_set('Europe/Paris');  // Règle le fuseau horaire

// SÉCURITÉ : Empêche un accès direct à cette page par l'URL (en tapant save.php).
// La page n'accepte que les données envoyées via un formulaire (méthode POST).
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    die("Erreur : méthode invalide.");
}


// ========================================================================
// 2. LECTURE DES DONNÉES ET VÉRIFICATION DU TOKEN
// ========================================================================
$datasFile = __DIR__ . "/admin/data/datas.txt";
$datas = json_decode(decryptData(file_get_contents($datasFile), $ENCRYPT_KEY, $ENCRYPT_IV), true) ?: [];

$enigme  = $_POST["enigme"] ?? '';
$token   = $_POST["token"] ?? '';

// Sécurité : On vérifie que le token est le bon
if (!isset($datas[$enigme]['meta']['token']) || $datas[$enigme]['meta']['token'] !== $token) {
    die("Erreur : token invalide pour cette énigme");
}

// On récupère l'état des champs exigés (tout est vrai par défaut)
$fields = $datas['options']['fields'] ?? ['email'=>true, 'nom'=>true, 'prenom'=>true, 'reponse'=>true];


// ========================================================================
// 3. RÉCUPÉRATION DU FORMULAIRE ET VÉRIFICATION
// ========================================================================
// On récupère les données envoyées et on utilise trim() pour enlever 
// les espaces invisibles tapés par erreur au début ou à la fin.
$email   = trim($_POST["email"] ?? '');
$reponse = trim($_POST["reponse"] ?? '');
$prenom  = trim($_POST["prenom"] ?? '');
$nom     = trim($_POST["nom"] ?? '');

// Vérification stricte uniquement pour les champs activés par l'admin
if ($fields['email'] && !$email) die("Erreur : adresse e-mail manquante.");
if ($fields['email'] && !filter_var($email, FILTER_VALIDATE_EMAIL)) die("Erreur : adresse e-mail invalide.");
if ($fields['reponse'] && !$reponse) die("Erreur : réponse manquante.");
if ($fields['prenom'] && !$prenom) die("Erreur : prénom manquant.");
if ($fields['nom'] && !$nom) die("Erreur : nom manquant.");


// ========================================================================
// 4. IDENTIFICATION DU JOUEUR (Le "Player Key")
// ========================================================================
// Si l'e-mail est activé, on s'en sert comme identifiant principal fiable.
// Sinon, on génère un identifiant anonyme stocké dans un cookie (valable 1 an).
if ($fields['email']) {
    $player_key = $email;
} else {
    if (isset($_COOKIE['riddle_player_id'])) {
        $player_key = $_COOKIE['riddle_player_id'];
    } else {
        $player_key = 'Joueur_' . substr(md5(uniqid()), 0, 8);
        setcookie('riddle_player_id', $player_key, time() + 31536000, '/');
    }
}


// ========================================================================
// 5. LECTURE DES RÉSULTATS EXISTANTS ET INITIALISATION DU PROFIL
// ========================================================================
$file = __DIR__ . "/admin/data/received.txt";
if (file_exists($file)) {
    $json = json_decode(decryptData(file_get_contents($file), $ENCRYPT_KEY, $ENCRYPT_IV), true);
    if (!is_array($json)) $json = [];
} else {
    $json = []; // Si le fichier n'existe pas encore
}

// Si le joueur n'a jamais joué, on crée son profil avec les infos fournies
if (!isset($json[$player_key])) {
    $json[$player_key] = [
        'prenom' => $prenom ?: 'Anonyme', 
        'nom' => $nom ?: '', 
        'reponses' => []
    ];
}


// ========================================================================
// 6. PRÉPARATION DE LA COMPARAISON DES RÉPONSES
// ========================================================================
// Récupération de la bonne réponse définie par l'admin
$reponse_correcte = $datas[$enigme]['reponse_correcte'] ?? '';

/**
 * Fonction de normalisation (Convertit le texte pour ignorer majuscules/accents)
 */
function normalizeString($str) {
    $str = trim($str);
    if (class_exists('Normalizer')) {
        $str = Normalizer::normalize($str, Normalizer::FORM_D);
    }
    $str = preg_replace('/[\x{0300}-\x{036f}]/u', '', $str);
    $str = mb_strtoupper($str, 'UTF-8');
    return $str;
}

// On normalise la réponse tapée par le joueur
$reponse_normalized = normalizeString($reponse);


// ========================================================================
// 7. LOGIQUE DE JEU ET VÉRIFICATION
// ========================================================================

// 7.A : Chargement des textes de notification personnalisés (ou textes par défaut)
$msgs = $datas['messages'] ?? [];
$global_deja = $msgs['deja_repondu'] ?? "<h2>Tu as déjà répondu à cette énigme.</h2><br><p>Rendez-vous chez les autres partenaires !</p>";
$global_bonne = $msgs['bonne_reponse'] ?? "<h2>Félicitations !</h2><br><p>C'est la bonne réponse !</p>";
$global_mauvaise = $msgs['mauvaise_reponse'] ?? "<h2>Oh noooon !</h2><br><p>Désolé, ce n'est pas la bonne réponse.</p>";

// Récupération des messages UNIQUES de l'énigme s'ils existent (sinon on prend le global)
$msg_deja = !empty($datas[$enigme]['messages_uniques']['deja_repondu']) 
    ? $datas[$enigme]['messages_uniques']['deja_repondu'] 
    : $global_deja;

$msg_bonne = !empty($datas[$enigme]['messages_uniques']['bonne_reponse']) 
    ? $datas[$enigme]['messages_uniques']['bonne_reponse'] 
    : $global_bonne;

$msg_mauvaise = !empty($datas[$enigme]['messages_uniques']['mauvaise_reponse']) 
    ? $datas[$enigme]['messages_uniques']['mauvaise_reponse'] 
    : $global_mauvaise;


// 7.B : Vérification si le joueur a déjà une réponse enregistrée pour CETTE énigme
if (isset($json[$player_key]['reponses'][$enigme])) {
    // Le joueur est bloqué, on lui affiche le message "Déjà répondu"
    $message = $msg_deja;
} else {
    // 7.C : Comparaison de la réponse
    $is_correct = false;
    
    // Si le champ réponse est désactivé, on considère que c'est automatiquement gagné (mode Check-in)
    if (!$fields['reponse']) {
        $is_correct = true;
        $reponse_normalized = "Check-in validé";
    } else {
        // Cas multi-réponses (tableau séparé par des point-virgules côté admin)
        if (is_array($reponse_correcte)) {
            foreach ($reponse_correcte as $rep) {
                if ($reponse_normalized === normalizeString($rep)) {
                    $is_correct = true;
                    break;
                }
            }
        } 
        // Cas réponse unique (chaîne de texte simple)
        else {
            if ($reponse_normalized === normalizeString($reponse_correcte)) {
                $is_correct = true;
            }
        }
    }

    // Récupération de l'option "1 seule tentative max"
    $une_seule_tentative = $datas['options']['une_seule_tentative'] ?? false;

    // 7.D : Traitement du résultat
    if ($is_correct) {
        // C'est juste ! On enregistre sa réponse et on prépare le message de victoire
        $json[$player_key]['reponses'][$enigme] = ["reponse" => $reponse_normalized];
        $message = $msg_bonne;
    } else {
        // C'est faux ! On prépare le message d'erreur
        $message = $msg_mauvaise;

        // VÉRIFICATION TENTATIVE UNIQUE :
        if ($une_seule_tentative) {
            // L'option est activée. Pour l'empêcher de rejouer, on inscrit une réponse factice ("///")
            // Ainsi, à sa prochaine tentative, le script s'arrêtera à l'étape 7.B (Déjà répondu).
            $json[$player_key]['reponses'][$enigme] = ["reponse" => "///"];
        }
    }

    // ========================================================================
    // 8. SAUVEGARDE DANS LE FICHIER (UNIQUEMENT SI NÉCESSAIRE)
    // ========================================================================
    if (!empty($json[$player_key]['reponses'])) {
        // Trie les énigmes du joueur par ordre alphabétique pour garder un fichier JSON propre
        $enigmes = array_keys($json[$player_key]['reponses']);
        if (is_array($enigmes)) {
            natsort($enigmes);
            $sorted = [];
            foreach ($enigmes as $e) {
                $sorted[$e] = $json[$player_key]['reponses'][$e];
            }
            $json[$player_key]['reponses'] = $sorted;
        }

        // Chiffrement et sauvegarde des données
        $plaintext = json_encode($json, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        $encrypted = encryptData($plaintext, $ENCRYPT_KEY, $ENCRYPT_IV);
        file_put_contents($file, $encrypted, LOCK_EX);
    }
}


// ========================================================================
// 9. RÉCUPÉRATION DU THÈME POUR LA PAGE DE RÉSULTAT
// ========================================================================
$theme_msg = $datas['theme_messages'] ?? [
    'background' => '#f9f9f9',
    'container_bg' => '#b5ceEE',
    'border_color' => '#2b7cff',
    'title_color' => '#1a4f9b',
    'text_color' => '#333'
];
?>
<!DOCTYPE html>
<html lang="fr">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Résultat - <?php echo htmlspecialchars($enigme, ENT_QUOTES); ?></title>
        <link rel="stylesheet" href="style.css">
        
        <!-- Application des couleurs personnalisées -->
        <style>
            body {
                background-color: <?php echo htmlspecialchars($theme_msg['background']); ?>;
                color: <?php echo htmlspecialchars($theme_msg['text_color']); ?>;
            }
            .enigme-container {
                background-color: <?php echo htmlspecialchars($theme_msg['container_bg']); ?>;
                border: 2px solid <?php echo htmlspecialchars($theme_msg['border_color']); ?>;
                text-align: center;
            }
            .enigme-container h2, .enigme-container h3 {
                color: <?php echo htmlspecialchars($theme_msg['title_color']); ?>;
            }
        </style>
    </head>
    <body>
        <div class="enigme-container">
            <?php echo $message; ?>
        </div>
    </body>
</html>