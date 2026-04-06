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

                <!-- Auto-Collect Configuration -->
                <div id="nc-auto-collect-section" style="display:none;margin-top:20px;padding-top:20px;border-top:1px solid var(--nc-border, #dee2e6);">
                    <h3 style="margin:0 0 15px;font-size:14px;color:var(--nc-accent);">
                        <span class="dashicons dashicons-clock"></span>
                        <?php _e('Coleta Automática', 'newsmast-curator'); ?>
                    </h3>
                    <div class="nc-form-group">
                        <label class="nc-form-label" style="display:flex;align-items:center;gap:10px;">
                            <input type="checkbox" id="nc-source-auto-collect" name="auto_collect">
                            <?php _e('Habilitar coleta automática para esta fonte', 'newsmast-curator'); ?>
                        </label>
                        <span class="nc-form-help"><?php _e('Quando habilitado, o sistema coletará novos itens automaticamente no intervalo configurado.', 'newsmast-curator'); ?></span>
                    </div>
                    <div class="nc-form-group" id="nc-collect-interval-group" style="display:none;">
                        <label class="nc-form-label"><?php _e('Intervalo de Coleta', 'newsmast-curator'); ?></label>
                        <select id="nc-source-collect-interval" name="collect_interval" class="nc-form-control">
                            <option value="every_15_minutes"><?php _e('A cada 15 minutos', 'newsmast-curator'); ?></option>
                            <option value="every_30_minutes"><?php _e('A cada 30 minutos', 'newsmast-curator'); ?></option>
                            <option value="hourly" selected><?php _e('A cada hora', 'newsmast-curator'); ?></option>
                            <option value="every_6_hours"><?php _e('A cada 6 horas', 'newsmast-curator'); ?></option>
                            <option value="every_12_hours"><?php _e('A cada 12 horas', 'newsmast-curator'); ?></option>
                            <option value="daily"><?php _e('Diariamente', 'newsmast-curator'); ?></option>
                        </select>
                        <span class="nc-form-help"><?php _e('Com que frequência o sistema verificará novos itens nesta fonte.', 'newsmast-curator'); ?></span>
                    </div>
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
                    <code><?php _e('Seletores padrão: .titulo, .subtitulo-noticia, .data, .newsImage', 'newsmast-curator'); ?></code>
                </div>
                <p class="nc-help-tip">
                    <span class="dashicons dashicons-info" style="color:var(--nc-accent);"></span>
                    <?php _e('O conector extrai título, resumo, data, imagem e tags automaticamente. Os seletores padrão funcionam para a maioria dos sites gov.br.', 'newsmast-curator'); ?>
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
                <p><?php _e('Para coletar itens de acervos digitais que usam o plugin Tainacan. Permite busca por assunto específico.', 'newsmast-curator'); ?></p>
                <div class="nc-help-example">
                    <strong><?php _e('Exemplo:', 'newsmast-curator'); ?></strong><br>
                    <code>URL: https://brasiliana.museus.gov.br</code><br>
                    <code>ID da Coleção: 5</code><br>
                    <code><?php _e('Termos de Busca: indígena', 'newsmast-curator'); ?></code>
                </div>
                <p class="nc-help-tip">
                    <span class="dashicons dashicons-info" style="color:var(--nc-accent);"></span>
                    <?php _e('O ID da coleção está na URL do admin do Tainacan: /admin/#/collections/123/items', 'newsmast-curator'); ?>
                </p>
                <p class="nc-help-tip">
                    <span class="dashicons dashicons-search" style="color:var(--nc-accent);"></span>
                    <?php _e('Dica: crie várias fontes do mesmo acervo com termos de busca diferentes para curar por assuntos específicos (ex: "indígena", "quilombo", "patrimônio imaterial").', 'newsmast-curator'); ?>
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

    // Toggle auto-collect
    $('#nc-source-auto-collect').on('change', function() {
        $('#nc-collect-interval-group').toggle(this.checked);
    });

    // Carregar campos dinâmicos e instruções ao mudar tipo
    $('#nc-source-type').on('change', function() {
        const type = $(this).val();
        if (!type) {
            $('#nc-connector-instructions').html('');
            $('#nc-config-fields').html('');
            $('#nc-url-group').hide();
            $('#nc-auto-collect-section').hide();
            return;
        }

        // Mostrar campo de URL e seção auto-collect
        $('#nc-url-group').show();
        $('#nc-auto-collect-section').show();

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
                            <div class="nc-step"><span class="nc-step-num">4</span> Os seletores padrão extraem título, resumo, data, imagem e tags automaticamente</div>
                        </div>
                        <div class="nc-connector-tip">
                            <span class="dashicons dashicons-lightbulb"></span>
                            <span>Os seletores padrão funcionam para sites gov.br. Altere apenas se o site usar classes CSS diferentes.</span>
                        </div>
                    </div>
                </div>`;

            fields = `
                <div class="nc-form-group">
                    <label class="nc-form-label">Páginas a coletar</label>
                    <input type="number" name="config[max_pages]" class="nc-form-control" value="1" min="1" max="10"
                           placeholder="1" style="width:100px;">
                    <span class="nc-form-help">Número de páginas da listagem a coletar (1-10). Cada página tem ~30 itens.</span>
                </div>
                <div class="nc-form-group">
                    <label class="nc-form-label">Seletor do Container de Itens</label>
                    <input type="text" name="config[selector]" class="nc-form-control" value="li"
                           placeholder="li">
                    <span class="nc-form-help">Tag ou classe que envolve cada item na listagem. <strong>Padrão: li</strong></span>
                </div>
                <div class="nc-form-group">
                    <label class="nc-form-label">Seletor do Título</label>
                    <input type="text" name="config[title_selector]" class="nc-form-control" value=".titulo"
                           placeholder=".titulo">
                    <span class="nc-form-help">Classe do elemento que contém o título (o link será buscado dentro). <strong>Padrão: .titulo</strong></span>
                </div>
                <div class="nc-form-group">
                    <label class="nc-form-label">Seletor do Resumo</label>
                    <input type="text" name="config[description_selector]" class="nc-form-control" value=".subtitulo-noticia"
                           placeholder=".subtitulo-noticia">
                    <span class="nc-form-help">Classe do resumo/subtítulo da notícia. <strong>Padrão: .subtitulo-noticia</strong></span>
                </div>
                <div class="nc-form-group">
                    <label class="nc-form-label">Seletor da Imagem</label>
                    <input type="text" name="config[image_selector]" class="nc-form-control" value=".newsImage"
                           placeholder=".newsImage">
                    <span class="nc-form-help">Classe da imagem da notícia. <strong>Padrão: .newsImage</strong></span>
                </div>
                <div class="nc-form-group">
                    <label class="nc-form-label">Seletor da Data</label>
                    <input type="text" name="config[date_selector]" class="nc-form-control" value=".data"
                           placeholder=".data">
                    <span class="nc-form-help">Classe do elemento de data (formato dd/mm/yyyy). <strong>Padrão: .data</strong></span>
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
                        <p>O conector Tainacan coleta itens de acervos digitais que usam o plugin Tainacan no WordPress. Permite curadoria por assunto usando termos de busca.</p>
                        <div class="nc-connector-steps">
                            <div class="nc-step"><span class="nc-step-num">1</span> Informe a URL do site que tem o Tainacan instalado</div>
                            <div class="nc-step"><span class="nc-step-num">2</span> Informe o ID numérico da coleção desejada</div>
                            <div class="nc-step"><span class="nc-step-num">3</span> Informe termos de busca para filtrar por assunto (ex: indígena, quilombo)</div>
                            <div class="nc-step"><span class="nc-step-num">4</span> Clique "Testar" para verificar se a busca retorna resultados antes de salvar</div>
                        </div>
                        <div class="nc-connector-tip">
                            <span class="dashicons dashicons-lightbulb"></span>
                            <span>Crie várias fontes do mesmo acervo com termos diferentes para curar por assuntos específicos!</span>
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
                    <label class="nc-form-label">
                        <span class="dashicons dashicons-search" style="font-size:16px;width:16px;height:16px;vertical-align:middle;color:var(--nc-accent);"></span>
                        Termos de Busca (curadoria por assunto)
                    </label>
                    <div style="display:flex;gap:8px;">
                        <input type="text" name="config[search_terms]" id="nc-tainacan-search-terms" class="nc-form-control" style="flex:1;"
                               placeholder="Ex: indígena, quilombo, patrimônio imaterial...">
                        <button type="button" class="nc-button nc-button-secondary" onclick="NC.testTainacanSearch()" id="nc-btn-test-search">
                            <span class="dashicons dashicons-search"></span>
                            Testar
                        </button>
                    </div>
                    <span class="nc-form-help">Filtra itens por palavras-chave no acervo. Deixe vazio para coletar todos os itens da coleção. <strong>Use o botão "Testar" para verificar se os termos retornam resultados antes de salvar.</strong></span>
                    <div id="nc-tainacan-search-result" style="margin-top:8px;"></div>
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

        let html = '<table class="nc-table"><thead><tr><th>Nome</th><th>Tipo</th><th>URL</th><th>Filtro</th><th>Status</th><th>Coleta Auto</th><th>Última Coleta</th><th>Ações</th></tr></thead><tbody>';
        sources.forEach(s => {
            const statusBadge = s.status === 'active' ? 'nc-badge-success' : s.status === 'error' ? 'nc-badge-danger' : 'nc-badge-warning';
            const statusLabel = s.status === 'active' ? 'Ativo' : s.status === 'error' ? 'Erro' : 'Inativo';
            const typeLabel = s.connector_type === 'plone' ? 'Plone CMS' :
                             s.connector_type === 'wordpress' ? 'WordPress' :
                             s.connector_type === 'tainacan' ? 'Tainacan' : s.connector_type;
            const lastCollection = s.last_collection ? new Date(s.last_collection).toLocaleString('pt-BR') : 'Nunca';
            const urlDisplay = s.url.length > 40 ? s.url.substring(0, 40) + '...' : s.url;

            const intervalLabels = {
                'every_15_minutes': '15min', 'every_30_minutes': '30min', 'hourly': '1h',
                'every_6_hours': '6h', 'every_12_hours': '12h', 'daily': '24h'
            };
            const autoCollectBadge = s.auto_collect
                ? `<span class="nc-badge nc-badge-success" style="font-size:10px;" title="Coleta automática a cada ${intervalLabels[s.collect_interval] || s.collect_interval}"><span class="dashicons dashicons-clock" style="font-size:12px;width:12px;height:12px;vertical-align:middle;"></span> ${intervalLabels[s.collect_interval] || s.collect_interval}</span>`
                : '<span class="nc-badge nc-badge-warning" style="font-size:10px;">Manual</span>';

            const searchTerms = (s.config && s.config.search_terms) ? s.config.search_terms : '';
            const filterDisplay = searchTerms
                ? `<span class="nc-badge nc-badge-info" style="font-size:10px;" title="Busca: ${NC.escapeHtml(searchTerms)}"><span class="dashicons dashicons-search" style="font-size:12px;width:12px;height:12px;vertical-align:middle;"></span> ${NC.escapeHtml(searchTerms.length > 20 ? searchTerms.substring(0, 20) + '...' : searchTerms)}</span>`
                : '<span style="color:var(--nc-text-light);font-size:12px;">—</span>';

            html += `<tr>
                <td><strong>${NC.escapeHtml(s.name)}</strong></td>
                <td><span class="nc-badge nc-badge-info" style="font-size:10px;">${typeLabel}</span></td>
                <td><a href="${NC.escapeHtml(s.url)}" target="_blank" style="color:var(--nc-accent);">${NC.escapeHtml(urlDisplay)}</a></td>
                <td>${filterDisplay}</td>
                <td><span class="nc-badge ${statusBadge}">${statusLabel}</span></td>
                <td>${autoCollectBadge}</td>
                <td><small>${lastCollection}</small></td>
                <td class="nc-table-actions">
                    <button class="nc-button nc-button-secondary" onclick="NC.editSource(${s.id})" title="Editar esta fonte">
                        <span class="dashicons dashicons-edit"></span>
                    </button>
                    <button class="nc-button nc-button-primary" onclick="NC.collectSource(${s.id})" title="${s.connector_type === 'tainacan' ? 'Explorar acervo e selecionar itens' : 'Coletar conteúdo agora'}">
                        <span class="dashicons ${s.connector_type === 'tainacan' ? 'dashicons-search' : 'dashicons-update'}"></span>
                        ${s.connector_type === 'tainacan' ? 'Explorar' : 'Coletar'}
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
    jQuery('#nc-auto-collect-section').hide();
    jQuery('#nc-collect-interval-group').hide();
    jQuery('#nc-source-auto-collect').prop('checked', false);
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
        config: {},
        auto_collect: jQuery('#nc-source-auto-collect').is(':checked') ? 1 : 0,
        collect_interval: jQuery('#nc-source-collect-interval').val()
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

            // Fill auto-collect fields
            jQuery('#nc-source-auto-collect').prop('checked', source.auto_collect);
            jQuery('#nc-source-collect-interval').val(source.collect_interval || 'hourly');
            jQuery('#nc-collect-interval-group').toggle(!!source.auto_collect);
        }, 100);

        NC.openModal('nc-source-modal');
    }).catch(error => {
        NC.showNotice('error', 'Erro ao carregar fonte: ' + (error.message || 'Erro desconhecido'));
    });
};

NC.collectSource = function(id) {
    // Para fontes Tainacan, abre o explorador para seleção manual
    wp.apiFetch({path: ncData.apiUrl + '/sources/' + id}).then(function(source) {
        if (source.connector_type === 'tainacan') {
            NC.openTainacanExplorer(source);
        } else {
            if (!confirm('Iniciar coleta desta fonte agora?')) return;
            NC.showNotice('info', 'Coletando...');
            wp.apiFetch({
                path: ncData.apiUrl + '/sources/' + id + '/collect',
                method: 'POST'
            }).then(function(result) {
                NC.showNotice('success', (result.items_collected || 0) + ' itens coletados!');
                NC.loadSources();
            }).catch(function(error) {
                NC.showNotice('error', 'Erro na coleta: ' + (error.message || 'Erro desconhecido'));
            });
        }
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

/**
 * Testa busca no acervo Tainacan via backend (evita bloqueio CORS)
 */
// ========== Tainacan Explorer Modal ==========
NC._exploreSelectedIds = {};
NC._explorePage = 1;
NC._exploreSourceId = null;

NC.openTainacanExplorer = function(source) {
    NC._exploreSourceId = source.id;
    NC._exploreSelectedIds = {};
    NC._explorePage = 1;

    jQuery('#nc-tainacan-explorer-modal').remove();

    var searchTerms = (source.config && source.config.search_terms) || '';
    var html =
    '<div id="nc-tainacan-explorer-modal" class="nc-modal-overlay">' +
        '<div class="nc-modal" style="max-width:900px;max-height:90vh;display:flex;flex-direction:column;">' +
            '<div class="nc-modal-header">' +
                '<h3 class="nc-modal-title"><span class="dashicons dashicons-database" style="color:var(--nc-accent);"></span> ' +
                    'Explorar: ' + NC.escapeHtml(source.name) + '</h3>' +
                '<button class="nc-modal-close" onclick="NC.closeModal(\'nc-tainacan-explorer-modal\')">&times;</button>' +
            '</div>' +
            '<div style="padding:12px 20px;border-bottom:1px solid var(--nc-border);display:flex;gap:10px;flex-wrap:wrap;align-items:flex-end;">' +
                '<div class="nc-form-group" style="flex:1;min-width:200px;margin:0;">' +
                    '<label class="nc-form-label">Buscar no acervo</label>' +
                    '<input type="text" id="nc-explore-search" class="nc-form-control" placeholder="Palavras-chave..." value="' + NC.escapeHtml(searchTerms) + '">' +
                '</div>' +
                '<div class="nc-form-group" style="flex:0 0 auto;margin:0;">' +
                    '<label class="nc-form-label">Ordenar</label>' +
                    '<select id="nc-explore-orderby" class="nc-form-control">' +
                        '<option value="date">Mais recentes</option>' +
                        '<option value="title">Título</option>' +
                    '</select>' +
                '</div>' +
                '<button class="nc-button nc-button-primary" onclick="NC.exploreBrowse(1)">' +
                    '<span class="dashicons dashicons-search"></span> Buscar' +
                '</button>' +
            '</div>' +
            '<div id="nc-explore-facets-row" style="display:none;padding:8px 20px;border-bottom:1px solid var(--nc-border);"></div>' +
            '<div id="nc-explore-status" style="padding:8px 20px;display:none;background:var(--nc-secondary);font-size:13px;display:flex;justify-content:space-between;align-items:center;">' +
                '<span><span class="dashicons dashicons-database" style="font-size:14px;width:14px;height:14px;vertical-align:middle;"></span> <span id="nc-explore-total">0</span> itens no acervo</span>' +
                '<span id="nc-explore-selected-info" style="display:none;">' +
                    '<strong id="nc-explore-selected-count">0</strong> selecionados' +
                '</span>' +
            '</div>' +
            '<div id="nc-explore-results" style="flex:1;overflow-y:auto;padding:0;">' +
                '<div style="padding:40px;text-align:center;color:var(--nc-text-light);">' +
                    '<span class="dashicons dashicons-search" style="font-size:48px;width:48px;height:48px;color:var(--nc-border);"></span>' +
                    '<p>Clique em <strong>Buscar</strong> para explorar o acervo Tainacan.</p>' +
                    '<p style="font-size:12px;">Selecione os itens desejados e importe para a curadoria.</p>' +
                '</div>' +
            '</div>' +
            '<div id="nc-explore-pagination" style="display:none;padding:10px 20px;border-top:1px solid var(--nc-border);text-align:center;"></div>' +
            '<div class="nc-modal-footer">' +
                '<button class="nc-button nc-button-secondary" onclick="NC.closeModal(\'nc-tainacan-explorer-modal\')">Fechar</button>' +
                '<button class="nc-button nc-button-outline" onclick="NC.exploreCollectAll()" title="Coleta tradicional: importa todos os itens da busca configurada">' +
                    '<span class="dashicons dashicons-update"></span> Coletar tudo (modo antigo)' +
                '</button>' +
                '<button class="nc-button nc-button-success" id="nc-explore-import-btn" onclick="NC.exploreImportSelected()" disabled>' +
                    '<span class="dashicons dashicons-download"></span> Importar selecionados (<span id="nc-explore-import-count">0</span>)' +
                '</button>' +
            '</div>' +
        '</div>' +
    '</div>';

    jQuery('body').append(html);

    // Load facets for this source
    wp.apiFetch({path: ncData.apiUrl + '/sources/' + source.id + '/tainacan-facets'}).then(function(data) {
        var facets = data.facets || [];
        if (facets.length === 0) return;
        var fhtml = '<div style="display:flex;gap:8px;flex-wrap:wrap;align-items:center;">';
        fhtml += '<span style="font-size:12px;font-weight:600;color:var(--nc-text-light);">Filtros:</span>';
        facets.forEach(function(f) {
            if (!f.values || f.values.length === 0) return;
            fhtml += '<select class="nc-form-control nc-explore-facet" data-slug="' + NC.escapeHtml(f.slug) + '" style="min-width:120px;font-size:12px;padding:4px 8px;">';
            fhtml += '<option value="">' + NC.escapeHtml(f.name) + '</option>';
            f.values.forEach(function(v) {
                if (v) fhtml += '<option value="' + NC.escapeHtml(v) + '">' + NC.escapeHtml(v.length > 30 ? v.substring(0, 30) + '...' : v) + '</option>';
            });
            fhtml += '</select>';
        });
        fhtml += '</div>';
        jQuery('#nc-explore-facets-row').html(fhtml).show();
    });

    jQuery('#nc-explore-search').on('keypress', function(e) {
        if (e.key === 'Enter') { e.preventDefault(); NC.exploreBrowse(1); }
    });

    NC.openModal('nc-tainacan-explorer-modal');

    // Auto-search if there are pre-configured search terms
    if (searchTerms) {
        NC.exploreBrowse(1);
    }
};

NC.exploreBrowse = function(page) {
    NC._explorePage = page || 1;
    jQuery('#nc-explore-results').html('<div style="padding:40px;text-align:center;"><div class="nc-spinner"></div> Buscando no acervo...</div>');

    var data = {
        source_id: NC._exploreSourceId,
        page: NC._explorePage,
        per_page: 24,
        search: jQuery('#nc-explore-search').val() || '',
        orderby: jQuery('#nc-explore-orderby').val() || 'date',
        order: 'DESC'
    };

    wp.apiFetch({
        path: ncData.apiUrl + '/items/browse-tainacan',
        method: 'POST',
        data: data
    }).then(function(res) {
        jQuery('#nc-explore-status').show();
        jQuery('#nc-explore-total').text(res.total || 0);

        if (!res.items || res.items.length === 0) {
            jQuery('#nc-explore-results').html(
                '<div style="padding:40px;text-align:center;color:var(--nc-text-light);">' +
                '<span class="dashicons dashicons-info" style="font-size:36px;width:36px;height:36px;color:var(--nc-border);"></span>' +
                '<p>Nenhum item encontrado para esta busca.</p></div>'
            );
            jQuery('#nc-explore-pagination').hide();
            return;
        }

        var html = '<div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(180px,1fr));gap:12px;padding:16px;">';
        res.items.forEach(function(item) {
            var isSelected = !!NC._exploreSelectedIds[item.tainacan_id];
            var imported = item.already_imported;
            html += '<div class="nc-explore-tile' + (isSelected ? ' nc-explore-tile-selected' : '') + (imported ? ' nc-explore-tile-imported' : '') + '" data-tainacan-id="' + item.tainacan_id + '" onclick="NC.exploreToggleItem(' + item.tainacan_id + ')">';

            if (item.image_url) {
                html += '<div class="nc-explore-tile-img"><img src="' + NC.escapeHtml(item.image_url) + '" alt="" loading="lazy"></div>';
            } else {
                html += '<div class="nc-explore-tile-img nc-explore-tile-noimg"><span class="dashicons dashicons-format-image"></span></div>';
            }

            html += '<div class="nc-explore-tile-info">';
            html += '<h4 class="nc-explore-tile-title">' + NC.escapeHtml(item.title.length > 50 ? item.title.substring(0, 50) + '...' : item.title) + '</h4>';
            if (item.author) html += '<div class="nc-explore-tile-meta">' + NC.escapeHtml(item.author) + '</div>';

            if (imported) {
                html += '<span class="nc-badge nc-badge-info" style="font-size:9px;">Já importado</span>';
            } else if (isSelected) {
                html += '<span class="nc-badge nc-badge-success" style="font-size:9px;">✓ Selecionado</span>';
            }

            html += '</div></div>';
        });
        html += '</div>';
        jQuery('#nc-explore-results').html(html);

        // Pagination
        if (res.total_pages > 1) {
            var phtml = '<div style="display:flex;gap:8px;justify-content:center;align-items:center;">';
            if (NC._explorePage > 1) {
                phtml += '<button class="nc-button nc-button-secondary nc-btn-sm" onclick="NC.exploreBrowse(' + (NC._explorePage - 1) + ')">&laquo; Anterior</button>';
            }
            phtml += '<span style="color:var(--nc-text-light);font-size:13px;">Página ' + NC._explorePage + ' de ' + res.total_pages + '</span>';
            if (NC._explorePage < res.total_pages) {
                phtml += '<button class="nc-button nc-button-secondary nc-btn-sm" onclick="NC.exploreBrowse(' + (NC._explorePage + 1) + ')">Próxima &raquo;</button>';
            }
            phtml += '</div>';
            jQuery('#nc-explore-pagination').html(phtml).show();
        } else {
            jQuery('#nc-explore-pagination').hide();
        }

        NC.exploreUpdateSelection();
    }).catch(function(e) {
        jQuery('#nc-explore-results').html(
            '<div class="nc-notice nc-notice-error" style="margin:16px;"><span class="dashicons dashicons-dismiss"></span> Erro: ' + (e.message || 'Falha na busca') + '</div>'
        );
    });
};

NC.exploreToggleItem = function(tainacanId) {
    var $tile = jQuery('[data-tainacan-id="' + tainacanId + '"]');
    if ($tile.hasClass('nc-explore-tile-imported')) return;

    if (NC._exploreSelectedIds[tainacanId]) {
        delete NC._exploreSelectedIds[tainacanId];
        $tile.removeClass('nc-explore-tile-selected');
    } else {
        NC._exploreSelectedIds[tainacanId] = true;
        $tile.addClass('nc-explore-tile-selected');
    }
    NC.exploreUpdateSelection();
};

NC.exploreUpdateSelection = function() {
    var count = Object.keys(NC._exploreSelectedIds).length;
    jQuery('#nc-explore-import-count').text(count);
    jQuery('#nc-explore-import-btn').prop('disabled', count === 0);
    if (count > 0) {
        jQuery('#nc-explore-selected-info').show();
        jQuery('#nc-explore-selected-count').text(count);
    } else {
        jQuery('#nc-explore-selected-info').hide();
    }
};

NC.exploreImportSelected = function() {
    var ids = Object.keys(NC._exploreSelectedIds).map(Number);
    if (ids.length === 0) return;

    NC.showNotice('info', 'Importando ' + ids.length + ' itens selecionados...');
    jQuery('#nc-explore-import-btn').prop('disabled', true).html('<span class="dashicons dashicons-update nc-spin"></span> Importando...');

    wp.apiFetch({
        path: ncData.apiUrl + '/items/import-selected',
        method: 'POST',
        data: { source_id: NC._exploreSourceId, tainacan_ids: ids }
    }).then(function(res) {
        NC.showNotice('success', res.imported + ' itens importados para curadoria! (' + res.skipped + ' já existiam)');
        NC._exploreSelectedIds = {};
        NC.exploreUpdateSelection();
        jQuery('#nc-explore-import-btn').html('<span class="dashicons dashicons-download"></span> Importar selecionados (<span id="nc-explore-import-count">0</span>)');
        // Refresh view to show imported badges
        NC.exploreBrowse(NC._explorePage);
        NC.loadSources();
    }).catch(function(e) {
        NC.showNotice('error', 'Erro na importação: ' + (e.message || ''));
        jQuery('#nc-explore-import-btn').prop('disabled', false).html('<span class="dashicons dashicons-download"></span> Importar selecionados (<span id="nc-explore-import-count">' + ids.length + '</span>)');
    });
};

NC.exploreCollectAll = function() {
    if (!confirm('Importar TODOS os itens desta fonte (modo tradicional)?\n\nIsto pode trazer muitos itens para a curadoria.')) return;

    NC.showNotice('info', 'Coletando todos os itens...');
    wp.apiFetch({
        path: ncData.apiUrl + '/sources/' + NC._exploreSourceId + '/collect',
        method: 'POST'
    }).then(function(result) {
        NC.showNotice('success', (result.items_collected || 0) + ' itens coletados!');
        NC.closeModal('nc-tainacan-explorer-modal');
        NC.loadSources();
    }).catch(function(error) {
        NC.showNotice('error', 'Erro na coleta: ' + (error.message || ''));
    });
};

NC.testTainacanSearch = function() {
    var url = jQuery('#nc-source-url').val().replace(/\/+$/, '');
    var collectionId = jQuery('#nc-config-fields [name="config[collection_id]"]').val();
    var searchTerms = jQuery('#nc-tainacan-search-terms').val().trim();
    var $result = jQuery('#nc-tainacan-search-result');
    var $btn = jQuery('#nc-btn-test-search');

    if (!url || !collectionId) {
        $result.html('<div class="nc-notice nc-notice-warning" style="margin:0;padding:8px 12px;font-size:12px;"><span class="dashicons dashicons-warning"></span> Preencha a URL e o ID da coleção antes de testar.</div>');
        return;
    }

    $btn.prop('disabled', true).find('.dashicons').removeClass('dashicons-search').addClass('dashicons-update nc-spin');
    $result.html('<div style="font-size:12px;color:var(--nc-text-light);"><span class="dashicons dashicons-update nc-spin" style="font-size:14px;width:14px;height:14px;vertical-align:middle;"></span> Consultando acervo Tainacan...</div>');

    wp.apiFetch({
        path: ncData.apiUrl + '/sources/test-tainacan',
        method: 'POST',
        data: {
            url: url,
            collection_id: collectionId,
            search_terms: searchTerms
        }
    }).then(function(result) {
        if (result.success) {
            var html = '<div class="nc-notice nc-notice-success" style="margin:0;padding:10px 12px;font-size:12px;">';
            html += '<span class="dashicons dashicons-yes-alt"></span> ';
            html += NC.escapeHtml(result.message);
            html += '</div>';

            // Mostrar amostra de itens
            if (result.sample_items && result.sample_items.length > 0) {
                html += '<div style="margin-top:8px;padding:10px;background:var(--nc-bg-alt,#f8f9fa);border-radius:6px;font-size:12px;">';
                html += '<strong style="font-size:11px;color:var(--nc-text-light);text-transform:uppercase;">Amostra dos primeiros itens:</strong>';
                html += '<ul style="margin:6px 0 0;padding-left:18px;list-style:disc;">';
                result.sample_items.forEach(function(item) {
                    var title = item.title || '(sem título)';
                    if (title.length > 80) title = title.substring(0, 80) + '...';
                    html += '<li>' + NC.escapeHtml(title) + '</li>';
                });
                html += '</ul></div>';
            }

            $result.html(html);
        } else {
            $result.html('<div class="nc-notice nc-notice-warning" style="margin:0;padding:8px 12px;font-size:12px;"><span class="dashicons dashicons-warning"></span> ' + NC.escapeHtml(result.message) + '</div>');
        }
    }).catch(function(error) {
        $result.html('<div class="nc-notice nc-notice-error" style="margin:0;padding:8px 12px;font-size:12px;"><span class="dashicons dashicons-dismiss"></span> Erro: ' + NC.escapeHtml(error.message || 'Erro desconhecido') + '</div>');
    }).finally(function() {
        $btn.prop('disabled', false).find('.dashicons').removeClass('dashicons-update nc-spin').addClass('dashicons-search');
    });
};
</script>
