<?php
/**
 * Model de Item Coletado
 *
 * @package NewsmastCurator
 * @subpackage Models
 */

namespace NewsmastCurator\Models;

class Item {
    private $id = 0;
    private $source_id = 0;
    private $external_id = '';
    private $title = '';
    private $content = '';
    private $excerpt = '';
    private $url = '';
    private $image_url = null;
    private $image_local_id = null;
    private $author = null;
    private $published_date = null;
    private $metadata = [];
    private $hash = '';
    private $curated = 0;
    private $curated_by = null;
    private $curated_at = null;
    private $collected_at = '';
    private $collection_type = 'manual';

    // Getters
    public function get_id() { return $this->id; }
    public function get_source_id() { return $this->source_id; }
    public function get_external_id() { return $this->external_id; }
    public function get_title() { return $this->title; }
    public function get_content() { return $this->content; }
    public function get_excerpt() { return $this->excerpt; }
    public function get_url() { return $this->url; }
    public function get_image_url() { return $this->image_url; }
    public function get_image_local_id() { return $this->image_local_id; }
    public function get_author() { return $this->author; }
    public function get_published_date() { return $this->published_date; }
    public function get_metadata($key = null) {
        if ($key === null) return $this->metadata;
        return $this->metadata[$key] ?? null;
    }
    public function get_hash() { return $this->hash; }
    public function is_curated() { return (bool) $this->curated; }
    public function get_curated_by() { return $this->curated_by; }
    public function get_curated_at() { return $this->curated_at; }
    public function get_collected_at() { return $this->collected_at; }
    public function get_collection_type() { return $this->collection_type; }
    public function is_auto_collected() { return $this->collection_type === 'auto'; }

    // Setters
    public function set_id($id) { $this->id = (int) $id; }
    public function set_source_id($id) { $this->source_id = (int) $id; }
    public function set_external_id($id) { $this->external_id = sanitize_text_field($id); }
    public function set_title($title) { $this->title = sanitize_text_field($title); }
    public function set_content($content) { $this->content = wp_kses_post($content); }
    public function set_excerpt($excerpt) { $this->excerpt = sanitize_textarea_field($excerpt); }
    public function set_url($url) { $this->url = esc_url_raw($url); }
    public function set_image_url($url) { $this->image_url = $url ? esc_url_raw($url) : null; }
    public function set_image_local_id($id) { $this->image_local_id = $id ? (int) $id : null; }
    public function set_author($author) { $this->author = $author ? sanitize_text_field($author) : null; }
    public function set_published_date($date) { $this->published_date = $date; }

    public function set_metadata($metadata) {
        if (is_string($metadata)) {
            $metadata = json_decode($metadata, true) ?? [];
        }
        $this->metadata = $metadata;
    }

    public function set_curated_by($user_id) { $this->curated_by = $user_id ? (int) $user_id : null; }
    public function set_curated_at($datetime) { $this->curated_at = $datetime; }
    public function set_collected_at($datetime) { $this->collected_at = $datetime; }
    public function set_collection_type($type) { $this->collection_type = in_array($type, ['manual', 'auto']) ? $type : 'manual'; }

    /**
     * Gera hash MD5 do conteúdo para detectar duplicatas
     *
     * @return string
     */
    public function generate_hash() {
        $content_for_hash = $this->title . $this->content . $this->url;
        $this->hash = md5($content_for_hash);
        return $this->hash;
    }

    /**
     * Define hash manualmente
     *
     * @param string $hash
     */
    public function set_hash($hash) {
        $this->hash = $hash;
    }

    /**
     * Marca item como curado
     *
     * @param int $user_id ID do usuário
     * @return void
     */
    public function mark_as_curated($user_id) {
        $this->curated = 1;
        $this->curated_by = (int) $user_id;
        $this->curated_at = current_time('mysql');
    }

    /**
     * Remove curadoria
     *
     * @return void
     */
    public function unmark_curated() {
        $this->curated = 0;
        $this->curated_by = null;
        $this->curated_at = null;
    }

    /**
     * Obtém conteúdo formatado para publicação
     *
     * @param int $max_length Tamanho máximo
     * @return string
     */
    public function get_formatted_content($max_length = 500) {
        $template = get_option('nc_post_template', "{title}\n\n{excerpt}\n\n{url}");
        $hashtags = get_option('nc_default_hashtags', '');

        // Prepara excerpt
        $excerpt = $this->excerpt;
        if (empty($excerpt) && !empty($this->content)) {
            $excerpt = wp_trim_words(strip_tags($this->content), 30, '...');
        }

        // Substitui placeholders
        $content = str_replace(
            ['{title}', '{excerpt}', '{url}', '{hashtags}'],
            [$this->title, $excerpt, $this->url, $hashtags],
            $template
        );

        // Limita tamanho
        if (mb_strlen($content) > $max_length) {
            $content = mb_substr($content, 0, $max_length - 3) . '...';
        }

        return $content;
    }

    /**
     * Obtém texto de preview
     *
     * @param int $length Tamanho do preview
     * @return string
     */
    public function get_preview_text($length = 150) {
        $text = strip_tags($this->content);
        if (mb_strlen($text) > $length) {
            $text = mb_substr($text, 0, $length) . '...';
        }
        return $text;
    }

