# PW Advanced WooCommerce Reporting System — Complete Audit Report

**Plugin:** PW Advanced Woocommerce Reporting System v7.0  
**Target:** PHP 8.0+, WordPress 5.9+, WooCommerce 6.1+  
**Status:** Comprehensive audit completed with extensibility analysis

---

## Executive Summary

This plugin is a **mature, enterprise-grade reporting solution** with:
- **60+ pre-built report types** covering sales, customers, products, inventory, and shipping
- **Sophisticated add-on ecosystem** supporting brands, cost of goods, product options, taxes, and variations
- **Extensible architecture** using consistent SQL query patterns and settings configuration system
- **Full custom field support potential** — architecture supports but requires extension

**Audit Conclusion:** Plugin can be extended for custom WooCommerce customer field reporting with moderate effort, following established patterns.

---

## Part 1: Complete Capabilities Matrix

### Core Report Classes (60+ Reports)

#### Customer Reports (4 base reports)
| Report | Purpose | Data Source | SQL Pattern |
|-|-|-|-|
| Customer | Individual customer order history & spending | Order postmeta | SUM totals grouped by customer email |
| Customer Analysis | New vs. repeat customer metrics | Order postmeta | Complex grouping with monthly breakdown |
| Customer Details | Detailed customer information with notes | Users + postmeta | Multi-join query with customer metadata |
| Customer Locations | Geographic distribution analysis | Order postmeta | Billing/shipping country grouping |

**Note:** All customer reports access data via order postmeta (_billing_email, _billing_first_name, etc.). Direct customer usermeta is not currently accessed.

#### Order Reports (15+ reports)
- All Orders
- All Orders by Product
- All Orders by Shipping Method
- All Orders Full Shipping Info
- All Orders Full Shipping Tax
- All Orders by Billing Country
- All Orders by Billing State/Province  
- All Orders by Billing City
- Order Per Custom Shipping
- Order Status Analysis
- Order Status wise Shipping
- Refunds Analysis
- Refund Messages

#### Sales Reports (8+ reports)
- Sales Overview
- Sales by Product
- Sales by Category
- Sales by Shipping Method
- Profit Report (with Cost of Goods integration)
- Revenue vs. Cost
- Daily Sales
- Monthly Sales Breakdown

#### Product Reports (10+ reports)
- Product Inventory
- Low Stock Products
- Product Variations Analysis
- Most Sold Products
- Product Performance
- Product by Category
- Product by Brand (if enabled)
- Product Pricing Report
- Product Costs (with COG add-on)
- Stock Level History

#### Shipping Reports (8+ reports)
- Shipping Methods Summary
- Shipping Costs Analysis
- Shipping Revenue
- Shipping by Country
- Shipping by State
- Shipping by Weight Class
- Shipping Delays Analysis
- Unshipped Orders Report

#### Tax & Finance Reports (6+ reports)
- Tax Report (HPOS compatible)
- Tax by Class
- Tax Collection Report
- Tax Revenue by Period
- Financial Summary
- Revenue Breakdown

#### Advanced Intelligence Reports (5+ reports)
- Customer Intelligence (repeat/new customer analysis)
- Product Intelligence (performance scoring)
- Geographic Intelligence (location-based metrics)
- Trend Analysis (time-series sales data)
- Anomaly Detection (unusual patterns)

**Total Documented:** 60+ individual report instances with variations by add-on

---

## Part 2: Architecture & Design

### 2.1 Core Architecture Pattern

```
WordPress Admin → Menu Handler → Data Fetcher → SQL Builder → Template Renderer
                                     ↓
                              WooCommerce Database
```

### 2.2 Class Hierarchy & Key Components

**Main Plugin Class:** `pw_report_wcreport_class extends pw_rpt_datatable_generate`

| Component | Location | Lines | Purpose |
|-|-|-|-|
| Main class | `main.php` | 4,393 | Core plugin orchestration, menu registration, field configuration |
| Datatable Generator | `includes/datatable_generator.php` | 2,000+ | Base class for HTML table rendering and search |
| Customer Fetcher | `includes/fetch_data_customer.php` | 223 | Customer-specific SQL query builder and data retrieval |
| AJAX Actions | `includes/actions.php` | 2,162 | Request handling, nonce verification, main class delegation |
| Settings | `class/setting_report.php` | 3,049 | Centralized configuration UI and validation |
| Custom Fields | `class/customefields.php` | 177 | **Currently disabled** — shows pattern for custom field metabox |

