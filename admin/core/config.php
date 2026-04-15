<?php
// ========================================================================
// 1. DÉTECTION DE L'ENVIRONNEMENT (DÉVELOPPEMENT VS PRODUCTION)
// ========================================================================
// On regarde le nom de domaine appelé dans la barre d'adresse du navigateur.
// Si l'URL contient "riddlegame" (le nom de votre dossier local sous WAMP), 
// on considère qu'on est en développement ('dev').
// Sinon, on considère qu'on est en ligne, sur le serveur réel ('prod').
// Cela permet au code de s'adapter automatiquement (ex: choix du fichier .htpasswd, URL de base, etc.)
$environment = (strpos($_SERVER['HTTP_HOST'], 'riddlegame') !== false) 
               ? 'dev' 
               : 'prod';

/* 
// --- BLOC DE DÉBOGAGE (Actuellement commenté) ---
// Ce bloc permet d'afficher les variables serveur à l'écran pour vérifier 
// pourquoi la détection d'environnement échouerait.
echo "<pre>";
echo "HTTP_HOST: " . $_SERVER['HTTP_HOST'] . "\n";
echo "URL complète: " . $_SERVER['REQUEST_SCHEME'] . '://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'] . "\n";

$environment = (strpos($_SERVER['HTTP_HOST'], 'riddlegame') !== false) 
               ? 'dev' 
               : 'prod';

echo "Environnement détecté: " . $environment . "\n";
echo "</pre>";
*/

// ========================================================================
// 2. CONFIGURATION DE LA SÉCURITÉ ET DU CHIFFREMENT (AES-256)
// ========================================================================
// Ces clés servent à brouiller le contenu de datas.txt et received.txt 
// pour qu'ils soient illisibles si quelqu'un arrivait à les télécharger.

// Clé secrète principale pour l'algorithme AES-256-CBC.
// Elle doit être très longue et complexe (ici 64 caractères hexadécimaux).
// ⚠️ Si cette clé est modifiée un jour, tous les anciens fichiers TXT deviendront illisibles !
$ENCRYPT_KEY = "fd27ce901302ae7872baf9bd7a8c62ebd65780b2463f9ca979e632c58e074434";

// Vecteur d'initialisation (IV - Initialization Vector).
// C'est un paramètre obligatoire pour l'algorithme AES-256-CBC. Il doit faire exactement 16 octets/caractères.
// Il garantit que deux textes identiques chiffrés ne donneront pas le même résultat visuel.
$ENCRYPT_IV = "d34ab172db6c8cde";

// Clé secrète pour générer la signature numérique (HMAC).
// Cette signature est envoyée au joueur (via le cookie ou le localStorage) 
// et vérifiée lors de la soumission de la réponse pour garantir que le joueur n'a pas falsifié ses données.
$SECRET_HMAC = "73d20af918aca394ca413088a121f205eb09a65af7c973ed242e211c87a14a6e";


// ========================================================================
// 3. PARAMÈTRES GLOBAUX DU JEU
// ========================================================================
// Le fichier datas.txt stocke à la fois la liste des énigmes ET les réglages du site.
// Ce tableau liste tous les mots-clés qui sont des "réglages" et non des énigmes.
// Partout dans le code (ex: génération du tableau de résultats), si on rencontre 
// une de ces clés, on sait qu'il faut l'ignorer en tant qu'énigme.
$RESERVED_KEYS = ['theme', 'theme_enigmes', 'theme_messages', 'messages', 'options'];


// ========================================================================
// 4. FONCTIONS DE CHIFFREMENT (UTILISÉES PAR TOUT LE PROJET)
// ========================================================================

/**
 * Chiffre un texte clair en texte illisible (Base64)
 * 
 * @param string $plaintext Le texte lisible (ex: le JSON de datas.txt)
 * @param string $key La clé de cryptage définie plus haut
 * @param string $iv Le vecteur d'initialisation défini plus haut
 * @return string Le texte chiffré et encodé en base 64 pour pouvoir être sauvegardé proprement
 */
function encryptData($plaintext, $key, $iv) {
    // 1. Chiffre la donnée en AES-256-CBC (format binaire brut)
    // 2. Encode ce format binaire en Base64 pour obtenir une chaîne de texte propre (lettres et chiffres uniquement)
    return base64_encode(openssl_encrypt($plaintext, "AES-256-CBC", $key, OPENSSL_RAW_DATA, $iv));
}

/**
 * Déchiffre un texte illisible en texte clair (JSON)
 * 
 * @param string $ciphertext Le texte crypté en base64
 * @param string $key La clé de cryptage définie plus haut
 * @param string $iv Le vecteur d'initialisation défini plus haut
 * @return string Le texte d'origine (ou false en cas d'erreur de clé)
 */
function decryptData($ciphertext, $key, $iv) {
    // 1. Décode la chaîne Base64 pour retrouver les données binaires chiffrées
    // 2. Déchiffre ces données avec la clé pour récupérer le texte original
    return openssl_decrypt(base64_decode($ciphertext), "AES-256-CBC", $key, OPENSSL_RAW_DATA, $iv);
}