<div class="nc-page-header">
    <div class="nc-page-title">
        <h1>
            <span class="dashicons dashicons-yes-alt"></span>
            <?php _e('Curadoria', 'newsmast-curator'); ?>
        </h1>
        <div class="nc-page-actions">
            <div class="nc-view-toggle" id="nc-view-toggle">
                <button class="nc-view-btn active" data-view="grid" onclick="NC.setViewMode('grid')" title="<?php esc_attr_e('Visualização em Grade', 'newsmast-curator'); ?>">
                    <span class="dashicons dashicons-grid-view"></span>
                </button>
                <button class="nc-view-btn" data-view="list" onclick="NC.setViewMode('list')" title="<?php esc_attr_e('Visualização em Lista', 'newsmast-curator'); ?>">
                    <span class="dashicons dashicons-list-view"></span>
                </button>
            </div>
            <button class="nc-button nc-button-outline" id="nc-btn-triage" onclick="NC.openTriageMode()">
                <span class="dashicons dashicons-images-alt"></span>
                <?php _e('Triagem Rápida', 'newsmast-curator'); ?>
            </button>
            <button class="nc-button nc-button-success" id="nc-btn-bulk-curate" onclick="NC.bulkCurate()">
                <span class="dashicons dashicons-yes"></span>
                <?php _e('Aprovar Selecionados', 'newsmast-curator'); ?>
            </button>
            <button class="nc-button nc-button-primary" id="nc-btn-bulk-schedule" style="display:none;" onclick="NC.openBulkScheduleModal()">
                <span class="dashicons dashicons-calendar-alt"></span>
                <?php _e('Agendar Selecionados', 'newsmast-curator'); ?>
            </button>
            <button class="nc-button nc-button-secondary" id="nc-btn-add-to-collection" style="display:none;" onclick="NC.openAddToCollectionModal()">
                <span class="dashicons dashicons-portfolio"></span>
                <?php _e('Adicionar à Coleção', 'newsmast-curator'); ?>
            </button>
        </div>
    </div>
    <p class="nc-page-description"><?php _e('Revise e aprove itens para publicação no Mastodon', 'newsmast-curator'); ?></p>
</div>

<div class="nc-tabs">
    <button class="nc-tab active" data-tab="novos" onclick="NC.switchTab(0)">
        <?php _e('Novos', 'newsmast-curator'); ?> <span id="nc-uncurated-count" class="nc-badge nc-badge-warning">0</span>
    </button>
    <button class="nc-tab" data-tab="aprovados" onclick="NC.switchTab(1)">
        <?php _e('Aprovados', 'newsmast-curator'); ?> <span id="nc-curated-count" class="nc-badge nc-badge-success">0</span>
    </button>
</div>

<!-- Search + Filters Bar -->
<div class="nc-search-filters">
    <div class="nc-search-bar">
        <span class="dashicons dashicons-search"></span>
        <input type="text" id="nc-search-input" class="nc-form-control" placeholder="<?php esc_attr_e('Buscar por título, conteúdo ou autor...', 'newsmast-curator'); ?>">
        <button class="nc-search-clear" id="nc-search-clear" style="display:none;" onclick="NC.clearSearch()" title="<?php esc_attr_e('Limpar busca', 'newsmast-curator'); ?>">&times;</button>
    </div>
    <div class="nc-filter-toggles">
        <select id="nc-filter-source" class="nc-form-control nc-filter-select">
            <option value=""><?php _e('Todas as fontes', 'newsmast-curator'); ?></option>
        </select>
        <button class="nc-button nc-button-secondary nc-btn-sm" id="nc-toggle-advanced" onclick="NC.toggleAdvancedFilters()">
            <span class="dashicons dashicons-filter"></span>
            <?php _e('Filtros', 'newsmast-curator'); ?>
            <span id="nc-active-filters-badge" class="nc-badge nc-badge-accent" style="display:none;">0</span>
        </button>
    </div>
</div>

<!-- Advanced Filters Panel -->
<div class="nc-advanced-filters" id="nc-advanced-filters" style="display:none;">
    <div class="nc-filters-grid">
        <div class="nc-filter-group">
            <label class="nc-filter-label"><?php _e('Período', 'newsmast-curator'); ?></label>
            <div class="nc-date-range">
                <input type="date" id="nc-filter-date-from" class="nc-form-control nc-filter-input" onchange="NC.applyFilters()">
                <span class="nc-date-sep"><?php _e('até', 'newsmast-curator'); ?></span>
                <input type="date" id="nc-filter-date-to" class="nc-form-control nc-filter-input" onchange="NC.applyFilters()">
            </div>
        </div>
        <div class="nc-filter-group">
            <label class="nc-filter-label"><?php _e('Autor', 'newsmast-curator'); ?></label>
            <input type="text" id="nc-filter-author" class="nc-form-control nc-filter-input" list="nc-authors-list" placeholder="<?php esc_attr_e('Filtrar por autor...', 'newsmast-curator'); ?>" onchange="NC.applyFilters()">
            <datalist id="nc-authors-list"></datalist>
        </div>
        <div class="nc-filter-group">
            <label class="nc-filter-label"><?php _e('Imagem', 'newsmast-curator'); ?></label>
            <select id="nc-filter-has-image" class="nc-form-control nc-filter-input" onchange="NC.applyFilters()">
                <option value=""><?php _e('Todos', 'newsmast-curator'); ?></option>
                <option value="1"><?php _e('Com imagem', 'newsmast-curator'); ?></option>
                <option value="0"><?php _e('Sem imagem', 'newsmast-curator'); ?></option>
            </select>
        </div>
        <div class="nc-filter-group">
            <label class="nc-filter-label"><?php _e('Tipo de coleta', 'newsmast-curator'); ?></label>
            <select id="nc-filter-collection-type" class="nc-form-control nc-filter-input" onchange="NC.applyFilters()">
                <option value=""><?php _e('Todos', 'newsmast-curator'); ?></option>
                <option value="auto"><?php _e('Automática', 'newsmast-curator'); ?></option>
                <option value="manual"><?php _e('Manual', 'newsmast-curator'); ?></option>
            </select>
        </div>
        <div class="nc-filter-group">
            <label class="nc-filter-label"><?php _e('Ordenar por', 'newsmast-curator'); ?></label>
            <select id="nc-filter-orderby" class="nc-form-control nc-filter-input" onchange="NC.applyFilters()">
                <option value="id"><?php _e('Mais recentes', 'newsmast-curator'); ?></option>
                <option value="title"><?php _e('Título (A-Z)', 'newsmast-curator'); ?></option>
                <option value="author"><?php _e('Autor', 'newsmast-curator'); ?></option>
                <option value="collected_at"><?php _e('Data de coleta', 'newsmast-curator'); ?></option>
            </select>
        </div>
    </div>
    <!-- Facetas dinâmicas Tainacan -->
    <div id="nc-tainacan-facets" class="nc-tainacan-facets" style="display:none;">
        <div class="nc-facets-header">
            <span class="dashicons dashicons-database"></span>
            <strong><?php _e('Metadados da Coleção', 'newsmast-curator'); ?></strong>
            <span id="nc-facets-source-name" class="nc-facets-source-label"></span>
        </div>
        <div class="nc-filters-grid" id="nc-facets-container"></div>
    </div>
    <div class="nc-filters-actions">
        <button class="nc-button nc-button-secondary nc-btn-sm" onclick="NC.clearAllFilters()">
            <span class="dashicons dashicons-dismiss"></span> <?php _e('Limpar filtros', 'newsmast-curator'); ?>
        </button>
    </div>
</div>

<!-- Items container -->
<div class="nc-card">
    <div class="nc-card-header nc-items-header" id="nc-select-all-header">
        <label style="display:flex;align-items:center;gap:8px;cursor:pointer;">
            <input type="checkbox" onchange="NC.selectAll(this)">
            <span><?php _e('Selecionar Todos', 'newsmast-curator'); ?></span>
        </label>
        <div class="nc-items-info">
            <span id="nc-items-total-info" class="nc-items-counter"></span>
            <span id="nc-selected-count" style="display:none;">
                <strong id="nc-selected-num">0</strong> <?php _e('selecionados', 'newsmast-curator'); ?>
            </span>
        </div>
    </div>
    <div class="nc-card-body nc-items-scroll-container" id="nc-items-list">
        <div class="nc-loading"><div class="nc-spinner"></div></div>
    </div>
</div>

