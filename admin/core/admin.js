// Fonction qui s'active quand on clique sur une miniature
function openQrPopup(imgSrc, enigmeName) {
    // Change la source de l'image dans la popup
    document.getElementById('qr-modal-img').src = imgSrc;
    // Modifie le titre
    document.getElementById('qr-modal-title').innerText = 'QR Code : ' + enigmeName;
    // Prépare le lien de téléchargement avec un joli nom de fichier
    const downloadBtn = document.getElementById('qr-modal-download');
    downloadBtn.href = imgSrc;
    downloadBtn.download = 'QRCode_' + enigmeName.replace(/[^a-zA-Z0-9]/g, '_') + '.png';
    // Affiche la popup
    document.getElementById('qr-modal').style.display = 'flex';
}

$(document).ready(function(){

    // 1. GESTION DES ONGLETS
    // Alterne l'affichage des onglets en modifiant les classes 'active'
    $('.tab').click(function(){
        $('.tab').removeClass('active');
        $(this).addClass('active');
        $('.tab-content').removeClass('active');
        const tabName = $(this).data('tab');
        $('#' + tabName).addClass('active');

        // Modifie l'URL sans recharger la page pour mémoriser l'onglet actif (utile pour la touche F5)
        const url = new URL(window.location);
        url.searchParams.set('tab', tabName);
        url.searchParams.delete('msg'); // Enlève le message de succès de l'URL
        window.history.replaceState({}, '', url);
    });

    // 2. INITIALISATION DATATABLES (Onglet 1)
    $('#table-results').DataTable({ pageLength:10, lengthMenu:[5,10,20,50], order:[] });

    // 3. NETTOYAGE URL
    // Enlève visuellement le paramètre '?msg=xxx' de la barre d'adresse
    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.has('msg')) {
        setTimeout(function() {
            const url = new URL(window.location);
            url.searchParams.delete('msg');
            window.history.replaceState({}, '', url);
        }, 100);
    }

    // 4. SAUVEGARDE ÉTAT DU PANNEAU COULEURS
    // Vérifie dans la mémoire du navigateur (localStorage) si l'admin avait replié le panneau
    const isCollapsed = localStorage.getItem('theme-panel-collapsed') === 'true';
    if (isCollapsed) {
        $('#theme-panel').hide();
        $('#toggle-theme').text('▲');
    } else {
        $('#theme-panel').show();
        $('#toggle-theme').text('▼');
    }

    // 5. SAUVEGARDE ÉTAT DU PANNEAU GLOBAL
    const isGlobalCollapsed = localStorage.getItem('global-panel-collapsed') === 'true';
    if (isGlobalCollapsed) {
        $('#global-panel').hide();
        $('#toggle-global').text('▲');
    } else {
        $('#global-panel').show();
        $('#toggle-global').text('▼');
    }

    // Initialisation visuelle
    updatePreview();

    // 6. SYNCHRONISATION COLOR-PICKER <-> CHAMP TEXTE HEXA (Onglet 2)

    // Quand on clique sur la couleur, ça met à jour le texte
    $('input[type="color"]').on('input', function() {
        $(this).next('.color-hex').val($(this).val().toUpperCase());
        updatePreview();
    });

    // Quand on tape du texte (ex: #FFFFFF), ça met à jour le sélecteur de couleur
    $('.color-hex').on('input', function() {
        const hexValue = $(this).val().toUpperCase();
        $(this).val(hexValue); // Force majuscule

        // Vérifie via Regex si c'est un format Hexadécimal valide (# + 6 caractères)
        if (/^#[0-9A-F]{6}$/i.test(hexValue)) {
            $(this).prev('input[type="color"]').val(hexValue);
            updatePreview();
        }
    });

    // Force l'affichage en majuscule au chargement
    $('input[type="color"]').each(function() {
        $(this).next('.color-hex').val($(this).val().toUpperCase());
    });

    // 7. GESTION DU HOVER SUR LE BOUTON D'APERÇU (Onglet 2)
    $('#preview-button').hover(
        function() {
            const hoverColor = $('input[name="theme_enigmes[button_hover]"]').val();
            $(this).css('background', hoverColor);
        },
        function() {
            const normalColor = $('input[name="theme_enigmes[button_bg]"]').val();
            $(this).css('background', normalColor);
        }
    );
});

// 8. ANIMATION DU PLIAGE/DÉPLIAGE DU PANNEAU COULEURS
$('#toggle-theme').click(function() {
    const isVisible = $('#theme-panel').is(':visible');

    if (isVisible) {
        $('#theme-panel').slideUp(300); // Animation vers le haut
        $(this).text('▲');
        localStorage.setItem('theme-panel-collapsed', 'true');
    } else {
        $('#theme-panel').slideDown(300); // Animation vers le bas
        $(this).text('▼');
        localStorage.setItem('theme-panel-collapsed', 'false');
    }
});

