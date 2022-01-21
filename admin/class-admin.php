<?php
defined( 'ABSPATH' ) or exit;
if ( ! class_exists( 'MEW_Admin' ) ) {
    class MEW_Admin{
        public function __construct()
        {
            require_once(MEW_PLUGIN_ADMIN_DIR.'/class-admin-settings.php');
            require_once(MEW_PLUGIN_ADMIN_DIR.'/class-admin-ajax.php');
            add_action('admin_menu',[&$this, 'mew_plugin_setup_menu']);
            add_action('admin_enqueue_scripts',[&$this,'admin_scripts']);
            add_action('init',[&$this,'start_cron_process']);
            add_action('mew_import_customer',[&$this,'cron_job_callback']);
            add_action('woocommerce_analytics_update_customer',[&$this,'update_customer']);
            add_action('before_delete_post',[&$this,'check_user_and_delete']);
        }
        
       public static function check_user_and_delete($id){
        $post_type = get_post_type($id);

            if ($post_type !== 'shop_order') {
            return;
            }
            $order = new WC_Order($id);
            $customer_id=$order->get_customer_id();
            
                global $wpdb;
		        $orders_table = $wpdb->prefix . 'wc_order_stats';
                $guest_customer_id=$wpdb->get_var(
                    $wpdb->prepare(
                        "SELECT customer_id FROM {$orders_table} WHERE order_id= %d",$id
                    )
                );
              $lastOrderData=MEW_Common_Functions::customerLastOrder($guest_customer_id);
              if($lastOrderData){
                  
                  $lastOrderId=$lastOrderData['id'];
               
                  if($id==$lastOrderId){
                    $reportData=MEW_Common_Functions::getIndividualCustomer($guest_customer_id);
                    $customers=$reportData->data;
                   
                    if(!empty($customers[0])){
                        $customerData=$customers[0];
                        $email=$customerData['email'];
                        $apiKey=get_option('mew_api_key');
                        $workspaceId=get_option('mew_workspace_id');
                        WC_Mux_Email::postMuxEmailServer('deleteContact',$apiKey,$workspaceId,['email'=>$email,'source'=>'WooCommerce'],'DELETE');

                    }
                  }
              }
          
       }
        public static function update_customer($customer_id){
            $reportData=MEW_Common_Functions::getIndividualCustomer($customer_id);
            $customers=$reportData->data;
            
            if(!empty($customers[0])){
                
                $apiKey=get_option('mew_api_key');
                $workspaceId=get_option('mew_workspace_id');
                $customerData=$customers[0];
                $exitingTags=get_option('mew_tags'.$workspaceId,[]);
                $customerArray=MEW_Common_Functions::createCustomerData($customer_id,$customerData);
                $customData=MEW_Common_Functions::createCustomerCustomData($customer_id,$customerData);
                if(!empty($customerArray)){
                   $customerArray['tags']=$exitingTags;
                }
                if(!empty($customData)){
                    $customerArray['custom']=$customData;
                }
               WC_Mux_Email::postMuxEmailServer('addOrUpdateContact',$apiKey,$workspaceId,$customerArray);
               $alreadyImported=get_option('mew_customer_imported_number'.$workspaceId,0);
               update_option('mew_customer_imported_number'.$workspaceId,($alreadyImported+1));

            }
        }
      
        public static function cron_job_callback($workspaceId){

            $mewCustomerPageNumber=get_option('mew_page_number'.$workspaceId,0);
            $alreadyImported=get_option('mew_customer_imported_number',0);
            $apiKey=get_option('mew_api_key');
            $mewCustomerPageNumber+=1;
            $reportData=MEW_Common_Functions::getCustomerObject(WC_Mux_Email::$numberOfCustomerPerPage,$mewCustomerPageNumber);
            $customers=$reportData->data;
            if(!empty($customers)){
                $finalRequestArray=[];
                $importedCustomer=0;
                foreach($customers as $customerData){
                    $customerId=$customerData['id'];
                    $customerArray=MEW_Common_Functions::createCustomerData($customerId,$customerData,true);
                    array_push($finalRequestArray,$customerArray);
                    $importedCustomer+=1;
                   
                }
               $exitingTags=get_option('mew_tags'.$workspaceId,[]);
               $requestArray['contacts']=$finalRequestArray;
               $requestArray['aTags']=$exitingTags;
               $requestArray['source']='Woocommerce';
               $requestArray['sourceId']=site_url();
               update_option('mew_customer_imported_number'.$workspaceId,($alreadyImported+$importedCustomer));
               WC_Mux_Email::postMuxEmailServer('syncContacts',$apiKey,$workspaceId,$requestArray);
            }
            $pageNo=$reportData->page_no;
            wc_get_logger()->debug($pageNo,['source'=>'Sync Page No']);
            if(!empty($pageNo) && $pageNo > 0){
                update_option('mew_page_number'.$workspaceId,$pageNo);
            }else{
                delete_option('mew_start_sync'.$workspaceId);
                update_option('mew_last_sync'.$workspaceId,time());
            }
            
        }
        public function start_cron_process(){
       
            $workspaceId=get_option('mew_workspace_id');
            if(get_option('mew_start_sync'.$workspaceId)){
                if ( false === as_next_scheduled_action( 'mew_import_customer' ) ) {
                as_schedule_recurring_action( strtotime( '+5 minutes' ),  5 * MINUTE_IN_SECONDS,'mew_import_customer' ,['workspaceId'=>$workspaceId],esc_attr__('MuxEmail customer import','mew') );
                }

            }else{
                as_unschedule_all_actions('mew_import_customer');
            }
        }

        public function mew_plugin_setup_menu(){
            add_submenu_page('woocommerce', esc_attr__('MuxEmail','mew'),esc_attr__('MuxEmail','mew') , 'manage_options', 'mew-options', [&$this,'mew_settings_init']);
            add_submenu_page(
                'options.php'
                 , esc_attr__('MuxEmail Installation','mew')
                 , esc_attr__('MuxEmail Installation','mew')
                 , 'manage_options'
                 , 'mew_plugin_install'
                 , [&$this,'plugin_installation_setting']
             );
        }
        public function plugin_installation_setting(){
            $loginCheck=get_option('mew_logged_in');
            $isLoggedIn=$loginCheck==1 ? true : false;
            if($isLoggedIn){
                wp_safe_redirect(admin_url( 'admin.php?page=mew-options&tab=general' ));
            }
           ?>
           
           <div class="mew_admin_heading">
                <img src="<?php echo MEW_PLUGIN_ABSOLUTE_PATH.'admin/assets/img/muxemail.svg'?>" alt="<?php esc_html_e("MuxEmail","mew") ?>" />
                <p class="description">
                    <b>
                    <?php esc_html_e('Connect MuxEmail with your WooCommerce store to sync, tag and send emails to your customer','mew');?>
                    </b>
                </p>
            </div>
           <div class="mew-wrap">
           <div id="mewLoader" style="display: none;">
                <div class="loader"></div>
            </div>   
           <h2><?php esc_attr_e('Connect your store to MuxEmail','mew'); ?></h2>
           <table class="form-table" role="presentation">
               <tbody>
                   <tr>
                       <th scope="row">
                           <label for="mew_api_key"> 
                               <?php esc_attr_e('Api Key','mew')?>
                           </label>
                    </th>
                       <td>       
                           <?php 
                           $savedApiKey=get_option('mew_api_key');

                            ?>
                            <script>
                                <?php if(!empty($savedApiKey)){
                                    ?>
                                    var loadWorkspaceOnPageLoad=true;
                               <?php } ?>
                             </script>   
                            <input type="text" value="<?php echo esc_attr($savedApiKey) ?>" class="mew-setting-text" id="mew_api_key"><a title="<?php esc_html_e('Load Workspace','mew')?>" class="mewLoadWorkSpace" href="#"><span class="dashicons dashicons-download"></span></a>
                            <p><?php 
                            printf(
                                esc_html__( "Find your MuxEmail API Key %s", "mew" ),
                                sprintf(
                                    '<a href="%1$s">%2$s</a>',
                                    esc_url('https://app.muxemail.com/#/'),
                                    esc_html__( 'here', 'mew' )
                                    ),
                            );
                            ?>
                            </p>
            </td>
        </tr>
        <tr>
            <th scope="row">
                <label for="mew_workspace_id">
                <?php esc_attr_e('Workspace Id','mew')?>
                </label>
            </th>
            <td>        
                <select id="mew_workspace_id" class="mew-setting-text">
                   
                </select>
            </td>
        </tr>
    </tbody>
</table>
        <div style="text-align: right;">
                <a id="connectMuxEmail" href="#" class="button button-primary button-large mew-submit-button"><?php esc_html_e('Connect','mew')?></a>
               <p>
                <?php
                printf(
                    esc_html__( "Don't have MuxEmail account? Create one %s", "mew" ),
                    sprintf(
                        '<a href="%1$s">%2$s</a>',
                        esc_url('https://app.muxemail.com/#/signup'),
                        esc_html__( 'here', 'mew' )
                        ),
                );
                 ?>
               </p>
               </div>
</div>
           <?php
        }
        public function admin_scripts(){
            wp_register_style( 'mewselect2css',MEW_PLUGIN_ABSOLUTE_PATH. 'admin/assets/css/select2.css', [], '3.4.8', 'all' );
            wp_register_script( 'mewselect2', MEW_PLUGIN_ABSOLUTE_PATH.'admin/assets/js/select2.js', [ 'jquery' ], '3.4.8', true );        
            wp_enqueue_style('mew-pure-css', MEW_PLUGIN_ABSOLUTE_PATH.'admin/assets/css/pure-min.css', [],'2.0.6','all');
            wp_enqueue_style('mew-pure-responsive', MEW_PLUGIN_ABSOLUTE_PATH.'admin/assets/css/grids-responsive-min.css',['mew-pure-css'],'2.0.6','all');
            wp_enqueue_style('mew-jquery-confirm', MEW_PLUGIN_ABSOLUTE_PATH.'admin/assets/css/jquery-confirm.min.css', [],'3.3.2','all');
            wp_enqueue_style('mew-main', MEW_PLUGIN_ABSOLUTE_PATH . 'admin/assets/css/mew.admin.css', false);
            wp_enqueue_script( 'mew_jquery_confirm',MEW_PLUGIN_ABSOLUTE_PATH.'admin/assets/js/jquery-confirm.min.js' , [], '3.3.2', true );
            wp_register_script( 'mew_admin_main',MEW_PLUGIN_ABSOLUTE_PATH.'admin/assets/js/mew.admin.js' , ['jquery'], '1.0.0', true );
            wp_localize_script('mew_admin_main','mew_vars',[
                'ajax_url'=>admin_url('admin-ajax.php'),
                'nonces' => [
                    'validation'    =>  wp_create_nonce('mew-validate-nonce'),
                    'login'         =>  wp_create_nonce('mew-login-nonce'),
                    'mapAttribute'  =>  wp_create_nonce('mew-attribute-nonce'),
                    'saveAttribute' =>  wp_create_nonce('mew-save-attribute-nonce'),
                    'createSynceProcess'=> wp_create_nonce('mew-create-sync-process')
                ],
                'apiKeyError'=>esc_attr__('MuxEmail API Key is required','mew'),
                'errorText'=>esc_attr__('Error','mew'),
                'successText'=>esc_attr__('Success','mew'),
                'workSpaceError'=>esc_attr__('MuxEmail Workspace Id is required','mew'),
                'errorTextServer'=>esc_attr__('MuxEmail API key has\'t validated','mew'),
                'errorTextEmpty'=>esc_attr__('API Key and Workspace ID is required','mew'),
                
            ]);
            wp_enqueue_style( 'mewselect2css' );
            wp_enqueue_script( 'mewselect2' );
        
            wp_enqueue_script('mew_admin_main');
        }
        public function mew_settings_init(){
            global $mew_settings_active_tab;
            $mew_settings_active_tab = isset( $_GET['tab'] ) ? sanitize_text_field($_GET['tab']) : 'general';
            ?>
            <h2 class="nav-tab-wrapper">
                <?php
                    do_action( 'mew_settings_tab' );
                ?>
            </h2>

            <?php
             do_action('mew_settings_content');
        }
    }
    new MEW_Admin();
}