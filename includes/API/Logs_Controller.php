<?php
namespace NewsmastCurator\API;

class Logs_Controller extends Base_REST_Controller {
    protected $rest_base = 'logs';

    public function register_routes() {
        register_rest_route($this->namespace, '/' . $this->rest_base, [
            ['methods' => 'GET', 'callback' => [$this, 'get_logs'], 'permission_callback' => [$this, 'check_permissions']],
            ['methods' => 'DELETE', 'callback' => [$this, 'clear_logs'], 'permission_callback' => [$this, 'check_permissions']],
        ]);
    }

    public function get_logs($request) {
        $logger = new \NewsmastCurator\Services\Logger_Service($this->database);
        $args = [];
        if (isset($request['type'])) $args['type'] = $request['type'];
        if (isset($request['level'])) $args['level'] = $request['level'];
        $args['per_page'] = $request['per_page'] ?? 50;
        $args['page'] = $request['page'] ?? 1;

        $logs = $logger->get_logs($args);
        return $this->prepare_response(['logs' => $logs]);
    }

    public function clear_logs($request) {
        $logger = new \NewsmastCurator\Services\Logger_Service($this->database);
        $days = $request['days'] ?? 30;
        $count = $logger->clear_old_logs($days);
        return $this->prepare_response(['success' => true, 'deleted' => $count]);
    }
}