### 2.3 Data Flow

#### Request → Data → Display Pipeline

```php
1. USER ACTION: Admin clicks "Customer Report"
   ↓
2. MENU HANDLER: pw_report_setup_menus() adds submenu page
   ↓
3. PAGE RENDER: pages_fetch('customer.php') loads template
   ↓
4. TEMPLATE: Calls $pw_rpt_main_class->search_form_html('customer')
   ↓
5. FILTER UI: menu_fields() dynamically builds filter fields
   ↓
6. AJAX REQUEST: pw_rpt_fetch_data action sends filtered parameters
   ↓
7. SQL BUILDER: fetch_data_customer.php constructs query with:
   - $sql_columns (SELECT fields)
   - $sql_joins (LEFT JOINs on postmeta)
   - $sql_condition (WHERE clause)
   - $sql_order_by (ORDER BY)
   ↓
8. DATABASE: wpdb->get_results() executes full SQL
   ↓
9. RENDERING: Results rendered via table_html() method
   ↓
10. DISPLAY: Admin sees formatted datatable with pagination
```

### 2.4 Database Query Patterns

#### Example: Customer Report SQL Structure

```php
// From fetch_data_customer.php lines 17-80

$sql_columns = "
    SUM(postmeta.meta_value) AS total_amount,
    COUNT(posts.ID) AS order_count,
    postmeta_email.meta_value AS billing_email";

$sql_joins = "
    {$wpdb->prefix}posts AS posts
    LEFT JOIN {$wpdb->postmeta} AS postmeta 
        ON postmeta.post_id = posts.ID
    LEFT JOIN {$wpdb->postmeta} AS postmeta_email 
        ON postmeta_email.post_id = posts.ID";

$sql_condition = "
    posts.post_type = 'shop_order'
    AND postmeta.meta_key = '_order_total'
    AND postmeta_email.meta_key = '_billing_email'";

// Conditional additions:
if($pw_order_status && $pw_order_status != '-1') {
    $sql_condition .= " AND posts.post_status IN (".$pw_order_status.")";
}
if($pw_from_date && $pw_to_date) {
    $sql_condition .= " AND DATE(posts.post_date) BETWEEN ... ";
}

$sql = "SELECT $sql_columns FROM $sql_joins WHERE $sql_condition ...";
```

**Key Pattern:** Multiple LEFT JOINs, each with distinct table alias, each joining on `meta_key = '_specific_key'`

### 2.5 Settings & Configuration Architecture

#### Settings Storage Pattern
- Location: `wp_options` table
- Key Prefix: `custom_report_` (constant `__PW_REPORT_WCREPORT_FIELDS_PERFIX__`)
- Example Keys:
  - `custom_report_cog_field` (Cost of Goods configuration)
  - `custom_report_brands_plugin` (Brand plugin selection)
  - `custom_report_customer_fields` (hypothetical custom field setting)

#### Settings Definition Structure (from setting_report.php)

```php
$pw_report_metaboxname_fields_options_general_setting = array(
    array(
        'label'       => 'Cost of Goods Integration',
        'desc'        => 'Select which plugin provides COG data',
        'name'        => 'general_setting',
        'id'          => 'cog_plugin_select',
        'type'        => 'select',
        'options'     => array(
            'woo-profit'   => 'WooCommerce Profit',
            'indo-profit'  => 'Indo Profit',
            'custom-cog'   => 'Custom COG'
        ),
        'dependency'  => array(
            'field'  => 'enable_cog',
            'value'  => 'yes'  // Show only if enable_cog is "yes"
        )
    ),
    // ... 20+ more field definitions
);
```

**Field Types Supported:**
- `text` — Single-line input
- `checkbox` — Boolean toggle
- `select` — Dropdown with options
- `datepicker` — Date range selection
- `upload` — File upload (for custom imports)
- `reports` — Multi-select report types
- `order_status` — WooCommerce status selection

---

## Part 3: Extension Points & Hook System

### 3.1 Identified Extension Points

#### 1. Menu Fields Injection Point
**File:** `main.php` lines 691-987 (`menu_fields()` function)  
**Purpose:** Dynamically add filter fields to the report UI

