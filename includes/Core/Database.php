<?php
/**
 * Gerencia o esquema do banco de dados
 *
 * @package NewsmastCurator
 * @subpackage Core
 */

namespace NewsmastCurator\Core;

class Database {
    /**
     * Instância do wpdb
     *
     * @var \wpdb
     */
    private $wpdb;

    /**
     * Nomes das tabelas
     *
     * @var array
     */
    private $tables = [];

    /**
     * Versão do esquema do banco
     *
     * @var string
     */
    private $db_version = '1.2.0';

    /**
     * Construtor
     */
    public function __construct() {
        global $wpdb;
        $this->wpdb = $wpdb;

        // Define nomes das tabelas com prefixo
        $this->tables = [
            'sources' => $wpdb->prefix . 'nc_sources',
            'items' => $wpdb->prefix . 'nc_items',
            'publications' => $wpdb->prefix . 'nc_publications',
            'logs' => $wpdb->prefix . 'nc_logs',
            'collections' => $wpdb->prefix . 'nc_collections',
            'collection_items' => $wpdb->prefix . 'nc_collection_items',
            'mastodon_accounts' => $wpdb->prefix . 'nc_mastodon_accounts',
        ];
    }

    /**
     * Obtém nome de uma tabela
     *
     * @param string $table Nome da tabela (sources, items, publications, logs)
     * @return string Nome completo da tabela com prefixo
     */
    public function get_table_name($table) {
        return $this->tables[$table] ?? '';
    }

    /**
     * Obtém todas as tabelas
     *
     * @return array Array associativo com nomes das tabelas
     */
    public function get_tables() {
        return $this->tables;
    }

    /**
     * Instala/atualiza o esquema do banco
     *
     * @return bool True se instalação foi bem-sucedida
     */
    public function install() {
        $current_version = get_option('nc_db_version', '0');

        if (version_compare($current_version, $this->db_version, '<')) {
            $this->create_sources_table();
            $this->create_items_table();
            $this->create_publications_table();
            $this->create_logs_table();
            $this->create_collections_table();
            $this->create_collection_items_table();
            $this->create_mastodon_accounts_table();

            // Run migrations for existing tables
            $this->migrate_sources_auto_collect();
            $this->migrate_items_collection_type();
            $this->migrate_publications_mastodon_account();

            update_option('nc_db_version', $this->db_version);

            return true;
        }

        return false;
    }

    /**
     * Cria tabela de fontes
     *
     * @return void
     */
    private function create_sources_table() {
        $table_name = $this->tables['sources'];
        $charset_collate = $this->wpdb->get_charset_collate();

        $sql = "CREATE TABLE {$table_name} (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            name VARCHAR(255) NOT NULL,
            connector_type VARCHAR(50) NOT NULL,
            url VARCHAR(500) NOT NULL,
            config LONGTEXT,
            status VARCHAR(20) DEFAULT 'active',
            last_collection DATETIME NULL,
            last_error TEXT NULL,
            created_at DATETIME NOT NULL,
            updated_at DATETIME NOT NULL,
            PRIMARY KEY (id),
            KEY idx_connector_type (connector_type),
            KEY idx_status (status),
            KEY idx_last_collection (last_collection)
        ) {$charset_collate};";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }

    /**
     * Cria tabela de itens
     *
     * @return void
     */
    private function create_items_table() {
        $table_name = $this->tables['items'];
        $sources_table = $this->tables['sources'];
        $charset_collate = $this->wpdb->get_charset_collate();

        $sql = "CREATE TABLE {$table_name} (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            source_id BIGINT(20) UNSIGNED NOT NULL,
            external_id VARCHAR(255) NOT NULL,
            title TEXT NOT NULL,
            content LONGTEXT NOT NULL,
            excerpt TEXT,
            url VARCHAR(500) NOT NULL,
            image_url VARCHAR(500) NULL,
            image_local_id BIGINT(20) NULL,
            author VARCHAR(255) NULL,
            published_date DATETIME NULL,
            metadata LONGTEXT,
            hash VARCHAR(64) NOT NULL,
            curated TINYINT(1) DEFAULT 0,
            curated_by BIGINT(20) NULL,
            curated_at DATETIME NULL,
            collected_at DATETIME NOT NULL,
            PRIMARY KEY (id),
            UNIQUE KEY idx_source_external (source_id, external_id),
            KEY idx_source_id (source_id),
            KEY idx_hash (hash),
            KEY idx_curated (curated),
            KEY idx_collected_at (collected_at),
            KEY idx_published_date (published_date)
        ) {$charset_collate};";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }

