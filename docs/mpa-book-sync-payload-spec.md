# MPA Book Sync Payload Spec (v1)

This document defines the payload sent from the book-generation app to WordPress.

## Endpoint

- Method: `POST`
- URL: `/wp-json/mpa-book-sync/v1/book`
- Auth: `X-MPA-Key`, `X-MPA-Timestamp`, `X-MPA-Signature`

## Signature

`X-MPA-Signature` is HMAC-SHA256 over:

`<timestamp>.<raw_request_body>`

using shared secret configured in WordPress option `mpa_sync_secret`.

## Payload

```json
{
  "languages": [
    {"code": "en", "label": "English", "locale": "en_US", "text_direction": "ltr"},
    {"code": "es", "label": "Español", "locale": "es_ES", "text_direction": "ltr"}
  ],
  "book": {
    "external_id": "BK-1001",
    "slug": "example-book",
    "title": "Example Book",
    "description": "Long description shown on series and book pages.",
    "excerpt": "Preview excerpt shown in customer-facing modal.",
    "cover_image_url": "https://cdn.example.com/covers/example-book.jpg",
    "language": "en",
    "locale": "en_US",
    "text_direction": "ltr",
    "series": {
      "external_id": "SER-10",
      "name": "Series Name",
      "slug": "series-name"
    },
    "genres": [
      {"name": "Fantasy", "slug": "fantasy"},
      {"name": "Adventure", "slug": "adventure"}
    ],
    "status": "publish",
    "price": "4.99",
    "is_free": false,
    "is_preorder": false,
    "formats": [
      {
        "code": "epub",
        "label": "EPUB",
        "enabled": true,
        "download_url": "https://files.example.com/books/example-book.epub"
      },
      {
        "code": "pdf",
        "label": "PDF",
        "enabled": true,
        "download_url": "https://files.example.com/books/example-book.pdf"
      }
    ]
  }
}
```

## Field rules

- `book.external_id` is immutable and is the upsert key.
- `languages` (top-level) should contain the canonical language options from the main app; these are saved in WordPress and used to render the storefront language menu.
- `book.slug` is optional; if omitted WordPress generates from title.
- `book.language` should be a language code used by the side language menu and filter (e.g., `en`, `es`, `fr`).
- `book.locale` should be a WordPress locale string when available (e.g., `en_US`, `es_ES`).
- `book.text_direction` should be `ltr` or `rtl`.
- `book.genres` is optional but recommended for storefront genre landing pages.
- `book.price` is applied to every enabled format variation.
- `book.formats[].enabled=false` removes/deactivates that variation.
- `book.excerpt` is stored for preview and should never include full story text.

## Response

```json
{
  "ok": true,
  "book_post_id": 123,
  "product_id": 456,
  "variation_ids": [789, 790]
}
```

## Error response

```json
{
  "ok": false,
  "error": "invalid_signature"
}
```
