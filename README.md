# HoloVerse

## Project structure

- `index.php` is the home page entry point.
- `pages/` contains the directly accessible PHP pages.
- `Functions/` retains legacy page URLs and forwards them to `pages/`.
- `app/store.php` initializes the session and database connection, then loads the shared application modules in `app/store/`.
- `app/store/` separates shared helpers, authentication, cart, catalog, flash-sale, and inventory logic.
- `app/views/layout/` contains shared page layouts.
- `app/views/layout/head.php` applies the branded loading screen only on the home page and uses the same page transition across layouts.
- `app/views/components/` contains reusable interface components.
- `config/` contains application configuration, including the database connection.
- `Database/holoverse.sql` creates the Supabase PostgreSQL schema and imports the initial catalog and talent data.
- `Assets/images/` and `Assets/css/` contain browser-delivered assets.
- `resources/css/` contains the Tailwind CSS source.

The **View all products** link on `/home` opens `/products`, which lists all active products from the database.

Build the stylesheet with `npm run build:css`; use `npm run watch:css` while developing styles.

## Database setup

1. Create a Supabase project with an empty `public` schema.
2. Open the Supabase SQL Editor and run `Database/holoverse.sql` once. It creates the PostgreSQL tables, indexes, foreign keys, timestamp triggers, identity sequences, and enables row-level security without public policies, then imports the initial catalog and talent data.
3. Configure the PHP runtime with either the Supabase PostgreSQL connection URL:

   - `SUPABASE_DB_URL`, for example `postgresql://postgres:<database-password>@db.<project-ref>.supabase.co:5432/postgres?sslmode=require`

   Or configure the connection details separately:

   - `SUPABASE_DB_DSN`, for example `pgsql:host=db.<project-ref>.supabase.co;port=5432;dbname=postgres;sslmode=require`
   - `SUPABASE_DB_USER`
   - `SUPABASE_DB_PASSWORD`

   Keep the database password private and out of browser-delivered code. The PHP runtime must have the `pdo_pgsql` extension enabled. Because row-level security has no public policies, the server-side connection must use the Supabase database owner (or another appropriately privileged backend role); never use these credentials in client-side code.
   PHP reuses persistent PostgreSQL connections across requests within each server process, avoiding a new remote database connection on every page navigation. The first request after starting the server still has to establish the connection; if it is slow or fails, check the Supabase project status and network access.
4. Serve the project through Apache with `mod_rewrite` enabled, or use PHP's development server with `php -S localhost:8000 router.php`. If the PHP server is already running, stop and restart it with this router command; plain `php -S localhost:8000` does not read `.htaccess` and cannot resolve clean URLs. All pages under `pages/` are available at their page-name URLs (for example `/login`, `/flash-sale-admin`, and `/flash_sale`), and old `.php` page URLs redirect to the clean equivalents while preserving query parameters.

`Database/holoverse.sql` is a one-time baseline for a new, empty schema; do not run it over an existing database. It includes the flash-sale table and the checked-in catalog, talent, sample-cart, and account snapshot data. It is not a live migration or backup of accounts, carts, or orders created after that snapshot.

For an existing database, run `Database/add-login-rate-limits.sql` before deploying the login changes. Sign-in is limited to five failed attempts per client IP, followed by a one-minute lockout with a live countdown; expired counters are periodically cleaned up, and successful sign-ins reset the failed-attempt count. This application-level limit helps slow repeated login attempts, but it is not a substitute for CDN/WAF or hosting-provider protection against distributed or volumetric DDoS traffic. If the app is behind a reverse proxy, configure the web server to provide the verified client address as `REMOTE_ADDR`; the app does not trust client-supplied forwarding headers.

For an existing database, run `Database/add-collector-favorites.sql` before deploying the Collector Showcase changes. Favorites are saved to the signed-in account; the showcase also lists the customer's confirmed purchases with a thank-you note.

For an existing database, run `Database/migrations/20261024_inventory_movement_history.sql` before deploying inventory management changes. It creates the inventory movement audit table. Existing stock levels are not backfilled because their prior adjustment history is unknown; new product stock, admin adjustments, and stock deducted for cash-on-delivery orders are recorded from deployment onward.

