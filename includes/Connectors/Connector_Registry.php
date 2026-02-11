<?php
/**
 * Registro de Conectores
 *
 * @package NewsmastCurator
 * @subpackage Connectors
 */

namespace NewsmastCurator\Connectors;

class Connector_Registry {
    /**
     * Conectores registrados
     *
     * @var array
     */
    private static $connectors = [];

    /**
     * Registra um conector
     *
     * @param string $type Tipo do conector
     * @param string $class Classe do conector
     * @param string $label Label para exibição
     * @param string $description Descrição
     * @return void
     */
    public static function register($type, $class, $label, $description = '') {
        self::$connectors[$type] = [
            'class' => $class,
            'label' => $label,
            'description' => $description,
        ];
    }

    /**
     * Obtém instância de um conector
     *
     * @param string $type
     * @return Connector_Interface|null
     */
    public static function get($type) {
        if (!isset(self::$connectors[$type])) {
            return null;
        }

        $class = self::$connectors[$type]['class'];

        if (!class_exists($class)) {
            return null;
        }

        return new $class();
    }

    /**
     * Obtém todos os tipos disponíveis
     *
     * @return array
     */
    public static function get_all_types() {
        return array_keys(self::$connectors);
    }

    /**
     * Obtém informações de um conector
     *
     * @param string $type
     * @return array|null
     */
    public static function get_info($type) {
        return self::$connectors[$type] ?? null;
    }

    /**
     * Obtém todos os conectores com suas informações
     *
     * @return array
     */
    public static function get_all() {
        return self::$connectors;
    }

    /**
     * Inicializa conectores padrão
     *
     * @return void
     */
    public static function init_default_connectors() {
        self::register(
            'plone',
            '\\NewsmastCurator\\Connectors\\Plone_Connector',
            __('Plone CMS', 'newsmast-curator'),
            __('Coleta notícias de sites Plone via scraping', 'newsmast-curator')
        );

        self::register(
            'wordpress',
            '\\NewsmastCurator\\Connectors\\WordPress_Connector',
            __('WordPress', 'newsmast-curator'),
            __('Coleta posts via REST API do WordPress', 'newsmast-curator')
        );

        self::register(
            'tainacan',
            '\\NewsmastCurator\\Connectors\\Tainacan_Connector',
            __('Tainacan', 'newsmast-curator'),
            __('Coleta itens do plugin Tainacan', 'newsmast-curator')
        );

        self::register(
            'visite_museus',
            '\\NewsmastCurator\\Connectors\\Visite_Museus_Connector',
            __('Visite Museus', 'newsmast-curator'),
            __('Coleta itens do portal Visite Museus', 'newsmast-curator')
        );
    }
}

// Inicializa conectores
Connector_Registry::init_default_connectors();
