# CLAUDE.md — VTA Student Information System (SIS)

## Project Overview
Web-based Student Information System for Vocational Training Authority – Ambegoda.
Stack: PHP 8.x + MySQL 8.0 + HTML/CSS/JS (vanilla) running on WAMP Server.

## Tech Stack
- **Backend**: PHP 8.x (procedural, no framework needed for simplicity)
- **Database**: MySQL 8.0 via WAMP
- **Frontend**: HTML5, CSS3, vanilla JavaScript (no build tools)
- **Server**: WAMP (Apache + MySQL + PHP on Windows)
- **DB Admin**: phpMyAdmin (bundled with WAMP)
- **Root path**: `C:/wamp64/www/vtasis/`

---

## File Structure
```
vtasis/
├── CLAUDE.md
├── index.php                  # Login page / redirect
├── config/
│   └── db.php                 # DB connection (PDO)
├── includes/
│   ├── header.php
│   ├── footer.php
│   ├── auth.php               # Session auth check
│   └── functions.php          # Shared helpers
├── assets/
│   ├── css/
│   │   └── style.css
│   └── js/
│       └── main.js
├── modules/
│   ├── auth/
│   │   ├── login.php
│   │   └── logout.php
│   ├── dashboard/
│   │   └── index.php          # Role-based dashboard
│   ├── students/
│   │   ├── index.php          # List students
│   │   ├── add.php
│   │   ├── edit.php
│   │   └── view.php
│   ├── courses/
│   │   ├── index.php
│   │   ├── add.php
│   │   └── edit.php
│   ├── enrollment/
│   │   ├── index.php
│   │   └── manage.php
│   ├── attendance/
│   │   ├── index.php
│   │   └── mark.php
│   ├── grades/
│   │   ├── index.php
│   │   └── manage.php
│   ├── faculty/
│   │   ├── index.php
│   │   ├── add.php
│   │   └── edit.php
│   ├── departments/
│   │   ├── index.php
│   │   └── manage.php
│   └── reports/
│       ├── academic.php
│       └── attendance.php
└── database/
    ├── vtasis_db.sql           # Full DB dump / setup script
    └── seed.sql                # Sample data
```

---

## Database: VTASIS_DB

### Tables
1. **users** — system login accounts (all roles)
2. **departments** — VTA departments
3. **faculty** — teacher/instructor records
4. **students** — student personal & enrollment info
5. **courses** — course catalog
6. **course_assignments** — faculty assigned to courses
7. **enrollments** — student ↔ course registration
8. **attendance** — per-student, per-course attendance records
9. **grades** — student grades per course
10. **financial_aid** — scholarship/financial assistance records

### Naming Convention
All tables and columns use `snake_case`. Foreign keys follow `<table_singular>_id` pattern.

---

## Roles (RBAC)
| Role | Access |
|------|--------|
| `admin` | Full system access |
| `faculty` | Courses, grades, attendance for their classes |
| `student` | View own records, grades, attendance |
| `support` | Administrative tasks, reports |

---

## Key Rules for Claude Code
- Always use **PDO with prepared statements** — no raw `mysqli_query` with user input
- All pages must start with `require_once '../includes/auth.php';` and check `$_SESSION['role']`
- Use `htmlspecialchars()` on all output to prevent XSS
- Passwords hashed with `password_hash()` / verified with `password_verify()`
- Keep each PHP file under ~150 lines — split logic into includes/functions.php if needed
- No external libraries/CDN unless specified — keep it offline-friendly for WAMP
- All DB queries go through `config/db.php` PDO connection (`$pdo`)
- Forms use POST; list/search pages use GET
- Flash messages via `$_SESSION['flash']` stored and displayed in header.php

---

## Environment
- WAMP root: `C:/wamp64/www/vtasis/`
- DB Host: `localhost`
- DB Name: `vtasis_db`
- DB User: `root`
- DB Pass: `` (empty by default on WAMP)
- PHP version: 8.x
- Access URL: `http://localhost/vtasis/`