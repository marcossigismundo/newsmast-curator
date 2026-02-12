<div class="nc-page-header">
    <div class="nc-page-title">
        <h1>
            <span class="dashicons dashicons-admin-site-alt3"></span>
            <?php _e('Fontes de Conteúdo', 'newsmast-curator'); ?>
        </h1>
        <div style="display:flex;gap:10px;">
            <button class="nc-button nc-button-secondary" onclick="NC.openModal('nc-help-sources-modal')">
                <span class="dashicons dashicons-editor-help"></span>
                <?php _e('Ajuda', 'newsmast-curator'); ?>
            </button>
            <button class="nc-button nc-button-primary" onclick="NC.showAddSourceModal()">
                <span class="dashicons dashicons-plus-alt"></span>
                <?php _e('Nova Fonte', 'newsmast-curator'); ?>
            </button>
        </div>
    </div>
    <p class="nc-page-description"><?php _e('Gerencie as fontes de onde o conteúdo é coletado automaticamente para curadoria e publicação no Mastodon.', 'newsmast-curator'); ?></p>
</div>

<!-- Guia rápido -->
<div class="nc-card" style="background:linear-gradient(135deg, #F1FAEE 0%, #A8DADC33 100%);border-left:4px solid var(--nc-accent);">
    <div class="nc-card-body" style="padding:20px;">
        <h3 style="margin:0 0 10px 0;color:var(--nc-accent);font-size:16px;">
            <span class="dashicons dashicons-lightbulb" style="color:var(--nc-warning);"></span>
            <?php _e('Como funciona?', 'newsmast-curator'); ?>
        </h3>
        <div style="display:grid;grid-template-columns:repeat(auto-fit, minmax(200px, 1fr));gap:15px;margin-top:15px;">
            <div style="display:flex;align-items:flex-start;gap:10px;">
                <span style="background:var(--nc-accent);color:white;border-radius:50%;width:28px;height:28px;display:flex;align-items:center;justify-content:center;font-weight:bold;font-size:13px;flex-shrink:0;">1</span>
                <div>
                    <strong style="font-size:13px;"><?php _e('Cadastre uma fonte', 'newsmast-curator'); ?></strong>
                    <p style="margin:4px 0 0;font-size:12px;color:var(--nc-text-light);"><?php _e('Clique em "Nova Fonte" e preencha os dados.', 'newsmast-curator'); ?></p>
                </div>
            </div>
            <div style="display:flex;align-items:flex-start;gap:10px;">
                <span style="background:var(--nc-accent);color:white;border-radius:50%;width:28px;height:28px;display:flex;align-items:center;justify-content:center;font-weight:bold;font-size:13px;flex-shrink:0;">2</span>
                <div>
                    <strong style="font-size:13px;"><?php _e('Colete conteúdo', 'newsmast-curator'); ?></strong>
                    <p style="margin:4px 0 0;font-size:12px;color:var(--nc-text-light);"><?php _e('Clique "Coletar" ou aguarde a coleta automática.', 'newsmast-curator'); ?></p>
                </div>
            </div>
            <div style="display:flex;align-items:flex-start;gap:10px;">
                <span style="background:var(--nc-accent);color:white;border-radius:50%;width:28px;height:28px;display:flex;align-items:center;justify-content:center;font-weight:bold;font-size:13px;flex-shrink:0;">3</span>
                <div>
                    <strong style="font-size:13px;"><?php _e('Faça a curadoria', 'newsmast-curator'); ?></strong>
                    <p style="margin:4px 0 0;font-size:12px;color:var(--nc-text-light);"><?php _e('Vá em Curadoria e aprove os itens coletados.', 'newsmast-curator'); ?></p>
                </div>
            </div>
            <div style="display:flex;align-items:flex-start;gap:10px;">
                <span style="background:var(--nc-accent);color:white;border-radius:50%;width:28px;height:28px;display:flex;align-items:center;justify-content:center;font-weight:bold;font-size:13px;flex-shrink:0;">4</span>
                <div>
                    <strong style="font-size:13px;"><?php _e('Publique no Mastodon', 'newsmast-curator'); ?></strong>
                    <p style="margin:4px 0 0;font-size:12px;color:var(--nc-text-light);"><?php _e('Agende ou publique os itens aprovados.', 'newsmast-curator'); ?></p>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Lista de fontes cadastradas -->
