# COHAS — Everything in Place Checklist

Quick verification that core pieces are present and consistent. Last check: after fixing unused `show` routes.

---

## ✅ Auth & access

| Item | Status |
|------|--------|
| Login (NACTVET reg no / check number / email for admin) | ✅ `LoginController`, `auth.login` |
| Logout | ✅ `logout` route |
| No self-registration; `/register` redirects with message | ✅ `register.create` |
| Change password (mandatory first login) | ✅ `password.change`, `EnsurePasswordChanged` |
| Profile complete (mandatory after password change) | ✅ `profile.complete`, `EnsureProfileCompleted` |
| Middleware: `guest`, `auth`, `password.changed`, `profile.completed`, `admin`, `not_student` | ✅ All in `bootstrap/app.php` |

---

## ✅ User management (admin only)

| Item | Status |
|------|--------|
| List users | ✅ `users.index`, `UserController@index` |
| Add user (student: pick existing student; staff: name, email, check number, role) | ✅ `users.create`, `users.store` |
| Edit user | ✅ `users.edit`, `users.update` |
| User import (CSV) | ✅ `users.import`, `users.import.store` |
| User ↔ Student link by email | ✅ `User::student()` via `students.email` |

---

## ✅ Student registration

| Item | Status |
|------|--------|
| List students | ✅ `students.index` |
| Register student (NACTVET reg no, programme, intake, names, etc.) | ✅ `students.create`, `students.store` |
| Edit / delete student | ✅ `students.edit`, `students.update`, `students.destroy` |
| Student profile (show) | ✅ `students.show` (student sees own only) |
| Student ledger | ✅ `students.ledger`, `students.ledger.charge` |
| Student import (CSV) | ✅ `students.import`, `students.import.store` |
| Internal Reg No generation | ✅ `StudentController::generateRegNo()` |

---

## ✅ Semester registration

| Item | Status |
|------|--------|
| List semester registrations (filter by semester) | ✅ `semester-registrations.index` |
| Create registration (student + semester) | ✅ `semester-registrations.create`, `store` |
| Approve / reject | ✅ `semester-registrations.approve`, `reject` |

---

## ✅ Other modules (routes + controllers + views)

| Module | Routes | Controller | Views |
|--------|--------|------------|--------|
| Programmes | index, create, store, edit, update, destroy | ✅ | index, create, edit |
| Courses | index, create, store, edit, update, destroy | ✅ | index, create, edit |
| Fee structures | index, create, store, edit, update, destroy | ✅ | index, create, edit |
| Academics | index | ✅ | index |
| Payments | index, create, store, show, receipt | ✅ | index, create, show, receipt |
| Results | index, create, store, lock, unlock, transcript | ✅ | index, create, transcript, transcript-select |
| Reports | index, enrollment, arrears, income + exports | ✅ | index, enrollment, arrears, income |
| Hostels | index, create, store, edit, update, destroy | ✅ | index, create, edit |
| Rooms | index, create, store, edit, update, destroy | ✅ | index, create, edit |
| Accommodation allocations | index, create, store, edit, update, end | ✅ | index, create, edit |
| Live room occupancy | occupancy, live-data JSON | ✅ `RoomController` | `rooms/occupancy` |
| Clinical rotations | full CRUD, attendance, exports | ✅ | multiple views |
| Inventory items | resource | ✅ | index, create, edit |
| Activity log (admin) | index, export | ✅ | `activity-log` |

**Note:** `show` was removed from programmes, fee-structures, hostels, and rooms (routes only) because those controllers do not implement `show()` and no views link to them.

**Migrations:** If `php artisan migrate` fails because tables already exist, run `php artisan cohas:sync-migration-records` then migrate again. See `docs/GO_LIVE.md`.

---

## ✅ Models & DB

| Model | Key relations / notes |
|-------|------------------------|
| User | `student()` via email; ROLES; fillable includes surname, check_number, nactvet_reg_no |
| Student | programme, semesterRegistrations, ledgerEntries, payments, results |
| Programme | students, feeStructures, etc. |
| Semester | semesterRegistrations |
| SemesterRegistration | student, semester; status (pending/approved/rejected) |
| Migrations | users (with role, surname, check_number, nactvet_reg_no, must_change_password, profile_completed_at), students, programmes, semesters, semester_registrations, payments, results, hostels, rooms, etc. |

---

## ✅ UI & assets

| Item | Status |
|------|--------|
| Landing (welcome) page with background image | ✅ `layouts.landing`, `welcome` |
| Login page with logo in circle, landing-style card | ✅ `layouts.auth`, `auth.login` |
| Logo in circle: auth left panel, login card, landing nav & hero, app sidebar & guest nav | ✅ `public/images/logo.png` used in all |
| Logo circle background (off-white #f8fafc) | ✅ Applied |
| App layout (sidebar, topbar, role-based menu) | ✅ `layouts.app` |
| Dashboard | ✅ `dashboard` with stats, recent payments, semester registration counts |

---

## ✅ Documentation

| Doc | Purpose |
|-----|---------|
| `docs/GO_LIVE.md` | Pre-production checklist (env, users, students, smoke tests) |
| `docs/USER_AND_STUDENT_REGISTRATION_MODALITY.md` | How user and student registration work and recommended order |
| `docs/CHECKLIST_EVERYTHING_IN_PLACE.md` | This checklist |

---

## Run checks (optional)

```bash
# From project root (cohas)
php artisan route:list
php artisan migrate:status
```

If you use PHPUnit:

```bash
php artisan test
```

---

**Summary:** Auth, user and student registration, semester registration, and the main modules (programmes, courses, fees, payments, results, reports, hostels, rooms, accommodation) are wired with routes, controllers, and views. The only change made during this check was excluding the unused `show` route for programmes, fee-structures, hostels, and rooms so they match their controllers.
