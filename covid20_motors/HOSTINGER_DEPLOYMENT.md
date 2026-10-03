# Hostinger Deployment

## 1. Create the database

In hPanel, create a MySQL database and database user, then grant that user access to the database. Hostinger commonly prefixes both names with your account name; use the exact names shown in hPanel, not the local XAMPP values.

## 2. Upload the project

Upload the project contents into the domain's document root (usually `public_html`). Keep the existing `user/` and `uploads/` directories in their current locations. The customer sign-in page is `/user/index.html`; the root `index.html` is the admin interface.

## 3. Configure database access

Prefer server environment variables when your hosting plan exposes them: `DB_HOST`, `DB_NAME`, `DB_USER`, `DB_PASSWORD`, and optionally `DB_PORT` (usually `3306`). Otherwise create `database_config.local.php` beside `database_config.php` with values copied from hPanel:

```php
<?php
return [
    'host' => 'localhost',
    'name' => 'YOUR_HACCOUNT_database',
    'user' => 'YOUR_HACCOUNT_user',
    'password' => 'YOUR_DATABASE_PASSWORD',
    'port' => '3306',
    'initial_admin_email' => 'admin@example.com',
    'initial_admin_password' => 'USE_A_UNIQUE_PASSWORD_OF_AT_LEAST_12_CHARACTERS',
];
```

Replace every example value. Do not commit or publish this file; it is excluded by `.gitignore` and direct web access is denied by the root `.htaccess`. Keep a separate copy outside the document root as a backup.

## 4. Import the schema

Open phpMyAdmin from hPanel, select the newly created database, and import `database.sql`. Then import `auth_login_attempts.sql` into the same database. The SQL files expect a database to have already been selected; they do not create Hostinger's account-prefixed database.

Set `INITIAL_ADMIN_PASSWORD` and optionally `INITIAL_ADMIN_EMAIL` as server environment variables, or use `initial_admin_password` and `initial_admin_email` in the ignored local config above. The password must be at least 12 characters. After importing the schema and setting these values, open the admin interface at `/` and sign in with identifier `admin` and that password; the first admin login request provisions the account. Do not use a public installer URL; `install_database.php` is intentionally blocked from web access.

## 5. Check PHP and writable folders

Use a PHP version supported by the hosting account and enable `pdo_mysql` and `mysqli`. Confirm the account can write to `uploads/product_images/` and `user/uploads/receipts/`; use the least-permissive writable mode Hostinger permits. Upload folders deny PHP execution and directory listing.

## 6. Smoke test

Open `/api_connect.php` and verify the JSON reports a successful database connection. Then test `/user/index.html`, account registration/login, product loading, image loading, cart operations, and one test order. Remove or disable diagnostic endpoints such as `api_connect.php` and `test_connection.php` after setup. Ensure HTTPS is active before using the site.

## Hostinger details still required

The database name, database username, password, domain/document-root choice, and exact hosting error must come from the Hostinger account. They are intentionally not guessed or stored in this repository. If a step fails, capture the failing URL and the matching PHP error-log entry from hPanel.