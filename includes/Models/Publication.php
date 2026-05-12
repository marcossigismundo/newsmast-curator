<?php
/**
 * Model de Publicação
 *
 * @package NewsmastCurator
 * @subpackage Models
 */

namespace NewsmastCurator\Models;

class Publication {
    private $id = 0;
    private $item_id = 0;
    private $destination_type = 'mastodon';
    private $mastodon_account_id = null;
    private $wp_category_id = null;
    private $wp_post_id = null;
    private $wp_post_url = null;
    private $status = 'scheduled';
    private $scheduled_for = '';
    private $content = '';
    private $alt_text = '';
    private $thread_id = null;
    private $thread_position = null;
    private $media_ids = [];
    private $mastodon_id = null;
    private $mastodon_url = null;
    private $attempt_count = 0;
    private $last_error = null;
    private $published_at = null;
    private $published_by = null;
    private $created_at = '';
    private $updated_at = '';

    // Constantes de status
    const STATUS_SCHEDULED = 'scheduled';
    const STATUS_PROCESSING = 'processing';
    const STATUS_PUBLISHED = 'published';
    const STATUS_FAILED = 'failed';

    // Constantes de destino
    const DESTINATION_MASTODON = 'mastodon';
    const DESTINATION_WORDPRESS = 'wordpress';

    // Getters
    public function get_id() { return $this->id; }
    public function get_item_id() { return $this->item_id; }
    public function get_destination_type() { return $this->destination_type; }
    public function get_mastodon_account_id() { return $this->mastodon_account_id; }
    public function get_wp_category_id() { return $this->wp_category_id; }
    public function get_wp_post_id() { return $this->wp_post_id; }
    public function get_wp_post_url() { return $this->wp_post_url; }
    public function get_status() { return $this->status; }
    public function get_scheduled_for() { return $this->scheduled_for; }
    public function get_content() { return $this->content; }
    public function get_alt_text() { return $this->alt_text; }
    public function get_thread_id() { return $this->thread_id; }
    public function get_thread_position() { return $this->thread_position; }
    public function get_media_ids() { return $this->media_ids; }
    public function get_mastodon_id() { return $this->mastodon_id; }
    public function get_mastodon_url() { return $this->mastodon_url; }
    public function get_attempt_count() { return $this->attempt_count; }
    public function get_last_error() { return $this->last_error; }
    public function get_published_at() { return $this->published_at; }
    public function get_published_by() { return $this->published_by; }
    public function get_created_at() { return $this->created_at; }
    public function get_updated_at() { return $this->updated_at; }

    // Setters
    public function set_id($id) { $this->id = (int) $id; }
    public function set_item_id($id) { $this->item_id = (int) $id; }
    public function set_destination_type($type) {
        $type = sanitize_text_field($type);
        $this->destination_type = in_array($type, [self::DESTINATION_MASTODON, self::DESTINATION_WORDPRESS], true)
            ? $type : self::DESTINATION_MASTODON;
    }
    public function set_mastodon_account_id($id) { $this->mastodon_account_id = $id ? (int) $id : null; }
    public function set_wp_category_id($id) { $this->wp_category_id = $id ? (int) $id : null; }
    public function set_wp_post_id($id) { $this->wp_post_id = $id ? (int) $id : null; }
    public function set_wp_post_url($url) { $this->wp_post_url = $url ? esc_url_raw($url) : null; }
    public function set_status($status) { $this->status = sanitize_text_field($status); }
    public function set_scheduled_for($datetime) { $this->scheduled_for = $datetime; }
    public function set_content($content) { $this->content = sanitize_textarea_field($content); }
    public function set_alt_text($text) { $this->alt_text = sanitize_textarea_field($text); }
    public function set_thread_id($id) { $this->thread_id = $id ? sanitize_text_field($id) : null; }
    public function set_thread_position($pos) { $this->thread_position = $pos !== null ? (int) $pos : null; }

    public function set_media_ids($ids) {
        if (is_string($ids)) {
            $ids = json_decode($ids, true) ?? [];
        }
        $this->media_ids = $ids;
    }

