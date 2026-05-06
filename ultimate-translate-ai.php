<?php
/**
 * Plugin Name: Ultimate Translate AI
 * Plugin URI:  https://costaluztransfers.com/
 * Description: AI-assisted multilingual translation for WordPress and Elementor. Creates linked translated copies of posts/pages using DeepL, Google Translate, OpenAI, Gemini or Claude.
 * Version:     0.1.0
 * Author:      Daniel Jay
 * License:     GPL-2.0-or-later
 * Text Domain: ultimate-translate-ai
 * Requires at least: 6.5
 * Requires PHP: 7.4
 */

if (!defined('ABSPATH')) {
    exit;
}

define('UTAI_VERSION', '0.1.0');
define('UTAI_FILE', __FILE__);
define('UTAI_PATH', plugin_dir_path(__FILE__));
define('UTAI_URL', plugin_dir_url(__FILE__));

require_once UTAI_PATH . 'includes/class-utai-plugin.php';
require_once UTAI_PATH . 'includes/class-utai-admin.php';
require_once UTAI_PATH . 'includes/class-utai-translator.php';
require_once UTAI_PATH . 'includes/class-utai-elementor.php';
require_once UTAI_PATH . 'includes/class-utai-frontend.php';

register_activation_hook(__FILE__, ['UTAI_Plugin', 'activate']);
register_deactivation_hook(__FILE__, ['UTAI_Plugin', 'deactivate']);

add_action('plugins_loaded', function () {
    UTAI_Plugin::instance();
});
