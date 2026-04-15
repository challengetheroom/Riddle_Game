<!-- ========================================================== -->
<!-- ONGLET 4 : AFFICHAGE DES DONNÉES BRUTES (DEBUG/SÉCURITÉ)   -->
<!-- ========================================================== -->
<div class="tab-content <?php echo ($activeTab === 'datas') ? 'active' : ''; ?>" id="datas">
    <div class="donnees-box">

        <!-- Fichier de configuration -->
        <div class="donnees-left">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px;">
                <h2 style="margin: 0;">datas.txt</h2>
                <!-- Bouton pour vider intégralement le fichier avec sécurité (JS) -->
                <button type="button" onclick="confirmReset('datas')" style="background: #dc3545; color: white; border: none; padding: 6px 12px; border-radius: 4px; cursor: pointer; font-weight: bold;">
                    ⚠️ Vider datas
                </button>
            </div>
            <div class="donnees-pre">
                <?php echo nl2br(safePrint(getDatasContent())); ?>
            </div>
        </div>

        <!-- Fichier des joueurs -->
        <div class="donnees-right">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px;">
                <h2 style="margin: 0;">received.txt</h2>
                <button type="button" onclick="confirmReset('received')" style="background: #dc3545; color: white; border: none; padding: 6px 12px; border-radius: 4px; cursor: pointer; font-weight: bold;">
                    ⚠️ Vider received
                </button>
            </div>
            <div class="donnees-pre">
                <?php echo nl2br(safePrint(getReceivedContent())); ?>
            </div>
        </div>
    </div>
</div>