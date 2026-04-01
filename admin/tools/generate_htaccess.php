<?php
// ========================================================================
// INITIALISATION ET CONFIGURATION
// ========================================================================
// On inclut le fichier de configuration principal pour récupérer la variable $environment ('dev' ou 'prod')
require_once "../config.php";

// SÉCURITÉ : La ligne ci-dessous est commentée. 
// En temps normal, ce type de script ne devrait s'exécuter qu'en local ('dev').
// Cependant, pour générer le .htaccess sur le serveur distant, il faut que le script puisse tourner en 'prod' une fois.
// Une fois le fichier .htaccess généré sur le serveur, il est VIVEMENT CONSEILLÉ de supprimer ce fichier generate_htaccess.php !
//if ($environment !== 'dev') {
//    die("Accès refusé : cette page n'est disponible qu'en environnement de développement.");
//}

$message = ''; // Variable pour stocker le message de confirmation après génération

// ========================================================================
// GÉNÉRATION DU FICHIER LORS DE LA SOUMISSION DU FORMULAIRE (POST)
// ========================================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // Tableau associatif contenant les chemins absolus vers le fichier de mots de passe (.htpasswd)
    // C'est la ligne la plus critique du projet concernant la sécurité serveur.
    $htpasswd_paths = [
        'dev' => 'C:/wamp64/www/Riddle_Game/admin/.htpasswd', // Chemin sous Windows (WAMP)
        'prod' => '/home/www/sitesClient/ChallengeTheRoom/JEU/Projets/Riddle_Game/admin/.htpasswd' // Chemin sous Linux (Serveur de production)
    ];

    // Construction dynamique du contenu texte du fichier .htaccess
    // Le serveur Apache lira ce fichier pour bloquer l'accès au dossier /admin
    $htaccess_content = "SetEnvIf Authorization \"(.*)\" HTTP_AUTHORIZATION=$1\n\n" . // Force la transmission du mot de passe à PHP (utile pour vider les logs)
                       "# Authentification\n" .
                       "AuthType Basic\n" .
                       "AuthName \"Zone administrateur\"\n" .
                       "AuthUserFile \"" . $htpasswd_paths[$environment] . "\"\n" . // Insère le bon chemin selon qu'on soit en dev ou en prod
                       "Require valid-user\n\n" .
                       "# Empêche l'accès direct aux fichiers système\n" . // Empêche un pirate de télécharger ou lire le .htpasswd directement via l'URL
                       "<FilesMatch \"^(\\.htaccess|\\.htpasswd)$\">\n" .
                       "    Require all denied\n" .
                       "</FilesMatch>\n";

    // Écrit le contenu généré dans le fichier ../.htaccess (dans le dossier admin/)
    // LOCK_EX bloque le fichier pendant l'écriture pour éviter les conflits
    file_put_contents('../.htaccess', $htaccess_content, LOCK_EX);
    
    $message = "Fichier .htaccess généré avec succès pour l'environnement <strong>" . htmlspecialchars($environment) . "</strong>.";
}

?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Générateur .htaccess (DEV ONLY)</title>
    <!-- Inclut le style CSS principal situé à la racine du projet -->
    <link rel="stylesheet" href="../../style.css">
    
    <!-- Styles CSS spécifiques à cet outil -->
    <style>
        .tool-container {
            max-width: 800px;
            margin: 40px auto;
            padding: 30px;
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
        /* Boîtes d'information et d'avertissement */
        .info-box {
            background: #e3f2fd;
            border-left: 4px solid #2196f3;
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 6px;
        }
        .warning {
            background: #fff3cd;
            border-left: 4px solid #ffc107;
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 6px;
            color: #856404;
        }
        .success {
            background: #d4edda;
            border-left: 4px solid #28a745;
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 6px;
            color: #155724;
        }
        /* Style de la zone de code "Aperçu" (façon terminal) */
        .preview {
            background: #1e1e1e;
            color: #0f0;
            padding: 15px;
            border-radius: 8px;
            font-family: 'Courier New', monospace;
            font-size: 14px;
            white-space: pre-wrap; /* Conserve les sauts de ligne */
            margin: 20px 0;
        }
        /* Bouton d'action principal */
        button.generate {
            background: #28a745;
            padding: 12px 30px;
            font-size: 16px;
        }
        button.generate:hover {
            background: #218838;
        }
    </style>
</head>
<body>
    <div class="tool-container">
        <h1>🔧 Générateur de .htaccess</h1>
        
        <!-- Affiche l'environnement en cours détecté par config.php (dev ou prod) -->
        <div class="info-box">
            <strong>ℹ️ Environnement détecté :</strong> <?php echo htmlspecialchars($environment); ?>
        </div>

        <!-- Avertissement utilisateur -->
        <div class="warning">
            ⚠️ <strong>Attention :</strong> Cette action écrasera le fichier <code>admin/.htaccess</code> existant. 
            Le fichier sera adapté automatiquement à l'environnement détecté.
        </div>

        <!-- Affichage du message de succès si le formulaire vient d'être soumis -->
        <?php if($message): ?>
            <div class="success"><?php echo $message; ?></div>
        <?php endif; ?>

        <h3>Aperçu du fichier qui sera généré :</h3>
        
        <?php
        // ========================================================================
        // PRÉPARATION DE L'APERÇU VISUEL (IDENTIQUE AU BLOC POST)
        // ========================================================================
        // On recrée exactement le même contenu que plus haut, mais cette fois uniquement pour l'afficher à l'écran
        $htpasswd_paths = [
            'dev' => 'C:/wamp64/www/Riddle_Game/admin/.htpasswd',
            'prod' => '/home/www/sitesClient/ChallengeTheRoom/JEU/Projets/Riddle_Game/admin/.htpasswd'
        ];

        // J'ai ajouté la ligne "SetEnvIf Authorization..." ici aussi pour que l'aperçu corresponde exactement au vrai fichier généré
        $preview = "SetEnvIf Authorization \"(.*)\" HTTP_AUTHORIZATION=$1\n\n" .
                   "# Authentification\n" .
                   "AuthType Basic\n" .
                   "AuthName \"Zone administrateur\"\n" .
                   "AuthUserFile \"" . $htpasswd_paths[$environment] . "\"\n" .
                   "Require valid-user\n\n" .
                   "# Empêche l'accès direct aux fichiers système\n" .
                   "<FilesMatch \"^(\\.htaccess|\\.htpasswd)$\">\n" .
                   "    Require all denied\n" .
                   "</FilesMatch>";
        ?>
        
        <!-- Affiche le code final généré dans une boîte noire façon code -->
        <div class="preview"><?php echo htmlspecialchars($preview); ?></div>

        <!-- Formulaire contenant le bouton d'action -->
        <form method="POST">
            <!-- Une popup JavaScript (confirm) s'ouvre pour valider l'action avant d'envoyer le POST -->
            <button type="submit" class="generate" onclick="return confirm('Générer le fichier .htaccess pour l\'environnement <?php echo htmlspecialchars($environment); ?> ?');">
                ✅ Générer admin/.htaccess
            </button>
        </form>

        <!-- Bouton retour -->
        <div style="text-align: center; margin-top: 30px;">
            <a href="../index.php" style="padding: 10px 20px; background: #007BFF; color: white; text-decoration: none; border-radius: 8px;">
                ← Retour à l'admin
            </a>
        </div>
    </div>
</body>
</html>