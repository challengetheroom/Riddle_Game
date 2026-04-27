<?php
// ========================================================================
// 1. INCLUSION DES CONFIGURATIONS DE BASE
// ========================================================================
// On charge config.php pour avoir accès aux clés de chiffrement et aux fonctions
require_once "admin/core/config.php";


// ========================================================================
// 2. VÉRIFICATION DU TOKEN DE SÉCURITÉ (Paramètre 'k' dans l'URL)
// ========================================================================
if (!isset($_GET['k']) || !preg_match('/^[a-f0-9]{12}$/', $_GET['k'])) {
    die("Token invalide"); // Arrête l'affichage de la page immédiatement
}
$token = $_GET['k'];


// ========================================================================
// 3. LECTURE ET DÉCHIFFREMENT DE LA BASE DE DONNÉES
// ========================================================================
$file_datas = "admin/data/datas.txt";

if (!file_exists($file_datas)) {
    die("Erreur : fichier datas.txt introuvable");
}

$datas = json_decode(decryptData(file_get_contents($file_datas), $ENCRYPT_KEY, $ENCRYPT_IV), true);


// ========================================================================
// 4. RÉCUPÉRATION DU THÈME (Couleurs personnalisées)
// ========================================================================
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
$nom_enigme = null;

foreach($datas as $nom => $e) {
    if (in_array($nom, $RESERVED_KEYS)) continue;

    if(isset($e['meta']['token']) && $e['meta']['token'] === $token) {
        $nom_enigme = $nom;          
        $texte_enigme = $e['texte']; 
        break; 
    }
}

if(!$nom_enigme) die("Énigme introuvable pour ce token");

$fields = $datas['options']['fields'] ?? ['email'=>true, 'nom'=>true, 'prenom'=>true, 'reponse'=>true];
?>

<!DOCTYPE html>
<html lang="fr">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title><?php echo htmlspecialchars($nom_enigme, ENT_QUOTES); ?></title>

        <link rel="stylesheet" href="style.css">

        <!-- ======================================================================== -->
        <!-- GÉNÉRATION DYNAMIQUE DES COULEURS ET DE L'IMAGE DE FOND                  -->
        <!-- ======================================================================== -->
        <?php
        // Récupération des nouveaux paramètres d'image de fond
        $containerBgHex = $theme['container_bg'] ?? '#b5ceEE';
        $bgOpacity = $theme['bg_opacity'] ?? '100';
        $bgImageData = $theme['bg_image'] ?? '';
        $bgScale = $theme['bg_scale'] ?? '100';
        $bgPosX = $theme['bg_pos_x'] ?? '50';
        $bgPosY = $theme['bg_pos_y'] ?? '50';

        // Conversion de la couleur Hex en RGB pour appliquer l'opacité
        $hex = ltrim($containerBgHex, '#');
        if (strlen($hex) == 6) {
            list($r, $g, $b) = sscanf($hex, "%02x%02x%02x");
        } else {
            $r = 255; $g = 255; $b = 255; // Fallback
        }
        $cssOpacity = floatval($bgOpacity) / 100;
        $rgbaColor = "rgba($r, $g, $b, $cssOpacity)";
        ?>
        <style>
            body {
                background: <?php echo htmlspecialchars($theme['background']); ?> !important;
                color: <?php echo htmlspecialchars($theme['text_color']); ?> !important;
            }

            .enigme-container {
                /* --- GESTION DU MIXAGE ET DE L'IMAGE --- */
                <?php if (!empty($bgImageData)): ?>
                background-image: linear-gradient(<?php echo $rgbaColor; ?>, <?php echo $rgbaColor; ?>), url('<?php echo $bgImageData; ?>') !important;
                background-size: <?php echo htmlspecialchars($bgScale); ?>% !important;
                background-position: <?php echo htmlspecialchars($bgPosX); ?>% <?php echo htmlspecialchars($bgPosY); ?>% !important;
                background-repeat: no-repeat !important;
                background-color: transparent !important;
                <?php else: ?>
                background-color: <?php echo $rgbaColor; ?> !important;
                background-image: none !important;
                <?php endif; ?>
                /* --------------------------------------- */

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
            input[type="text"], input[type="email"] {
                border-color: <?php echo htmlspecialchars($theme['border_color']); ?> !important;
            }
        </style>
    </head>
    <body>
        <div class="enigme-container">

            <h2><?php echo htmlspecialchars($nom_enigme, ENT_QUOTES); ?></h2>
            <p><?php echo htmlspecialchars($texte_enigme, ENT_QUOTES); ?></p>
            <br>

            <form action="save.php" method="POST">
                <input type="hidden" name="enigme" value="<?php echo htmlspecialchars($nom_enigme, ENT_QUOTES); ?>">
                <input type="hidden" name="token" value="<?php echo htmlspecialchars($token, ENT_QUOTES); ?>">

                <?php if ($fields['reponse']): ?>
                <div class="section">
                    <label>Votre réponse <span style="color:red;">*</span> :<br><input type="text" name="reponse" placeholder="..." required></label>
                </div>
                <?php endif; ?>

                <?php if ($fields['email']): ?>
                <div class="section">
                    <label>E-mail <span style="color:red;">*</span> :<br><input type="email" name="email" placeholder="ex : joueur@example.com" required></label>
                </div>
                <?php endif; ?>

                <?php if ($fields['prenom']): ?>
                <div class="section">
                    <label>Prénom <span style="color:red;">*</span> :<br><input type="text" name="prenom" placeholder="ex : Jean" required></label>
                </div>
                <?php endif; ?>

                <?php if ($fields['nom']): ?>
                <div class="section">
                    <label>Nom <span style="color:red;">*</span> :<br><input type="text" name="nom" placeholder="ex : Dupont" required></label>
                </div>
                <?php endif; ?>

                <button type="submit">Valider</button>
            </form>

            <h6><span style="color:red;">*</span> Champ obligatoire</h6>
        </div>
    </body>
</html>