<?php
// ========================================================================
// 1. INITIALISATION ET VÉRIFICATION DE LA REQUÊTE
// ========================================================================
require_once __DIR__ . "/admin/config.php"; // Charge les clés de cryptage
date_default_timezone_set('Europe/Paris');  // Règle le fuseau horaire

// SÉCURITÉ : Empêche un accès direct à cette page par l'URL (en tapant save.php).
// La page n'accepte que les données envoyées via un formulaire (méthode POST).
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    die("Erreur : méthode invalide.");
}

// ========================================================================
// 2. RÉCUPÉRATION ET NETTOYAGE DES DONNÉES DU FORMULAIRE
// ========================================================================
// On récupère les données envoyées et on utilise trim() pour enlever 
// les espaces invisibles tapés par erreur au début ou à la fin.
$email   = trim($_POST["email"] ?? '');
$reponse = trim($_POST["reponse"] ?? '');
$prenom  = trim($_POST["prenom"] ?? '');
$nom     = trim($_POST["nom"] ?? '');
$enigme  = $_POST["enigme"] ?? '';
$token   = $_POST["token"] ?? '';

// Vérifie que tous les champs obligatoires ont bien été remplis
if (!$email || !$reponse || !$prenom || !$nom || !$enigme || !$token) {
    die("Erreur : informations manquantes.");
}

// Vérifie que l'adresse e-mail a un format valide (ex: contient un @ et un point)
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    die("Erreur : adresse e-mail invalide.");
}


// ========================================================================
// 3. VÉRIFICATION DU TOKEN ET DE L'ÉNIGME (SÉCURITÉ)
// ========================================================================
$datasFile = __DIR__ . "/admin/datas.txt";

// Lecture et déchiffrement du fichier contenant la configuration et les énigmes
$datas = json_decode(decryptData(file_get_contents($datasFile), $ENCRYPT_KEY, $ENCRYPT_IV), true) ?: [];

// On vérifie que le token envoyé par le formulaire correspond bien 
// au token officiel enregistré pour cette énigme spécifique.
// Cela empêche un petit malin de trafiquer le code HTML pour valider une autre énigme.
if (!isset($datas[$enigme]['meta']['token']) || $datas[$enigme]['meta']['token'] !== $token) {
    die("Erreur : token invalide pour cette énigme");
}


// ========================================================================
// 4. PRÉPARATION DE LA COMPARAISON DES RÉPONSES
// ========================================================================
// Récupération de la bonne réponse définie par l'admin
$reponse_correcte = $datas[$enigme]['reponse_correcte'] ?? '';

/**
 * Fonction de normalisation (déjà vue dans les autres fichiers)
 * Convertit le texte pour ignorer les majuscules et les accents lors de la comparaison.
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
// 5. LECTURE DES RÉSULTATS EXISTANTS (received.txt)
// ========================================================================
$file = __DIR__ . "/admin/received.txt";
if (file_exists($file)) {
    $json = json_decode(decryptData(file_get_contents($file), $ENCRYPT_KEY, $ENCRYPT_IV), true);
    if (!is_array($json)) $json = [];
} else {
    $json = []; // Si le fichier n'existe pas encore (1er joueur), on crée un tableau vide
}

// Si le joueur (identifié par son email) n'a jamais joué à aucune énigme, on crée son profil
if (!isset($json[$email])) {
    $json[$email] = ['prenom' => $prenom, 'nom' => $nom, 'reponses' => []];
}


// ========================================================================
// 6. LOGIQUE DE JEU ET VÉRIFICATION DE LA RÉPONSE
// ========================================================================

// 6.A : Chargement des textes de notification
// Récupération des messages globaux
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


// 6.B : Vérification si le joueur a déjà une réponse enregistrée pour CETTE énigme
if (isset($json[$email]['reponses'][$enigme])) {
    // Le joueur est bloqué, on lui affiche le message "Déjà répondu"
    $message = $msg_deja;
} else {
    // 6.C : Comparaison de sa réponse avec la/les bonne(s) réponse(s)
    $is_correct = false;

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

    // Récupération de l'option "1 seule tentative max"
    $une_seule_tentative = $datas['options']['une_seule_tentative'] ?? false;

    // 6.D : Traitement du résultat
    if ($is_correct) {
        // C'est juste ! On enregistre sa réponse et on prépare le message de victoire
        $json[$email]['reponses'][$enigme] = ["reponse" => $reponse_normalized];
        $message = $msg_bonne;
    } else {
        // C'est faux ! On prépare le message d'erreur
        $message = $msg_mauvaise;

        // VÉRIFICATION TENTATIVE UNIQUE :
        if ($une_seule_tentative) {
            // L'option est activée. Pour l'empêcher de rejouer, on inscrit une réponse factice ("///")
            // Ainsi, à sa prochaine tentative, le script s'arrêtera à l'étape 6.B (Déjà répondu).
            $json[$email]['reponses'][$enigme] = ["reponse" => "///"];
        }
        // Si l'option n'est pas activée (comportement par défaut), on n'enregistre rien dans le fichier.
        // Le joueur n'ayant pas de trace dans le JSON, il pourra soumettre à nouveau le formulaire.
    }

    // ========================================================================
    // 7. SAUVEGARDE DANS LE FICHIER (UNIQUEMENT SI NÉCESSAIRE)
    // ========================================================================
    if (!empty($json[$email]['reponses'])) {
        // Trie les énigmes du joueur par ordre alphabétique pour garder un fichier JSON propre
        $enigmes = array_keys($json[$email]['reponses']);
        if (is_array($enigmes)) {
            natsort($enigmes);
            $sorted = [];
            foreach ($enigmes as $e) {
                $sorted[$e] = $json[$email]['reponses'][$e];
            }
            $json[$email]['reponses'] = $sorted;
        }

        // Chiffrement et sauvegarde des données
        $plaintext = json_encode($json, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        $encrypted = encryptData($plaintext, $ENCRYPT_KEY, $ENCRYPT_IV);
        file_put_contents($file, $encrypted, LOCK_EX);
    }
}


// ========================================================================
// 8. RÉCUPÉRATION DU THÈME POUR LA PAGE DE RÉSULTAT
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
        <!-- Balise viewport indispensable pour l'affichage correct sur les téléphones mobiles -->
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Résultat - <?php echo htmlspecialchars($enigme, ENT_QUOTES); ?></title>
        <link rel="stylesheet" href="style.css">

        <!-- Application des couleurs personnalisées par l'administrateur pour la page de résultat -->
        <style>
            body {
                background-color: <?php echo htmlspecialchars($theme_msg['background']); ?>;
                color: <?php echo htmlspecialchars($theme_msg['text_color']); ?>;
            }
            .enigme-container {
                background-color: <?php echo htmlspecialchars($theme_msg['container_bg']); ?>;
                border: 2px solid <?php echo htmlspecialchars($theme_msg['border_color']); ?>;
                text-align: center; /* On centre le texte sur cette page de résultat */
            }
            .enigme-container h2, .enigme-container h3 {
                color: <?php echo htmlspecialchars($theme_msg['title_color']); ?>;
            }
        </style>
    </head>
    <body>
        <div class="enigme-container">
            <!-- On affiche le texte (HTML autorisé) correspondant au résultat de l'utilisateur -->
            <!-- Note : On n'utilise PAS htmlspecialchars ici car l'admin est autorisé à mettre du code HTML (ex: <h2>, <br>) dans ses messages -->
            <?php echo $message; ?>
        </div>
    </body>
</html>