<div class="nc-card">
    <div class="nc-card-header">
        <h2 class="nc-card-title">
            <span class="dashicons dashicons-list-view"></span>
            <?php _e('Fontes Cadastradas', 'newsmast-curator'); ?>
        </h2>
        <button class="nc-button nc-button-primary" onclick="NC.collectAll()">
            <span class="dashicons dashicons-update"></span>
            <?php _e('Coletar Todas', 'newsmast-curator'); ?>
        </button>
    </div>
    <div class="nc-card-body" id="nc-sources-list">
        <div class="nc-loading">
            <div class="nc-spinner"></div>
        </div>
    </div>
</div>

<!-- Modal para adicionar/editar fonte -->
<div class="nc-modal-overlay" id="nc-source-modal">
    <div class="nc-modal" style="max-width:700px;">
        <div class="nc-modal-header">
            <h2 class="nc-modal-title" id="nc-modal-title"><?php _e('Nova Fonte', 'newsmast-curator'); ?></h2>
            <button class="nc-modal-close" onclick="NC.closeModal('nc-source-modal')">
                <span class="dashicons dashicons-no-alt"></span>
            </button>
        </div>
        <div class="nc-modal-body">
            <form id="nc-source-form">
                <input type="hidden" id="nc-source-id" name="id">

                <div class="nc-form-group">
                    <label class="nc-form-label">
                        <?php _e('Nome da Fonte', 'newsmast-curator'); ?> *
                    </label>
                    <input type="text" id="nc-source-name" name="name" class="nc-form-control" required
                           placeholder="<?php _e('Ex: Notícias IBRAM, Blog do Museu Nacional...', 'newsmast-curator'); ?>">
                    <span class="nc-form-help"><?php _e('Escolha um nome descritivo para identificar esta fonte facilmente.', 'newsmast-curator'); ?></span>
                </div>

                <div class="nc-form-group">
                    <label class="nc-form-label">
                        <?php _e('Tipo de Conector', 'newsmast-curator'); ?> *
                    </label>
                    <select id="nc-source-type" name="connector_type" class="nc-form-control" required>
                        <option value=""><?php _e('Selecione o tipo de site...', 'newsmast-curator'); ?></option>
                        <option value="plone">Plone CMS (gov.br, IBRAM)</option>
                        <option value="wordpress">WordPress (blogs, sites)</option>
                        <option value="tainacan">Tainacan (acervos digitais)</option>
                    </select>
                    <span class="nc-form-help"><?php _e('Selecione o tipo de site de onde deseja coletar conteúdo.', 'newsmast-curator'); ?></span>
                </div>

                <!-- Instruções dinâmicas por tipo de conector -->
                <div id="nc-connector-instructions"></div>

                <div class="nc-form-group" id="nc-url-group" style="display:none;">
                    <label class="nc-form-label" id="nc-url-label">
                        <?php _e('URL', 'newsmast-curator'); ?> *
                    </label>
                    <input type="url" id="nc-source-url" name="url" class="nc-form-control" required>
                    <span class="nc-form-help" id="nc-url-help"><?php _e('URL completa da fonte de conteúdo', 'newsmast-curator'); ?></span>
                </div>

                <div class="nc-form-group" id="nc-config-fields">
                    <!-- Campos dinâmicos de configuração serão inseridos aqui -->
                </div>
            </form>
        </div>
        <div class="nc-modal-footer">
            <button type="button" class="nc-button nc-button-secondary" onclick="NC.closeModal('nc-source-modal')">
                <?php _e('Cancelar', 'newsmast-curator'); ?>
            </button>
            <button type="button" class="nc-button nc-button-primary" onclick="NC.saveSource()">
                <span class="dashicons dashicons-saved"></span>
                <?php _e('Salvar Fonte', 'newsmast-curator'); ?>
            </button>
        </div>
    </div>
</div>

