<?php
/**
 * Repository de Itens
 *
 * @package NewsmastCurator
 * @subpackage Repositories
 */

namespace NewsmastCurator\Repositories;

use NewsmastCurator\Core\Database;
use NewsmastCurator\Models\Item;

class Item_Repository extends Base_Repository {
    /**
     * Construtor
     *
     * @param Database $database
     */
    public function __construct(Database $database) {
        parent::__construct($database);
        $this->table_name = $database->get_table_name('items');
        $this->model_class = Item::class;
    }

    /**
     * Busca itens por fonte
     *
     * @param int $source_id
     * @param array $args
     * @return array
     */
    public function find_by_source($source_id, $args = []) {
        $args['source_id'] = $source_id;
        return $this->find_all($args);
    }

    /**
     * Busca item por hash
     *
     * @param string $hash
     * @return Item|null
     */
    public function find_by_hash($hash) {
        $sql = $this->wpdb->prepare(
            "SELECT * FROM {$this->table_name} WHERE hash = %s LIMIT 1",
            $hash
        );

        $row = $this->wpdb->get_row($sql);

        return $row ? Item::from_row($row) : null;
    }

    /**
     * Busca itens curados
     *
     * @param array $args
     * @return array
     */
    public function find_curated($args = []) {
        $args['curated'] = 1;
        return $this->find_all($args);
    }

    /**
     * Busca itens não curados
     *
     * @param array $args
     * @return array
     */
    public function find_uncurated($args = []) {
        $args['curated'] = 0;
        return $this->find_all($args);
    }

    /**
     * Verifica se item já existe
     *
     * @param int $source_id
     * @param string $external_id
     * @return bool
     */
    public function exists_by_external_id($source_id, $external_id) {
        $sql = $this->wpdb->prepare(
            "SELECT COUNT(*) FROM {$this->table_name}
             WHERE source_id = %d AND external_id = %s",
            $source_id,
            $external_id
        );

        return (int) $this->wpdb->get_var($sql) > 0;
    }

    /**
     * Marca item como curado
     *
     * @param int $id
     * @param int $user_id
     * @return bool
     */
    public function mark_as_curated($id, $user_id) {
        return $this->wpdb->update(
            $this->table_name,
            [
                'curated' => 1,
                'curated_by' => $user_id,
                'curated_at' => current_time('mysql'),
            ],
            ['id' => $id],
            ['%d', '%d', '%s'],
            ['%d']
        ) !== false;
    }

    /**
     * Remove curadoria de item
     *
     * @param int $id
     * @return bool
     */
    public function unmark_curated($id) {
        return $this->wpdb->update(
            $this->table_name,
            [
                'curated' => 0,
                'curated_by' => null,
                'curated_at' => null,
            ],
            ['id' => $id]
        ) !== false;
    }

    /**
     * Obtém estatísticas de curadoria
     *
     * @return array
     */
    public function get_curated_stats() {
        $sql = "SELECT
                    COUNT(*) as total,
                    SUM(CASE WHEN curated = 1 THEN 1 ELSE 0 END) as curated,
                    SUM(CASE WHEN curated = 0 THEN 1 ELSE 0 END) as uncurated
                FROM {$this->table_name}";

        return (array) $this->wpdb->get_row($sql, ARRAY_A);
    }

    /**
     * Obtém estatísticas por fonte
     *
     * @param int $source_id
     * @return array
     */
    public function get_stats_by_source($source_id) {
        $sql = $this->wpdb->prepare(
            "SELECT
                COUNT(*) as total,
                SUM(CASE WHEN curated = 1 THEN 1 ELSE 0 END) as curated,
                SUM(CASE WHEN curated = 0 THEN 1 ELSE 0 END) as uncurated
            FROM {$this->table_name}
            WHERE source_id = %d",
            $source_id
        );

        return (array) $this->wpdb->get_row($sql, ARRAY_A);
    }

    /**
     * Busca itens coletados recentemente
     *
     * @param int $limit
     * @return array
     */
    public function find_recent($limit = 10) {
        $sql = $this->wpdb->prepare(
            "SELECT * FROM {$this->table_name}
             ORDER BY collected_at DESC
             LIMIT %d",
            $limit
        );

        $rows = $this->wpdb->get_results($sql);

        return array_map([Item::class, 'from_row'], $rows);
    }

