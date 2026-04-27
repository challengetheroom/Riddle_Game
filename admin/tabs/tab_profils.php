<!-- ========================================================== -->
<!-- ONGLET : GESTION DES PROFILS                               -->
<!-- ========================================================== -->
<?php
// Lecture du fichier des profils existants
$profilesFile = 'data/profiles.txt';
$profiles = [];
if (file_exists($profilesFile)) {
    $dec_profiles = decryptData(file_get_contents($profilesFile), $ENCRYPT_KEY, $ENCRYPT_IV);
    $profiles = json_decode($dec_profiles, true) ?: [];
}
?>

<div class="tab-content <?php echo ($activeTab === 'profils') ? 'active' : ''; ?>" id="profils">
    <div class="section" style="padding: 20px;">
        <h3 style="margin-top: 0;">💾 Gestion des Profils (Sauvegardes)</h3>
        <p style="color: #555; margin-bottom: 20px;">
            Un profil est une "photographie" complète de votre jeu actuel. Il sauvegarde vos énigmes, vos réglages, vos couleurs, et les résultats des joueurs.<br>
            Vous pouvez sauvegarder l'état actuel pour le mettre de côté, et charger un ancien profil pour le réactiver.
        </p>

        <!-- Formulaire de création de sauvegarde -->
        <form method="POST" style="background: #e8f0ff; padding: 20px; border-radius: 8px; border: 1px solid #b5ceEE; margin-bottom: 30px;">
            <input type="hidden" name="action" value="save_profile">
            <h4 style="margin-top: 0; color: #1a4f9b;">Créer une nouvelle sauvegarde</h4>
            <label style="display: block; margin-bottom: 10px;">
                Nom du profil (ex: "Jeu de Pâques 2026") :<br>
                <input type="text" name="profile_name" required style="width: 100%; max-width: 400px;">
            </label>
            <button type="submit" style="background: #28a745;">💾 Sauvegarder l'état actuel du jeu</button>
        </form>

        <!-- Liste des profils sauvegardés -->
        <h4 style="margin-top: 0;">Profils sauvegardés</h4>

        <?php if (empty($profiles)): ?>
        <p style="font-style: italic; color: #888;">Aucun profil sauvegardé pour le moment.</p>
        <?php else: ?>
        <table class="dataTable" style="width: 100%; text-align: left; border-collapse: collapse;">
            <thead>
                <tr>
                    <th style="padding: 10px; border-bottom: 2px solid #ccc;">Nom du profil</th>
                    <th style="padding: 10px; border-bottom: 2px solid #ccc;">Date de sauvegarde</th>
                    <th style="padding: 10px; border-bottom: 2px solid #ccc;">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($profiles as $id => $p): ?>
                <tr style="border-bottom: 1px solid #eee;">
                    <td style="padding: 10px;"><b><?php echo htmlspecialchars($p['name']); ?></b></td>
                    <td style="padding: 10px;"><?php echo $p['date']; ?></td>
                    <td style="padding: 10px;">
                        <!-- Bouton Charger -->
                        <form method="POST" style="display: inline-block; margin: 0; padding: 0; background: none; box-shadow: none; border: none;">
                            <input type="hidden" name="action" value="load_profile">
                            <input type="hidden" name="profile_id" value="<?php echo $id; ?>">
                            <button type="submit" style="background: #007BFF; padding: 6px 12px; font-size: 13px;" onclick="return confirm('⚠️ ATTENTION ! Charger ce profil va écraser et remplacer toutes vos énigmes et résultats actuels.\\n\\nAvez-vous bien sauvegardé votre jeu actuel avant ?');">📂 Charger</button>
                        </form>

                        <!-- NOUVEAU : Bouton Mettre à jour -->
                        <form method="POST" style="display: inline-block; margin: 0; margin-left: 5px; padding: 0; background: none; box-shadow: none; border: none;">
                            <input type="hidden" name="action" value="update_profile">
                            <input type="hidden" name="profile_id" value="<?php echo $id; ?>">
                            <button type="submit" style="background: #ffc107; color: #333; padding: 6px 12px; font-size: 13px;" onclick="return confirm('Écraser ce profil avec l\'état actuel du jeu ?');">🔄 Mettre à jour</button>
                        </form>

                        <!-- Bouton Supprimer -->
                        <form method="POST" style="display: inline-block; margin: 0; margin-left: 5px; padding: 0; background: none; box-shadow: none; border: none;">
                            <input type="hidden" name="action" value="delete_profile">
                            <input type="hidden" name="profile_id" value="<?php echo $id; ?>">
                            <button type="submit" style="background: #dc3545; padding: 6px 12px; font-size: 13px;" onclick="return confirm('Supprimer définitivement ce profil ? Cette action est irréversible.');">🗑️</button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </div>
</div>