<?php
// ========================================================================
// INCLUSION DES CONFIGURATIONS
// ========================================================================
// Récupère les clés de cryptage ($ENCRYPT_KEY, $ENCRYPT_IV) et les fonctions 
// de déchiffrement depuis le fichier principal config.php
require_once "config.php";


// ========================================================================
// FONCTION : getDatasContent()
// ========================================================================
/**
 * Lit et déchiffre le contenu du fichier "datas.txt" (Énigmes et Configurations).
 * Cette fonction est appelée dans l'onglet "Données" de l'administration.
 * 
 * @return string Le texte JSON en clair, ou un message d'erreur HTML.
 */
function getDatasContent() {
    // Le mot-clé "global" permet d'importer à l'intérieur de cette fonction 
    // les variables qui ont été définies à l'extérieur (dans config.php).
    global $ENCRYPT_KEY, $ENCRYPT_IV;
    
    // CORRECTION ICI : Le fichier est dans admin/data/
    $filename = __DIR__ . "/../data/datas.txt";
    
    // Vérifie d'abord si le fichier existe physiquement sur le serveur
    if (!file_exists($filename)) {
        return "<p>Fichier introuvable.</p>";
    }
    
    // Lit le fichier sous sa forme cryptée (Base64)
    $enc = file_get_contents($filename);
    
    // Retourne le texte décrypté (le vrai JSON lisible)
    return decryptData($enc, $ENCRYPT_KEY, $ENCRYPT_IV);
}


// ========================================================================
// FONCTION : getReceivedContent()
// ========================================================================
/**
 * Lit et déchiffre le contenu du fichier "received.txt" (Résultats des joueurs).
 * 
 * @return string Le texte JSON en clair, ou un message d'erreur HTML.
 */
function getReceivedContent() {
    // Importe les variables globales de cryptage
    global $ENCRYPT_KEY, $ENCRYPT_IV;
    
    // CORRECTION ICI : Le fichier est dans admin/data/
    $filename = __DIR__ . "/../data/received.txt";
    
    // Vérifie si le fichier de résultats existe
    if (!file_exists($filename)) {
        return "<p>Fichier introuvable.</p>";
    }
    
    // Lit et décrypte les données
    $enc = file_get_contents($filename);
    return decryptData($enc, $ENCRYPT_KEY, $ENCRYPT_IV);
}


// ========================================================================
// FONCTION DE SÉCURITÉ : safePrint()
// ========================================================================
/**
 * Sécurise l'affichage du texte brut à l'écran (Protection contre la faille XSS).
 * 
 * Si un joueur malveillant s'amuse à taper du code HTML ou JavaScript comme 
 * réponse (ex: <script>alert('piraté')</script>), cette fonction va 
 * transformer les chevrons en entités inoffensives (&lt; et &gt;).
 * Ainsi, le navigateur affichera le texte, mais refusera de l'exécuter.
 * 
 * @param string $content Le texte à sécuriser (le JSON brut)
 * @return string Le texte nettoyé et prêt à être affiché dans le HTML
 */
function safePrint($content) {
    // ENT_QUOTES convertit à la fois les guillemets doubles et simples.
    // 'UTF-8' garantit que les caractères accentués ne posent pas de problème.
    return htmlspecialchars($content, ENT_QUOTES, 'UTF-8');
}
?>