# User Role Filter Implementation Plan
*For PW Advanced WooCommerce Reporting System (LBC Fork)*

---

## Overview

Add user role filtering capability to the Customer Report, allowing users to:
- **Include** specific user roles in the report
- **Exclude** specific user roles from the report

---

## Architecture Analysis

### Existing Pattern Found
The plugin already has user role filtering in these reports:
- `fetch_data_customer_role_top_products.php`
- `fetch_data_customer_role_total_sale.php`
- `fetch_data_customer_role_registered.php`

These use WordPress's `wp_dropdown_roles()` for the UI and query `wp_usermeta` with `meta_key = 'wp_capabilities'`.

### Current Customer Report (`fetch_data_customer.php`)
- Has basic date filters only
- No user role filtering
- Uses `pw_postmeta4.meta_value` for customer user ID

---

## Implementation Plan

### Phase 1: Add User Role Parameter (SQL)

**File:** `includes/fetch_data_customer.php`

**Changes:**
1. Add parameter retrieval: `$pw_user_roles = $this->pw_get_woo_requests('pw_user_roles', NULL, true);`
2. Add parameter retrieval: `$pw_user_roles_exclude = $this->pw_get_woo_requests('pw_user_roles_exclude', NULL, true);`
3. Add usermeta JOIN for role filtering:
```php
$sql_joins .= " LEFT JOIN {$wpdb->prefix}usermeta AS usermeta_role 
    ON usermeta_role.user_id = pw_postmeta4.meta_value 
    AND usermeta_role.meta_key = '{$wpdb->prefix}capabilities'";
```
4. Add WHERE conditions:
```php
// Include role
if($pw_user_roles != NULL && $pw_user_roles != '-1') {
    $sql_condition .= " AND usermeta_role.meta_value LIKE '%\"$pw_user_roles\"%'";
}
// Exclude role  
if($pw_user_roles_exclude != NULL && $pw_user_roles_exclude != '-1') {
    $sql_condition .= " AND (usermeta_role.meta_value NOT LIKE '%\"$pw_user_roles_exclude\"%' OR usermeta_role.meta_value IS NULL)";
}
```

---

### Phase 2: Add UI to Search Form

**File:** `includes/fetch_data_customer.php` (search_form section)

**Add after the date fields:**

```html
<div class="col-md-6">
    <div class="awr-form-title">
        <?php _e('Include User Role',__PW_REPORT_WCREPORT_TEXTDOMAIN__);?>
    </div>
    <select name="pw_user_roles" class="pw_user_roles">
        <option value=""><?php _e('All Roles',__PW_REPORT_WCREPORT_TEXTDOMAIN__); ?></option>
        <?php wp_dropdown_roles(); ?>
    </select>
</div>

<div class="col-md-6">
    <div class="awr-form-title">
        <?php _e('Exclude User Role',__PW_REPORT_WCREPORT_TEXTDOMAIN__);?>
    </div>
    <select name="pw_user_roles_exclude" class="pw_user_roles_exclude">
        <option value=""><?php _e('None',__PW_REPORT_WCREPORT_TEXTDOMAIN__); ?></option>
        <?php wp_dropdown_roles(); ?>
    </select>
</div>
```

---

### Phase 3: Version Update

**File:** `main.php`

Update version constant from `7.0` to reflect the patch:
```php
define( "pw_reporte_wcreport_version", "7.0.1" );
```

**Add changelog entry:**
```
= 7.0.1 =
* Added user role filter to Customer Report
* Added ability to exclude specific user roles
* Added billing_company column to Customer Report
```

---

## Files to Modify

| File | Changes |
|------|---------|
| `includes/fetch_data_customer.php` | Add SQL JOIN, WHERE conditions, UI form fields |
| `main.php` | Update version number |

---

## Testing Checklist

- [ ] Test with "Include Role" filter - only shows selected role
- [ ] Test with "Exclude Role" filter - hides selected role  
- [ ] Test with both filters - proper AND logic
- [ ] Test with no filters - shows all (backward compatible)
- [ ] Test date range + role filters together
- [ ] Verify SQL works with existing orders (no customer user)

---

## Implementation Notes

### WordPress Role Storage
Roles are stored in `wp_usermeta` as serialized array:
- Key: `wp_capabilities`  
- Value: `a:1:{s:6:"customer";b:1;}` (for customer role)

### SQL Pattern for Role Matching
The LIKE pattern must account for serialized format:
```sql
usermeta.meta_value LIKE '%"customer"%'
```

### Order without User
Some orders may not have a registered customer (guest checkout). The SQL should handle NULL user gracefully with `LEFT JOIN`.