    /**
     * Estatísticas detalhadas para limpeza
     *
     * @return array
     */
    public function get_cleanup_stats() {
        $pubs_table = $this->database->get_table_name('publications');

        // Items com publicação publicada
        $published_items = (int) $this->wpdb->get_var(
            "SELECT COUNT(DISTINCT i.id) FROM {$this->table_name} i
             INNER JOIN {$pubs_table} p ON p.item_id = i.id AND p.status = 'published'"
        );

        // Items não curados (nunca aprovados)
        $uncurated = (int) $this->wpdb->get_var(
            "SELECT COUNT(*) FROM {$this->table_name} WHERE curated = 0"
        );

        // Items curados mas sem publicação
        $curated_no_pub = (int) $this->wpdb->get_var(
            "SELECT COUNT(*) FROM {$this->table_name} i
             WHERE i.curated = 1
             AND NOT EXISTS (SELECT 1 FROM {$pubs_table} p WHERE p.item_id = i.id)"
        );

        // Items com publicação agendada/processando
        $with_pending = (int) $this->wpdb->get_var(
            "SELECT COUNT(DISTINCT i.id) FROM {$this->table_name} i
             INNER JOIN {$pubs_table} p ON p.item_id = i.id
             AND p.status IN ('scheduled', 'processing')"
        );

        $total = (int) $this->wpdb->get_var("SELECT COUNT(*) FROM {$this->table_name}");

        // Items coletados há mais de 30 dias e não curados
        $old_uncurated = (int) $this->wpdb->get_var(
            $this->wpdb->prepare(
                "SELECT COUNT(*) FROM {$this->table_name}
                 WHERE curated = 0 AND collected_at < %s",
                date('Y-m-d H:i:s', current_time('timestamp') - (30 * DAY_IN_SECONDS))
            )
        );

        return [
            'total' => $total,
            'published_items' => $published_items,
            'uncurated' => $uncurated,
            'curated_no_pub' => $curated_no_pub,
            'with_pending' => $with_pending,
            'old_uncurated' => $old_uncurated,
            'safe_to_delete' => $published_items + $old_uncurated, // Items que podem ser limpos com segurança
        ];
    }

    /**
     * Remove itens já publicados (todas as publicações com status 'published')
     * Retorna contagem de itens removidos
     *
     * @return int
     */
    public function delete_published_items() {
        $pubs_table = $this->database->get_table_name('publications');
        $collection_items_table = $this->database->get_table_name('collection_items');

        // Busca IDs dos itens que foram publicados E não têm publicações pendentes
        $ids = $this->wpdb->get_col(
            "SELECT DISTINCT i.id FROM {$this->table_name} i
             INNER JOIN {$pubs_table} p ON p.item_id = i.id AND p.status = 'published'
             WHERE NOT EXISTS (
                SELECT 1 FROM {$pubs_table} p2
                WHERE p2.item_id = i.id AND p2.status IN ('scheduled', 'processing')
             )"
        );

        if (empty($ids)) return 0;

        $ids_str = implode(',', array_map('intval', $ids));

        // Remove dos collection_items
        $this->wpdb->query("DELETE FROM {$collection_items_table} WHERE item_id IN ({$ids_str})");

        // Remove publicações associadas (já publicadas ou falhadas)
        $this->wpdb->query("DELETE FROM {$pubs_table} WHERE item_id IN ({$ids_str})");

        // Remove itens
        $deleted = $this->wpdb->query("DELETE FROM {$this->table_name} WHERE id IN ({$ids_str})");

        return (int) $deleted;
    }

    /**
     * Remove itens não curados antigos (coletados há mais de X dias)
     *
     * @param int $days_old Dias mínimos de idade
     * @return int Itens removidos
     */
    public function delete_old_uncurated($days_old = 30) {
        $pubs_table = $this->database->get_table_name('publications');
        $collection_items_table = $this->database->get_table_name('collection_items');

        $threshold = date('Y-m-d H:i:s', current_time('timestamp') - ($days_old * DAY_IN_SECONDS));

        $ids = $this->wpdb->get_col(
            $this->wpdb->prepare(
                "SELECT id FROM {$this->table_name}
                 WHERE curated = 0 AND collected_at < %s",
                $threshold
            )
        );

        if (empty($ids)) return 0;

        $ids_str = implode(',', array_map('intval', $ids));
        $this->wpdb->query("DELETE FROM {$collection_items_table} WHERE item_id IN ({$ids_str})");
        $this->wpdb->query("DELETE FROM {$pubs_table} WHERE item_id IN ({$ids_str})");
        $deleted = $this->wpdb->query("DELETE FROM {$this->table_name} WHERE id IN ({$ids_str})");

        return (int) $deleted;
    }

