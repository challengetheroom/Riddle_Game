<?php
// ========================================================================
// 1. INITIALISATION ET INCLUSION DES DÉPENDANCES
// ========================================================================
// Charge les configurations (clés de chiffrement, variables d'environnement, etc.)
require_once "config.php";

// Charge l'autoloader de Composer. C'est ce qui permet à PHP de trouver 
// et d'utiliser la bibliothèque PhpSpreadsheet située dans le dossier "vendor".
require __DIR__ . '/../vendor/autoload.php';

// Importation des classes spécifiques de PhpSpreadsheet dont on a besoin
use PhpOffice\PhpSpreadsheet\Spreadsheet;      // Le classeur Excel global
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;      // Le moteur pour écrire au format .xlsx
use PhpOffice\PhpSpreadsheet\Style\Color;      // Gestion des couleurs
use PhpOffice\PhpSpreadsheet\Style\Fill;       // Gestion du remplissage des cellules (fonds)

// ========================================================================
// 2. FONCTIONS UTILITAIRES
// ========================================================================

/**
 * Normalise une chaîne de caractères pour la comparaison.
 * Permet de valider que "VéLo" = "velo" = " VÉLO "
 */
function normalizeString($str) {
    if (is_array($str)) return ''; // Sécurité : si on passe un tableau par erreur, on retourne vide
    $str = trim((string)$str); // Enlève les espaces avant et après
    
    // Normalise les caractères Unicode (sépare les lettres de leurs accents)
    if (class_exists('Normalizer')) {
        $str = Normalizer::normalize($str, Normalizer::FORM_D);
    }
    
    // Supprime tous les accents grâce à une expression régulière (Regex)
    $str = preg_replace('/[\x{0300}-\x{036f}]/u', '', $str);
    
    // Convertit le tout en majuscules
    $str = mb_strtoupper($str, 'UTF-8');
    
    return $str;
}

/**
 * Convertit un numéro de colonne en lettre Excel.
 * Exemple : 1 => A, 2 => B ... 27 => AA
 */
function colLetter($c) {
    $c = intval($c);
    $letter = '';
    while ($c > 0) {
        $c--;
        $letter = chr(65 + $c % 26) . $letter;
        $c = intval($c / 26);
    }
    return $letter;
}


// ========================================================================
// 3. RÉCUPÉRATION ET DÉCHIFFREMENT DES DONNÉES
// ========================================================================

// On récupère les configurations et les énigmes
$datas = [];
$datasFile = __DIR__ . '/../data/datas.txt';
if (file_exists($datasFile)) {
    $dec = decryptData(file_get_contents($datasFile), $ENCRYPT_KEY, $ENCRYPT_IV);
    $datas = json_decode($dec, true) ?: [];
}

// On récupère les résultats des joueurs
$received = [];
$receivedFile = __DIR__ . '/../data/received.txt';
if (file_exists($receivedFile)) {
    $dec = decryptData(file_get_contents($receivedFile), $ENCRYPT_KEY, $ENCRYPT_IV);
    $received = json_decode($dec, true) ?: [];
}


// ========================================================================
// 4. CRÉATION DU FICHIER EXCEL
// ========================================================================

// Crée un nouveau document Excel (Classeur) vide en mémoire
$spreadsheet = new Spreadsheet();
// Sélectionne la première page (feuille)
$sheet = $spreadsheet->getActiveSheet();
// Donne un nom à l'onglet en bas de page Excel
$sheet->setTitle('Résultats');


// --- ÉTAPE A : CRÉATION DE LA LIGNE D'EN-TÊTE (Ligne 1) ---
$sheet->setCellValue('A1', 'Email');
$sheet->setCellValue('B1', 'Prénom');
$sheet->setCellValue('C1', 'Nom');

$col = 4; // On commence à placer les énigmes à partir de la 4ème colonne (D)
foreach (array_keys($datas) as $enigme) {
    // Si la clé est un paramètre global (ex: "options", "theme"), on passe à la suivante
    if (in_array($enigme, $RESERVED_KEYS)) continue;
    
    // Écrit le nom de l'énigme dans l'en-tête (ex: Cellule D1, E1, etc.)
    $sheet->setCellValue(colLetter($col) . '1', $enigme);
    $col += 1;
}


