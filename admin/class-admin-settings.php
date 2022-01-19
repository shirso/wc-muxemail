<?php
if ( ! defined( 'ABSPATH' ) ) exit;
use Automattic\WooCommerce\Admin\API\Reports\Customers;
if(!class_exists('MEW_Admin_Settings')){
    class MEW_Admin_Settings{
        public function __construct(){
            add_action('admin_init',[&$this,'plugin_admin_init_settings']);

            add_action('mew_settings_tab',[&$this,'mew_settings_tab_general'],1);
            add_action('mew_settings_tab',[&$this,'mew_settings_tab_contacts'],2);
            add_action('mew_settings_tab',[&$this,'mew_settings_tab_contacts'],2);


            add_action('mew_settings_content',[&$this,'mew_settings_content_general']);
            add_action('mew_settings_content',[&$this,'mew_settings_content_contacts']);
            add_action('mew_settings_content',[&$this,'mew_settings_content_logout']);

            add_action('update_option_mew_settings',[&$this,'add_logout_status'],10,3);
            add_action('add_option_mew_settings',[&$this,'add_logout_status_add'],10);
        }
        public  function add_logout_status($old_value,$value,$option)
        {
            update_option('mew_settings_login',1);
        }
        public  function add_logout_status_add($new)
        {
            update_option('mew_settings_login',1);
        }
        public function plugin_admin_init_settings(){
            register_setting( 'mew_plugin_options', 'mew_settings',[&$this,'plugin_options_validate']);
            add_settings_section('mew_plugin_main', null, [&$this,'plugin_description'], 'mew_plugin_fields');
            add_settings_field('mew_api_key', esc_attr__('Api Key','mew'), [&$this,'mew_api_key'],'mew_plugin_fields','mew_plugin_main');
            add_settings_field('mew_workspace_id', esc_attr__('Workspace Id','mew'), [&$this,'mew_workspace_id'],'mew_plugin_fields','mew_plugin_main');


        }
        public function plugin_options_validate($input){
            $options = [];
            $valid=true;
            if($input['api_key']==''){
                add_settings_error(
                    'mew_error_message',
                    'mew_message',
                    esc_attr__( 'API Key missing', 'mew' ),
                    'error'
                );
                $options['api_key']=false;
                $valid=false;
            }else{
                $options['api_key']=$input['api_key'];
            }
            if($input['workspace_id']==''){
                add_settings_error(
                    'mew_error_message',
                    'mew_message',
                    esc_attr__( 'Workspace Id missing', 'mew' ),
                    'error'
                );
                $options['workspace_id']=false;
                $valid=false;
            }else{
                $options['workspace_id']=$input['workspace_id'];
            }
            if($valid){
            $url=WC_Mux_Email::$apiEndPoint.'authentiticationWithTokens';
            $response = wp_safe_remote_post(
                $url,
                [
                    'headers'     => [
                        'Content-Type'  => 'application/json',
                        'apikey' => $options['api_key'],
                        'workspaceid'=>$options['workspace_id']
                    ],
                    'body'        => wp_json_encode([]),
                    'method'      => 'POST',
                    'data_format' => 'body',
                ]
            );
            if(!is_wp_error($response)){
            $retriveBody=wp_remote_retrieve_body($response);
              $retriveBodyArray=json_decode($retriveBody,true);
              if(in_array('error',array_keys($retriveBodyArray))){
                add_settings_error(
                    'mew_error_message',
                    'mew_message',
                    esc_attr__( 'MuxEmail credentials are not valid', 'mew' ),
                    'error'
                );
                $options['workspace_id']=false;
                $options['api_key']=false;
                delete_option('mew_settings_login');
              }
            }else{
                add_settings_error(
                    'mew_error_message',
                    'mew_message',
                    esc_attr__( "Can't validate MuxEmail server", 'mew' ),
                    'error'
                );
                $options['workspace_id']=false;
                $options['api_key']=false;
                delete_option('mew_settings_login');
            }
           
        }
            return $options;
        }
        public function plugin_description(){
            ?>
            <?php
        }
        public function mew_settings_tab_general(){
            global $mew_settings_active_tab;
           
            ?>
           <a class="nav-tab <?php echo $mew_settings_active_tab == esc_attr('general') || '' ? 'nav-tab-active' : ''; ?>" href="<?php echo admin_url( 'admin.php?page=mew-options&tab=general' ); ?>"><?php esc_attr_e( 'General', 'mew' ); ?> </a>
           <?php
        }
        public function mew_settings_tab_contacts(){
            global $mew_settings_active_tab;
           
            ?>
           <a class="nav-tab <?php echo $mew_settings_active_tab == esc_attr('contacts') ? 'nav-tab-active' : ''; ?>" href="<?php echo admin_url( 'admin.php?page=mew-options&tab=contacts' ); ?>"><?php esc_attr_e( 'Contacts', 'mew' ); ?> </a>
           <?php
        }
        public function mew_settings_content_general(){
            global $mew_settings_active_tab;
            if ( 'general' != $mew_settings_active_tab )
                return;
                $loginCheck=get_option('mew_logged_in');
                $isLoggedIn=$loginCheck==1 ? true : false;
                if(!$isLoggedIn){
                    wp_safe_redirect(admin_url( 'options.php?page=mew_plugin_install' ));
                }
                if($isLoggedIn){
              ?>
               
               <div class="wrap mew-wrap">
               
                <p class="description">
                <?php
                $workSpaceName='';
                $apiKey=get_option('mew_api_key');
                $savedWorkSpaceId=get_option('mew_workspace_id');
                $workSpaces=WC_Mux_Email::getMuxEmailGet('getAllWorkspaces',$apiKey);
                if($workSpaces && is_array($workSpaces)){
                    foreach($workSpaces as $workSpace){
                        if($workSpace['workspaceID']==$savedWorkSpaceId){
                            $workSpaceName=esc_attr($workSpace['name']);
                        }
                    }
                }
                // echo sprintf(esc_html__('You are currently logged into <b>"%s"</b>. Customers from your Woocommerce store will be add into the
                // directory of the above mentioned workspace.','mew'),$workSpaceName);
                    printf(
                        esc_html__( "You are currently logged into %s. Customers from your Woocommerce store will be add into the
                         directory of the above mentioned workspace.", "mew" ),
                         sprintf(
                            '<b>%s</b>',
                            $workSpaceName
                            ),
                    );
                     ?>
                
                </p>
                <p><?php esc_html_e('If you wish to add your customers to a different workspace, kindly logout and connect to a different worspace.','mew')?></p>
                <div style="text-align: right;">
                <a class="button button-primary button-large mew-logout-button" href="<?php echo admin_url( 'admin.php?page=mew-options&tab=logout' );?>"><?php esc_attr_e('Logout','mew')?></a>
               </div>
              
               </div>    
                <?php }?>
              <?php  
        }
        public function mew_api_key(){
            $settings=get_option('mew_settings');
            ?>
        <input type="text" value="<?php echo esc_attr(@$settings['api_key'])?>" class="mew-setting-text" name="mew_settings[api_key]" />
         <p><?php echo sprintf(esc_html__('Find your MuxEmail API Key <a href="%s">here</a>','mew'),'https://app.muxemail.com/#/myprofile')?></p>
            <?php
        }
        public function mew_workspace_id(){
            $settings=get_option('mew_settings');
            ?>
        <input type="text" value="<?php echo esc_attr(@$settings['workspace_id'])?>" class="mew-setting-text" name="mew_settings[workspace_id]" />
        <p><?php echo sprintf(esc_html__('Find your MuxEmail Workspace Id <a href="%s">here</a>','mew'),'https://app.muxemail.com/#/workspaces')?></p>

            <?php
        }
        public  function mew_settings_content_logout()
        {
            global $mew_settings_active_tab;
            if ( 'logout' != $mew_settings_active_tab )
                return;
                $workspaceId=get_option('mew_workspace_id');
                delete_option('mew_start_sync'.$workspaceId);
                as_unschedule_all_actions('mew_import_customer');
                delete_option('mew_workspace_id');
                delete_option('mew_logged_in');
                wp_safe_redirect(admin_url( 'options.php?page=mew_plugin_install' ));
                exit;
        }
        public function mew_settings_content_contacts(){
            global $mew_settings_active_tab;
            if ( 'contacts' != $mew_settings_active_tab )
                return;
               ?>
               <div class="mew-wrap">
                   <div class="mewSyncContentContainer">
                   <div class="pure-g">
                       <div class="pure-u-1 pure-u-md-4-5 pure-u-lg-4-5">
                           <h2><?php esc_html_e('Syncing of contacts','mew')?></h2>
                           <p class="description">
                               <?php
                               $report_data=MEW_Common_Functions::getCustomerObject(WC_Mux_Email::$numberOfCustomerPerPage,1); 
                               $customerCount=0;
                               if($report_data->total > 0){
                               $customerCount=$report_data->total;
                               }
                               $apiKey=get_option('mew_api_key');
                               $workSpaceId=get_option('mew_workspace_id');
                               $allTags=WC_Mux_Email::getMuxEmailGet('getAllTags',$apiKey,$workSpaceId);
                               $allFields=WC_Mux_Email::getMuxEmailGet('getAllSubscriberFields',$apiKey,$workSpaceId);
                               $alreadyImported=get_option('mew_customer_imported_number'.$workSpaceId,0);
                             
                               $customerCount=intval($customerCount)-intval($alreadyImported);
                               $customerCount= $customerCount < 0 ? 0 : $customerCount;
                               $allWoofields=WC_Mux_Email::wooCustomerFields();
                               set_transient('_mew_fields',$allFields,300);
                               $existingAttributes=get_option('mew_field_attributes'.$workSpaceId);
                               $existingTags=get_option('mew_tags'.$workSpaceId,[]);
                               ?>
                               <?php 
                            echo sprintf(esc_html__('You have %s existing customers. Do you want to add them to your MuxEmail workspace?','mew'),$customerCount)
                           
                               ?>
                           </p>
                       </div>
                       <div class="pure-u-1 pure-u-md-1-5 pure-u-lg-1-5">
                           <div class="syncButtonContainer">
                               <?php 
                               $alreadyStarted=get_option('mew_sync_started'.$workSpaceId) ? true : false;
                               $disbleClass=$alreadyStarted && $customerCount==0 ? 'disable' : '';
                               ?>
                                <a class="button button-primary button-large mew-submit-button <?php echo esc_attr($disbleClass) ?>" id="mewSyncContacts">
                                <?php esc_attr_e('Sync my customers','mew')?>
                                    </a>
                                    <p class="description">
                                        <?php esc_html_e('Last sync on','mew')?> 
                                        <?php 
                                        $lastSyncTime=get_option('mew_last_sync'.$workSpaceId);
                                        if(!empty($lastSyncTime)){
                                          
                                            echo date_i18n( get_option('date_format'), $lastSyncTime );
                                          
                                        }
                                        ?>
                                    </p>
                           </div>
                       </div>
                   </div>
                   </div>
                   <div class="mewTagContainer">
                      <div class="pure-g">
                          <div class="pure-u-1 pure-u-md-1-2 pure-u-lg-1-2">
                               <h2><?php esc_html_e('Tags','mew')?></h2>
                               <p class="description">
                                   <?php esc_html_e('Select the tags you want to assign to the contacts coming in via your Woocommerce store.','mew')?>
                               </p>
                          </div>
                          <div class="pure-u-1 pure-u-md-1-2 pure-u-lg-1-2">
                              <div class="mewTagSelectContainer">
                              <select  multiple data-placeholder="<?php esc_attr_e('Select Tags','mew')?>" id="mewTagSelect" name="sbh_settings[available_locations][]">

                                      <?php if(!empty($allTags)){
                                          foreach($allTags as $tag){
                                              $selectedTag=in_array($tag['name'],$existingTags) ? "selected" : "";
                                          ?>
                                          <option <?php echo esc_attr($selectedTag) ?> value="<?php echo esc_attr($tag['name']);?>"><?php echo esc_attr($tag['name'])?></option>
                                      <?php }} ?>
                                  </select>
                              </div>
                          </div>   
                      </div>         
                   </div>
                   <div class="mewAttributeContainer">
                       <h2>
                           <?php esc_html_e('Match Attributes','mew')?>
                       </h2>
                       <p class="description">
                           <?php esc_html_e('By default, MuxEmail imports the email id,first name and last name of your customers. To import more attributes make sure to map them to their correct MuxEmail contact fields.','mew')?>
                       </p>
                       <div id="mewLoader" style="display: none;">
                        <div class="loader"></div>
                         </div>   
                       <div class="pure-g mewAttributeFormFields">
                           <div class="pure-u-1 pure-u-md-1-2">
                              
                               <select class="mew_woo_attributes mew_non_removable_attr" disabled name="mew_woo_attributes[]">
                                <?php foreach($allWoofields as $k0=>$wooField){
                                    $selected=$k0=='email'?'selected':'disabled';
                                    ?>
                                    <option <?php esc_attr($selected) ?> value="<?php echo esc_attr($k0)?>"><?php echo esc_attr($wooField);?></option>
                               <?php } ?>
                               </select>
                           </div>    
                           <div class="pure-u-1 pure-u-md-1-2">
                                    <select class="mew_attributes mew_non_removable_attr" disabled name="mew_attributes[]">
                                        <?php if(!empty($allFields)){
                                            foreach($allFields as $field){
                                                $selected=$field['fieldKey']=='email'?'selected':'disabled';
                                            ?>
                                            <option <?php echo esc_attr($selected)?> value="<?php echo esc_attr($field['fieldKey'])?>"><?php echo isset($field['fieldLabel'])?esc_attr($field['fieldLabel']):esc_attr($field['fieldKey'])?></option>
                                            <?php }}?>
                                    </select>
                           </div>             
                       </div>
                       <div class="pure-g mewAttributeFormFields">
                           <div class="pure-u-1 pure-u-md-1-2">
                               <select class="mew_woo_attributes mew_non_removable_attr" disabled  name="mew_woo_attributes[]">
                                <?php foreach($allWoofields as $k1=>$wooField){
                                    $selected=$k1=='first_name'?'selected':'disabled';
                                    ?>
                                    <option <?php echo esc_attr($selected)?> value="<?php echo esc_attr($k1)?>"><?php echo esc_attr($wooField)?></option>
                               <?php } ?>
                               </select>
                           </div>    
                           <div class="pure-u-1 pure-u-md-1-2">
                                    <select class="mew_attributes mew_non_removable_attr" disabled  name="mew_attributes[]">
                                        <?php if(!empty($allFields)){
                                            foreach($allFields as $field){
                                                $selected=$field['fieldKey']=='fname'?'selected':'disabled';
                                            ?>
                                            <option <?php echo esc_attr($selected) ?> value="<?php echo esc_attr($field['fieldKey']) ?>"><?php echo isset($field['fieldLabel'])?esc_attr($field['fieldLabel']):esc_attr($field['fieldKey']) ?></option>
                                            <?php }}?>
                                    </select>
                           </div>             
                       </div>
                       <div class="pure-g mewAttributeFormFields">
                           <div class="pure-u-1 pure-u-md-1-2">
                               <select class="mew_woo_attributes mew_non_removable_attr" disabled  name="mew_woo_attributes[]">
                                <?php foreach($allWoofields as $k2=>$wooField){
                                    $selected=$k2=='last_name'?'selected':'disabled';
                                    ?>
                                    <option <?php echo esc_attr($selected) ?> value="<?php echo esc_attr($k2) ?>"><?php echo esc_attr($wooField) ?></option>
                               <?php } ?>
                               </select>
                           </div>    
                           <div class="pure-u-1 pure-u-md-1-2">
                                    <select class="mew_attributes mew_non_removable_attr" name="mew_attributes[]" disabled>
                                        <?php if(!empty($allFields)){
                                            foreach($allFields as $field){
                                                $selected=$field['fieldKey']=='lname'?'selected':'disabled';
                                            ?>
                                            <option <?php echo esc_attr($selected) ?> value="<?php echo esc_attr($field['fieldKey']) ?>"><?php echo isset($field['fieldLabel'])?esc_attr($field['fieldLabel']):esc_attr($field['fieldKey'])?></option>
                                            <?php }}?>
                                    </select>
                                    <?php if(empty($existingAttributes)){ ?>
                                   <div id="mewMapNewAttributesButtonContainer"><a id="mewMapNewAttributes" class="mew-new-field-button" href="#"><span class="dashicons dashicons-plus-alt"></span></a></div> 
                                <?php }?>
                           </div>             
                       </div>
                       <div id="mewNewMapAttributes">
                            <?php 
                          
                         
                          if(!empty($existingAttributes)){
                              $ignoreAttrs=['email','first_name','last_name'];
                              $ignoreMAttrs=['email','fname','lname'];
                              $disabledWooAttrs=[];
                              $disabledMewAttrs=[];
                              foreach($existingAttributes as $k=>$existingAttribute){
                                if(in_array($k,$ignoreAttrs))continue;
                                ?>
                                 <div class="pure-g mewAttributeFormFields">
                                    <div class="pure-u-1 pure-u-md-1-2">
                                    <select class="mew_woo_attributes"  name="mew_woo_attributes[]">
                                    <?php foreach($allWoofields as $key=>$wooField){ 
                                        $selected= $k==$key ? 'selected' : '';
                                        $disabled=in_array($key,$disabledWooAttrs) || in_array($key,$ignoreAttrs) ? "disabled" : "";
                                        if($k==$key){
                                        array_push($disabledWooAttrs,$key);
                                        }
                                        ?>
                                        <option <?php echo esc_attr($disabled) ?>  <?php echo esc_attr($selected) ?> value="<?php echo esc_attr($key) ?>"><?php echo esc_attr($wooField) ?></option>
                                     <?php }?>   
                                    </select>   
                                    </div>  
                                    <div class="pure-u-1 pure-u-md-1-2">
                                        <select class="mew_attributes"  name="mew_attributes[]">
                                        <?php if(!empty($allFields)){
                                              foreach($allFields as $field){
                                                  $selected=$field['fieldKey']==$existingAttribute ? "selected":"";
                                                  $disabled=in_array($field['fieldKey'],$disabledMewAttrs) || in_array($field['fieldKey'],$ignoreMAttrs) ? "disabled" : "";

                                                  if($field['fieldKey']==$existingAttribute){
                                                      array_push($disabledMewAttrs,$field['fieldKey']);
                                                  }
                                            ?>
                            <option <?php echo esc_attr($disabled) ?> <?php echo esc_attr($selected) ?> value="<?php echo esc_attr($field['fieldKey']) ?>"><?php echo isset($field['fieldLabel'])?esc_attr($field['fieldLabel']):esc_attr($field['fieldKey']) ?></option>

                                         <?php }}?>   
                                        </select>   
                                        <div class="mewRemoveAttributesButtonContainer"><a class="mew-new-field-button mew_remove_attr" href="#"><span class="dashicons dashicons-remove"></span></a></div> 

                                    </div>     
                                 </div>   
                                <?php
                              }
                          }
                            ?>
                       </div>
                      
                    

                   </div>
                   <div style="text-align: right;margin-top:10px">
                   <a id="mewApplySetting" title="<?php esc_attr_e('Map new attribute','mew')?>" href="#" class="button button-primary button-large mew-submit-button"><?php esc_attr_e('Apply','mew')?></a>
                       
                    </div>
               </div>
               <?php
        }
    }
    new MEW_Admin_Settings();
}