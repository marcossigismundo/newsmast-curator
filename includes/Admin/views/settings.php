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
                            <td><?php _e('Agendar publicação', 'newsmast-curator'); ?></td>
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
  -d '{"item_id": 1, "scheduled_for": "2026-03-04 15:00:00"}'</code>

                <div style="margin-top:16px;">
                    <strong style="font-size:13px;"><?php _e('Campos do POST /schedule:', 'newsmast-curator'); ?></strong>
                </div>
                <table class="nc-table" style="font-size:12px;margin-top:8px;">
                    <thead>
                        <tr>
                            <th><?php _e('Campo', 'newsmast-curator'); ?></th>
                            <th><?php _e('Tipo', 'newsmast-curator'); ?></th>
                            <th><?php _e('Obrigatório', 'newsmast-curator'); ?></th>
                            <th><?php _e('Descrição', 'newsmast-curator'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td><code>item_id</code></td>
                            <td>integer</td>
                            <td><?php _e('Sim', 'newsmast-curator'); ?></td>
                            <td><?php _e('ID do item curado', 'newsmast-curator'); ?></td>
                        </tr>
                        <tr>
                            <td><code>scheduled_for</code></td>
                            <td>string</td>
                            <td><?php _e('Sim', 'newsmast-curator'); ?></td>
                            <td><?php _e('Data/hora futura (Y-m-d H:i:s)', 'newsmast-curator'); ?></td>
                        </tr>
                        <tr>
                            <td><code>content</code></td>
                            <td>string</td>
                            <td><?php _e('Não', 'newsmast-curator'); ?></td>
                            <td><?php _e('Texto do post. Se omitido, usa o template configurado.', 'newsmast-curator'); ?></td>
                        </tr>
                    </tbody>
                </table>

                <div style="margin-top:16px;">
                    <strong style="font-size:13px;"><?php _e('Códigos de resposta:', 'newsmast-curator'); ?></strong>
                </div>
                <table class="nc-table" style="font-size:12px;margin-top:8px;">
                    <tbody>
                        <tr><td><code>200</code></td><td><?php _e('Sucesso', 'newsmast-curator'); ?></td></tr>
                        <tr><td><code>201</code></td><td><?php _e('Publicação criada', 'newsmast-curator'); ?></td></tr>
                        <tr><td><code>400</code></td><td><?php _e('Erro de validação (campos obrigatórios, data retroativa)', 'newsmast-curator'); ?></td></tr>
                        <tr><td><code>401</code></td><td><?php _e('API desabilitada ou não configurada', 'newsmast-curator'); ?></td></tr>
                        <tr><td><code>403</code></td><td><?php _e('API Key inválida', 'newsmast-curator'); ?></td></tr>
                        <tr><td><code>404</code></td><td><?php _e('Item ou publicação não encontrada', 'newsmast-curator'); ?></td></tr>
                    </tbody>
                </table>

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

    $('#nc-setting-api-enabled').on('change', function() {
        $('#nc-api-settings-panel').toggle(this.checked);
    });
});
</script>