// --- ÉTAPE B : REMPLISSAGE DES DONNÉES DES JOUEURS ---
$rowNum = 2; // On commence à la ligne 2 (sous les en-têtes)
foreach ($received as $email => $data) {
    // Infos du joueur
    $sheet->setCellValue('A' . $rowNum, $email);
    $sheet->setCellValue('B' . $rowNum, $data['prenom'] ?? '');
    $sheet->setCellValue('C' . $rowNum, $data['nom'] ?? '');
    
    $col = 4; // On se replace sur la colonne D pour ses réponses
    foreach (array_keys($datas) as $enigme) {
        if (in_array($enigme, $RESERVED_KEYS)) continue; // Ignore les configs

        // Récupère la réponse donnée par le joueur pour cette énigme précise
        $rep = $data['reponses'][$enigme]['reponse'] ?? '';
        // Inscrit la réponse dans la cellule correspondante
        $sheet->setCellValue(colLetter($col) . $rowNum, $rep);

        // --- ÉTAPE C : VÉRIFICATION ET COLORATION ---
        // On récupère la bonne réponse définie par l'admin
        $bonneRep = $datas[$enigme]['reponse_correcte'] ?? null;
        
        // Si le joueur a répondu ET qu'une bonne réponse existe pour cette énigme
        if ($rep && $bonneRep !== null) {
            $is_correct = false;
            // On normalise la réponse du joueur
            $rep_normalized = normalizeString($rep);

            // CAS 1 : Plusieurs réponses correctes sont acceptées (tableau généré par les points-virgules)
            if (is_array($bonneRep)) {
                foreach ($bonneRep as $br) {
                    if ($rep_normalized === normalizeString($br)) {
                        $is_correct = true;
                        break; // Dès qu'on trouve une correspondance, on arrête de chercher
                    }
                }
            } 
            // CAS 2 : Une seule réponse correcte attendue (chaîne de texte standard)
            else {
                if ($rep_normalized === normalizeString($bonneRep)) {
                    $is_correct = true;
                }
            }

            // Si la réponse est juste, on colore le fond de la cellule en vert clair
            if ($is_correct) {
                $sheet->getStyle(colLetter($col) . $rowNum)
                    ->getFill()
                    ->setFillType(Fill::FILL_SOLID)
                    ->getStartColor()->setARGB('FF90EE90'); // Code couleur Hexa avec opacité (Alpha, R, G, B)
            }
        }

        $col += 1; // Passe à la colonne de l'énigme suivante
    }
    $rowNum++; // Passe au joueur suivant (Ligne suivante)
}


// --- ÉTAPE D : FORMATAGE AUTOMATIQUE ---
// Boucle sur toutes les colonnes utilisées pour ajuster automatiquement 
// leur largeur en fonction de la taille du texte le plus long qu'elles contiennent
foreach (range(1, $col-1) as $c) {
    $sheet->getColumnDimension(colLetter($c))->setAutoSize(true);
}


// ========================================================================
// 5. ENVOI DU FICHIER AU NAVIGATEUR (TÉLÉCHARGEMENT)
// ========================================================================

// Ces "headers" (en-têtes HTTP) disent au navigateur de l'utilisateur :
// 1. "Ce qui arrive n'est pas une page web, c'est un fichier Excel (.xlsx)"
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
// 2. "Ouvre la fenêtre de téléchargement et propose ce nom par défaut"
header('Content-Disposition: attachment; filename="résultats.xlsx"');
// 3. "Ne garde pas ce fichier en cache, force le téléchargement de la version la plus récente"
header('Cache-Control: max-age=0');

// Initialise le moteur d'écriture Xlsx de PhpSpreadsheet
$writer = new Xlsx($spreadsheet);
// Enregistre et envoie directement le flux de données généré vers la sortie PHP ("php://output")
// qui sera interceptée par le navigateur pour créer le fichier sur l'ordinateur de l'utilisateur.
$writer->save('php://output');

// Arrête l'exécution du script proprement pour ne rien rajouter dans le fichier (ce qui le corromprait)
exit;