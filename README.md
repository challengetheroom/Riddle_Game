# 🧩 Jeu d'Énigmes par QR Code (Riddle Game)

Une application web PHP légère et sécurisée permettant de créer des jeux de piste et des énigmes via des QR Codes. Les joueurs scannent un code, atterrissent sur une page personnalisée, et tentent de répondre à l'énigme. L'administrateur peut gérer les énigmes, personnaliser le design et exporter les résultats.

---

## ✨ Fonctionnalités Principales

- **Génération par Token :** Chaque énigme possède un identifiant unique (URL sécurisée) empêchant la triche.
- **QR Codes Automatiques :** Création et sauvegarde automatique des images QR Codes (via l'API QRCode-Monkey) directement dans l'interface administrateur.
- **Stockage Sécurisé :** Pas de base de données (MySQL). Les données sont stockées dans des fichiers plats (`.txt`) **intégralement chiffrés en AES-256**.
- **Personnalisation poussée :** Interface admin permettant de changer les couleurs globales. Les messages de victoire/défaite/déjà répondu peuvent être configurés globalement, ou **spécifiquement pour chaque énigme**.
- **Export Excel :** Génération d'un fichier `.xlsx` des résultats avec coloration automatique des bonnes réponses.
- **Sécurité Admin :** Espace protégé par `.htaccess` et `.htpasswd` (Authentification HTTP Basic).

---

## 📂 Architecture du Projet (MVC simplifié)

```text
/ (Racine publique - Côté Joueur)
├── index.php                 # Page de l'énigme (affiche la question selon le token)
├── save.php                  # Script de traitement de la réponse du joueur
├── style.css                 # Feuille de style globale (nettoyée et responsive)
├── /vendor/                  # Dossier Composer (Généré automatiquement, contient PhpSpreadsheet)
│
└── /admin/                   # (Espace Administrateur - Protégé)
    ├── index.php             # Chef d'orchestre (Contrôleur principal de l'interface)
    ├── /core/                # Moteur de l'application (actions.php, config.php, export.php, etc.)
    ├── /data/                # Bases de données chiffrées (datas.txt et received.txt)
    ├── /onglets/             # Vues HTML incluses (tab_edition.php, tab_resultats.php, etc.)
    ├── /QRCodes/             # Images PNG des QR Codes générés automatiquement
    ├── .htaccess             # Règle Apache bloquant l'accès à l'admin
    ├── .htpasswd             # Fichier contenant les mots de passe admin hachés
    │
    └── /tools/               # Outils de développement et déploiement
        ├── generate_htaccess.php # Script de génération dynamique du .htaccess
        └── edit_raw_data.php     # Éditeur JSON brut (accessible uniquement en DEV)
```

---

## ⚙️ Prérequis

- Serveur Web (Apache recommandé pour la prise en charge native du `.htaccess`).
- PHP 7.4 ou supérieur (Extensions requises : `openssl`, `zip`, `xml`, `gd`, `curl`).
- **Composer** (pour l'installation de la bibliothèque d'export Excel).

---

## 🚀 Installation & Déploiement

### 1. Installation en Local (Développement)
1. Clonez le dépôt dans votre dossier serveur (ex: `C:/wamp64/www/Riddle_Game/`).
2. Ouvrez un terminal dans le dossier du projet et installez les dépendances :
   ```bash
   composer install
   ```
   *(Si Composer n'est pas initialisé, lancez : `composer require phpoffice/phpspreadsheet`)*
3. Modifiez si besoin les clés de sécurité dans `admin/core/config.php`.

### 2. Déploiement en Production (En ligne)
Le déploiement requiert une attention particulière à cause du `.htaccess` qui utilise des chemins absolus (différents entre votre PC et le serveur).

1. Envoyez tous les fichiers sur votre serveur (y compris le dossier `vendor`).
2. Visitez la page : `https://votresite.com/chemin/admin/tools/generate_htaccess.php`.
3. Cliquez sur le bouton **"Générer admin/.htaccess"**. Le script détectera l'environnement (`prod`) et écrira le bon chemin absolu vers le `.htpasswd`.
4. **⚠️ TRÈS IMPORTANT :** Une fois le fichier généré, supprimez ou renommez le dossier `/tools/` pour éviter qu'un visiteur ne regénère le fichier à votre insu.

---

## 🔒 Sécurité et Cryptographie

### Les clés de chiffrement
Le fichier `admin/core/config.php` contient les constantes `$ENCRYPT_KEY` et `$ENCRYPT_IV`. 
- **Ne perdez jamais ces clés !** Si elles sont modifiées ou perdues, les fichiers du dossier `data/` deviendront définitivement illisibles.
- Ne transmettez jamais ce fichier publiquement.

### Vidage des fichiers en production (Problème FastCGI)
Si vous êtes sur un hébergement mutualisé utilisant PHP en mode *CGI/FastCGI*, le serveur peut refuser de transmettre le mot de passe de l'admin à PHP. Pour que le bouton "Vider les données" fonctionne, le fichier `.htaccess` généré inclut automatiquement la directive suivante :
```apache
SetEnvIf Authorization "(.*)" HTTP_AUTHORIZATION=$1
```

---

## 📖 Guide d'utilisation (Pour l'administrateur)

1. **Créer une énigme :** Allez dans l'onglet *Édition des énigmes*, tapez un nom (ex: `Enigme_Cuisine`) et validez.
2. **Récupérer le QR Code :** Le système génère instantanément l'image du QR Code unique. Cliquez sur la miniature dans la grille pour l'agrandir et la télécharger.
3. **Configurer les réponses :** Ajoutez le texte de l'énigme et la/les bonne(s) réponse(s). Pour accepter plusieurs réponses, séparez-les par un point-virgule (ex: `Velo; Bicyclette`). La vérification ignore les majuscules et les accents.
4. **Messages spécifiques (Optionnel) :** Par défaut, les joueurs voient les messages configurés dans l'onglet *Édition des messages*. Vous pouvez cocher "UNIQUE" sous une énigme pour lui attribuer un message de victoire ou de défaite sur-mesure.
5. **Option "Une seule tentative" :** Dans l'onglet d'édition, vous pouvez cocher la case interdisant au joueur de réessayer s'il se trompe.
6. **Exporter :** Dans l'onglet *Résultats*, cliquez sur le bouton d'exportation pour télécharger le tableau Excel. Les joueurs ayant trouvé la bonne réponse seront surlignés en vert.