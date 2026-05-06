<?php
if (!defined('ABSPATH')) exit;

class UTAI_Frontend {
    public function __construct() {
        add_shortcode('utai_language_switcher', [$this, 'shortcode']);
        add_action('wp_head', [$this, 'hreflang_tags']);
    }

    public function shortcode() {
        if (!is_singular()) return '';
        $post_id = get_the_ID();
        $translations = $this->translations($post_id);
        if (!$translations) return '';
        $current = get_post_meta($post_id, '_utai_language', true);
        $langs = UTAI_Plugin::languages();
        $html = '<nav class="utai-language-switcher" aria-label="Language switcher">';
        foreach ($translations as $lang => $id) {
            $label = $langs[$lang] ?? strtoupper($lang);
            $class = $lang === $current ? ' class="is-active"' : '';
            $html .= '<a' . $class . ' href="' . esc_url(get_permalink($id)) . '">' . esc_html($label) . '</a> ';
        }
        $html .= '</nav>';
        return $html;
    }

    public function hreflang_tags() {
        if (!is_singular()) return;
        $translations = $this->translations(get_the_ID());
        foreach ($translations as $lang => $id) {
            echo '<link rel="alternate" hreflang="' . esc_attr($lang) . '" href="' . esc_url(get_permalink($id)) . '" />' . "\n";
        }
    }

    private function translations($post_id) {
        $group = get_post_meta($post_id, '_utai_translation_group', true);
        if (!$group) return [];
        $posts = get_posts([
            'post_type' => get_post_type($post_id),
            'post_status' => 'publish',
            'numberposts' => -1,
            'meta_key' => '_utai_translation_group',
            'meta_value' => $group,
        ]);
        $out = [];
        foreach ($posts as $p) {
            $lang = get_post_meta($p->ID, '_utai_language', true);
            if ($lang) $out[$lang] = $p->ID;
        }
        return $out;
    }
}
