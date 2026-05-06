<?php
if (!defined('ABSPATH')) exit;

class UTAI_Admin {
    public function __construct() {
        add_action('admin_menu', [$this, 'admin_menu']);
        add_action('admin_init', [$this, 'register_settings']);
        add_action('add_meta_boxes', [$this, 'add_meta_boxes']);
        add_action('save_post', [$this, 'save_language_meta']);
        add_action('admin_post_utai_translate_post', [$this, 'handle_translate_post']);
        add_filter('post_row_actions', [$this, 'row_actions'], 10, 2);
        add_filter('page_row_actions', [$this, 'row_actions'], 10, 2);
    }

    public function admin_menu() {
        add_menu_page(
            __('Ultimate Translate AI', 'ultimate-translate-ai'),
            __('Ultimate Translate AI', 'ultimate-translate-ai'),
            'manage_options',
            'ultimate-translate-ai',
            [$this, 'settings_page'],
            'dashicons-translation',
            58
        );
    }

    public function register_settings() {
        register_setting('utai_settings_group', 'utai_settings', [$this, 'sanitize_settings']);
    }

    public function sanitize_settings($input) {
        $old = UTAI_Plugin::settings();
        $clean = [];
        $clean['default_language'] = sanitize_key($input['default_language'] ?? $old['default_language']);
        $clean['languages'] = sanitize_textarea_field($input['languages'] ?? $old['languages']);
        $clean['provider'] = sanitize_key($input['provider'] ?? $old['provider']);
        $clean['tone'] = sanitize_text_field($input['tone'] ?? $old['tone']);
        $clean['openai_api_key'] = sanitize_text_field($input['openai_api_key'] ?? $old['openai_api_key']);
        $clean['openai_model'] = sanitize_text_field($input['openai_model'] ?? $old['openai_model']);
        $clean['gemini_api_key'] = sanitize_text_field($input['gemini_api_key'] ?? $old['gemini_api_key']);
        $clean['gemini_model'] = sanitize_text_field($input['gemini_model'] ?? $old['gemini_model']);
        $clean['claude_api_key'] = sanitize_text_field($input['claude_api_key'] ?? $old['claude_api_key']);
        $clean['claude_model'] = sanitize_text_field($input['claude_model'] ?? $old['claude_model']);
        $clean['deepl_api_key'] = sanitize_text_field($input['deepl_api_key'] ?? $old['deepl_api_key']);
        $clean['deepl_formality'] = sanitize_key($input['deepl_formality'] ?? $old['deepl_formality']);
        $clean['google_api_key'] = sanitize_text_field($input['google_api_key'] ?? $old['google_api_key']);
        $clean['google_project_id'] = sanitize_text_field($input['google_project_id'] ?? $old['google_project_id']);
        return $clean;
    }