<!-- Modal de Ajuda -->
<div class="nc-modal-overlay" id="nc-help-sources-modal">
    <div class="nc-modal" style="max-width:750px;">
        <div class="nc-modal-header">
            <h2 class="nc-modal-title">
                <span class="dashicons dashicons-editor-help" style="color:var(--nc-accent);"></span>
                <?php _e('Guia de Fontes de Conteúdo', 'newsmast-curator'); ?>
            </h2>
            <button class="nc-modal-close" onclick="NC.closeModal('nc-help-sources-modal')">
                <span class="dashicons dashicons-no-alt"></span>
            </button>
        </div>
        <div class="nc-modal-body">
            <p style="color:var(--nc-text-light);margin-bottom:20px;">
                <?php _e('As fontes de conteúdo são sites de onde o plugin coleta automaticamente notícias, eventos e itens de acervo para que você possa selecionar e publicar no Mastodon.', 'newsmast-curator'); ?>
            </p>

            <!-- Plone -->
            <div class="nc-help-section">
                <h3 class="nc-help-title">
                    <span style="background:#E74C3C;color:white;padding:3px 10px;border-radius:4px;font-size:12px;">Plone</span>
                    <?php _e('Sites Plone CMS (gov.br)', 'newsmast-curator'); ?>
                </h3>
                <p><?php _e('Para coletar notícias de sites do governo que usam Plone CMS, como o portal do IBRAM.', 'newsmast-curator'); ?></p>
                <div class="nc-help-example">
                    <strong><?php _e('Exemplo:', 'newsmast-curator'); ?></strong><br>
                    <code>URL: https://www.gov.br/museus/pt-br/assuntos/noticias</code><br>
                    <code>CSS Selector: .tileItem (padrão para Plone)</code>
                </div>
                <p class="nc-help-tip">
                    <span class="dashicons dashicons-info" style="color:var(--nc-accent);"></span>
                    <?php _e('O conector faz scraping da página HTML. Os seletores CSS padrão funcionam para a maioria dos sites gov.br.', 'newsmast-curator'); ?>
                </p>
            </div>

            <!-- WordPress -->
            <div class="nc-help-section">
                <h3 class="nc-help-title">
                    <span style="background:#21759B;color:white;padding:3px 10px;border-radius:4px;font-size:12px;">WordPress</span>
                    <?php _e('Sites WordPress', 'newsmast-curator'); ?>
                </h3>
                <p><?php _e('Para coletar posts de qualquer site WordPress com REST API habilitada (padrão desde WP 4.7).', 'newsmast-curator'); ?></p>
                <div class="nc-help-example">
                    <strong><?php _e('Exemplo:', 'newsmast-curator'); ?></strong><br>
                    <code>URL: https://exemplo.com.br</code><br>
                    <code>Endpoint: /wp-json/wp/v2/posts</code>
                </div>
                <p class="nc-help-tip">
                    <span class="dashicons dashicons-info" style="color:var(--nc-accent);"></span>
                    <?php _e('Teste antes: acesse site.com/wp-json/wp/v2/posts no navegador. Se retornar JSON, a API está ativa.', 'newsmast-curator'); ?>
                </p>
            </div>

            <!-- Tainacan -->
            <div class="nc-help-section">
                <h3 class="nc-help-title">
                    <span style="background:#298E7E;color:white;padding:3px 10px;border-radius:4px;font-size:12px;">Tainacan</span>
                    <?php _e('Repositórios Tainacan', 'newsmast-curator'); ?>
                </h3>
                <p><?php _e('Para coletar itens de acervos digitais que usam o plugin Tainacan.', 'newsmast-curator'); ?></p>
                <div class="nc-help-example">
                    <strong><?php _e('Exemplo:', 'newsmast-curator'); ?></strong><br>
                    <code>URL: https://acervo.exemplo.com.br</code><br>
                    <code>ID da Coleção: 123</code>
                </div>
                <p class="nc-help-tip">
                    <span class="dashicons dashicons-info" style="color:var(--nc-accent);"></span>
                    <?php _e('O ID da coleção está na URL do admin do Tainacan: /admin/#/collections/123/items', 'newsmast-curator'); ?>
                </p>
            </div>

        </div>
        <div class="nc-modal-footer">
            <button type="button" class="nc-button nc-button-primary" onclick="NC.closeModal('nc-help-sources-modal')">
                <?php _e('Entendi!', 'newsmast-curator'); ?>
            </button>
        </div>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    NC.loadSources();

    // Carregar campos dinâmicos e instruções ao mudar tipo
    $('#nc-source-type').on('change', function() {
        const type = $(this).val();
        if (!type) {
            $('#nc-connector-instructions').html('');
            $('#nc-config-fields').html('');
            $('#nc-url-group').hide();
            return;
        }

        // Mostrar campo de URL
        $('#nc-url-group').show();

        let instructions = '';
        let fields = '';

        if (type === 'plone') {
            $('#nc-source-url').attr('placeholder', 'https://www.gov.br/museus/pt-br/assuntos/noticias');
            $('#nc-url-label').text('URL da Página de Notícias *');
            $('#nc-url-help').text('Cole a URL completa da página que lista as notícias no site Plone.');

            instructions = `
                <div class="nc-connector-info nc-connector-plone">
                    <div class="nc-connector-info-header">
                        <span class="dashicons dashicons-admin-site"></span>
                        <strong>Configurando fonte Plone CMS</strong>
                    </div>
                    <div class="nc-connector-info-body">
                        <p>O conector Plone coleta notícias de sites do governo que usam o CMS Plone (como gov.br).</p>
                        <div class="nc-connector-steps">
                            <div class="nc-step"><span class="nc-step-num">1</span> Acesse o site e navegue até a página de listagem de notícias</div>
                            <div class="nc-step"><span class="nc-step-num">2</span> Copie a URL completa da barra de endereços do navegador</div>
                            <div class="nc-step"><span class="nc-step-num">3</span> Cole a URL no campo abaixo</div>
                            <div class="nc-step"><span class="nc-step-num">4</span> Os seletores CSS padrão funcionam para a maioria dos sites gov.br</div>
                        </div>
                        <div class="nc-connector-tip">
                            <span class="dashicons dashicons-lightbulb"></span>
                            <span>Altere os seletores CSS apenas se o site usar uma estrutura diferente do padrão Plone.</span>
                        </div>
                    </div>
                </div>`;

            fields = `
                <div class="nc-form-group">
                    <label class="nc-form-label">CSS Selector dos Itens</label>
                    <input type="text" name="config[selector]" class="nc-form-control" value=".tileItem"
                           placeholder=".tileItem">
                    <span class="nc-form-help">Seletor CSS que identifica cada item de notícia na listagem. <strong>Padrão: .tileItem</strong></span>
                </div>
                <div class="nc-form-group">
                    <label class="nc-form-label">Selector do Título</label>
                    <input type="text" name="config[title_selector]" class="nc-form-control" value=".tileHeadline a"
                           placeholder=".tileHeadline a">
                    <span class="nc-form-help">Seletor do título dentro de cada item. <strong>Padrão: .tileHeadline a</strong></span>
                </div>
                <div class="nc-form-group">
                    <label class="nc-form-label">Selector do Resumo</label>
                    <input type="text" name="config[description_selector]" class="nc-form-control" value=".tileBody .description"
                           placeholder=".tileBody .description">
                    <span class="nc-form-help">Seletor da descrição/resumo. <strong>Padrão: .tileBody .description</strong></span>
                </div>
                <div class="nc-form-group">
                    <label class="nc-form-label">Selector da Imagem</label>
                    <input type="text" name="config[image_selector]" class="nc-form-control" value=".tileImage img"
                           placeholder=".tileImage img">
                    <span class="nc-form-help">Seletor da imagem de cada item. <strong>Padrão: .tileImage img</strong></span>
                </div>`;

        } else if (type === 'wordpress') {
            $('#nc-source-url').attr('placeholder', 'https://exemplo.com.br');
            $('#nc-url-label').text('URL do Site WordPress *');
            $('#nc-url-help').text('Cole a URL principal do site WordPress (sem /wp-json/ no final).');

            instructions = `
                <div class="nc-connector-info nc-connector-wordpress">
                    <div class="nc-connector-info-header">
                        <span class="dashicons dashicons-wordpress"></span>
                        <strong>Configurando fonte WordPress</strong>
                    </div>
                    <div class="nc-connector-info-body">
                        <p>O conector WordPress usa a REST API nativa para coletar posts automaticamente.</p>
                        <div class="nc-connector-steps">
                            <div class="nc-step"><span class="nc-step-num">1</span> Informe apenas a URL principal do site (ex: https://exemplo.com.br)</div>
                            <div class="nc-step"><span class="nc-step-num">2</span> O endpoint padrão já busca os posts mais recentes</div>
                            <div class="nc-step"><span class="nc-step-num">3</span> Para verificar: acesse <em>site.com/wp-json/wp/v2/posts</em> no navegador</div>
                        </div>
                        <div class="nc-connector-tip">
                            <span class="dashicons dashicons-lightbulb"></span>
                            <span>Se o JSON aparecer no navegador, a API está funcionando e pronta para coleta!</span>
                        </div>
                    </div>
                </div>`;

            fields = `
                <div class="nc-form-group">
                    <label class="nc-form-label">Endpoint da API</label>
                    <input type="text" name="config[api_endpoint]" class="nc-form-control" value="/wp-json/wp/v2/posts"
                           placeholder="/wp-json/wp/v2/posts">
                    <span class="nc-form-help">Caminho da API REST. <strong>Padrão: /wp-json/wp/v2/posts</strong>. Para páginas use /wp-json/wp/v2/pages.</span>
                </div>
                <div class="nc-form-group">
                    <label class="nc-form-label">Posts por Coleta</label>
                    <input type="number" name="config[per_page]" class="nc-form-control" value="10" min="1" max="100"
                           placeholder="10">
                    <span class="nc-form-help">Quantidade de posts por coleta. <strong>Padrão: 10</strong>. Máximo recomendado: 50.</span>
                </div>
                <div class="nc-form-group">
                    <label class="nc-form-label">Categorias (opcional)</label>
                    <input type="text" name="config[categories]" class="nc-form-control"
                           placeholder="Ex: 5,12,28">
                    <span class="nc-form-help">IDs de categorias separados por vírgula. Deixe vazio para coletar de todas.</span>
                </div>`;

        } else if (type === 'tainacan') {
            $('#nc-source-url').attr('placeholder', 'https://acervo.exemplo.com.br');
            $('#nc-url-label').text('URL do Site com Tainacan *');
            $('#nc-url-help').text('Cole a URL principal do site WordPress que tem o plugin Tainacan instalado.');

            instructions = `
                <div class="nc-connector-info nc-connector-tainacan">
                    <div class="nc-connector-info-header">
                        <span class="dashicons dashicons-archive"></span>
                        <strong>Configurando fonte Tainacan</strong>
                    </div>
                    <div class="nc-connector-info-body">
                        <p>O conector Tainacan coleta itens de acervos digitais que usam o plugin Tainacan no WordPress.</p>
                        <div class="nc-connector-steps">
                            <div class="nc-step"><span class="nc-step-num">1</span> Informe a URL do site que tem o Tainacan instalado</div>
                            <div class="nc-step"><span class="nc-step-num">2</span> Informe o ID numérico da coleção desejada</div>
                            <div class="nc-step"><span class="nc-step-num">3</span> Para encontrar o ID: abra a coleção no admin do Tainacan e veja o número na URL</div>
                        </div>
                        <div class="nc-connector-tip">
                            <span class="dashicons dashicons-lightbulb"></span>
                            <span>No admin do Tainacan, a URL da coleção contém o ID: <em>/admin/#/collections/<strong>123</strong>/items</em></span>
                        </div>
                    </div>
                </div>`;

            fields = `
                <div class="nc-form-group">
                    <label class="nc-form-label">ID da Coleção *</label>
                    <input type="number" name="config[collection_id]" class="nc-form-control" required
                           placeholder="Ex: 123">
                    <span class="nc-form-help">Número de identificação da coleção. Encontrado na URL do admin do Tainacan.</span>
                </div>
                <div class="nc-form-group">
                    <label class="nc-form-label">Itens por Coleta</label>
                    <input type="number" name="config[per_page]" class="nc-form-control" value="20" min="1" max="100"
                           placeholder="20">
                    <span class="nc-form-help">Quantidade de itens por coleta. <strong>Padrão: 20</strong>.</span>
                </div>
                <div class="nc-form-group">
                    <label class="nc-form-label">Ordenação</label>
                    <select name="config[orderby]" class="nc-form-control">
                        <option value="date">Data de criação (mais recentes primeiro)</option>
                        <option value="modified">Data de modificação</option>
                        <option value="title">Título (A-Z)</option>
                    </select>
                    <span class="nc-form-help">Como ordenar os itens ao buscar da coleção.</span>
                </div>`;

        }

        $('#nc-connector-instructions').html(instructions);
        $('#nc-config-fields').html(fields);
    });
});