<!-- Floating Action Bar (persistent selection) -->
<div class="nc-floating-bar" id="nc-floating-bar" style="display:none;">
    <div class="nc-floating-bar-inner">
        <span class="nc-floating-count">
            <span class="dashicons dashicons-yes-alt"></span>
            <strong id="nc-floating-num">0</strong> <?php _e('itens selecionados', 'newsmast-curator'); ?>
        </span>
        <div class="nc-floating-actions">
            <button class="nc-button nc-button-success nc-btn-sm" id="nc-floating-approve" onclick="NC.bulkCurate()">
                <span class="dashicons dashicons-yes"></span> <?php _e('Aprovar', 'newsmast-curator'); ?>
            </button>
            <button class="nc-button nc-button-primary nc-btn-sm" id="nc-floating-schedule" style="display:none;" onclick="NC.openBulkScheduleModal()">
                <span class="dashicons dashicons-calendar-alt"></span> <?php _e('Agendar', 'newsmast-curator'); ?>
            </button>
            <button class="nc-button nc-button-secondary nc-btn-sm" id="nc-floating-collection" style="display:none;" onclick="NC.openAddToCollectionModal()">
                <span class="dashicons dashicons-portfolio"></span> <?php _e('Coleção', 'newsmast-curator'); ?>
            </button>
            <button class="nc-button nc-button-secondary nc-btn-sm" onclick="NC.viewSelection()">
                <span class="dashicons dashicons-visibility"></span> <?php _e('Ver seleção', 'newsmast-curator'); ?>
            </button>
            <button class="nc-button nc-button-outline nc-btn-sm" onclick="NC.clearSelection()">
                <span class="dashicons dashicons-dismiss"></span> <?php _e('Limpar', 'newsmast-curator'); ?>
            </button>
        </div>
    </div>
</div>

<!-- Preview Lightbox -->
<div id="nc-lightbox" class="nc-lightbox-overlay" style="display:none;">
    <div class="nc-lightbox-container">
        <button class="nc-lightbox-close" onclick="NC.closeLightbox()">&times;</button>
        <button class="nc-lightbox-nav nc-lightbox-prev" onclick="NC.lightboxNav(-1)">
            <span class="dashicons dashicons-arrow-left-alt2"></span>
        </button>
        <div class="nc-lightbox-content">
            <div class="nc-lightbox-image-wrap">
                <img id="nc-lightbox-image" src="" alt="">
                <div id="nc-lightbox-no-image" class="nc-lightbox-no-image" style="display:none;">
                    <span class="dashicons dashicons-format-image"></span>
                </div>
            </div>
            <div class="nc-lightbox-details">
                <h2 id="nc-lightbox-title"></h2>
                <div class="nc-lightbox-meta" id="nc-lightbox-meta"></div>
                <div class="nc-lightbox-description" id="nc-lightbox-description"></div>
                <div class="nc-lightbox-metadata" id="nc-lightbox-metadata"></div>
                <div class="nc-lightbox-actions" id="nc-lightbox-actions"></div>
            </div>
        </div>
        <button class="nc-lightbox-nav nc-lightbox-next" onclick="NC.lightboxNav(1)">
            <span class="dashicons dashicons-arrow-right-alt2"></span>
        </button>
        <div class="nc-lightbox-counter" id="nc-lightbox-counter"></div>
    </div>
</div>

<!-- Triage Mode -->
<div id="nc-triage-overlay" class="nc-triage-overlay" style="display:none;">
    <div class="nc-triage-header">
        <h3><span class="dashicons dashicons-images-alt"></span> <?php _e('Triagem Rápida', 'newsmast-curator'); ?></h3>
        <div class="nc-triage-progress">
            <div class="nc-triage-progress-bar">
                <div class="nc-triage-progress-fill" id="nc-triage-progress-fill"></div>
            </div>
            <span id="nc-triage-counter">0 / 0</span>
        </div>
        <div class="nc-triage-stats">
            <span class="nc-triage-stat nc-triage-approved"><span class="dashicons dashicons-yes-alt"></span> <span id="nc-triage-approved-count">0</span></span>
            <span class="nc-triage-stat nc-triage-skipped"><span class="dashicons dashicons-arrow-right-alt"></span> <span id="nc-triage-skipped-count">0</span></span>
        </div>
        <button class="nc-button nc-button-secondary nc-btn-sm" onclick="NC.closeTriageMode()">
            <span class="dashicons dashicons-no-alt"></span> <?php _e('Sair', 'newsmast-curator'); ?>
        </button>
    </div>
    <!-- Triage Filters -->
    <div class="nc-triage-filters" id="nc-triage-filters">
        <div class="nc-triage-filters-row">
            <div class="nc-triage-filter-group">
                <span class="dashicons dashicons-search"></span>
                <input type="text" id="nc-triage-search" class="nc-form-control nc-triage-filter-input" placeholder="<?php esc_attr_e('Buscar por título, conteúdo ou autor...', 'newsmast-curator'); ?>">
            </div>
            <div class="nc-triage-filter-group">
                <span class="dashicons dashicons-admin-site-alt3"></span>
                <select id="nc-triage-source" class="nc-form-control nc-triage-filter-select">
                    <option value=""><?php _e('Todas as fontes', 'newsmast-curator'); ?></option>
                </select>
            </div>
            <div class="nc-triage-filter-group">
                <span class="dashicons dashicons-format-image"></span>
                <select id="nc-triage-has-image" class="nc-form-control nc-triage-filter-select">
                    <option value=""><?php _e('Todos', 'newsmast-curator'); ?></option>
                    <option value="1"><?php _e('Com imagem', 'newsmast-curator'); ?></option>
                    <option value="0"><?php _e('Sem imagem', 'newsmast-curator'); ?></option>
                </select>
            </div>
            <div class="nc-triage-filter-group">
                <span class="dashicons dashicons-sort"></span>
                <select id="nc-triage-orderby" class="nc-form-control nc-triage-filter-select">
                    <option value="id"><?php _e('Mais recentes', 'newsmast-curator'); ?></option>
                    <option value="title"><?php _e('Título (A-Z)', 'newsmast-curator'); ?></option>
                    <option value="author"><?php _e('Autor', 'newsmast-curator'); ?></option>
                    <option value="collected_at"><?php _e('Data de coleta', 'newsmast-curator'); ?></option>
                </select>
            </div>
            <button class="nc-button nc-button-primary nc-btn-sm" onclick="NC.triageApplyFilters()">
                <span class="dashicons dashicons-update"></span> <?php _e('Aplicar', 'newsmast-curator'); ?>
            </button>
            <button class="nc-button nc-button-outline nc-btn-sm" onclick="NC.triageClearFilters()">
                <span class="dashicons dashicons-dismiss"></span>
            </button>
        </div>
        <div id="nc-triage-tainacan-facets" class="nc-triage-facets-row" style="display:none;"></div>
        <div class="nc-triage-filter-info" id="nc-triage-filter-info"></div>
    </div>
    <div class="nc-triage-body" id="nc-triage-body">
        <!-- Grid de itens -->
        <div class="nc-triage-grid" id="nc-triage-grid"></div>
    </div>
    <!-- Barra de ações em lote fixada no rodapé -->
    <div class="nc-triage-batch-bar" id="nc-triage-batch-bar" style="display:none;">
        <span class="nc-triage-batch-count">
            <span class="dashicons dashicons-yes-alt"></span>
            <strong id="nc-triage-batch-num">0</strong> <?php _e('selecionados', 'newsmast-curator'); ?>
        </span>
        <div class="nc-triage-batch-actions">
            <button class="nc-button nc-button-success nc-btn-sm" onclick="NC.triageBatchApprove()">
                <span class="dashicons dashicons-yes"></span> <?php _e('Aprovar selecionados', 'newsmast-curator'); ?>
                <kbd>K</kbd>
            </button>
            <button class="nc-button nc-button-primary nc-btn-sm" onclick="NC.triageSelectAll()">
                <span class="dashicons dashicons-yes-alt"></span> <?php _e('Selecionar todos', 'newsmast-curator'); ?>
                <kbd>A</kbd>
            </button>
            <button class="nc-button nc-button-outline nc-btn-sm" onclick="NC.triageClearSelection()">
                <span class="dashicons dashicons-dismiss"></span> <?php _e('Limpar', 'newsmast-curator'); ?>
            </button>
        </div>
        <div class="nc-triage-batch-shortcuts">
            <span><kbd>A</kbd> <?php _e('Selecionar todos', 'newsmast-curator'); ?></span>
            <span><kbd>K</kbd> <?php _e('Aprovar selecionados', 'newsmast-curator'); ?></span>
            <span><kbd>Z</kbd> <?php _e('Desfazer', 'newsmast-curator'); ?></span>
            <span><kbd>F</kbd> <?php _e('Filtrar', 'newsmast-curator'); ?></span>
            <span><kbd>Esc</kbd> <?php _e('Sair', 'newsmast-curator'); ?></span>
        </div>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    // Stats
    wp.apiFetch({path: ncData.apiUrl + '/items/stats'}).then(function(stats) {
        $('#nc-uncurated-count').text(stats.uncurated || 0);
        $('#nc-curated-count').text(stats.curated || 0);
    });

    // Load sources for filter (store for connector_type lookup)
    NC._sourcesMap = {};
    wp.apiFetch({path: ncData.apiUrl + '/sources'}).then(function(data) {
        var $sel = $('#nc-filter-source');
        (data.sources || data || []).forEach(function(s) {
            NC._sourcesMap[s.id] = s;
            var label = NC.escapeHtml(s.name);
            if (s.config && s.config.search_terms) {
                label += ' (' + NC.escapeHtml(s.config.search_terms) + ')';
            }
            $sel.append('<option value="' + s.id + '" data-type="' + (s.connector_type || '') + '">' + label + '</option>');
        });
    });

    // Load Tainacan facets when source changes
    $('#nc-filter-source').on('change', function() {
        NC.loadTainacanFacets($(this).val(), 'main');
        NC.applyFilters();
    });

    // Load authors for autocomplete
    wp.apiFetch({path: ncData.apiUrl + '/items/authors'}).then(function(data) {
        var $list = $('#nc-authors-list');
        (data.authors || []).forEach(function(a) {
            $list.append('<option value="' + NC.escapeHtml(a) + '">');
        });
    });

    NC._viewMode = localStorage.getItem('nc_view_mode') || 'grid';
    $('#nc-view-toggle .nc-view-btn').removeClass('active');
    $('#nc-view-toggle .nc-view-btn[data-view="' + NC._viewMode + '"]').addClass('active');

    // Search with debounce
    var searchTimer = null;
    $('#nc-search-input').on('input', function() {
        var val = $(this).val();
        $('#nc-search-clear').toggle(val.length > 0);
        clearTimeout(searchTimer);
        searchTimer = setTimeout(function() {
            NC.resetAndLoad();
        }, 300);
    });

    // Checkbox handler with persistent selection
    $(document).on('change', '.nc-item-checkbox', function() {
        var id = $(this).val();
        var item = NC._loadedItemsMap[id];
        if ($(this).is(':checked') && item) {
            NC._selectedItemsMap[id] = item;
        } else {
            delete NC._selectedItemsMap[id];
        }
        NC.updateSelectionUI();
    });

    NC.loadItems(0);
});