    public function set_mastodon_id($id) { $this->mastodon_id = $id ? sanitize_text_field($id) : null; }
    public function set_mastodon_url($url) { $this->mastodon_url = $url ? esc_url_raw($url) : null; }
    public function set_attempt_count($count) { $this->attempt_count = (int) $count; }
    public function set_last_error($error) { $this->last_error = $error ? sanitize_textarea_field($error) : null; }
    public function set_published_at($datetime) { $this->published_at = $datetime; }
    public function set_published_by($user_id) { $this->published_by = $user_id ? (int) $user_id : null; }
    public function set_created_at($datetime) { $this->created_at = $datetime; }
    public function set_updated_at($datetime) { $this->updated_at = $datetime; }

    /**
     * Verifica se pode tentar novamente
     *
     * @return bool
     */
    public function can_retry() {
        $max_attempts = get_option('nc_max_retry_attempts', 3);
        return $this->attempt_count < $max_attempts;
    }

    /**
     * Incrementa contador de tentativas
     *
     * @return void
     */
    public function increment_attempts() {
        $this->attempt_count++;
    }

    /**
     * Marca como publicada com sucesso
     *
     * @param string $mastodon_id ID do post no Mastodon
     * @param string $mastodon_url URL do post
     * @return void
     */
    public function mark_as_published($mastodon_id, $mastodon_url) {
        $this->status = self::STATUS_PUBLISHED;
        $this->mastodon_id = $mastodon_id;
        $this->mastodon_url = $mastodon_url;
        $this->published_at = current_time('mysql');
        $this->last_error = null;
    }

    /**
     * Marca como publicada no WordPress
     *
     * @param int $post_id ID do post no WordPress
     * @param string $post_url URL do post
     * @return void
     */
    public function mark_as_published_wp($post_id, $post_url) {
        $this->status = self::STATUS_PUBLISHED;
        $this->wp_post_id = (int) $post_id;
        $this->wp_post_url = $post_url;
        $this->published_at = current_time('mysql');
        $this->last_error = null;
    }

    /**
     * Verifica se é publicação WordPress
     */
    public function is_wordpress() {
        return $this->destination_type === self::DESTINATION_WORDPRESS;
    }

    /**
     * Verifica se é publicação Mastodon
     */
    public function is_mastodon() {
        return $this->destination_type === self::DESTINATION_MASTODON;
    }

    /**
     * Marca como falhada
     *
     * @param string $error Mensagem de erro
     * @return void
     */
    public function mark_as_failed($error) {
        $this->status = self::STATUS_FAILED;
        $this->last_error = $error;
    }

    /**
     * Marca como em processamento
     *
     * @return void
     */
    public function mark_as_processing() {
        $this->status = self::STATUS_PROCESSING;
    }

    /**
     * Reagenda publicação
     *
     * @param int $minutes_from_now Minutos a partir de agora
     * @return void
     */
    public function reschedule($minutes_from_now = 10) {
        $this->status = self::STATUS_SCHEDULED;
        $this->scheduled_for = date('Y-m-d H:i:s', strtotime("+{$minutes_from_now} minutes"));
    }

    /**
     * Verifica se está agendada
     *
     * @return bool
     */
    public function is_scheduled() {
        return $this->status === self::STATUS_SCHEDULED;
    }

    /**
     * Verifica se foi publicada
     *
     * @return bool
     */
    public function is_published() {
        return $this->status === self::STATUS_PUBLISHED;
    }

    /**
     * Verifica se falhou
     *
     * @return bool
     */
    public function is_failed() {
        return $this->status === self::STATUS_FAILED;
    }

    /**
     * Verifica se está em processamento
     *
     * @return bool
     */
    public function is_processing() {
        return $this->status === self::STATUS_PROCESSING;
    }

    /**
     * Verifica se está pronta para processar
     *
     * @return bool
     */
    /**
     * Verifica se faz parte de uma thread
     */
    public function is_thread() {
        return !empty($this->thread_id);
    }

