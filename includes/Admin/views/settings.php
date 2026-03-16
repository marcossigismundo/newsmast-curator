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
    <p class="nc-page-description"><?php _e('Configure as contas Mastodon, formato das publicações e API pública', 'newsmast-curator'); ?></p>
</div>

<!-- Mastodon Accounts (Multi-Account) -->
<div class="nc-card">
    <div class="nc-card-header">
        <h2 class="nc-card-title">
            <span class="dashicons dashicons-share-alt2"></span>
            <?php _e('Contas Mastodon', 'newsmast-curator'); ?>
        </h2>
        <button class="nc-button nc-button-primary" onclick="NC.showAddAccountModal()">
            <span class="dashicons dashicons-plus-alt"></span>
            <?php _e('Nova Conta', 'newsmast-curator'); ?>
        </button>
    </div>
    <div class="nc-card-body">
        <p style="color:var(--nc-text-light);margin:0 0 15px;">
            <?php _e('Configure múltiplas contas Mastodon para publicar conteúdo em diferentes instâncias. A conta padrão será usada quando nenhuma for selecionada ao agendar.', 'newsmast-curator'); ?>
        </p>

        <?php
        $legacy_instance = get_option('nc_mastodon_instance', '');
        $legacy_token = get_option('nc_mastodon_token', '');
        if (!empty($legacy_instance) && !empty($legacy_token)):
        ?>
        <div class="nc-notice nc-notice-warning" id="nc-legacy-mastodon-notice" style="margin-bottom:15px;">
            <span class="dashicons dashicons-warning"></span>
            <span>
                <?php printf(
                    __('Configuração legada detectada (%s). Clique para migrar para o novo sistema de contas.', 'newsmast-curator'),
                    '<strong>' . esc_html($legacy_instance) . '</strong>'
                ); ?>
            </span>
            <button class="nc-button nc-button-secondary" style="margin-left:10px;" onclick="NC.migrateLegacyMastodon()">
                <span class="dashicons dashicons-migrate"></span>
                <?php _e('Migrar', 'newsmast-curator'); ?>
            </button>
        </div>
        <?php endif; ?>

        <div id="nc-mastodon-accounts-list">
            <div class="nc-loading">
                <div class="nc-spinner"></div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Adicionar/Editar Conta Mastodon -->
<div class="nc-modal-overlay" id="nc-account-modal">
    <div class="nc-modal" style="max-width:600px;">
        <div class="nc-modal-header">
            <h2 class="nc-modal-title" id="nc-account-modal-title"><?php _e('Nova Conta Mastodon', 'newsmast-curator'); ?></h2>
            <button class="nc-modal-close" onclick="NC.closeModal('nc-account-modal')">
                <span class="dashicons dashicons-no-alt"></span>
            </button>
        </div>
        <div class="nc-modal-body">
            <form id="nc-account-form">
                <input type="hidden" id="nc-account-id">
                <div class="nc-form-group">
                    <label class="nc-form-label"><?php _e('Nome da Conta', 'newsmast-curator'); ?> *</label>
                    <input type="text" id="nc-account-name" class="nc-form-control" required
                           placeholder="<?php _e('Ex: IBRAM Oficial, Museus do Brasil...', 'newsmast-curator'); ?>">
                    <span class="nc-form-help"><?php _e('Nome descritivo para identificar esta conta', 'newsmast-curator'); ?></span>
                </div>
                <div class="nc-form-group">
                    <label class="nc-form-label"><?php _e('URL da Instância', 'newsmast-curator'); ?> *</label>
                    <input type="url" id="nc-account-instance" class="nc-form-control" required
                           placeholder="https://mastodon.social">
                    <span class="nc-form-help"><?php _e('URL completa da instância Mastodon (ex: https://mastodon.social)', 'newsmast-curator'); ?></span>
                </div>
                <div class="nc-form-group">
                    <label class="nc-form-label"><?php _e('Token de Acesso', 'newsmast-curator'); ?> *</label>
                    <input type="password" id="nc-account-token" class="nc-form-control" required
                           placeholder="<?php _e('Token de acesso do aplicativo', 'newsmast-curator'); ?>">
                    <span class="nc-form-help"><?php _e('Obtenha em: Sua Instância → Configurações → Desenvolvimento → Novo Aplicativo (escopos: read, write:statuses, write:media)', 'newsmast-curator'); ?></span>
                </div>
                <div id="nc-account-test-result"></div>
            </form>
        </div>
        <div class="nc-modal-footer">
            <button type="button" class="nc-button nc-button-secondary" onclick="NC.closeModal('nc-account-modal')">
                <?php _e('Cancelar', 'newsmast-curator'); ?>
            </button>
            <button type="button" class="nc-button nc-button-primary" onclick="NC.saveAccount()">
                <span class="dashicons dashicons-saved"></span>
                <?php _e('Salvar Conta', 'newsmast-curator'); ?>
            </button>
        </div>
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