    /**
     * Remove todos os itens não curados
     *
     * @return int Itens removidos
     */
    public function delete_all_uncurated() {
        $pubs_table = $this->database->get_table_name('publications');
        $collection_items_table = $this->database->get_table_name('collection_items');

        $ids = $this->wpdb->get_col(
            "SELECT id FROM {$this->table_name} WHERE curated = 0"
        );

        if (empty($ids)) return 0;

        $ids_str = implode(',', array_map('intval', $ids));
        $this->wpdb->query("DELETE FROM {$collection_items_table} WHERE item_id IN ({$ids_str})");
        $this->wpdb->query("DELETE FROM {$pubs_table} WHERE item_id IN ({$ids_str})");
        $deleted = $this->wpdb->query("DELETE FROM {$this->table_name} WHERE id IN ({$ids_str})");

        return (int) $deleted;
    }

    /**
     * Prepara WHERE com filtros específicos
     *
     * @param array $args
     * @return string
     */
    protected function prepare_where($args) {
        $conditions = [];

        if (isset($args['source_id'])) {
            $conditions[] = $this->wpdb->prepare('source_id = %d', $args['source_id']);
        }

        if (isset($args['curated'])) {
            $conditions[] = $this->wpdb->prepare('curated = %d', $args['curated']);
        }

        if (!empty($args['search'])) {
            $like = '%' . $this->wpdb->esc_like($args['search']) . '%';
            $conditions[] = $this->wpdb->prepare(
                '(title LIKE %s OR content LIKE %s OR author LIKE %s)',
                $like, $like, $like
            );
        }

        if (!empty($args['date_from'])) {
            $conditions[] = $this->wpdb->prepare(
                'collected_at >= %s',
                $args['date_from']
            );
        }

        if (!empty($args['date_to'])) {
            $conditions[] = $this->wpdb->prepare(
                'collected_at <= %s',
                $args['date_to']
            );
        }

        if (!empty($args['author'])) {
            $like = '%' . $this->wpdb->esc_like($args['author']) . '%';
            $conditions[] = $this->wpdb->prepare('author LIKE %s', $like);
        }

        if (isset($args['has_image']) && $args['has_image'] !== '') {
            if ($args['has_image']) {
                $conditions[] = "image_url IS NOT NULL AND image_url != ''";
            } else {
                $conditions[] = "(image_url IS NULL OR image_url = '')";
            }
        }

        if (!empty($args['collection_type'])) {
            $conditions[] = $this->wpdb->prepare('collection_type = %s', $args['collection_type']);
        }

        // Filtros de metadados Tainacan (JSON_EXTRACT ou LIKE fallback)
        if (!empty($args['meta_filters']) && is_array($args['meta_filters'])) {
            foreach ($args['meta_filters'] as $slug => $value) {
                $json_path = '$."' . esc_sql($slug) . '".value_as_string';
                // Tenta JSON_EXTRACT primeiro, com fallback LIKE
                if ($this->supports_json_extract()) {
                    $conditions[] = $this->wpdb->prepare(
                        "JSON_UNQUOTE(JSON_EXTRACT(metadata, %s)) = %s",
                        $json_path, $value
                    );
                } else {
                    $like_pattern = '%"' . $this->wpdb->esc_like($slug) . '"%"value_as_string"%"' . $this->wpdb->esc_like($value) . '"%';
                    $conditions[] = $this->wpdb->prepare('metadata LIKE %s', $like_pattern);
                }
            }
        }

        if (empty($conditions)) {
            return '';
        }

        return 'WHERE ' . implode(' AND ', $conditions);
    }