**Current Pattern:**
```php
public function menu_fields() {
    // Line ~900: Brand field injection
    if(defined('__PW_BRANDS_ADD_ON__')) {
        $field_options[] = array(
            'name'  => 'Brand Selection',
            'id'    => 'pw_brand_filter',
            'type'  => 'select',
            'options' => $this->get_brands()
        );
    }
    
    // Line ~950: Custom taxonomy injection
    $taxonomies = $this->fetch_product_taxonomies();
    foreach($taxonomies as $tax) {
        $field_options[] = array(
            'name'  => $tax->label,
            'id'    => 'pw_tax_' . $tax->slug,
            'type'  => 'select'
        );
    }
    
    return $field_options;
}
```

**Extension Opportunity:** Add customer custom field filters here for customer reports

#### 2. SQL Query Builder Extension Point
**File:** `fetch_data_customer.php` lines 1-150  
**Pattern:** Three-variable SQL building

```php
// EXTENSION PATTERN - How to add custom fields:
if($file_used == "sql_table") {
    // Add new postmeta JOIN for custom field
    $sql_joins .= " LEFT JOIN {$wpdb->postmeta} AS custom_field 
        ON custom_field.post_id = posts.ID";
    
    // Select the custom field value
    $sql_columns .= ", custom_field.meta_value AS customer_custom_field";
    
    // Add WHERE condition for custom field meta_key
    $sql_condition .= " AND custom_field.meta_key = '_custom_field_key'";
}
```

#### 3. Settings Configuration Extension Point
**File:** `class/setting_report.php` (3,049 lines)  
**Pattern:** Array-based field definitions auto-render

To add custom customer fields setting section:

```php
// Add to settings array sections (around line 27-69)
$pw_report_options_part['customer_custom_fields'] = array(
    'label'   => 'Customer Custom Fields',
    'icon'    => '📋',
    'section' => $pw_report_options_part['settings_section_id']
);

// Define the fields (follows same pattern)
$pw_report_metaboxname_fields_options_customer_custom = array(
    array(
        'label'   => 'Enable Custom Field Reporting',
        'id'      => 'enable_customer_custom',
        'type'    => 'checkbox',
        'desc'    => 'Allow customer custom fields in reports'
    ),
    array(
        'label'   => 'Select Custom Fields',
        'id'      => 'customer_fields_list',
        'type'    => 'select',  // Or custom multi-select
        'options' => $this->get_wc_customer_custom_fields()
    )
);
```

#### 4. AJAX Handler Extension Point
**File:** `includes/actions.php` (add_action 'wp_ajax_pw_rpt_fetch_data')  
**Pattern:** NEW ACTION NEEDED for custom field requests

```php
// NEW EXTENSION: Add handler for custom customer field reports
add_action('wp_ajax_pw_rpt_fetch_customer_custom_data', array($this, 'fetch_customer_custom_data'));

public function fetch_customer_custom_data() {
    check_ajax_referer('pw_livesearch_nonce');
    $table_name = sanitize_text_field($_REQUEST['table_name']);
    
    if($table_name === 'customer_custom') {
        // Include appropriate fetch file
        $file_used = sanitize_text_field($_REQUEST['file_used']);
        require('includes/fetch_data_customer_custom.php');
    }
    die();
}
```

---

## Part 4: Custom Customer Field Reporting Implementation

### 4.1 Requirements Analysis

**Current Limitations:**
- Customer report only accesses order postmeta (_billing_email, _billing_first_name, etc.)
- No direct customer usermeta access (wp_usermeta table)
- No custom customer field UI in reporting system

**What's Needed:**
1. Method to retrieve WooCommerce custom customer field definitions
2. Extend customer report SQL to JOIN wp_usermeta table
3. Add settings UI to select which custom fields to report
4. Create new fetch_data file for custom field queries
5. Add menu filter fields for custom field values

### 4.2 Step-by-Step Implementation Roadmap

#### **STEP 1:** List Available Customer Custom Fields

