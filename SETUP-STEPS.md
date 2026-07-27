# MUSOMA COHAS — Setup Steps

Follow these steps in order to run the project on your machine.

---

## Step 1: Enable PHP zip extension

1. Open **`C:\xampp\php\php.ini`** in a text editor.
2. Search for: `;extension=zip`
3. Remove the semicolon so it becomes: **`extension=zip`**
4. Save the file.
5. If XAMPP Apache is running, restart it from the XAMPP Control Panel.

*(This makes Composer use .zip downloads and run much faster.)*

---

## Step 2: Install PHP dependencies

1. Open **Command Prompt** or **PowerShell**.
2. Go to the project folder:
   ```powershell
   cd c:\xampp\htdocs\srs\cohas
   ```
3. Run:
   ```powershell
   composer install
   ```
4. Wait until it finishes (no errors at the end).

---

## Step 3: Create environment file and app key

1. In the same folder (`cohas`), run:
   ```powershell
   copy .env.example .env
   ```
2. Generate the application key:
   ```powershell
   php artisan key:generate
   ```

---

## Step 4: Create the MySQL database

1. Start **XAMPP** and start **Apache** and **MySQL**.
2. Open **phpMyAdmin**: http://localhost/phpmyadmin
3. Click **New** (or “Databases”) and create a database named: **`cohas`**
4. Collation: **utf8mb4_unicode_ci** (or leave default).
5. Click **Create**.

*(Or in MySQL command line: `CREATE DATABASE cohas;`)*

---

## Step 5: Configure database in .env

1. Open **`c:\xampp\htdocs\srs\cohas\.env`** in a text editor.
2. Ensure these lines match your MySQL setup (default XAMPP is usually root with no password):
   ```env
   DB_CONNECTION=mysql
   DB_HOST=127.0.0.1
   DB_PORT=3306
   DB_DATABASE=cohas
   DB_USERNAME=root
   DB_PASSWORD=
   ```
3. If your MySQL has a password, set it in **`DB_PASSWORD=`**.
4. Save the file.

---

## Step 6: Run database migrations

1. In the project folder, run:
   ```powershell
   cd c:\xampp\htdocs\srs\cohas
   php artisan migrate
   ```
2. When asked “Do you want to run the migrations?”, type **yes** and press Enter.
3. Wait until you see “Migration table created” and migrations completed.

---

## Step 7: Run the application

1. In the same folder, run:
   ```powershell
   php artisan serve
   ```
2. You should see: `Server running on [http://127.0.0.1:8000]`.
3. Open a browser and go to: **http://127.0.0.1:8000**
4. You should see the MUSOMA COHAS welcome page and be able to open **Dashboard** from the menu.

---

## Quick checklist

| Step | Action |
|------|--------|
| 1 | Enable `extension=zip` in `C:\xampp\php\php.ini` and restart Apache |
| 2 | `cd c:\xampp\htdocs\srs\cohas` then `composer install` |
| 3 | `copy .env.example .env` then `php artisan key:generate` |
| 4 | Create database **cohas** in phpMyAdmin (or MySQL) |
| 5 | Set `DB_DATABASE=cohas`, `DB_USERNAME=root`, `DB_PASSWORD=` in `.env` |
| 6 | `php artisan migrate` (answer **yes**) |
| 7 | `php artisan serve` and open http://127.0.0.1:8000 |

---

## If something goes wrong

- **“zip extension missing”** → Step 1 not done or PHP not restarted.
- **“could not find driver” (database)** → Enable `extension=pdo_mysql` in `php.ini` (remove `;`).
- **“Access denied” (MySQL)** → Check `DB_USERNAME` and `DB_PASSWORD` in `.env` (Step 5).
- **“No application encryption key”** → Run `php artisan key:generate` again (Step 3).
