# StudentHub Portal

StudentHub is a web portal designed for college students to manage their academic and campus life digitally. It reduces dependency on physical notice boards, scattered WhatsApp groups, and manual processes by providing a centralized platform for notices, timetables, study materials, and student profiles.

## User Roles
- **Student** – View notices, check timetable, download study material, update profile
- **Faculty** – Post notices, upload study materials, manage class schedule
- **Admin** – Manage users, approve/moderate content, oversee all modules

## Key Modules
1. Authentication (Login / Register)
2. Notice Board
3. Timetable / Schedule
4. Study Material Repository
5. Profile & Settings

## Pages
1. Home / Landing Page
2. Login Page
3. Register Page
4. Student Dashboard
5. Faculty Dashboard
6. Notice Board
7. Notice Detail Page
8. Timetable Page
9. Study Materials Page
10. Profile Page
11. About / Contact Page

## Navigation Flow
Landing Page → Login/Register → Role-based Dashboard → Module Pages (Notice Board / Timetable / Study Materials) → Profile

## Tech Stack
HTML, CSS, JavaScript, PHP, MySQLi, MariaDB, XAMPP, GitHub

## Folder Structure
```
studenthub-portal/
├── output/
│   ├── pdf/                 # Practical reports 1-15
│   └── csv/                 # Student list exports
├── docs/                    # Supporting documents and screenshots
│   ├── sitemap/
│   ├── wireframes/
│   ├── responsive-screenshots/
│   ├── practical9-screenshots/
│   ├── practical10-screenshots/
│   ├── practical11-screenshots/
│   └── practical12-screenshots/
├── scripts/
│   ├── build-practical9-report.py
│   ├── build-practical10-report.py
│   ├── seed-practical10.php
│   ├── test-practical10.py
│   ├── build-practical11-report.py
│   ├── test-practical11.py
│   ├── export-practical11.php
│   ├── build-practical12-report.py
│   ├── setup-practical12.php
│   ├── test-practical12.py
│   ├── export-practical12.php
│   ├── test-practical13.py
│   ├── test-practical13-input.php
│   ├── check-practical15.py
│   ├── test-practical14.py
│   └── build-practical13-15-reports.py
├── sql/
├── php/
├── css/
├── js/
├── images/
├── data/
└── README.md
```

All practical PDF reports are stored in `output/pdf/`. Supporting screenshots,
wireframes and setup notes remain in `docs/`. Temporary report-generation files
have been removed; the reusable report builders are kept in `scripts/`.

Practical 10 login and session setup: [docs/Practical10-setup.md](docs/Practical10-setup.md).

Practical 11 student management setup: [docs/Practical11-setup.md](docs/Practical11-setup.md).

Practical 12 event management setup: [docs/Practical12-setup.md](docs/Practical12-setup.md).

Practical 13 JSON API setup: [docs/Practical13-setup.md](docs/Practical13-setup.md).

Practical 14 dashboard and audit setup: [docs/Practical14-setup.md](docs/Practical14-setup.md). Apply `sql/practical14.sql` before using the admin activity log.

Practical 15 integration and local deployment: [docs/Practical15-deployment.md](docs/Practical15-deployment.md).
