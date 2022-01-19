<?php
defined( 'ABSPATH' ) or exit;
use Automattic\WooCommerce\Admin\API\Reports\Customers;
if ( ! class_exists( 'MEW_Common_Functions' ) ) {
    class MEW_Common_Functions{
        const MAX_QUERY_DEPTH = 10;
        public static function parse($string, &$result)
        {
            if ($string === '') return;
    
            $vars = explode('&', $string);
    
            if (false === is_array($result))
            {
                $result = [];
            }
    
            foreach ($vars as $var)
            {
                if (false === ($eqPos = strpos($var, '='))) {
                    continue;
                }
    
                $key = substr($var, 0, $eqPos);
                $value = urldecode(substr($var, $eqPos+1));
    
                static::setQueryArrayValue($key, $result, $value);
            }
        }
        private static function setQueryArrayValue($path, &$array, $value, $depth = 0)
        {
            if ($depth > static::MAX_QUERY_DEPTH) return;
    
            if (false === ($arraySignPos = strpos($path, '[')))
            {
                $array[$path] = $value;
                return;
            }
    
            $key = substr($path, 0, $arraySignPos);
    
            $arrayESignPos = strpos($path, ']', $arraySignPos);
    
            if (false === $arrayESignPos) return;
    
            $subkey = substr($path, $arraySignPos + 1, $arrayESignPos - $arraySignPos - 1);
    
            if (empty($array[$key]) || !is_array($array[$key])) $array[$key] = [];
    
            if ($subkey != '')
            {
                $right = substr($path, $arrayESignPos + 1);
    
                if ('[' !== substr($right, 0, 1)) $right = '';
    
                static::setQueryArrayValue($subkey.$right, $array[$key], $value, $depth + 1);
                return;
            }
    
            $array[esc_attr($key)][] = sanitize_text_field($value);
    
        }
        public static function array_sanitize($arr=[]){
           return array_map( [ __CLASS__, 'sanitizi_text_field' ], $arr );
        }
        public static function sanitizi_text_field($n){
            return sanitize_text_field($n);
        }
        public static function getCustomerObject($numberOfCustomer,$pageNumber){
            $resquest=[
                'per_page'=>$numberOfCustomer,
                'page'=>$pageNumber,
                'order'=>'desc',
                'orderby'=>'date_last_active',
                'fields'=>'*',
                'registered_before'=>null,
                'registered_after'=>null,
                'order_before' => null,
                'order_after' => null,
                'match' => 'all',
                'search' => null,
                'searchby' => 'name',
                'name_includes' => null,
                'name_excludes' => null,
                'username_includes' => null,
                'username_excludes' => null,
                'email_includes' => null,
                'email_excludes' => null,
                'country_includes' => null,
                'country_excludes' => null,
                'last_active_before' => null,
                'last_active_after' => null,
                'orders_count_min' => null,
                'orders_count_max' => null,
                'total_spend_min' => null,
                'total_spend_max' => null,
                'avg_order_value_min' => null,
                'avg_order_value_max' => null,
                'last_order_before' => null,
                'last_order_after' => null,
                'customers' => null
           ];
           $customerQuery=new Automattic\WooCommerce\Admin\API\Reports\Customers\Query($resquest);
           $report_data     = $customerQuery->get_data();
           return $report_data;
        }
        public static function getIndividualCustomer($customerId){
            $resquest=[
                'per_page'=>1,
                'page'=>1,
                'order'=>'desc',
                'orderby'=>'date_last_active',
                'fields'=>'*',
                'registered_before'=>null,
                'registered_after'=>null,
                'order_before' => null,
                'order_after' => null,
                'match' => 'all',
                'search' => null,
                'searchby' => 'name',
                'name_includes' => null,
                'name_excludes' => null,
                'username_includes' => null,
                'username_excludes' => null,
                'email_includes' => null,
                'email_excludes' => null,
                'country_includes' => null,
                'country_excludes' => null,
                'last_active_before' => null,
                'last_active_after' => null,
                'orders_count_min' => null,
                'orders_count_max' => null,
                'total_spend_min' => null,
                'total_spend_max' => null,
                'avg_order_value_min' => null,
                'avg_order_value_max' => null,
                'last_order_before' => null,
                'last_order_after' => null,
                'customers' => [$customerId]
           ];
           $customerQuery=new Automattic\WooCommerce\Admin\API\Reports\Customers\Query($resquest);
           $report_data     = $customerQuery->get_data();
           return $report_data;
        }
        public static function customerLastOrder($customerId){
            $customerDataStore=new Automattic\WooCommerce\Admin\API\Reports\Customers\DataStore();
            if(!empty($customerDataStore->get_last_order($customerId))){
                return $customerDataStore->get_last_order($customerId)->get_data();
            }
            return false;
          
        }
        
        public static function getFirstNameLastName($name){
            $name = trim($name);
            $last_name = (strpos($name, ' ') === false) ? '' : preg_replace('#.*\s([\w-]*)$#', '$1', $name);
            $first_name = trim( preg_replace('#'.preg_quote($last_name,'#').'#', '', $name ) );
            return array($first_name, $last_name);
        
        }
        public static function createCustomerData($customerId,$customerData,$isSync=false){
          
            $returnArray=[];
            $workspaceId=get_option('mew_workspace_id');
            $existingAttributes=get_option('mew_field_attributes'.$workspaceId);
            if(!empty($existingAttributes)){
                $name=$customerData['name'];
                $nameSplit=self::getFirstNameLastName($name);
                $lastOrder=self::customerLastOrder($customerId);
                if(!$lastOrder) return [];
                foreach($existingAttributes as $wooAttr=>$MewAttr){
                    if(!$isSync && strpos($MewAttr,'custom.') > -1){
                      continue;
                    }
                    switch($wooAttr){
                        case 'email' :
                            $returnArray[$MewAttr]=$customerData['email'];
                            break;
                        case 'first_name':
                            $returnArray[$MewAttr]=$nameSplit[0];
                            break;
                        case 'last_name':    
                            $returnArray[$MewAttr]=$nameSplit[1];
                            break;
                        case 'billing_first_name':
                            $returnArray[$MewAttr]=!empty($lastOrder['billing']['first_name'])?$lastOrder['billing']['first_name']:'';
                            break;
                        case 'billing_last_name':
                            $returnArray[$MewAttr]=!empty($lastOrder['billing']['last_name'])?$lastOrder['billing']['last_name']:'';
                            break;    
                        case 'billing_company':
                            $returnArray[$MewAttr]=!empty($lastOrder['billing']['company'])?$lastOrder['billing']['company']:'';
                            break;   
                        case 'billing_address_1':
                            $returnArray[$MewAttr]=!empty($lastOrder['billing']['address_1'])?$lastOrder['billing']['address_1']:'';
                            break;    
                        case 'billing_address_2':
                            $returnArray[$MewAttr]=!empty($lastOrder['billing']['address_2'])?$lastOrder['billing']['address_2']:'';
                            break;  
                        case 'billing_city':
                            $returnArray[$MewAttr]=!empty($lastOrder['billing']['city'])?$lastOrder['billing']['city']:'';
                            break;   
                        case 'billing_state':
                            $returnArray[$MewAttr]=!empty($lastOrder['billing']['state'])?$lastOrder['billing']['state']:'';
                            break;  
                        case 'billing_postcode':
                            $returnArray[$MewAttr]=!empty($lastOrder['billing']['postcode'])?$lastOrder['billing']['postcode']:'';
                            break;    
                        case 'billing_country':
                            $returnArray[$MewAttr]=!empty($lastOrder['billing']['country'])?$lastOrder['billing']['country']:'';
                            break;      
                        case 'billing_email':
                            $returnArray[$MewAttr]=!empty($lastOrder['billing']['email'])?$lastOrder['billing']['country']:'';
                            break;  
                        case 'billing_phone':
                            $returnArray[$MewAttr]=!empty($lastOrder['billing']['phone'])?$lastOrder['billing']['phone']:'';
                            break;    
                            case 'shipping_first_name':
                                $returnArray[$MewAttr]=!empty($lastOrder['shipping']['first_name'])?$lastOrder['shipping']['first_name']:'';
                                break;
                            case 'shipping_last_name':
                                $returnArray[$MewAttr]=!empty($lastOrder['shipping']['last_name'])?$lastOrder['shipping']['last_name']:'';
                                break;    
                            case 'shipping_company':
                                $returnArray[$MewAttr]=!empty($lastOrder['shipping']['company'])?$lastOrder['shipping']['company']:'';
                                break;   
                            case 'shipping_address_1':
                                $returnArray[$MewAttr]=!empty($lastOrder['shipping']['address_1'])?$lastOrder['shipping']['address_1']:'';
                                break;    
                            case 'shipping_address_2':
                                $returnArray[$MewAttr]=!empty($lastOrder['shipping']['address_2'])?$lastOrder['shipping']['address_2']:'';
                                break;  
                            case 'shipping_city':
                                $returnArray[$MewAttr]=!empty($lastOrder['shipping']['city'])?$lastOrder['shipping']['city']:'';
                                break;   
                            case 'shipping_state':
                                $returnArray[$MewAttr]=!empty($lastOrder['shipping']['state'])?$lastOrder['shipping']['state']:'';
                                break;  
                            case 'shipping_postcode':
                                $returnArray[$MewAttr]=!empty($lastOrder['shipping']['postcode'])?$lastOrder['shipping']['postcode']:'';
                                break;    
                            case 'shipping_country':
                                $returnArray[$MewAttr]=!empty($lastOrder['shipping']['country'])?$lastOrder['shipping']['country']:'';
                                break;
                            case 'shipping_phone':
                                $returnArray[$MewAttr]=!empty($lastOrder['shipping']['phone'])?$lastOrder['shipping']['phone']:'';
                                break; 
                            case 'total_order_count':
                                $returnArray[$MewAttr]=!empty($customerData['orders_count'])?$customerData['orders_count']:0;
                                break;
                            case 'total_spend':
                                $returnArray[$MewAttr]=!empty($customerData['total_spend'])? $customerData['total_spend'] :0;
                                break;   
                            case 'aov':
                                $returnArray[$MewAttr]=!empty($customerData['avg_order_value'])? $customerData['avg_order_value'] :0;
                                break;    
                             case 'is_paying_customer':
                                $customerClass=new WC_Customer($customerId);
                                $returnArray[$MewAttr]=$customerClass->get_is_paying_customer()?$customerClass->get_is_paying_customer():'false';
                                break;                                      
                    }
                }
                return $returnArray;
            }
        }
        public static function createCustomerCustomData($customerId,$customerData){
            
            $returnArray=[];
            $workspaceId=get_option('mew_workspace_id');
            $existingAttributes=get_option('mew_field_attributes'.$workspaceId);
            if(!empty($existingAttributes)){
                $name=$customerData['name'];
                $nameSplit=self::getFirstNameLastName($name);
                $lastOrder=self::customerLastOrder($customerId);
                if(!$lastOrder) return [];
                foreach($existingAttributes as $wooAttr=>$MewAttr){
                    if(strpos($MewAttr,'custom.')  === false){
                      continue;
                    }
                    $MewAttr=str_replace('custom.','',$MewAttr);
                    switch($wooAttr){
                        case 'email' :
                            $returnArray[$MewAttr]=$customerData['email'];
                            break;
                        case 'first_name':
                            $returnArray[$MewAttr]=$nameSplit[0];
                            break;
                        case 'last_name':    
                            $returnArray[$MewAttr]=$nameSplit[1];
                            break;
                        case 'billing_first_name':
                            $returnArray[$MewAttr]=!empty($lastOrder['billing']['first_name'])?$lastOrder['billing']['first_name']:'';
                            break;
                        case 'billing_last_name':
                            $returnArray[$MewAttr]=!empty($lastOrder['billing']['last_name'])?$lastOrder['billing']['last_name']:'';
                            break;    
                        case 'billing_company':
                            $returnArray[$MewAttr]=!empty($lastOrder['billing']['company'])?$lastOrder['billing']['company']:'';
                            break;   
                        case 'billing_address_1':
                            $returnArray[$MewAttr]=!empty($lastOrder['billing']['address_1'])?$lastOrder['billing']['address_1']:'';
                            break;    
                        case 'billing_address_2':
                            $returnArray[$MewAttr]=!empty($lastOrder['billing']['address_2'])?$lastOrder['billing']['address_2']:'';
                            break;  
                        case 'billing_city':
                            $returnArray[$MewAttr]=!empty($lastOrder['billing']['city'])?$lastOrder['billing']['city']:'';
                            break;   
                        case 'billing_state':
                            $returnArray[$MewAttr]=!empty($lastOrder['billing']['state'])?$lastOrder['billing']['state']:'';
                            break;  
                        case 'billing_postcode':
                            $returnArray[$MewAttr]=!empty($lastOrder['billing']['postcode'])?$lastOrder['billing']['postcode']:'';
                            break;    
                        case 'billing_country':
                            $returnArray[$MewAttr]=!empty($lastOrder['billing']['country'])?$lastOrder['billing']['country']:'';
                            break;      
                        case 'billing_email':
                            $returnArray[$MewAttr]=!empty($lastOrder['billing']['email'])?$lastOrder['billing']['country']:'';
                            break;  
                        case 'billing_phone':
                            $returnArray[$MewAttr]=!empty($lastOrder['billing']['phone'])?$lastOrder['billing']['phone']:'';
                            break;    
                            case 'shipping_first_name':
                                $returnArray[$MewAttr]=!empty($lastOrder['shipping']['first_name'])?$lastOrder['shipping']['first_name']:'';
                                break;
                            case 'shipping_last_name':
                                $returnArray[$MewAttr]=!empty($lastOrder['shipping']['last_name'])?$lastOrder['shipping']['last_name']:'';
                                break;    
                            case 'shipping_company':
                                $returnArray[$MewAttr]=!empty($lastOrder['shipping']['company'])?$lastOrder['shipping']['company']:'';
                                break;   
                            case 'shipping_address_1':
                                $returnArray[$MewAttr]=!empty($lastOrder['shipping']['address_1'])?$lastOrder['shipping']['address_1']:'';
                                break;    
                            case 'shipping_address_2':
                                $returnArray[$MewAttr]=!empty($lastOrder['shipping']['address_2'])?$lastOrder['shipping']['address_2']:'';
                                break;  
                            case 'shipping_city':
                                $returnArray[$MewAttr]=!empty($lastOrder['shipping']['city'])?$lastOrder['shipping']['city']:'';
                                break;   
                            case 'shipping_state':
                                $returnArray[$MewAttr]=!empty($lastOrder['shipping']['state'])?$lastOrder['shipping']['state']:'';
                                break;  
                            case 'shipping_postcode':
                                $returnArray[$MewAttr]=!empty($lastOrder['shipping']['postcode'])?$lastOrder['shipping']['postcode']:'';
                                break;    
                            case 'shipping_country':
                                $returnArray[$MewAttr]=!empty($lastOrder['shipping']['country'])?$lastOrder['shipping']['country']:'';
                                break;
                            case 'shipping_phone':
                                $returnArray[$MewAttr]=!empty($lastOrder['shipping']['phone'])?$lastOrder['shipping']['phone']:'';
                                break; 
                            case 'total_order_count':
                                $returnArray[$MewAttr]=!empty($customerData['orders_count'])?$customerData['orders_count']:0;
                                break;
                            case 'total_spend':
                                $returnArray[$MewAttr]=!empty($customerData['total_spend'])? $customerData['total_spend'] :0;
                                break;   
                            case 'aov':
                                $returnArray[$MewAttr]=!empty($customerData['avg_order_value'])? $customerData['avg_order_value'] :0;
                                break;    
                             case 'is_paying_customer':
                                $customerClass=new WC_Customer($customerId);
                                $returnArray[$MewAttr]=$customerClass->get_is_paying_customer()?$customerClass->get_is_paying_customer():'false';
                                break;                                      
                    }
                }
                return $returnArray;
            }
        }
    }
}