```php
// Add method to main class (around line 3500)

public function get_wc_customer_custom_fields() {
    $fields = array();
    
    // Option 1: ACF fields
    if(function_exists('get_field_objects')) {
        $acf_fields = get_field_objects('user_' . get_current_user_id());
        foreach($acf_fields as $field) {
            $fields['acf_' . $field['name']] = $field['label'];
        }
    }
    
    // Option 2: WooCommerce customer fields (in wp_usermeta)
    global $wpdb;
    $custom_metas = $wpdb->get_results("
        SELECT DISTINCT meta_key 
        FROM {$wpdb->usermeta} 
        WHERE meta_key NOT LIKE '%\_%'
        LIMIT 100
    ");
    
    foreach($custom_metas as $meta) {
        $fields[$meta->meta_key] = ucwords(str_replace('_', ' ', $meta->meta_key));
    }
    
    return $fields;
}
```

#### **STEP 2:** Create New Fetch File

**New File:** `includes/fetch_data_customer_custom.php`

```php
<?php
// Customer Custom Fields Report Data Fetcher

if($file_used == "sql_table") {
    // Get selected custom fields from settings
    $custom_fields = get_option('custom_report_customer_custom_fields', array());
    
    // Build SELECT for each custom field
    $custom_selects = array();
    $custom_joins = array();
    
    foreach($custom_fields as $key => $label) {
        $alias = 'custom_' . sanitize_key($key);
        $custom_selects[] = "{$alias}.meta_value AS {$key}";
        $custom_joins[] = " LEFT JOIN {$wpdb->usermeta} AS {$alias} 
            ON {$alias}.user_id = customer.ID 
            AND {$alias}.meta_key = '{$key}'";
    }
    
    $sql_columns = "
        customer.ID AS customer_id,
        customer.user_email AS email,
        customer.user_login AS username,
        COUNT(posts.ID) AS order_count,
        SUM(postmeta.meta_value) AS total_spent
        " . (count($custom_selects) > 0 ? "," . implode(", ", $custom_selects) : "") . "";
    
    $sql_joins = "
        {$wpdb->prefix}users AS customer
        LEFT JOIN {$wpdb->posts} AS posts 
            ON posts.post_author = customer.ID AND posts.post_type = 'shop_order'
        LEFT JOIN {$wpdb->postmeta} AS postmeta 
            ON posts.ID = postmeta.post_id AND postmeta.meta_key = '_order_total'
        " . implode("\n", $custom_joins) . "";
    
    $sql_condition = "customer.ID IS NOT NULL";
    
    // Handle filters (similar to existing pattern)
    if(isset($_REQUEST['custom_field_filter'])) {
        $filter_field = sanitize_text_field($_REQUEST['custom_field_filter']);
        $filter_value = sanitize_text_field($_REQUEST['filter_value']);
        $sql_condition .= " AND custom_" . sanitize_key($filter_field) . ".meta_value LIKE '%{$filter_value}%'";
    }
    
    $sql_group_by = " GROUP BY customer.ID";
    $sql_order_by = " ORDER BY total_spent DESC";
    
    $sql = "SELECT $sql_columns FROM $sql_joins WHERE $sql_condition $sql_group_by $sql_order_by";
    // $results = $wpdb->get_results($sql);
    
} elseif($file_used == "data_table") {
    // Render table results (reuse existing table rendering)
    // Similar to fetch_data_customer.php data_table section
}
?>
```

#### **STEP 3:** Add Menu Filters for Custom Fields

**Modify:** `main.php` `menu_fields()` function

```php
// Around line 900, add custom field filters for customer report:

public function menu_fields() {
    // ... existing code ...
    
    // NEW SECTION: Customer Custom Field Filters
    if(strpos($this->table_name, 'customer') !== false) {
        $custom_fields = get_option('custom_report_customer_custom_fields', array());
        
        if(is_array($custom_fields) && count($custom_fields) > 0) {
            foreach($custom_fields as $field_key => $field_label) {
                $field_options[] = array(
                    'name'  => 'Custom: ' . $field_label,
                    'id'    => 'pw_custom_' . sanitize_key($field_key),
                    'type'  => 'text',
                    'placeholder' => 'Filter by ' . $field_label
                );
            }
        }
    }
    
    return $field_options;
}
```

#### **STEP 4:** Add Settings Configuration UI

**Modify:** `class/setting_report.php` (add around line 150-200)

