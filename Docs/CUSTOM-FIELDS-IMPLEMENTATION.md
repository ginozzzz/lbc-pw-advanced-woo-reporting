# Custom Customer Fields Implementation Guide
*Quick-start for extending PW Advanced WooCommerce Reporting*

---

## Overview

This guide provides copy-paste ready code to add custom WooCommerce customer field reporting to the plugin.

**Time Estimate:** 2-4 hours  
**Files to Modify:** 3 files  
**Files to Create:** 1 new file  

---

## Quick Implementation Checklist

- [ ] **Step 1:** Add method to retrieve customer custom fields
- [ ] **Step 2:** Create new data fetcher for custom fields  
- [ ] **Step 3:** Add settings UI for field selection
- [ ] **Step 4:** Add AJAX handler for custom field reports
- [ ] **Step 5:** Test SQL query in database admin
- [ ] **Step 6:** Test UI in WordPress admin

---

## Step 1: Add Custom Field Discovery

**File to Modify:** `includes/PW-Advanced-Woocommerce-Reporting-System/main.php`

**Add this new method** after line 3500 (or anywhere in the class):

```php
/**
 * Get available WooCommerce customer custom fields from usermeta
 * 
 * @return array Key => Label pairs
 */
public function get_customer_custom_fields() {
    global $wpdb;
    
    $fields = array();
    
    // Get all non-system usermeta keys
    $query = $wpdb->prepare("
        SELECT DISTINCT meta_key 
        FROM {$wpdb->usermeta} 
        WHERE meta_key NOT LIKE %s 
            AND meta_key NOT LIKE %s
            AND meta_key NOT LIKE %s
            AND meta_key NOT LIKE %s
        ORDER BY meta_key ASC
        LIMIT 100
    ", '\\_%', 'wp\\_%', '%capabilities%', '%session%');
    
    $custom_keys = $wpdb->get_col($query);
    
    if($custom_keys) {
        foreach($custom_keys as $key) {
            // Create readable label
            $label = ucwords(str_replace(array('_', '-'), ' ', $key));
            $fields[$key] = $label;
        }
    }
    
    // Also check for ACF fields if ACF is active
    if(function_exists('get_option') && get_option('afc_installed')) {
        $acf_fields = get_option('acf_field_groups', array());
        if($acf_fields) {
            $fields['_acf_notice'] = '--- ACF Fields Below ---';
            // ACF field enumeration would go here
        }
    }
    
    return $fields;
}
```

**Location Tip:** Add right after the `get_capability()` method (around line 2100). It keeps similar utility methods together.

---

## Step 2: Create New Data Fetcher

**File to Create:** `includes/fetch_data_customer_custom.php`

Create this new file in the `includes/` folder with this content:

