# HoloVerse

## Project structure

- `index.php` is the home page entry point.
- `pages/` contains the directly accessible PHP pages.
- `Functions/` retains legacy page URLs and forwards them to `pages/`.
- `app/views/layout/` contains shared page layouts.
- `app/views/components/` contains reusable interface components.
- `config/` contains application configuration, including the database connection.
- `Database/holoverse.sql` creates the Supabase PostgreSQL schema and imports the initial catalog and talent data.
- `Assets/images/` and `Assets/css/` contain browser-delivered assets.
- `resources/css/` contains the Tailwind CSS source.

The **View all products** link in the home page's featured merchandise section opens `pages/products.php`, which lists all active products from the database.

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
4. Serve the project through PHP.

`Database/holoverse.sql` is a one-time baseline for a new, empty schema; do not run it over an existing database. It includes the flash-sale table and the checked-in catalog, talent, sample-cart, and account snapshot data. It is not a live migration or backup of accounts, carts, or orders created after that snapshot.

For an existing database, run `Database/migrations/20261018_add_flash_sale_cart_holds.sql` once in the Supabase SQL Editor to enable five-minute reservations for live flash-sale items. The migration releases any reservations from the earlier general cart-hold behavior; it keeps cart lines and regular items are not reserved.

All catalog and order prices are stored and displayed in Philippine pesos (PHP/₱).

The sign-in page supports account registration and login. Passwords are stored using PHP's `password_hash`; guest carts are stored against a random session token and merged into the signed-in account cart after login.

Checkout is available only from the cart and to signed-in accounts. Products must be added to the cart before checkout; guests are sent to account registration or sign-in, and their cart is preserved. Adding a live flash-sale item reserves its available inventory for five minutes; the cart and checkout show a countdown, and the reservation expires even if the browser remains open. Changing a quantity does not extend the timer. Expired reservations are released and the item must be added again. Regular items are not reserved until checkout. Checkout records a pending order with a shipping address and confirms available stock; payment is not collected by the current checkout flow.

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
