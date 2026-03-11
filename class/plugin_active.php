<?php

	$pw_active_plugin = array(

		array(
			'label'	=> esc_html__('Purchase Code',__PW_REPORT_WCREPORT_TEXTDOMAIN__),
			'desc'	=> esc_html__('Enter Your Purchase Code',__PW_REPORT_WCREPORT_TEXTDOMAIN__),
			'name'  => __PW_REPORT_WCREPORT_FIELDS_PERFIX__.'activate_purchase_code',
			'id'	=> __PW_REPORT_WCREPORT_FIELDS_PERFIX__.'activate_purchase_code',
			'type'	=> 'text',

		),

		array(
			'label'	=> esc_html__('Email',__PW_REPORT_WCREPORT_TEXTDOMAIN__),
			'desc'	=> esc_html__('Enter Your Valid Email.',__PW_REPORT_WCREPORT_TEXTDOMAIN__),
			'name'  => __PW_REPORT_WCREPORT_FIELDS_PERFIX__.'activate_email',
			'id'	=> __PW_REPORT_WCREPORT_FIELDS_PERFIX__.'activate_email',
			'type'	=> 'text',
		
		),


	);
    $text_return='';
	if (isset($_POST["update_settings"])) {
		// Do the saving

        $email=isset($_POST[__PW_REPORT_WCREPORT_FIELDS_PERFIX__.'activate_email']) ? $_POST[__PW_REPORT_WCREPORT_FIELDS_PERFIX__.'activate_email']:"";

		foreach($_POST as $key=>$value){
			if(!isset($_POST[$key])){
				delete_option($key);
				continue;
			}

			$old = get_option($key);
			$new = $value;
			if(!is_array($new))
			{



				if ($new && $new != $old) {
					update_option($key, $new);
				} elseif ('' == $new && $old) {
					delete_option($key);
				}
			}else{


				$get_year=array_keys($value);
				$get_year=$get_year[0];

				foreach($value[$get_year] as $keys=>$vals){

					$old = get_option($key."_".$get_year."_".$keys);
					$new = $vals;

					if ($new && $new != $old) {
						update_option($key."_".$get_year."_".$keys, $new);
					} elseif ('' == $new && $old) {
						delete_option($key."_".$get_year."_".$keys);
					}

				}
			}
		}

		global $pw_rpt_main_class;
		$field=__PW_REPORT_WCREPORT_FIELDS_PERFIX__.'activate_purchase_code';
		$pw_rpt_main_class->pw_plugin_status=get_option($field);


		$field=__PW_REPORT_WCREPORT_FIELDS_PERFIX__.'activate_email';
		$pw_rpt_main_class->email=get_option($field);


		$text='';


		$check_db = $pw_rpt_main_class->dashboard($pw_rpt_main_class->pw_plugin_status);

		if ($pw_rpt_main_class->dashboard($pw_rpt_main_class->pw_plugin_status) && isset($check_db["verify-purchase"]["status"]) && $check_db["verify-purchase"]["status"]=='valid' && filter_var($pw_rpt_main_class->email, FILTER_VALIDATE_EMAIL)){
			$text=esc_html__('Congratulation, The Plugin has been Activated Successfully !',__PW_REPORT_WCREPORT_TEXTDOMAIN__);
			?>
                <script>
                    jQuery(document).ready(function ($) {
                        setTimeout(function() {
                            $(".pw_active_ok").attr("style", "display:block !important");
                            $(".pw_active_email").attr("style", "display:none !important");
                        },500);
                    });
                </script>

            <?php
		}else if ((isset($check_db["verify-purchase"]["status"]) && $check_db["verify-purchase"]["status"]!='valid') || !filter_var($pw_rpt_main_class->email, FILTER_VALIDATE_EMAIL)){
			$text=esc_html__('Unfortunately, The Purchase code is Wrong, Please try Again !',__PW_REPORT_WCREPORT_TEXTDOMAIN__);
			$text_return=$check_db["verify-purchase"]["status"];
			?>
                <script>
                    jQuery(document).ready(function ($) {
                        setTimeout(function(){
                            $(".pw_active_error").attr("style","display:block !important");
                        },500);
                    });
                </script>

            <?php
		}
	}

    global $pw_rpt_main_class;
	$field_1=$pw_active_plugin[0];
	$field_2=$pw_active_plugin[1];

	$meta_1 = get_option($field_1['id']);
	$meta_2 = get_option($field_2['id']);

    $text_ok=esc_html__('Congratulation, The Plugin has been Activated Successfully ! Move to ',__PW_REPORT_WCREPORT_TEXTDOMAIN__).'<a href="admin.php?page='.$pw_rpt_main_class->pw_plugin_main_url.'">'.esc_html__("Dashboard",__PW_REPORT_WCREPORT_TEXTDOMAIN__).'</a>';
    $text_error=esc_html__('Unfortunately, The Purchase code is Wrong or Email is not Valid, Please try Again !',__PW_REPORT_WCREPORT_TEXTDOMAIN__);

	$html= '
    <div class="wrap">
        <div class="row">
                <div class="col-xs-12">
                    <div class="awr-box">
                            <div class="awr-title">
                                <h3><i class="fa fa-shield"></i>'.esc_html__('Plugin Activate',__PW_REPORT_WCREPORT_TEXTDOMAIN__).'  </h3>
                            </div><!--awr-title -->
                            <div class="awr-box-content" >
                                <div class="col-md-12">
                                    <div id="setting-error-settings_updated" class="updated settings-error pw_active_ok">
                                        <p><strong>'.$text_ok.'</strong></p>
                                    </div>

                                    <div id="setting-error-settings_updated" class="error pw_active_error">
                                        <p><strong>'.$text_error.'</strong></p>
                                        <p style="color: #f1c40f"><strong>'.$text_return.'</strong></p>
                                    </div>';
                                    global $pw_rpt_main_class;
                                    $field=__PW_REPORT_WCREPORT_FIELDS_PERFIX__.'activate_email';
                                    $pw_rpt_main_class->email=get_option($field);
                                    if(!filter_var($pw_rpt_main_class->email, FILTER_VALIDATE_EMAIL)) {
	                                    $html .= '
                                        <div id="setting-error-settings_updated" class="updated email-notice pw_active_email">
                                            <p><strong>' . esc_html__( 'Please set email for complete activation in Ver4.0', __PW_REPORT_WCREPORT_TEXTDOMAIN__ ) . '</strong></p>
                                        </div>';
                                    }
                                    $html.= '
                                </div>

                                <form method="POST" action="" class="awr-setting-form">
                                        <input type="hidden" name="update_settings" value="Y" />
                                        <div class="col-md-6">
                                            <div class="awr-form-title"><label>'.$field_1['label'].'</label></div>

                                            <input type="text" name="'.$field_1['id'].'" id="'.$field_1['id'].'" class="'.$field_1['id'].'" value="'.$meta_1.'" >
                                            <br /><div class="description">'.$field_1['desc'].'</div>

                                        </div>

                                        <div class="col-md-6">
                                            <div class="awr-form-title"><label>'.$field_2['label'].'</label></div>

                                            <input type="email" name="'.$field_2['id'].'" id="'.$field_2['id'].'" class="'.$field_2['id'].'" value="'.$meta_2.'" >
                                            <br /><div class="description">'.$field_1['desc'].'</div>

                                        </div>

                                        <div class="col-md-12">
                                            <div class="awr-setting-submit" style="margin-top:20px">
                                                <button type="submit" value="Save settings" class="button-primary"><i class="fa fa-floppy-o"></i> <span>'.esc_html__('Save Settings',__PW_REPORT_WCREPORT_TEXTDOMAIN__).'</span></button>

                                            </div>
                                        </div>
                                </form>
                            </div>

                    </div>
                </div>
        </div>
	</div>

	';

	echo $html;
?>