<!-- Public API -->
<?php
$api_enabled = get_option('nc_api_enabled', '0') === '1';
$api_key = get_option('nc_api_key', '');
$site_url = get_rest_url(null, 'newsmast-curator/v1/public');
?>
<div class="nc-card">
    <div class="nc-card-header">
        <h2 class="nc-card-title">
            <span class="dashicons dashicons-rest-api"></span>
            <?php _e('API Pública', 'newsmast-curator'); ?>
        </h2>
    </div>
    <div class="nc-card-body">
        <div class="nc-form-group">
            <label class="nc-form-label" style="display:flex;align-items:center;gap:10px;">
                <input type="checkbox" id="nc-setting-api-enabled" <?php checked($api_enabled); ?>>
                <?php _e('Habilitar API Pública', 'newsmast-curator'); ?>
            </label>
            <span class="nc-form-help"><?php _e('Permite que sistemas externos agendem publicações via REST API autenticada por API Key.', 'newsmast-curator'); ?></span>
        </div>

        <div id="nc-api-settings-panel" style="<?php echo $api_enabled ? '' : 'display:none;'; ?>">
            <div class="nc-form-group">
                <label class="nc-form-label"><?php _e('API Key', 'newsmast-curator'); ?></label>
                <div style="display:flex;gap:8px;">
                    <input type="text" id="nc-setting-api-key" class="nc-form-control" style="flex:1;font-family:monospace;"
                           value="<?php echo $api_key ? '********' : ''; ?>"
                           placeholder="<?php _e('Clique em Gerar para criar uma chave', 'newsmast-curator'); ?>" readonly>
                    <button type="button" class="nc-button nc-button-secondary" onclick="NC.generateApiKey()">
                        <span class="dashicons dashicons-randomize"></span>
                        <?php _e('Gerar', 'newsmast-curator'); ?>
                    </button>
                    <button type="button" class="nc-button nc-button-secondary" onclick="NC.copyApiKey()">
                        <span class="dashicons dashicons-clipboard"></span>
                        <?php _e('Copiar', 'newsmast-curator'); ?>
                    </button>
                </div>
                <span class="nc-form-help"><?php _e('Após gerar, salve as configurações e copie a chave. Ela será mascarada após salvar.', 'newsmast-curator'); ?></span>
            </div>

            <div style="margin-top:20px;padding:15px;background:var(--nc-bg-alt,#f8f9fa);border-radius:8px;">
                <h3 style="margin:0 0 12px;font-size:14px;">
                    <span class="dashicons dashicons-book" style="color:var(--nc-accent);"></span>
                    <?php _e('Documentação da API', 'newsmast-curator'); ?>
                </h3>

                <p style="margin:0 0 8px;font-size:13px;color:#6C757D;">
                    <?php _e('Autenticação via header em todas as requisições:', 'newsmast-curator'); ?>
                </p>
                <code style="display:block;padding:8px 10px;background:#1e1e1e;color:#d4d4d4;border-radius:4px;font-size:12px;margin-bottom:16px;">X-NC-API-Key: SUA_CHAVE</code>

                <div style="margin-bottom:12px;">
                    <strong style="font-size:13px;"><?php _e('Endpoints disponíveis:', 'newsmast-curator'); ?></strong>
                </div>

                <table class="nc-table" style="font-size:12px;">
                    <thead>
                        <tr>
                            <th><?php _e('Método', 'newsmast-curator'); ?></th>
                            <th><?php _e('Endpoint', 'newsmast-curator'); ?></th>
                            <th><?php _e('Descrição', 'newsmast-curator'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td><span class="nc-badge nc-badge-success">GET</span></td>
                            <td><code>/public/items</code></td>
                            <td><?php _e('Listar itens curados disponíveis', 'newsmast-curator'); ?></td>
                        </tr>
                        <tr>
                            <td><span class="nc-badge nc-badge-success">GET</span></td>
                            <td><code>/public/items/{id}</code></td>
                            <td><?php _e('Detalhes de um item', 'newsmast-curator'); ?></td>
                        </tr>
                        <tr>
                            <td><span class="nc-badge nc-badge-info">POST</span></td>
                            <td><code>/public/schedule</code></td>
                            <td><?php _e('Agendar publicação (aceita mastodon_account_id)', 'newsmast-curator'); ?></td>
                        </tr>
                        <tr>
                            <td><span class="nc-badge nc-badge-success">GET</span></td>
                            <td><code>/public/publications</code></td>
                            <td><?php _e('Listar publicações (filtro por status)', 'newsmast-curator'); ?></td>
                        </tr>
                        <tr>
                            <td><span class="nc-badge nc-badge-success">GET</span></td>
                            <td><code>/public/publications/{id}</code></td>
                            <td><?php _e('Detalhes de uma publicação', 'newsmast-curator'); ?></td>
                        </tr>
                    </tbody>
                </table>

                <div style="margin-top:16px;">
                    <strong style="font-size:13px;"><?php _e('Exemplo: Agendar publicação', 'newsmast-curator'); ?></strong>
                </div>
                <code style="display:block;padding:10px;background:#1e1e1e;color:#d4d4d4;border-radius:4px;font-size:11px;margin-top:8px;white-space:pre-wrap;word-break:break-all;">curl -X POST "<?php echo esc_url($site_url); ?>/schedule" \
  -H "X-NC-API-Key: SUA_CHAVE" \
  -H "Content-Type: application/json" \
  -d '{"item_id": 1, "scheduled_for": "2026-03-04 15:00:00", "mastodon_account_ids": [1, 2]}'</code>

                <p style="margin:16px 0 0;font-size:12px;color:#999;">
                    <strong>Base URL:</strong> <code style="font-size:11px;"><?php echo esc_url($site_url); ?></code>
                </p>
            </div>
        </div>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    NC.updateTemplatePreview();
    NC.loadMastodonAccounts();

    $('#nc-setting-api-enabled').on('change', function() {
        $('#nc-api-settings-panel').toggle(this.checked);
    });
});
</script>