// 9. ANIMATION DU PLIAGE/DÉPLIAGE DU PANNEAU GLOBAL
$('#toggle-global').click(function() {
    const isVisible = $('#global-panel').is(':visible');

    if (isVisible) {
        $('#global-panel').slideUp(300); // Plie
        $(this).text('▲');
        localStorage.setItem('global-panel-collapsed', 'true');
    } else {
        $('#global-panel').slideDown(300); // Déplie
        $(this).text('▼');
        localStorage.setItem('global-panel-collapsed', 'false');
    }
});

// 10. FONCTION DE MISE À JOUR DE L'APERÇU DES ÉNIGMES (Onglet 2)
function updatePreview() {
    // Récupère toutes les valeurs actuelles des sélecteurs
    const background = $('input[name="theme_enigmes[background]"]').val();
    const containerBg = $('input[name="theme_enigmes[container_bg]"]').val();
    const borderColor = $('input[name="theme_enigmes[border_color]"]').val();
    const titleColor = $('input[name="theme_enigmes[title_color]"]').val();
    const textColor = $('input[name="theme_enigmes[text_color]"]').val();
    const formBg = $('input[name="theme_enigmes[form_bg]"]').val();
    const buttonBg = $('input[name="theme_enigmes[button_bg]"]').val();

    // Applique les styles CSS en direct
    $('#preview').css('background', background);
    $('#preview-container').css({
        'background': containerBg,
        'border-color': borderColor,
        'color': textColor
    });
    $('#preview-title').css('color', titleColor);
    $('#preview-text').css('color', textColor);
    $('#preview-form-box').css('background', formBg);
    $('#preview-input').css('border-color', borderColor);
    $('#preview-button').css('background', buttonBg);
}

// 11. SÉCURITÉ DE SUPPRESSION (Onglet 4)
// Demande une double vérification (Dialogue + Mot de passe) avant de formater un fichier JSON
function confirmReset(fileType) {
    // 1ère étape : Boîte de dialogue JS classique
    if (confirm("⚠️ ATTENTION : Voulez-vous vraiment vider totalement le fichier " + fileType + ".txt ?\n\nToutes les données seront perdues. Cette action est IRRÉVERSIBLE.")) {

        // 2ème étape : Fenêtre de saisie (Prompt)
        let pwd = prompt("Veuillez entrer le mot de passe administrateur pour confirmer la suppression de " + fileType + ".txt :");

        if (pwd !== null && pwd.trim() !== "") {
            // Génère un formulaire caché en HTML pour poster les données vers PHP
            let form = $('<form>', {
                method: 'POST',
                action: 'index.php'
            });
            form.append($('<input>', { type: 'hidden', name: 'action', value: 'reset_file' }));
            form.append($('<input>', { type: 'hidden', name: 'file_type', value: fileType }));
            form.append($('<input>', { type: 'hidden', name: 'password', value: pwd }));
            form.append($('<input>', { type: 'hidden', name: 'active_tab', value: 'datas' }));

            // Ajoute le formulaire au corps de la page et l'envoie automatiquement
            $('body').append(form);
            form.submit();
        } else if (pwd !== null) {
            alert("Mot de passe vide, action annulée.");
        }
    }
}

// 12. FONCTION DE MISE À JOUR DE L'APERÇU DES MESSAGES (Onglet 3)
function updateMsgPreview() {
    const bg = $('input[name="theme_messages[background]"]').val();
    const container = $('input[name="theme_messages[container_bg]"]').val();
    const border = $('input[name="theme_messages[border_color]"]').val();
    const title = $('input[name="theme_messages[title_color]"]').val();
    const text = $('input[name="theme_messages[text_color]"]').val();

    // Applique l'arrière-plan global
    $('#preview-msg-bg').css('background', bg);

    // Applique le style aux 3 blocs de messages
    $('.preview-msg-container').css({
        'background': container,
        'border-color': border,
        'color': text
    });

    // Copie le texte (HTML) tapé dans les <textarea> vers les blocs d'aperçu
    $('#preview-bonne-reponse').html($('textarea[name="msg_bonne_reponse"]').val());
    $('#preview-mauvaise-reponse').html($('textarea[name="msg_mauvaise_reponse"]').val());
    $('#preview-deja-repondu').html($('textarea[name="msg_deja_repondu"]').val());

    // Colorise le texte standard
    $('.preview-msg-content').css('color', text);
    // Cible spécifiquement les balises de titre pour leur donner la bonne couleur
    $('.preview-msg-content').find('h1, h2, h3, h4, h5, h6').css('color', title);
}

// 13. ÉCOUTEURS D'ÉVÉNEMENTS POUR L'ONGLET 3 (MESSAGES)
// Relie les champs de couleur, les champs hexa et les zones de texte à la fonction d'aperçu
$('.msg-color').on('input', function() {
    $(this).next('.msg-hex').val($(this).val().toUpperCase());
    updateMsgPreview();
});
$('.msg-hex').on('input', function() {
    const hexValue = $(this).val().toUpperCase();
    $(this).val(hexValue);
    if (/^#[0-9A-F]{6}$/i.test(hexValue)) {
        $(this).prev('input[type="color"]').val(hexValue);
        updateMsgPreview();
    }
});

$('.msg-input').on('input', function() {
    updateMsgPreview();
});

// Lancement initial de l'aperçu
updateMsgPreview();