// ========== State ==========
NC._currentCuratedTab = 0;
NC._currentPage = 1;
NC._totalItems = 0;
NC._totalPages = 0;
NC._isLoadingMore = false;
NC._allLoadedItems = [];
NC._loadedItemsMap = {};
NC._selectedItemsMap = {};
NC._scrollObserver = null;

// ========== Tab Switch ==========
NC.switchTab = function(curated) {
    NC._currentCuratedTab = curated;
    NC._selectedItemsMap = {};
    NC.updateSelectionUI();

    jQuery('.nc-tab').removeClass('active');
    jQuery('.nc-tab[data-tab="' + (curated === 0 ? 'novos' : 'aprovados') + '"]').addClass('active');

    if (curated === 1) {
        jQuery('#nc-btn-bulk-curate').hide();
        jQuery('#nc-btn-bulk-schedule').show();
        jQuery('#nc-btn-add-to-collection').show();
        jQuery('#nc-btn-triage').hide();
        jQuery('#nc-floating-approve').hide();
        jQuery('#nc-floating-schedule').show();
        jQuery('#nc-floating-collection').show();
    } else {
        jQuery('#nc-btn-bulk-curate').show();
        jQuery('#nc-btn-bulk-schedule').hide();
        jQuery('#nc-btn-add-to-collection').hide();
        jQuery('#nc-btn-triage').show();
        jQuery('#nc-floating-approve').show();
        jQuery('#nc-floating-schedule').hide();
        jQuery('#nc-floating-collection').hide();
    }

    NC.resetAndLoad();
};

// ========== Filters ==========
NC._facetsCache = {};
NC._currentFacets = [];

NC.getFilterParams = function() {
    var params = '';
    var search = jQuery('#nc-search-input').val();
    if (search) params += '&search=' + encodeURIComponent(search);
    var source = jQuery('#nc-filter-source').val();
    if (source) params += '&source_id=' + source;
    var dateFrom = jQuery('#nc-filter-date-from').val();
    if (dateFrom) params += '&date_from=' + dateFrom + ' 00:00:00';
    var dateTo = jQuery('#nc-filter-date-to').val();
    if (dateTo) params += '&date_to=' + dateTo + ' 23:59:59';
    var author = jQuery('#nc-filter-author').val();
    if (author) params += '&author=' + encodeURIComponent(author);
    var hasImage = jQuery('#nc-filter-has-image').val();
    if (hasImage !== '') params += '&has_image=' + hasImage;
    var collType = jQuery('#nc-filter-collection-type').val();
    if (collType) params += '&collection_type=' + collType;
    var orderby = jQuery('#nc-filter-orderby').val();
    if (orderby && orderby !== 'id') params += '&orderby=' + orderby + '&order=ASC';
    // Tainacan metadata facets
    jQuery('.nc-facet-select').each(function() {
        var val = jQuery(this).val();
        if (val) {
            params += '&meta_' + jQuery(this).data('slug') + '=' + encodeURIComponent(val);
        }
    });
    return params;
};

NC.applyFilters = function() {
    NC.updateActiveFiltersCount();
    NC.resetAndLoad();
};

NC.toggleAdvancedFilters = function() {
    jQuery('#nc-advanced-filters').slideToggle(200);
    jQuery('#nc-toggle-advanced').toggleClass('active');
};

NC.clearSearch = function() {
    jQuery('#nc-search-input').val('');
    jQuery('#nc-search-clear').hide();
    NC.resetAndLoad();
};

NC.clearAllFilters = function() {
    jQuery('#nc-search-input').val('');
    jQuery('#nc-search-clear').hide();
    jQuery('#nc-filter-source').val('').trigger('change');
    jQuery('#nc-filter-date-from').val('');
    jQuery('#nc-filter-date-to').val('');
    jQuery('#nc-filter-author').val('');
    jQuery('#nc-filter-has-image').val('');
    jQuery('#nc-filter-collection-type').val('');
    jQuery('#nc-filter-orderby').val('id');
    jQuery('.nc-facet-select').val('');
    NC.updateActiveFiltersCount();
    NC.resetAndLoad();
};

NC.updateActiveFiltersCount = function() {
    var count = 0;
    if (jQuery('#nc-filter-date-from').val()) count++;
    if (jQuery('#nc-filter-date-to').val()) count++;
    if (jQuery('#nc-filter-author').val()) count++;
    if (jQuery('#nc-filter-has-image').val() !== '') count++;
    if (jQuery('#nc-filter-collection-type').val()) count++;
    if (jQuery('#nc-filter-orderby').val() !== 'id') count++;
    jQuery('.nc-facet-select').each(function() { if (jQuery(this).val()) count++; });
    var $badge = jQuery('#nc-active-filters-badge');
    if (count > 0) {
        $badge.text(count).show();
    } else {
        $badge.hide();
    }
};

// ========== Tainacan Facets ==========
NC.loadTainacanFacets = function(sourceId, target) {
    var $container, $panel;
    if (target === 'triage') {
        $container = jQuery('#nc-triage-tainacan-facets');
    } else {
        $container = jQuery('#nc-facets-container');
        $panel = jQuery('#nc-tainacan-facets');
    }

    // Se não tem fonte selecionada ou não é Tainacan, esconde
    if (!sourceId || !NC._sourcesMap[sourceId] || NC._sourcesMap[sourceId].connector_type !== 'tainacan') {
        if (target === 'triage') {
            $container.hide().empty();
        } else {
            $panel.hide();
            $container.empty();
        }
        NC._currentFacets = [];
        return;
    }

    var source = NC._sourcesMap[sourceId];

    // Cache hit?
    if (NC._facetsCache[sourceId]) {
        NC.renderFacets(NC._facetsCache[sourceId], source, target);
        return;
    }

    // Loading state
    if (target === 'triage') {
        $container.html('<span style="color:var(--nc-text-light);font-size:12px;padding:4px 0;"><span class="nc-spinner-small" style="display:inline-block;vertical-align:middle;margin-right:6px;"></span> Carregando metadados da coleção...</span>').show();
    } else {
        $panel.show();
        $container.html('<div style="grid-column:1/-1;color:var(--nc-text-light);font-size:13px;"><span class="nc-spinner-small" style="display:inline-block;vertical-align:middle;margin-right:6px;"></span> Carregando metadados da coleção...</div>');
    }

    wp.apiFetch({path: ncData.apiUrl + '/sources/' + sourceId + '/tainacan-facets'}).then(function(data) {
        var facets = (data.facets || []).filter(function(f) {
            return f.values && f.values.length > 0;
        });
        NC._facetsCache[sourceId] = facets;
        NC.renderFacets(facets, source, target);
    }).catch(function(e) {
        if (target === 'triage') {
            $container.hide().empty();
        } else {
            $panel.hide();
            $container.empty();
        }
    });
};

