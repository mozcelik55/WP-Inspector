# WP Inspector – WordPress Plugin

A powerful inspection & audit management system for WordPress, inspired by iAuditor/SafetyCulture.

---

## Features

- 📋 **Template Builder** — Create inspection forms with multiple question types
- 🔍 **Conduct Inspections** — Fill out inspections with Yes/No, text, number, photo, and more
- 📊 **Dashboard** — Overview of all inspections, scores, and status
- 📄 **PDF Reports** — Generate printable reports per inspection
- ⚑ **Flag Items** — Mark specific questions for follow-up
- 🔒 **Role-based Access** — Admins see all, users see their own

---

## Question Types

| Type              | Description                        |
|-------------------|------------------------------------|
| Yes / No          | Pass/Fail with N/A option          |
| Text              | Free-form text answer              |
| Number            | Numeric input                      |
| Multiple Choice   | Select from custom options         |
| Photo             | Attach a photo URL                 |
| Date & Time       | Date/time picker                   |

---

## Installation

1. Upload the `wp-inspector` folder to `/wp-content/plugins/`
2. Activate the plugin in **WordPress Admin → Plugins**
3. The plugin will automatically create the required database tables
4. Navigate to **WP Inspector** in the admin sidebar

---

## REST API Endpoints

All endpoints are under `/wp-json/wp-inspector/v1/` and require authentication.

| Method | Endpoint                            | Description              |
|--------|-------------------------------------|--------------------------|
| GET    | /templates                          | List all templates       |
| POST   | /templates                          | Create a template        |
| GET    | /templates/{id}                     | Get a template           |
| PUT    | /templates/{id}                     | Update a template        |
| POST   | /templates/{id}/questions           | Save questions           |
| GET    | /templates/{id}/questions           | Get questions            |
| GET    | /inspections                        | List inspections         |
| POST   | /inspections                        | Start an inspection      |
| GET    | /inspections/{id}                   | Get inspection + responses|
| PUT    | /inspections/{id}                   | Save responses           |
| GET    | /inspections/{id}/pdf               | Download PDF report      |
| GET    | /dashboard                          | Dashboard stats          |

---

## Database Tables

| Table                    | Description                      |
|--------------------------|----------------------------------|
| wp_wpi_templates         | Inspection form templates        |
| wp_wpi_questions         | Questions per template           |
| wp_wpi_inspections       | Conducted inspection records     |
| wp_wpi_responses         | Answers per inspection           |

---

## Roadmap / Next Steps

- [ ] WP Media Library picker for photo questions
- [ ] Email reports after completion
- [ ] Custom user roles (Inspector, Auditor, Manager)
- [ ] CSV export of inspection data
- [ ] Scheduled/recurring inspections
- [ ] mPDF integration for richer PDF reports
- [ ] Front-end shortcode for non-admin users

---

## Requirements

- WordPress 5.8+
- PHP 7.4+
- MySQL 5.7+ or MariaDB 10.3+

---

## License

GPL-2.0+
