<?php
/**
 * Model de Conta Mastodon
 *
 * @package NewsmastCurator
 * @subpackage Models
 */

namespace NewsmastCurator\Models;

class Mastodon_Account {
    private $id = 0;
    private $name = '';
    private $instance_url = '';
    private $access_token = '';
    private $username = null;
    private $is_default = 0;
    private $status = 'active';
    private $last_error = null;
    private $created_at = '';
    private $updated_at = '';
    private $errors = [];

    // Getters
    public function get_id() { return $this->id; }
    public function get_name() { return $this->name; }
    public function get_instance_url() { return $this->instance_url; }
    public function get_access_token() { return $this->access_token; }
    public function get_username() { return $this->username; }
    public function is_default() { return (bool) $this->is_default; }
    public function get_status() { return $this->status; }
    public function get_last_error() { return $this->last_error; }
    public function get_created_at() { return $this->created_at; }
    public function get_updated_at() { return $this->updated_at; }

    // Setters
    public function set_id($id) { $this->id = (int) $id; }
    public function set_name($name) { $this->name = sanitize_text_field($name); }

    public function set_instance_url($url) {
        $url = esc_url_raw($url);
        // Strip common frontend paths
        $url = preg_replace('#/(home|web|public|about|auth)(/.*)?$#', '', rtrim($url, '/'));
        $this->instance_url = $url;
    }

    public function set_access_token($token) { $this->access_token = sanitize_text_field($token); }
    public function set_username($username) { $this->username = $username ? sanitize_text_field($username) : null; }
    public function set_is_default($is_default) { $this->is_default = (int) (bool) $is_default; }
    public function set_status($status) { $this->status = sanitize_text_field($status); }
    public function set_last_error($error) { $this->last_error = $error ? sanitize_textarea_field($error) : null; }
    public function set_created_at($datetime) { $this->created_at = $datetime; }
    public function set_updated_at($datetime) { $this->updated_at = $datetime; }

    /**
     * Verifica se está ativa
     */
    public function is_active() {
        return $this->status === 'active';
    }

    /**
     * Valida os dados da conta
     */
    public function validate() {
        $this->errors = [];

        if (empty($this->name)) {
            $this->errors['name'] = __('Nome é obrigatório.', 'newsmast-curator');
        }

        if (empty($this->instance_url) || !filter_var($this->instance_url, FILTER_VALIDATE_URL)) {
            $this->errors['instance_url'] = __('URL da instância é obrigatória e deve ser válida.', 'newsmast-curator');
        }

        if (empty($this->access_token)) {
            $this->errors['access_token'] = __('Token de acesso é obrigatório.', 'newsmast-curator');
        }

        return empty($this->errors) ? true : $this->errors;
    }

    public function get_errors() {
        return $this->errors;
    }

    /**
     * Cria instância a partir de linha do banco
     */
    public static function from_row($row) {
        $account = new self();

        $account->set_id($row->id ?? 0);
        $account->set_name($row->name ?? '');
        $account->set_instance_url($row->instance_url ?? '');
        $account->access_token = $row->access_token ?? ''; // Skip sanitize on read
        $account->set_username($row->username ?? null);
        $account->set_is_default($row->is_default ?? 0);
        $account->set_status($row->status ?? 'active');
        $account->set_last_error($row->last_error ?? null);
        $account->set_created_at($row->created_at ?? current_time('mysql'));
        $account->set_updated_at($row->updated_at ?? current_time('mysql'));

        return $account;
    }

    /**
     * Converte para array (para banco)
     */
    public function to_array() {
        $data = [
            'name' => $this->name,
            'instance_url' => $this->instance_url,
            'access_token' => $this->access_token,
            'username' => $this->username,
            'is_default' => $this->is_default,
            'status' => $this->status,
            'last_error' => $this->last_error,
            'updated_at' => current_time('mysql'),
        ];

        if ($this->id === 0) {
            $data['created_at'] = current_time('mysql');
        }

        return $data;
    }

    /**
     * Converte para resposta de API (token mascarado)
     */
    public function to_api_response() {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'instance_url' => $this->instance_url,
            'access_token' => $this->access_token ? '********' : '',
            'username' => $this->username,
            'is_default' => (bool) $this->is_default,
            'status' => $this->status,
            'last_error' => $this->last_error,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
