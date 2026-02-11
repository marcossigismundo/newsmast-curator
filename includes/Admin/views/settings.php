<div class="nc-page-header">
    <div class="nc-page-title">
        <h1>
            <span class="dashicons dashicons-admin-settings"></span>
            <?php _e('Configurações', 'newsmast-curator'); ?>
        </h1>
        <button class="nc-button nc-button-primary" onclick="NC.saveSettings()">
            <span class="dashicons dashicons-saved"></span>
            <?php _e('Salvar Alterações', 'newsmast-curator'); ?>
        </button>
    </div>
    <p class="nc-page-description"><?php _e('Configure a integração com Mastodon', 'newsmast-curator'); ?></p>
</div>

<div class="nc-card">
    <div class="nc-card-header">
        <h2 class="nc-card-title">
            <span class="dashicons dashicons-share-alt2"></span>
            <?php _e('Conexão Mastodon', 'newsmast-curator'); ?>
        </h2>
        <button class="nc-button nc-button-secondary" onclick="NC.testMastodon()">
            <span class="dashicons dashicons-update"></span>
            <?php _e('Testar Conexão', 'newsmast-curator'); ?>
        </button>
    </div>
    <div class="nc-card-body">
        <form id="nc-settings-form">
            <div class="nc-form-group">
                <label class="nc-form-label"><?php _e('Instância Mastodon', 'newsmast-curator'); ?></label>
                <input type="url" id="nc-setting-instance" class="nc-form-control"
                       value="<?php echo esc_attr(get_option('nc_mastodon_instance', '')); ?>"
                       placeholder="https://masto.donte.com.br">
                <span class="nc-form-help"><?php _e('URL completa da instância Mastodon', 'newsmast-curator'); ?></span>
            </div>

            <div class="nc-form-group">
                <label class="nc-form-label"><?php _e('Token de Acesso', 'newsmast-curator'); ?></label>
                <input type="password" id="nc-setting-token" class="nc-form-control"
                       placeholder="Seu token de acesso">
                <span class="nc-form-help"><?php _e('Token de API do Mastodon (obtenha em Configurações > Desenvolvimento)', 'newsmast-curator'); ?></span>
            </div>

            <div id="nc-mastodon-status"></div>
        </form>
    </div>
</div>

<div class="nc-card">
    <div class="nc-card-header">
        <h2 class="nc-card-title">
            <span class="dashicons dashicons-edit"></span>
            <?php _e('Formato de Posts', 'newsmast-curator'); ?>
        </h2>
    </div>
    <div class="nc-card-body">
        <div class="nc-form-group">
            <label class="nc-form-label"><?php _e('Template Padrão', 'newsmast-curator'); ?></label>
            <textarea id="nc-setting-template" class="nc-form-control" rows="5"><?php echo esc_textarea(get_option('nc_post_template', "{title}\n\n{excerpt}\n\n{url}\n\n{hashtags}")); ?></textarea>
            <span class="nc-form-help"><?php _e('Use: {title}, {excerpt}, {url}, {hashtags}', 'newsmast-curator'); ?></span>
        </div>

        <div class="nc-form-group">
            <label class="nc-form-label"><?php _e('Hashtags Padrão', 'newsmast-curator'); ?></label>
            <input type="text" id="nc-setting-hashtags" class="nc-form-control"
                   value="<?php echo esc_attr(get_option('nc_default_hashtags', '#museus #patrimônio #cultura')); ?>">
        </div>
    </div>
</div>
