<?php
	if($file_used=="sql_table")
	{

		//GET POSTED PARAMETERS
		$pw_sort_by 			= $this->pw_get_woo_requests('sort_by','product_name',true);
		$pw_order_by 			= $this->pw_get_woo_requests('order_by','DESC',true);
		$group_by 			= $this->pw_get_woo_requests('pw_groupby','variation_id',true);

		$pw_paid_customer		= $this->pw_get_woo_requests('pw_customers_paid',"-1",true);

		if($pw_paid_customer != NULL  && $pw_paid_customer != '-1')
		{
			$pw_paid_customer = "'".str_replace(",", "','",$pw_paid_customer)."'";
		}

		$pw_billing_post_code	= $this->pw_get_woo_requests('pw_bill_post_code',"-1",true);

		$pw_product_sku 		= $this->pw_get_woo_requests('pw_sku_products','-1',true);
		if($pw_product_sku != NULL  && $pw_product_sku != '-1'){
			$pw_product_sku  		= "'".str_replace(",","','",$pw_product_sku)."'";
		}

		$pw_variation_sku 		= $this->pw_get_woo_requests('pw_sku_variations','-1',true);
		if($pw_variation_sku != NULL  && $pw_variation_sku != '-1'){
			$pw_variation_sku  		= "'".str_replace(",","','",$pw_variation_sku)."'";
		}

		$page				= $this->pw_get_woo_requests('page',NULL);
		$pw_show_variation 	= get_option($page.'_show_variation','variable');
		$report_name 		= apply_filters($page.'_default_report_name', 'product_page');

		$report_name 		= $this->pw_get_woo_requests('report_name',$report_name,true);
		$admin_page			= $this->pw_get_woo_requests('admin_page',$page,true);

		$pw_EndDate				= $this->pw_get_woo_requests('pw_to_date',false);
		$pw_StareDate			= $this->pw_get_woo_requests('pw_from_date',false);
		$category_id		= $this->pw_get_woo_requests('pw_category_id','-1',true);
		$tag_id		= $this->pw_get_woo_requests('pw_tag_id','-1',true);

		////ADDED IN VER4.0
		//BRANDS ADDONS
		$brand_id		= $this->pw_get_woo_requests('pw_brand_id','-1',true);

		$pw_id_order_status 	= $this->pw_get_woo_requests('pw_id_order_status',NULL,true);
		$pw_order_status		= $this->pw_get_woo_requests('pw_orders_status','-1',true);
		//$pw_order_status  		= "'".str_replace(",","','",$pw_order_status)."'";
		$pw_hide_os		= $this->pw_get_woo_requests('pw_hide_os','-1',true);
		$pw_product_id			= $this->pw_get_woo_requests('pw_product_id','-1',true);
		$pw_variations			= $this->pw_get_woo_requests('pw_variations','-1',true);
		$pw_variation_column	= $this->pw_get_woo_requests('pw_variation_cols','1',true);
		$pw_show_variation		= $this->pw_get_woo_requests('pw_show_adr_variaton',$pw_show_variation,true);
		$count_generated	= $this->pw_get_woo_requests('count_generated',0,true);



		$item_att = array();
		$pw_item_meta_key =  '-1';
		if($pw_show_variation=='variable' && $pw_variations != '-1' and strlen($pw_variations) > 0){

				$pw_variations = explode(",",$pw_variations);
				//$this->print_array($pw_variations);
				$var = array();
				foreach($pw_variations as $key => $value):
					$var[] .=  "attribute_pa_".$value;
					$var[] .=  "attribute_".$value;
					$item_att[] .=  "pa_".$value;
					$item_att[] .=  $value;
				endforeach;
				$pw_variations =  implode("', '",$var);
				$pw_item_meta_key =  implode("', '",$item_att);
		}
		$pw_variation_attributes= $pw_variations;
		$pw_variation_item_meta_key= $pw_item_meta_key;



		//GET POSTED PARAMETERS
		$start				= 0;
		$pw_from_date		  = $this->pw_get_woo_requests('pw_from_date',NULL,true);
		$pw_to_date			= $this->pw_get_woo_requests('pw_to_date',NULL,true);
		$date_format = $this->pw_date_format($pw_from_date);

		$pw_product_id			= $this->pw_get_woo_requests('pw_product_id',"-1",true);
		$category_id 		= $this->pw_get_woo_requests('pw_category_id','-1',true);
		$pw_cat_prod_id_string = $this->pw_get_woo_pli_category($category_id,$pw_product_id);

		////ADDED IN VER4.0
		//BRANDS ADDONS
		$brand_id 		= $this->pw_get_woo_requests('pw_brand_id','-1',true);
		$pw_brand_prod_id_string = $this->pw_get_woo_pli_category($brand_id,$pw_product_id);

		$pw_sort_by 			= $this->pw_get_woo_requests('sort_by','-1',true);
		$pw_order_by 			= $this->pw_get_woo_requests('order_by','ASC',true);

		$pw_id_order_status 	= $this->pw_get_woo_requests('pw_id_order_status',NULL,true);
		$pw_order_status		= $this->pw_get_woo_requests('pw_orders_status','-1',true);
		//$pw_order_status  		= "'".str_replace(",","','",$pw_order_status)."'";

		$pw_show_cog		= $this->pw_get_woo_requests('pw_show_cog','no',true);

		///////////HIDDEN FIELDS////////////
		$pw_hide_os		= $this->pw_get_woo_requests('pw_hide_os','-1',true);
		$pw_publish_order='no';


		/////////////////////////
		//APPLY PERMISSION TERMS
		$key=$this->pw_get_woo_requests('table_names','',true);

		$category_id=$this->pw_get_form_element_permission('pw_category_id',$category_id,$key);

		////ADDED IN VER4.0
		//BRANDS ADDONS
		$brand_id=$this->pw_get_form_element_permission('pw_brand_id',$brand_id,$key);

		$pw_order_status=$this->pw_get_form_element_permission('pw_orders_status',$pw_order_status,$key);

		if($pw_order_status != NULL  && $pw_order_status != '-1')
			$pw_order_status  		= "'".str_replace(",","','",$pw_order_status)."'";
		///////////////////////////

		//CUSTOM WORK - 15862
		$pw_product_custom_sku		= $this->pw_get_woo_requests('pw_product_custom_sku','-1',true);
		$pw_variation_custom_sku		= $this->pw_get_woo_requests('pw_variation_custom_sku','-1',true);


		$data_format=$this->pw_get_woo_requests_links('date_format',get_option('date_format'),true);
		//////////////////////

		//CUSTOM WORK - 15862
		$pw_product_custom_sku_join='';
		$pw_product_custom_sku_condition='';

		$pw_variation_custom_sku_join='';
		$pw_variation_custom_sku_condition='';


		//CATEGORY
		$category_id_join='';
		$category_id_condition='';
		$pw_cat_prod_id_string_condition='';
		//TAG
		$tag_id_join='';
		$tag_id_condition='';
		$pw_tag_prod_id_string_condition='';

		////ADDED IN VER4.0
		//BRANDS ADDONS
		$brand_id_join='';
		$brand_id_condition='';
		$pw_brand_prod_id_string_condition='';

		//DATE
		$pw_from_date_condition='';

		//PRODUCT ID
		$pw_product_id_condition='';

		//ORDER
		$pw_id_order_status_join='';

		//VARIATION
		$pw_variation_item_meta_key_join='';
		$sql_variation_join='';
		$pw_show_variation_join='';
		$pw_variation_item_meta_key_condition='';
		$sql_variation_condition='';

		//SKU
		$product_variation_sku_condition='';
		$pw_variation_sku_condition='';
		$pw_product_sku_condition='';

		//PAID CUSTOMER
		$pw_paid_customer_join='';
		$pw_paid_customer_condition='';

		//BILLING CODE
		$pw_billing_post_code_join='';
		$pw_billing_post_code_condition='';

		//ORDER STATUS
		$pw_id_order_status_condition='';
		$pw_order_status_condition='';

		//HIDE ORDER
		$pw_hide_os_condition='';


		$sql_columns = "
		pw_woocommerce_order_items.order_item_name			AS 'product_name'
		,SUM(woocommerce_order_itemmeta.meta_value)		AS 'quantity'
		,SUM(pw_woocommerce_order_itemmeta6.meta_value)	AS 'amount'";

		//COST OF GOOD
		if($pw_show_cog=='yes'){
			$sql_columns .= " ,SUM(woocommerce_order_itemmeta.meta_value * pw_woocommerce_order_itemmeta22.meta_value) AS 'total_cost'";
		}

		$sql_columns .= "
		,DATE(shop_order.post_date)						AS post_date
		,pw_woocommerce_order_itemmeta7.meta_value			AS product_id
		,pw_woocommerce_order_items.order_item_id 			AS order_item_id";

		if($pw_show_variation == 'variable') {

			$sql_columns .= ", pw_woocommerce_order_itemmeta8.meta_value AS 'variation_id'";

			if($pw_sort_by == "sku")
				$sql_columns .= ", IF(pw_postmeta_sku.meta_value IS NULL or pw_postmeta_sku.meta_value = '', IF(pw_postmeta_product_sku.meta_value IS NULL or pw_postmeta_product_sku.meta_value = '', '', pw_postmeta_product_sku.meta_value), pw_postmeta_sku.meta_value) as pw_sku ";

			}else{
			if($pw_sort_by == "sku")
				$sql_columns .= ", IF(pw_postmeta_product_sku.meta_value IS NULL or pw_postmeta_product_sku.meta_value = '', '', pw_postmeta_product_sku.meta_value) as pw_sku";

		}


		if(($pw_variation_item_meta_key != "-1" and strlen($pw_variation_item_meta_key)>1)){
			$sql_columns .= " , pw_woocommerce_order_itemmeta_variation.meta_key AS variation_key";
			$sql_columns .= " , pw_woocommerce_order_itemmeta_variation.meta_value AS variation_value";
		}


		$sql_joins =  "
			{$wpdb->prefix}woocommerce_order_items as pw_woocommerce_order_items
			LEFT JOIN  {$wpdb->prefix}woocommerce_order_itemmeta as woocommerce_order_itemmeta ON woocommerce_order_itemmeta.order_item_id	= pw_woocommerce_order_items.order_item_id
			LEFT JOIN  {$wpdb->prefix}woocommerce_order_itemmeta as pw_woocommerce_order_itemmeta6 ON pw_woocommerce_order_itemmeta6.order_item_id= pw_woocommerce_order_items.order_item_id";

			//COST OF GOOD
			if($pw_show_cog=='yes'){
				$sql_joins .=	"
				LEFT JOIN  {$wpdb->prefix}woocommerce_order_itemmeta as pw_woocommerce_order_itemmeta22 ON pw_woocommerce_order_itemmeta22.order_item_id=pw_woocommerce_order_items.order_item_id ";
			}

			$sql_joins .=	"
			LEFT JOIN  {$wpdb->prefix}woocommerce_order_itemmeta as pw_woocommerce_order_itemmeta7 ON pw_woocommerce_order_itemmeta7.order_item_id= pw_woocommerce_order_items.order_item_id";



		if($category_id  && $category_id != "-1") {
			$category_id_join= "
				LEFT JOIN  {$wpdb->prefix}term_relationships 	as pw_term_relationships 	ON pw_term_relationships.object_id		=	pw_woocommerce_order_itemmeta7.meta_value
				LEFT JOIN  {$wpdb->prefix}term_taxonomy 		as term_taxonomy 		ON term_taxonomy.term_taxonomy_id	=	pw_term_relationships.term_taxonomy_id
				LEFT JOIN  {$wpdb->prefix}terms 				as pw_terms 				ON pw_terms.term_id					=	term_taxonomy.term_id";
		}

		if($tag_id  && $tag_id != "-1") {
			$tag_id_join= "
				LEFT JOIN  {$wpdb->prefix}term_relationships 	as pw_term_relationships_tag 	ON pw_term_relationships_tag.object_id		=	pw_woocommerce_order_itemmeta7.meta_value
				LEFT JOIN  {$wpdb->prefix}term_taxonomy 		as term_taxonomy_tag 		ON term_taxonomy_tag.term_taxonomy_id	=	pw_term_relationships_tag.term_taxonomy_id
				LEFT JOIN  {$wpdb->prefix}terms 				as pw_terms_tag 				ON pw_terms_tag.term_id					=	term_taxonomy_tag.term_id";
		}
		////ADDED IN VER4.0
		//BRANDS ADDONS
		if($brand_id  && $brand_id != "-1") {
			$brand_id_join= "
				LEFT JOIN  {$wpdb->prefix}term_relationships 	as pw_term_relationships_brand 	ON pw_term_relationships_brand.object_id		=	pw_woocommerce_order_itemmeta7.meta_value
				LEFT JOIN  {$wpdb->prefix}term_taxonomy 		as term_taxonomy_brand 		ON term_taxonomy_brand.term_taxonomy_id	=	pw_term_relationships_brand.term_taxonomy_id
				LEFT JOIN  {$wpdb->prefix}terms 				as pw_terms_brand 				ON pw_terms_brand.term_id					=	term_taxonomy_brand.term_id";
		}

		if($pw_id_order_status  && $pw_id_order_status != "-1") {
			$pw_id_order_status_join= "
				LEFT JOIN  {$wpdb->prefix}term_relationships	as pw_term_relationships2 	ON pw_term_relationships2.object_id	=	pw_woocommerce_order_items.order_id
				LEFT JOIN  {$wpdb->prefix}term_taxonomy			as pw_term_taxonomy2 		ON pw_term_taxonomy2.term_taxonomy_id	=	pw_term_relationships2.term_taxonomy_id
				LEFT JOIN  {$wpdb->prefix}terms					as terms2 				ON terms2.term_id					=	pw_term_taxonomy2.term_id";
		}


		$sql_joins.=$category_id_join.$tag_id_join.$brand_id_join.$pw_id_order_status_join;

		if($pw_show_variation == 'variable'){
			$sql_joins .= "
					LEFT JOIN  {$wpdb->prefix}woocommerce_order_itemmeta as pw_woocommerce_order_itemmeta8 ON pw_woocommerce_order_itemmeta8.order_item_id = pw_woocommerce_order_items.order_item_id
					";
			if(($pw_sort_by == "sku") || ($pw_product_sku and $pw_product_sku != '-1') || $pw_variation_sku != '-1')
				$sql_joins .= "	LEFT JOIN  {$wpdb->prefix}postmeta as pw_postmeta_sku 		ON pw_postmeta_sku.post_id		= pw_woocommerce_order_itemmeta8.meta_value";

			if(($pw_variation_item_meta_key != "-1" and strlen($pw_variation_item_meta_key)>1)){
				$pw_variation_item_meta_key_join= " LEFT JOIN  {$wpdb->prefix}woocommerce_order_itemmeta as pw_woocommerce_order_itemmeta_variation ON pw_woocommerce_order_itemmeta_variation.order_item_id= pw_woocommerce_order_items.order_item_id";
			}

			$sql_variation_join='';
			if(isset($this->search_form_fields['pw_new_value_variations']) and count($this->search_form_fields['pw_new_value_variations'])>0){
				foreach($this->search_form_fields['pw_new_value_variations'] as $key => $value){
					$new_v_key = "wcvf_".$this->pw_woo_filter_chars($key);
					$sql_variation_join= " LEFT JOIN  {$wpdb->prefix}woocommerce_order_itemmeta as woocommerce_order_itemmeta_{$new_v_key} ON woocommerce_order_itemmeta_{$new_v_key}.order_item_id = pw_woocommerce_order_items.order_item_id";
				}
			}

		}

		//CUSTOM WORK - 15862
		if(is_array(__CUSTOMWORK_ID__) && in_array('15862',__CUSTOMWORK_ID__)){

			if($pw_product_custom_sku  && $pw_product_custom_sku != "-1") {
				$pw_product_custom_sku_join = " LEFT JOIN {$wpdb->prefix}postmeta as pw_custom_sku ON pw_custom_sku.post_id = pw_woocommerce_order_itemmeta7.meta_value ";
			}
			if($pw_variation_custom_sku  && $pw_variation_custom_sku != "-1") {
				$pw_variation_custom_sku_join = " LEFT JOIN {$wpdb->prefix}postmeta as pw_v_custom_sku ON pw_v_custom_sku.post_id = pw_woocommerce_order_itemmeta8.meta_value ";
			}
		}


		$sql_joins.=$pw_variation_custom_sku_join.$pw_product_custom_sku_join.$pw_variation_item_meta_key_join.$sql_variation_join;

		if(($pw_sort_by == "sku") || ($pw_product_sku and $pw_product_sku != '-1'))
			$sql_joins .= "	LEFT JOIN  {$wpdb->prefix}postmeta		 as pw_postmeta_product_sku 		ON pw_postmeta_product_sku.post_id 			= pw_woocommerce_order_itemmeta7.meta_value	";

		$sql_joins .= " LEFT JOIN  {$wpdb->prefix}posts as shop_order ON shop_order.id=pw_woocommerce_order_items.order_id";//For shop_order

		if($pw_show_variation == 2 || ($pw_show_variation == 'grouped' || $pw_show_variation == 'external' || $pw_show_variation == 'simple' || $pw_show_variation == 'variable_')){
			$pw_show_variation_join= "
					LEFT JOIN  {$wpdb->prefix}term_relationships 	as pw_term_relationships_product_type 	ON pw_term_relationships_product_type.object_id		=	pw_woocommerce_order_itemmeta7.meta_value
					LEFT JOIN  {$wpdb->prefix}term_taxonomy 		as pw_term_taxonomy_product_type 		ON pw_term_taxonomy_product_type.term_taxonomy_id		=	pw_term_relationships_product_type.term_taxonomy_id
					LEFT JOIN  {$wpdb->prefix}terms 				as pw_terms_product_type 				ON pw_terms_product_type.term_id						=	pw_term_taxonomy_product_type.term_id";
		}

		if($pw_paid_customer  && $pw_paid_customer != '-1' and $pw_paid_customer != "'-1'"){
			$pw_paid_customer_join= "
				LEFT JOIN  {$wpdb->prefix}postmeta 			as pw_postmeta_billing_email				ON pw_postmeta_billing_email.post_id=pw_woocommerce_order_items.order_id";
		}

		if($pw_billing_post_code and $pw_billing_post_code != '-1'){
			$pw_billing_post_code_join= " LEFT JOIN  {$wpdb->prefix}postmeta as pw_postmeta_billing_postcode ON pw_postmeta_billing_postcode.post_id	=	pw_woocommerce_order_items.order_id";
		}

		$sql_joins.=$pw_show_variation_join.$pw_paid_customer_join.$pw_billing_post_code_join;

		$sql_condition= "
			woocommerce_order_itemmeta.meta_key	= '_qty'
			AND pw_woocommerce_order_itemmeta6.meta_key	= '_line_total' ";

			//COST OF GOOD
			if($pw_show_cog=='yes'){
				$sql_condition .="
				AND pw_woocommerce_order_itemmeta22.meta_key	= '".__PW_COG_TOTAL__."' ";
			}

			$sql_condition .="
			AND pw_woocommerce_order_itemmeta7.meta_key 	= '_product_id'
			AND shop_order.post_type					= 'shop_order'
			";

		//CUSTOM WORK - 15862
		if(is_array(__CUSTOMWORK_ID__) && in_array('15862',__CUSTOMWORK_ID__)){
			if($pw_product_custom_sku  && $pw_product_custom_sku != "-1") {
				$pw_product_custom_sku_condition.= " AND pw_custom_sku.meta_key = 'jk_sku' AND pw_custom_sku.meta_value LIKE '%$pw_product_custom_sku%'";
			}
			if($pw_variation_custom_sku  && $pw_variation_custom_sku != "-1") {
				$pw_variation_custom_sku_condition.= " AND pw_v_custom_sku.meta_key = 'custom_field' AND pw_v_custom_sku.meta_value LIKE '%$pw_variation_custom_sku%'";
			}
		}


		if($pw_show_variation == 'variable'){
			$sql_condition.= "
					AND pw_woocommerce_order_itemmeta8.meta_key = '_variation_id'
					AND (pw_woocommerce_order_itemmeta8.meta_value IS NOT NULL AND pw_woocommerce_order_itemmeta8.meta_value > 0)
					";

			if(($pw_sort_by == "sku") || ($pw_variation_sku and $pw_variation_sku != '-1'))
				$sql_condition .=	" AND pw_postmeta_sku.meta_key	= '_sku'";



			if(($pw_variation_item_meta_key != "-1" and strlen($pw_variation_item_meta_key)>1)){
				$pw_variation_item_meta_key_condition= " AND pw_woocommerce_order_itemmeta_variation.meta_key IN ('{$pw_variation_item_meta_key}')";
			}

			$sql_variation_condition='';
			if(isset($this->search_form_fields['pw_new_value_variations']) and count($this->search_form_fields['pw_new_value_variations'])>0){
				foreach($this->search_form_fields['pw_new_value_variations'] as $key => $value){
					$new_v_key = "wcvf_".$this->pw_woo_filter_chars($key);
					$key = str_replace("'","",$key);
					$sql .= " AND woocommerce_order_itemmeta_{$new_v_key}.meta_key = '{$key}'";
					$vv = is_array($value) ? implode(",",$value) : $value;
					//$vv = str_replace("','",",",$vv);
					$vv = str_replace(",","','",$vv);
					$sql_variation_condition= " AND woocommerce_order_itemmeta_{$new_v_key}.meta_value IN ('{$vv}') ";
				}
			}
		}

		$sql_condition.=$pw_variation_custom_sku_condition.$pw_product_custom_sku_condition.$pw_variation_item_meta_key_condition.$sql_variation_condition;

		if(($pw_sort_by == "sku") || ($pw_product_sku and $pw_product_sku != '-1'))
			$sql_condition .= " AND pw_postmeta_product_sku.meta_key			= '_sku'";

		if($pw_show_variation == 'variable'){

			if(($pw_product_sku and $pw_product_sku != '-1') and ($pw_variation_sku and $pw_variation_sku != '-1')){
				$product_variation_sku_condition= " AND (pw_postmeta_product_sku.meta_value IN (".$pw_product_sku.") AND pw_postmeta_sku.meta_value IN (".$pw_variation_sku."))";
			}else if ($pw_variation_sku and $pw_variation_sku != '-1'){
				$pw_variation_sku_condition= " AND pw_postmeta_sku.meta_value IN (".$pw_variation_sku.")";
			}else{
				if($pw_product_sku and $pw_product_sku != '-1')
					$pw_product_sku_condition= " AND pw_postmeta_product_sku.meta_value IN (".$pw_product_sku.")";
			}

		}else{

			if($pw_product_sku and $pw_product_sku != '-1')
				$pw_product_sku_condition= " AND pw_postmeta_product_sku.meta_value IN (".$pw_product_sku.")";

		}

		if ($pw_from_date != NULL &&  $pw_to_date !=NULL){
			$pw_from_date_condition= "
					AND (DATE(shop_order.post_date) BETWEEN STR_TO_DATE('" . $pw_from_date . "', '$date_format') and STR_TO_DATE('" . $pw_to_date . "', '$date_format'))";
		}

		if($pw_product_id  && $pw_product_id != "-1")
			$pw_product_id_condition= "
					AND pw_woocommerce_order_itemmeta7.meta_value IN (".$pw_product_id .")";

		if($category_id  && $category_id != "-1")
			$category_id_condition= "
					AND pw_terms.term_id IN (".$category_id .")";

		////ADDED IN VER4.0
		//BRANDS ADDONS
		if($brand_id  && $brand_id != "-1")
			$brand_id_condition= "
                AND term_taxonomy_brand.taxonomy LIKE('".__PW_BRAND_SLUG__."')
                AND pw_terms_brand.term_id IN (".$brand_id .")";

		if($pw_cat_prod_id_string  && $pw_cat_prod_id_string != "-1")
			$pw_cat_prod_id_string_condition= " AND pw_woocommerce_order_itemmeta7.meta_value IN (".$pw_cat_prod_id_string .")";

		if($pw_tag_prod_id_string  && $pw_tag_prod_id_string != "-1")
			$pw_tag_prod_id_string_condition= " AND pw_woocommerce_order_itemmeta7.meta_value IN (".$pw_tag_prod_id_string .")";

		////ADDED IN VER4.0
		//BRANDS ADDONS
		if($pw_brand_prod_id_string  && $pw_brand_prod_id_string != "-1")
			$pw_brand_prod_id_string_condition= " AND pw_woocommerce_order_itemmeta7.meta_value IN (".$pw_brand_prod_id_string .")";

		if($pw_id_order_status  && $pw_id_order_status != "-1")
			$pw_id_order_status_condition= "
					AND terms2.term_id IN (".$pw_id_order_status .")";


		$sql_condition.=$product_variation_sku_condition.$pw_variation_sku_condition.$pw_product_sku_condition.$pw_from_date_condition.$pw_product_id_condition.$category_id_condition.$brand_id_condition.$pw_cat_prod_id_string_condition.$pw_brand_prod_id_string_condition.$pw_id_order_status_condition;


		if($pw_show_variation == 'grouped' || $pw_show_variation == 'external' || $pw_show_variation == 'simple' || $pw_show_variation == 'variable_'){
			$sql_condition .= " AND pw_terms_product_type.name IN ('{$pw_show_variation}')";
		}



		if($pw_show_variation == 2){
			$sql_condition .= " AND pw_terms_product_type.name IN ('simple')";
		}

		if($pw_paid_customer  && $pw_paid_customer != '-1' and $pw_paid_customer != "'-1'"){
			$pw_paid_customer_condition= " AND pw_postmeta_billing_email.meta_key='_billing_email'";
			$pw_paid_customer_condition .= " AND pw_postmeta_billing_email.meta_value IN (".$pw_paid_customer.")";
		}

		if($pw_billing_post_code and $pw_billing_post_code != '-1'){
			$pw_billing_post_code_condition= " AND pw_postmeta_billing_postcode.meta_key='_billing_postcode' AND pw_postmeta_billing_postcode.meta_value IN ({$pw_billing_post_code}) ";
		}



		if($pw_order_status  && $pw_order_status != '-1' and $pw_order_status != "'-1'")
			$pw_order_status_condition= " AND shop_order.post_status IN (".$pw_order_status.")";

		if($pw_hide_os  && $pw_hide_os != '-1' and $pw_hide_os != "'-1'")
			$pw_hide_os_condition= " AND shop_order.post_status NOT IN ('".$pw_hide_os."')";

		$sql_condition.=$pw_paid_customer_condition.$pw_billing_post_code_condition.$pw_order_status_condition.$pw_hide_os_condition;

		$sql_group_by='';
		if($pw_show_variation == 'variable'){
			switch ($group_by) {
				case "variation_id":
					$sql_group_by= " GROUP BY pw_woocommerce_order_itemmeta8.meta_value ";
					break;
				case "order_item_id":
					$sql_group_by= " GROUP BY pw_woocommerce_order_items.order_item_id ";
					break;
				default:
					$sql_group_by= " GROUP BY pw_woocommerce_order_itemmeta8.meta_value ";
					break;

			}
			//$sql .= " GROUP BY pw_woocommerce_order_itemmeta8.meta_value ";
		}else{
			$sql_group_by= "
					GROUP BY  pw_woocommerce_order_itemmeta7.meta_value";
		}

		$sql_order_by='';
		switch ($pw_sort_by) {
			case "sku":
				$sql_order_by= " ORDER BY sku " .$pw_order_by;
				break;
			case "product_name":
				$sql_order_by= " ORDER BY product_name " .$pw_order_by;
				break;
			case "ProductID":
				$sql_order_by= " ORDER BY CAST(product_id AS DECIMAL(10,2)) " .$pw_order_by;
				break;
			case "amount":
				$sql_order_by= " ORDER BY amount " .$pw_order_by;
				break;
			case "variation_id":
				if($pw_show_variation == 'variable'){
					$sql_order_by= " ORDER BY CAST(variation_id AS DECIMAL(10,2)) " .$pw_order_by;
				}
				break;
			default:
				$sql_order_by= " ORDER BY amount DESC";
				break;
		}

		$sql="SELECT $sql_columns FROM $sql_joins WHERE $sql_condition $sql_group_by

		UNION
		SELECT (SELECT post_title FROM wp_posts WHERE id = pm.post_id) AS product_name,
		0 as quantity,0 as total_amount ,
		 Date(post_date) as post_date,  IF(p.post_parent = 0, p.ID, p.post_parent) AS post_parent,
		'' as order_item_id,
		post_id as variation_id FROM wp_postmeta AS pm JOIN wp_posts AS p ON p.ID = pm.post_id JOIN wp_term_relationships AS tr ON tr.object_id = IF(p.post_parent = 0, p.ID, p.post_parent)

		LEFT JOIN wp_term_taxonomy as term_taxonomy ON term_taxonomy.term_taxonomy_id = tr.term_taxonomy_id LEFT JOIN wp_terms as pw_terms ON pw_terms.term_id = term_taxonomy.term_id

		WHERE meta_key in ('_product_version') $category_id_condition AND p.post_status in ('publish') AND p.post_type = 'product_variation'
		AND (DATE(p.post_date) BETWEEN STR_TO_DATE('" . $pw_from_date . "', '$date_format') and STR_TO_DATE('" . $pw_to_date . "', '$date_format'))
		AND IFNULL(SUBSTR((SELECT meta_value FROM wp_postmeta WHERE post_id = pm.post_id AND meta_key = '_product_attributes' LIMIT 1), INSTR((SELECT meta_value FROM wp_postmeta WHERE post_id = pm.post_id AND
 		meta_key = '_product_attributes' ORDER BY pm.post_id ASC LIMIT 1), 'is_variation')+16,1),0)=0 Group by post_id
		";

		//echo $sql;
//die;

        $this->table_cols =$this->table_columns($table_name);
		///////////CHECK IF BRANDS ADD ON IS ENABLE///////////
		$array_index=5;
		$brands_cols=array();
		if(__PW_BRAND_SLUG__){
			$brands_cols[]=array('lable'=>__PW_BRAND_LABEL__,'status'=>'show');
			array_splice($this->table_cols,$array_index,0,$brands_cols);
			$array_index++;
		}

		///////////////////
		//VARIATIONS COLUMNS





		//CUSTOM WORK - 15862
		if(is_array(__CUSTOMWORK_ID__) && in_array('15862',__CUSTOMWORK_ID__)) {
			$custom_sku_cols[]=array('lable'=>'Custom SKU','status'=>'show');
			$custom_sku_cols[]=array('lable'=>'Variation Custom SKU','status'=>'show');
			array_splice($this->table_cols,2,0,$custom_sku_cols);
		}


		//CHECK IF COST OF GOOD IS ENABLE
		if($pw_show_cog!='yes'){
			unset($this->table_cols[count($this->table_cols)-1]);
			unset($this->table_cols[count($this->table_cols)-1]);
		}

	//	echo $sql;

	}elseif($file_used=="data_table"){

		$pw_show_variation		= $this->pw_get_woo_requests('pw_show_adr_variaton','variable',true);


		////ADDE IN VER4.0
		/// TOTAL ROWS VARIABLES
		$result_count=$sale_qty=$total_amnt=$cog_amnt=$profit_amnt=0;
$p_arr=[];
		foreach($this->results as $items){
		    $index_cols=0;
		//for($i=1; $i<=20 ; $i++){

			////ADDE IN VER4.0
			/// TOTAL ROWS
			$result_count++;


			if(in_array($items->variation_id,$p_arr)) continue;
			else $p_arr[]=$items->variation_id;


			$datatable_value.=("<tr>");
			//$product = new WC_Product( $items->variation_id );

				//Product ID
				$display_class='';
				if($this->table_cols[$index_cols++]['status']=='hide') $display_class='display:none';
				$datatable_value.=("<td style='".$display_class."'>");
					$datatable_value.= $items->product_id;
				$datatable_value.=("</td>");

				//Product SKU
				$display_class='';
				if($this->table_cols[$index_cols++]['status']=='hide') $display_class='display:none';
				$datatable_value.=("<td style='".$display_class."'>");
					$datatable_value.= get_post_meta( $items->variation_id, '_sku', true );
				$datatable_value.=("</td>");


				//Product Name
				$display_class='';
				if($this->table_cols[$index_cols++]['status']=='hide') $display_class='display:none';
				$datatable_value.=("<td style='".$display_class."'>");
					$datatable_value.= $items->product_name;
				$datatable_value.=("</td>");

				//Product Price
				$variable_product = wc_get_product($items->variation_id);
				$price = $variable_product->get_price();

				$display_class='';
				if($this->table_cols[$index_cols++]['status']=='hide') $display_class='display:none';
				$datatable_value.=("<td style='".$display_class."'>");
					$datatable_value.= wc_price($price);
				$datatable_value.=("</td>");


				//Categories
        $display_class='';
        if($this->table_cols[$index_cols++]['status']=='hide') $display_class='display:none';
        $datatable_value.=("<td style='".$display_class."'>");
        $datatable_value.= $this->pw_get_cn_product_id($items->product_id,"product_cat");
        $datatable_value.=("</td>");


        //Tags
        $display_class='';
        if($this->table_cols[$index_cols++]['status']=='hide') $display_class='display:none';
        $datatable_value.=("<td style='".$display_class."'>");
        $datatable_value.= $this->pw_get_cn_product_id($items->product_id,"product_tag");
        $datatable_value.=("</td>");

        ///////////CHECK IF BRANDS ADD ON IS ENABLE///////////
        if(__PW_BRAND_SLUG__){
            $display_class='';
            if($this->table_cols[$index_cols++]['status']=='hide') $display_class='display:none';
            $datatable_value.=("<td style='".$display_class."'>");
            $datatable_value.= $this->pw_get_cn_product_id($items->product_id,__PW_BRAND_SLUG__);
            $datatable_value.=("</td>");
        }



				//Sales Qty.
				$display_class='';
				if($this->table_cols[$j++]['status']=='hide') $display_class='display:none';
				$datatable_value.=("<td style='".$display_class."'>");
					$datatable_value.= $items->quantity;

                    ////ADDE IN VER4.0
                    /// TOTAL ROWS
                    $sale_qty+= $items->quantity;

				$datatable_value.=("</td>");

				//Current Stock
				$display_class='';
				if($this->table_cols[$j++]['status']=='hide') $display_class='display:none';
				$datatable_value.=("<td style='".$display_class."'>");
					$datatable_value.= get_post_meta( $items->variation_id, '_stock', true );
				$datatable_value.=("</td>");

				//Amount
				$display_class='';
				if($this->table_cols[$j++]['status']=='hide') $display_class='display:none';
				$datatable_value.=("<td style='".$display_class."'>");
					$datatable_value.= $this->price($items->amount);

                    ////ADDE IN VER4.0
                    /// TOTAL ROWS
                    $total_amnt+= $items->amount;

				$datatable_value.=("</td>");

				//COST OF GOOD
				$pw_show_cog= $this->pw_get_woo_requests('pw_show_cog',"no",true);
				if($pw_show_cog=='yes'){
					$display_class='';
					/*$cog=get_post_meta($items->product_id,__PW_COG__,true);
					$cog*=$items->quantity;*/
					if($this->table_cols[$index_cols++]['status']=='hide') $display_class='display:none';
					$datatable_value.=("<td style='".$display_class."'>");
						//$datatable_value.= $cog == 0 ? $this->price(0) : $this->price($cog);
						$datatable_value.= $items->total_cost == 0 ? $this->price(0) : $this->price($items->total_cost);

                        ////ADDED IN VER4.0
                        /// TOTAL ROWS
                        $cog_amnt+=$items->total_cost;

					$datatable_value.=("</td>");

					if($this->table_cols[$index_cols++]['status']=='hide') $display_class='display:none';
					$datatable_value.=("<td style='".$display_class."'>");
						//$datatable_value.= $cog == 0 ? $this->price(0) : $this->price($cog);
						$datatable_value.= ($items->amount-$items->total_cost) == 0 ? $this->price(0) : $this->price($items->amount-$items->total_cost);

                        ////ADDED IN VER4.0
                        /// TOTAL ROWS
                        $profit_amnt+=($items->amount-$items->total_cost);

					$datatable_value.=("</td>");
				}



			$datatable_value.=("</tr>");
		}

		////ADDE IN VER4.0
		/// TOTAL ROWS
		$table_name_total= $table_name;
		$datatable_value_total='';
		$pw_show_cog		= $this->pw_get_woo_requests('pw_show_cog','no',true);
		$this->table_cols_total = $this->table_columns_total( $table_name_total );
		if($pw_show_cog!='yes'){
			////ADDE IN VER4.0
			/// COST OF GOOD
			unset($this->table_cols_total[count($this->table_cols_total)-1]);
			unset($this->table_cols_total[count($this->table_cols_total)-1]);
		}

		$datatable_value_total.=("<tr>");
		$datatable_value_total.="<td>$result_count</td>";
		$datatable_value_total.="<td>$sale_qty</td>";
		$datatable_value_total.="<td>".(($total_amnt) == 0 ? $this->price(0) : $this->price($total_amnt))."</td>";
		if($pw_show_cog=='yes'){
			$datatable_value_total.="<td>".(($cog_amnt) == 0 ? $this->price(0) : $this->price($cog_amnt))."</td>";
			$datatable_value_total.="<td>".(($profit_amnt) == 0 ? $this->price(0) : $this->price($profit_amnt))."</td>";
		}
		$datatable_value_total.=("</tr>");

	}elseif($file_used=="search_form"){
	?>
		<form class='alldetails search_form_report' action='' method='post'>
            <input type='hidden' name='action' value='submit-form' />
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
                </div>

								<div class="col-md-6"  >
										<div class="awr-form-title">
												<?php _e('Category',__PW_REPORT_WCREPORT_TEXTDOMAIN__);?>
										</div>
					<span class="awr-form-icon"><i class="fa fa-tags"></i></span>
					<?php
											$args = array(
													'orderby'                  => 'name',
													'order'                    => 'ASC',
													'hide_empty'               => 1,
													'hierarchical'             => 0,
													'exclude'                  => '',
													'include'                  => '',
													'child_of'          		 => 0,
													'number'                   => '',
													'pad_counts'               => false

											);

											//$categories = get_categories($args);
											$current_category=$this->pw_get_woo_requests_links('pw_category_id','',true);

											$categories = get_terms('product_cat',$args);
											$option='';
											foreach ($categories as $category) {
						$selected='';

													$option .= '<option value="'.$category->term_id.'" '.$selected.'>';
													$option .= $category->name;
													$option .= ' ('.$category->count.')';
													$option .= '</option>';
											}
									?>
									<select name="pw_category_id[]" multiple="multiple" size="5"  data-size="5" class="chosen-select-search">
											<?php
												if($this->get_form_element_permission('pw_category_id') && ((!is_array($permission_value)) || (is_array($permission_value) && in_array('all',$permission_value))))
						{
					?>
											<option value="-1"><?php _e('Select All',__PW_REPORT_WCREPORT_TEXTDOMAIN__);?></option>
											<?php
						}
					?>
											<?php
													echo $option;
											?>
									</select>

								</div>

            <div class="col-md-12">
                    <?php
                    	$pw_hide_os=$this->otder_status_hide;
						$pw_publish_order='no';

						$data_format=$this->pw_get_woo_requests_links('date_format',get_option('date_format'),true);
					?>
                    <input type="hidden" name="list_parent_category" value="">
                    <input type="hidden" name="group_by_parent_cat" value="0">

                	<input type="hidden" name="pw_hide_os" id="pw_hide_os" value="<?php echo $pw_hide_os;?>" />

                    <input type="hidden" name="date_format" id="date_format" value="<?php echo $data_format;?>" />

                	<input type="hidden" name="table_names" value="<?php echo $table_name;?>"/>
                    <div class="fetch_form_loading search-form-loading"></div>
                    <button type="submit" value="Search" class="button-primary"><i class="fa fa-search"></i> <span><?php echo esc_html__('Search',__PW_REPORT_WCREPORT_TEXTDOMAIN__); ?></span></button>
					<button type="button" value="Reset" class="button-secondary form_reset_btn"><i class="fa fa-reply"></i><span><?php echo esc_html__('Reset Form',__PW_REPORT_WCREPORT_TEXTDOMAIN__); ?></span></button>
            </div>

        </form>
    <?php
	}

?>