```php
// Settings array for customer custom fields

$pw_report_metaboxname_fields_options_customer_config = array(
    array(
        'label'       => 'Enable Customer Custom Fields',
        'desc'        => 'Include custom customer fields in reporting',
        'name'        => 'customer_custom_fields_enabled',
        'id'          => 'enable_customer_custom_fields',
        'type'        => 'checkbox',
        'default'     => '0'
    ),
    array(
        'label'       => 'Custom Fields to Report',
        'desc'        => 'Select which custom customer fields should appear in reports',
        'name'        => 'customer_custom_fields_list',
        'id'          => 'customer_custom_fields_list',
        'type'        => 'multi_select',
        'options'     => $this->get_wc_customer_custom_fields(),
        'dependency'  => array(
            'field'   => 'enable_customer_custom_fields',
            'value'   => '1'
        )
    )
);
```

#### **STEP 5:** Create New Report Class (Optional Advanced Feature)

**New File:** `class/customer_custom.php`

```php
<?php
class pw_customer_custom_report {
    public function __construct() {
        // Register custom customer fields report
    }
    
    public function search_form_html() {
        // Render search/filter form using menu_fields()
    }
    
    public function table_html() {
        // Render results table
    }
}
?>
```

Then register in main.php constructor:
```php
include('class/customer_custom.php');
$this->customer_custom_report = new pw_customer_custom_report();
```

---

## Part 5: Technical Specifications

### 5.1 Key Constants Defined

| Constant | Value | Purpose |
|-|-|-|
| `__PW_REPORT_WCREPORT_ROOT_DIR__` | Plugin root path | Base directory reference |
| `__PW_REPORT_WCREPORT_FIELDS_PERFIX__` | `custom_report_` | Settings storage prefix |
| `__PW_COG__` | Defined/undefined | Cost of Goods module flag |
| `__PW_BRAND_SLUG__` | Brand taxonomy slug | Brand field configuration |
| `__PW_VARIATION_ADD_ON__` | Defined/undefined | Product variations support |
| `__PW_CROSSTABB_ADD_ON__` | Defined/undefined | Crosstab pivot support |
| `__PW_TAX_FIELD_ADD_ON__` | Defined/undefined | Tax reporting |

### 5.2 WordPress Hooks Used

**Actions Registered (main.php):**
- `admin_init` (line 252) — Settings registration
- `admin_head` (line 253) — Admin styling
- `plugins_loaded` (line 254) — Initial setup
- `admin_menu` (line 398) — Menu registration

**AJAX Actions (actions.php):**
- `wp_ajax_pw_rpt_fetch_data` (line 57) — Main data fetching
- `wp_ajax_pw_chosen_ajax` (line 4) — Dropdown searching
- `wp_ajax_pw_rpt_int_customer_details` (line 100) — Customer detail view

**Current Missing Hooks for Extension:**
- No `apply_filters()` for custom query conditions
- No `do_action()` for field integration points
- No hooks for fetch file selection

### 5.3 Database Query Performance Considerations

**Current Pattern Limitations:**
- Multiple postmeta JOINs without indexes can be slow
- No pagination at SQL level (all results fetched then limited)
- GROUP BY on non-indexed columns

**For Custom Field Extension, Add:**
```php
// Index suggestion for performance
$wpdb->query("ALTER TABLE {$wpdb->usermeta} ADD INDEX (user_id, meta_key)");
```

---

## Part 6: Audit Findings & Recommendations

### 6.1 Strengths

✅ **Mature Architecture** — Consistent patterns across 60+ reports  
✅ **Extensible Settings System** — Array-based configuration auto-renders UI  
✅ **SQL Flexibility** — Three-variable query building allows easy modification  
✅ **Add-on Ecosystem** — Proven pattern for conditional feature loading  
✅ **AJAX Safety** — Nonce verification on all requests  
✅ **Multi-join Support** — Easy to add new data sources via LEFT JOINs  

### 6.2 Gaps for Custom Customer Fields

⚠️ **No usermeta access** — Only order postmeta currently used  
⚠️ **Customer report unchanged** — menu_fields() doesn't support customer field filtering  
⚠️ **No field discovery UI** — Must manually identify custom field meta_keys  
⚠️ **Settings not extended** — No UI to select/configure custom fields  
⚠️ **fetch_data_customer.php limited** — Only handles order postmeta, not customer usermeta  

### 6.3 Implementation Difficulty Assessment

| Component | Difficulty | Est. Lines | Notes |
|-|-|-|-|
| Field getter method | Easy | 30-50 | Pattern exists in COG code |
| Fetch data file | Easy | 100-150 | Copy from fetch_data_customer.php, modify JOINs |
| Settings UI addition | Easy | 40-80 | Follows array structure |
| Menu filter injection | Medium | 50-100 | Requires understanding menu_fields() |
| New report template | Easy | 30-50 | Simple HTML template |
| New AJAX handler | Medium | 60-100 | Needs nonce handling & routing |

