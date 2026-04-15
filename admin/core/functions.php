<?php
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