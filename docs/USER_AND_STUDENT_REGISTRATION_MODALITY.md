# User Registration & Student Registration Modality

This document describes how **users** and **students** are registered in COHAS, and how they relate.

---

## 1. Overview

| Concept | What it is | Who can do it |
|--------|-------------|----------------|
| **User** | A login account (email/check number/NACTVET reg no + password). Roles: Admin, Student, Tutor/Staff, Account/Bursar. | **Admin only** (no self-registration). |
| **Student** | A student record (personal + programme, NACTVET reg no, internal reg no). | Staff/Admin (Academics, Bursar, Admin). |
| **Student user** | A user whose login is tied to an existing student (by email). Logs in with **NACTVET reg no**. | Admin creates from an existing student. |
| **Semester registration** | Enrolling a student into a specific semester (for courses, results, etc.). | Staff/Admin submit; can be approved/rejected. |

**Important:** There is **no public self-registration**. The `/register` route redirects to login with a message that accounts are created by an administrator.

---

## 2. Student Registration (Student Record First)

**Order:** Create the **student record** before any **student user** account.

### 2.1 Who can register a student

- Users with role **Admin**, **Tutor/Staff**, or **Account/Bursar** (i.e. anyone who can access **Students** in the sidebar).
- **Students** cannot register other students; they only see their own profile.

### 2.2 How to register a student

**Path:** **Academics / Students** → **Register Student** (or **Create**).

1. **Required:**
   - **NACTVET Registration No** (unique; Form Four index–based).
   - **Programme** (from active programmes).
   - **Intake year** (e.g. 2025).
   - **First name**, **Last name**.

2. **Optional:**
   - Date of birth, NTA level, student type (regular/transferred), transfer details, email, phone.

3. **System behaviour:**
   - System generates an **internal Reg No** (e.g. `MCHAS-2025-NURS-001`).
   - Student is saved with status **active**.

4. **After submit:** Redirect to Students list with success message including the generated Reg No.

### 2.3 Bulk import (students)

**Path:** **Students** → **Import** (or equivalent).

- Upload a **CSV** with columns such as: `nactvet_reg_no`, `first_name`, `last_name`, `programme_code`, `intake_year`, and optionally `email`, `phone`.
- Programme is matched by **programme code**; internal Reg No is generated per row.
- Duplicate NACTVET reg no are skipped.

---

## 3. User Registration (Login Accounts)

**All user accounts are created by an Admin.** There is no self-registration.

**Path:** **Users** (sidebar, Admin only) → **Add User**.

### 3.1 Two ways to create a user

#### A. Student user (login for an existing student)

1. Choose role **Student**.
2. Select **Student** from the dropdown.
   - Dropdown lists only **active** students who:
     - Have a non-empty **email**, and
     - Do **not** already have a user account (no existing user with that email).
3. Submit.
4. **System creates user with:**
   - Name = student’s full name  
   - Surname = student’s last name (also used as **initial password**)  
   - Email = student’s email  
   - **nactvet_reg_no** = student’s NACTVET reg no  
   - Role = student  
   - **must_change_password** = true  

5. **Login for this user:**
   - **Username:** **NACTVET reg no** (student), or check number (staff), or email (admin only).
   - **Initial password:** **surname** (must change on first login).

**Linking:** User and Student are linked by **email** (User hasOne Student where `student.email = user.email`). No `user_id` on the student table.

#### B. Staff / Admin user (non-student)

1. Choose role **Admin**, **Tutor/Staff**, or **Account/Bursar**.
2. Fill:
   - **Full name**, **Surname** (surname = initial password).
   - **Email** (unique).
   - **Check number** (unique; used as **login** for staff).
3. Submit.
4. **Login:** Check number or email; initial password = surname.

### 3.2 Bulk import (users)

**Path:** **Users** → **Import** (Admin only).

- CSV columns: `name`, `surname`, and for staff: `email`, `check_number`, `role`; for students: `nactvet_reg_no` (and student must already exist with that NACTVET reg no and an email).
- Initial password for all = surname; must change on first login.

---

## 4. Semester Registration (Enrolling in a Semester)

This is **not** the same as “student registration” (creating a student record). It is **enrolling an existing student into a semester**.

### 4.1 Who

- Staff/Admin: can create and approve/reject semester registrations.
- Students: can have registrations created for them (by staff) or, if the UI allows, submit a request (e.g. “Submit new registration” from dashboard).

### 4.2 Flow

1. **Create registration:**  
   **Semester Registrations** → **New Registration** → select **Student** and **Semester** → submit.
2. System checks the student is not already registered for that semester; then creates a **pending** semester registration.
3. **Approve / Reject:** Staff/Admin use **Semester Registrations** list to Approve or Reject (with optional notes). Only **pending** registrations can be approved/rejected.

Statuses typically: `pending`, `approved`, `rejected`.

---

## 5. End-to-end Modality (Recommended Order)

1. **Programmes** and **Semesters** (and academic setup) are in place.
2. **Register students** (Students → Register Student or Import). Each has NACTVET reg no, programme, intake, and ideally **email**.
3. **Create user accounts** (Users → Add User):
   - For **students:** select existing student → user gets NACTVET reg no as username, surname as initial password.
   - For **staff/admin:** enter name, surname, email, check number, role.
4. User **first login:** forced to change password (and complete profile if required).
5. **Semester registration:** when a semester is open, create (and approve) semester registrations for students so they can have courses, results, and fees for that semester.

---

## 6. Quick Reference: Login Identifiers

| Role            | Login (username)     | Initial password |
|-----------------|----------------------|------------------|
| Student         | NACTVET reg no       | Surname          |
| Tutor/Staff     | Check number or email| Surname          |
| Account/Bursar  | Check number or email| Surname          |
| Admin           | Email                | Surname          |

---

## 7. Routes & Permissions (Reference)

| Action                    | Route / Path                          | Access        |
|---------------------------|----------------------------------------|---------------|
| List students             | `students.index`                       | Not student   |
| Register student (form)   | `students.create`                      | Not student   |
| Store student             | `students.store`                       | Not student   |
| Import students           | `students.import` / `students.import.store` | Not student |
| List users                | `users.index`                          | Admin         |
| Add user                  | `users.create` / `users.store`         | Admin         |
| Import users              | `users.import` / `users.import.store`  | Admin         |
| Semester registrations    | `semester-registrations.*`             | Not student (manage); student (own) |
| Public “register”         | `register.create`                      | Redirects to login (no self-registration) |

This modality keeps a single, clear sequence: **Student record first → then User account (for students: from existing student); semester registration is a separate step for enrolment.**