```php
<?php
/**
 * Customer Custom Fields Report Data Fetcher
 * Handles SQL building and result rendering for custom customer field reports
 */

if($file_used == "sql_table") {
    
    // Get configuration
    $custom_fields = get_option('custom_report_customer_custom_fields', array());
    $custom_fields = is_array($custom_fields) ? $custom_fields : array();
    
    if(empty($custom_fields)) {
        $custom_fields = array();
    }
    
    // Initialize arrays
    $custom_selects = array();
    $custom_joins = array();
    $join_counter = 0;
    
    // Build SELECT and JOIN for each selected custom field
    foreach($custom_fields as $meta_key) {
        $alias = 'cust_field_' . $join_counter;
        $join_counter++;
        
        // Add to SELECT
        $custom_selects[] = "{$alias}.meta_value AS custom_" . sanitize_key($meta_key);
        
        // Add to JOIN
        $custom_joins[] = " LEFT JOIN {$wpdb->usermeta} AS {$alias}
            ON {$alias}.user_id = users.ID
            AND {$alias}.meta_key = '" . esc_sql($meta_key) . "'";
    }
    
    // Build the query - Select clause
    $sql_columns = "
        users.ID AS customer_id,
        users.user_email AS billing_email,
        users.user_login AS username,
        COUNT(DISTINCT posts.ID) AS order_count,
        SUM(CAST(om.meta_value AS DECIMAL(10,2))) AS total_spent";
    
    if(!empty($custom_selects)) {
        $sql_columns .= ", " . implode(", ", $custom_selects);
    }
    
    // From clause
    $sql_joins = "
        {$wpdb->users} AS users
        LEFT JOIN {$wpdb->prefix}woocommerce_customer_lookup AS customer 
            ON customer.user_id = users.ID
        LEFT JOIN {$wpdb->posts} AS posts 
            ON posts.post_author = users.ID 
            AND posts.post_type = 'shop_order'
        LEFT JOIN {$wpdb->postmeta} AS om 
            ON om.post_id = posts.ID 
            AND om.meta_key = '_order_total'";
    
    if(!empty($custom_joins)) {
        $sql_joins .= implode("", $custom_joins);
    }
    
    // Where clause
    $sql_condition = " users.ID > 0";
    
    // Handle date filters if provided
    $pw_from_date = isset($_REQUEST['pw_from_date']) ? sanitize_text_field($_REQUEST['pw_from_date']) : null;
    $pw_to_date = isset($_REQUEST['pw_to_date']) ? sanitize_text_field($_REQUEST['pw_to_date']) : null;
    
    if($pw_from_date && $pw_to_date) {
        $date_format = $this->pw_date_format($pw_from_date);
        $sql_condition .= " AND DATE(posts.post_date) BETWEEN STR_TO_DATE('" . esc_sql($pw_from_date) . "', '$date_format') 
            AND STR_TO_DATE('" . esc_sql($pw_to_date) . "', '$date_format')";
    }
    
    // Handle custom field filters
    foreach($custom_fields as $field_key) {
        $filter_key = 'custom_' . sanitize_key($field_key);
        if(isset($_REQUEST[$filter_key]) && !empty($_REQUEST[$filter_key])) {
            $filter_value = sanitize_text_field($_REQUEST[$filter_key]);
            $alias = 'cust_field_' . array_search($field_key, $custom_fields);
            $sql_condition .= " AND {$alias}.meta_value LIKE '%" . esc_sql($filter_value) . "%'";
        }
    }
    
    // Build complete query
    $sql_group_by = " GROUP BY users.ID";
    $sql_order_by = " ORDER BY total_spent DESC LIMIT 0, 100";
    
    $sql = "SELECT $sql_columns FROM $sql_joins WHERE $sql_condition $sql_group_by $sql_order_by";
    
} elseif($file_used == "data_table") {
    
    // Render the results table (reuse existing table rendering)
    $columns = array(
        'customer_id' => 'ID',
        'billing_email' => 'Email',
        'username' => 'Username',
        'order_count' => 'Orders',
        'total_spent' => 'Total Spent'
    );
    
    // Add custom field columns
    $custom_fields = get_option('custom_report_customer_custom_fields', array());
    if($custom_fields) {
        foreach($custom_fields as $meta_key) {
            $columns['custom_' . sanitize_key($meta_key)] = ucwords(str_replace('_', ' ', $meta_key));
        }
    }
    
    // Output table header
    echo '<table class="wp-list-table widefat fixed">';
    echo '<thead><tr>';
    foreach($columns as $col_key => $col_label) {
        echo '<th>' . esc_html($col_label) . '</th>';
    }
    echo '</tr></thead>';
    
    // Output data rows
    echo '<tbody>';
    if($this->results) {
        foreach($this->results as $row) {
            echo '<tr>';
            foreach($columns as $col_key => $col_label) {
                $value = isset($row->$col_key) ? $row->$col_key : '';
                echo '<td>' . esc_html($value) . '</td>';
            }
            echo '</tr>';
        }
    } else {
        echo '<tr><td colspan="' . count($columns) . '">No results found</td></tr>';
    }
    echo '</tbody>';
    echo '</table>';
}
?>
```

**Important Notes:**
- Line 23: Adjust the field alias pattern if needed
- Line 62: Uses WooCommerce customer lookup table (HPOS-compatible)
- Line 100: Limit to first 100 results for now (add pagination later)

---

## Step 3: Add Settings UI

**File to Modify:** `class/setting_report.php`

**Find this section** (around line 27-69):
```php
$pw_report_options_part = array(
    'general_setting' => array(
        'label' => 'General Settings',
        ...
```

**Add this as a new array element** among the section definitions (before the closing bracket):

```php
    'customer_custom_fields' => array(
        'label'   => 'Customer Custom Fields',
        'icon'    => '🎯',
        'section' => 'customer'
    ),
```

**Next, find the field definitions section** (around line 90-250) and add this new array:

```php
// Customer Custom Fields Configuration
$pw_report_metaboxname_fields_options_customer_custom_fields = array(
    
    array(
        'label'       => 'Enable Custom Field Reporting',
        'desc'        => 'Allow customer custom fields to appear in reports',
        'name'        => 'customer_custom_fields',
        'id'          => 'customer_custom_fields_enabled',
        'type'        => 'checkbox',
        'default'     => '',
        'section'     => 'customer_custom_fields'
    ),
    
    array(
        'label'       => 'Select Custom Fields to Report',
        'desc'        => 'Choose which customer custom fields should be included',
        'name'        => 'customer_custom_fields',
        'id'          => 'customer_custom_fields_list',
        'type'        => 'select',
        'multiple'    => true,
        'options'     => $pw_report_wcreport_class->get_customer_custom_fields(),
        'dependency'  => array(
            'field'   => 'customer_custom_fields_enabled',
            'value'   => 'on'
        ),
        'section'     => 'customer_custom_fields'
    ),
    
    array(
        'label'       => 'Include in Customer Report',
        'desc'        => 'Add custom fields as columns to the main customer report',
        'name'        => 'customer_custom_fields',
        'id'          => 'customer_custom_fields_in_main_report',
        'type'        => 'checkbox',
        'default'     => 'on',
        'dependency'  => array(
            'field'   => 'customer_custom_fields_enabled',
            'value'   => 'on'
        ),
        'section'     => 'customer_custom_fields'
    )
);
```

