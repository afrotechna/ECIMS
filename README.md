# MUSOMA COHAS — Integrated Management System

MUSOMA College of Health and Allied Sciences — Academic, Clinical & Accounting Management System.

**Stack:** Laravel 11, MySQL, Blade, Livewire, Bootstrap 5, Bootstrap Icons, SweetAlert2, Alpine.js.

---

## Requirements

- PHP 8.2+
- Composer
- MySQL 8+ (or MariaDB)
- XAMPP (or equivalent) for local development

---

## Setup

### 1. Enable PHP zip extension (recommended for faster Composer)

In `C:\xampp\php\php.ini`, uncomment:

```ini
;extension=zip
```

Change to:

```ini
extension=zip
```

Save and restart Apache if needed.

### 2. Install dependencies

```bash
cd c:\xampp\htdocs\srs\cohas
composer install
```

### 3. Environment

```bash
copy .env.example .env
php artisan key:generate
```

Edit `.env` and set your MySQL database:

```env
DB_DATABASE=cohas
DB_USERNAME=root
DB_PASSWORD=
```

### 4. Create database

Create a database named `cohas` in MySQL (e.g. via phpMyAdmin or `mysql -u root -e "CREATE DATABASE cohas;"`).

### 5. Run migrations

```bash
php artisan migrate
```

### 6. Run the app

**Option A — Laravel development server**

```bash
php artisan serve
```

Then open: http://127.0.0.1:8000

**Option B — XAMPP Apache**

Point your vhost document root to `c:\xampp\htdocs\srs\cohas\public` and ensure `mod_rewrite` is enabled. Then open your configured URL (e.g. http://localhost/cohas/public).

---

## Project structure (high level)

- `app/` — Application code (models, controllers, Livewire components)
- `config/` — Configuration (app, database, auth, session, view)
- `database/migrations/` — Database migrations (users, sessions, cache)
- `resources/views/` — Blade templates (layouts/app, welcome, dashboard, auth)
- `routes/web.php` — Web routes (home, dashboard, login, register)
- `public/` — Entry point (`index.php`), assets

---

## Next steps

- Add Laravel Breeze for full login/register (or Fortify).
- Implement student registration (NACTVET reg no, system reg no).
- Add programmes, fee structure, payments, and reporting modules.

---

## Documentation

See the SRS docs in the parent folder:

- `../SRS-IMPROVEMENTS.md`
- `../TECHNICAL-SPECIFICATIONS.md`
- `../TECHNOLOGY-STACK.md`