To reduce the active catalog while preserving product coverage for talents currently in the talent directory, run `Database/migrations/20261024_curate_active_catalog.sql`. It links products whose names match a talent's slug and associates group-named products with their listed group members. It retains up to 10 of the smallest-variant products per talent; when a talent has fewer than 10 eligible products, it retains all matched products available in the database. It activates required drafts and adds other products up to (but not above) 2,000 active variants. Excess active products are changed to drafts, not deleted. The transaction aborts without applying the reduction if the required products alone exceed 2,000 variants. This migration does not fetch new products from the official shop; catalog entries must already be present in the database. Previous product statuses and newly added talent links are recorded in backup tables for recovery. Run this migration against the existing database in Supabase SQL Editor; it is not part of the fresh-schema setup.

All catalog and order prices are stored and displayed in Philippine pesos (PHP/₱).

The sign-in page supports account registration, login, and password resets using a single-use email code that expires after 10 minutes. Passwords and reset codes are stored using PHP's `password_hash`; guest carts are stored against a random session token and merged into the signed-in account cart after login. Run `composer install` to install PHPMailer and `vlucas/phpdotenv`. Configure SMTP settings in the root `.env.local` or `.env` file; `.env.local` takes precedence, and both files are ignored by Git and blocked from direct Apache access. Use dotenv `KEY=value` syntax in these files, not shell commands such as `$env:KEY=value`. `HOLOVERSE_SMTP_HOST`, `HOLOVERSE_SMTP_USERNAME`, `HOLOVERSE_SMTP_PASSWORD`, and `HOLOVERSE_MAIL_FROM` are required. `HOLOVERSE_SMTP_PORT` defaults to `587`, `HOLOVERSE_SMTP_ENCRYPTION` defaults to `tls` (use `ssl` for implicit TLS, typically on port `465`), and `HOLOVERSE_MAIL_FROM_NAME` defaults to `HoloVerse`. `HOLOVERSE_SITE_URL` sets the link used in email headers; if omitted, it defaults to `http://localhost:8000/home`. For Gmail, use an App Password rather than your regular account password. Keep SMTP credentials out of source control. Password reset codes and order receipts are sent with PHPMailer; email delivery failures are written to the PHP error log and do not undo a successfully completed order. The reset request response does not reveal whether the email has an account.

Checkout is available only from the cart and to signed-in accounts. Products must be added to the cart before checkout; guests are sent to account registration or sign-in, and their cart is preserved. A 12% sales tax is calculated on each product line and shown in the cart and checkout totals. Adding a live flash-sale item reserves its available inventory for five minutes; the cart and checkout show a countdown, and the reservation expires even if the browser remains open. Changing a quantity does not extend the timer. Expired reservations are released and the item must be added again. Regular items are not reserved until checkout. Checkout records the selected payment method and shipping address. Cash-on-delivery orders decrement stock at order placement; card and e-wallet selections remain pending payment and do not decrement stock because online payment verification is not integrated.

The administrator Inventory page supports adding stock, removing stock, and correcting stock to a counted quantity. Each manual change requires a reason and is written to the inventory movement history in the same transaction as the stock update. New product stock and cash-on-delivery stock deductions are also recorded, with order references for sales.

## Administrator tools

Sign in with an administrator account and open **Admin dashboard** under **Administrator tools** in the account menu. The dashboard provides store overview metrics, recent product and order activity, and controls to grant or revoke administrator access for other accounts. The available privilege levels are **Customer** and **Administrator**. Administrators cannot change their own privilege, and the last administrator cannot be demoted.

To create a catalog listing, use **Add Product** from the dashboard. The form saves the product and its PHP-priced variant in one database transaction, with optional image and talent links. New products can be saved as drafts or published immediately. The user's role is read from the database on each request, so role changes take effect without signing in again; administrator pages also check the role server-side.

Use **Manage Flash Sales** from the admin dashboard to schedule an active, discounted, in-stock variant. Sale windows are interpreted in `HOLOVERSE_TIMEZONE` (defaults to `Asia/Manila`). Scheduled windows drive the flash-sale page's live, next-hour, tomorrow, and archive tabs. The catalog's configured price and compare-at price remain the source of product pricing; scheduling controls when a product is listed as a flash sale, not a price change.

To grant administrator access, first register an account, then update its role in the Supabase SQL Editor:

```sql
UPDATE public.user_tbl SET user_role = 'admin', updated_at = CURRENT_TIMESTAMP
WHERE LOWER(email) = LOWER('admin@example.com');
```

Standard customer accounts cannot access the product entry page.