NC.renderFacets = function(facets, source, target) {
    NC._currentFacets = facets;
    var $container, $panel;
    if (target === 'triage') {
        $container = jQuery('#nc-triage-tainacan-facets');
    } else {
        $container = jQuery('#nc-facets-container');
        $panel = jQuery('#nc-tainacan-facets');
    }

    if (!facets || facets.length === 0) {
        if (target === 'triage') {
            $container.hide().empty();
        } else {
            $panel.hide();
            $container.empty();
        }
        return;
    }

    var html = '';
    if (target !== 'triage') {
        jQuery('#nc-facets-source-name').text(source.name || '');
    }

    facets.forEach(function(facet) {
        var cls = target === 'triage' ? 'nc-triage-filter-group' : 'nc-filter-group';
        var selectCls = target === 'triage' ? 'nc-form-control nc-triage-filter-select nc-facet-select nc-triage-facet-select' : 'nc-form-control nc-filter-input nc-facet-select';
        var changeHandler = target === 'triage' ? '' : ' onchange="NC.applyFilters()"';

        html += '<div class="' + cls + '">';
        if (target !== 'triage') {
            html += '<label class="nc-filter-label">' + NC.escapeHtml(facet.name) + '</label>';
        } else {
            html += '<span class="dashicons dashicons-tag" style="font-size:14px;width:14px;height:14px;color:var(--nc-text-light);align-self:center;"></span>';
        }
        html += '<select class="' + selectCls + '" data-slug="' + NC.escapeHtml(facet.slug) + '"' + changeHandler + ' title="' + NC.escapeHtml(facet.name) + '">';
        html += '<option value="">' + NC.escapeHtml(facet.name) + '</option>';
        facet.values.forEach(function(val) {
            var display = val.length > 40 ? val.substring(0, 40) + '...' : val;
            html += '<option value="' + NC.escapeHtml(val) + '">' + NC.escapeHtml(display) + '</option>';
        });
        html += '</select></div>';
    });

    $container.html(html);

    if (target === 'triage') {
        $container.show();
    } else {
        $panel.show();
        // Auto-open advanced filters if facets are shown
        if (!jQuery('#nc-advanced-filters').is(':visible')) {
            jQuery('#nc-advanced-filters').slideDown(200);
            jQuery('#nc-toggle-advanced').addClass('active');
        }
    }
};

// ========== Infinite Scroll ==========
NC.resetAndLoad = function() {
    NC._currentPage = 1;
    NC._allLoadedItems = [];
    NC._loadedItemsMap = {};
    NC._isLoadingMore = false;
    if (NC._scrollObserver) NC._scrollObserver.disconnect();
    jQuery('#nc-select-all-header input[type="checkbox"]').prop('checked', false);
    NC.loadItems(NC._currentCuratedTab);
};

NC.loadItems = function(curated) {
    NC._currentCuratedTab = curated;

    if (NC._currentPage === 1) {
        jQuery('#nc-items-list').html('<div class="nc-loading"><div class="nc-spinner"></div></div>');
    }

    var apiPath = ncData.apiUrl + '/items?curated=' + curated + '&per_page=50&page=' + NC._currentPage;
    apiPath += NC.getFilterParams();

    wp.apiFetch({path: apiPath}).then(function(data) {
        NC._totalItems = data.total || 0;
        NC._totalPages = data.pages || 0;

        // Update total info
        jQuery('#nc-items-total-info').text(
            NC._allLoadedItems.length + (data.items ? data.items.length : 0) + ' de ' + NC._totalItems + ' itens'
        );

        if (!data.items || data.items.length === 0) {
            if (NC._currentPage === 1) {
                var emptyMsg = curated === 0 ?
                    NC.__('no_new_items', 'Nenhum item novo para curadoria. Execute uma coleta para trazer novos itens.') :
                    NC.__('no_approved_items_yet', 'Nenhum item aprovado ainda. Aprove itens na aba "Novos".');
                var searchVal = jQuery('#nc-search-input').val();
                if (searchVal) {
                    emptyMsg = 'Nenhum item encontrado para "' + NC.escapeHtml(searchVal) + '". Tente ajustar os filtros.';
                }
                jQuery('#nc-items-list').html(
                    '<div class="nc-empty-state">' +
                        '<span class="dashicons dashicons-' + (curated === 0 ? 'inbox' : 'yes-alt') + '"></span>' +
                        '<p>' + emptyMsg + '</p>' +
                    '</div>'
                );
            }
            NC._isLoadingMore = false;
            return;
        }

        // Store items in map
        data.items.forEach(function(item) {
            NC._loadedItemsMap[item.id] = item;
            NC._allLoadedItems.push(item);
        });

        var viewMode = NC._viewMode || 'grid';
        if (NC._currentPage === 1) {
            var html = viewMode === 'list' ? NC.renderItemsList(data.items, curated) : NC.renderItemsGrid(data.items, curated);
            jQuery('#nc-items-list').html(html);
        } else {
            // Append items
            if (viewMode === 'list') {
                var rows = '';
                data.items.forEach(function(item) {
                    rows += NC.renderItemRow(item, curated);
                });
                jQuery('#nc-items-list .nc-table tbody').append(rows);
            } else {
                data.items.forEach(function(item) {
                    jQuery('#nc-items-list .nc-items-grid').append(NC.renderItemCard(item, curated));
                });
            }
        }

        // Restore checkboxes for persistent selection
        NC.restoreCheckboxes();

        // Setup infinite scroll sentinel
        NC.setupScrollObserver();
        NC._isLoadingMore = false;

    }).catch(function() {
        if (NC._currentPage === 1) {
            jQuery('#nc-items-list').html(
                '<div class="nc-notice nc-notice-error">' +
                    '<span class="dashicons dashicons-dismiss"></span> ' + NC.__('load_items_error', 'Erro ao carregar itens') +
                '</div>'
            );
        }
        NC._isLoadingMore = false;
    });
};

NC.setupScrollObserver = function() {
    if (NC._scrollObserver) NC._scrollObserver.disconnect();
    if (NC._currentPage >= NC._totalPages) {
        // Remove old sentinel
        jQuery('#nc-scroll-sentinel').remove();
        return;
    }

    // Add sentinel element
    jQuery('#nc-scroll-sentinel').remove();
    jQuery('#nc-items-list').append('<div id="nc-scroll-sentinel" class="nc-scroll-sentinel"><div class="nc-spinner-small"></div> Carregando mais itens...</div>');

    NC._scrollObserver = new IntersectionObserver(function(entries) {
        if (entries[0].isIntersecting && !NC._isLoadingMore && NC._currentPage < NC._totalPages) {
            NC._isLoadingMore = true;
            NC._currentPage++;
            NC.loadItems(NC._currentCuratedTab);
        }
    }, { threshold: 0.1 });

    var sentinel = document.getElementById('nc-scroll-sentinel');
    if (sentinel) NC._scrollObserver.observe(sentinel);
};

// ========== Persistent Selection ==========
NC.updateSelectionUI = function() {
    var count = Object.keys(NC._selectedItemsMap).length;
    if (count > 0) {
        jQuery('#nc-selected-count').show();
        jQuery('#nc-selected-num').text(count);
        jQuery('#nc-floating-bar').slideDown(200);
        jQuery('#nc-floating-num').text(count);
    } else {
        jQuery('#nc-selected-count').hide();
        jQuery('#nc-floating-bar').slideUp(200);
    }
};

NC.restoreCheckboxes = function() {
    jQuery('.nc-item-checkbox').each(function() {
        var id = jQuery(this).val();
        if (NC._selectedItemsMap[id]) {
            jQuery(this).prop('checked', true);
        }
    });
};

NC.selectAll = function(checkbox) {
    jQuery('.nc-item-checkbox').prop('checked', checkbox.checked);
    jQuery('.nc-item-checkbox').each(function() {
        var id = jQuery(this).val();
        var item = NC._loadedItemsMap[id];
        if (checkbox.checked && item) {
            NC._selectedItemsMap[id] = item;
        } else if (!checkbox.checked) {
            delete NC._selectedItemsMap[id];
        }
    });
    NC.updateSelectionUI();
};