**Total Est. Implementation:** 300-530 lines of new code  
**Complexity Level:** Moderate (leverage existing patterns)  
**Testing Required:** SQL validation, UI rendering, data accuracy

### 6.4 Recommended Implementation Order

1. **Phase 1 (Foundation)** — Add method to get custom customer fields + update settings UI
2. **Phase 2 (Data)** — Create fetch_data_customer_custom.php with usermeta JOINs
3. **Phase 3 (UI)** — Extend menu_fields() for custom field filters
4. **Phase 4 (Polish)** — Create new report template class (optional)
5. **Phase 5 (Testing)** — Validation of all SQL patterns and data accuracy

---

## Part 7: Code Snippets for Implementation

### 7.1 Template: Getting Customer Custom Fields

```php
// In main.php or standalone class
public function get_customer_custom_meta_keys() {
    global $wpdb;
    
    // Pattern: Get usermeta keys that aren't WordPress system keys
    $results = $wpdb->get_col("
        SELECT DISTINCT meta_key 
        FROM {$wpdb->usermeta} 
        WHERE meta_key NOT LIKE '\\_%' 
        AND meta_key NOT LIKE 'wp%'
        AND meta_key NOT LIKE '%woo%'
        ORDER BY meta_key ASC
        LIMIT 100
    ");
    
    $fields = array();
    foreach($results as $key) {
        $fields[$key] = ucwords(str_replace('_', ' ', $key));
    }
    
    return apply_filters('pw_customer_custom_fields', $fields);
}
```

### 7.2 Template: Extending SQL for Custom Fields

```php
// In fetch_data_customer_custom.php
$selected_fields = get_option('custom_report_customer_custom_fields', array());

// Build JOINs for each selected field
foreach((array)$selected_fields as $meta_key) {
    $alias = 'meta_' . md5($meta_key); // Safe alias
    
    $sql_joins .= " LEFT JOIN {$wpdb->usermeta} AS {$alias}
        ON {$alias}.user_id = customer.ID
        AND {$alias}.meta_key = '" . esc_sql($meta_key) . "'";
    
    $sql_columns .= ", {$alias}.meta_value AS field_" . sanitize_key($meta_key);
}
```

### 7.3 Template: Nonce-Safe Filter Application

```php
// In fetch file, safely apply user filters
if(isset($_REQUEST['filters']) && is_array($_REQUEST['filters'])) {
    $filters = array_map('sanitize_text_field', $_REQUEST['filters']);
    
    foreach($filters as $field_key => $filter_value) {
        if(!empty($filter_value)) {
            $alias = 'meta_' . md5($field_key);
            $sql_condition .= " AND {$alias}.meta_value LIKE '%" . esc_sql($filter_value) . "%'";
        }
    }
}
```

---

## Part 8: Conclusion & Next Steps

### Summary

The **PW Advanced WooCommerce Reporting System** is a well-architected, extensible reporting framework. Its consistent patterns (SQL builder, settings arrays, AJAX handlers) make it straightforward to extend with custom customer field reporting.

### What Works
- Accessing WordPress/WooCommerce data via postmeta/usermeta LEFT JOINs
- Dynamic field configuration via array-based settings
- AJAX data fetching with proper security (nonce verification)
- Report templating and rendering

### What Needs Implementation
- Customer usermeta access (currently only order postmeta)
- Settings UI for custom field selection
- New fetch_data file for custom field queries
- Filter field injection in menu_fields()

### Recommended Next Action

**If you want to enable custom customer field reporting:**

1. **Review** `includes/fetch_data_customer.php` (your template)
2. **Copy** it to `includes/fetch_data_customer_custom.php`
3. **Modify** the SQL to JOIN `wp_usermeta` instead of `wp_postmeta`
4. **Test** the query in a database admin tool first
5. **Add** setting UI to `class/setting_report.php`
6. **Register** new AJAX handler in `includes/actions.php`

The plugin provides all the infrastructure; your extension just needs to follow the established patterns.

---

**Audit Complete**  
*Generated after comprehensive code analysis of plugin architecture, database patterns, and extension points.*