NC.loadSources = function() {
    wp.apiFetch({path: ncData.apiUrl + '/sources'}).then(sources => {
        if (sources.length === 0) {
            jQuery('#nc-sources-list').html(`
                <div style="text-align:center;padding:60px 20px;">
                    <span class="dashicons dashicons-admin-site-alt3" style="font-size:60px;width:60px;height:60px;color:var(--nc-border);display:block;margin:0 auto 20px;"></span>
                    <h3 style="color:var(--nc-text-light);margin:0 0 10px;">Nenhuma fonte cadastrada</h3>
                    <p style="color:var(--nc-text-light);margin:0 0 20px;">Clique em <strong>"Nova Fonte"</strong> para cadastrar sua primeira fonte de conteúdo.</p>
                    <button class="nc-button nc-button-primary" onclick="NC.showAddSourceModal()">
                        <span class="dashicons dashicons-plus-alt"></span> Nova Fonte
                    </button>
                </div>
            `);
            return;
        }

        let html = '<table class="nc-table"><thead><tr><th>Nome</th><th>Tipo</th><th>URL</th><th>Status</th><th>Última Coleta</th><th>Ações</th></tr></thead><tbody>';
        sources.forEach(s => {
            const statusBadge = s.status === 'active' ? 'nc-badge-success' : s.status === 'error' ? 'nc-badge-danger' : 'nc-badge-warning';
            const statusLabel = s.status === 'active' ? 'Ativo' : s.status === 'error' ? 'Erro' : 'Inativo';
            const typeLabel = s.connector_type === 'plone' ? 'Plone CMS' :
                             s.connector_type === 'wordpress' ? 'WordPress' :
                             s.connector_type === 'tainacan' ? 'Tainacan' : s.connector_type;
            const lastCollection = s.last_collection ? new Date(s.last_collection).toLocaleString('pt-BR') : 'Nunca';
            const urlDisplay = s.url.length > 40 ? s.url.substring(0, 40) + '...' : s.url;

            html += `<tr>
                <td><strong>${s.name}</strong></td>
                <td><span class="nc-badge nc-badge-info" style="font-size:10px;">${typeLabel}</span></td>
                <td><a href="${s.url}" target="_blank" style="color:var(--nc-accent);">${urlDisplay}</a></td>
                <td><span class="nc-badge ${statusBadge}">${statusLabel}</span></td>
                <td><small>${lastCollection}</small></td>
                <td class="nc-table-actions">
                    <button class="nc-button nc-button-secondary" onclick="NC.editSource(${s.id})" title="Editar esta fonte">
                        <span class="dashicons dashicons-edit"></span>
                    </button>
                    <button class="nc-button nc-button-primary" onclick="NC.collectSource(${s.id})" title="Coletar conteúdo agora">
                        <span class="dashicons dashicons-update"></span>
                        Coletar
                    </button>
                    <button class="nc-button nc-button-danger" onclick="NC.deleteSource(${s.id})" title="Remover esta fonte">
                        <span class="dashicons dashicons-trash"></span>
                    </button>
                </td>
            </tr>`;
        });
        html += '</tbody></table>';
        jQuery('#nc-sources-list').html(html);
    }).catch(error => {
        console.error('Error loading sources:', error);
        jQuery('#nc-sources-list').html('<div class="nc-notice nc-notice-error">Erro ao carregar fontes: ' + (error.message || 'Erro desconhecido') + '</div>');
    });
};