NC.clearSelection = function() {
    NC._selectedItemsMap = {};
    jQuery('.nc-item-checkbox').prop('checked', false);
    jQuery('#nc-select-all-header input[type="checkbox"]').prop('checked', false);
    NC.updateSelectionUI();
};

NC.viewSelection = function() {
    var items = Object.values(NC._selectedItemsMap);
    if (items.length === 0) return;
    NC._lightboxItems = items;
    NC._lightboxIndex = 0;
    NC.showLightbox();
};

// ========== Render helpers ==========
NC.renderItemCard = function(item, curated) {
    var html = '<div class="nc-item-card" data-item-id="' + item.id + '">';
    if (item.image_url) {
        html += '<img src="' + NC.escapeHtml(item.image_url) + '" class="nc-item-image nc-item-preview-trigger" alt="" onclick="NC.openLightboxForItem(' + item.id + ')">';
    } else {
        html += '<div class="nc-item-image-placeholder nc-item-preview-trigger" onclick="NC.openLightboxForItem(' + item.id + ')">' +
            '<span class="dashicons dashicons-format-image"></span></div>';
    }
    html += '<div class="nc-item-content">';
    html += '<label style="display:flex;align-items:center;gap:8px;margin-bottom:10px;">' +
        '<input type="checkbox" class="nc-item-checkbox" value="' + item.id + '"' +
        ' data-formatted="' + encodeURIComponent(item.formatted_content || '') + '">' +
        '<span style="font-size:12px;color:#6C757D;">' + NC.__('select', 'Selecionar') + '</span></label>';

    var collectionBadge = item.collection_type === 'auto'
        ? '<span class="nc-badge nc-badge-info" style="font-size:9px;vertical-align:middle;" title="Coletado automaticamente em ' + new Date(item.collected_at).toLocaleString('pt-BR') + '"><span class="dashicons dashicons-clock" style="font-size:11px;width:11px;height:11px;vertical-align:middle;"></span> Auto</span> '
        : '';

    html += '<h3 class="nc-item-title nc-item-preview-trigger" onclick="NC.openLightboxForItem(' + item.id + ')" style="cursor:pointer;">' + collectionBadge + NC.escapeHtml(item.title) + '</h3>' +
        '<div class="nc-item-meta">';
    if (item.source_name) {
        html += '<span title="Fonte"><span class="dashicons dashicons-admin-site-alt3" style="font-size:14px;"></span> ' + NC.escapeHtml(item.source_name) + '</span>';
    }
    if (item.search_terms) {
        html += '<span title="Termo de busca"><span class="dashicons dashicons-search" style="font-size:14px;"></span> ' + NC.escapeHtml(item.search_terms) + '</span>';
    }
    html += '<span><span class="dashicons dashicons-calendar" style="font-size:14px;"></span> ' +
            new Date(item.collected_at).toLocaleString('pt-BR') + '</span>';
    if (item.author) {
        html += '<span><span class="dashicons dashicons-admin-users" style="font-size:14px;"></span> ' + NC.escapeHtml(item.author) + '</span>';
    }
    html += '</div><p class="nc-item-excerpt">' + NC.escapeHtml(item.preview_text) + '</p>' +
        '<div class="nc-item-actions">';
    if (curated === 0) {
        html += '<button class="nc-button nc-button-success" onclick="NC.curateItem(' + item.id + ')">' +
            '<span class="dashicons dashicons-yes-alt"></span> ' + NC.__('approve', 'Aprovar') + '</button>';
    } else {
        html += '<button class="nc-button nc-button-primary" onclick="NC.openScheduleModal(' + item.id + ')">' +
            '<span class="dashicons dashicons-calendar-alt"></span> ' + NC.__('schedule', 'Agendar') + '</button>';
    }
    html += '<a href="' + NC.escapeHtml(item.url) + '" target="_blank" class="nc-button nc-button-secondary">' +
        '<span class="dashicons dashicons-external"></span> ' + NC.__('view_original', 'Ver Original') + '</a>' +
        '</div></div></div>';
    return html;
};

NC.renderItemsGrid = function(items, curated) {
    var html = '<div class="nc-items-grid">';
    items.forEach(function(item) {
        html += NC.renderItemCard(item, curated);
    });
    html += '</div>';
    return html;
};

NC.renderItemRow = function(item, curated) {
    var html = '<tr data-item-id="' + item.id + '">';
    html += '<td><input type="checkbox" class="nc-item-checkbox" value="' + item.id + '"' +
        ' data-formatted="' + encodeURIComponent(item.formatted_content || '') + '"></td>';
    html += '<td class="nc-list-thumb-cell" onclick="NC.openLightboxForItem(' + item.id + ')" style="cursor:pointer;">';
    if (item.image_url) {
        html += '<img src="' + NC.escapeHtml(item.image_url) + '" class="nc-list-thumb" alt="">';
    } else {
        html += '<span class="nc-list-thumb-placeholder dashicons dashicons-format-image"></span>';
    }
    html += '</td>';
    var title = item.title.length > 80 ? NC.escapeHtml(item.title.substring(0, 80)) + '...' : NC.escapeHtml(item.title);
    var excerpt = item.preview_text ? (item.preview_text.length > 100 ? NC.escapeHtml(item.preview_text.substring(0, 100)) + '...' : NC.escapeHtml(item.preview_text)) : '';
    var listBadge = item.collection_type === 'auto'
        ? '<span class="nc-badge nc-badge-info" style="font-size:9px;margin-left:6px;" title="Coleta automática"><span class="dashicons dashicons-clock" style="font-size:11px;width:11px;height:11px;vertical-align:middle;"></span> Auto</span>'
        : '';
    html += '<td><strong class="nc-item-title nc-item-preview-trigger" onclick="NC.openLightboxForItem(' + item.id + ')" style="cursor:pointer;">' + title + listBadge + '</strong>';
    if (excerpt) {
        html += '<div class="nc-list-excerpt">' + excerpt + '</div>';
    }
    html += '</td>';
    html += '<td>' + NC.escapeHtml(item.source_name || '-') + '</td>';
    var searchBadge = item.search_terms
        ? '<span class="nc-badge nc-badge-info" style="font-size:10px;"><span class="dashicons dashicons-search" style="font-size:11px;width:11px;height:11px;vertical-align:middle;"></span> ' + NC.escapeHtml(item.search_terms) + '</span>'
        : '<span style="color:var(--nc-text-light);">—</span>';
    html += '<td>' + searchBadge + '</td>';
    html += '<td>' + new Date(item.collected_at).toLocaleDateString('pt-BR') + '</td>';
    html += '<td class="nc-table-actions">';
    if (curated === 0) {
        html += '<button class="nc-button nc-button-success" onclick="NC.curateItem(' + item.id + ')" title="' + NC.__('approve', 'Aprovar') + '">' +
            '<span class="dashicons dashicons-yes-alt"></span></button>';
    } else {
        html += '<button class="nc-button nc-button-primary" onclick="NC.openScheduleModal(' + item.id + ')" title="' + NC.__('schedule', 'Agendar') + '">' +
            '<span class="dashicons dashicons-calendar-alt"></span></button>';
    }
    html += '<a href="' + NC.escapeHtml(item.url) + '" target="_blank" class="nc-button nc-button-secondary" title="' + NC.__('view_original', 'Ver Original') + '">' +
        '<span class="dashicons dashicons-external"></span></a>';
    html += '</td></tr>';
    return html;
};

NC.renderItemsList = function(items, curated) {
    var html = '<div class="nc-items-list-view"><table class="nc-table"><thead><tr>' +
        '<th style="width:40px;"><input type="checkbox" onchange="NC.selectAll(this)"></th>' +
        '<th style="width:60px;">' + NC.__('image', 'Imagem') + '</th>' +
        '<th>' + NC.__('title', 'Título') + '</th>' +
        '<th>' + NC.__('source', 'Fonte') + '</th>' +
        '<th>' + NC.__('filter', 'Filtro') + '</th>' +
        '<th>' + NC.__('date', 'Data') + '</th>' +
        '<th>' + NC.__('actions', 'Ações') + '</th>' +
        '</tr></thead><tbody>';

    items.forEach(function(item) {
        html += NC.renderItemRow(item, curated);
    });
    html += '</tbody></table></div>';
    return html;
};

// ========== Preview Lightbox ==========
NC._lightboxItems = [];
NC._lightboxIndex = 0;

