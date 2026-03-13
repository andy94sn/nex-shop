# NexDistribution Shop — Backend API

Laravel 12 + Lighthouse GraphQL API for the NexDistribution e-commerce platform.

## Stack

| Layer | Technology |
|---|---|
| Runtime | PHP 8.4 / Laravel 12 |
| API | Lighthouse 6 (GraphQL) |
| Database | MySQL 8.0 |
| Cache / Queue | Redis 7.2 (predis) |
| Search | TNTSearch via Laravel Scout |
| Locales | Romanian (`ro`) · Russian (`ru`) |
| Media | Spatie Media Library |
| Permissions | Spatie Laravel Permission |
| Containers | Docker (PHP-FPM, Nginx, MySQL, Redis, Queue, Scheduler, Mailpit) |

---

## Quick Start

### Prerequisites
- Docker Desktop
- GNU Make

### First-time setup

```bash
make setup
```

This will:
1. Copy `.env.example` → `.env`
2. Build and start all Docker containers
3. Install Composer dependencies
4. Generate application key
5. Run all migrations
6. Create `storage` symlink

### Day-to-day commands

```bash
make up            # Start containers
make down          # Stop containers
make logs          # Tail all container logs
make shell         # Open bash in app container
make migrate       # Run pending migrations
make migrate-fresh # Drop all tables and re-migrate (with seed)
make tinker        # Laravel Tinker REPL
make clear         # Clear all caches
make optimize      # Re-cache config/routes/views
```

### Running artisan commands

```bash
make artisan migrate:status
make artisan queue:work
make artisan scout:import "Modules\Catalog\Models\Product"
```

---

## Architecture

The project uses a **modular monolith** pattern. All domain logic lives under `Modules/`:

```
Modules/
├── Core/           # Middleware, base classes
├── Settings/       # Site-wide settings (singleton)
├── Catalog/        # Products, Categories, Brands, Attributes
├── Content/        # Pages, Banners, FAQ, Support Requests
├── Commerce/       # Cart, Orders, Credit, Coupons, Shipping
├── Interactions/   # Wishlist, Compare, Search
└── Marketing/      # Promotions, Newsletter
```

Each module has:
```
ModuleName/
├── Database/Migrations/
├── GraphQL/
│   ├── Queries/
│   └── Mutations/
├── Http/
├── Mail/
├── Models/
├── Observers/
├── Providers/ModuleServiceProvider.php
├── resources/views/
└── Services/
```

---

## GraphQL API

The GraphQL playground is available at [`http://localhost/graphql-playground`](http://localhost/graphql-playground).

The schema is defined in `graphql/schema.graphql` and imports module schemas from `graphql/modules/`.

### Key queries

| Query | Description |
|---|---|
| `siteSettings` | Global site configuration |
| `categoryMenu` | Hierarchical category tree (cached) |
| `categoryPage(slug, filters, sort, page)` | Category listing with full filters |
| `product(slug)` | Product detail with attributes & credit options |
| `cart` | Current session cart |
| `checkoutTotals` | Live total calculation |
| `search(query, filters, sort, page)` | Full-text search with filters |
| `wishlist` | Session wishlist products |
| `compareProducts(category_id)` | Side-by-side product comparison |
| `promotion(slug)` | Promotion page with products |

### Key mutations

| Mutation | Description |
|---|---|
| `addToCart(article, quantity)` | Add product to cart |
| `removeFromCart(article)` | Remove product from cart |
| `updateCartQuantity(article, quantity)` | Update cart item quantity |
| `clearCart` | Empty the cart |
| `applyCoupon(code)` | Apply discount coupon |
| `removeCoupon` | Remove applied coupon |
| `setShippingRegion(shipping_region_id)` | Set delivery region |
| `setPaymentMethod(method)` | Set payment method |
| `placeOrder(input)` | Submit order (validates stock, creates order, sends email) |
| `addToWishlist(article)` | Add to wishlist |
| `removeFromWishlist(article)` | Remove from wishlist |
| `addToCompare(article)` | Add to compare list |
| `subscribe(email, name, phone)` | Newsletter subscribe |
| `submitSupportRequest(input)` | Contact form submission |

---

## Environment Variables

Copy `.env.example` to `.env` and adjust as needed.

| Variable | Default | Description |
|---|---|---|
| `APP_SUPPORTED_LOCALES` | `ro,ru` | Comma-separated locale list |
| `CACHE_TTL_MENU` | `3600` | Category menu cache TTL (seconds) |
| `CACHE_TTL_SETTINGS` | `86400` | Site settings cache TTL |
| `CACHE_TTL_WISHLIST` | `2592000` | Wishlist Redis TTL (30 days) |
| `CACHE_TTL_CART` | `2592000` | Cart Redis TTL (30 days) |
| `COMPARE_MAX_PER_CATEGORY` | `3` | Max products per category in compare |
| `MAIL_ADMIN_ADDRESS` | — | Admin email for support request notifications |

---

## Services

| Service | URL |
|---|---|
| App (Nginx) | http://localhost |
| GraphQL Playground | http://localhost/graphql-playground |
| Mailpit | http://localhost:8025 |
| phpMyAdmin | http://localhost:8080 |

---

## License

Proprietary — Minicode SRL. All rights reserved.
