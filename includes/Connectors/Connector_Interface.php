<?php
/**
 * Interface para conectores de fontes
 *
 * @package NewsmastCurator
 * @subpackage Connectors
 */

namespace NewsmastCurator\Connectors;

interface Connector_Interface {
    /**
     * Conecta à fonte com configuração
     *
     * @param array $config Configuração do conector
     * @return bool
     */
    public function connect($config);

    /**
     * Valida configuração
     *
     * @param array $config
     * @return bool|array True se válido, array de erros caso contrário
     */
    public function validate_config($config);

    /**
     * Coleta itens da fonte
     *
     * @return array Array de itens normalizados
     */
    public function collect();

    /**
     * Obtém campos de configuração do conector
     *
     * @return array
     */
    public function get_config_fields();

    /**
     * Testa conexão
     *
     * @return array ['success' => bool, 'message' => string, 'sample_items' => array]
     */
    public function test_connection();
}
