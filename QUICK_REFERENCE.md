# WP Inspector – Quick Reference Guide

## Key Files at a Glance

### Entry Point
```
wp-inspector.php (164KB)
├── Defines: WPI_VERSION, WPI_PLUGIN_DIR, WPI_PLUGIN_URL
├── Activates database & migrations
├── Login hooks & brute-force protection
└── Requires all 11 classes
```

### Main API Handler
```
includes/class-ajax.php (404KB)
├── AJAX handlers: wp_ajax_wpi_*
├── Templates: create, save, list, share, duplicate, export
├── Inspections: create, save_responses, flag, complete
├── Photos: upload, resize, thumbnail generation
├── Scheduling: create_schedule, get_schedule, run_schedule
├── Reports: generate, download, email
└── Nonce validation + Org scoping on all calls
```

### PDF & Email
```
includes/class-pdf.php (176KB)
├── HTML → PDF conversion
├── Photo thumbnails & signatures
├── Conditional content rendering
└── Header/footer with metadata

includes/class-pdf-email.php (56KB)
├── Async PDF generation via WP Cron
├── Mailjet integration
└── Retry logic & error handling
```

### Access & Security
```
includes/class-access.php (32KB)
├── Organization scoping
├── Role checks: admin, manager, inspector, viewer
├── User activation/deactivation
└── Daily expiry cron

includes/class-billing.php (36KB)
├── License validation
├── Seat allocation
└── Quota enforcement
```

### Frontend
```
assets/js/app.js (648KB)
├── Hyperscript h() UI (no JSX)
├── Template builder
├── Inspection form renderer
├── Dashboard with filters
└── Photo upload + compression

assets/css/app.css (12KB)
├── Responsive mobile-first
├── PWA styling
└── Print styles for PDF

manifest.json + sw.js
├── PWA configuration
├── Service worker caching
└── Push notification setup
```

---

## Common AJAX Endpoints

All require nonce + WordPress authentication.

### Templates
```
POST /wp-admin/admin-ajax.php?action=wpi_create_template
POST /wp-admin/admin-ajax.php?action=wpi_save_template
GET  /wp-admin/admin-ajax.php?action=wpi_list_templates
GET  /wp-admin/admin-ajax.php?action=wpi_get_template&id=X
POST /wp-admin/admin-ajax.php?action=wpi_save_questions
```

### Inspections
```
POST /wp-admin/admin-ajax.php?action=wpi_create_inspection
POST /wp-admin/admin-ajax.php?action=wpi_save_responses
GET  /wp-admin/admin-ajax.php?action=wpi_get_inspection&id=X
POST /wp-admin/admin-ajax.php?action=wpi_complete_audit
POST /wp-admin/admin-ajax.php?action=wpi_flag_item
```

### Photos
```
POST /wp-admin/admin-ajax.php?action=wpi_upload_photo
POST /wp-admin/admin-ajax.php?action=wpi_delete_photo
```

### Reports & Email
```
POST /wp-admin/admin-ajax.php?action=wpi_generate_pdf
POST /wp-admin/admin-ajax.php?action=wpi_send_pdf_email
```

### Scheduling
```
POST /wp-admin/admin-ajax.php?action=wpi_create_schedule
GET  /wp-admin/admin-ajax.php?action=wpi_list_schedules
POST /wp-admin/admin-ajax.php?action=wpi_run_schedule
```

---

## Database Tables

```sql
wp_wpi_templates
├── id, org_id, user_id (creator)
├── name, description, status (draft/published/archived)
├── created_at, updated_at
└── Indexes: idx_status_updated, idx_org_status_updated

wp_wpi_questions
├── id, template_id, sort_order
├── question_text, type (yes_no, text, photo, etc.)
├── required, conditional_logic, repeatable
└── Index: idx_template_sort

wp_wpi_inspections
├── id, template_id, org_id, user_id (conductor)
├── status (in_progress, completed)
├── conducted_at, completed_at
├── flagged_count, score
└── Index: idx_template_conducted

wp_wpi_responses
├── id, inspection_id, question_id
├── answer_text, answer_value, photos
├── signature (base64)
└── Index: idx_inspection_question

wp_wpi_licences
├── id, org_id, user_id (owner)
├── license_key (hashed)
├── seats_available, seats_used
├── status, expires_at
└── Main licensing table

wp_wpi_licence_seats
├── id, licence_id, user_id
├── assigned_at, expires_at
└── Seat assignments per license

wp_wpi_template_shares
├── id, template_id
├── shared_with_type (user, role, org)
├── shared_with_id
└── Index: idx_share_lookup

wp_wpi_scheduled_inspections
├── id, template_id, org_id
├── schedule (cron format)
├── recipient_emails
└── Next run, last run timestamps
```

---

## File Modification Workflow

**When updating code:**

1. Never directly edit in `/wp-content/plugins/wp-inspector/`
2. Copy files to `/home/claude/wp-inspector/`
3. Make changes in working directory
4. Test locally (if possible) or inspect visually
5. Create zip package and deliver
6. Include changelog & deployment notes

**Deliver as zip:**
```bash
cd /home/claude
zip -r wp-inspector-1_4_45.zip wp-inspector/
```

---

## Security Checklist

- ✅ All AJAX actions use `wp_verify_nonce()`
- ✅ All database queries use `$wpdb->prepare()`
- ✅ All org-scoped queries filter by `org_id`
- ✅ All role checks use `current_user_can()`
- ✅ Brute-force protection: 10 attempts per IP per 15 min
- ✅ User deactivation blocks login immediately
- ✅ PDF generation doesn't expose file paths
- ✅ Email sending uses Mailjet (authenticated)

---

## Debugging Tips

### Check AJAX Errors
```php
error_log( 'Debug: ' . json_encode( $var ) );
// Tail: tail -f /wp-content/debug.log
```

### Check Database Queries
```php
// In class-ajax.php, add before any query:
$wpdb->show_errors();
$result = $wpdb->get_results( ... );
error_log( $wpdb->last_query );
```

### Check PWA/Service Worker
- Open DevTools → Application → Service Workers
- Check Console for registration errors
- Clear cache if stale assets served

### Check Email Queue
```php
// In class-pdf-email.php, check WP Cron:
wp_schedule_event( time(), 'wpi_hourly', 'wpi_send_pdf_email' );
// Verify via Tools → Cron Events (with plugin)
```

### Check License Status
- Admin UI → WP Inspector → Billing
- Database: `SELECT * FROM wp_wpi_licences WHERE org_id = X;`

---

## Deployment Commands

**After modification:**

```bash
# Test zip integrity
unzip -t wp-inspector-1_4_45.zip

# Verify file structure
unzip -l wp-inspector-1_4_45.zip | head -20

# Get file counts
unzip -l wp-inspector-1_4_45.zip | tail -1

# Check for common errors
grep -r "TODO\|FIXME\|XXX\|HACK" wp-inspector/
```

---

**Last Updated:** May 19, 2026
