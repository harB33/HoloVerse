# HoloVerse

## Project structure

- `index.php` is the home page entry point.
- `pages/` contains the directly accessible PHP pages.
- `Functions/` retains legacy page URLs and forwards them to `pages/`.
- `app/views/layout/` contains shared page layouts.
- `app/views/components/` contains reusable interface components.
- `config/` contains application configuration, including the database connection.
- `Database/holoverse.sql` creates the schema and loads the starter catalog, talent directory, and sample merchandise.
- `Assets/images/` and `Assets/css/` contain browser-delivered assets.
- `resources/css/` contains the Tailwind CSS source.

The **View all products** link in the home page's featured merchandise section opens `pages/products.php`, which lists all active products from the database.

Build the stylesheet with `npm run build:css`; use `npm run watch:css` while developing styles.

## Database setup

1. Create an empty MySQL or MariaDB database named `holoverse`.
2. Import `Database/holoverse.sql` once. It creates the tables and loads the initial catalog, full talent directory, and sample merchandise.
3. Configure the connection with `HOLOVERSE_DB_HOST`, `HOLOVERSE_DB_PORT`, `HOLOVERSE_DB_USER`, `HOLOVERSE_DB_PASSWORD`, and `HOLOVERSE_DB_NAME`. The defaults are `localhost:3306`, user `root`, an empty password, and database `holoverse`.
4. Serve the project through PHP with the `mysqli` extension enabled.

The consolidated SQL file is for setting up a fresh, empty database. Do not import it over an existing database; it is not a backup or an update script. To back up existing accounts, orders, and other live data, export the database with both its structure and data using phpMyAdmin or `mysqldump`.

All catalog and order prices are stored and displayed in Philippine pesos (PHP/₱).

The sign-in page supports account registration and login. Passwords are stored using PHP's `password_hash`; guest carts are stored against a random session token and merged into the signed-in account cart after login.

## Administrator tools

Sign in with an administrator account and open **Admin dashboard** under **Administrator tools** in the account menu. The dashboard provides store overview metrics, recent product and order activity, and controls to grant or revoke administrator access for other accounts. The available privilege levels are **Customer** and **Administrator**. Administrators cannot change their own privilege, and the last administrator cannot be demoted.

To create a catalog listing, use **Add Product** from the dashboard. The form saves the product and its PHP-priced variant in one database transaction, with optional image and talent links. New products can be saved as drafts or published immediately. The user's role is read from the database on each request, so role changes take effect without signing in again; administrator pages also check the role server-side.

To grant administrator access, first register an account, then update its role in MySQL:

```sql
UPDATE user_tbl SET user_role = 'admin' WHERE email = 'admin@example.com';
```

Standard customer accounts cannot access the product entry page.
