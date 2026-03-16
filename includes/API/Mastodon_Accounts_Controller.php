<?php
namespace NewsmastCurator\API;

use NewsmastCurator\Repositories\Mastodon_Account_Repository;
use NewsmastCurator\Models\Mastodon_Account;
use NewsmastCurator\Services\Mastodon_Service;

class Mastodon_Accounts_Controller extends Base_REST_Controller {
    protected $rest_base = 'mastodon-accounts';

    public function register_routes() {
        register_rest_route($this->namespace, '/' . $this->rest_base, [
            ['methods' => 'GET', 'callback' => [$this, 'get_items'], 'permission_callback' => [$this, 'check_settings_permission']],
            ['methods' => 'POST', 'callback' => [$this, 'create_item'], 'permission_callback' => [$this, 'check_settings_permission']],
        ]);

        register_rest_route($this->namespace, '/' . $this->rest_base . '/(?P<id>[\d]+)', [
            ['methods' => 'GET', 'callback' => [$this, 'get_item'], 'permission_callback' => [$this, 'check_settings_permission']],
            ['methods' => 'PUT', 'callback' => [$this, 'update_item'], 'permission_callback' => [$this, 'check_settings_permission']],
            ['methods' => 'DELETE', 'callback' => [$this, 'delete_item'], 'permission_callback' => [$this, 'check_settings_permission']],
        ]);

        register_rest_route($this->namespace, '/' . $this->rest_base . '/(?P<id>[\d]+)/test', [
            ['methods' => 'POST', 'callback' => [$this, 'test_connection'], 'permission_callback' => [$this, 'check_settings_permission']],
        ]);

        register_rest_route($this->namespace, '/' . $this->rest_base . '/(?P<id>[\d]+)/set-default', [
            ['methods' => 'POST', 'callback' => [$this, 'set_default'], 'permission_callback' => [$this, 'check_settings_permission']],
        ]);
    }

    public function check_settings_permission($request) {
        return current_user_can('manage_nc_settings');
    }

    public function get_items($request) {
        $repo = new Mastodon_Account_Repository($this->database);
        $accounts = $repo->find_all(['per_page' => 100, 'orderby' => 'name', 'order' => 'ASC']);
        return $this->prepare_response(array_map(fn($a) => $a->to_api_response(), $accounts));
    }

    public function get_item($request) {
        $repo = new Mastodon_Account_Repository($this->database);
        $account = $repo->find($request['id']);
        return $account ? $this->prepare_response($account->to_api_response()) : $this->prepare_error(__('Conta não encontrada', 'newsmast-curator'), 'not_found', 404);
    }

    public function create_item($request) {
        $account = new Mastodon_Account();
        $account->set_name($request['name'] ?? '');
        $account->set_instance_url($request['instance_url'] ?? '');
        $account->set_access_token($request['access_token'] ?? '');

        $validation = $account->validate();
        if ($validation !== true) {
            return $this->prepare_error(implode(' ', $validation), 'validation_error', 400);
        }

        // Testar conexão antes de salvar
        $service = new Mastodon_Service($account);
        $test = $service->validate_credentials();
        if ($test['success']) {
            $account->set_username($test['account']['username'] ?? '');
        } else {
            return $this->prepare_error(
                sprintf(__('Falha ao conectar: %s', 'newsmast-curator'), $test['message'] ?? ''),
                'connection_failed',
                400
            );
        }

        $repo = new Mastodon_Account_Repository($this->database);

        // Se é a primeira conta, torna padrão automaticamente
        $existing = $repo->find_active();
        if (empty($existing)) {
            $account->set_is_default(true);
        }

        $id = $repo->insert($account);
        return $id ? $this->prepare_response($account->to_api_response(), 201) : $this->prepare_error(__('Falha ao criar conta', 'newsmast-curator'));
    }

    public function update_item($request) {
        $repo = new Mastodon_Account_Repository($this->database);
        $account = $repo->find($request['id']);
        if (!$account) {
            return $this->prepare_error(__('Conta não encontrada', 'newsmast-curator'), 'not_found', 404);
        }

        if (isset($request['name'])) $account->set_name($request['name']);
        if (isset($request['instance_url'])) $account->set_instance_url($request['instance_url']);

        // Só atualiza token se não for máscara
        if (isset($request['access_token']) && !empty($request['access_token']) && $request['access_token'] !== '********') {
            $account->set_access_token($request['access_token']);
        }

        if (isset($request['status'])) $account->set_status($request['status']);

        $repo->update($account);
        return $this->prepare_response($account->to_api_response());
    }

    public function delete_item($request) {
        $repo = new Mastodon_Account_Repository($this->database);
        $account = $repo->find($request['id']);

        if (!$account) {
            return $this->prepare_error(__('Conta não encontrada', 'newsmast-curator'), 'not_found', 404);
        }

        // Não permite deletar conta com publicações pendentes
        global $wpdb;
        $pub_table = $this->database->get_table_name('publications');
        $pending = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$pub_table} WHERE mastodon_account_id = %d AND status IN ('scheduled', 'processing')",
            $request['id']
        ));

        if ($pending > 0) {
            return $this->prepare_error(
                sprintf(__('Esta conta tem %d publicação(ões) pendente(s). Cancele-as antes de remover.', 'newsmast-curator'), $pending),
                'has_pending',
                400
            );
        }

        $result = $repo->delete($request['id']);
        return $result ? $this->prepare_response(['success' => true]) : $this->prepare_error(__('Falha ao remover', 'newsmast-curator'));
    }

    public function test_connection($request) {
        $repo = new Mastodon_Account_Repository($this->database);
        $account = $repo->find($request['id']);
        if (!$account) {
            return $this->prepare_error(__('Conta não encontrada', 'newsmast-curator'), 'not_found', 404);
        }

        $service = new Mastodon_Service($account);
        $result = $service->validate_credentials();

        if ($result['success']) {
            $account->set_username($result['account']['username'] ?? '');
            $repo->clear_error($account->get_id());
            $repo->update($account);
        } else {
            $repo->update_error($account->get_id(), $result['message'] ?? 'Erro desconhecido');
        }

        return $this->prepare_response($result);
    }

    public function set_default($request) {
        $repo = new Mastodon_Account_Repository($this->database);
        $account = $repo->find($request['id']);
        if (!$account) {
            return $this->prepare_error(__('Conta não encontrada', 'newsmast-curator'), 'not_found', 404);
        }

        $repo->set_default($request['id']);
        return $this->prepare_response(['success' => true, 'message' => __('Conta definida como padrão', 'newsmast-curator')]);
    }
}
