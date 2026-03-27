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
}
