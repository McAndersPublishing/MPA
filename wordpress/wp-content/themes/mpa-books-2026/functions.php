<?php
if (!defined('ABSPATH')) {
    exit;
}

add_action('after_setup_theme', 'mpa_theme_setup');
add_action('wp_enqueue_scripts', 'mpa_enqueue_assets');
add_filter('query_vars', 'mpa_register_query_vars');
add_action('pre_get_posts', 'mpa_filter_book_queries_by_language');
add_filter('locale', 'mpa_filter_locale_from_lang_query');
add_action('template_redirect', 'mpa_persist_language_preference_cookie', 1);
add_action('template_redirect', 'mpa_redirect_base_url_to_preferred_language', 2);

function mpa_theme_setup(): void
{
    add_theme_support('title-tag');
    add_theme_support('post-thumbnails');
    add_theme_support('menus');

    register_nav_menus([
        'primary' => __('Primary Menu', 'mpa-books-2026'),
        'sidebar' => __('Sidebar Menu', 'mpa-books-2026'),
    ]);

    load_theme_textdomain('mpa-books-2026', get_template_directory() . '/languages');
}

function mpa_enqueue_assets(): void
{
    wp_enqueue_style(
        'mpa-books-2026-style',
        get_stylesheet_uri(),
        [],
        wp_get_theme()->get('Version')
    );
}

function mpa_register_query_vars(array $vars): array
{
    $vars[] = 'lang';

    return $vars;
}

function mpa_get_current_language_code(): string
{
    $lang = get_query_var('lang');
    if (!is_string($lang) || $lang === '') {
        return '';
    }

    return sanitize_key($lang);
}

function mpa_get_language_locale_map(): array
{
    return [
        'en' => 'en_US',
        'es' => 'es_ES',
        'fr' => 'fr_FR',
        'de' => 'de_DE',
        'it' => 'it_IT',
        'pt' => 'pt_PT',
        'pt-br' => 'pt_BR',
        'nl' => 'nl_NL',
        'sv' => 'sv_SE',
        'no' => 'nb_NO',
        'da' => 'da_DK',
        'fi' => 'fi_FI',
        'pl' => 'pl_PL',
        'cs' => 'cs_CZ',
        'ro' => 'ro_RO',
        'hu' => 'hu_HU',
        'tr' => 'tr_TR',
        'el' => 'el',
        'ru' => 'ru_RU',
        'uk' => 'uk',
        'he' => 'he_IL',
        'ar' => 'ar',
        'ja' => 'ja',
        'ko' => 'ko_KR',
        'zh' => 'zh_CN',
        'zh-tw' => 'zh_TW',
    ];
}

function mpa_filter_locale_from_lang_query(string $locale): string
{
    $code = mpa_get_current_language_code();
    if ($code === '') {
        return $locale;
    }

    $map = mpa_get_language_locale_map();

    if (!empty($map[$code])) {
        return $map[$code];
    }

    return $locale;
}

function mpa_filter_book_queries_by_language(WP_Query $query): void
{
    if (is_admin() || !$query->is_main_query()) {
        return;
    }

    $lang = mpa_get_current_language_code();
    if ($lang === '') {
        return;
    }

    if (!($query->is_post_type_archive('mpa_book') || $query->is_tax('mpa_series') || $query->is_tax('mpa_genre') || $query->is_singular('mpa_book'))) {
        return;
    }

    $meta_query = $query->get('meta_query');
    if (!is_array($meta_query)) {
        $meta_query = [];
    }

    $meta_query[] = [
        'key' => 'book_language',
        'value' => $lang,
        'compare' => '=',
    ];

    $query->set('meta_query', $meta_query);
}

