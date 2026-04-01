<?php
// ========================================================================
// INITIALISATION ET SÉCURITÉ
// ========================================================================
// Charge les configurations globales (clés de chiffrement, chemins, environnement)
require_once "../config.php";

// SÉCURITÉ MAJEURE : On empêche l'exécution de ce script sur le serveur de production.
// Si un utilisateur malveillant (ou vous-même par erreur) tente d'y accéder en ligne, le script s'arrête.
// Il ne fonctionne que sur WAMP/XAMPP (quand $environment === 'dev').
if ($environment !== 'dev') {
    die("Accès refusé : cette page n'est disponible qu'en environnement de développement.");
}

$message = ''; // Variable pour stocker les messages de retour (succès ou erreur)

// ========================================================================
// TRAITEMENT DES FORMULAIRES DE SAUVEGARDE
// ========================================================================
// Vérifie si le formulaire a été soumis en POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Récupère l'action demandée (soit 'save_datas' soit 'save_received')
    $action = $_POST['action'] ?? '';
    
    // --- SAUVEGARDE DE datas.txt ---
    if ($action === 'save_datas') {
        $content = $_POST['datas_content'] ?? ''; // Récupère le texte brut tapé dans le textarea
        
        // VÉRIFICATION DE LA VALIDITÉ DU JSON
        // On tente de décoder le texte pour voir s'il respecte bien la syntaxe JSON (pas de virgule manquante, etc.)
        $test = json_decode($content, true);
        
        // Si json_last_error() ne retourne pas "JSON_ERROR_NONE", c'est que le texte est mal formaté
        if (json_last_error() !== JSON_ERROR_NONE) {
            $message = "Erreur JSON dans datas.txt : " . json_last_error_msg();
        } else {
            // Si le JSON est valide, on le rechiffre avec nos clés de sécurité
            $encrypted = encryptData($content, $ENCRYPT_KEY, $ENCRYPT_IV);
            // On écrase l'ancien fichier datas.txt avec le nouveau contenu chiffré
            // LOCK_EX empêche qu'un autre utilisateur lise le fichier pendant qu'on écrit dedans
            file_put_contents('../datas.txt', $encrypted, LOCK_EX);
            $message = "datas.txt sauvegardé avec succès.";
        }
    }
    
    // --- SAUVEGARDE DE received.txt ---
    elseif ($action === 'save_received') {
        $content = $_POST['received_content'] ?? '';
        
        // Même logique de vérification JSON que pour datas.txt
        $test = json_decode($content, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            $message = "Erreur JSON dans received.txt : " . json_last_error_msg();
        } else {
            // Chiffrement et sauvegarde
            $encrypted = encryptData($content, $ENCRYPT_KEY, $ENCRYPT_IV);
            file_put_contents('../received.txt', $encrypted, LOCK_EX);
            $message = "received.txt sauvegardé avec succès.";
        }
    }
}

// ========================================================================
// LECTURE ET PRÉPARATION DES DONNÉES POUR L'AFFICHAGE
// ========================================================================
// On lit les fichiers actuels pour peupler les zones de texte (textareas)

$datas_content = '';
if (file_exists('../datas.txt')) {
    // Lecture du fichier crypté
    $enc = file_get_contents('../datas.txt');
    // Déchiffrement pour obtenir le texte JSON clair
    $datas_content = decryptData($enc, $ENCRYPT_KEY, $ENCRYPT_IV);
}

$received_content = '';
if (file_exists('../received.txt')) {
    // Lecture du fichier crypté
    $enc = file_get_contents('../received.txt');
    // Déchiffrement pour obtenir le texte JSON clair
    $received_content = decryptData($enc, $ENCRYPT_KEY, $ENCRYPT_IV);
}

