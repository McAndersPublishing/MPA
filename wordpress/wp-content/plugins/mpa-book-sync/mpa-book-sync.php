<?php
/**
 * Plugin Name: MPA Book Sync
 * Description: Syncs books from the MPA app into WordPress and WooCommerce variable products.
 * Version: 0.1.0
 * Author: MPA
 */

if (!defined('ABSPATH')) {
    exit;
}

const MPA_SYNC_OPTION_SECRET = 'mpa_sync_secret';
const MPA_SYNC_OPTION_KEY = 'mpa_sync_key';
const MPA_SYNC_OPTION_LANGUAGES = 'mpa_sync_languages';

add_action('init', 'mpa_register_content_model');
add_action('rest_api_init', 'mpa_register_sync_routes');

function mpa_register_content_model(): void
{
    register_post_type('mpa_book', [
        'label' => 'Books',
        'public' => true,
        'show_in_rest' => true,
        'supports' => ['title', 'editor', 'excerpt', 'thumbnail'],
        'rewrite' => ['slug' => 'books'],
    ]);

    register_taxonomy('mpa_series', 'mpa_book', [
        'label' => 'Series',
        'public' => true,
        'show_in_rest' => true,
        'hierarchical' => true,
        'rewrite' => ['slug' => 'series'],
    ]);

    register_taxonomy('mpa_genre', 'mpa_book', [
        'label' => 'Genres',
        'public' => true,
        'show_in_rest' => true,
        'hierarchical' => true,
        'rewrite' => ['slug' => 'genre'],
    ]);
}

function mpa_register_sync_routes(): void
{
    register_rest_route('mpa-book-sync/v1', '/book', [
        'methods' => 'POST',
        'callback' => 'mpa_handle_book_sync',
        'permission_callback' => '__return_true',
    ]);
}

function mpa_handle_book_sync(WP_REST_Request $request): WP_REST_Response
{
    $raw_body = $request->get_body();
    $timestamp = (string) $request->get_header('x-mpa-timestamp');
    $provided_key = (string) $request->get_header('x-mpa-key');
    $provided_signature = (string) $request->get_header('x-mpa-signature');

    $auth_error = mpa_validate_request_signature($timestamp, $provided_key, $provided_signature, $raw_body);
    if ($auth_error instanceof WP_Error) {
        return new WP_REST_Response([
            'ok' => false,
            'error' => $auth_error->get_error_code(),
        ], 401);
    }

    $payload = json_decode($raw_body, true);
    if (!is_array($payload) || empty($payload['book'])) {
        return new WP_REST_Response([
            'ok' => false,
            'error' => 'invalid_payload',
        ], 400);
    }

    mpa_sync_language_options($payload);

    $result = mpa_upsert_book_and_product($payload['book']);
    if ($result instanceof WP_Error) {
        return new WP_REST_Response([
            'ok' => false,
            'error' => $result->get_error_code(),
        ], 400);
    }

    return new WP_REST_Response(array_merge(['ok' => true], $result), 200);
}

function mpa_validate_request_signature(string $timestamp, string $provided_key, string $provided_signature, string $raw_body)
{
    $expected_key = (string) get_option(MPA_SYNC_OPTION_KEY, '');
    $secret = (string) get_option(MPA_SYNC_OPTION_SECRET, '');

    if ($expected_key === '' || $secret === '') {
        return new WP_Error('sync_not_configured', 'MPA sync credentials are not configured.');
    }

    if (!hash_equals($expected_key, $provided_key)) {
        return new WP_Error('invalid_key', 'Invalid sync key.');
    }

    if (!ctype_digit($timestamp)) {
        return new WP_Error('invalid_timestamp', 'Invalid timestamp format.');
    }

    $age = abs(time() - (int) $timestamp);
    if ($age > 300) {
        return new WP_Error('stale_timestamp', 'Request timestamp is outside allowed window.');
    }

    $signed_payload = $timestamp . '.' . $raw_body;
    $expected_signature = hash_hmac('sha256', $signed_payload, $secret);

    if (!hash_equals($expected_signature, $provided_signature)) {
        return new WP_Error('invalid_signature', 'Invalid signature.');
    }

    return true;
}

function mpa_upsert_book_and_product(array $book)
{
    if (empty($book['external_id']) || empty($book['title'])) {
        return new WP_Error('missing_required_fields', 'Book external_id and title are required.');
    }

    $post_id = mpa_upsert_book_post($book);
    if (is_wp_error($post_id)) {
        return $post_id;
    }

    $product_result = mpa_upsert_woocommerce_product($book, $post_id);
    if (is_wp_error($product_result)) {
        return $product_result;
    }

    return [
        'book_post_id' => $post_id,
        'product_id' => $product_result['product_id'],
        'variation_ids' => $product_result['variation_ids'],
    ];
}

