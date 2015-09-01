<?php
/**
 * 
 * Plugin Name: Amazon Publisher API
 * Plugin URI: http://github.com/parickingle/amazon-publisher-api
 * Description: Populate products from Amazon affiliates categories/products
 * Version: 1.1
 * Author: Patrick Ingle
 * Author URI: http://github.com/patrickingle
 * 
 **/

 
class AmazonPublisherAPI {
	
	public function __construct() {
		
	}
}

if (!function_exists('awspa_activate')) {
	function awspa_activate() {
		if (awspa_is_plugin_active('ready-ecommerce/ecommerce.php')) {
			if (defined('S_PLUGIN_INSTALLED') && defined('S_VERSION')) {
				$plugin_info = get_plugin_data(ABSPATH.'wp-content/plugins/ready-ecommerce/ecommerce.php');
				if (S_VERSION >= '0.4.0.9' && S_VERSION == $plugin_info['Version'] && S_PLUGIN_INSTALLED == true) {
					//include(dirname(__FILE__).'/admin/admin.php');
					//add_action('admin_menu', 'msp_helloworld_admin_menu_setup');
					update_option('awspa_ecommerce','ready-ecommerce');
				} else {
					update_option('awspa_ecommerce','undefined');
					deactivate_plugins(basename(__FILE__));
					wp_die("Requires Ready! Ecommerce plugin version 0.4.0.9");
				}
			} else {
				update_option('awspa_ecommerce','undefined');
				deactivate_plugins(basename(__FILE__));
				wp_die("Requires Ready! Ecommerce plugin version 0.4.0.9");
			}
		} elseif (awspa_is_plugin_active('woocommerce/woocommerce.php')) {
			if (class_exists( 'WooCommerce' )) {
				if (WC_VERSION >= '2.4.6') {
					update_option('awspa_ecommerce','woocommerce');					
				} else {
					update_option('awspa_ecommerce','undefined');
					deactivate_plugins(basename(__FILE__));
					wp_die("Requires WooCommerce version 2.4.6");
				}
			} else {
				update_option('awspa_ecommerce','undefined');
				deactivate_plugins(basename(__FILE__));
				wp_die("Requires WooCommerce version 2.4.6");
			}
		} else {
			deactivate_plugins(basename(__FILE__));	
			wp_die("Requires Ready! Ecommerce plugin version 0.4.0.9 or WooCommerce version 2.4.6");
		}
	}
}

function awspa_plugin_menu() {
    add_options_page('Amazon Publisher API', 'Amazon Publisher API', 8, 'awspa', 'awspa_plugin_options');
	
}

function awspa_plugin_options() {
	include (dirname(__FILE__).'/admin/admin.php');
}

function awspa_is_plugin_active($plugin) {
	$active_plugins = (array)get_option( 'active_plugins', array() );
	if (in_array($plugin,$active_plugins)) return TRUE;
	return FALSE;	
}

register_activation_hook(__FILE__,'awspa_activate');

/* settings link in plugin management screen */
function awspa_settings_link($actions, $file) {
if(false !== strpos($file, 'publisher'))
 $actions['settings'] = '<a href="options-general.php?page=awspa">Settings</a>';
return $actions; 
}
add_filter('plugin_action_links', 'awspa_settings_link', 2, 2);
add_action( 'admin_menu', 'awspa_plugin_menu' );

?>