    public function is_ready_to_process() {
        if (!$this->is_scheduled()) {
            return false;
        }

        $now = current_time('timestamp');
        $scheduled_timestamp = strtotime($this->scheduled_for);

        return $scheduled_timestamp <= $now;
    }

    /**
     * Cria instância a partir de linha do banco
     *
     * @param object $row
     * @return Publication
     */
    public static function from_row($row) {
        $pub = new self();

        $pub->set_id($row->id ?? 0);
        $pub->set_item_id($row->item_id ?? 0);
        $pub->set_destination_type($row->destination_type ?? 'mastodon');
        $pub->set_mastodon_account_id($row->mastodon_account_id ?? null);
        $pub->set_wp_category_id($row->wp_category_id ?? null);
        $pub->set_wp_post_id($row->wp_post_id ?? null);
        $pub->set_wp_post_url($row->wp_post_url ?? null);
        $pub->set_status($row->status ?? 'scheduled');
        $pub->set_scheduled_for($row->scheduled_for ?? '');
        $pub->set_content($row->content ?? '');
        $pub->set_alt_text($row->alt_text ?? '');
        $pub->set_thread_id($row->thread_id ?? null);
        $pub->set_thread_position($row->thread_position ?? null);
        $pub->set_media_ids($row->media_ids ?? '[]');
        $pub->set_mastodon_id($row->mastodon_id ?? null);
        $pub->set_mastodon_url($row->mastodon_url ?? null);
        $pub->set_attempt_count($row->attempt_count ?? 0);
        $pub->set_last_error($row->last_error ?? null);
        $pub->set_published_at($row->published_at ?? null);
        $pub->set_published_by($row->published_by ?? null);
        $pub->set_created_at($row->created_at ?? current_time('mysql'));
        $pub->set_updated_at($row->updated_at ?? current_time('mysql'));

        return $pub;
    }

    /**
     * Converte para array (para banco)
     *
     * @return array
     */
    public function to_array() {
        $data = [
            'item_id' => $this->item_id,
            'destination_type' => $this->destination_type,
            'mastodon_account_id' => $this->mastodon_account_id,
            'wp_category_id' => $this->wp_category_id,
            'wp_post_id' => $this->wp_post_id,
            'wp_post_url' => $this->wp_post_url,
            'status' => $this->status,
            'scheduled_for' => $this->scheduled_for,
            'content' => $this->content,
            'alt_text' => $this->alt_text,
            'thread_id' => $this->thread_id,
            'thread_position' => $this->thread_position,
            'media_ids' => wp_json_encode($this->media_ids),
            'mastodon_id' => $this->mastodon_id,
            'mastodon_url' => $this->mastodon_url,
            'attempt_count' => $this->attempt_count,
            'last_error' => $this->last_error,
            'published_at' => $this->published_at,
            'published_by' => $this->published_by,
            'updated_at' => current_time('mysql'),
        ];

        // Adiciona created_at apenas se for novo
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
            'item_id' => $this->item_id,
            'destination_type' => $this->destination_type,
            'mastodon_account_id' => $this->mastodon_account_id,
            'wp_category_id' => $this->wp_category_id,
            'wp_post_id' => $this->wp_post_id,
            'wp_post_url' => $this->wp_post_url,
            'status' => $this->status,
            'scheduled_for' => $this->scheduled_for,
            'content' => $this->content,
            'alt_text' => $this->alt_text,
            'character_count' => mb_strlen($this->content),
            'thread_id' => $this->thread_id,
            'thread_position' => $this->thread_position,
            'is_thread' => $this->is_thread(),
            'has_media' => !empty($this->media_ids),
            'media_count' => count($this->media_ids),
            'mastodon_id' => $this->mastodon_id,
            'mastodon_url' => $this->mastodon_url,
            'attempt_count' => $this->attempt_count,
            'can_retry' => $this->can_retry(),
            'last_error' => $this->last_error,
            'published_at' => $this->published_at,
            'published_by' => $this->published_by,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'is_ready' => $this->is_ready_to_process(),
        ];
    }
}
