<?php
/**
 * Model de Fonte de Conteúdo
 *
 * @package NewsmastCurator
 * @subpackage Models
 */

namespace NewsmastCurator\Models;

class Source {
    /**
     * ID da fonte
     *
     * @var int
     */
    private $id = 0;

    /**
     * Nome da fonte
     *
     * @var string
     */
    private $name = '';

    /**
     * Tipo de conector
     *
     * @var string
     */
    private $connector_type = '';

    /**
     * URL da fonte
     *
     * @var string
     */
    private $url = '';

    /**
     * Configurações do conector (JSON)
     *
     * @var array
     */
    private $config = [];

    /**
     * Status da fonte
     *
     * @var string
     */
    private $status = 'active';

    /**
     * Data da última coleta
     *
     * @var string|null
     */
    private $last_collection = null;

    /**
     * Último erro
     *
     * @var string|null
     */
    private $last_error = null;

    /**
     * Data de criação
     *
     * @var string
     */
    private $created_at = '';

    /**
     * Data de atualização
     *
     * @var string
     */
    private $updated_at = '';

    /**
     * Erros de validação
     *
     * @var array
     */
    private $errors = [];

    // Getters

    public function get_id() {
        return $this->id;
    }

    public function get_name() {
        return $this->name;
    }

    public function get_connector_type() {
        return $this->connector_type;
    }

    public function get_url() {
        return $this->url;
    }

    public function get_config($key = null) {
        if ($key === null) {
            return $this->config;
        }
        return $this->config[$key] ?? null;
    }

    public function get_status() {
        return $this->status;
    }

    public function get_last_collection() {
        return $this->last_collection;
    }

    public function get_last_error() {
        return $this->last_error;
    }

    public function get_created_at() {
        return $this->created_at;
    }

    public function get_updated_at() {
        return $this->updated_at;
    }

    // Setters

    public function set_id($id) {
        $this->id = (int) $id;
    }

    public function set_name($name) {
        $this->name = sanitize_text_field($name);
    }

    public function set_connector_type($type) {
        $this->connector_type = sanitize_text_field($type);
    }

    public function set_url($url) {
        $this->url = esc_url_raw($url);
    }

    public function set_config($config) {
        if (is_string($config)) {
            $config = json_decode($config, true) ?? [];
        }
        $this->config = $this->sanitize_config($config);
    }

    public function set_status($status) {
        $this->status = sanitize_text_field($status);
    }

    public function set_last_collection($datetime) {
        $this->last_collection = $datetime;
    }

    public function set_last_error($error) {
        $this->last_error = $error ? sanitize_textarea_field($error) : null;
    }

    public function set_created_at($datetime) {
        $this->created_at = $datetime;
    }

    public function set_updated_at($datetime) {
        $this->updated_at = $datetime;
    }

    /**
     * Cria instância a partir de linha do banco
     *
     * @param object $row Linha do banco
     * @return Source
     */
    public static function from_row($row) {
        $source = new self();

        $source->set_id($row->id ?? 0);
        $source->set_name($row->name ?? '');
        $source->set_connector_type($row->connector_type ?? '');
        $source->set_url($row->url ?? '');
        $source->set_config($row->config ?? '[]');
        $source->set_status($row->status ?? 'active');
        $source->set_last_collection($row->last_collection ?? null);
        $source->set_last_error($row->last_error ?? null);
        $source->set_created_at($row->created_at ?? current_time('mysql'));
        $source->set_updated_at($row->updated_at ?? current_time('mysql'));

        return $source;
    }

    /**
     * Converte para array (para inserir/atualizar no banco)
     *
     * @return array
     */
    public function to_array() {
        $data = [
            'name' => $this->name,
            'connector_type' => $this->connector_type,
            'url' => $this->url,
            'config' => wp_json_encode($this->config),
            'status' => $this->status,
            'last_collection' => $this->last_collection,
            'last_error' => $this->last_error,
            'updated_at' => current_time('mysql'),
        ];

        // Inclui created_at apenas se for novo
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
            'connector_type' => $this->connector_type,
            'url' => $this->url,
            'config' => $this->config,
            'status' => $this->status,
            'last_collection' => $this->last_collection,
            'last_error' => $this->last_error,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }

    /**
     * Valida os dados da fonte
     *
     * @return bool|array True se válido, array de erros caso contrário
     */
    public function validate() {
        $this->errors = [];

        // Nome obrigatório
        if (empty($this->name)) {
            $this->errors['name'] = __('Nome é obrigatório.', 'newsmast-curator');
        }

        // Tipo de conector válido
        $valid_types = ['plone', 'wordpress', 'tainacan', 'visite_museus'];
        if (!in_array($this->connector_type, $valid_types)) {
            $this->errors['connector_type'] = __('Tipo de conector inválido.', 'newsmast-curator');
        }

        // URL válida
        if (empty($this->url) || !filter_var($this->url, FILTER_VALIDATE_URL)) {
            $this->errors['url'] = __('URL inválida.', 'newsmast-curator');
        }

        return empty($this->errors) ? true : $this->errors;
    }

    /**
     * Obtém erros de validação
     *
     * @return array
     */
    public function get_errors() {
        return $this->errors;
    }

    /**
     * Verifica se está ativa
     *
     * @return bool
     */
    public function is_active() {
        return $this->status === 'active';
    }

    /**
     * Verifica se tem erro
     *
     * @return bool
     */
    public function has_error() {
        return !empty($this->last_error);
    }

    /**
     * Sanitiza configuração recursivamente
     *
     * @param array $config
     * @return array
     */
    private function sanitize_config($config) {
        $sanitized = [];

        foreach ($config as $key => $value) {
            $key = sanitize_key($key);

            if (is_array($value)) {
                $sanitized[$key] = $this->sanitize_config($value);
            } else {
                $sanitized[$key] = sanitize_text_field($value);
            }
        }

        return $sanitized;
    }
}
