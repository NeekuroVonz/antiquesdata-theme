---
name: antiques-catalog
description: Query and display the Catawiki antiques catalog in this WordPress theme. Use when changing search, currency conversion, 10-category taxonomy, bid history, sold-to country, or the market-trends dashboard.
---

# Antiques catalog

## Facts

- Remote DB: `catawiki_products.products` (EUR hammer = `current_bid_amount`).
- One row per Catawiki lot (~29k). PRIMARY KEY `id` (autoincrement), UNIQUE `product_id`. Listing SELECT still uses `antiques_marketplace_latest_lot_where_sql()`; COUNT/dashboard use `antiques_marketplace_products_from_latest_lot()`.
- English UI shows USD; Japanese UI shows JPY. Rates: Frankfurter ECB, cached 6 hours.
- Winning buyer country is `bidding_history[0].country.code` (JSON already captured). Seller country is `seller_country`.
- Ten categories live in `antiques_marketplace_catalog_taxonomy()`.

## Dashboard equations (`/market-trends`)

1. Median sold price = P50(`current_bid_amount`) in the selected window (1 / 6 / 12 months)
2. Price trend = P50(hammer) by day (1 month) or month (6 / 12 months)
3. Lots sold over time = `COUNT(*)` in the same buckets
4. Who is buying what = winning bidder `bidding_history[0].country.code` × `antiques_marketplace_classify_listing()` (ten taxonomy slugs)

Do not show GMV / sell-through / estimate ratio on this page. Cache the payload 30 minutes. Do not run these aggregations on the homepage.

## Do not

- Pull thousands of rows to dedupe in PHP
- Search `product_description` with `LIKE %term%`
- Show "View source listing" (`antiques_marketplace_show_source_listing()` is false)
- Hardcode `$` as if prices were USD
