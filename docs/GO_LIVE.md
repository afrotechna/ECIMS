# COHAS — Go-live checklist

Use this before opening the system to all staff and students.

## 1. Environment (production server)

- [ ] Copy `.env.example` → `.env` and set `APP_ENV=production`, `APP_DEBUG=false`, `LOG_LEVEL=warning` or `error`
- [ ] Run `php artisan key:generate` if `APP_KEY` is empty
- [ ] Set `APP_URL` to the real HTTPS URL
- [ ] Configure MySQL credentials (`DB_*`)
- [ ] Set `MAIL_*` for real email (not `log`) if you use notifications
- [ ] Run `php artisan migrate` on a **staging** copy first
- [ ] If tables already exist but `migrate:status` shows **Pending**, run:  
  `php artisan cohas:sync-migration-records`  
  then `php artisan migrate` again
- [ ] Run `php artisan config:cache` and `php artisan route:cache` (after testing without cache)

## 2. Database backup

- [ ] Daily MySQL backup before live fee/academic data entry
- [ ] Test restore once on a copy

## 3. Staff accounts

Create users for each role that will work in the system (Users menu, administrator only):

- [ ] Administrator (at least one)
- [ ] Principal / vice principals as needed
- [ ] Accountant (finance: payments, reports, fee structures)
- [ ] Tutors / HOD / examination officer (academics, results, clinical rotations)
- [ ] Admission officer (students, semester registration)

Each new user gets a temporary password and must change it on first login.

## 4. Student logins

Students need an **email** on their record and a **user** account.

```bash
# Preview placeholder emails (uses NACTVET/reg no + domain)
php artisan students:prepare-login-emails --dry-run

# Apply (default domain: student.cohas.local)
php artisan students:prepare-login-emails
```

Then in the app: **System → Users → Create logins for all students**, or create individually.

**Getting passwords:** Passwords are encrypted and **cannot be looked up later**. After bulk create, download the **CSV** (login + temporary password). For accounts already created, use **Users → Re-issue all student passwords (CSV)** or open a user → **Issue temporary password** (shown on screen).

**Email passwords (optional):** On the user edit page, tick **Email to …** when issuing a password. Requires real student emails (not `@student.cohas.local`) and `MAIL_*` settings in `.env` (SMTP). With `MAIL_MAILER=log`, emails are written to `storage/logs/laravel.log` only.

Students log in with **NACTVET registration number** (see login page). They must change the temporary password and complete profile.

## 5. Master data

- [ ] Programmes and courses
- [ ] Active semesters for the academic year
- [ ] Fee structures linked to programmes/semesters
- [ ] Hostels → generate or add rooms (berth count per room)

## 6. Accommodation smoke test

- [ ] Create allocation for student A in a room with free berths
- [ ] Confirm room **does not** appear for student B when room is full
- [ ] **Live by room** shows resident name, phone, “Fully occupied” when full
- [ ] End allocation → room available again for another student

## 7. Finance & academics smoke test

- [ ] Record a payment; print receipt
- [ ] Run arrears and income reports
- [ ] Approve a semester registration
- [ ] Import or enter results (if in scope)

## 8. Security & operations

- [ ] Do **not** run `demo:reset-data` on production
- [ ] Restrict SSH/RDP and database access
- [ ] If `QUEUE_CONNECTION=database`, run a queue worker:  
  `php artisan queue:work`
- [ ] Optional: schedule `php artisan schedule:run` in cron

## 9. Automated tests (developers / CI)

```bash
composer test
```

All tests should pass before tagging a release.

## 10. LAN access (current setup)

If using Laragon on a PC, set `APP_URL` to `http://<this-PC-IPv4>` so links and assets work from other computers on the network.

---

**Quick health check:** open `/up` — should return OK when the app and database are reachable.
