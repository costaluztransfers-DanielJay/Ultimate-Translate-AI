<?php
if (!defined('ABSPATH')) exit;

class UTAI_Translator {
    private $settings;

    public function __construct() {
        $this->settings = UTAI_Plugin::settings();
    }

    public function translate($text, $source_lang, $target_lang) {
        $text = (string) $text;
        if (trim($text) === '') return $text;
        $provider = $this->settings['provider'];
        switch ($provider) {
            case 'google': return $this->google($text, $source_lang, $target_lang);
            case 'openai': return $this->openai($text, $source_lang, $target_lang);
            case 'gemini': return $this->gemini($text, $source_lang, $target_lang);
            case 'claude': return $this->claude($text, $source_lang, $target_lang);
            case 'deepl':
            default: return $this->deepl($text, $source_lang, $target_lang);
        }
    }

    private function prompt($text, $source_lang, $target_lang) {
        $tone = $this->settings['tone'] ?: 'natural and professional';
        return "Translate the following website content from {$source_lang} to {$target_lang}. Keep HTML tags, shortcodes, URLs, placeholders, line breaks and variables unchanged. Use this tone: {$tone}. Return only the translated content, with no explanations.\n\nCONTENT:\n" . $text;
    }

    private function deepl($text, $source_lang, $target_lang) {
        if (empty($this->settings['deepl_api_key'])) return new WP_Error('utai_no_key', 'Falta la API key de DeepL.');
        $host = (strpos($this->settings['deepl_api_key'], ':fx') !== false) ? 'https://api-free.deepl.com/v2/translate' : 'https://api.deepl.com/v2/translate';
        $body = [
            'text' => [$text],
            'target_lang' => strtoupper($target_lang),
        ];
        if ($source_lang) $body['source_lang'] = strtoupper($source_lang);
        $response = wp_remote_post($host, [
            'timeout' => 45,
            'headers' => [
                'Authorization' => 'DeepL-Auth-Key ' . $this->settings['deepl_api_key'],
                'Content-Type' => 'application/json',
            ],
            'body' => wp_json_encode($body),
        ]);
        return $this->parse_deepl($response);
    }

    private function parse_deepl($response) {
        if (is_wp_error($response)) return $response;
        $code = wp_remote_retrieve_response_code($response);
        $data = json_decode(wp_remote_retrieve_body($response), true);
        if ($code < 200 || $code >= 300) return new WP_Error('utai_deepl_error', 'DeepL error: ' . wp_remote_retrieve_body($response));
        return $data['translations'][0]['text'] ?? new WP_Error('utai_deepl_parse', 'Respuesta inesperada de DeepL.');
    }

    private function google($text, $source_lang, $target_lang) {
        if (empty($this->settings['google_api_key'])) return new WP_Error('utai_no_key', 'Falta la API key de Google Translate.');
        $url = 'https://translation.googleapis.com/language/translate/v2?key=' . rawurlencode($this->settings['google_api_key']);
        $response = wp_remote_post($url, [
            'timeout' => 45,
            'headers' => ['Content-Type' => 'application/json'],
            'body' => wp_json_encode([
                'q' => $text,
                'source' => $source_lang,
                'target' => $target_lang,
                'format' => 'html',
            ]),
        ]);
        if (is_wp_error($response)) return $response;
        $code = wp_remote_retrieve_response_code($response);
        $data = json_decode(wp_remote_retrieve_body($response), true);
        if ($code < 200 || $code >= 300) return new WP_Error('utai_google_error', 'Google Translate error: ' . wp_remote_retrieve_body($response));
        return $data['data']['translations'][0]['translatedText'] ?? new WP_Error('utai_google_parse', 'Respuesta inesperada de Google Translate.');
    }

    private function openai($text, $source_lang, $target_lang) {
        if (empty($this->settings['openai_api_key'])) return new WP_Error('utai_no_key', 'Falta la API key de OpenAI.');
        $response = wp_remote_post('https://api.openai.com/v1/responses', [
            'timeout' => 60,
            'headers' => [
                'Authorization' => 'Bearer ' . $this->settings['openai_api_key'],
                'Content-Type' => 'application/json',
            ],
            'body' => wp_json_encode([
                'model' => $this->settings['openai_model'],
                'input' => $this->prompt($text, $source_lang, $target_lang),
            ]),
        ]);
        if (is_wp_error($response)) return $response;
        $code = wp_remote_retrieve_response_code($response);
        $data = json_decode(wp_remote_retrieve_body($response), true);
        if ($code < 200 || $code >= 300) return new WP_Error('utai_openai_error', 'OpenAI error: ' . wp_remote_retrieve_body($response));
        return $data['output_text'] ?? $this->extract_openai_text($data);
    }

    private function extract_openai_text($data) {
        if (!empty($data['output']) && is_array($data['output'])) {
            foreach ($data['output'] as $item) {
                if (!empty($item['content']) && is_array($item['content'])) {
                    foreach ($item['content'] as $content) {
                        if (isset($content['text'])) return $content['text'];
                    }
                }
            }
        }
        return new WP_Error('utai_openai_parse', 'Respuesta inesperada de OpenAI.');
    }

    private function gemini($text, $source_lang, $target_lang) {
        if (empty($this->settings['gemini_api_key'])) return new WP_Error('utai_no_key', 'Falta la API key de Gemini.');
        $model = rawurlencode($this->settings['gemini_model']);
        $url = "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key=" . rawurlencode($this->settings['gemini_api_key']);
        $response = wp_remote_post($url, [
            'timeout' => 60,
            'headers' => ['Content-Type' => 'application/json'],
            'body' => wp_json_encode([
                'contents' => [[ 'parts' => [[ 'text' => $this->prompt($text, $source_lang, $target_lang) ]] ]],
            ]),
        ]);
        if (is_wp_error($response)) return $response;
        $code = wp_remote_retrieve_response_code($response);
        $data = json_decode(wp_remote_retrieve_body($response), true);
        if ($code < 200 || $code >= 300) return new WP_Error('utai_gemini_error', 'Gemini error: ' . wp_remote_retrieve_body($response));
        return $data['candidates'][0]['content']['parts'][0]['text'] ?? new WP_Error('utai_gemini_parse', 'Respuesta inesperada de Gemini.');
    }

    private function claude($text, $source_lang, $target_lang) {
        if (empty($this->settings['claude_api_key'])) return new WP_Error('utai_no_key', 'Falta la API key de Claude.');
        $response = wp_remote_post('https://api.anthropic.com/v1/messages', [
            'timeout' => 60,
            'headers' => [
                'x-api-key' => $this->settings['claude_api_key'],
                'anthropic-version' => '2023-06-01',
                'Content-Type' => 'application/json',
            ],
            'body' => wp_json_encode([
                'model' => $this->settings['claude_model'],
                'max_tokens' => 4096,
                'messages' => [[ 'role' => 'user', 'content' => $this->prompt($text, $source_lang, $target_lang) ]],
            ]),
        ]);
        if (is_wp_error($response)) return $response;
        $code = wp_remote_retrieve_response_code($response);
        $data = json_decode(wp_remote_retrieve_body($response), true);
        if ($code < 200 || $code >= 300) return new WP_Error('utai_claude_error', 'Claude error: ' . wp_remote_retrieve_body($response));
        return $data['content'][0]['text'] ?? new WP_Error('utai_claude_parse', 'Respuesta inesperada de Claude.');
    }
}
