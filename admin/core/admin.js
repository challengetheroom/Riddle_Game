// ============================================================================
// FONCTIONS GLOBALES (Accessibles depuis le HTML via onclick)
// ============================================================================

// Fonction qui s'active quand on clique sur une miniature (Onglet 2)
function openQrPopup(imgSrc, enigmeName) {
    document.getElementById('qr-modal-img').src = imgSrc;
    document.getElementById('qr-modal-title').innerText = 'QR Code : ' + enigmeName;

    const downloadBtn = document.getElementById('qr-modal-download');
    downloadBtn.href = imgSrc;
    downloadBtn.download = 'QRCode_' + enigmeName.replace(/[^a-zA-Z0-9]/g, '_') + '.png';

    document.getElementById('qr-modal').style.display = 'flex';
}

// Sécurité de suppression (Onglet 4)
function confirmReset(fileType) {
    if (confirm("⚠️ ATTENTION : Voulez-vous vraiment vider totalement le fichier " + fileType + ".txt ?\\n\\nToutes les données seront perdues. Cette action est IRRÉVERSIBLE.")) {
        let pwd = prompt("Veuillez entrer le mot de passe administrateur pour confirmer la suppression de " + fileType + ".txt :");

        if (pwd !== null && pwd.trim() !== "") {
            let form = $('<form>', { method: 'POST', action: 'index.php' });
            form.append($('<input>', { type: 'hidden', name: 'action', value: 'reset_file' }));
            form.append($('<input>', { type: 'hidden', name: 'file_type', value: fileType }));
            form.append($('<input>', { type: 'hidden', name: 'password', value: pwd }));
            form.append($('<input>', { type: 'hidden', name: 'active_tab', value: 'datas' }));

            $('body').append(form);
            form.submit();
        } else if (pwd !== null) {
            alert("Mot de passe vide, action annulée.");
        }
    }
}

// Fonction pour renommer un profil (Onglet Profils)
function renameProfile(id, currentName) {
    let newName = prompt("Entrez le nouveau nom pour ce profil :", currentName);
    if (newName && newName.trim() !== "" && newName !== currentName) {
        let form = $('<form>', { method: 'POST', action: 'index.php', style: 'display:none;' });
        form.append($('<input>', { type: 'hidden', name: 'action', value: 'rename_profile' }));
        form.append($('<input>', { type: 'hidden', name: 'profile_id', value: id }));
        form.append($('<input>', { type: 'hidden', name: 'new_name', value: newName }));
        $('body').append(form);
        form.submit();
    }
}

// Fonction de mise à jour de l'aperçu des énigmes (Onglet 2)
function updatePreview() {
    const background = $('input[name="theme_enigmes[background]"]').val() || '#f9f9f9';
    const containerBgHex = $('input[name="theme_enigmes[container_bg]"]').val() || '#b5ceEE';
    const borderColor = $('input[name="theme_enigmes[border_color]"]').val() || '#2b7cff';
    const titleColor = $('input[name="theme_enigmes[title_color]"]').val() || '#1a4f9b';
    const textColor = $('input[name="theme_enigmes[text_color]"]').val() || '#333';
    const formBg = $('input[name="theme_enigmes[form_bg]"]').val() || '#e8f0ff';
    const buttonBg = $('input[name="theme_enigmes[button_bg]"]').val() || '#2b7cff';

    // Récupère les nouveaux réglages de l'image de fond
    const bgImageData = $('#bg_image_data').val();
    const bgOpacity = $('#bg_opacity').val() || '100';
    const bgScale = $('#bg_scale').val() || '100';
    const bgPosX = $('#bg_pos_x').val() || '50';
    const bgPosY = $('#bg_pos_y').val() || '50';

    $('#preview').css('background', background);
    $('#preview-title').css('color', titleColor);
    $('#preview-text').css('color', textColor);
    $('#preview-form-box').css('background', formBg);
    $('#preview-input').css('border-color', borderColor);
    $('#preview-button').css('background', buttonBg);

    // --- LE MIXAGE (BLEND) ---
    // Conversion de la couleur Hex en RGBA
    let r = parseInt(containerBgHex.slice(1, 3), 16) || 255;
    let g = parseInt(containerBgHex.slice(3, 5), 16) || 255;
    let b = parseInt(containerBgHex.slice(5, 7), 16) || 255;
    let cssOpacity = bgOpacity / 100;
    let rgbaColor = `rgba(${r}, ${g}, ${b}, ${cssOpacity})`;

    if (bgImageData) {
        $('#preview-container').css({
            'background-image': `linear-gradient(${rgbaColor}, ${rgbaColor}), url('${bgImageData}')`,
            'background-size': `${bgScale}%`,
            'background-position': `${bgPosX}% ${bgPosY}%`,
            'background-repeat': 'no-repeat',
            'background-color': 'transparent', // Annule le fond solide
            'border-color': borderColor,
            'color': textColor
        });
    } else {
        $('#preview-container').css({
            'background-image': 'none',
            'background-color': rgbaColor,
            'border-color': borderColor,
            'color': textColor
        });
    }
}