    public function settings_page() {
        if (!current_user_can('manage_options')) return;
        $s = UTAI_Plugin::settings();
        ?>
        <div class="wrap">
            <h1>Ultimate Translate AI</h1>
            <p><strong>Autor:</strong> Daniel Jay</p>
            <?php if (isset($_GET['utai_notice'])): ?>
                <div class="notice notice-success is-dismissible"><p><?php echo esc_html(wp_unslash($_GET['utai_notice'])); ?></p></div>
            <?php endif; ?>
            <?php if (isset($_GET['utai_error'])): ?>
                <div class="notice notice-error is-dismissible"><p><?php echo esc_html(wp_unslash($_GET['utai_error'])); ?></p></div>
            <?php endif; ?>
            <form method="post" action="options.php">
                <?php settings_fields('utai_settings_group'); ?>
                <table class="form-table" role="presentation">
                    <tr><th>Idioma principal</th><td><input name="utai_settings[default_language]" value="<?php echo esc_attr($s['default_language']); ?>" class="regular-text" /><p class="description">Ejemplo: en, es, fr, de.</p></td></tr>
                    <tr><th>Idiomas</th><td><textarea name="utai_settings[languages]" rows="6" class="large-text code"><?php echo esc_textarea($s['languages']); ?></textarea><p class="description">Formato: código|Nombre. Un idioma por línea.</p></td></tr>
                    <tr><th>Motor predeterminado</th><td><select name="utai_settings[provider]">
                        <?php foreach (['deepl'=>'DeepL','google'=>'Google Translate','openai'=>'ChatGPT / OpenAI','gemini'=>'Gemini','claude'=>'Claude'] as $k=>$v): ?>
                            <option value="<?php echo esc_attr($k); ?>" <?php selected($s['provider'], $k); ?>><?php echo esc_html($v); ?></option>
                        <?php endforeach; ?>
                    </select></td></tr>
                    <tr><th>Tono de traducción</th><td><input name="utai_settings[tone]" value="<?php echo esc_attr($s['tone']); ?>" class="large-text" /></td></tr>
                    <tr><th>DeepL API key</th><td><input type="password" name="utai_settings[deepl_api_key]" value="<?php echo esc_attr($s['deepl_api_key']); ?>" class="large-text" autocomplete="off" /></td></tr>
                    <tr><th>Google API key</th><td><input type="password" name="utai_settings[google_api_key]" value="<?php echo esc_attr($s['google_api_key']); ?>" class="large-text" autocomplete="off" /></td></tr>
                    <tr><th>OpenAI API key / modelo</th><td><input type="password" name="utai_settings[openai_api_key]" value="<?php echo esc_attr($s['openai_api_key']); ?>" class="large-text" autocomplete="off" /><br><input name="utai_settings[openai_model]" value="<?php echo esc_attr($s['openai_model']); ?>" class="regular-text" /></td></tr>
                    <tr><th>Gemini API key / modelo</th><td><input type="password" name="utai_settings[gemini_api_key]" value="<?php echo esc_attr($s['gemini_api_key']); ?>" class="large-text" autocomplete="off" /><br><input name="utai_settings[gemini_model]" value="<?php echo esc_attr($s['gemini_model']); ?>" class="regular-text" /></td></tr>
                    <tr><th>Claude API key / modelo</th><td><input type="password" name="utai_settings[claude_api_key]" value="<?php echo esc_attr($s['claude_api_key']); ?>" class="large-text" autocomplete="off" /><br><input name="utai_settings[claude_model]" value="<?php echo esc_attr($s['claude_model']); ?>" class="regular-text" /></td></tr>
                </table>
                <?php submit_button('Guardar ajustes'); ?>
            </form>
            <hr>
            <h2>Shortcode</h2>
            <p>Usa <code>[utai_language_switcher]</code> para mostrar enlaces a las traducciones relacionadas.</p>
        </div>
        <?php
    }

    public function add_meta_boxes() {
        foreach (get_post_types(['public' => true], 'names') as $post_type) {
            add_meta_box('utai_box', 'Ultimate Translate AI', [$this, 'meta_box'], $post_type, 'side', 'high');
        }
    }

    public function meta_box($post) {
        wp_nonce_field('utai_save_meta', 'utai_meta_nonce');
        $language = get_post_meta($post->ID, '_utai_language', true) ?: UTAI_Plugin::settings()['default_language'];
        $languages = UTAI_Plugin::languages();
        ?>
        <p><label for="utai_language"><strong>Idioma de esta página</strong></label></p>
        <select name="utai_language" id="utai_language" style="width:100%;">
            <?php foreach ($languages as $code => $name): ?>
                <option value="<?php echo esc_attr($code); ?>" <?php selected($language, $code); ?>><?php echo esc_html($name . ' (' . $code . ')'); ?></option>
            <?php endforeach; ?>
        </select>
        <hr>
        <p><strong>Crear traducción</strong></p>
        <?php foreach ($languages as $code => $name): if ($code === $language) continue; ?>
            <?php $url = wp_nonce_url(admin_url('admin-post.php?action=utai_translate_post&post_id=' . absint($post->ID) . '&target=' . rawurlencode($code)), 'utai_translate_' . $post->ID); ?>
            <p><a class="button button-secondary" href="<?php echo esc_url($url); ?>">Traducir a <?php echo esc_html($name); ?></a></p>
        <?php endforeach; ?>
        <?php
    }

