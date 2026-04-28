<?php
// ========================================================================
// 1. INCLUSIONS ET CONFIGURATION
// ========================================================================
require_once "core/config.php";
require_once "core/functions.php";
require_once "core/view_datas.php";
date_default_timezone_set('Europe/Paris');

// ========================================================================
// 2. DÉCONNEXION (Si demandé)
// ========================================================================
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

// ========================================================================
// 3. LECTURE DES DONNÉES
// ========================================================================
$datasFile = 'data/datas.txt';
$datas = [];
if (file_exists($datasFile)) {
    $enc = file_get_contents($datasFile);
    $dec = decryptData($enc, $ENCRYPT_KEY, $ENCRYPT_IV);
    $datas = json_decode($dec, true) ?: [];
}

$received = [];
if (file_exists('data/received.txt')) {
    $enc = file_get_contents('data/received.txt');
    $dec = decryptData($enc, $ENCRYPT_KEY, $ENCRYPT_IV);
    $received = json_decode($dec, true) ?: [];
}

// ========================================================================
// 4. TRAITEMENT DES FORMULAIRES (Ajout/Modif/Suppression)
// ========================================================================
require_once "core/actions.php"; // <-- Le "Cerveau" prend le relais ici si on a cliqué sur un bouton !

// ========================================================================
// 5. AFFICHAGE (Préparation de l'interface)
// ========================================================================
// Récupération des variables passées dans l'URL pour gérer l'affichage
$message = $_GET['msg'] ?? '';
$activeTab = $_GET['tab'] ?? 'resultats';
?>