function mpa_get_available_languages(): array
{
    $synced_languages = function_exists('mpa_get_synced_languages') ? mpa_get_synced_languages() : [];
    $languages = [];

    if (!empty($synced_languages)) {
        foreach ($synced_languages as $language) {
            if (!is_array($language)) {
                continue;
            }

            $code = sanitize_key((string) ($language['code'] ?? ''));
            if ($code === '') {
                continue;
            }

            $label = sanitize_text_field((string) ($language['label'] ?? ''));
            $languages[$code] = $label !== '' ? $label : mpa_get_language_label($code);
        }

        if (!empty($languages)) {
            return $languages;
        }
    }

    global $wpdb;

    $rows = $wpdb->get_col(
        $wpdb->prepare(
            "SELECT DISTINCT pm.meta_value
            FROM {$wpdb->postmeta} pm
            INNER JOIN {$wpdb->posts} p ON p.ID = pm.post_id
            WHERE pm.meta_key = %s
              AND pm.meta_value <> ''
              AND p.post_type = %s
              AND p.post_status = 'publish'
            ORDER BY pm.meta_value ASC",
            'book_language',
            'mpa_book'
        )
    );

    if (!is_array($rows)) {
        return [];
    }

    foreach ($rows as $value) {
        $code = sanitize_key((string) $value);
        if ($code === '') {
            continue;
        }

        $languages[$code] = mpa_get_language_label($code);
    }

    return $languages;
}

function mpa_get_language_label(string $code): string
{
    $labels = [
        'en' => __('English', 'mpa-books-2026'),
        'es' => __('Spanish', 'mpa-books-2026'),
        'fr' => __('French', 'mpa-books-2026'),
        'de' => __('German', 'mpa-books-2026'),
        'it' => __('Italian', 'mpa-books-2026'),
        'pt' => __('Portuguese', 'mpa-books-2026'),
        'pt-br' => __('Portuguese (Brazil)', 'mpa-books-2026'),
        'nl' => __('Dutch', 'mpa-books-2026'),
        'sv' => __('Swedish', 'mpa-books-2026'),
        'no' => __('Norwegian', 'mpa-books-2026'),
        'da' => __('Danish', 'mpa-books-2026'),
        'fi' => __('Finnish', 'mpa-books-2026'),
        'pl' => __('Polish', 'mpa-books-2026'),
        'cs' => __('Czech', 'mpa-books-2026'),
        'ro' => __('Romanian', 'mpa-books-2026'),
        'hu' => __('Hungarian', 'mpa-books-2026'),
        'tr' => __('Turkish', 'mpa-books-2026'),
        'el' => __('Greek', 'mpa-books-2026'),
        'ru' => __('Russian', 'mpa-books-2026'),
        'uk' => __('Ukrainian', 'mpa-books-2026'),
        'he' => __('Hebrew', 'mpa-books-2026'),
        'ar' => __('Arabic', 'mpa-books-2026'),
        'ja' => __('Japanese', 'mpa-books-2026'),
        'ko' => __('Korean', 'mpa-books-2026'),
        'zh' => __('Chinese (Simplified)', 'mpa-books-2026'),
        'zh-tw' => __('Chinese (Traditional)', 'mpa-books-2026'),
    ];

    return $labels[$code] ?? strtoupper($code);
}

function mpa_get_language_switch_url(string $code): string
{
    $url = remove_query_arg('lang');

    if ($code !== '') {
        $url = add_query_arg('lang', rawurlencode($code), $url);
    }

    return (string) $url;
}

function mpa_render_language_menu(): void
{
    $languages = mpa_get_available_languages();
    $current = mpa_get_current_language_code();

    if (empty($languages)) {
        return;
    }

    echo '<section class="mpa-language-menu mpa-sidebar-section" aria-label="' . esc_attr__('Language menu', 'mpa-books-2026') . '">';
    echo '<h2 class="mpa-language-menu__title">' . esc_html__('Languages', 'mpa-books-2026') . '</h2>';
    echo '<ul class="mpa-language-menu__list">';

    echo '<li><a class="' . ($current === '' ? 'is-active' : '') . '" href="' . esc_url(mpa_get_language_switch_url('')) . '">' . esc_html__('All languages', 'mpa-books-2026') . '</a></li>';

    foreach ($languages as $code => $label) {
        $active_class = $current === $code ? 'is-active' : '';
        echo '<li><a class="' . esc_attr($active_class) . '" href="' . esc_url(mpa_get_language_switch_url($code)) . '">' . esc_html($label) . '</a></li>';
    }

    echo '</ul>';
    echo '</section>';
}