function mpa_upsert_book_post(array $book)
{
    $existing = get_posts([
        'post_type' => 'mpa_book',
        'post_status' => 'any',
        'meta_key' => 'book_external_id',
        'meta_value' => sanitize_text_field((string) $book['external_id']),
        'numberposts' => 1,
        'fields' => 'ids',
    ]);

    $post_data = [
        'post_type' => 'mpa_book',
        'post_title' => sanitize_text_field((string) $book['title']),
        'post_content' => wp_kses_post((string) ($book['description'] ?? '')),
        'post_excerpt' => sanitize_textarea_field((string) ($book['excerpt'] ?? '')),
        'post_status' => mpa_map_post_status((string) ($book['status'] ?? 'publish')),
    ];

    if (!empty($book['slug'])) {
        $post_data['post_name'] = sanitize_title((string) $book['slug']);
    }

    if (!empty($existing)) {
        $post_data['ID'] = (int) $existing[0];
        $post_id = wp_update_post($post_data, true);
    } else {
        $post_id = wp_insert_post($post_data, true);
    }

    if (is_wp_error($post_id)) {
        return $post_id;
    }

    update_post_meta($post_id, 'book_external_id', sanitize_text_field((string) $book['external_id']));
    update_post_meta($post_id, 'book_language', sanitize_text_field((string) ($book['language'] ?? '')));
    update_post_meta($post_id, 'book_locale', sanitize_text_field((string) ($book['locale'] ?? '')));
    update_post_meta($post_id, 'book_text_direction', sanitize_key((string) ($book['text_direction'] ?? 'ltr')));
    update_post_meta($post_id, 'book_cover_image_url', esc_url_raw((string) ($book['cover_image_url'] ?? '')));

    if (!empty($book['series']['name'])) {
        $series_name = sanitize_text_field((string) $book['series']['name']);
        $series_slug = sanitize_title((string) ($book['series']['slug'] ?? $series_name));

        $term = term_exists($series_slug, 'mpa_series');
        if (!$term) {
            $term = wp_insert_term($series_name, 'mpa_series', ['slug' => $series_slug]);
        }

        if (!is_wp_error($term)) {
            $term_id = is_array($term) ? (int) $term['term_id'] : (int) $term;
            wp_set_object_terms($post_id, [$term_id], 'mpa_series', false);
        }
    }

    $genre_term_ids = [];
    $genres = is_array($book['genres'] ?? null) ? $book['genres'] : [];

    foreach ($genres as $genre) {
        if (!is_array($genre) || empty($genre['name'])) {
            continue;
        }

        $genre_name = sanitize_text_field((string) $genre['name']);
        $genre_slug = sanitize_title((string) ($genre['slug'] ?? $genre_name));
        $genre_term = term_exists($genre_slug, 'mpa_genre');

        if (!$genre_term) {
            $genre_term = wp_insert_term($genre_name, 'mpa_genre', ['slug' => $genre_slug]);
        }

        if (!is_wp_error($genre_term)) {
            $genre_term_ids[] = is_array($genre_term) ? (int) $genre_term['term_id'] : (int) $genre_term;
        }
    }

    if (!empty($genre_term_ids)) {
        wp_set_object_terms($post_id, $genre_term_ids, 'mpa_genre', false);
    }

    return $post_id;
}

function mpa_upsert_woocommerce_product(array $book, int $book_post_id)
{
    if (!class_exists('WC_Product_Variable')) {
        return new WP_Error('woocommerce_not_active', 'WooCommerce must be active to sync products.');
    }

    $existing = get_posts([
        'post_type' => 'product',
        'post_status' => 'any',
        'meta_key' => 'book_external_id',
        'meta_value' => sanitize_text_field((string) $book['external_id']),
        'numberposts' => 1,
        'fields' => 'ids',
    ]);

    $product_id = !empty($existing) ? (int) $existing[0] : 0;
    $product = $product_id > 0 ? wc_get_product($product_id) : new WC_Product_Variable();

    $product->set_name(sanitize_text_field((string) $book['title']));
    $product->set_description(wp_kses_post((string) ($book['description'] ?? '')));
    $product->set_short_description(sanitize_textarea_field((string) ($book['excerpt'] ?? '')));
    $product->set_status(mpa_map_post_status((string) ($book['status'] ?? 'publish')));
    $product->set_catalog_visibility('visible');

    $product_id = $product->save();

    update_post_meta($product_id, 'book_external_id', sanitize_text_field((string) $book['external_id']));
    update_post_meta($product_id, 'linked_book_post_id', $book_post_id);
    update_post_meta($book_post_id, 'linked_product_id', $product_id);

    $variation_ids = mpa_sync_product_variations($product_id, $book);
    if (is_wp_error($variation_ids)) {
        return $variation_ids;
    }

    return [
        'product_id' => $product_id,
        'variation_ids' => $variation_ids,
    ];
}