NC.openLightboxForItem = function(itemId) {
    NC._lightboxItems = NC._allLoadedItems;
    NC._lightboxIndex = NC._allLoadedItems.findIndex(function(i) { return i.id == itemId; });
    if (NC._lightboxIndex === -1) NC._lightboxIndex = 0;
    NC.showLightbox();
};

NC.showLightbox = function() {
    if (NC._lightboxItems.length === 0) return;
    var item = NC._lightboxItems[NC._lightboxIndex];
    NC.renderLightboxItem(item);
    jQuery('#nc-lightbox').fadeIn(200);
    jQuery('body').css('overflow', 'hidden');
};

NC.renderLightboxItem = function(item) {
    if (item.image_url) {
        jQuery('#nc-lightbox-image').attr('src', item.image_url).show();
        jQuery('#nc-lightbox-no-image').hide();
    } else {
        jQuery('#nc-lightbox-image').hide();
        jQuery('#nc-lightbox-no-image').show();
    }
    jQuery('#nc-lightbox-title').text(item.title);

    var meta = '';
    if (item.source_name) meta += '<span><span class="dashicons dashicons-admin-site-alt3"></span> ' + NC.escapeHtml(item.source_name) + '</span>';
    if (item.author) meta += '<span><span class="dashicons dashicons-admin-users"></span> ' + NC.escapeHtml(item.author) + '</span>';
    if (item.collected_at) meta += '<span><span class="dashicons dashicons-calendar"></span> ' + new Date(item.collected_at).toLocaleString('pt-BR') + '</span>';
    if (item.search_terms) meta += '<span><span class="dashicons dashicons-search"></span> ' + NC.escapeHtml(item.search_terms) + '</span>';
    jQuery('#nc-lightbox-meta').html(meta);

    // Content/description
    var desc = item.content || item.preview_text || '';
    if (desc.length > 500) desc = desc.substring(0, 500) + '...';
    jQuery('#nc-lightbox-description').html('<p>' + NC.escapeHtml(desc) + '</p>');

    // Tainacan metadata
    var metadataHtml = '';
    if (item.metadata) {
        var md = typeof item.metadata === 'string' ? JSON.parse(item.metadata) : item.metadata;
        if (md && typeof md === 'object') {
            var keys = Object.keys(md);
            if (keys.length > 0) {
                metadataHtml = '<div class="nc-lightbox-metadata-grid">';
                keys.forEach(function(key) {
                    var val = md[key];
                    if (val && typeof val === 'string' && val.trim()) {
                        metadataHtml += '<div class="nc-metadata-item"><strong>' + NC.escapeHtml(key) + ':</strong> ' + NC.escapeHtml(val) + '</div>';
                    }
                });
                metadataHtml += '</div>';
            }
        }
    }
    jQuery('#nc-lightbox-metadata').html(metadataHtml);

    // Actions
    var curated = NC._currentCuratedTab;
    var actions = '';
    if (curated === 0) {
        actions += '<button class="nc-button nc-button-success" onclick="NC.curateItemFromLightbox(' + item.id + ')">' +
            '<span class="dashicons dashicons-yes-alt"></span> Aprovar</button>';
    } else {
        actions += '<button class="nc-button nc-button-primary" onclick="NC.closeLightbox();NC.openScheduleModal(' + item.id + ')">' +
            '<span class="dashicons dashicons-calendar-alt"></span> Agendar</button>';
    }
    actions += '<a href="' + NC.escapeHtml(item.url) + '" target="_blank" class="nc-button nc-button-secondary">' +
        '<span class="dashicons dashicons-external"></span> Ver Original</a>';
    jQuery('#nc-lightbox-actions').html(actions);

    jQuery('#nc-lightbox-counter').text((NC._lightboxIndex + 1) + ' / ' + NC._lightboxItems.length);
};

NC.curateItemFromLightbox = function(itemId) {
    wp.apiFetch({ path: ncData.apiUrl + '/items/' + itemId + '/curate', method: 'POST' }).then(function() {
        NC.showNotice('success', 'Item aprovado!');
        // Auto-advance
        NC._lightboxItems = NC._lightboxItems.filter(function(i) { return i.id != itemId; });
        NC._allLoadedItems = NC._allLoadedItems.filter(function(i) { return i.id != itemId; });
        delete NC._loadedItemsMap[itemId];
        jQuery('[data-item-id="' + itemId + '"]').fadeOut(300, function() { jQuery(this).remove(); });
        var $counter = jQuery('#nc-uncurated-count');
        $counter.text(Math.max(0, (parseInt($counter.text()) || 0) - 1));
        jQuery('#nc-curated-count').text((parseInt(jQuery('#nc-curated-count').text()) || 0) + 1);

        if (NC._lightboxItems.length === 0) {
            NC.closeLightbox();
        } else {
            if (NC._lightboxIndex >= NC._lightboxItems.length) NC._lightboxIndex = NC._lightboxItems.length - 1;
            NC.renderLightboxItem(NC._lightboxItems[NC._lightboxIndex]);
        }
    }).catch(function(e) { NC.showNotice('error', 'Erro: ' + (e.message || '')); });
};

NC.lightboxNav = function(dir) {
    NC._lightboxIndex += dir;
    if (NC._lightboxIndex < 0) NC._lightboxIndex = NC._lightboxItems.length - 1;
    if (NC._lightboxIndex >= NC._lightboxItems.length) NC._lightboxIndex = 0;
    NC.renderLightboxItem(NC._lightboxItems[NC._lightboxIndex]);
};

NC.closeLightbox = function() {
    jQuery('#nc-lightbox').fadeOut(200);
    jQuery('body').css('overflow', '');
};

// Keyboard for lightbox
jQuery(document).on('keydown', function(e) {
    if (jQuery('#nc-lightbox').is(':visible')) {
        if (e.key === 'ArrowLeft') NC.lightboxNav(-1);
        else if (e.key === 'ArrowRight') NC.lightboxNav(1);
        else if (e.key === 'Escape') NC.closeLightbox();
        return;
    }
    // Triage keyboard handled separately
});

// ========== Triage Mode ==========
NC._triageItems = [];
NC._triageApproved = 0;
NC._triageUndoStack = [];
NC._triageActive = false;
NC._triageSelectedIds = {};

NC.openTriageMode = function() {
    NC.triageSyncFiltersFromMain();
    NC.triageLoadItems();
};

NC.triageSyncFiltersFromMain = function() {
    var $mainSource = jQuery('#nc-filter-source');
    var $triageSource = jQuery('#nc-triage-source');
    $triageSource.html($mainSource.html());
    $triageSource.val($mainSource.val());
    jQuery('#nc-triage-search').val(jQuery('#nc-search-input').val());
    jQuery('#nc-triage-has-image').val(jQuery('#nc-filter-has-image').val());
    jQuery('#nc-triage-orderby').val(jQuery('#nc-filter-orderby').val());
    var sourceVal = $triageSource.val();
    if (sourceVal) {
        NC.loadTainacanFacets(sourceVal, 'triage');
    }
};

jQuery('#nc-triage-source').on('change', function() {
    NC.loadTainacanFacets(jQuery(this).val(), 'triage');
});

NC.triageGetFilterParams = function() {
    var params = '';
    var search = jQuery('#nc-triage-search').val();
    if (search) params += '&search=' + encodeURIComponent(search);
    var source = jQuery('#nc-triage-source').val();
    if (source) params += '&source_id=' + source;
    var hasImage = jQuery('#nc-triage-has-image').val();
    if (hasImage !== '') params += '&has_image=' + hasImage;
    var orderby = jQuery('#nc-triage-orderby').val();
    if (orderby && orderby !== 'id') params += '&orderby=' + orderby + '&order=ASC';
    jQuery('.nc-triage-facet-select').each(function() {
        var val = jQuery(this).val();
        if (val) {
            params += '&meta_' + jQuery(this).data('slug') + '=' + encodeURIComponent(val);
        }
    });
    return params;
};

NC.triageLoadItems = function() {
    var apiPath = ncData.apiUrl + '/items?curated=0&per_page=200';
    apiPath += NC.triageGetFilterParams();

    jQuery('#nc-triage-grid').html(
        '<div class="nc-triage-loading"><div class="nc-spinner"></div><p>Carregando itens...</p></div>'
    );

    if (!jQuery('#nc-triage-overlay').is(':visible')) {
        jQuery('#nc-triage-overlay').fadeIn(200);
        jQuery('body').css('overflow', 'hidden');
    }

    NC._triageActive = true;
    NC._triageApproved = 0;
    NC._triageUndoStack = [];
    NC._triageSelectedIds = {};

    wp.apiFetch({path: apiPath}).then(function(data) {
        if (!data.items || data.items.length === 0) {
            NC._triageItems = [];
            jQuery('#nc-triage-grid').html(
                '<div class="nc-triage-empty">' +
                    '<span class="dashicons dashicons-inbox"></span>' +
                    '<h3>Nenhum item encontrado</h3>' +
                    '<p>Tente ajustar os filtros acima.</p>' +
                '</div>'
            );
            NC.triageUpdateFilterInfo(0);
            NC.triageUpdateHeader();
            return;
        }
        NC._triageItems = data.items;
        NC.triageUpdateFilterInfo(data.total || data.items.length);
        NC.triageRenderGrid();
    }).catch(function(e) {
        NC.showNotice('error', 'Erro ao carregar itens: ' + (e.message || ''));
        NC._triageActive = false;
    });
};

