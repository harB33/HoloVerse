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

Build the stylesheet with `npm run build:css`; use `npm run watch:css` while developing styles.

## Database setup

1. Create a MySQL or MariaDB database named `holoverse`.
2. Import `Database/holoverse.sql` into that database to create the tables.
3. Import `Database/holoverse_seed.sql` once to add starter merchandise and talent records.
4. Import `Database/add_full_talent_directory.sql` to add all 69 talent, group, and membership records. This migration is safe to run on a database that already has the five starter talents.
5. Import `Database/add_featured_talent_merch.sql` to add sample shop merchandise for Tsunomaki Watame, Momosuzu Nene, and Kazama Iroha. The migration is safe to rerun.
6. Import `Database/convert_catalog_currency_to_php.sql` to convert existing USD and JPY catalog and order amounts to Philippine pesos. The migration uses 2026-10-16 reference rates and is safe to rerun.
7. Configure the connection with `HOLOVERSE_DB_HOST`, `HOLOVERSE_DB_PORT`, `HOLOVERSE_DB_USER`, `HOLOVERSE_DB_PASSWORD`, and `HOLOVERSE_DB_NAME`. The defaults are `localhost:3306`, user `root`, an empty password, and database `holoverse`.
8. Serve the project through PHP with the `mysqli` extension enabled.

If you already imported the starter catalog, import only `Database/add_full_talent_directory.sql` to populate the full directory. It can be rerun safely and does not change existing products or talent IDs; rerunning it also refreshes the portraits for Tsunomaki Watame, Momosuzu Nene, and Kazama Iroha.

Import `Database/add_featured_talent_merch.sql` after the full talent directory to add these three talents’ shop-inspired product listings to an existing database.

All catalog and order prices are stored and displayed in Philippine pesos (PHP/₱).

The sign-in page supports account registration and login. Passwords are stored using PHP's `password_hash`; guest carts are stored against a random session token and merged into the signed-in account cart after login.