    /**
     * Verifica se tem imagem
     *
     * @return bool
     */
    public function has_image() {
        return !empty($this->image_url) || !empty($this->image_local_id);
    }

    /**
     * Obtém URL da imagem (local ou remota)
     *
     * @return string|null
     */
    public function get_image() {
        if ($this->image_local_id) {
            $url = wp_get_attachment_url($this->image_local_id);
            if ($url) {
                return $url;
            }
        }

        return $this->image_url;
    }

    /**
     * Cria instância a partir de linha do banco
     *
     * @param object $row
     * @return Item
     */
    public static function from_row($row) {
        $item = new self();

        $item->set_id($row->id ?? 0);
        $item->set_source_id($row->source_id ?? 0);
        $item->set_external_id($row->external_id ?? '');
        $item->set_title($row->title ?? '');
        $item->set_content($row->content ?? '');
        $item->set_excerpt($row->excerpt ?? '');
        $item->set_url($row->url ?? '');
        $item->set_image_url($row->image_url ?? null);
        $item->set_image_local_id($row->image_local_id ?? null);
        $item->set_author($row->author ?? null);
        $item->set_published_date($row->published_date ?? null);
        $item->set_metadata($row->metadata ?? '{}');
        $item->set_hash($row->hash ?? '');
        $item->curated = (int) ($row->curated ?? 0);
        $item->set_curated_by($row->curated_by ?? null);
        $item->set_curated_at($row->curated_at ?? null);
        $item->set_collected_at($row->collected_at ?? current_time('mysql'));
        $item->set_collection_type($row->collection_type ?? 'manual');

        return $item;
    }

    /**
     * Converte para array (para banco)
     *
     * @return array
     */
    public function to_array() {
        // Gera hash se não existir
        if (empty($this->hash)) {
            $this->generate_hash();
        }

        return [
            'source_id' => $this->source_id,
            'external_id' => $this->external_id,
            'title' => $this->title,
            'content' => $this->content,
            'excerpt' => $this->excerpt,
            'url' => $this->url,
            'image_url' => $this->image_url,
            'image_local_id' => $this->image_local_id,
            'author' => $this->author,
            'published_date' => $this->published_date,
            'metadata' => wp_json_encode($this->metadata),
            'hash' => $this->hash,
            'curated' => $this->curated,
            'curated_by' => $this->curated_by,
            'curated_at' => $this->curated_at,
            'collected_at' => $this->id === 0 ? current_time('mysql') : $this->collected_at,
            'collection_type' => $this->collection_type,
        ];
    }

    /**
     * Converte para resposta de API
     *
     * @return array
     */
    /**
     * Gera texto alternativo estruturado para a imagem do item
     *
     * @return string Alt text (máx 1500 chars, limite do Mastodon)
     */
    public function build_alt_text() {
        $parts = [];

        $title = wp_strip_all_tags($this->title);
        if (!empty($title)) {
            $parts[] = $title;
        }

        $author = $this->author;
        if (!empty($author)) {
            $parts[] = sprintf(__('Autoria: %s', 'newsmast-curator'), $author);
        }

        $excerpt = wp_strip_all_tags($this->excerpt);
        if (empty($excerpt)) {
            $excerpt = wp_strip_all_tags($this->content);
        }
        if (!empty($excerpt)) {
            $parts[] = wp_trim_words($excerpt, 60, '…');
        }

        $metadata = $this->metadata;
        if (is_array($metadata) && !empty($metadata)) {
            $relevant_fields = [
                'material-tecnica-2' => __('Material/Técnica', 'newsmast-curator'),
                'dimensoes'          => __('Dimensões', 'newsmast-curator'),
                'data-de-producao'   => __('Data de produção', 'newsmast-curator'),
                'instalacao'         => __('Museu', 'newsmast-curator'),
                'localizacao'        => __('Coleção', 'newsmast-curator'),
            ];
            foreach ($relevant_fields as $slug => $label) {
                if (isset($metadata[$slug]) && !empty($metadata[$slug]['value_as_string'])) {
                    $value = wp_strip_all_tags($metadata[$slug]['value_as_string']);
                    if (!empty($value)) {
                        $parts[] = "{$label}: {$value}";
                    }
                }
            }
        }

        $url = $this->url;
        if (!empty($url)) {
            $parts[] = sprintf(__('Fonte: %s', 'newsmast-curator'), $url);
        }

        $alt_text = implode("\n", $parts);

        if (mb_strlen($alt_text) > 1500) {
            $alt_text = mb_substr($alt_text, 0, 1497) . '…';
        }

        return $alt_text;
    }

    public function to_api_response() {
        return [
            'id' => $this->id,
            'source_id' => $this->source_id,
            'external_id' => $this->external_id,
            'title' => $this->title,
            'content' => $this->content,
            'excerpt' => $this->excerpt,
            'url' => $this->url,
            'image_url' => $this->get_image(),
            'has_image' => $this->has_image(),
            'author' => $this->author,
            'published_date' => $this->published_date,
            'metadata' => $this->metadata,
            'curated' => (bool) $this->curated,
            'curated_by' => $this->curated_by,
            'curated_at' => $this->curated_at,
            'collected_at' => $this->collected_at,
            'collection_type' => $this->collection_type,
            'preview_text' => $this->get_preview_text(),
            'formatted_content' => $this->get_formatted_content(),
        ];
    }
}
