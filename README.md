# Antiques Marketplace WordPress Theme

This is a starter WordPress marketplace theme inspired by eBay-like listing pages.

## What is included

- Marketplace-style sticky header with search + category select
- Homepage hero section and filter sidebar
- Responsive listing card grid
- Sorting and price filtering (`min_price`, `max_price`, `sort`)
- Listing cards powered by regular WordPress posts

## Use it in WordPress

1. Place this folder in your WordPress themes directory:
   - `wp-content/themes/antiquesdata-theme`
2. In WP Admin, activate **Antiques Marketplace**
3. Create posts for products/listings
4. Set featured image for each post
5. Add custom field `_listing_price` for each post (numeric)
6. Set a static homepage to use `front-page.php` in **Settings > Reading**

## Next upgrades for real eBay behavior

- Add WooCommerce for cart/checkout
- Create custom post type `listing`
- Add bid/auction plugin integration
- Add user dashboard for sellers
- Add condition, shipping, and location metadata
