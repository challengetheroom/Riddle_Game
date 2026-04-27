<?php
// ========================================================================
// GESTION DES REQUÊTES POST (ENREGISTREMENT DES FORMULAIRES)
// ========================================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Récupération de l'action demandée par le formulaire soumis
    $action = $_POST['action'] ?? '';
    // Mémorise l'onglet depuis lequel l'action a été faite pour y retourner
    $activeTab = $_POST['active_tab'] ?? 'resultats';

    // --- CRÉER UNE NOUVELLE ÉNIGME ---
    if ($action === 'add_enigme') {
        $nom = trim($_POST['nouvelle_enigme'] ?? '');
        if ($nom === '') $message = "Nom d'énigme vide.";
        elseif (isset($datas[$nom])) $message = "Erreur : cette énigme existe déjà.";
        else {
            // 1. Initialisation avec un nouveau token
            $token = generateToken(12);

            // =========================================================
            // GÉNÉRATION DU QR CODE VIA L'API QRCODE-MONKEY
            // =========================================================
            global $environment;
            $scheme = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? 'https://' : 'http://';

            if (isset($environment) && $environment === 'dev') {
                $baseURL = $scheme . 'riddlegame';
            } else {
                $baseURL = $scheme . 'ctr.komotion.fr/Projets/Riddle_Game';
            }
            $enigmeUrl = $baseURL . '/index.php?k=' . urlencode($token);

            // Préparation des données pour l'API
            $apiData = [
                "data" => $enigmeUrl,
                "config" => [
                    "eye" => "frame13",
                    "eyeBall" => "ball15"/*,
                    "gradientColor1" => "#692A00",
                    "gradientColor2" => "#000000",
                    "gradientType" => "radial",
                    "gradientOnEyes" => true*/
                ],
                "size" => 600,
                "download" => false,
                "file" => "png"
            ];

            $payload = json_encode($apiData);

            // Appel à l'API via cURL
            $ch = curl_init('https://api.qrcode-monkey.com/qr/custom');
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
            curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Content-Type: application/json',
                'Content-Length: ' . strlen($payload)
            ]);

            // --- AJOUTS INDISPENSABLES POUR LE LOCAL (Désactive le SSL) ---
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);

            $qrImage = curl_exec($ch);

            // On vérifie si cURL a échoué
            $curlError = curl_error($ch);
            curl_close($ch);

            // 2. Enregistrement des données dans le JSON
            $datas[$nom] = [
                "meta" => ["token" => $token],
                "texte" => "Nouveau texte",
                "reponse_correcte" => ""
            ];

            // 3. Création du dossier et sauvegarde de l'image
            // Si on a bien reçu une image ET qu'il n'y a pas eu d'erreur cURL
            if ($qrImage && empty($curlError) && strpos($qrImage, 'PNG') !== false) {
                // CORRECTION : On remonte d'un cran (../) car actions.php est dans core/
                $qrDir = __DIR__ . '/../QRCodes';
                if (!is_dir($qrDir)) {
                    mkdir($qrDir, 0777, true);
                }
                $safeName = preg_replace('/[^a-zA-Z0-9_-]/', '_', $nom);
                file_put_contents($qrDir . '/' . $safeName . '.png', $qrImage);
                $message = "Nouvelle énigme '$nom' ajoutée et QR Code généré.";
            } else {
                // Si ça rate, l'énigme est quand même créée, mais on prévient que le QR Code a échoué
                $message = "Nouvelle énigme '$nom' ajoutée, MAIS erreur QR Code : " . ($curlError ? $curlError : "Mauvaise réponse de l'API");
            }
            // =========================================================
        }
    }
    // --- METTRE À JOUR LES COULEURS DES ÉNIGMES ---
    elseif ($action === 'update_theme') {
        $theme = $_POST['theme_enigmes'] ?? [];
        if (!empty($theme)) {
            $datas['theme_enigmes'] = $theme;
            // Nettoyage de l'ancienne clé "theme" si elle existe encore
            if (isset($datas['theme'])) unset($datas['theme']); 
        }
        $message = "Thème mis à jour avec succès.";
    }
    // --- METTRE À JOUR LES OPTIONS GLOBALES DU JEU ---
    elseif ($action === 'update_options') {
        $datas['options']['une_seule_tentative'] = isset($_POST['une_seule_tentative']) ? true : false;

        // Enregistrement de l'état des 4 champs
        $datas['options']['fields'] = [
            'email'   => isset($_POST['field_email']) ? true : false,
            'nom'     => isset($_POST['field_nom']) ? true : false,
            'prenom'  => isset($_POST['field_prenom']) ? true : false,
            'reponse' => isset($_POST['field_reponse']) ? true : false
        ];

        $message = "Options globales mises à jour.";
    }
    // --- MODIFIER UNE ÉNIGME EXISTANTE ---
    elseif ($action === 'update_enigme') {
        $nom = $_POST['enigme'] ?? '';
        if ($nom === '' || !isset($datas[$nom])) $message = "Énigme introuvable pour mise à jour.";
        else {
            $texte = $_POST['texte'] ?? '';
            $raw_reponses = trim($_POST['reponse_correcte'] ?? '');

            // Traitement des réponses multiples : si un point-virgule est présent, on convertit la chaîne en tableau
            if (strpos($raw_reponses, ';') !== false) {
                $array_reponses = array_map('trim', explode(';', $raw_reponses));
                $array_reponses = array_filter($array_reponses, function($val) { return $val !== ''; }); // Retire les valeurs vides
                $datas[$nom]['reponse_correcte'] = array_values($array_reponses);
            } else {
                // Sinon on stocke la réponse en tant que chaîne simple
                $datas[$nom]['reponse_correcte'] = $raw_reponses;
            }

            $datas[$nom]['texte'] = $texte;

            // --- GESTION DES MESSAGES UNIQUES ---
            // On s'assure que le sous-tableau existe
            if (!isset($datas[$nom]['messages_uniques'])) {
                $datas[$nom]['messages_uniques'] = [];
            }

            // 1. Bonne réponse unique
            if (isset($_POST['use_unique_bonne'])) {
                $datas[$nom]['messages_uniques']['bonne_reponse'] = $_POST['unique_bonne_reponse'] ?? '';
            } else {
                unset($datas[$nom]['messages_uniques']['bonne_reponse']);
            }

            // 2. Mauvaise réponse unique
            if (isset($_POST['use_unique_mauvaise'])) {
                $datas[$nom]['messages_uniques']['mauvaise_reponse'] = $_POST['unique_mauvaise_reponse'] ?? '';
            } else {
                unset($datas[$nom]['messages_uniques']['mauvaise_reponse']);
            }

            // 3. Déjà répondu unique
            if (isset($_POST['use_unique_deja'])) {
                $datas[$nom]['messages_uniques']['deja_repondu'] = $_POST['unique_deja_repondu'] ?? '';
            } else {
                unset($datas[$nom]['messages_uniques']['deja_repondu']);
            }

            // Nettoyage : si le tableau des messages uniques est vide, on le supprime pour ne pas alourdir le JSON
            if (empty($datas[$nom]['messages_uniques'])) {
                unset($datas[$nom]['messages_uniques']);
            }

            $message = "Énigme '$nom' mise à jour.";
        }
    }
    // --- SUPPRIMER UNE ÉNIGME ---
    elseif ($action === 'delete_enigme') {
        $nom = $_POST['enigme'] ?? '';
        if ($nom === '' || !isset($datas[$nom])) {
            $message = "Énigme introuvable pour suppression.";
        } else {
            unset($datas[$nom]); // Supprime la clé du tableau JSON

            // ---> SUPPRESSION DU QR CODE ASSOCIÉ <---
            // CORRECTION : On remonte d'un cran (../) car actions.php est dans core/
            $safeName = preg_replace('/[^a-zA-Z0-9_-]/', '_', $nom);
            $qrPath = __DIR__ . '/../QRCodes/' . $safeName . '.png';
            if (file_exists($qrPath)) {
                unlink($qrPath); // Détruit le fichier physiquement
            }

            $message = "Énigme '$nom' et son QR Code supprimés.";
        }
    }
    // --- SUPPRIMER TOUTES LES ÉNIGMES ---
    elseif ($action === 'delete_all_enigmes') {
        $count = 0;
        // On parcourt toutes les clés du fichier datas.txt
        foreach (array_keys($datas) as $key) {
            // Si la clé n'est PAS un réglage de configuration, c'est une énigme : on la supprime
            if (!in_array($key, $RESERVED_KEYS)) {
                unset($datas[$key]);

                // ---> SUPPRESSION DU QR CODE ASSOCIÉ <---
                // CORRECTION : On remonte d'un cran (../) car actions.php est dans core/
                $safeName = preg_replace('/[^a-zA-Z0-9_-]/', '_', $key);
                $qrPath = __DIR__ . '/../QRCodes/' . $safeName . '.png';
                if (file_exists($qrPath)) {
                    unlink($qrPath); // Détruit le fichier physiquement
                }

                $count++;
            }
        }
        $message = "Toutes les énigmes ($count au total) et leurs QR Codes ont été supprimées.";
    }
    // --- VIDER INTÉGRALEMENT UN FICHIER TXT ---
    elseif ($action === 'reset_file') {
        $fileType = $_POST['file_type'] ?? ''; // 'datas' ou 'received'
        $pwd = $_POST['password'] ?? '';

        // Tente de récupérer le mot de passe du .htaccess via les variables du serveur PHP
        $adminPassword = $_SERVER['PHP_AUTH_PW'] ?? '';

        // Si vide (fréquent sur serveur de production FastCGI), on va fouiller dans les en-têtes HTTP bruts
        if ($adminPassword === '') {
            $authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? $_SERVER['REDIRECT_HTTP_AUTHORIZATION'] ?? '';
            if (!$authHeader && function_exists('apache_request_headers')) {
                $headers = apache_request_headers();
                $authHeader = $headers['Authorization'] ?? $headers['authorization'] ?? '';
            }
            // Décryptage de l'en-tête "Basic Base64"
            if ($authHeader && preg_match('/Basic\s+(.*)$/i', $authHeader, $matches)) {
                $decoded = base64_decode($matches[1]);
                if (strpos($decoded, ':') !== false) {
                    list($authUser, $authPw) = explode(':', $decoded, 2);
                    $adminPassword = $authPw;
                }
            }
        }

        // Vérifie si le mot de passe tapé par l'utilisateur correspond à celui trouvé dans le serveur
        if ($adminPassword === '' || $pwd !== $adminPassword) {
            $message = "❌ Mot de passe incorrect. Le fichier n'a pas été vidé.";
        } else {
            // Création d'un JSON vide et cryptage
            $emptyData = json_encode([], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
            $enc = encryptData($emptyData, $ENCRYPT_KEY, $ENCRYPT_IV);

            // Écrasement du fichier demandé
            if ($fileType === 'datas') {
                file_put_contents($datasFile, $enc, LOCK_EX);

                // ---> SUPPRESSION DE TOUS LES QR CODES PHYSIQUES <---
                // CORRECTION : On remonte d'un cran (../) car actions.php est dans core/
                $qrDir = __DIR__ . '/../QRCodes';
                if (is_dir($qrDir)) {
                    $files = glob($qrDir . '/*.png'); // Trouve tous les PNG
                    foreach ($files as $file) {
                        if (is_file($file)) {
                            unlink($file); // Supprime le fichier
                        }
                    }
                }

                $message = "✅ Le fichier datas.txt et les QR Codes ont été entièrement vidés !";
            } elseif ($fileType === 'received') {
                // CORRECTION : On pointe bien vers le nouveau dossier data/
                file_put_contents('data/received.txt', $enc, LOCK_EX);
                $message = "✅ Le fichier received.txt a été entièrement vidé !";
            } else {
                $message = "Erreur : Type de fichier invalide.";
            }
        }

        // Redirection immédiate pour éviter le renvoi du formulaire au rafraîchissement
        header("Location: index.php?tab=$activeTab&msg=" . urlencode($message));
        exit;
    }
    // --- METTRE À JOUR LES MESSAGES DE RÉSULTATS ---
    elseif ($action === 'update_messages') {
        // Enregistre les textes (HTML autorisé)
        $datas['messages'] = [
            'bonne_reponse' => $_POST['msg_bonne_reponse'] ?? '',
            'mauvaise_reponse' => $_POST['msg_mauvaise_reponse'] ?? '',
            'deja_repondu' => $_POST['msg_deja_repondu'] ?? ''
        ];
        // Enregistre les couleurs de la page de résultats
        $datas['theme_messages'] = $_POST['theme_messages'] ?? [];
        $message = "Messages et couleurs mis à jour.";
    }

    // ========================================================================
    // SAUVEGARDE FINALE DE DATAS.TXT
    // ========================================================================
    // Si l'action exécutée fait partie de cette liste blanche, on chiffre et on sauvegarde $datas.
    if (in_array($action, ['add_enigme','update_enigme','delete_enigme','delete_all_enigmes','update_theme','update_options','update_messages', 'reset_file'])) {
        // Trie le tableau par ordre alphabétique naturel (Enigme 1, Enigme 2, Enigme 10...)
        ksort($datas, SORT_NATURAL | SORT_FLAG_CASE);
        // Conversion en JSON lisible
        $plaintext = json_encode($datas, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        // Cryptage
        $enc = encryptData($plaintext, $ENCRYPT_KEY, $ENCRYPT_IV);
        // Écriture sécurisée (LOCK_EX empêche qu'un autre processus lise le fichier pendant l'écriture)
        file_put_contents($datasFile, $enc, LOCK_EX);

        // Redirection (empêche le comportement F5 = renvoi du formulaire POST)
        header("Location: index.php?tab=$activeTab&msg=" . urlencode($message));
        exit;
    }
}
?>