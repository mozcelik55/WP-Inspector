# Shared Template Archive/Restore Fix – v1.4.45

**Issue:** Shared users unable to archive templates from their view
**Error Message:** "Only the template owner can archive this template"
**Root Cause:** Logic mismatch between template ownership (global) and shared user permissions (per-user view)

---

## Changes Made

### 1. **wpi_archive_template()** (line 1704)
**Before:** Only template owner could archive globally
**After:** 
- Template owner/system owner → archives globally (changes `templates.status`)
- Shared users → automatically redirected to user-scoped archive (user meta only)
  - Stores in `user_meta: wpi_archived_templates`
  - Template stays active globally; hidden only for that user
  - Their completed inspections remain visible to them

**Logic:**
```php
if (shared_user) {
    return $this->wpi_user_archive_template(); // User-scoped
} else if (owner) {
    $wpdb->update('templates', ['status'=>'archived']); // Global
}
```

### 2. **wpi_restore_template()** (line 1751)
**Same pattern:**
- Owner → restores globally
- Shared user → auto-redirect to `wpi_user_restore_template()` (user meta removal)

### 3. **wpi_user_archive_template()** (line 2990)
**Enhanced:** Now accepts both `id` and `template_id` parameters
```php
$template_id = absint( $input['template_id'] ?? $input['id'] ?? 0 );
```
This ensures compatibility with frontend sending either parameter name.

### 4. **wpi_user_restore_template()** (line 3004)
**Enhanced:** Same dual parameter support as above

---

## Behavior After Fix

### Template Owner
```
Archive button → Changes templates.status to 'archived' globally
                → Template disappears for ALL users
                → Inspections remain visible
                → Other users lose access
```

### Shared User (view/conduct/edit access)
```
Archive button → Stores template_id in user_meta['wpi_archived_templates']
                → Template hidden from THEIR view only
                → Template still active for owner & other users
                → Completed inspections visible to this user
                → Owner can still see it, conduct it, share it
```

---

## Technical Details

### Share Permission Check
```php
$is_shared = $wpdb->get_var(
    "SELECT id FROM wpi_template_shares 
     WHERE template_id=$id AND (
        (shared_with_type='user' AND shared_with_id=$uid) OR
        (shared_with_type='team' AND shared_with_id IN (
            SELECT team_id FROM wpi_team_members WHERE user_id=$uid
        ))
     ) LIMIT 1"
);
```

Both direct user shares and team-based shares are checked.

### User Meta Storage
```php
$hidden = get_user_meta( $uid, 'wpi_archived_templates', true );
// Returns: array of template IDs [123, 456, 789]
update_user_meta( $uid, 'wpi_archived_templates', $hidden );
```

### Inspection Visibility
When user archives a shared template:
1. ✅ Completed inspections still visible to them
2. ✅ Template no longer appears in template list (filtered via `wpi_archived_templates`)
3. ✅ Restore option available in archived templates section
4. ✅ Owner still owns template and can reactivate globally

---

## Migration Notes

**Existing Data:**
- No database schema changes
- User archives stored in `wp_usermeta` (keymeta='wpi_archived_templates')
- No cleanup needed

**Backward Compatibility:**
- ✅ Old code sending `id` parameter → now works
- ✅ New code sending `template_id` parameter → works
- ✅ Both AJAX actions (`wpi_archive_template`, `wpi_user_archive_template`) available

---

## Testing Checklist

- [ ] Template owner archives template → disappears for all users
- [ ] Shared user (conduct access) archives same template → only disappears for them
- [ ] Owner can still see shared template in dashboard
- [ ] Shared user can restore from their archived list
- [ ] Completed inspections remain visible after archive
- [ ] Multiple users can archive/restore independently

---

## Version
- **Plugin:** WP Inspector v1.4.45
- **Date:** May 19, 2026
- **Related:** Previously working in older builds; regression introduced when archive logic was unified