    /**
     * Cria tabela de publicações
     *
     * @return void
     */
    private function create_publications_table() {
        $table_name = $this->tables['publications'];
        $items_table = $this->tables['items'];
        $charset_collate = $this->wpdb->get_charset_collate();

        $sql = "CREATE TABLE {$table_name} (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            item_id BIGINT(20) UNSIGNED NOT NULL,
            status VARCHAR(20) DEFAULT 'scheduled',
            scheduled_for DATETIME NOT NULL,
            content TEXT NOT NULL,
            media_ids TEXT NULL,
            mastodon_id VARCHAR(100) NULL,
            mastodon_url VARCHAR(500) NULL,
            attempt_count INT DEFAULT 0,
            last_error TEXT NULL,
            published_at DATETIME NULL,
            published_by BIGINT(20) NULL,
            created_at DATETIME NOT NULL,
            updated_at DATETIME NOT NULL,
            PRIMARY KEY (id),
            KEY idx_item_id (item_id),
            KEY idx_status (status),
            KEY idx_scheduled_for (scheduled_for),
            KEY idx_published_at (published_at)
        ) {$charset_collate};";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }

    /**
     * Cria tabela de logs
     *
     * @return void
     */
    private function create_logs_table() {
        $table_name = $this->tables['logs'];
        $charset_collate = $this->wpdb->get_charset_collate();

        $sql = "CREATE TABLE {$table_name} (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            type VARCHAR(50) NOT NULL,
            level VARCHAR(20) NOT NULL,
            message TEXT NOT NULL,
            context LONGTEXT,
            related_id BIGINT(20) NULL,
            related_type VARCHAR(50) NULL,
            user_id BIGINT(20) NULL,
            created_at DATETIME NOT NULL,
            PRIMARY KEY (id),
            KEY idx_type (type),
            KEY idx_level (level),
            KEY idx_created_at (created_at),
            KEY idx_related (related_type, related_id)
        ) {$charset_collate};";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }

    /**
     * Cria tabela de coleções
     *
     * @return void
     */
    private function create_collections_table() {
        $table_name = $this->tables['collections'];
        $charset_collate = $this->wpdb->get_charset_collate();

        $sql = "CREATE TABLE {$table_name} (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            name VARCHAR(255) NOT NULL,
            description TEXT NULL,
            color VARCHAR(7) DEFAULT '#457B9D',
            status VARCHAR(20) DEFAULT 'draft',
            scheduled_for DATETIME NULL,
            interval_minutes INT DEFAULT 60,
            created_by BIGINT(20) UNSIGNED NULL,
            created_at DATETIME NOT NULL,
            updated_at DATETIME NOT NULL,
            PRIMARY KEY (id),
            KEY idx_status (status),
            KEY idx_scheduled_for (scheduled_for),
            KEY idx_created_by (created_by)
        ) {$charset_collate};";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }

