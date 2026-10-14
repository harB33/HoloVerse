# HoloVerse

## Project structure

- `index.php` is the home page entry point.
- `pages/` contains the directly accessible PHP pages.
- `Functions/` retains legacy page URLs and forwards them to `pages/`.
- `app/views/layout/` contains shared page layouts.
- `app/views/components/` contains reusable interface components.
- `config/` contains application configuration, including the database connection.
- `database/` contains the SQL schema.
- `assets/images/` and `assets/css/` contain browser-delivered assets.
- `resources/css/` contains the Tailwind CSS source.

Build the stylesheet with `npm run build:css`; use `npm run watch:css` while developing styles.
