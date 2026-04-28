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
                    "eyeBall" => "ball15"
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
            if ($qrImage && empty($curlError) && strpos($qrImage, 'PNG') !== false) {
                $qrDir = __DIR__ . '/../QRCodes';
                if (!is_dir($qrDir)) {
                    mkdir($qrDir, 0777, true);
                }
                $safeName = preg_replace('/[^a-zA-Z0-9_-]/', '_', $nom);
                file_put_contents($qrDir . '/' . $safeName . '.png', $qrImage);
                $message = "Nouvelle énigme '$nom' ajoutée et QR Code généré.";
            } else {
                $message = "Nouvelle énigme '$nom' ajoutée, MAIS erreur QR Code : " . ($curlError ? $curlError : "Mauvaise réponse de l'API");
            }
        }
    }
    // --- METTRE À JOUR LES COULEURS DES ÉNIGMES ---
    elseif ($action === 'update_theme') {
        $theme = $_POST['theme_enigmes'] ?? [];
        if (!empty($theme)) {
            $datas['theme_enigmes'] = $theme;
            if (isset($datas['theme'])) unset($datas['theme']); 
        }
        $message = "Thème mis à jour avec succès.";
    }
    // --- METTRE À JOUR LES OPTIONS GLOBALES DU JEU ---
    elseif ($action === 'update_options') {
        $datas['options']['une_seule_tentative'] = isset($_POST['une_seule_tentative']) ? true : false;
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

            if (strpos($raw_reponses, ';') !== false) {
                $array_reponses = array_map('trim', explode(';', $raw_reponses));
                $array_reponses = array_filter($array_reponses, function($val) { return $val !== ''; }); 
                $datas[$nom]['reponse_correcte'] = array_values($array_reponses);
            } else {
                $datas[$nom]['reponse_correcte'] = $raw_reponses;
            }

            $datas[$nom]['texte'] = $texte;

            if (!isset($datas[$nom]['messages_uniques'])) {
                $datas[$nom]['messages_uniques'] = [];
            }

            if (isset($_POST['use_unique_bonne'])) {
                $datas[$nom]['messages_uniques']['bonne_reponse'] = $_POST['unique_bonne_reponse'] ?? '';
            } else {
                unset($datas[$nom]['messages_uniques']['bonne_reponse']);
            }

            if (isset($_POST['use_unique_mauvaise'])) {
                $datas[$nom]['messages_uniques']['mauvaise_reponse'] = $_POST['unique_mauvaise_reponse'] ?? '';
            } else {
                unset($datas[$nom]['messages_uniques']['mauvaise_reponse']);
            }

            if (isset($_POST['use_unique_deja'])) {
                $datas[$nom]['messages_uniques']['deja_repondu'] = $_POST['unique_deja_repondu'] ?? '';
            } else {
                unset($datas[$nom]['messages_uniques']['deja_repondu']);
            }

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
            unset($datas[$nom]); 
            $safeName = preg_replace('/[^a-zA-Z0-9_-]/', '_', $nom);
            $qrPath = __DIR__ . '/../QRCodes/' . $safeName . '.png';
            if (file_exists($qrPath)) {
                unlink($qrPath); 
            }
            $message = "Énigme '$nom' et son QR Code supprimés.";
        }
    }
    // --- SUPPRIMER TOUTES LES ÉNIGMES ---
    elseif ($action === 'delete_all_enigmes') {
        $count = 0;
        foreach (array_keys($datas) as $key) {
            if (!in_array($key, $RESERVED_KEYS)) {
                unset($datas[$key]);
                $safeName = preg_replace('/[^a-zA-Z0-9_-]/', '_', $key);
                $qrPath = __DIR__ . '/../QRCodes/' . $safeName . '.png';
                if (file_exists($qrPath)) {
                    unlink($qrPath); 
                }
                $count++;
            }
        }
        $message = "Toutes les énigmes ($count au total) et leurs QR Codes ont été supprimées.";
    }
    // --- VIDER INTÉGRALEMENT UN FICHIER TXT ---
    elseif ($action === 'reset_file') {
        $fileType = $_POST['file_type'] ?? ''; 
        $pwd = $_POST['password'] ?? '';

        $adminPassword = $_SERVER['PHP_AUTH_PW'] ?? '';

        if ($adminPassword === '') {
            $authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? $_SERVER['REDIRECT_HTTP_AUTHORIZATION'] ?? '';
            if (!$authHeader && function_exists('apache_request_headers')) {
                $headers = apache_request_headers();
                $authHeader = $headers['Authorization'] ?? $headers['authorization'] ?? '';
            }
            if ($authHeader && preg_match('/Basic\\s+(.*)$/i', $authHeader, $matches)) {
                $decoded = base64_decode($matches[1]);
                if (strpos($decoded, ':') !== false) {
                    list($authUser, $authPw) = explode(':', $decoded, 2);
                    $adminPassword = $authPw;
                }
            }
        }

        if ($adminPassword === '' || $pwd !== $adminPassword) {
            $message = "❌ Mot de passe incorrect. Le fichier n'a pas été vidé.";
        } else {
            $emptyData = json_encode([], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
            $enc = encryptData($emptyData, $ENCRYPT_KEY, $ENCRYPT_IV);

            if ($fileType === 'datas') {
                file_put_contents($datasFile, $enc, LOCK_EX);
                $qrDir = __DIR__ . '/../QRCodes';
                if (is_dir($qrDir)) {
                    $files = glob($qrDir . '/*.png'); 
                    foreach ($files as $file) {
                        if (is_file($file)) {
                            unlink($file); 
                        }
                    }
                }
                $message = "✅ Le fichier datas.txt et les QR Codes ont été entièrement vidés !";
            } elseif ($fileType === 'received') {
                file_put_contents('data/received.txt', $enc, LOCK_EX);
                $message = "✅ Le fichier received.txt a été entièrement vidé !";
            } else {
                $message = "Erreur : Type de fichier invalide.";
            }
        }
        header("Location: index.php?tab=$activeTab&msg=" . urlencode($message));
        exit;
    }
    // --- METTRE À JOUR LES MESSAGES DE RÉSULTATS ---
    elseif ($action === 'update_messages') {
        $datas['messages'] = [
            'bonne_reponse' => $_POST['msg_bonne_reponse'] ?? '',
            'mauvaise_reponse' => $_POST['msg_mauvaise_reponse'] ?? '',
            'deja_repondu' => $_POST['msg_deja_repondu'] ?? ''
        ];
        $datas['theme_messages'] = $_POST['theme_messages'] ?? [];
        $message = "Messages et couleurs mis à jour.";
    }


    // ========================================================================
    // 🆕 GESTION DES PROFILS (Sauvegarder, Charger, Supprimer, Dupliquer, Renommer)
    // ========================================================================

    // 1. Sauvegarder l'état actuel (Créer un nouveau profil)
    elseif ($action === 'save_profile') {
        $profile_name = trim($_POST['profile_name'] ?? '');
        if ($profile_name) {
            $profilesFile = 'data/profiles.txt';
            $profiles = [];
            if (file_exists($profilesFile)) {
                $profiles = json_decode(decryptData(file_get_contents($profilesFile), $ENCRYPT_KEY, $ENCRYPT_IV), true) ?: [];
            }

            // Récupération des bases de données actuelles
            $current_datas = file_exists('data/datas.txt') ? json_decode(decryptData(file_get_contents('data/datas.txt'), $ENCRYPT_KEY, $ENCRYPT_IV), true) : [];
            $current_received = file_exists('data/received.txt') ? json_decode(decryptData(file_get_contents('data/received.txt'), $ENCRYPT_KEY, $ENCRYPT_IV), true) : [];

            // Sauvegarde des QR Codes en Base64
            $qr_codes_backup = [];
            $qrDir = __DIR__ . '/../QRCodes';
            if (is_dir($qrDir)) {
                $files = glob($qrDir . '/*.png');
                foreach ($files as $file) {
                    $filename = basename($file);
                    $qr_codes_backup[$filename] = base64_encode(file_get_contents($file));
                }
            }

            $profile_id = uniqid('prof_');

            // --- NOUVEAU : On mémorise que ce nouveau profil devient le profil actif ---
            if (!isset($current_datas['options'])) $current_datas['options'] = [];
            $current_datas['options']['current_profile_id'] = $profile_id;
            $current_datas['options']['current_profile_name'] = $profile_name;
            // NOUVEAU : on s'assure qu'un nouveau profil n'a pas l'alerte d'emblée
            unset($current_datas['options']['unsaved_profile_changes']);

            // On sauvegarde tout de suite dans datas.txt pour actualiser l'interface
            $enc_current = encryptData(json_encode($current_datas, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE), $ENCRYPT_KEY, $ENCRYPT_IV);
            file_put_contents('data/datas.txt', $enc_current, LOCK_EX);

            // Stockage global du profil dans profiles.txt
            $profiles[$profile_id] = [
                'name' => $profile_name,
                'date' => date('d/m/Y H:i:s'),
                'datas' => $current_datas,
                'received' => $current_received,
                'qrcodes' => $qr_codes_backup
            ];

            $enc = encryptData(json_encode($profiles, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE), $ENCRYPT_KEY, $ENCRYPT_IV);
            file_put_contents($profilesFile, $enc, LOCK_EX);

            header("Location: index.php?tab=$activeTab&msg=" . urlencode("Le profil et ses QR Codes ont été sauvegardés."));
            exit;
        }
    }

    // 2. Charger un profil existant
    elseif ($action === 'load_profile') {
        $profile_id = $_POST['profile_id'] ?? '';
        $profilesFile = 'data/profiles.txt';

        if ($profile_id && file_exists($profilesFile)) {
            $profiles = json_decode(decryptData(file_get_contents($profilesFile), $ENCRYPT_KEY, $ENCRYPT_IV), true) ?: [];

            if (isset($profiles[$profile_id])) {
                $p = $profiles[$profile_id];

                // --- NOUVEAU : On mémorise l'identité du profil qu'on vient de charger ---
                if (!isset($p['datas']['options'])) $p['datas']['options'] = [];
                $p['datas']['options']['current_profile_id'] = $profile_id;
                $p['datas']['options']['current_profile_name'] = $p['name'];
                // NOUVEAU : on s'assure qu'un profil chargé n'a pas l'alerte d'emblée
                unset($p['datas']['options']['unsaved_profile_changes']);

                // Restauration des fichiers textes
                $enc_datas = encryptData(json_encode($p['datas'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE), $ENCRYPT_KEY, $ENCRYPT_IV);
                file_put_contents('data/datas.txt', $enc_datas, LOCK_EX);

                $enc_received = encryptData(json_encode($p['received'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE), $ENCRYPT_KEY, $ENCRYPT_IV);
                file_put_contents('data/received.txt', $enc_received, LOCK_EX);

                // Restauration des QR Codes
                $qrDir = __DIR__ . '/../QRCodes';

                if (is_dir($qrDir)) {
                    $files = glob($qrDir . '/*.png');
                    foreach ($files as $file) {
                        unlink($file);
                    }
                } else {
                    mkdir($qrDir, 0777, true);
                }

                if (isset($p['qrcodes']) && is_array($p['qrcodes'])) {
                    foreach ($p['qrcodes'] as $filename => $base64_data) {
                        file_put_contents($qrDir . '/' . $filename, base64_decode($base64_data));
                    }
                }

                header("Location: index.php?tab=$activeTab&msg=" . urlencode("Profil chargé ! Données et QR Codes restaurés."));
                exit;
            }
        }
    }

    // 3. Mettre à jour un profil existant (Écrase sa sauvegarde)
    elseif ($action === 'update_profile') {
        $profile_id = $_POST['profile_id'] ?? '';
        $profilesFile = 'data/profiles.txt';

        if ($profile_id && file_exists($profilesFile)) {
            $profiles = json_decode(decryptData(file_get_contents($profilesFile), $ENCRYPT_KEY, $ENCRYPT_IV), true) ?: [];

            if (isset($profiles[$profile_id])) {
                $current_datas = file_exists('data/datas.txt') ? json_decode(decryptData(file_get_contents('data/datas.txt'), $ENCRYPT_KEY, $ENCRYPT_IV), true) : [];
                $current_received = file_exists('data/received.txt') ? json_decode(decryptData(file_get_contents('data/received.txt'), $ENCRYPT_KEY, $ENCRYPT_IV), true) : [];

                $qr_codes_backup = [];
                $qrDir = __DIR__ . '/../QRCodes';
                if (is_dir($qrDir)) {
                    $files = glob($qrDir . '/*.png');
                    foreach ($files as $file) {
                        $filename = basename($file);
                        $qr_codes_backup[$filename] = base64_encode(file_get_contents($file));
                    }
                }

                // --- NOUVEAU : On s'assure que le profil actif reste bien enregistré ---
                if (!isset($current_datas['options'])) $current_datas['options'] = [];
                $current_datas['options']['current_profile_id'] = $profile_id;
                $current_datas['options']['current_profile_name'] = $profiles[$profile_id]['name'];

                // NOUVEAU : On efface le marqueur car le profil est de nouveau à jour
                unset($current_datas['options']['unsaved_profile_changes']);

                // Sauvegarde silencieuse dans datas.txt pour retirer la potentielle alerte "non-sauvegardé"
                $enc_current = encryptData(json_encode($current_datas, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE), $ENCRYPT_KEY, $ENCRYPT_IV);
                file_put_contents('data/datas.txt', $enc_current, LOCK_EX);

                $profiles[$profile_id]['date'] = date('d/m/Y H:i:s') . ' (mis à jour)';
                $profiles[$profile_id]['datas'] = $current_datas;
                $profiles[$profile_id]['received'] = $current_received;
                $profiles[$profile_id]['qrcodes'] = $qr_codes_backup;

                $enc = encryptData(json_encode($profiles, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE), $ENCRYPT_KEY, $ENCRYPT_IV);
                file_put_contents($profilesFile, $enc, LOCK_EX);

                header("Location: index.php?tab=$activeTab&msg=" . urlencode("La sauvegarde du profil a été mise à jour avec vos modifications."));
                exit;
            }
        }
    }

    // 4. Supprimer un profil
    elseif ($action === 'delete_profile') {
        $profile_id = $_POST['profile_id'] ?? '';
        $profilesFile = 'data/profiles.txt';

        if ($profile_id && file_exists($profilesFile)) {
            $profiles = json_decode(decryptData(file_get_contents($profilesFile), $ENCRYPT_KEY, $ENCRYPT_IV), true) ?: [];

            if (isset($profiles[$profile_id])) {
                unset($profiles[$profile_id]); 

                $enc = encryptData(json_encode($profiles, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE), $ENCRYPT_KEY, $ENCRYPT_IV);
                file_put_contents($profilesFile, $enc, LOCK_EX);

                // --- NOUVEAU : Si on supprime le profil actuellement utilisé, on le "désactive" de la mémoire ---
                $current_datas = file_exists('data/datas.txt') ? json_decode(decryptData(file_get_contents('data/datas.txt'), $ENCRYPT_KEY, $ENCRYPT_IV), true) : [];
                if (isset($current_datas['options']['current_profile_id']) && $current_datas['options']['current_profile_id'] === $profile_id) {
                    unset($current_datas['options']['current_profile_id']);
                    unset($current_datas['options']['current_profile_name']);
                    unset($current_datas['options']['unsaved_profile_changes']); // On nettoie tout
                    $enc_current = encryptData(json_encode($current_datas, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE), $ENCRYPT_KEY, $ENCRYPT_IV);
                    file_put_contents('data/datas.txt', $enc_current, LOCK_EX);
                }

                header("Location: index.php?tab=$activeTab&msg=" . urlencode("Profil supprimé avec succès."));
                exit;
            }
        }
    }

    // 5. NOUVEAU : Dupliquer un profil
    elseif ($action === 'duplicate_profile') {
        $profile_id = $_POST['profile_id'] ?? '';
        $profilesFile = 'data/profiles.txt';

        if ($profile_id && file_exists($profilesFile)) {
            $profiles = json_decode(decryptData(file_get_contents($profilesFile), $ENCRYPT_KEY, $ENCRYPT_IV), true) ?: [];

            if (isset($profiles[$profile_id])) {
                $newId = uniqid('prof_');
                $profiles[$newId] = $profiles[$profile_id]; // Copie complète
                $profiles[$newId]['name'] = $profiles[$profile_id]['name'] . ' (Copie)';
                $profiles[$newId]['date'] = date('d/m/Y H:i:s');

                $enc = encryptData(json_encode($profiles, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE), $ENCRYPT_KEY, $ENCRYPT_IV);
                file_put_contents($profilesFile, $enc, LOCK_EX);

                header("Location: index.php?tab=$activeTab&msg=" . urlencode("Profil dupliqué avec succès."));
                exit;
            }
        }
    }

    // 6. NOUVEAU : Renommer un profil
    elseif ($action === 'rename_profile') {
        $profile_id = $_POST['profile_id'] ?? '';
        $new_name = trim(strip_tags($_POST['new_name'] ?? ''));
        $profilesFile = 'data/profiles.txt';

        if ($profile_id && $new_name && file_exists($profilesFile)) {
            $profiles = json_decode(decryptData(file_get_contents($profilesFile), $ENCRYPT_KEY, $ENCRYPT_IV), true) ?: [];

            if (isset($profiles[$profile_id])) {
                $profiles[$profile_id]['name'] = $new_name;

                $enc = encryptData(json_encode($profiles, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE), $ENCRYPT_KEY, $ENCRYPT_IV);
                file_put_contents($profilesFile, $enc, LOCK_EX);

                // --- Si c'est le profil actuellement actif, on actualise aussi le bandeau ---
                $current_datas = file_exists('data/datas.txt') ? json_decode(decryptData(file_get_contents('data/datas.txt'), $ENCRYPT_KEY, $ENCRYPT_IV), true) : [];
                if (isset($current_datas['options']['current_profile_id']) && $current_datas['options']['current_profile_id'] === $profile_id) {
                    $current_datas['options']['current_profile_name'] = $new_name;
                    $enc_current = encryptData(json_encode($current_datas, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE), $ENCRYPT_KEY, $ENCRYPT_IV);
                    file_put_contents('data/datas.txt', $enc_current, LOCK_EX);
                }

                header("Location: index.php?tab=$activeTab&msg=" . urlencode("Le profil a été renommé en '$new_name'."));
                exit;
            }
        }
    }


    // ========================================================================
    // SAUVEGARDE FINALE DE DATAS.TXT (Ne s'applique qu'aux actions basiques)
    // ========================================================================
    if (in_array($action, ['add_enigme','update_enigme','delete_enigme','delete_all_enigmes','update_theme','update_options','update_messages', 'reset_file'])) {
        
        // --- NOUVEAU : On indique à datas.txt que le profil n'est plus à jour ---
        if (isset($datas['options']['current_profile_id'])) {
            $datas['options']['unsaved_profile_changes'] = true;
        }

        ksort($datas, SORT_NATURAL | SORT_FLAG_CASE);
        $plaintext = json_encode($datas, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        $enc = encryptData($plaintext, $ENCRYPT_KEY, $ENCRYPT_IV);
        file_put_contents($datasFile, $enc, LOCK_EX);

        // Si la requête demandait aussi une mise à jour du profil (via le bouton du bandeau)
        if (!empty($_POST['also_update_profile'])) {
            $profile_id = $_POST['also_update_profile'];
            $profilesFile = 'data/profiles.txt';
            if (file_exists($profilesFile)) {
                $profiles = json_decode(decryptData(file_get_contents($profilesFile), $ENCRYPT_KEY, $ENCRYPT_IV), true) ?: [];
                if (isset($profiles[$profile_id])) {
                    // On recharge les données toutes fraîches qu'on vient d'enregistrer
                    $current_datas = json_decode(decryptData(file_get_contents($datasFile), $ENCRYPT_KEY, $ENCRYPT_IV), true);
                    $current_received = file_exists('data/received.txt') ? json_decode(decryptData(file_get_contents('data/received.txt'), $ENCRYPT_KEY, $ENCRYPT_IV), true) : [];

                    $qr_codes_backup = [];
                    $qrDir = __DIR__ . '/../QRCodes';
                    if (is_dir($qrDir)) {
                        $files = glob($qrDir . '/*.png');
                        foreach ($files as $file) { $qr_codes_backup[basename($file)] = base64_encode(file_get_contents($file)); }
                    }

                    // NOUVEAU : On efface le marqueur car le profil est de nouveau à jour
                    unset($current_datas['options']['unsaved_profile_changes']);

                    // On ré-enregistre datas.txt avec le marqueur effacé
                    $enc_clean = encryptData(json_encode($current_datas, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE), $ENCRYPT_KEY, $ENCRYPT_IV);
                    file_put_contents($datasFile, $enc_clean, LOCK_EX);

                    $profiles[$profile_id]['date'] = date('d/m/Y H:i:s') . ' (mis à jour)';
                    $profiles[$profile_id]['datas'] = $current_datas;
                    $profiles[$profile_id]['received'] = $current_received;
                    $profiles[$profile_id]['qrcodes'] = $qr_codes_backup;

                    $enc_prof = encryptData(json_encode($profiles, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE), $ENCRYPT_KEY, $ENCRYPT_IV);
                    file_put_contents($profilesFile, $enc_prof, LOCK_EX);

                    $message .= " (Et le profil a été mis à jour avec succès !)";
                }
            }
        }

        header("Location: index.php?tab=$activeTab&msg=" . urlencode($message));
        exit;
    }
}
?>