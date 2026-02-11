<?php
namespace NewsmastCurator\Services;

class Mastodon_Service {
    private $api_base_url;
    private $access_token;
    private $character_limit = 500;

    public function __construct() {
        $this->api_base_url = rtrim(get_option('nc_mastodon_instance', ''), '/');
        $this->access_token = get_option('nc_mastodon_token', '');
        $this->character_limit = (int) get_option('nc_mastodon_character_limit', 500);
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

    public function upload_media($file_path) {
        $endpoint = '/api/v1/media';
        $boundary = wp_generate_password(24);

        $body = '';
        $body .= "--{$boundary}\r\n";
        $body .= 'Content-Disposition: form-data; name="file"; filename="' . basename($file_path) . "\"\r\n";
        $body .= "Content-Type: application/octet-stream\r\n\r\n";
        $body .= file_get_contents($file_path) . "\r\n";
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
        if ($code !== 200) {
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

    private function make_request($endpoint, $method, $data = []) {
        $url = $this->api_base_url . $endpoint;

        $args = [
            'method' => $method,
            'headers' => [
                'Authorization' => 'Bearer ' . $this->access_token,
                'Content-Type' => 'application/json',
            ],
            'timeout' => 30,
        ];

        if ($method === 'POST' && !empty($data)) {
            $args['body'] = wp_json_encode($data);
        }

        $response = wp_remote_request($url, $args);

        if (is_wp_error($response)) {
            throw new \Exception($response->get_error_message());
        }

        $code = wp_remote_retrieve_response_code($response);
        if ($code < 200 || $code >= 300) {
            throw new \Exception(sprintf(__('Erro API Mastodon: %d', 'newsmast-curator'), $code));
        }

        return json_decode(wp_remote_retrieve_body($response), true);
    }
}
