<?php
if (!defined('ABSPATH')) exit;

final class UTAI_Plugin {
    private static $instance = null;

    public static function instance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action('init', [$this, 'load_textdomain']);
        add_action('init', [$this, 'register_meta']);
        new UTAI_Admin();
        new UTAI_Frontend();
    }

    public function load_textdomain() {
        load_plugin_textdomain('ultimate-translate-ai', false, dirname(plugin_basename(UTAI_FILE)) . '/languages');
    }

    public function register_meta() {
        $post_types = get_post_types(['public' => true], 'names');
        foreach ($post_types as $post_type) {
            register_post_meta($post_type, '_utai_language', [
                'single' => true,
                'type' => 'string',
                'sanitize_callback' => 'sanitize_text_field',
                'show_in_rest' => true,
                'auth_callback' => function () { return current_user_can('edit_posts'); },
            ]);
            register_post_meta($post_type, '_utai_source_post_id', [
                'single' => true,
                'type' => 'integer',
                'sanitize_callback' => 'absint',
                'show_in_rest' => true,
                'auth_callback' => function () { return current_user_can('edit_posts'); },
            ]);
            register_post_meta($post_type, '_utai_translation_group', [
                'single' => true,
                'type' => 'string',
                'sanitize_callback' => 'sanitize_text_field',
                'show_in_rest' => true,
                'auth_callback' => function () { return current_user_can('edit_posts'); },
            ]);
        }
    }

    public static function activate() {
        $defaults = [
            'default_language' => 'en',
            'languages' => "en|English\nes|Español\nfr|Français\nde|Deutsch",
            'provider' => 'deepl',
            'tone' => 'natural, professional, SEO-friendly',
            'openai_model' => 'gpt-4.1-mini',
            'gemini_model' => 'gemini-2.5-flash',
            'claude_model' => 'claude-sonnet-4-5',
            'google_project_id' => '',
            'deepl_formality' => 'default',
        ];
        if (!get_option('utai_settings')) {
            add_option('utai_settings', $defaults, '', false);
        }
    }

    public static function deactivate() {}

    public static function settings() {
        $defaults = [
            'default_language' => 'en',
            'languages' => "en|English\nes|Español\nfr|Français\nde|Deutsch",
            'provider' => 'deepl',
            'tone' => 'natural, professional, SEO-friendly',
            'openai_api_key' => '',
            'openai_model' => 'gpt-4.1-mini',
            'gemini_api_key' => '',
            'gemini_model' => 'gemini-2.5-flash',
            'claude_api_key' => '',
            'claude_model' => 'claude-sonnet-4-5',
            'deepl_api_key' => '',
            'deepl_formality' => 'default',
            'google_api_key' => '',
            'google_project_id' => '',
        ];
        return wp_parse_args(get_option('utai_settings', []), $defaults);
    }

    public static function languages() {
        $settings = self::settings();
        $rows = preg_split('/\r\n|\r|\n/', (string) $settings['languages']);
        $languages = [];
        foreach ($rows as $row) {
            $parts = array_map('trim', explode('|', $row, 2));
            if (count($parts) === 2 && $parts[0] !== '' && $parts[1] !== '') {
                $languages[sanitize_key($parts[0])] = sanitize_text_field($parts[1]);
            }
        }
        return $languages ?: ['en' => 'English'];
    }
}
