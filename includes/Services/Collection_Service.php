<?php
namespace NewsmastCurator\Services;

use NewsmastCurator\Core\Database;
use NewsmastCurator\Repositories\Source_Repository;
use NewsmastCurator\Repositories\Item_Repository;
use NewsmastCurator\Connectors\Connector_Registry;
use NewsmastCurator\Models\Item;

class Collection_Service {
    private $database;
    private $source_repo;
    private $item_repo;
    private $logger;

    public function __construct(Database $database) {
        $this->database = $database;
        $this->source_repo = new Source_Repository($database);
        $this->item_repo = new Item_Repository($database);
        $this->logger = new Logger_Service($database);
    }

    public function collect_all_sources() {
        $sources = $this->source_repo->find_active();

        foreach ($sources as $source) {
            try {
                $this->collect_from_source($source->get_id());
            } catch (\Exception $e) {
                $this->logger->error('Erro ao coletar de fonte', [
                    'related_type' => 'source',
                    'related_id' => $source->get_id(),
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }

    public function collect_from_source($source_id) {
        $source = $this->source_repo->find($source_id);
        if (!$source) return false;

        $connector = Connector_Registry::get($source->get_connector_type());
        if (!$connector) return false;

        $config = array_merge(['url' => $source->get_url()], $source->get_config());
        $connector->connect($config);

        $this->logger->info('Iniciando coleta', [
            'related_type' => 'source',
            'related_id' => $source_id,
            'details' => sprintf('Conector: %s | URL: %s', $source->get_connector_type(), $source->get_url()),
        ]);

        $collected_items = $connector->collect();

        // Logar erro do conector se não coletou nada
        if (empty($collected_items)) {
            $error_detail = method_exists($connector, 'get_last_error') ? $connector->get_last_error() : '';
            $this->logger->warning('Coleta retornou 0 itens', [
                'related_type' => 'source',
                'related_id' => $source_id,
                'details' => $error_detail ?: 'Nenhum item retornado pelo conector',
            ]);
            $this->source_repo->update_last_collection($source_id, current_time('mysql'));
            return 0;
        }

        $count = 0;
        $duplicates = 0;
        foreach ($collected_items as $item_data) {
            $item = new Item();
            $item->set_source_id($source_id);
            $item->set_external_id($item_data['external_id']);
            $item->set_title($item_data['title']);
            $item->set_content($item_data['content']);
            $item->set_excerpt($item_data['excerpt'] ?? '');
            $item->set_url($item_data['url']);
            $item->set_image_url($item_data['image_url'] ?? null);
            $item->set_author($item_data['author'] ?? null);
            $item->set_published_date($item_data['published_date'] ?? null);
            $item->set_metadata($item_data['metadata'] ?? []);
            $item->generate_hash();

            // Verifica duplicata
            $existing = $this->item_repo->find_by_hash($item->get_hash());
            if (!$existing) {
                $this->item_repo->insert($item);
                $count++;
            } else {
                $duplicates++;
            }
        }

        $this->source_repo->update_last_collection($source_id, current_time('mysql'));
        $this->logger->info("Coletados {$count} novos itens", [
            'related_type' => 'source',
            'related_id' => $source_id,
            'details' => sprintf('Total do conector: %d | Novos: %d | Duplicados: %d', count($collected_items), $count, $duplicates),
        ]);

        return $count;
    }
}