NC.showAddSourceModal = function() {
    jQuery('#nc-modal-title').text('Nova Fonte');
    jQuery('#nc-source-form')[0].reset();
    jQuery('#nc-source-id').val('');
    jQuery('#nc-connector-instructions').html('');
    jQuery('#nc-config-fields').html('');
    jQuery('#nc-url-group').hide();
    NC.openModal('nc-source-modal');
};

NC.saveSource = function() {
    const form = jQuery('#nc-source-form')[0];
    if (!form.checkValidity()) {
        form.reportValidity();
        return;
    }

    const data = {
        name: jQuery('#nc-source-name').val(),
        connector_type: jQuery('#nc-source-type').val(),
        url: jQuery('#nc-source-url').val(),
        config: {}
    };

    // Pegar campos de config
    jQuery('#nc-config-fields input, #nc-config-fields select').each(function() {
        const name = jQuery(this).attr('name');
        if (name && name.startsWith('config[')) {
            const key = name.replace('config[', '').replace(']', '');
            data.config[key] = jQuery(this).val();
        }
    });

    const id = jQuery('#nc-source-id').val();
    const method = id ? 'PUT' : 'POST';
    const path = id ? `${ncData.apiUrl}/sources/${id}` : `${ncData.apiUrl}/sources`;

    wp.apiFetch({path, method, data}).then(response => {
        NC.showNotice('success', 'Fonte salva com sucesso!');
        NC.closeModal('nc-source-modal');
        NC.loadSources();
    }).catch(error => {
        NC.showNotice('error', 'Erro ao salvar fonte: ' + (error.message || 'Erro desconhecido'));
    });
};