    /**
     * Obtém lista de autores distintos
     */
    public function get_distinct_authors($args = []) {
        $where = $this->prepare_where($args);
        $sql = "SELECT DISTINCT author FROM {$this->table_name} {$where} AND author IS NOT NULL AND author != '' ORDER BY author ASC LIMIT 200";
        if (empty($where)) {
            $sql = "SELECT DISTINCT author FROM {$this->table_name} WHERE author IS NOT NULL AND author != '' ORDER BY author ASC LIMIT 200";
        }
        return $this->wpdb->get_col($sql);
    }

    /**
     * Verifica se o banco suporta JSON_EXTRACT
     */
    private function supports_json_extract() {
        static $supports = null;
        if ($supports === null) {
            $this->wpdb->suppress_errors(true);
            $this->wpdb->query("SELECT JSON_EXTRACT('{}', '$.test')");
            $supports = empty($this->wpdb->last_error);
            $this->wpdb->suppress_errors(false);
        }
        return $supports;
    }

    /**
     * Extrai valores distintos de um metadado específico para uma fonte
     * Usa JSON_EXTRACT para MySQL 5.7+ / MariaDB 10.2+
     */
    public function get_distinct_metadata_values($source_id, $meta_slug) {
        $json_path = '$."' . esc_sql($meta_slug) . '".value_as_string';

        // Tenta JSON_EXTRACT (MySQL 5.7+ / MariaDB 10.2+)
        $sql = $this->wpdb->prepare(
            "SELECT DISTINCT JSON_UNQUOTE(JSON_EXTRACT(metadata, %s)) AS val
             FROM {$this->table_name}
             WHERE source_id = %d
               AND metadata IS NOT NULL
               AND JSON_EXTRACT(metadata, %s) IS NOT NULL
             ORDER BY val ASC
             LIMIT 100",
            $json_path, $source_id, $json_path
        );

        $this->wpdb->suppress_errors(true);
        $results = $this->wpdb->get_col($sql);
        $this->wpdb->suppress_errors(false);

        if ($this->wpdb->last_error) {
            // Fallback para LIKE (bancos sem JSON_EXTRACT)
            return $this->get_distinct_metadata_values_fallback($source_id, $meta_slug);
        }

        // Filtra valores nulos ou vazios
        return array_values(array_filter($results, function($v) {
            return $v !== null && $v !== '' && $v !== 'null';
        }));
    }

    /**
     * Fallback: extrai valores de metadados via PHP (para MySQL sem JSON support)
     */
    private function get_distinct_metadata_values_fallback($source_id, $meta_slug) {
        $sql = $this->wpdb->prepare(
            "SELECT metadata FROM {$this->table_name}
             WHERE source_id = %d AND metadata IS NOT NULL AND metadata != ''
             LIMIT 500",
            $source_id
        );
        $rows = $this->wpdb->get_col($sql);

        $values = [];
        foreach ($rows as $json) {
            $data = json_decode($json, true);
            if (!is_array($data)) continue;
            if (isset($data[$meta_slug]['value_as_string'])) {
                $val = trim($data[$meta_slug]['value_as_string']);
                if ($val !== '') {
                    $values[$val] = true;
                }
            }
        }

        $result = array_keys($values);
        sort($result);
        return array_slice($result, 0, 100);
    }

    /**
     * Extrai slugs de metadados existentes nos itens de uma fonte (fallback sem API Tainacan)
     */
    public function get_metadata_keys_for_source($source_id) {
        $sql = $this->wpdb->prepare(
            "SELECT metadata FROM {$this->table_name}
             WHERE source_id = %d AND metadata IS NOT NULL AND metadata != ''
             LIMIT 20",
            $source_id
        );
        $rows = $this->wpdb->get_col($sql);

        $keys = [];
        foreach ($rows as $json) {
            $data = json_decode($json, true);
            if (!is_array($data)) continue;
            foreach ($data as $slug => $meta) {
                if (isset($keys[$slug])) continue;
                if (empty($meta['name']) || empty($meta['value_as_string'])) continue;
                // Ignora campos core
                if (in_array($slug, ['title', 'description', 'document'], true)) continue;
                $keys[$slug] = [
                    'slug' => $slug,
                    'name' => $meta['name'],
                    'type' => 'text',
                    'values' => [],
                ];
            }
        }

        return array_values($keys);
    }
}
