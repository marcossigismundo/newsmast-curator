<?php
namespace NewsmastCurator\API;

abstract class Base_REST_Controller extends \WP_REST_Controller {
    protected $namespace = 'newsmast-curator/v1';
    protected $database;

    public function __construct($database = null) {
        $this->database = $database;
    }

    protected function check_permissions($request) {
        return current_user_can('manage_nc_items');
    }

    protected function prepare_response($data, $status = 200) {
        return new \WP_REST_Response($data, $status);
    }

    protected function prepare_error($message, $code = 'error', $status = 400) {
        return new \WP_Error($code, $message, ['status' => $status]);
    }
}
