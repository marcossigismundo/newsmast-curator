<?php
namespace NewsmastCurator\API;

class Settings_Controller extends Base_REST_Controller {
    protected $rest_base = 'settings';

    public function register_routes() {
        register_rest_route($this->namespace, '/' . $this->rest_base, [
            ['methods' => 'GET', 'callback' => [$this, 'get_settings'], 'permission_callback' => [$this, 'check_settings_permission']],
            ['methods' => 'POST', 'callback' => [$this, 'save_settings'], 'permission_callback' => [$this, 'check_settings_permission']],
        ]);

        register_rest_route($this->namespace, '/' . $this->rest_base . '/test-mastodon', [
            ['methods' => 'POST', 'callback' => [$this, 'test_mastodon'], 'permission_callback' => [$this, 'check_settings_permission']],
        ]);
    }

    public function check_settings_permission($request) {
        return current_user_can('manage_nc_settings');
    }

    public function get_settings($request) {
        return $this->prepare_response([
            'mastodon_instance' => get_option('nc_mastodon_instance', ''),
            'mastodon_token' => '********',
            'post_template' => get_option('nc_post_template', ''),
            'default_hashtags' => get_option('nc_default_hashtags', ''),
            'collection_frequency' => get_option('nc_collection_frequency', 'hourly'),
        ]);
    }

    public function save_settings($request) {
        if (isset($request['mastodon_instance'])) {
            $instance_url = esc_url_raw($request['mastodon_instance']);
            // Strip common frontend paths — only the base URL is needed for API calls
            $instance_url = preg_replace('#/(home|web|public|about|auth)(/.*)?$#', '', rtrim($instance_url, '/'));
            update_option('nc_mastodon_instance', $instance_url);
        }
        if (isset($request['mastodon_token']) && !empty($request['mastodon_token']) && $request['mastodon_token'] !== '********') {
            update_option('nc_mastodon_token', sanitize_text_field($request['mastodon_token']));
        }
        if (isset($request['post_template'])) update_option('nc_post_template', sanitize_textarea_field($request['post_template']));
        if (isset($request['default_hashtags'])) update_option('nc_default_hashtags', sanitize_text_field($request['default_hashtags']));

        return $this->prepare_response(['success' => true]);
    }

    public function test_mastodon($request) {
        $service = new \NewsmastCurator\Services\Mastodon_Service();
        $result = $service->validate_credentials();
        return $this->prepare_response($result);
    }
}