    /**
     * Cria tabela pivot de itens de coleções
     *
     * @return void
     */
    private function create_collection_items_table() {
        $table_name = $this->tables['collection_items'];
        $charset_collate = $this->wpdb->get_charset_collate();

        $sql = "CREATE TABLE {$table_name} (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            collection_id BIGINT(20) UNSIGNED NOT NULL,
            item_id BIGINT(20) UNSIGNED NOT NULL,
            sort_order INT DEFAULT 0,
            added_at DATETIME NOT NULL,
            PRIMARY KEY (id),
            UNIQUE KEY idx_collection_item (collection_id, item_id),
            KEY idx_collection_id (collection_id),
            KEY idx_item_id (item_id),
            KEY idx_sort_order (sort_order)
        ) {$charset_collate};";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }

    /**
     * Cria tabela de contas Mastodon
     *
     * @return void
     */
    private function create_mastodon_accounts_table() {
        $table_name = $this->tables['mastodon_accounts'];
        $charset_collate = $this->wpdb->get_charset_collate();

        $sql = "CREATE TABLE {$table_name} (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            name VARCHAR(255) NOT NULL,
            instance_url VARCHAR(500) NOT NULL,
            access_token VARCHAR(500) NOT NULL,
            username VARCHAR(255) NULL,
            is_default TINYINT(1) DEFAULT 0,
            status VARCHAR(20) DEFAULT 'active',
            last_error TEXT NULL,
            created_at DATETIME NOT NULL,
            updated_at DATETIME NOT NULL,
            PRIMARY KEY (id),
            KEY idx_status (status),
            KEY idx_is_default (is_default)
        ) {$charset_collate};";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }

    /**
     * Migração: adiciona campos de coleta automática à tabela sources
     *
     * @return void
     */
    private function migrate_sources_auto_collect() {
        $table = $this->tables['sources'];
        $row = $this->wpdb->get_row("SHOW COLUMNS FROM {$table} LIKE 'auto_collect'");
        if (!$row) {
            $this->wpdb->query("ALTER TABLE {$table} ADD COLUMN auto_collect TINYINT(1) DEFAULT 0 AFTER last_error");
            $this->wpdb->query("ALTER TABLE {$table} ADD COLUMN collect_interval VARCHAR(20) DEFAULT 'hourly' AFTER auto_collect");
        }
    }

    /**
     * Migração: adiciona campo collection_type à tabela items
     *
     * @return void
     */
    private function migrate_items_collection_type() {
        $table = $this->tables['items'];
        $row = $this->wpdb->get_row("SHOW COLUMNS FROM {$table} LIKE 'collection_type'");
        if (!$row) {
            $this->wpdb->query("ALTER TABLE {$table} ADD COLUMN collection_type VARCHAR(20) DEFAULT 'manual' AFTER collected_at");
            $this->wpdb->query("ALTER TABLE {$table} ADD KEY idx_collection_type (collection_type)");
        }
    }

    /**
     * Migração: adiciona campo mastodon_account_id à tabela publications
     *
     * @return void
     */
    private function migrate_publications_mastodon_account() {
        $table = $this->tables['publications'];
        $row = $this->wpdb->get_row("SHOW COLUMNS FROM {$table} LIKE 'mastodon_account_id'");
        if (!$row) {
            $this->wpdb->query("ALTER TABLE {$table} ADD COLUMN mastodon_account_id BIGINT(20) UNSIGNED NULL AFTER item_id");
            $this->wpdb->query("ALTER TABLE {$table} ADD KEY idx_mastodon_account_id (mastodon_account_id)");
        }
    }

    /**
     * Obtém versão atual do banco
     *
     * @return string
     */
    public function get_version() {
        return get_option('nc_db_version', '0');
    }

    /**
     * Verifica se precisa atualizar o banco
     *
     * @return bool
     */
    public function needs_upgrade() {
        $current_version = $this->get_version();
        return version_compare($current_version, $this->db_version, '<');
    }

    /**
     * Remove todas as tabelas do plugin
     * Usado apenas no uninstall.php
     *
     * @return void
     */
    public function drop_tables() {
        foreach (array_reverse($this->tables) as $table) {
            $this->wpdb->query("DROP TABLE IF EXISTS {$table}");
        }

        delete_option('nc_db_version');
    }
}
