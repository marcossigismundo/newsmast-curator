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
    <p class="nc-page-description"><?php _e('Configure a integração com Mastodon e o formato das publicações', 'newsmast-curator'); ?></p>
</div>

<!-- Mastodon Connection -->
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
                <span class="nc-form-help"><?php _e('URL completa da instância Mastodon (ex: https://mastodon.social)', 'newsmast-curator'); ?></span>
            </div>

            <div class="nc-form-group">
                <label class="nc-form-label"><?php _e('Token de Acesso', 'newsmast-curator'); ?></label>
                <input type="password" id="nc-setting-token" class="nc-form-control"
                       placeholder="<?php echo get_option('nc_mastodon_token', '') ? '••••••••••••••••' : 'Seu token de acesso'; ?>">
                <span class="nc-form-help"><?php _e('Obtenha em: Sua Instância → Configurações → Desenvolvimento → Novo Aplicativo', 'newsmast-curator'); ?></span>
            </div>

            <div id="nc-mastodon-status"></div>
        </form>
    </div>
</div>

<!-- Post Format -->
<div class="nc-card">
    <div class="nc-card-header">
        <h2 class="nc-card-title">
            <span class="dashicons dashicons-edit"></span>
            <?php _e('Formato de Posts', 'newsmast-curator'); ?>
        </h2>
    </div>
    <div class="nc-card-body">
        <!-- Template Variables Reference -->
        <div class="nc-form-group">
            <label class="nc-form-label"><?php _e('Variáveis Disponíveis', 'newsmast-curator'); ?></label>
            <div class="nc-template-vars">
                <div class="nc-template-var">
                    <code>{title}</code>
                    <span><?php _e('Título do item coletado da fonte', 'newsmast-curator'); ?></span>
                </div>
                <div class="nc-template-var">
                    <code>{excerpt}</code>
                    <span><?php _e('Resumo ou trecho do conteúdo (auto-gerado se vazio)', 'newsmast-curator'); ?></span>
                </div>
                <div class="nc-template-var">
                    <code>{url}</code>
                    <span><?php _e('Link original do item na fonte de conteúdo', 'newsmast-curator'); ?></span>
                </div>
                <div class="nc-template-var">
                    <code>{hashtags}</code>
                    <span><?php _e('Hashtags padrão definidas abaixo', 'newsmast-curator'); ?></span>
                </div>
            </div>
        </div>

        <div class="nc-form-group">
            <label class="nc-form-label"><?php _e('Template Padrão', 'newsmast-curator'); ?></label>
            <textarea id="nc-setting-template" class="nc-form-control" rows="5"
                      oninput="NC.updateTemplatePreview()"><?php echo esc_textarea(get_option('nc_post_template', "{title}\n\n{excerpt}\n\n{url}\n\n{hashtags}")); ?></textarea>
            <span class="nc-form-help"><?php _e('Este template será usado como base ao agendar publicações. Você pode editar o conteúdo individualmente ao agendar.', 'newsmast-curator'); ?></span>
        </div>

        <div class="nc-form-group">
            <label class="nc-form-label"><?php _e('Hashtags Padrão', 'newsmast-curator'); ?></label>
            <input type="text" id="nc-setting-hashtags" class="nc-form-control"
                   value="<?php echo esc_attr(get_option('nc_default_hashtags', '#museus #patrimônio #cultura')); ?>"
                   oninput="NC.updateTemplatePreview()"
                   placeholder="#museus #patrimônio #cultura">
            <span class="nc-form-help"><?php _e('Estas hashtags substituem a variável {hashtags} no template', 'newsmast-curator'); ?></span>
        </div>

        <!-- Live Preview -->
        <div class="nc-form-group">
            <label class="nc-form-label"><?php _e('Preview (exemplo com dados fictícios)', 'newsmast-curator'); ?></label>
            <div class="nc-schedule-preview">
                <div class="nc-schedule-preview-header">
                    <span class="dashicons dashicons-admin-site-alt3"></span>
                    <strong>Mastodon Post</strong>
                </div>
                <p id="nc-template-preview-text"></p>
                <div class="nc-char-counter" style="margin-top:10px;">
                    <span id="nc-template-preview-count">0</span> / 500 caracteres
                </div>
            </div>
        </div>
    </div>
</div>

<script>
jQuery(document).ready(function() {
    NC.updateTemplatePreview();
});
</script>