?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Éditeur de données brutes (DEV ONLY)</title>
    <!-- On remonte de deux dossiers pour trouver le style.css racine -->
    <link rel="stylesheet" href="../../style.css">
    
    <!-- CSS spécifique à cette page d'outil -->
    <style>
        .edit-container {
            max-width: 100%;
            margin: 20px auto;
            padding: 20px;
        }
        /* Conteneur d'un éditeur */
        .edit-box {
            margin-bottom: 30px;
            background: #fff;
            padding: 20px;
            border-radius: 12px;
            box-shadow: 0 3px 6px rgba(0,0,0,0.1);
        }
        /* Titre de l'éditeur (datas.txt ou received.txt) */
        .edit-box h2 {
            margin-top: 0;
            color: #000;
            background: #ffeb3b;
            padding: 10px;
            border-radius: 6px;
        }
        /* Style façon terminal/console de la zone de texte */
        textarea.json-editor {
            width: 100%;
            height: 400px;
            font-family: 'Courier New', monospace; /* Police à chasse fixe obligatoire pour coder */
            font-size: 14px;
            padding: 15px;
            border: 2px solid #333;
            border-radius: 8px;
            background: #1e1e1e; /* Fond sombre */
            color: #0f0; /* Texte vert */
            resize: vertical; /* L'utilisateur peut agrandir le champ en hauteur seulement */
        }
        /* Bannières de notification */
        .warning {
            background: #ff5722;
            color: white;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-weight: bold;
        }
        .success {
            background: #4caf50;
            color: white;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-weight: bold;
        }
        .error {
            background: #f44336;
            color: white;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <div class="edit-container">
        <h1>🔧 Éditeur de données brutes (DEV ONLY)</h1>
        
        <!-- Avertissement de sécurité permanent -->
        <div class="warning">
            ⚠️ ATTENTION : Cette page permet de modifier directement les fichiers chiffrés. 
            Toute erreur de syntaxe JSON peut corrompre les données. 
            Faites une sauvegarde avant toute modification !
        </div>

        <!-- Affichage des messages de confirmation ou d'erreur -->
        <?php if($message): ?>
            <!-- Si le message contient le mot "Erreur", on affiche la bannière rouge -->
            <?php if(strpos($message, 'Erreur') !== false): ?>
                <div class="error"><?php echo htmlspecialchars($message, ENT_QUOTES); ?></div>
            <!-- Sinon c'est un succès, on affiche la bannière verte -->
            <?php else: ?>
                <div class="success"><?php echo htmlspecialchars($message, ENT_QUOTES); ?></div>
            <?php endif; ?>
        <?php endif; ?>

        <!-- ========================================================== -->
        <!-- FORMULAIRE D'ÉDITION DE datas.txt                          -->
        <!-- ========================================================== -->
        <div class="edit-box">
            <h2>📝 datas.txt</h2>
            <form method="POST">
                <!-- Champ caché pour indiquer au PHP quelle action exécuter -->
                <input type="hidden" name="action" value="save_datas">
                <!-- Zone de texte préremplie avec le JSON déchiffré -->
                <textarea name="datas_content" class="json-editor"><?php echo htmlspecialchars($datas_content, ENT_QUOTES); ?></textarea>
                <br><br>
                <!-- Bouton de soumission avec sécurité JS native (confirm) pour éviter les clics accidentels -->
                <button type="submit" onclick="return confirm('Êtes-vous sûr de vouloir sauvegarder datas.txt ?');">
                    💾 Sauvegarder datas.txt
                </button>
            </form>
        </div>

        <!-- ========================================================== -->
        <!-- FORMULAIRE D'ÉDITION DE received.txt                       -->
        <!-- ========================================================== -->
        <div class="edit-box">
            <h2>📝 received.txt</h2>
            <form method="POST">
                <input type="hidden" name="action" value="save_received">
                <textarea name="received_content" class="json-editor"><?php echo htmlspecialchars($received_content, ENT_QUOTES); ?></textarea>
                <br><br>
                <button type="submit" onclick="return confirm('Êtes-vous sûr de vouloir sauvegarder received.txt ?');">
                    💾 Sauvegarder received.txt
                </button>
            </form>
        </div>

        <!-- Bouton de retour vers l'interface d'administration classique -->
        <div style="text-align: center; margin-top: 30px;">
            <a href="../index.php" style="padding: 10px 20px; background: #007BFF; color: white; text-decoration: none; border-radius: 8px;">
                ← Retour à l'admin
            </a>
        </div>
    </div>
</body>
</html>