// Fonction de mise à jour de l'aperçu des messages (Onglet 3)
function updateMsgPreview() {
    const bg = $('input[name="theme_messages[background]"]').val();
    const container = $('input[name="theme_messages[container_bg]"]').val();
    const border = $('input[name="theme_messages[border_color]"]').val();
    const title = $('input[name="theme_messages[title_color]"]').val();
    const text = $('input[name="theme_messages[text_color]"]').val();

    $('#preview-msg-bg').css('background', bg);
    $('.preview-msg-container').css({ 'background': container, 'border-color': border, 'color': text });

    $('#preview-bonne-reponse').html($('textarea[name="msg_bonne_reponse"]').val());
    $('#preview-mauvaise-reponse').html($('textarea[name="msg_mauvaise_reponse"]').val());
    $('#preview-deja-repondu').html($('textarea[name="msg_deja_repondu"]').val());

    $('.preview-msg-content').css('color', text);
    $('.preview-msg-content').find('h1, h2, h3, h4, h5, h6').css('color', title);
}


// ============================================================================
// INITIALISATION AU CHARGEMENT DE LA PAGE (DOM Ready)
// ============================================================================
$(document).ready(function(){

    // 1. GESTION DES ONGLETS
    $('.tab').click(function(){
        $('.tab').removeClass('active');
        $(this).addClass('active');
        $('.tab-content').removeClass('active');
        const tabName = $(this).data('tab');
        $('#' + tabName).addClass('active');

        const url = new URL(window.location);
        url.searchParams.set('tab', tabName);
        url.searchParams.delete('msg');
        window.history.replaceState({}, '', url);
    });

    // 2. INITIALISATION DATATABLES
    $('#table-results').DataTable({ pageLength:10, lengthMenu:[5,10,20,50], order:[] });

    // 3. NETTOYAGE URL (?msg=)
    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.has('msg')) {
        setTimeout(function() {
            const url = new URL(window.location);
            url.searchParams.delete('msg');
            window.history.replaceState({}, '', url);
        }, 100);
    }

    // 4. ANIMATION & SAUVEGARDE ÉTAT DU PANNEAU COULEURS
    const isCollapsed = localStorage.getItem('theme-panel-collapsed') === 'true';
    if (isCollapsed) {
        $('#theme-panel').hide();
        $('#toggle-theme').text('▲');
    } else {
        $('#theme-panel').show();
        $('#toggle-theme').text('▼');
    }

    $('#toggle-theme').click(function() {
        const isVisible = $('#theme-panel').is(':visible');
        if (isVisible) {
            $('#theme-panel').slideUp(300);
            $(this).text('▲');
            localStorage.setItem('theme-panel-collapsed', 'true');
        } else {
            $('#theme-panel').slideDown(300);
            $(this).text('▼');
            localStorage.setItem('theme-panel-collapsed', 'false');
        }
    });

    // 5. ANIMATION & SAUVEGARDE ÉTAT DU PANNEAU GLOBAL
    const isGlobalCollapsed = localStorage.getItem('global-panel-collapsed') === 'true';
    if (isGlobalCollapsed) {
        $('#global-panel').hide();
        $('#toggle-global').text('▲');
    } else {
        $('#global-panel').show();
        $('#toggle-global').text('▼');
    }

    $('#toggle-global').click(function() {
        const isVisible = $('#global-panel').is(':visible');
        if (isVisible) {
            $('#global-panel').slideUp(300);
            $(this).text('▲');
            localStorage.setItem('global-panel-collapsed', 'true');
        } else {
            $('#global-panel').slideDown(300);
            $(this).text('▼');
            localStorage.setItem('global-panel-collapsed', 'false');
        }
    });

    // 6. SYNCHRONISATION COLOR-PICKER <-> CHAMP TEXTE HEXA
    $('input[type="color"]').on('input', function() {
        $(this).next('.color-hex, .msg-hex').val($(this).val().toUpperCase());
        updatePreview();
        updateMsgPreview();
    });

    $('.color-hex, .msg-hex').on('input', function() {
        const hexValue = $(this).val().toUpperCase();
        $(this).val(hexValue);
        if (/^#[0-9A-F]{6}$/i.test(hexValue)) {
            $(this).prev('input[type="color"]').val(hexValue);
            updatePreview();
            updateMsgPreview();
        }
    });

    $('input[type="color"]').each(function() {
        $(this).next('.color-hex, .msg-hex').val($(this).val().toUpperCase());
    });

    // 7. GESTION DU HOVER SUR LE BOUTON D'APERÇU
    $('#preview-button').hover(
        function() { $(this).css('background', $('input[name="theme_enigmes[button_hover]"]').val()); },
        function() { $(this).css('background', $('input[name="theme_enigmes[button_bg]"]').val()); }
    );

    // 8. ÉCOUTEURS DES MESSAGES (Onglet 3)
    $('.msg-input').on('input', function() {
        updateMsgPreview();
    });

    // ========================================================================
    // 🆕 GESTION DE L'IMAGE DE FOND ET COMPRESSION
    // ========================================================================
    $('#btn_upload_bg').click(function() {
        $('#bg_image_upload').click();
    });

    $('#bg_image_upload').change(function(e) {
        const file = e.target.files[0];
        if (!file) return;

        $('#bg_image_status').text('Compression... ⏳').css('color', '#ff9900');

        const reader = new FileReader();
        reader.onload = function(event) {
            const img = new Image();
            img.onload = function() {
                const canvas = document.createElement('canvas');
                const ctx = canvas.getContext('2d');
                const MAX_SIZE = 800;
                let width = img.width;
                let height = img.height;

                if (width > height && width > MAX_SIZE) {
                    height *= MAX_SIZE / width;
                    width = MAX_SIZE;
                } else if (height > MAX_SIZE) {
                    width *= MAX_SIZE / height;
                    height = MAX_SIZE;
                }

                canvas.width = width;
                canvas.height = height;
                ctx.drawImage(img, 0, 0, width, height);

                const compressedBase64 = canvas.toDataURL('image/webp', 0.7);
                $('#bg_image_data').val(compressedBase64);

                $('#bg_image_status').text('Image chargée ✓').css('color', '#28a745');
                $('#btn_remove_bg').show();
                updatePreview();
            };
            img.src = event.target.result;
        };
        reader.readAsDataURL(file);
    });

    $('#btn_remove_bg').click(function() {
        $('#bg_image_data').val('');
        $('#bg_image_upload').val('');
        $('#bg_image_status').text('Aucune image').css('color', '#666');
        $(this).hide();
        updatePreview();
    });

    // ========================================================================
    // 🆕 DRAG-TO-CHANGE (Cliquer-Glisser sur les valeurs)
    // ========================================================================
    let isDragging = false;
    let startX = 0;
    let startValue = 0;
    let currentInput = null;

    $('.drag-input').mousedown(function(e) {
        // --- CORRECTION POUR LES FLÈCHES (SPINNERS) ---
        // On vérifie si l'utilisateur clique à l'extrême droite du champ (là où sont les flèches natives)
        // Généralement, les flèches font environ 20px de large.
        const inputWidth = $(this).outerWidth();
        const clickPositionX = e.offsetX; // Position du clic par rapport au bord gauche de l'input

        // Si on clique dans les 25 derniers pixels à droite, c'est sûrement sur les flèches : on laisse le navigateur faire son job normal !
        if (clickPositionX > inputWidth - 25) {
            return; // On annule notre script Drag-to-Change pour ce clic
        }
        // ----------------------------------------------

        isDragging = true;
        startX = e.pageX;
        currentInput = $(this);
        startValue = parseFloat(currentInput.val()) || 0;
        $('body').css('cursor', 'ew-resize');

        // Empêche la sélection de texte pendant le glissement
        e.preventDefault();
    });

    $(document).mousemove(function(e) {
        if (!isDragging || !currentInput) return;

        const diff = e.pageX - startX;

        // Ajustement de la sensibilité (moins sensible pour que ce soit plus précis)
        const sensitivity = 0.5; 

        // On n'utilise plus le step pour le drag pour que ce soit fluide, 
        // mais on garde les min/max pour les limites
        let newValue = startValue + (diff * sensitivity);
        const min = parseFloat(currentInput.attr('min'));
        const max = parseFloat(currentInput.attr('max'));

        if (!isNaN(min) && newValue < min) newValue = min;
        if (!isNaN(max) && newValue > max) newValue = max;

        // On arrondit à l'entier le plus proche pour avoir des valeurs propres
        currentInput.val(Math.round(newValue));

        // Force le déclenchement de l'événement 'input' pour alerter le système de sécurité
        currentInput.trigger('input');

        updatePreview();
    });

    $(document).mouseup(function() {
        if (isDragging) {
            isDragging = false;
            currentInput = null;
            $('body').css('cursor', 'default');
        }
    });

    // Capture aussi les changements faits à la main (clavier ou clics sur les flèches)
    $('.drag-input').on('input', function() {
        updatePreview();
    });

    // ========================================================================
    // 🆕 GESTION DU BOUTON RESET DES PARAMÈTRES D'IMAGE DE FOND
    // ========================================================================
    $('#reset-bg-settings').click(function() {
        // Rétablit les valeurs par défaut
        $('#bg_opacity').val(100); 
        $('#bg_scale').val(100);   
        $('#bg_pos_x').val(50);    
        $('#bg_pos_y').val(50);    

        // Met à jour l'aperçu en direct
        updatePreview(); 
    });

    // Lancement visuel initial
    updatePreview();
    updateMsgPreview();

    // ========================================================================
    // 🆕 SÉCURITÉ : ALERTE SI MODIFICATIONS NON SAUVEGARDÉES (F5 / Quitter)
    // ========================================================================
    let isDirty = false; // Variable qui retient si on a touché à quelque chose

    // 1. On écoute TOUS les champs de TOUS les formulaires du panneau d'administration
    $('input, textarea, select').on('input change', function() {
        // Exceptions : on ignore les clics sur les boutons des onglets, les champs cachés, et la case d'alerte
        const isTabButton = $(this).closest('.tabs').length > 0;
        const isHidden = $(this).attr('type') === 'hidden';
        const isWarnCheckbox = $(this).attr('id') === 'warn_unsaved';

        if (!isTabButton && !isHidden && !isWarnCheckbox) {
            if (!isDirty) {
                isDirty = true;

                // NOUVEAU : Met à jour l'apparence du bandeau du profil
                $('#display-profile-name').css('font-weight', 'bold');
                $('#unsaved-asterisk').show();
                $('#btn-quick-update').show();
            }
        }
    });

    // 2. Si on clique sur un vrai bouton de sauvegarde (form submit), on annule l'alerte ET on force l'onglet actif
    $('form').on('submit', function() {
        isDirty = false;

        // On récupère le nom de l'onglet visuellement actif
        const currentTab = $('.tab.active').data('tab') || 'resultats';

        // On injecte ou met à jour ce nom dans le formulaire qui est en train d'être envoyé
        if ($(this).find('input[name="active_tab"]').length === 0) {
            $(this).append('<input type="hidden" name="active_tab" value="' + currentTab + '">');
        } else {
            $(this).find('input[name="active_tab"]').val(currentTab);
        }
    });

    // 3. Action du bouton "Mettre à jour" du bandeau
    $('#btn-quick-update').click(function() {
        if (!confirm("⚠️ Êtes-vous sûr de vouloir mettre à jour la sauvegarde de ce profil avec TOUTES les modifications actuelles de la page ?")) {
            return false;
        }

        const profileId = $(this).data('profile-id');
        const currentTab = $('.tab.active').data('tab') || 'resultats';

        // On récupère le formulaire actif sur l'onglet actuel (s'il y en a un qui contient des champs de saisie)
        const activeForm = $('#' + currentTab).find('form').filter(function() {
            // On cherche le formulaire principal de l'onglet (pas les petits formulaires de suppression)
            return $(this).find('input[type="text"], input[type="color"], input[type="number"], textarea, select').length > 0;
        }).first();

        // Si on a trouvé un formulaire avec des modifications, on modifie son action pour faire d'une pierre deux coups !
        if (activeForm.length > 0) {
            // On ajoute un champ caché pour dire au serveur de faire AUSSI une mise à jour du profil après avoir enregistré les données
            if (activeForm.find('input[name="also_update_profile"]').length === 0) {
                activeForm.append('<input type="hidden" name="also_update_profile" value="' + profileId + '">');
            }
            // On désactive l'alerte de sécurité et on soumet LE formulaire de la page
            isDirty = false;
            activeForm.submit();
        } else {
            // S'il n'y a pas de formulaire à enregistrer sur cet onglet, on fait juste la mise à jour classique du profil
            let form = $('<form>', { method: 'POST', action: 'index.php', style: 'display:none;' });
            form.append($('<input>', { type: 'hidden', name: 'action', value: 'update_profile' }));
            form.append($('<input>', { type: 'hidden', name: 'profile_id', value: profileId }));
            form.append($('<input>', { type: 'hidden', name: 'active_tab', value: currentTab }));
            $('body').append(form);
            isDirty = false;
            form.submit();
        }
    });

    // 4. Gestion de l'état de la case à cocher (mémorisé dans le navigateur)
    const warnEnabled = localStorage.getItem('warn_unsaved_changes') !== 'false'; // true par défaut
    $('#warn_unsaved').prop('checked', warnEnabled);

    $('#warn_unsaved').change(function() {
        localStorage.setItem('warn_unsaved_changes', $(this).is(':checked'));
    });

    // 4. L'événement natif du navigateur pour bloquer le rechargement
    window.addEventListener('beforeunload', function(e) {
        const isWarnActive = $('#warn_unsaved').is(':checked');

        if (isDirty && isWarnActive) {
            // Ces deux lignes sont requises pour forcer Chrome/Firefox/Edge à afficher la popup
            e.preventDefault(); 
            e.returnValue = ''; 
        }
    });

}); // Fin du $(document).ready