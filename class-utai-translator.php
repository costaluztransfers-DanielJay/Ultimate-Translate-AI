<?php
if (!defined('ABSPATH')) exit;

class UTAI_Elementor {
    private static $translatable_keys = [
        'title','editor','text','button_text','link_text','description','caption','html','shortcode','tabs','items','icon_list','testimonial_content','testimonial_name','price','period','features_list','alert_title','alert_description','form_name','field_label','placeholder','submit_button_text'
    ];

    public static function translate_elementor_json($json, $source_lang, $target_lang, UTAI_Translator $translator) {
        $data = json_decode($json, true);
        if (json_last_error() !== JSON_ERROR_NONE) return new WP_Error('utai_json', 'Elementor data JSON no válido.');
        $data = self::walk($data, $source_lang, $target_lang, $translator, null);
        if (is_wp_error($data)) return $data;
        return wp_json_encode($data);
    }

    private static function walk($value, $source_lang, $target_lang, UTAI_Translator $translator, $current_key) {
        if (is_array($value)) {
            $out = [];
            foreach ($value as $k => $v) {
                $out[$k] = self::walk($v, $source_lang, $target_lang, $translator, is_string($k) ? $k : $current_key);
                if (is_wp_error($out[$k])) return $out[$k];
            }
            return $out;
        }

        if (is_string($value) && self::should_translate($current_key, $value)) {
            $translated = $translator->translate($value, $source_lang, $target_lang);
            return is_wp_error($translated) ? $value : $translated;
        }
        return $value;
    }

    private static function should_translate($key, $value) {
        $trim = trim(wp_strip_all_tags($value));
        if ($trim === '' || strlen($trim) < 2) return false;
        if (preg_match('/^(#|https?:\/\/|mailto:|tel:|\{\{|\[)/i', $trim)) return false;
        if (preg_match('/^[\d\s\-\+\.,:;\/]+$/', $trim)) return false;
        if (!$key) return false;
        foreach (self::$translatable_keys as $allowed) {
            if ($key === $allowed || strpos($key, $allowed) !== false) return true;
        }
        return false;
    }
}
