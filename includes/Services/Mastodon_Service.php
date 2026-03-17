<?php
namespace NewsmastCurator\Services;

use NewsmastCurator\Models\Mastodon_Account;

class Mastodon_Service {
    private $api_base_url;
    private $access_token;
    private $character_limit = 500;
    private $account_id = null;

    /**
     * Construtor - aceita conta Mastodon ou usa configuração legada
     *
     * @param Mastodon_Account|null $account Conta Mastodon específica
     */
    public function __construct($account = null) {
        if ($account instanceof Mastodon_Account) {
            $this->api_base_url = rtrim($account->get_instance_url(), '/');
            $this->access_token = $account->get_access_token();
            $this->account_id = $account->get_id();
        } else {
            // Fallback para configuração legada (wp_options)
            $this->api_base_url = rtrim(get_option('nc_mastodon_instance', ''), '/');
            $this->access_token = get_option('nc_mastodon_token', '');
        }
        $this->character_limit = (int) get_option('nc_mastodon_character_limit', 500);
    }

    /**
     * Obtém ID da conta usada
     */
    public function get_account_id() {
        return $this->account_id;
    }

    public function post_status($content, $media_ids = []) {
        if (empty($this->api_base_url) || empty($this->access_token)) {
            throw new \Exception(__('Mastodon não configurado', 'newsmast-curator'));
        }

        $endpoint = '/api/v1/statuses';
        $data = ['status' => $content];

        if (!empty($media_ids)) {
            $data['media_ids'] = $media_ids;
        }

        return $this->make_request($endpoint, 'POST', $data);
    }

    public function upload_media($file_path, $description = '') {
        $endpoint = '/api/v1/media';
        $boundary = wp_generate_password(24);

        $body = '';
        $body .= "--{$boundary}\r\n";
        $body .= 'Content-Disposition: form-data; name="file"; filename="' . basename($file_path) . "\"\r\n";
        $body .= "Content-Type: application/octet-stream\r\n\r\n";
        $body .= file_get_contents($file_path) . "\r\n";

        // Alt text para acessibilidade (limite do Mastodon: 1500 caracteres)
        if (!empty($description)) {
            $body .= "--{$boundary}\r\n";
            $body .= "Content-Disposition: form-data; name=\"description\"\r\n\r\n";
            $body .= mb_substr($description, 0, 1500) . "\r\n";
        }

        $body .= "--{$boundary}--\r\n";

        $response = wp_remote_post($this->api_base_url . $endpoint, [
            'headers' => [
                'Authorization' => 'Bearer ' . $this->access_token,
                'Content-Type' => 'multipart/form-data; boundary=' . $boundary,
            ],
            'body' => $body,
            'timeout' => 30,
        ]);

        if (is_wp_error($response)) {
            throw new \Exception($response->get_error_message());
        }

        $code = wp_remote_retrieve_response_code($response);
        if ($code !== 200 && $code !== 202) {
            throw new \Exception(sprintf(__('Erro ao fazer upload: %d', 'newsmast-curator'), $code));
        }

        $result = json_decode(wp_remote_retrieve_body($response), true);
        return $result['id'] ?? null;
    }

    public function validate_credentials() {
        try {
            $result = $this->make_request('/api/v1/accounts/verify_credentials', 'GET');
            return [
                'success' => true,
                'account' => [
                    'username' => $result['username'] ?? '',
                    'display_name' => $result['display_name'] ?? '',
                    'url' => $result['url'] ?? '',
                ],
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }
    }

    public function get_character_limit() {
        return $this->character_limit;
    }

    /**
     * Cria instância para uma conta específica pelo ID
     *
     * @param int $account_id
     * @param \NewsmastCurator\Core\Database $database
     * @return self
     */
    public static function for_account($account_id, $database) {
        $repo = new \NewsmastCurator\Repositories\Mastodon_Account_Repository($database);
        $account = $repo->find($account_id);

        if (!$account) {
            throw new \Exception(sprintf(__('Conta Mastodon #%d não encontrada', 'newsmast-curator'), $account_id));
        }

        if (!$account->is_active()) {
            throw new \Exception(sprintf(__('Conta Mastodon "%s" está inativa', 'newsmast-curator'), $account->get_name()));
        }

        return new self($account);
    }

    /**
     * Cria instância para a conta padrão ou legada
     *
     * @param \NewsmastCurator\Core\Database|null $database
     * @return self
     */
    public static function get_default($database = null) {
        if ($database) {
            $repo = new \NewsmastCurator\Repositories\Mastodon_Account_Repository($database);
            $account = $repo->find_default();
            if ($account) {
                return new self($account);
            }
        }

        // Fallback para configuração legada
        return new self();
    }

    private function make_request($endpoint, $method, $data = []) {
        $url = $this->api_base_url . $endpoint;

        $args = [
            'method' => $method,
            'headers' => [
                'Authorization' => 'Bearer ' . $this->access_token,
            ],
            'timeout' => 30,
        ];

        if ($method === 'POST' && !empty($data)) {
            // Use form-urlencoded (Mastodon API standard) instead of JSON
            $args['body'] = $data;
        } elseif ($method === 'GET') {
            $args['headers']['Content-Type'] = 'application/json';
        }

        $response = wp_remote_request($url, $args);

        if (is_wp_error($response)) {
            throw new \Exception($response->get_error_message());
        }

        $body = wp_remote_retrieve_body($response);
        $code = wp_remote_retrieve_response_code($response);

        if ($code < 200 || $code >= 300) {
            // Include Mastodon's error detail in the exception
            $error_detail = '';
            $decoded = json_decode($body, true);
            if ($decoded && isset($decoded['error'])) {
                $error_detail = ': ' . $decoded['error'];
            }
            throw new \Exception(sprintf(
                __('Erro API Mastodon: %d%s', 'newsmast-curator'),
                $code,
                $error_detail
            ));
        }

        return json_decode($body, true);
    }
}
