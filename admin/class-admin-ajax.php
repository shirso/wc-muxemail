<?php

use function TeamBooking\WPML\update;

defined( 'ABSPATH' ) or exit;
if ( ! class_exists( 'MEW_Admin_Ajax' ) ) {
    class MEW_Admin_Ajax{
        public function __construct()
        {
            add_action('wp_ajax_mew_load_workspace_data',[&$this,'ajax_mew_load_workspace_data']);
            add_action('wp_ajax_mew_create_login',[&$this,'ajax_mew_create_login']);
            add_action('wp_ajax_mew_add_new_attribute',[&$this,'ajax_mew_add_new_attribute']);
            add_action('wp_ajax_mew_save_settings',[&$this,'ajax_mew_save_settings']);
            add_action('wp_ajax_mew_create_sync_process',[&$this,'ajax_mew_create_sync_process']);
        }
        public static function ajax_mew_create_sync_process(){
            check_ajax_referer( 'mew-create-sync-process', 'security' );
            $workspaceId=get_option('mew_workspace_id');
            update_option('mew_start_sync'.$workspaceId,true);
            update_option('mew_sync_started'.$workspaceId,true);
            wp_send_json_success([
                'msg'=>esc_attr__('Initial sync process has been started!','mew')
            ]);
        }
       
        public static function ajax_mew_save_settings(){
            check_ajax_referer( 'mew-save-attribute-nonce', 'security' );
            MEW_Common_Functions::parse(urldecode($_POST['mew_attrs']),$mewAttrs);
            MEW_Common_Functions::parse(urldecode($_POST['woo_attrs']),$wooAttrs);
            $mewAttrs=$mewAttrs['mew_attributes'];
            $wooAttrs=$wooAttrs['mew_woo_attributes'];
            $workspaceId=get_option('mew_workspace_id');
            $attributes=array_combine($wooAttrs,$mewAttrs);
            update_option('mew_field_attributes'.$workspaceId,$attributes);
            update_option('mew_tags'.$workspaceId,MEW_Common_Functions::array_sanitize($_POST['tags']));
            wp_send_json_success(['msg'=>esc_attr__('Settings have been saved.','mew')]);
        }
        public static function ajax_mew_add_new_attribute(){
            check_ajax_referer( 'mew-attribute-nonce', 'security' );
            MEW_Common_Functions::parse(urldecode($_POST['mew_attrs']),$mewAttrs);
            MEW_Common_Functions::parse(urldecode($_POST['woo_attrs']),$wooAttrs);
            //print_r($mewAttrs);exit;
            $mewAttrs=$mewAttrs['mew_attributes'];
            $wooAttrs=$wooAttrs['mew_woo_attributes'];
            $attributes=array_combine($wooAttrs,$mewAttrs);
            $apiKey=get_option('mew_api_key');
            $workSpaceId=get_option('mew_workspace_id');
            $allWoofields=WC_Mux_Email::wooCustomerFields();
         
            $allFields=get_transient('_mew_fields');
            if(empty($allFields)){
                $allFields=WC_Mux_Email::getMuxEmailGet('getAllSubscriberFields',$apiKey,$workSpaceId);
                set_transient('_mew_fields',$allFields,300);
            }
            ob_start();
          ?>
          <div class="pure-g mewAttributeFormFields">
            <div class="pure-u-1 pure-u-md-1-2">
            <select class="mew_woo_attributes"  name="mew_woo_attributes[]">
            <?php foreach($allWoofields as $k0=>$wooField){ 
                $disabled=in_array($k0,array_keys($attributes)) ? 'disabled' :'';
                ?>
                <option  <?php echo esc_attr($disabled) ?> value="<?php echo esc_attr($k0) ?>"><?php echo esc_attr($wooField) ?></option>
             <?php } ?>   
            </select>   
            </div>   
            <div class="pure-u-1 pure-u-md-1-2">
                <select class="mew_attributes"  name="mew_attributes[]">
                <?php if(!empty($allFields)){
                                            foreach($allFields as $field){
                                                $disabled=in_array($field['fieldKey'],array_values($attributes))?'disabled':'';
                                            ?>
                                            <option <?php echo esc_attr($disabled) ?> value="<?php echo esc_attr($field['fieldKey']) ?>"><?php echo isset($field['fieldLabel'])?esc_attr($field['fieldLabel']):esc_attr($field['fieldKey']) ?></option>
                                            <?php }}?>
                </select>   
                <div class="mewRemoveAttributesButtonContainer"><a class="mew-new-field-button mew_remove_attr" href="#"><span class="dashicons dashicons-remove"></span></a></div> 

                <div id="mewMapNewAttributesButtonContainer"><a id="mewMapNewAttributes" class="mew-new-field-button" href="#"><span class="dashicons dashicons-plus-alt"></span></a></div> 

            </div>   
          </div>
          <?php
          die(ob_get_clean());
        }
        public static function ajax_mew_load_workspace_data(){
            check_ajax_referer( 'mew-validate-nonce', 'nonce' );
            $apiKey=sanitize_text_field($_POST['apikey']);
            $workSpaces=WC_Mux_Email::getMuxEmailGet('getAllWorkspaces',$apiKey);
           
            $html='';
            $html.='<option value="">'.esc_attr__('Select Workspace','mew').'</option>';
            if($workSpaces && is_array($workSpaces)){
                
                foreach($workSpaces as $workSpace){
                    $html.='<option value="'.esc_attr($workSpace['workspaceID']).'">'.esc_attr($workSpace['name']).'</option>';
                }
            }
            wp_send_json_success(['html'=>$html]);
        }
        public function ajax_mew_create_login(){
            check_ajax_referer( 'mew-login-nonce', 'security' );
            $apiKey=sanitize_text_field($_POST['apiKey']);
            $workSpaceId=sanitize_text_field($_POST['workSpaceId']);
            $validateCredentials=WC_Mux_Email::postMuxEmailServer('authentiticationWithTokens',$apiKey,$workSpaceId);
            if($validateCredentials){
            update_option('mew_api_key',$apiKey);
            update_option('mew_workspace_id',$workSpaceId);
            update_option('mew_logged_in',1);
            $returnUrl=admin_url( 'admin.php?page=mew-options&tab=general' );
            wp_send_json_success(['url'=>$returnUrl]);
            }else{
                wp_send_json_error(['msg'=>esc_attr__('Invalid MuxEmail Credentials','mew')]);
            }

        }
    }
    new MEW_Admin_Ajax();
}