NC.editSource = function(id) {
    NC.showNotice('info', 'Carregando fonte...');

    wp.apiFetch({path: `${ncData.apiUrl}/sources/${id}`}).then(source => {
        jQuery('#nc-modal-title').text('Editar Fonte');
        jQuery('#nc-source-id').val(source.id);
        jQuery('#nc-source-name').val(source.name);

        // Set type and trigger change to load dynamic fields
        jQuery('#nc-source-type').val(source.connector_type).trigger('change');

        // Wait for dynamic fields to render, then fill values
        setTimeout(function() {
            jQuery('#nc-source-url').val(source.url);

            // Fill config fields
            if (source.config && typeof source.config === 'object') {
                Object.keys(source.config).forEach(function(key) {
                    const $field = jQuery('#nc-config-fields').find(`[name="config[${key}]"]`);
                    if ($field.length) {
                        $field.val(source.config[key]);
                    }
                });
            }
        }, 100);

        NC.openModal('nc-source-modal');
    }).catch(error => {
        NC.showNotice('error', 'Erro ao carregar fonte: ' + (error.message || 'Erro desconhecido'));
    });
};

NC.collectSource = function(id) {
    if (!confirm('Iniciar coleta desta fonte agora?')) return;

    NC.showNotice('info', 'Coletando...');

    wp.apiFetch({
        path: `${ncData.apiUrl}/sources/${id}/collect`,
        method: 'POST'
    }).then(result => {
        NC.showNotice('success', `${result.items_collected} itens coletados!`);
        NC.loadSources();
    }).catch(error => {
        NC.showNotice('error', 'Erro na coleta: ' + (error.message || 'Erro desconhecido'));
    });
};

NC.deleteSource = function(id) {
    if (!confirm('Tem certeza que deseja remover esta fonte?\n\nOs itens já coletados serão mantidos.')) return;

    wp.apiFetch({
        path: `${ncData.apiUrl}/sources/${id}`,
        method: 'DELETE'
    }).then(() => {
        NC.showNotice('success', 'Fonte removida com sucesso!');
        NC.loadSources();
    }).catch(error => {
        NC.showNotice('error', 'Erro ao remover: ' + (error.message || 'Erro desconhecido'));
    });
};
</script>