NC.triageRenderGrid = function() {
    var html = '';
    NC._triageItems.forEach(function(item) {
        var isSelected = !!NC._triageSelectedIds[item.id];
        html += '<div class="nc-triage-tile' + (isSelected ? ' nc-triage-tile-selected' : '') + '" data-triage-id="' + item.id + '">';
        html += '<div class="nc-triage-tile-select" onclick="NC.triageToggleItem(' + item.id + ', event)">';
        html += '<input type="checkbox" class="nc-triage-cb"' + (isSelected ? ' checked' : '') + '>';
        html += '</div>';
        if (item.image_url) {
            html += '<div class="nc-triage-tile-img" onclick="NC.triagePreviewItem(' + item.id + ')">' +
                '<img src="' + NC.escapeHtml(item.image_url) + '" alt="" loading="lazy"></div>';
        } else {
            html += '<div class="nc-triage-tile-img nc-triage-tile-noimg" onclick="NC.triagePreviewItem(' + item.id + ')">' +
                '<span class="dashicons dashicons-format-image"></span></div>';
        }
        html += '<div class="nc-triage-tile-info">';
        html += '<h4 class="nc-triage-tile-title" onclick="NC.triagePreviewItem(' + item.id + ')">' + NC.escapeHtml(item.title.length > 60 ? item.title.substring(0, 60) + '...' : item.title) + '</h4>';
        html += '<div class="nc-triage-tile-meta">';
        if (item.author) html += '<span>' + NC.escapeHtml(item.author) + '</span>';
        if (item.source_name) html += '<span>' + NC.escapeHtml(item.source_name) + '</span>';
        html += '</div>';
        html += '</div>';
        html += '<div class="nc-triage-tile-actions">';
        html += '<button class="nc-triage-tile-btn nc-triage-tile-approve" onclick="NC.triageApproveOne(' + item.id + ')" title="Aprovar">' +
            '<span class="dashicons dashicons-yes-alt"></span></button>';
        html += '<a href="' + NC.escapeHtml(item.url) + '" target="_blank" class="nc-triage-tile-btn" title="Ver original">' +
            '<span class="dashicons dashicons-external"></span></a>';
        html += '</div></div>';
    });
    jQuery('#nc-triage-grid').html(html);
    NC.triageUpdateHeader();
    NC.triageUpdateBatchBar();
};

NC.triageToggleItem = function(id, e) {
    if (e) e.stopPropagation();
    if (NC._triageSelectedIds[id]) {
        delete NC._triageSelectedIds[id];
    } else {
        NC._triageSelectedIds[id] = true;
    }
    var $tile = jQuery('[data-triage-id="' + id + '"]');
    $tile.toggleClass('nc-triage-tile-selected');
    $tile.find('.nc-triage-cb').prop('checked', !!NC._triageSelectedIds[id]);
    NC.triageUpdateBatchBar();
};

NC.triageSelectAll = function() {
    NC._triageItems.forEach(function(item) {
        NC._triageSelectedIds[item.id] = true;
    });
    jQuery('.nc-triage-tile').addClass('nc-triage-tile-selected');
    jQuery('.nc-triage-cb').prop('checked', true);
    NC.triageUpdateBatchBar();
};

NC.triageClearSelection = function() {
    NC._triageSelectedIds = {};
    jQuery('.nc-triage-tile').removeClass('nc-triage-tile-selected');
    jQuery('.nc-triage-cb').prop('checked', false);
    NC.triageUpdateBatchBar();
};

NC.triageUpdateBatchBar = function() {
    var count = Object.keys(NC._triageSelectedIds).length;
    if (count > 0) {
        jQuery('#nc-triage-batch-bar').slideDown(200);
        jQuery('#nc-triage-batch-num').text(count);
    } else {
        jQuery('#nc-triage-batch-bar').slideUp(200);
    }
};

NC.triageApproveOne = function(id) {
    NC._triageUndoStack.push({ ids: [id], action: 'approve' });
    wp.apiFetch({ path: ncData.apiUrl + '/items/' + id + '/curate', method: 'POST' });
    NC._triageApproved++;
    delete NC._triageSelectedIds[id];
    var $tile = jQuery('[data-triage-id="' + id + '"]');
    $tile.addClass('nc-triage-tile-approved');
    setTimeout(function() { $tile.slideUp(200, function() { jQuery(this).remove(); }); }, 300);
    NC._triageItems = NC._triageItems.filter(function(i) { return i.id !== id; });
    NC.triageUpdateHeader();
    NC.triageUpdateBatchBar();

    var $counter = jQuery('#nc-uncurated-count');
    $counter.text(Math.max(0, (parseInt($counter.text()) || 0) - 1));
    jQuery('#nc-curated-count').text((parseInt(jQuery('#nc-curated-count').text()) || 0) + 1);
};

NC.triageBatchApprove = function() {
    var ids = Object.keys(NC._triageSelectedIds);
    if (ids.length === 0) return;

    NC._triageUndoStack.push({ ids: ids.slice(), action: 'approve' });

    var promises = ids.map(function(id) {
        return wp.apiFetch({ path: ncData.apiUrl + '/items/' + id + '/curate', method: 'POST' });
    });

    ids.forEach(function(id) {
        var $tile = jQuery('[data-triage-id="' + id + '"]');
        $tile.addClass('nc-triage-tile-approved');
        setTimeout(function() { $tile.slideUp(200, function() { jQuery(this).remove(); }); }, 300);
    });

    NC._triageApproved += ids.length;
    NC._triageItems = NC._triageItems.filter(function(i) { return !NC._triageSelectedIds[i.id]; });
    NC._triageSelectedIds = {};

    Promise.all(promises).then(function() {
        NC.showNotice('success', ids.length + ' itens aprovados!');
    });

    NC.triageUpdateHeader();
    NC.triageUpdateBatchBar();

    var $counter = jQuery('#nc-uncurated-count');
    $counter.text(Math.max(0, (parseInt($counter.text()) || 0) - ids.length));
    jQuery('#nc-curated-count').text((parseInt(jQuery('#nc-curated-count').text()) || 0) + ids.length);
};

NC.triageUndo = function() {
    if (NC._triageUndoStack.length === 0) return;
    var last = NC._triageUndoStack.pop();
    NC.showNotice('info', 'Desfazendo ' + last.ids.length + ' aprovação(ões)...');
    NC._triageApproved -= last.ids.length;
    // Reload to restore undone items
    NC.triageLoadItems();
};

NC.triagePreviewItem = function(id) {
    var items = NC._triageItems;
    NC._lightboxItems = items;
    NC._lightboxIndex = items.findIndex(function(i) { return i.id === id; });
    if (NC._lightboxIndex === -1) NC._lightboxIndex = 0;
    NC.showLightbox();
};

NC.triageUpdateHeader = function() {
    var remaining = NC._triageItems.length;
    jQuery('#nc-triage-counter').text(remaining + ' restantes');
    jQuery('#nc-triage-approved-count').text(NC._triageApproved);
    jQuery('#nc-triage-skipped-count').text(Object.keys(NC._triageSelectedIds).length);
    var total = remaining + NC._triageApproved;
    var pct = total > 0 ? ((NC._triageApproved / total) * 100) : 0;
    jQuery('#nc-triage-progress-fill').css('width', pct + '%');
};

NC.triageUpdateFilterInfo = function(total) {
    var parts = [];
    var search = jQuery('#nc-triage-search').val();
    if (search) parts.push('busca: "' + NC.escapeHtml(search) + '"');
    var sourceText = jQuery('#nc-triage-source option:selected').text();
    var sourceVal = jQuery('#nc-triage-source').val();
    if (sourceVal) parts.push('fonte: ' + NC.escapeHtml(sourceText));
    var hasImage = jQuery('#nc-triage-has-image').val();
    if (hasImage === '1') parts.push('com imagem');
    if (hasImage === '0') parts.push('sem imagem');
    jQuery('.nc-triage-facet-select').each(function() {
        var val = jQuery(this).val();
        if (val) {
            var name = jQuery(this).attr('title') || jQuery(this).data('slug');
            parts.push(NC.escapeHtml(name) + ': ' + NC.escapeHtml(val.length > 25 ? val.substring(0, 25) + '...' : val));
        }
    });
    var html = '<span class="nc-triage-filter-total">' + total + ' itens encontrados</span>';
    if (parts.length > 0) {
        html += '<span class="nc-triage-filter-tags">';
        parts.forEach(function(p) {
            html += '<span class="nc-triage-filter-tag">' + p + '</span>';
        });
        html += '</span>';
    }
    jQuery('#nc-triage-filter-info').html(html);
};

