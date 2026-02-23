<?php
/**
 * Model de Coleção
 *
 * @package NewsmastCurator
 * @subpackage Models
 */

namespace NewsmastCurator\Models;

class Collection {
    const STATUS_DRAFT = 'draft';
    const STATUS_SCHEDULED = 'scheduled';
    const STATUS_PUBLISHED = 'published';
    const STATUS_PARTIAL = 'partial';

    private $id = 0;
    private $name = '';
    private $description = '';
    private $color = '#457B9D';
    private $status = 'draft';
    private $scheduled_for = null;
    private $interval_minutes = 60;
    private $created_by = null;
    private $created_at = '';
    private $updated_at = '';

    // Propriedades transientes (populadas por JOIN)
    private $items_count = 0;
    private $published_count = 0;

    // Getters
    public function get_id() { return $this->id; }
    public function get_name() { return $this->name; }
    public function get_description() { return $this->description; }
    public function get_color() { return $this->color; }
    public function get_status() { return $this->status; }
    public function get_scheduled_for() { return $this->scheduled_for; }
    public function get_interval_minutes() { return $this->interval_minutes; }
    public function get_created_by() { return $this->created_by; }
    public function get_created_at() { return $this->created_at; }
    public function get_updated_at() { return $this->updated_at; }
    public function get_items_count() { return $this->items_count; }
    public function get_published_count() { return $this->published_count; }

    // Setters
    public function set_id($id) { $this->id = (int) $id; }
    public function set_name($name) { $this->name = sanitize_text_field($name); }
    public function set_description($desc) { $this->description = sanitize_textarea_field($desc); }

    public function set_color($color) {
        if (preg_match('/^#[a-fA-F0-9]{6}$/', $color)) {
            $this->color = $color;
        } else {
            $this->color = '#457B9D';
        }
    }

    public function set_status($status) { $this->status = sanitize_text_field($status); }
    public function set_scheduled_for($datetime) { $this->scheduled_for = $datetime; }
    public function set_interval_minutes($minutes) { $this->interval_minutes = max(1, (int) $minutes); }
    public function set_created_by($user_id) { $this->created_by = $user_id ? (int) $user_id : null; }
    public function set_created_at($datetime) { $this->created_at = $datetime; }
    public function set_updated_at($datetime) { $this->updated_at = $datetime; }
    public function set_items_count($count) { $this->items_count = (int) $count; }
    public function set_published_count($count) { $this->published_count = (int) $count; }

    // Status helpers
    public function is_draft() { return $this->status === self::STATUS_DRAFT; }
    public function is_scheduled() { return $this->status === self::STATUS_SCHEDULED; }
    public function is_published() { return $this->status === self::STATUS_PUBLISHED; }
    public function is_partial() { return $this->status === self::STATUS_PARTIAL; }

    /**
     * Validação
     *
     * @return true|array True se válido, array de erros caso contrário
     */
    private $errors = [];

    public function validate() {
        $this->errors = [];

        if (empty($this->name)) {
            $this->errors['name'] = __('Nome é obrigatório.', 'newsmast-curator');
        }

        $valid_statuses = [self::STATUS_DRAFT, self::STATUS_SCHEDULED, self::STATUS_PUBLISHED, self::STATUS_PARTIAL];
        if (!in_array($this->status, $valid_statuses, true)) {
            $this->errors['status'] = __('Status inválido.', 'newsmast-curator');
        }

        return empty($this->errors) ? true : $this->errors;
    }

    public function get_errors() { return $this->errors; }

    /**
     * Cria instância a partir de linha do banco
     *
     * @param object $row
     * @return Collection
     */
    public static function from_row($row) {
        $collection = new self();

        $collection->set_id($row->id ?? 0);
        $collection->set_name($row->name ?? '');
        $collection->set_description($row->description ?? '');
        $collection->set_color($row->color ?? '#457B9D');
        $collection->set_status($row->status ?? 'draft');
        $collection->set_scheduled_for($row->scheduled_for ?? null);
        $collection->set_interval_minutes($row->interval_minutes ?? 60);
        $collection->set_created_by($row->created_by ?? null);
        $collection->set_created_at($row->created_at ?? current_time('mysql'));
        $collection->set_updated_at($row->updated_at ?? current_time('mysql'));

        if (isset($row->items_count)) $collection->set_items_count($row->items_count);
        if (isset($row->published_count)) $collection->set_published_count($row->published_count);

        return $collection;
    }

    /**
     * Converte para array (para banco)
     *
     * @return array
     */
    public function to_array() {
        $data = [
            'name' => $this->name,
            'description' => $this->description,
            'color' => $this->color,
            'status' => $this->status,
            'scheduled_for' => $this->scheduled_for,
            'interval_minutes' => $this->interval_minutes,
            'created_by' => $this->created_by,
            'updated_at' => current_time('mysql'),
        ];

        if ($this->id === 0) {
            $data['created_at'] = current_time('mysql');
        }

        return $data;
    }

    /**
     * Converte para resposta de API
     *
     * @return array
     */
    public function to_api_response() {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'color' => $this->color,
            'status' => $this->status,
            'status_label' => $this->get_status_label(),
            'scheduled_for' => $this->scheduled_for,
            'interval_minutes' => $this->interval_minutes,
            'created_by' => $this->created_by,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'items_count' => $this->items_count,
            'published_count' => $this->published_count,
        ];
    }

    /**
     * Obtém label traduzido do status
     *
     * @return string
     */
    private function get_status_label() {
        $labels = [
            'draft' => __('Rascunho', 'newsmast-curator'),
            'scheduled' => __('Agendada', 'newsmast-curator'),
            'published' => __('Publicada', 'newsmast-curator'),
            'partial' => __('Parcial', 'newsmast-curator'),
        ];
        return $labels[$this->status] ?? $this->status;
    }
}
