# How to Run MUSOMA COHAS

This document describes how to set up and run the COHAS (College of Health and Allied Sciences) application on your machine.

---

## Prerequisites

- **PHP 8.2 or higher** (with extensions: bcmath, ctype, fileinfo, json, mbstring, openssl, pdo, tokenizer, xml)
- **Composer** ([getcomposer.org](https://getcomposer.org))
- **MySQL** (e.g. via XAMPP, WAMP, or standalone)
- **Git** (optional, for cloning)

---

## 1. Get the project

If you have the project as a folder (e.g. `cohas`), go into it:

```bash
cd path/to/cohas
```

If cloning from a repo:

```bash
git clone <repository-url> cohas
cd cohas
```

---

## 2. Install PHP dependencies

```bash
composer install
```

Use `composer install --no-dev` on production.

---

## 3. Environment configuration

Copy the example environment file and generate an application key:

```bash
copy .env.example .env
php artisan key:generate
```

(On Linux/macOS: `cp .env.example .env`)

Edit `.env` and set at least:

| Variable     | Description                    | Example          |
|-------------|--------------------------------|------------------|
| `APP_URL`   | Base URL of the app            | `http://localhost` or `http://localhost/srs/cohas/public` (see below) |
| `DB_DATABASE` | MySQL database name          | `cohas`          |
| `DB_USERNAME` | MySQL user                   | `root`           |
| `DB_PASSWORD` | MySQL password (empty if none) | ``             |

---

## 4. Database setup

1. Create a MySQL database named `cohas` (or the name you set in `DB_DATABASE`):

   ```sql
   CREATE DATABASE cohas CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
   ```

2. Run migrations:

   ```bash
   php artisan migrate
   ```

3. (Optional) Seed initial data if seeders exist:

   ```bash
   php artisan db:seed
   ```

---

## 5. Run the application

### Option A: Laravel development server (simplest)

From the project root (`cohas`):

```bash
php artisan serve
```

Then open in the browser: **http://127.0.0.1:8000**

To use a different host/port:

```bash
php artisan serve --host=0.0.0.0 --port=9000
```

Set `APP_URL` in `.env` to match (e.g. `http://127.0.0.1:8000`).

---

### Option B: XAMPP (Apache)

If the project is under XAMPP (e.g. `C:\xampp\htdocs\srs\cohas`):

1. **Using a virtual host (recommended)**  
   Point the document root to the `public` folder of the project, e.g.:

   - Document root: `C:/xampp/htdocs/srs/cohas/public`
   - Then open: `http://localhost` (or the hostname you configured)

2. **Without virtual host**  
   Ensure the web server can serve from the `public` directory. For example, if `htdocs` is your web root, you could:
   - Put the project in `htdocs/srs/cohas` and open: **http://localhost/srs/cohas/public**  
   and set in `.env`:

   ```env
   APP_URL=http://localhost/srs/cohas/public
   ```

Start **Apache** and **MySQL** from the XAMPP Control Panel, then visit the URL above.

---

## 6. Storage and cache (if needed)

If you see permission or “storage link” errors:

```bash
php artisan storage:link
```

Clear config/cache after changing `.env`:

```bash
php artisan config:clear
php artisan cache:clear
```

---

## 7. Default login

If the app uses seeders for a default admin/user, check the seeders or project documentation for the initial login (email/username and password). Otherwise, create a user via the application or a seeder.

---

## Quick reference

| Step              | Command / action                          |
|-------------------|-------------------------------------------|
| Install deps      | `composer install`                        |
| Env + key         | `copy .env.example .env` then `php artisan key:generate` |
| Migrate DB        | `php artisan migrate`                     |
| Run dev server    | `php artisan serve` → http://127.0.0.1:8000 |
| Clear config      | `php artisan config:clear`                 |

---

## Troubleshooting

- **500 error:** Check `storage/logs/laravel.log`, ensure `storage` and `bootstrap/cache` are writable, and that `APP_KEY` is set in `.env`.
- **Database connection error:** Verify MySQL is running and `DB_*` values in `.env` match your database.
- **Page not found / wrong routes:** Confirm you are opening the correct URL (e.g. `.../public` or the virtual host pointing to `public`).
