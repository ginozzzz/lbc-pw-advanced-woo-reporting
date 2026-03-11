<?php

if($file_used=="sql_table")
{
    //GET POSTED PARAMETERS
    $request 			= array();
    $start				= 0;
    $pw_from_date		  = $this->pw_get_woo_requests('pw_from_date',NULL,true);
    $pw_to_date			= $this->pw_get_woo_requests('pw_to_date',NULL,true);
    $date_format = $this->pw_date_format($pw_from_date);

    $pw_id_order_status 	= $this->pw_get_woo_requests('pw_id_order_status',NULL,true);
    $pw_order_status		= $this->pw_get_woo_requests('pw_orders_status','-1',true);
    $pw_order_status  		= "'".str_replace(",","','",$pw_order_status)."'";

    //User Role Filter
    $pw_user_roles			= $this->pw_get_woo_requests('pw_user_roles',NULL,true);
    $pw_user_roles_exclude	= $this->pw_get_woo_requests('pw_user_roles_exclude',NULL,true);

    ///////////HIDDEN FIELDS////////////
    $pw_hide_os		= $this->pw_get_woo_requests('pw_hide_os','-1',true);
    $pw_publish_order='no';
    $data_format=$this->pw_get_woo_requests_links('date_format',get_option('date_format'),true);
    //////////////////////


    //ORDER SATTUS
    $pw_id_order_status_join='';
    $pw_order_status_condition='';

    //ORDER STATUS
    $pw_id_order_status_condition='';

    //DATE
    $pw_from_date_condition='';

    //PUBLISH ORDER
    $pw_publish_order_condition='';

    //HIDE ORDER STATUS
    $pw_hide_os_condition ='';

    $sql_columns= "
		SUM(pw_postmeta1.meta_value) AS 'total_amount'
		,pw_postmeta2.meta_value AS 'billing_email'
		,pw_postmeta3.meta_value AS 'billing_first_name'
		,pw_postmeta5.meta_value AS 'billing_company'
		,Count(pw_postmeta2.meta_value) AS 'order_count'
		,pw_postmeta4.meta_value AS customer_id";

    $sql_joins = "{$wpdb->prefix}posts as pw_posts
		LEFT JOIN  {$wpdb->prefix}postmeta as pw_postmeta1 ON pw_postmeta1.post_id=pw_posts.ID
		LEFT JOIN  {$wpdb->prefix}postmeta as pw_postmeta2 ON pw_postmeta2.post_id=pw_posts.ID
		LEFT JOIN  {$wpdb->prefix}postmeta as pw_postmeta3 ON pw_postmeta3.post_id=pw_posts.ID
		LEFT JOIN  {$wpdb->prefix}postmeta as pw_postmeta5 ON pw_postmeta5.post_id=pw_posts.ID
		LEFT JOIN  {$wpdb->prefix}postmeta as pw_postmeta4 ON pw_postmeta4.post_id=pw_posts.ID
		LEFT JOIN  {$wpdb->prefix}usermeta as usermeta_role ON usermeta_role.user_id = pw_postmeta4.meta_value AND usermeta_role.meta_key = '{$wpdb->prefix}capabilities'";

    if(strlen($pw_id_order_status)>0 && $pw_id_order_status != "-1" && $pw_id_order_status != "no" && $pw_id_order_status != "all"){
        $pw_id_order_status_join= "
				LEFT JOIN  {$wpdb->prefix}term_relationships 	as pw_term_relationships 	ON pw_term_relationships.object_id		=	pw_posts.ID
				LEFT JOIN  {$wpdb->prefix}term_taxonomy 		as term_taxonomy 		ON term_taxonomy.term_taxonomy_id	=	pw_term_relationships.term_taxonomy_id";
    }
    $sql_condition = "
		pw_posts.post_type='shop_order'
		AND pw_postmeta1.meta_key='_order_total'
		AND pw_postmeta2.meta_key='_billing_email'
		AND pw_postmeta3.meta_key='_billing_first_name'
		AND pw_postmeta5.meta_key='_billing_company'
		AND pw_postmeta4.meta_key='_customer_user'
		";

	//User Role Include Filter (supports comma-separated multiple roles)
	if($pw_user_roles!=NULL && $pw_user_roles!='-1' && $pw_user_roles!=''){
		$include_roles = array_filter(array_map('trim', explode(',', $pw_user_roles)));
		if(!empty($include_roles)){
			$include_parts = array_map(function($r){ return "usermeta_role.meta_value LIKE '%\"" . esc_sql($r) . "\"%'"; }, $include_roles);
			$sql_condition .= " AND (" . implode(' OR ', $include_parts) . ")";
		}
	}

	//User Role Exclude Filter (supports comma-separated multiple roles)
	if($pw_user_roles_exclude!=NULL && $pw_user_roles_exclude!='-1' && $pw_user_roles_exclude!=''){
		$exclude_roles = array_filter(array_map('trim', explode(',', $pw_user_roles_exclude)));
		if(!empty($exclude_roles)){
			$exclude_parts = array_map(function($r){ return "usermeta_role.meta_value NOT LIKE '%\"" . esc_sql($r) . "\"%'"; }, $exclude_roles);
			$sql_condition .= " AND (" . implode(' AND ', $exclude_parts) . " OR usermeta_role.meta_value IS NULL)";
		}
	}

    if(strlen($pw_id_order_status)>0 && $pw_id_order_status != "-1" && $pw_id_order_status != "no" && $pw_id_order_status != "all"){
        $pw_id_order_status_condition = " AND  term_taxonomy.term_id IN ({$pw_id_order_status})";
    }

    if ($pw_from_date != NULL &&  $pw_to_date !=NULL){
        $pw_from_date_condition= " AND DATE(pw_posts.post_date) BETWEEN STR_TO_DATE('" . $pw_from_date . "', '$date_format') and STR_TO_DATE('" . $pw_to_date . "', '$date_format')";
    }
    if(strlen($pw_publish_order)>0 && $pw_publish_order != "-1" && $pw_publish_order != "no" && $pw_publish_order != "all"){
        $in_post_status		= str_replace(",","','",$pw_publish_order);
        $pw_publish_order_condition= " AND  pw_posts.post_status IN ('{$in_post_status}')";
    }


    if($pw_order_status  && $pw_order_status != '-1' and $pw_order_status != "'-1'")
        $pw_order_status_condition= " AND pw_posts.post_status IN (".$pw_order_status.")";

    if($pw_hide_os  && $pw_hide_os != '-1' and $pw_hide_os != "'-1'")
        $pw_hide_os_condition= " AND pw_posts.post_status NOT IN ('".$pw_hide_os."')";

    $sql_group_by= "  GROUP BY  pw_postmeta2.meta_value";
    $sql_order_by="Order By total_amount DESC";

    $sql = "SELECT $sql_columns FROM $sql_joins $pw_id_order_status_join WHERE $sql_condition
				$pw_id_order_status_condition $pw_from_date_condition $pw_publish_order_condition
				$pw_order_status_condition $pw_hide_os_condition
				$sql_group_by $sql_order_by
				";

}elseif($file_used=="data_table"){


    ////ADDE IN VER4.0
    /// TOTAL ROWS VARIABLES
    $result_count=$order_count=$total_amnt=0;

    foreach($this->results as $items){
        $index_cols=0;
        //for($i=1; $i<=20 ; $i++){

        ////ADDE IN VER4.0
        /// TOTAL ROWS
        $result_count++;

        $datatable_value.=("<tr>");

        //Billing First Name
        $display_class='';
        if($this->table_cols[$index_cols++]['status']=='hide') $display_class='display:none';
        $datatable_value.=("<td style='".$display_class."'>");
        $datatable_value.= $items->billing_first_name;
        $datatable_value.=("</td>");

        //Billing Company
        $display_class='';
        if($this->table_cols[$index_cols++]['status']=='hide') $display_class='display:none';
        $datatable_value.=("<td style='".$display_class."'>");
        $datatable_value.= $items->billing_company;
        $datatable_value.=("</td>");

        //Billing Last Name
        $display_class='';
        if($this->table_cols[$index_cols++]['status']=='hide') $display_class='display:none';
        $datatable_value.=("<td style='".$display_class."'>");
        $datatable_value.= get_user_meta( $items->customer_id, 'last_name', true );
        $datatable_value.=("</td>");

        //Billing Email
        $display_class='';
        if($this->table_cols[$index_cols++]['status']=='hide') $display_class='display:none';
        $datatable_value.=("<td style='".$display_class."'>");
        $datatable_value.= $this->pw_email_link_format($items->billing_email,false);
        $datatable_value.=("</td>");

        //Order Count
        $display_class='';
        if($this->table_cols[$index_cols++]['status']=='hide') $display_class='display:none';
        $datatable_value.=("<td style='".$display_class."'>");
        $datatable_value.= $items->order_count;

        ////ADDE IN VER4.0
        /// TOTAL ROWS
        $order_count+= $items->order_count;
        $datatable_value.=("</td>");

        //Amount
        $display_class='';
        if($this->table_cols[$index_cols++]['status']=='hide') $display_class='display:none';
        $datatable_value.=("<td style='".$display_class."'>");
        $datatable_value.= $items->total_amount == 0 ? $this->price(0) : $this->price($items->total_amount);

        ////ADDE IN VER4.0
        /// TOTAL ROWS
        $total_amnt+= $items->total_amount;
        $datatable_value.=("</td>");

        $datatable_value.=("</tr>");
    }

    ////ADDE IN VER4.0
    /// TOTAL ROWS
    $table_name_total= $table_name;
    $this->table_cols_total = $this->table_columns_total( $table_name_total );
    $datatable_value_total='';

    $datatable_value_total.=("<tr>");
    $datatable_value_total.="<td>$result_count</td>";
    $datatable_value_total.="<td>$order_count</td>";
    $datatable_value_total.="<td>".(($total_amnt) == 0 ? $this->price(0) : $this->price($total_amnt))."</td>";
    $datatable_value_total.=("</tr>");

}elseif($file_used=="search_form"){
    ?>
    <form class='alldetails search_form_report' action='' method='post'>
        <input type='hidden' name='action' value='submit-form' />
        <div class="row">

            <div class="col-md-6">
                <div class="awr-form-title">
                    <?php _e('Quick Select Date Range',__PW_REPORT_WCREPORT_TEXTDOMAIN__);?>
                </div>
                <select id="pw_date_preset" class="pw_date_preset">
                    <option value="custom"><?php _e('Custom Date Range',__PW_REPORT_WCREPORT_TEXTDOMAIN__); ?></option>
                    <option value="last7days"><?php _e('Last 7 Days',__PW_REPORT_WCREPORT_TEXTDOMAIN__); ?></option>
                    <option value="last30days"><?php _e('Last 30 Days',__PW_REPORT_WCREPORT_TEXTDOMAIN__); ?></option>
                    <option value="last90days"><?php _e('Last 90 Days',__PW_REPORT_WCREPORT_TEXTDOMAIN__); ?></option>
                    <option value="thismonth"><?php _e('This Month',__PW_REPORT_WCREPORT_TEXTDOMAIN__); ?></option>
                    <option value="lastmonth"><?php _e('Last Month',__PW_REPORT_WCREPORT_TEXTDOMAIN__); ?></option>
                    <option value="thisquarter"><?php _e('This Quarter',__PW_REPORT_WCREPORT_TEXTDOMAIN__); ?></option>
                    <option value="lastquarter"><?php _e('Last Quarter',__PW_REPORT_WCREPORT_TEXTDOMAIN__); ?></option>
                    <option value="ytd"><?php _e('Year to Date',__PW_REPORT_WCREPORT_TEXTDOMAIN__); ?></option>
                    <option value="last12months"><?php _e('Last 12 Months',__PW_REPORT_WCREPORT_TEXTDOMAIN__); ?></option>
                </select>
            </div>

        </div>

        <div class="row">

            <div class="col-md-6">
                <div class="awr-form-title">
                    <?php _e('From Date',__PW_REPORT_WCREPORT_TEXTDOMAIN__);?>
                </div>
                <span class="awr-form-icon"><i class="fa fa-calendar"></i></span>
                <input name="pw_from_date" id="pwr_from_date" type="text" readonly='true' class="datepick"/>
            </div>

            <div class="col-md-6">
                <div class="awr-form-title">
                    <?php _e('To Date',__PW_REPORT_WCREPORT_TEXTDOMAIN__);?>
                </div>
                <span class="awr-form-icon"><i class="fa fa-calendar"></i></span>
                <input name="pw_to_date" id="pwr_to_date" type="text" readonly='true' class="datepick"/>

                <input type="hidden" name="pw_id_order_status[]" id="pw_id_order_status" value="-1">
                <input type="hidden" name="pw_orders_status[]" id="order_status" value="<?php echo $this->pw_shop_status; ?>">
            </div>

        </div>

        <div class="row">

            <div class="col-md-6">
                <div class="awr-form-title">
                    <?php _e('Include User Role',__PW_REPORT_WCREPORT_TEXTDOMAIN__);?>
                </div>
                <select name="pw_user_roles[]" class="pw_user_roles" multiple="multiple">
                    <?php wp_dropdown_roles(); ?>
                </select>
            </div>

            <div class="col-md-6">
                <div class="awr-form-title">
                    <?php _e('Exclude User Role',__PW_REPORT_WCREPORT_TEXTDOMAIN__);?>
                </div>
                <select name="pw_user_roles_exclude[]" class="pw_user_roles_exclude" multiple="multiple">
                    <?php wp_dropdown_roles(); ?>
                </select>
            </div>

        </div>

        <div class="col-md-12">

            <?php
            $pw_hide_os=$this->otder_status_hide;
            $pw_publish_order='no';
            $data_format=$this->pw_get_woo_requests_links('date_format',get_option('date_format'),true);
            ?>
            <input type="hidden" name="list_parent_category" value="">
            <input type="hidden" name="pw_category_id" value="-1">
            <input type="hidden" name="group_by_parent_cat" value="0">

            <input type="hidden" name="pw_hide_os" id="pw_hide_os" value="<?php echo $pw_hide_os;?>" />

            <input type="hidden" name="date_format" id="date_format" value="<?php echo $data_format;?>" />

            <input type="hidden" name="table_names" value="<?php echo $table_name;?>"/>
            <div class="fetch_form_loading search-form-loading"></div>
            <button type="submit" value="Search" class="button-primary"><i class="fa fa-search"></i> <span><?php echo esc_html__('Search',__PW_REPORT_WCREPORT_TEXTDOMAIN__); ?></span></button>
            <button type="button" value="Reset" class="button-secondary form_reset_btn"><i class="fa fa-reply"></i><span><?php echo esc_html__('Reset Form',__PW_REPORT_WCREPORT_TEXTDOMAIN__); ?></span></button>

        </div>

    </form>
    <script type="text/javascript">
        jQuery(document).ready(function($) {
            // Initialize Select2 for role dropdowns
            $('select.pw_user_roles').select2({
                placeholder: '<?php _e('Select roles to include (leave empty for all)', __PW_REPORT_WCREPORT_TEXTDOMAIN__); ?>',
                allowClear: true,
                width: '100%',
                closeOnSelect: false
            });
            
            $('select.pw_user_roles_exclude').select2({
                placeholder: '<?php _e('Select roles to exclude (leave empty for none)', __PW_REPORT_WCREPORT_TEXTDOMAIN__); ?>',
                allowClear: true,
                width: '100%',
                closeOnSelect: false
            });

            // Date preset functionality
            function setDateRange(fromDate, toDate) {
                $('#pwr_from_date').datepicker('setDate', fromDate);
                $('#pwr_to_date').datepicker('setDate', toDate);
            }

            $('#pw_date_preset').on('change', function() {
                var preset = $(this).val();
                var today = new Date();
                var fromDate, toDate;

                switch(preset) {
                    case 'last7days':
                        toDate = new Date();
                        fromDate = new Date(today.setDate(today.getDate() - 7));
                        break;
                    case 'last30days':
                        toDate = new Date();
                        fromDate = new Date(today.setDate(today.getDate() - 30));
                        break;
                    case 'last90days':
                        toDate = new Date();
                        fromDate = new Date(today.setDate(today.getDate() - 90));
                        break;
                    case 'thismonth':
                        fromDate = new Date(today.getFullYear(), today.getMonth(), 1);
                        toDate = new Date();
                        break;
                    case 'lastmonth':
                        fromDate = new Date(today.getFullYear(), today.getMonth() - 1, 1);
                        toDate = new Date(today.getFullYear(), today.getMonth(), 0);
                        break;
                    case 'thisquarter':
                        var quarter = Math.floor(today.getMonth() / 3);
                        fromDate = new Date(today.getFullYear(), quarter * 3, 1);
                        toDate = new Date();
                        break;
                    case 'lastquarter':
                        var quarter = Math.floor(today.getMonth() / 3);
                        if (quarter === 0) {
                            fromDate = new Date(today.getFullYear() - 1, 9, 1);
                            toDate = new Date(today.getFullYear() - 1, 11, 31);
                        } else {
                            fromDate = new Date(today.getFullYear(), (quarter - 1) * 3, 1);
                            toDate = new Date(today.getFullYear(), quarter * 3, 0);
                        }
                        break;
                    case 'ytd':
                        fromDate = new Date(today.getFullYear(), 0, 1);
                        toDate = new Date();
                        break;
                    case 'last12months':
                        toDate = new Date();
                        fromDate = new Date(today.setFullYear(today.getFullYear() - 1));
                        break;
                    case 'custom':
                        return;
                }

                if (fromDate && toDate) {
                    setDateRange(fromDate, toDate);
                }
            });
        });
    </script>
    <?php
}

?>
