<?php
// ========================================================================
// 1. INCLUSION DES CONFIGURATIONS DE BASE
// ========================================================================
// On charge config.php pour avoir accès aux clés de chiffrement et aux fonctions
require_once "admin/config.php";


// ========================================================================
// 2. VÉRIFICATION DU TOKEN DE SÉCURITÉ (Paramètre 'k' dans l'URL)
// ========================================================================
// On vérifie que l'URL contient bien un paramètre "?k=" (le token de l'énigme)
// preg_match vérifie que le token est au bon format : exactement 12 caractères 
// alphanumériques (lettres de 'a' à 'f' et chiffres de '0' à '9').
// C'est une sécurité contre les injections de code via l'URL.
if (!isset($_GET['k']) || !preg_match('/^[a-f0-9]{12}$/', $_GET['k'])) {
    die("Token invalide"); // Arrête l'affichage de la page immédiatement
}
// Si tout est bon, on stocke le token dans une variable
$token = $_GET['k'];


// ========================================================================
// 3. LECTURE ET DÉCHIFFREMENT DE LA BASE DE DONNÉES
// ========================================================================
$file_datas = "admin/datas.txt";

// Sécurité : on vérifie que le fichier existe bien sur le serveur
if (!file_exists($file_datas)) {
    die("Erreur : fichier datas.txt introuvable");
}

// 1. file_get_contents : Lit le fichier crypté
// 2. decryptData : Déchiffre le texte avec les clés AES-256
// 3. json_decode(..., true) : Transforme le texte JSON en un tableau PHP exploitable
$datas = json_decode(decryptData(file_get_contents($file_datas), $ENCRYPT_KEY, $ENCRYPT_IV), true);


// ========================================================================
// 4. RÉCUPÉRATION DU THÈME (Couleurs personnalisées)
// ========================================================================
// On cherche la clé 'theme_enigmes' dans le fichier de données. 
// Si elle n'existe pas (ex: premier lancement du jeu), on utilise des couleurs par défaut.
$theme = $datas['theme_enigmes'] ?? [
    'background' => '#f9f9f9',
    'container_bg' => '#b5ceEE',
    'border_color' => '#2b7cff',
    'title_color' => '#1a4f9b',
    'text_color' => '#333',
    'form_bg' => '#e8f0ff',
    'button_bg' => '#2b7cff',
    'button_hover' => '#1a5fd1'
];


// ========================================================================
// 5. RECHERCHE DE L'ÉNIGME CORRESPONDANT AU TOKEN
// ========================================================================
$nom_enigme = null; // Variable qui va stocker le titre de l'énigme trouvée

// On parcourt toutes les données de datas.txt
foreach($datas as $nom => $e) {
    // Si on tombe sur un réglage (ex: "theme", "options"), on l'ignore
    if (in_array($nom, $RESERVED_KEYS)) continue;

    // Si on trouve une énigme dont le token correspond exactement à celui de l'URL
    if(isset($e['meta']['token']) && $e['meta']['token'] === $token) {
        $nom_enigme = $nom;          // On sauvegarde son titre
        $texte_enigme = $e['texte']; // On sauvegarde son texte explicatif
        break; // On arrête la boucle, on a trouvé ce qu'on cherchait !
    }
}

// Si après avoir fouillé tout le fichier on n'a rien trouvé, on bloque l'accès.
// Cela arrive si l'admin supprime l'énigme mais que des QR codes circulent encore.
if(!$nom_enigme) die("Énigme introuvable pour ce token");
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <!-- Affiche le nom de l'énigme dans l'onglet du navigateur -->
    <title><?php echo htmlspecialchars($nom_enigme, ENT_QUOTES); ?></title>
    
    <!-- Feuille de style de base (la structure, les marges, etc.) -->
    <link rel="stylesheet" href="style.css">
    
    <!-- ======================================================================== -->
    <!-- GÉNÉRATION DYNAMIQUE DES COULEURS (CSS) EN FONCTION DU THÈME CHOISI      -->
    <!-- ======================================================================== -->
    <style>
        /* On utilise !important pour forcer le remplacement des couleurs du style.css */
        body {
            background: <?php echo htmlspecialchars($theme['background']); ?> !important;
            color: <?php echo htmlspecialchars($theme['text_color']); ?> !important;
        }
        .enigme-container {
            background: <?php echo htmlspecialchars($theme['container_bg']); ?> !important;
            border-color: <?php echo htmlspecialchars($theme['border_color']); ?> !important;
        }
        .enigme-container h2 {
            color: <?php echo htmlspecialchars($theme['title_color']); ?> !important;
        }
        .enigme-container p {
            color: <?php echo htmlspecialchars($theme['text_color']); ?> !important;
        }
        form {
            background: <?php echo htmlspecialchars($theme['form_bg']); ?> !important;
        }
        button[type="submit"] {
            background: <?php echo htmlspecialchars($theme['button_bg']); ?> !important;
        }
        button[type="submit"]:hover {
            background: <?php echo htmlspecialchars($theme['button_hover']); ?> !important;
        }
        /* Colorise les bordures des champs de texte */
        input[type="text"], input[type="email"] {
            border-color: <?php echo htmlspecialchars($theme['border_color']); ?> !important;
        }
    </style>
</head>
<body>
    <!-- Conteneur principal de l'énigme -->
    <div class="enigme-container">
        
        <!-- Titre et descriptif de l'énigme -->
        <h2><?php echo htmlspecialchars($nom_enigme, ENT_QUOTES); ?></h2>
        <p><?php echo htmlspecialchars($texte_enigme, ENT_QUOTES); ?></p>
        <br>

        <!-- ======================================================================== -->
        <!-- FORMULAIRE DE RÉPONSE (Envoyé vers save.php)                             -->
        <!-- ======================================================================== -->
        <form action="save.php" method="POST">
            
            <!-- Champs cachés (indispensables pour save.php) -->
            <!-- Ils permettent de dire discrètement au serveur : "Voici à quelle énigme le joueur a répondu" -->
            <input type="hidden" name="enigme" value="<?php echo htmlspecialchars($nom_enigme, ENT_QUOTES); ?>">
            <input type="hidden" name="token" value="<?php echo htmlspecialchars($token, ENT_QUOTES); ?>">

            <!-- Champ : Réponse du joueur -->
            <div class="section">
                <label>
                    Ta réponse <span style="color:red;">*</span> :<br>
                    <!-- 'required' empêche de valider si le champ est vide -->
                    <input type="text" name="reponse" placeholder="Votre réponse" required>
                </label>
            </div>

            <!-- Champ : Adresse E-mail -->
            <div class="section">
                <label>
                    Ton e-mail <span style="color:red;">*</span> :<br>
                    <!-- type="email" force la présence d'un '@' sur les mobiles -->
                    <input type="email" name="email" placeholder="ex : joueur@example.com" required>
                </label>
            </div>

            <!-- Champ : Prénom -->
            <div class="section">
                <label>
                    Ton prénom <span style="color:red;">*</span> :<br>
                    <input type="text" name="prenom" placeholder="ex : Jean" required>
                </label>
            </div>

            <!-- Champ : Nom de famille -->
            <div class="section">
                <label>
                    Ton nom <span style="color:red;">*</span> :<br>
                    <input type="text" name="nom" placeholder="ex : Dupont" required>
                </label>
            </div>

            <!-- Bouton de validation -->
            <button type="submit">Valider</button>
            
        </form>
        
        <!-- Petite mention pour les astérisques rouges -->
        <h6><span style="color:red;">*</span> Champ obligatoire</h6>
    </div>
</body>
</html>