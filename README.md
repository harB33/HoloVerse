# HoloVerse

## Project structure

- `index.php` is the home page entry point.
- `pages/` contains the directly accessible PHP pages.
- `Functions/` retains legacy page URLs and forwards them to `pages/`.
- `app/views/layout/` contains shared page layouts.
- `app/views/components/` contains reusable interface components.
- `config/` contains application configuration, including the database connection.
- `Database/holoverse.sql` creates the schema.
- `Database/holoverse_seed.sql` adds starter catalog data and prepares password storage.
- `Assets/images/` and `Assets/css/` contain browser-delivered assets.
- `resources/css/` contains the Tailwind CSS source.

The **View all products** link in the home page's featured merchandise section opens `pages/products.php`, which lists all active products from the database.

Build the stylesheet with `npm run build:css`; use `npm run watch:css` while developing styles.

## Database setup

1. Create a MySQL or MariaDB database named `holoverse`.
2. Import `Database/holoverse.sql` into that database to create the tables.
3. Import `Database/holoverse_seed.sql` once to add starter merchandise and talent records.
4. Import `Database/add_full_talent_directory.sql` to add all 69 talent, group, and membership records. This migration is safe to run on a database that already has the five starter talents.
5. Import `Database/add_featured_talent_merch.sql` to add sample shop merchandise for Tsunomaki Watame, Momosuzu Nene, and Kazama Iroha. The migration is safe to rerun.
6. Import `Database/add_official_shop_merch.sql` to add current official-shop-inspired merch listings for Hoshimachi Suisei, Kureiji Ollie, Otonose Kanade, Hakui Koyori, and ReGLOSS. It is safe to rerun and stores prices in PHP.
7. Import `Database/convert_catalog_currency_to_php.sql` to convert existing USD and JPY catalog and order amounts to Philippine pesos. The migration uses 2026-10-16 reference rates and is safe to rerun.
8. Configure the connection with `HOLOVERSE_DB_HOST`, `HOLOVERSE_DB_PORT`, `HOLOVERSE_DB_USER`, `HOLOVERSE_DB_PASSWORD`, and `HOLOVERSE_DB_NAME`. The defaults are `localhost:3306`, user `root`, an empty password, and database `holoverse`.
9. Serve the project through PHP with the `mysqli` extension enabled.

If you already imported the starter catalog, import only `Database/add_full_talent_directory.sql` to populate the full directory. It can be rerun safely and does not change existing products or talent IDs; rerunning it also refreshes the portraits for Tsunomaki Watame, Momosuzu Nene, and Kazama Iroha.

Import `Database/add_featured_talent_merch.sql` after the full talent directory to add these three talents’ shop-inspired product listings to an existing database.

Import `Database/add_official_shop_merch.sql` after the full talent directory to add more current merchandise listings based on the [official hololive production shop](https://shop.hololivepro.com/en). The product records, variants, and talent associations are stored in the catalog database; product images are referenced from the official shop's CDN.

All catalog and order prices are stored and displayed in Philippine pesos (PHP/₱).

The sign-in page supports account registration and login. Passwords are stored using PHP's `password_hash`; guest carts are stored against a random session token and merged into the signed-in account cart after login.

## Product entry

Sign in with an administrator account and choose **Add a product** under **Administrator tools** in the account menu to create a catalog listing. The form saves the product and its PHP-priced variant in one database transaction, with optional image and talent links. New products can be saved as drafts or published immediately. The user's role is read from the database on each request, so role changes take effect without signing in again; the product entry page also checks the role server-side.

To grant administrator access, first register an account, then update its role in MySQL:

```sql
UPDATE user_tbl SET user_role = 'admin' WHERE email = 'admin@example.com';
```

Standard customer accounts cannot access the product entry page.
