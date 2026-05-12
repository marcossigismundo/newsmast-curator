<?php
namespace NewsmastCurator\Services;

use NewsmastCurator\Core\Database;
use NewsmastCurator\Models\Item;
use NewsmastCurator\Models\Publication;
use NewsmastCurator\Repositories\Item_Repository;

class WordPress_Publisher_Service {
    private $database;
    private $item_repo;

    public function __construct(Database $database) {
        $this->database = $database;
        $this->item_repo = new Item_Repository($database);
    }

    /**
     * Publica item como post WordPress
     *
     * @param Publication $pub
     * @return array { post_id, post_url }
     * @throws \Exception
     */
    public function publish(Publication $pub) {
        $item = $this->item_repo->find($pub->get_item_id());
        if (!$item) {
            throw new \Exception(__('Item não encontrado', 'newsmast-curator'));
        }

        $category_id = $pub->get_wp_category_id();
        if (!$category_id || !term_exists($category_id, 'category')) {
            throw new \Exception(__('Categoria WordPress inválida ou não existe', 'newsmast-curator'));
        }

        $post_content = $this->build_post_content($item, $pub);
        $post_data = [
            'post_title'    => wp_strip_all_tags($item->get_title()),
            'post_content'  => $post_content,
            'post_excerpt'  => $item->get_excerpt() ?: wp_trim_words(wp_strip_all_tags($item->get_content()), 30),
            'post_status'   => 'publish',
            'post_author'   => $pub->get_published_by() ?: get_current_user_id() ?: 1,
            'post_category' => [(int) $category_id],
            'meta_input'    => [
                '_nc_source_id'   => $item->get_source_id(),
                '_nc_external_id' => $item->get_external_id(),
                '_nc_item_id'     => $item->get_id(),
                '_nc_origin_url'  => $item->get_url(),
            ],
        ];

        if ($item->get_published_date()) {
            $post_data['post_date'] = $item->get_published_date();
            $post_data['post_date_gmt'] = get_gmt_from_date($item->get_published_date());
        }

        $post_id = wp_insert_post($post_data, true);

        if (is_wp_error($post_id)) {
            throw new \Exception($post_id->get_error_message());
        }

        // Featured image: side-load se houver imagem remota
        $this->set_featured_image($post_id, $item);

        return [
            'post_id' => $post_id,
            'post_url' => get_permalink($post_id),
        ];
    }

    /**
     * Monta o conteúdo do post (HTML)
     */
    private function build_post_content(Item $item, Publication $pub) {
        $content = '';

        // Conteúdo customizado da publicação (pode ter sido editado pelo curador)
        $custom_content = $pub->get_content();
        if (!empty($custom_content) && $custom_content !== $item->get_content()) {
            $content .= '<p>' . wp_kses_post(nl2br($custom_content)) . '</p>';
        }

        // Conteúdo principal do item
        if (!empty($item->get_content())) {
            $content .= wp_kses_post($item->get_content());
        } elseif (!empty($item->get_excerpt())) {
            $content .= '<p>' . wp_kses_post($item->get_excerpt()) . '</p>';
        }

        // Atribuição da fonte
        if (!empty($item->get_url())) {
            $content .= "\n\n";
            $content .= '<p class="nc-source-attribution">';
            $content .= sprintf(
                /* translators: %s: source URL */
                __('Fonte original: %s', 'newsmast-curator'),
                '<a href="' . esc_url($item->get_url()) . '" target="_blank" rel="noopener">' . esc_html($item->get_url()) . '</a>'
            );
            $content .= '</p>';
        }

        return $content;
    }

    /**
     * Faz side-load da imagem do item como featured image do post
     */
    private function set_featured_image($post_id, Item $item) {
        // Já existe attachment local? Usa direto
        if ($item->get_image_local_id()) {
            set_post_thumbnail($post_id, $item->get_image_local_id());
            return;
        }

        $image_url = $item->get_image();
        if (empty($image_url)) {
            return;
        }

        // Side-load a imagem remota como attachment
        require_once(ABSPATH . 'wp-admin/includes/media.php');
        require_once(ABSPATH . 'wp-admin/includes/file.php');
        require_once(ABSPATH . 'wp-admin/includes/image.php');

        $tmp = download_url($image_url, 30);
        if (is_wp_error($tmp)) {
            return; // Falha silenciosa — post é publicado sem featured image
        }

        $filename = basename(wp_parse_url($image_url, PHP_URL_PATH)) ?: ('nc-' . $item->get_id() . '.jpg');
        $file_array = [
            'name'     => sanitize_file_name($filename),
            'tmp_name' => $tmp,
        ];

        $attachment_id = media_handle_sideload($file_array, $post_id, $item->get_title());

        if (is_wp_error($attachment_id)) {
            @unlink($tmp);
            return;
        }

        // Define alt text do attachment
        $alt_text = $item->build_alt_text();
        if (!empty($alt_text)) {
            update_post_meta($attachment_id, '_wp_attachment_image_alt', mb_substr($alt_text, 0, 250));
        }

        set_post_thumbnail($post_id, $attachment_id);
    }

    /**
     * Lista categorias do WordPress para o seletor
     *
     * @return array
     */
    public static function get_categories() {
        $cats = get_categories([
            'hide_empty' => false,
            'orderby'    => 'name',
            'order'      => 'ASC',
        ]);

        return array_map(function($c) {
            return [
                'id'    => (int) $c->term_id,
                'name'  => $c->name,
                'slug'  => $c->slug,
                'count' => (int) $c->count,
            ];
        }, $cats);
    }
}