    public function save_language_meta($post_id) {
        if (!isset($_POST['utai_meta_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['utai_meta_nonce'])), 'utai_save_meta')) return;
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
        if (!current_user_can('edit_post', $post_id)) return;
        if (isset($_POST['utai_language'])) update_post_meta($post_id, '_utai_language', sanitize_key($_POST['utai_language']));
        if (!get_post_meta($post_id, '_utai_translation_group', true)) update_post_meta($post_id, '_utai_translation_group', wp_generate_uuid4());
    }

    public function row_actions($actions, $post) {
        if (!current_user_can('edit_post', $post->ID)) return $actions;
        $url = admin_url('post.php?post=' . absint($post->ID) . '&action=edit#utai_box');
        $actions['utai'] = '<a href="' . esc_url($url) . '">Ultimate Translate AI</a>';
        return $actions;
    }

    public function handle_translate_post() {
        $post_id = absint($_GET['post_id'] ?? 0);
        $target = sanitize_key($_GET['target'] ?? '');
        if (!$post_id || !$target || !current_user_can('edit_post', $post_id)) wp_die('Permiso denegado.');
        check_admin_referer('utai_translate_' . $post_id);

        $post = get_post($post_id);
        if (!$post) wp_die('Contenido no encontrado.');

        $translator = new UTAI_Translator();
        $source_lang = get_post_meta($post_id, '_utai_language', true) ?: UTAI_Plugin::settings()['default_language'];
        $result = $this->create_translation($post, $source_lang, $target, $translator);

        if (is_wp_error($result)) {
            wp_safe_redirect(add_query_arg('utai_error', rawurlencode($result->get_error_message()), admin_url('admin.php?page=ultimate-translate-ai')));
            exit;
        }
        wp_safe_redirect(admin_url('post.php?post=' . absint($result) . '&action=edit'));
        exit;
    }

    private function create_translation($post, $source_lang, $target_lang, UTAI_Translator $translator) {
        $group = get_post_meta($post->ID, '_utai_translation_group', true) ?: wp_generate_uuid4();
        update_post_meta($post->ID, '_utai_translation_group', $group);

        $existing = get_posts([
            'post_type' => $post->post_type,
            'post_status' => ['draft','publish','pending','private'],
            'meta_query' => [
                ['key' => '_utai_translation_group', 'value' => $group],
                ['key' => '_utai_language', 'value' => $target_lang],
            ],
            'fields' => 'ids',
            'numberposts' => 1,
        ]);
        if (!empty($existing)) return new WP_Error('utai_exists', 'Ya existe una traducción para ese idioma.');

        $translated_title = $translator->translate($post->post_title, $source_lang, $target_lang);
        if (is_wp_error($translated_title)) return $translated_title;

        $translated_content = $translator->translate($post->post_content, $source_lang, $target_lang);
        if (is_wp_error($translated_content)) return $translated_content;

        $translated_excerpt = $post->post_excerpt ? $translator->translate($post->post_excerpt, $source_lang, $target_lang) : '';
        if (is_wp_error($translated_excerpt)) return $translated_excerpt;

        $new_id = wp_insert_post([
            'post_type' => $post->post_type,
            'post_title' => wp_strip_all_tags($translated_title),
            'post_content' => $translated_content,
            'post_excerpt' => $translated_excerpt,
            'post_status' => 'draft',
            'post_author' => get_current_user_id(),
            'post_parent' => $post->post_parent,
        ], true);
        if (is_wp_error($new_id)) return $new_id;

        update_post_meta($new_id, '_utai_language', $target_lang);
        update_post_meta($new_id, '_utai_source_post_id', $post->ID);
        update_post_meta($new_id, '_utai_translation_group', $group);

        $this->copy_selected_meta($post->ID, $new_id, $source_lang, $target_lang, $translator);
        return $new_id;
    }

    private function copy_selected_meta($source_id, $new_id, $source_lang, $target_lang, UTAI_Translator $translator) {
        $copy_keys = ['_wp_page_template', '_thumbnail_id', '_elementor_edit_mode', '_elementor_template_type', '_elementor_version', '_elementor_page_settings'];
        foreach ($copy_keys as $key) {
            $value = get_post_meta($source_id, $key, true);
            if ($value !== '') update_post_meta($new_id, $key, $value);
        }

        $elementor_data = get_post_meta($source_id, '_elementor_data', true);
        if ($elementor_data) {
            $translated = UTAI_Elementor::translate_elementor_json($elementor_data, $source_lang, $target_lang, $translator);
            if (!is_wp_error($translated)) update_post_meta($new_id, '_elementor_data', wp_slash($translated));
        }

        foreach (['_yoast_wpseo_title', '_yoast_wpseo_metadesc', '_yoast_wpseo_focuskw'] as $seo_key) {
            $value = get_post_meta($source_id, $seo_key, true);
            if ($value) {
                $translated = $translator->translate($value, $source_lang, $target_lang);
                if (!is_wp_error($translated)) update_post_meta($new_id, $seo_key, $translated);
            }
        }
    }
}