function mpa_sync_product_variations(int $product_id, array $book)
{
    $formats = is_array($book['formats'] ?? null) ? $book['formats'] : [];
    $price = (string) ($book['price'] ?? '0');

    $attribute_name = 'pa_format';
    if (!taxonomy_exists($attribute_name)) {
        register_taxonomy($attribute_name, 'product', [
            'hierarchical' => false,
            'label' => 'Format',
            'query_var' => true,
            'rewrite' => false,
        ]);
    }

    $variation_ids = [];

    foreach ($formats as $format) {
        $enabled = !empty($format['enabled']);
        if (!$enabled || empty($format['code']) || empty($format['label'])) {
            continue;
        }

        $term = term_exists(sanitize_title((string) $format['code']), $attribute_name);
        if (!$term) {
            $term = wp_insert_term(sanitize_text_field((string) $format['label']), $attribute_name, [
                'slug' => sanitize_title((string) $format['code']),
            ]);
        }

        if (is_wp_error($term)) {
            continue;
        }

        $term_id = is_array($term) ? (int) $term['term_id'] : (int) $term;
        wp_set_object_terms($product_id, [$term_id], $attribute_name, true);

        $variation_id = mpa_upsert_single_variation($product_id, $attribute_name, (string) $format['code'], $price, (string) ($format['download_url'] ?? ''));
        if ($variation_id > 0) {
            $variation_ids[] = $variation_id;
        }
    }

    return $variation_ids;
}

function mpa_upsert_single_variation(int $product_id, string $attribute_name, string $format_code, string $price, string $download_url): int
{
    $query = new WP_Query([
        'post_type' => 'product_variation',
        'post_parent' => $product_id,
        'meta_key' => 'attribute_' . $attribute_name,
        'meta_value' => sanitize_title($format_code),
        'fields' => 'ids',
        'posts_per_page' => 1,
        'post_status' => 'any',
    ]);

    $variation_id = !empty($query->posts) ? (int) $query->posts[0] : 0;
    $variation = $variation_id > 0 ? new WC_Product_Variation($variation_id) : new WC_Product_Variation();

    if ($variation_id === 0) {
        $variation->set_parent_id($product_id);
    }

    $variation->set_attributes([$attribute_name => sanitize_title($format_code)]);
    $variation->set_regular_price(wc_format_decimal($price));
    $variation->set_downloadable(true);
    $variation->set_virtual(true);
    $variation->set_status('publish');

    if ($download_url !== '') {
        $download = new WC_Product_Download();
        $download->set_id(md5($product_id . ':' . $format_code));
        $download->set_name(strtoupper($format_code));
        $download->set_file(esc_url_raw($download_url));
        $variation->set_downloads([$download]);
    }

    return $variation->save();
}

function mpa_sync_language_options(array $payload): void
{
    $languages = is_array($payload['languages'] ?? null) ? $payload['languages'] : [];
    if (empty($languages)) {
        return;
    }

    $normalized = [];

    foreach ($languages as $language) {
        if (!is_array($language)) {
            continue;
        }

        $code = sanitize_key((string) ($language['code'] ?? ''));
        if ($code === '') {
            continue;
        }

        $label = sanitize_text_field((string) ($language['label'] ?? $language['name'] ?? strtoupper($code)));
        $normalized[$code] = [
            'code' => $code,
            'label' => $label,
            'locale' => sanitize_text_field((string) ($language['locale'] ?? '')),
            'text_direction' => sanitize_key((string) ($language['text_direction'] ?? '')),
        ];
    }

    if (!empty($normalized)) {
        update_option(MPA_SYNC_OPTION_LANGUAGES, array_values($normalized), false);
    }
}

function mpa_get_synced_languages(): array
{
    $languages = get_option(MPA_SYNC_OPTION_LANGUAGES, []);

    return is_array($languages) ? $languages : [];
}

function mpa_map_post_status(string $status): string
{
    $allowed = ['publish', 'draft', 'pending', 'private'];

    if (in_array($status, $allowed, true)) {
        return $status;
    }

    return 'publish';
}