<!DOCTYPE html>
<html lang="fr">
    <head>
        <meta charset="UTF-8">
        <title>Admin - Gestion des énigmes</title>
        <!-- Feuille de style globale du projet (située dans le dossier parent) -->
        <link rel="stylesheet" href="../style.css">
        <!-- Feuille de style spécifique à l'administration -->
        <link rel="stylesheet" href="style-admin.css">
        <!-- CSS de DataTables (pour le tableau des résultats triable/cherchable) -->
        <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">
        <!-- Scripts jQuery et DataTables -->
        <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
        <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
        <!-- Script pour l'éditeur de texte riche TinyMCE (Onglet Création de message) -->
        <script src="https://cdnjs.cloudflare.com/ajax/libs/tinymce/6.8.2/tinymce.min.js"></script>
    </head>
    <body>
        <div class="container">

            <!-- En-tête avec profil actif et bouton de déconnexion -->
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; flex-wrap: wrap; gap: 15px;">
                <h1 style="margin: 0;">Admin - Gestion des énigmes</h1>

                <!-- ========================================================== -->
                <!-- BANDEAU DU PROFIL ACTIF                                    -->
                <!-- ========================================================== -->
                <?php
                $activeProfileName = $datas['options']['current_profile_name'] ?? null;
                $activeProfileId = $datas['options']['current_profile_id'] ?? null;
                $unsavedChanges = $datas['options']['unsaved_profile_changes'] ?? false;
                ?>
                <div id="active-profile-banner" style="background: #e8f0ff; padding: 8px 15px; border-radius: 8px; border: 1px solid #b5ceEE; display: flex; align-items: center; gap: 15px; flex-grow: 1; max-width: fit-content;">
                    <div style="font-size: 15px;">
                        Profil : 
                        <?php if ($activeProfileName): ?>
                        <span id="display-profile-name" style="color: #1a4f9b; <?php echo $unsavedChanges ? 'font-weight: bold;' : ''; ?>"><?php echo htmlspecialchars($activeProfileName); ?></span>
                        <span id="unsaved-asterisk" style="color: #dc3545; font-size: 1.2em; font-weight: bold; <?php echo $unsavedChanges ? 'display: inline;' : 'display: none;'; ?> vertical-align: middle;" title="Modifications non sauvegardées"> *</span>
                        <?php else: ?>
                        <span style="font-style: italic; color: #6c757d;">Profil inconnu</span>
                        <?php endif; ?>
                    </div>

                    <div>
                        <?php if ($activeProfileName): ?>
                        <button type="button" id="btn-quick-update" data-profile-id="<?php echo htmlspecialchars($activeProfileId); ?>" style="<?php echo $unsavedChanges ? 'display: inline-block;' : 'display: none;'; ?> background: #ffc107; color: #333; font-weight: bold; padding: 5px 10px; border-radius: 4px; border: 1px solid #d39e00; cursor: pointer; font-size: 13px; margin: 0;">
                            🔄 Mettre à jour
                        </button>
                        <?php else: ?>
                        <!-- Bouton qui utilise jQuery pour activer l'onglet Profils -->
                        <button type="button" style="background: #28a745; color: white; font-weight: bold; padding: 5px 10px; border-radius: 4px; border: none; cursor: pointer; font-size: 13px;" onclick="$('.tab').removeClass('active'); $('.tab-content').removeClass('active'); $('[data-tab=\\'profils\\']').addClass('active'); $('#profils').addClass('active');">
                            💾 Sauvegarder (Nouveau)
                        </button>
                        <?php endif; ?>
                    </div>
                </div>
                <!-- ========================================================== -->

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
                <div class="tab <?php echo ($activeTab === 'creation') ? 'active' : ''; ?>" data-tab="creation">Création de message</div>
                <div class="tab <?php echo ($activeTab === 'profils') ? 'active' : ''; ?>" data-tab="profils">Profils</div>
                <div class="tab <?php echo ($activeTab === 'datas') ? 'active' : ''; ?>" data-tab="datas">Données</div>
            </div>

            <!-- ========================================================== -->
            <!-- ONGLET 1 : RÉSULTATS DES JOUEURS                           -->
            <!-- ========================================================== -->
            <?php include 'tabs/tab_resultats.php'; ?>

            <!-- ========================================================== -->
            <!-- ONGLET 2 : ÉDITION DES ÉNIGMES                             -->
            <!-- ========================================================== -->
            <?php include 'tabs/tab_edition.php'; ?>

            <!-- ========================================================== -->
            <!-- ONGLET 3 : ÉDITION DES MESSAGES DE RÉSULTAT                -->
            <!-- ========================================================== -->
            <?php include 'tabs/tab_messages.php'; ?>

            <!-- ========================================================== -->
            <!-- ONGLET NOUVEAU : CRÉATION DE MESSAGE (Bac à sable)         -->
            <!-- ========================================================== -->
            <?php include 'tabs/tab_creation.php'; ?>

            <!-- ========================================================== -->
            <!-- ONGLET : GESTION DES PROFILS (Sauvegardes)                 -->
            <!-- ========================================================== -->
            <?php include 'tabs/tab_profils.php'; ?>

            <!-- ========================================================== -->
            <!-- ONGLET 4 : AFFICHAGE DES DONNÉES BRUTES (DEBUG/SÉCURITÉ)   -->
            <!-- ========================================================== -->
            <?php include 'tabs/tab_datas.php'; ?>
        </div>

        <!-- ======================================================================== -->
        <!-- POPUP QR CODE (Masquée par défaut)                                       -->
        <!-- ======================================================================== -->
        <div id="qr-modal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.8); z-index: 9999; justify-content: center; align-items: center; flex-direction: column; backdrop-filter: blur(3px);">
            <div style="background: #fff; padding: 25px; border-radius: 12px; text-align: center; max-width: 90%; width: 350px; position: relative; box-shadow: 0 5px 15px rgba(0,0,0,0.3);">
                <!-- Bouton fermer -->
                <button onclick="document.getElementById('qr-modal').style.display='none'" style="position: absolute; top: 15px; right: 15px; background: #dc3545; color: white; border: none; border-radius: 50%; width: 32px; height: 32px; cursor: pointer; font-weight: bold; font-size: 16px;">&times;</button>

                <h3 id="qr-modal-title" style="margin-top: 0; margin-bottom: 20px; color: #333;">QR Code</h3>

                <!-- Image grand format -->
                <img id="qr-modal-img" src="" style="width: 250px; height: 250px; object-fit: contain; margin-bottom: 20px; border: 1px solid #eee; border-radius: 8px; padding: 10px; background: #f9f9f9;">
                <br>

                <!-- Bouton de téléchargement -->
                <a id="qr-modal-download" href="" download style="display: block; padding: 12px 20px; background: #28a745; color: white; text-decoration: none; border-radius: 8px; font-weight: bold; font-size: 16px; transition: background 0.3s;" onmouseover="this.style.background='#218838'" onmouseout="this.style.background='#28a745'">
                    📥 Télécharger l'image
                </a>
            </div>
        </div>

        <!-- ========================================================== -->
        <!-- SCRIPTS JAVASCRIPT GÉRANT L'INTERFACE                      -->
        <!-- ========================================================== -->
        <script src="core/admin.js"></script>
    </body>
</html>