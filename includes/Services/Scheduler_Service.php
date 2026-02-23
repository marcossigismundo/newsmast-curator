<?php
namespace NewsmastCurator\Services;

use NewsmastCurator\Core\Database;
use NewsmastCurator\Repositories\Publication_Repository;
use NewsmastCurator\Repositories\Collection_Repository;

class Scheduler_Service {
    private $database;
    private $pub_repo;
    private $mastodon_service;
    private $logger;
    private $lock_key = 'nc_scheduler_lock';

    public function __construct(Database $database) {
        $this->database = $database;
        $this->pub_repo = new Publication_Repository($database);
        $this->mastodon_service = new Mastodon_Service();
        $this->logger = new Logger_Service($database);
    }

    public function process_scheduled_publications() {
        if ($this->is_locked()) return;
        if (!$this->acquire_lock()) return;

        try {
            $publications = $this->pub_repo->find_pending_for_processing(5);

            foreach ($publications as $pub) {
                $this->process_publication($pub);
            }

            // Refresh collection statuses based on publication results
            if (!empty($publications)) {
                $collection_repo = new Collection_Repository($this->database);
                $collection_repo->refresh_statuses();
            }
        } finally {
            $this->release_lock();
        }
    }

    private function process_publication($pub) {
        $pub->mark_as_processing();
        $this->pub_repo->update($pub);

        try {
            $media_ids = [];
            $result = $this->mastodon_service->post_status($pub->get_content(), $media_ids);

            if ($result && isset($result['id'])) {
                $pub->mark_as_published($result['id'], $result['url'] ?? '');
                $this->pub_repo->update($pub);
                $this->logger->success('Publicação realizada', [
                    'related_type' => 'publication',
                    'related_id' => $pub->get_id(),
                ]);
            } else {
                throw new \Exception('Resposta inválida do Mastodon');
            }
        } catch (\Exception $e) {
            $pub->increment_attempts();

            if ($pub->can_retry()) {
                $pub->reschedule(10);
                $this->logger->warning('Falha na publicação, reagendando', [
                    'related_type' => 'publication',
                    'related_id' => $pub->get_id(),
                    'error' => $e->getMessage(),
                ]);
            } else {
                $pub->mark_as_failed($e->getMessage());
                $this->logger->error('Publicação falhou definitivamente', [
                    'related_type' => 'publication',
                    'related_id' => $pub->get_id(),
                    'error' => $e->getMessage(),
                ]);
            }

            $this->pub_repo->update($pub);
        }
    }

    private function is_locked() {
        $lock = get_option($this->lock_key);
        return $lock && $lock > time();
    }

    private function acquire_lock() {
        return add_option($this->lock_key, time() + 300, '', 'no');
    }

    private function release_lock() {
        delete_option($this->lock_key);
    }
}