function mpa_render_sidebar_panel(): void
{
    echo '<aside class="mpa-sidebar" aria-label="' . esc_attr__('Site sidebar', 'mpa-books-2026') . '">';

    if (has_nav_menu('sidebar')) {
        echo '<nav class="mpa-sidebar-section mpa-sidebar-nav" aria-label="' . esc_attr__('Sidebar navigation', 'mpa-books-2026') . '">';
        wp_nav_menu([
            'theme_location' => 'sidebar',
            'container' => false,
            'menu_class' => 'mpa-sidebar-nav__list',
            'fallback_cb' => false,
        ]);
        echo '</nav>';
    }

    mpa_render_language_menu();

    echo '</aside>';
}

function mpa_persist_language_preference_cookie(): void
{
    if (is_admin() || wp_doing_ajax() || (defined('REST_REQUEST') && REST_REQUEST)) {
        return;
    }

    $lang = isset($_GET['lang']) ? sanitize_key((string) $_GET['lang']) : '';
    if ($lang === '') {
        return;
    }

    setcookie('mpa_lang', $lang, time() + YEAR_IN_SECONDS, COOKIEPATH ?: '/');
}

function mpa_get_preferred_language_from_browser(): string
{
    $header = isset($_SERVER['HTTP_ACCEPT_LANGUAGE']) ? (string) $_SERVER['HTTP_ACCEPT_LANGUAGE'] : '';
    if ($header === '') {
        return '';
    }

    $parts = explode(',', $header);
    foreach ($parts as $part) {
        $lang = strtolower(trim(explode(';', $part)[0] ?? ''));
        if ($lang === '') {
            continue;
        }

        $primary = sanitize_key(explode('-', $lang)[0] ?? '');
        if ($primary !== '') {
            return $primary;
        }
    }

    return '';
}

function mpa_should_apply_base_language_redirect(): bool
{
    if (is_admin() || wp_doing_ajax() || (defined('REST_REQUEST') && REST_REQUEST)) {
        return false;
    }

    if (isset($_GET['lang'])) {
        return false;
    }

    $request_uri = isset($_SERVER['REQUEST_URI']) ? (string) $_SERVER['REQUEST_URI'] : '';
    $request_path = (string) parse_url($request_uri, PHP_URL_PATH);

    $home_path = (string) parse_url(home_url('/'), PHP_URL_PATH);
    $home_path = $home_path === '' ? '/' : $home_path;

    return rtrim($request_path, '/') === rtrim($home_path, '/');
}

function mpa_redirect_base_url_to_preferred_language(): void
{
    if (!mpa_should_apply_base_language_redirect()) {
        return;
    }

    $cookie_lang = isset($_COOKIE['mpa_lang']) ? sanitize_key((string) $_COOKIE['mpa_lang']) : '';
    $preferred = $cookie_lang !== '' ? $cookie_lang : mpa_get_preferred_language_from_browser();

    if ($preferred === '') {
        return;
    }

    $available_languages = array_keys(mpa_get_available_languages());
    if (!in_array($preferred, $available_languages, true)) {
        return;
    }

    $target = add_query_arg('lang', rawurlencode($preferred), home_url('/'));
    setcookie('mpa_lang', $preferred, time() + YEAR_IN_SECONDS, COOKIEPATH ?: '/');
    wp_safe_redirect($target, 302);
    exit;
}

function mpa_get_book_formats(int $book_id): array
{
    $linked_product_id = (int) get_post_meta($book_id, 'linked_product_id', true);
    if ($linked_product_id <= 0 || !function_exists('wc_get_product')) {
        return [];
    }

    $product = wc_get_product($linked_product_id);
    if (!$product || !$product->is_type('variable')) {
        return [];
    }

    $formats = [];
    foreach ($product->get_children() as $variation_id) {
        $variation = wc_get_product($variation_id);
        if (!$variation) {
            continue;
        }

        $attributes = $variation->get_attributes();
        if (!empty($attributes['pa_format'])) {
            $formats[] = strtoupper((string) $attributes['pa_format']);
        }
    }

    return array_values(array_unique($formats));
}

function mpa_get_buy_url(int $book_id): string
{
    $linked_product_id = (int) get_post_meta($book_id, 'linked_product_id', true);
    if ($linked_product_id <= 0) {
        return '';
    }

    return get_permalink($linked_product_id) ?: '';
}
