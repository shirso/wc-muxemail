<?php
/*
Plugin Name: WooCommerce - MuxEmail Add-on
Plugin URI: https://muxemail.com/muxemail-email-marketing
Description: MuxEmail client for Woocommerce
Version: 1.0.0
Requires at least: 4.5
Tested up to: 5.8.2
WC requires at least: 3.5
WC tested up to: 5.9.0
Author: MuxEmail
Author URI: https://muxemail.com
Text Domain: mew
Domain Path: /languages/
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly
if (!defined('MEW_PLUGIN_DIR'))
    define( 'MEW_PLUGIN_DIR', dirname(__FILE__) );
if (!defined('MEW_PLUGIN_ROOT_PHP'))
    define( 'MEW_PLUGIN_ROOT_PHP', dirname(__FILE__).'/'.basename(__FILE__)  );
if(!defined('MEW_PLUGIN_ABSOLUTE_PATH'))
    define('MEW_PLUGIN_ABSOLUTE_PATH',plugin_dir_url(__FILE__));
if (!defined('MEW_PLUGIN_ADMIN_DIR'))
    define( 'MEW_PLUGIN_ADMIN_DIR', dirname(__FILE__) . '/admin' );

if( !class_exists('WC_Mux_Email') ) {
    class WC_Mux_Email{
        public static $apiEndPoint='https://apis.muxemail.com/v1/';
        public static  $numberOfCustomerPerPage=25;
        public function __construct() {

            if ( in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) {
                require_once( MEW_PLUGIN_DIR.'/library/action-scheduler/action-scheduler.php' );
                require_once(MEW_PLUGIN_ADMIN_DIR.'/class-admin.php');
                require_once(MEW_PLUGIN_DIR.'/inc/class-common.php');
                add_filter('plugin_action_links_'.plugin_basename(__FILE__), [&$this,'add_setting_link']);

            }else{
                add_action( 'admin_notices',[&$this,'add_notice_for_woocommerce']);
            }
            add_action('init',[&$this,'load_mew_language_file']);
        }
       public function load_mew_language_file(){
        load_plugin_textdomain( 'mew', false, basename( dirname( __FILE__ ) ) . '/languages' );
       }
        public function add_setting_link($links){
            array_unshift($links, '<a href="' .
                admin_url( 'admin.php?page=mew-options' ) .
                '">' . esc_attr__('Settings','mew') . '</a>');
            return $links;
        }
        public function add_notice_for_woocommerce(){
            ?>
            <div class="notice notice-error">
                <p><strong><?php esc_attr_e( 'MuxEmail Email Marketing requires the WooCommerce plugin to be installed and active.', 'mew'); ?></strong></p>
            </div>
            <?php
        }
        public static function getMuxEmailGet($type,$apiKey,$workSpaceId=null){
            $url=self::$apiEndPoint.$type;
            $headrs=[];
            $headrs=[
                'Content-Type'  => 'application/json',
                'apikey' => $apiKey,
            ];
            if(!empty($workSpaceId)){
               $headrs['workspaceid']=$workSpaceId;
            }
            $response = wp_safe_remote_post(
                $url,
                [
                    'headers'     => $headrs,
                    'method'      => 'GET',
                ]
            );
            if(!is_wp_error($response)){
                $retriveBody=wp_remote_retrieve_body($response);
                $retriveBodyArray=json_decode($retriveBody,true);
                if(!empty($retriveBodyArray) && is_array($retriveBodyArray)){
                  
                   if(isset($retriveBodyArray['error'])){
                       return false;
                   }
                   return $retriveBodyArray;
                }
            }
            return false;
        }
        public static function postMuxEmailServer($type,$apiKey,$workSpaceId,$payload=[],$method='POST'){
            $url=self::$apiEndPoint.$type;
            $response = wp_safe_remote_post(
                $url,
                [
                    'headers'     => [
                        'Content-Type'  => 'application/json',
                        'apikey' => $apiKey,
                        'workspaceid'=>$workSpaceId
                    ],
                    'body'        => wp_json_encode($payload),
                    'method'      => $method,
                    'data_format' => 'body',
                ]
            );
            if(!is_wp_error($response)){
                $retriveBody=wp_remote_retrieve_body($response);
                $retriveBodyArray=json_decode($retriveBody,true);
                if(in_array('error',array_keys($retriveBodyArray))){
                    return false;
                }else{
                    return true;
                }

            }
            return false;
        } 
       
       public static function wooCustomerFields(){
        $wooCustomerFields =[
             'email' =>esc_attr__('Email','mew'),
             'first_name'=>esc_attr__('First Name','mew'),
             'last_name'=>esc_attr__('Last Name','mew'),
             'billing_first_name'=>esc_attr__('Billing First Name','mew'),
             'billing_last_name'=>esc_attr__('Billing Last Name','mew'),
             'billing_company'=>esc_attr__('Billing Company','mew'),
             'billing_address_1'=>esc_attr__('Billing Address line 1','mew'),
             'billing_address_2'=>esc_attr__('Billing Address line 2','mew'),
             'billing_city'=>esc_attr__('Billing City','mew'),
             'billing_state'=>esc_attr__('Billing State','mew'),
             'billing_postcode'=>esc_attr__('Billing Post Code','mew'),
             'billing_country'=>esc_attr__('Billing Country','mew'),
             'billing_email'=>esc_attr__('Billing Email','mew'),
             'billing_phone'=>esc_attr__('Billing Phone','mew'),
             'shipping_first_name'=>esc_attr__('Shipping First Name','mew'),
             'shipping_last_name'=>esc_attr__('Shipping Last Name','mew'),
             'shipping_company'=>esc_attr__('Shipping Company','mew'),
             'shipping_address_1'=>esc_attr__('Shipping Address line 1','mew'),
             'shipping_address_2'=>esc_attr__('Shipping Address line 2','mew'),
             'shipping_city'=>esc_attr__('Shipping City','mew'),
             'shipping_state'=>esc_attr__('Shipping State','mew'),
             'shipping_postcode'=>esc_attr__('Shipping Post Code','mew'),
             'shipping_country'=>esc_attr__('Shipping Country','mew'),
             'total_order_count'=>esc_attr__('Total Order Count','mew'),
             'total_spend'=>esc_attr__('Total Spend','mew'),
             'aov'=>esc_attr__('AOV','mew'),
             'is_paying_customer'=>esc_attr__('Is Paying Customer','mew')
         ];
         return $wooCustomerFields;
       }
    }
    new WC_Mux_Email();
    
}