---

## Step 4: Add AJAX Handler

**File to Modify:** `includes/actions.php`

**Find this section** (around line 57):
```php
add_action('wp_ajax_pw_rpt_fetch_data', array($this, 'pw_rpt_fetch_data'));
```

**Add this new action registration** right after it:

```php
add_action('wp_ajax_pw_rpt_fetch_customer_custom', array($this, 'pw_rpt_fetch_customer_custom'));
```

**Now add the handler method** to the class (add around line 800):

```php
/**
 * AJAX handler for customer custom fields report
 */
public function pw_rpt_fetch_customer_custom() {
    
    // Verify nonce
    if(!isset($_REQUEST['security']) || !wp_verify_nonce($_REQUEST['security'], 'pw_livesearch_nonce')) {
        wp_die('Security check failed');
    }
    
    // Check permissions
    if(!current_user_can('manage_woocommerce')) {
        wp_die('Insufficient permissions');
    }
    
    // Get request parameters
    $file_used = isset($_REQUEST['file_used']) ? sanitize_text_field($_REQUEST['file_used']) : 'data_table';
    
    // Check if custom fields are enabled
    $enabled = get_option('customer_custom_fields_enabled');
    if(empty($enabled)) {
        wp_die('Customer custom fields reporting is not enabled');
    }
    
    // Include the data fetcher
    $this->table_name = 'customer_custom';
    
    // Build query if sql_table mode
    if($file_used === 'sql_table') {
        ob_start();
        include('fetch_data_customer_custom.php');
        $this->results = $wpdb->get_results($sql);
        ob_end_clean();
    } elseif($file_used === 'data_table') {
        // Results already set, just render
        include('fetch_data_customer_custom.php');
    }
    
    die();
}
```

---

## Step 5: Test the SQL

**Before enabling the feature, test the query:**

1. Go to **Tools → phpMyAdmin** (or your database admin)
2. Select your WordPress database
3. Paste this query (replace `wp_` with your table prefix if different):

```sql
SELECT 
    users.ID AS customer_id,
    users.user_email AS billing_email,
    users.user_login AS username,
    COUNT(DISTINCT posts.ID) AS order_count,
    SUM(CAST(om.meta_value AS DECIMAL(10,2))) AS total_spent
FROM wp_users AS users
LEFT JOIN wp_posts AS posts 
    ON posts.post_author = users.ID 
    AND posts.post_type = 'shop_order'
LEFT JOIN wp_postmeta AS om 
    ON om.post_id = posts.ID 
    AND om.meta_key = '_order_total'
WHERE users.ID > 0
GROUP BY users.ID
LIMIT 20;
```

**Expected Result:** List of customers with their spending totals

---

## Step 6: Enable and Test

1. **Go to:** WordPress Admin → Reports → Settings → Customer Custom Fields
2. **Check:** "Enable Custom Field Reporting"
3. **Select:** The custom fields you want to include
4. **Save**
5. **Go to:** Reports → Customers
6. **Test:** The filters should now show your custom fields

---

## Troubleshooting

| Issue | Solution |
|-|-|
| No custom fields appearing | Run `get_customer_custom_fields()` to verify fields exist in database |
| Query errors | Check table prefix in SQL — should match your wp-config.php |
| No results loading | Verify AJAX nonce: Check `pw_livesearch_nonce` exists in page |
| Filters not working | Ensure custom field meta_keys in options match database exactly |

---

## Performance Tips

After confirming it works, optimize:

```sql
-- Add indexes to speed up usermeta queries
ALTER TABLE wp_usermeta ADD INDEX idx_user_meta (user_id, meta_key);
ALTER TABLE wp_postmeta ADD INDEX idx_post_meta (post_id, meta_key);
```

---

## Extending Further

**To add more custom field types:**

Modify `fetch_data_customer_custom.php` to handle:
- Date range filters
- Numeric comparisons (greater_than, less_than)
- Multiple value selection (checkboxes)
- Export to CSV

**To create a dedicated report page:**

Create `class/customer_custom.php` mirroring `class/customer.php` with:
```php
class pw_report_customer_custom_report {
    public function __construct($main_class) {
        add_submenu_page(
            'woocommerce',
            'Customer Custom Fields Report',
            'Customer Custom Fields',
            'manage_woocommerce',
            'pw_customer_custom_report',
            array($this, 'render_report')
        );
    }
    
    public function render_report() {
        // Render report using same patterns
    }
}
```

Then register in main.php constructor:
```php
require_once('class/customer_custom.php');
new pw_report_customer_custom_report($this);
```

---

## Support Reference

- **Plugin Documentation:** Check inside plugin's `docs/` folder
- **Schema Reference:** [WooCommerce Database Schema](https://woocommerce.com/documentation/plugins/woocommerce/)
- **WordPress Hooks:** [Plugin API Hooks](https://developer.wordpress.org/plugins/hooks/)

**Done!** You now have custom customer field reporting enabled.