NC.triageApplyFilters = function() {
    NC.triageLoadItems();
};

NC.triageClearFilters = function() {
    jQuery('#nc-triage-search').val('');
    jQuery('#nc-triage-source').val('');
    jQuery('#nc-triage-has-image').val('');
    jQuery('#nc-triage-orderby').val('id');
    jQuery('.nc-triage-facet-select').val('');
    jQuery('#nc-triage-tainacan-facets').hide().empty();
    NC.triageLoadItems();
};

NC.closeTriageMode = function() {
    NC._triageActive = false;
    jQuery('#nc-triage-overlay').fadeOut(200);
    jQuery('body').css('overflow', '');
    NC.resetAndLoad();
    wp.apiFetch({path: ncData.apiUrl + '/items/stats'}).then(function(stats) {
        jQuery('#nc-uncurated-count').text(stats.uncurated || 0);
        jQuery('#nc-curated-count').text(stats.curated || 0);
    });
};

// Triage keyboard shortcuts
jQuery(document).on('keydown', function(e) {
    if (!NC._triageActive) return;
    if (jQuery('#nc-lightbox').is(':visible')) return;

    // Don't capture shortcuts when typing in triage filter inputs
    var tag = (e.target.tagName || '').toLowerCase();
    if (tag === 'input' || tag === 'select' || tag === 'textarea') {
        if (e.key === 'Enter' && jQuery(e.target).closest('.nc-triage-filters').length) {
            e.preventDefault();
            NC.triageApplyFilters();
        }
        return;
    }

    if (e.key === 'a' || e.key === 'A') {
        e.preventDefault();
        NC.triageSelectAll();
    } else if (e.key === 'k' || e.key === 'K') {
        e.preventDefault();
        NC.triageBatchApprove();
    } else if (e.key === 'z' || e.key === 'Z') {
        e.preventDefault();
        NC.triageUndo();
    } else if (e.key === 'Escape') {
        NC.closeTriageMode();
    } else if (e.key === 'f' || e.key === 'F') {
        e.preventDefault();
        jQuery('#nc-triage-search').focus();
    } else if (e.key === 'c' || e.key === 'C') {
        e.preventDefault();
        NC.triageClearSelection();
    }
});

// ========== Add to Collection Modal ==========

NC.openAddToCollectionModal = function() {
    var ids = Object.keys(NC._selectedItemsMap);
    if (ids.length === 0) {
        var $checked = jQuery('.nc-item-checkbox:checked');
        if ($checked.length === 0) {
            NC.showNotice('warning', NC.__('select_at_least_one', 'Selecione ao menos um item'));
            return;
        }
        ids = $checked.map(function() { return jQuery(this).val(); }).get();
    }
    if (!jQuery('#nc-add-collection-modal').length) { NC.createAddToCollectionModal(); }
    NC._selectedItemIds = ids;
    jQuery('#nc-collection-item-count').text(NC._selectedItemIds.length);
    NC.loadCollectionDropdown();
    NC.openModal('nc-add-collection-modal');
};

NC.createAddToCollectionModal = function() {
    var html =
    '<div id="nc-add-collection-modal" class="nc-modal-overlay">' +
        '<div class="nc-modal" style="max-width:500px;">' +
            '<div class="nc-modal-header">' +
                '<h3 class="nc-modal-title"><span class="dashicons dashicons-portfolio" style="color:var(--nc-accent);"></span> ' +
                    NC.__('add_to_collection', 'Adicionar à Coleção') + '</h3>' +
                '<button class="nc-modal-close" onclick="NC.closeModal(\'nc-add-collection-modal\')">&times;</button>' +
            '</div>' +
            '<div class="nc-modal-body">' +
                '<div class="nc-notice nc-notice-info" style="margin-bottom:20px;"><span class="dashicons dashicons-info"></span>' +
                    '<span><strong id="nc-collection-item-count">0</strong> ' + NC.__('items_selected', 'itens selecionados') + '</span></div>' +
                '<div class="nc-form-group"><label class="nc-form-label">' + NC.__('select_collection', 'Selecionar Coleção') + '</label>' +
                    '<select id="nc-collection-select" class="nc-form-control"><option value="">' + NC.__('loading', 'Carregando...') + '</option></select></div>' +
                '<div class="nc-form-group" id="nc-new-collection-fields" style="display:none;">' +
                    '<label class="nc-form-label">' + NC.__('collection_name', 'Nome da Coleção') + ' *</label>' +
                    '<input type="text" id="nc-new-collection-name" class="nc-form-control" placeholder="' + NC.__('collection_name_placeholder', 'Ex: Semana dos Museus 2026') + '">' +
                    '<div style="margin-top:10px;"><label class="nc-form-label">' + NC.__('description', 'Descrição') + '</label>' +
                    '<textarea id="nc-new-collection-desc" class="nc-form-control" rows="2"></textarea></div></div>' +
            '</div>' +
            '<div class="nc-modal-footer">' +
                '<button class="nc-button nc-button-secondary" onclick="NC.closeModal(\'nc-add-collection-modal\')">' + NC.__('cancel', 'Cancelar') + '</button>' +
                '<button class="nc-button nc-button-primary" onclick="NC.submitAddToCollection()">' +
                    '<span class="dashicons dashicons-plus-alt2"></span> ' + NC.__('add', 'Adicionar') + '</button>' +
            '</div></div></div>';
    jQuery('body').append(html);
    jQuery('#nc-collection-select').on('change', function() {
        jQuery('#nc-new-collection-fields').toggle(jQuery(this).val() === 'new');
    });
};

NC.loadCollectionDropdown = function() {
    var $s = jQuery('#nc-collection-select');
    $s.html('<option value="">' + NC.__('loading', 'Carregando...') + '</option>');
    wp.apiFetch({path: ncData.apiUrl + '/collections?status=draft&per_page=100'}).then(function(data) {
        $s.html('<option value="">' + NC.__('select_collection_option', '-- Selecione --') + '</option>');
        if (data.collections) { data.collections.forEach(function(c) {
            $s.append('<option value="' + c.id + '">' + NC.escapeHtml(c.name) + ' (' + c.items_count + ' itens)</option>');
        }); }
        $s.append('<option value="new">' + NC.__('create_new_collection', '+ Criar nova coleção') + '</option>');
    }).catch(function() { $s.html('<option value="new">' + NC.__('create_new_collection', '+ Criar nova coleção') + '</option>'); });
};

NC.submitAddToCollection = function() {
    var cid = jQuery('#nc-collection-select').val();
    var ids = NC._selectedItemIds || [];
    if (!cid) { NC.showNotice('warning', NC.__('select_a_collection', 'Selecione uma coleção')); return; }
    if (cid === 'new') {
        var name = jQuery('#nc-new-collection-name').val();
        if (!name) { NC.showNotice('warning', NC.__('collection_name_required', 'Nome da coleção é obrigatório')); return; }
        wp.apiFetch({ path: ncData.apiUrl + '/collections', method: 'POST',
            data: { name: name, description: jQuery('#nc-new-collection-desc').val() || '' }
        }).then(function(col) { NC.addItemsToExistingCollection(col.id, ids); })
        .catch(function(e) { NC.showNotice('error', NC.__('create_collection_error', 'Erro ao criar coleção: ') + (e.message || '')); });
    } else {
        NC.addItemsToExistingCollection(parseInt(cid), ids);
    }
};

NC.addItemsToExistingCollection = function(cid, ids) {
    wp.apiFetch({ path: ncData.apiUrl + '/collections/' + cid + '/items', method: 'POST',
        data: { item_ids: ids.map(function(i) { return parseInt(i); }) }
    }).then(function(r) {
        NC.showNotice('success', (r.added || 0) + ' ' + NC.__('items_added_to_collection', 'itens adicionados à coleção!'));
        NC.closeModal('nc-add-collection-modal');
    }).catch(function(e) { NC.showNotice('error', NC.__('add_to_collection_error', 'Erro ao adicionar: ') + (e.message || '')); });
};
</script>
