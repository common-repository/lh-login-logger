<?php
/**
 * Plugin Name: LH Login Logger
 * Plugin URI: https://lhero.org/portfolio/lh-login-logger/
 * Description:Keeps track of the last login by  each user and how many times they have logged in
 * Version: 1.03
 * Author: Peter Shaw
 * Author URI: https://shawfactor.com
*/

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly


if (!class_exists('LH_Login_logger_plugin')) {

class LH_Login_logger_plugin {

private static $instance;

static function return_plugin_namespace(){

return 'lh_login_logger';

}


	/**
	 * Update the login timestamp.
	 *
	 * @access public
	 *
	 * @param  string $user_login The user's login name.
	 *
	 * @return void
	 */
	public function wp_login( $user_login, $user ) {

global $wpdb;

		$user = get_user_by( 'login', $user_login );
		update_user_option( $user->ID, self::return_plugin_namespace().'-local-last_login', time() );
		update_user_meta( $user->ID, self::return_plugin_namespace().'-global-last_login', time() );
		
		update_user_meta($user->ID, self::return_plugin_namespace().'_current_ip', $_SERVER['REMOTE_ADDR']);

$global_number = (int)get_user_meta( $user->ID, self::return_plugin_namespace().'-global-login_number', true);

if (isset($global_number)){

$global_number = $global_number + 1;

update_user_meta( $user->ID, self::return_plugin_namespace().'-global-login_number', $global_number);

} else {

update_user_meta( $user->ID, self::return_plugin_namespace().'-global-login_number', '1');


}


$local_number = (int)get_user_meta( $user->ID, $wpdb->prefix.self::return_plugin_namespace().'-local-login_number', true);

if (isset($local_number)){

$local_number = $local_number + 1;

update_user_option( $user->ID, self::return_plugin_namespace().'-local-login_number', $local_number);

} else {

update_user_option( $user->ID, self::return_plugin_namespace().'-local-login_number', '1');


}



	}


	/**
	 * Adds the last login column to the user list.
	 *
	 * @access public
	 *
	 * @param  array $cols The default columns.
	 *
	 * @return array
	 */
	public function add_columns( $cols ) {
	    
	    

		$cols[ self::return_plugin_namespace().'-local-last_login' ] = __( 'Last Login', self::return_plugin_namespace() );
		$cols[ self::return_plugin_namespace().'-local-login_number' ] = __( 'Login Number', self::return_plugin_namespace() );
		$cols[ self::return_plugin_namespace().'-local-has_logged_in' ] = __( 'Has Logged In', self::return_plugin_namespace() );
		
		return $cols;
	}


	/**
	 * Adds the last login column to the network user list.
	 *
	 * @access public
	 *
	 * @param  array $cols The default columns.
	 *
	 * @return array
	 */
	public function add_network_columns( $cols ) {

		$cols[ self::return_plugin_namespace().'-global-last_login' ] = __( 'Last Login', self::return_plugin_namespace());
		$cols[ self::return_plugin_namespace().'-global-login_number' ] =  __( 'Login Number', self::return_plugin_namespace() );
		$cols[ self::return_plugin_namespace().'-global-has_logged_in' ] =  __( 'Has Logged In', self::return_plugin_namespace() );
		return $cols;
	}




	/**
	 * Handle ordering by last login.
	 *
	 * @access public
	 *
	 * @param  WP_User_Query $user_query Request arguments.
	 *
	 * @return WP_User_Query
	 */
	public function pre_get_users( $user_query ) {

global $wpdb;
		if ( isset( $user_query->query_vars['orderby'] ) && self::return_plugin_namespace().'-local-last_login' == $user_query->query_vars['orderby'] ) {
			$user_query->query_vars = array_merge( $user_query->query_vars, array(
				'meta_key' => $wpdb->prefix.self::return_plugin_namespace().'-local-last_login',
				'orderby'  => 'meta_value_num',
			) );
		} elseif ( isset( $user_query->query_vars['orderby'] ) && self::return_plugin_namespace().'-global-last_login' == $user_query->query_vars['orderby'] ) {
			$user_query->query_vars = array_merge( $user_query->query_vars, array(
				'meta_key' => self::return_plugin_namespace().'-global-last_login',
				'orderby'  => 'meta_value_num',
			) );
		} elseif ( isset( $user_query->query_vars['orderby'] ) && self::return_plugin_namespace().'-local-login_number' == $user_query->query_vars['orderby'] ) {
			$user_query->query_vars = array_merge( $user_query->query_vars, array(
				'meta_key' => $wpdb->prefix.self::return_plugin_namespace().'-local-login_number',
				'orderby'  => 'meta_value_num',
			) );
		} elseif ( isset( $user_query->query_vars['orderby'] ) && self::return_plugin_namespace().'-global-login_number' == $user_query->query_vars['orderby'] ) {
			$user_query->query_vars = array_merge( $user_query->query_vars, array(
				'meta_key' => self::return_plugin_namespace().'-global-login_number',
				'orderby'  => 'meta_value_num',
			) );
		}

		return $user_query;
	}



public function add_sortable_columns($sortable_columns){
  $sortable_columns[ self::return_plugin_namespace().'-local-last_login' ] = self::return_plugin_namespace().'-local-last_login';
  $sortable_columns[ self::return_plugin_namespace().'-local-login_number' ] = self::return_plugin_namespace().'-local-login_number';
  return $sortable_columns;
}


public function add_network_sortable_columns($sortable_columns){
  $sortable_columns[ self::return_plugin_namespace().'-global-last_login' ] = self::return_plugin_namespace().'-global-last_login';
  $sortable_columns[ self::return_plugin_namespace().'-global-login_number' ] = self::return_plugin_namespace().'-global-login_number';
  return $sortable_columns;
}

public function show_columns_content($value, $column_name, $user_id) {
global $wpdb;

$format = apply_filters( 'lh_login_logger_date_format', get_option( 'date_format' ) );

if ( self::return_plugin_namespace().'-local-last_login' == $column_name ) {

			$value      = __( 'Never.', self::return_plugin_namespace() );
			$last_login = (int) get_user_meta( $user_id, $wpdb->prefix.self::return_plugin_namespace().'-local-last_login', true);
//

			if ( isset($last_login) and ($last_login > 0) ) {
				
				$value  = date_i18n( $format, $last_login );
			}
		} elseif ( self::return_plugin_namespace().'-global-last_login' == $column_name ) {

			$value      = __( 'Never.', self::return_plugin_namespace() );
	$meta = get_user_meta( $user_id, self::return_plugin_namespace().'-global-last_login', true);

if (isset($meta) and is_numeric($meta)){

$last_login = (int) $meta;

$format = apply_filters( 'lh_login_logger_date_format', get_option( 'date_format' ) );
$value  = date_i18n( $format, $last_login );


} else {

$value      = __( 'Never.', self::return_plugin_namespace() );



}

} elseif ( self::return_plugin_namespace().'-local-login_number' == $column_name ) {

			$value      = __( 'None.', self::return_plugin_namespace() );
			$login_number = (int) get_user_meta( $user_id, $wpdb->prefix.self::return_plugin_namespace().'-local-login_number', true);

			if ( isset($login_number) and ($login_number > 0) ) {
				$value  = $login_number;
			}




} elseif ( self::return_plugin_namespace().'-local-has_logged_in' == $column_name ) {

			$value      = __( 'No', self::return_plugin_namespace() );
			$login_number = (int) get_user_meta( $user_id, $wpdb->prefix.self::return_plugin_namespace().'-local-login_number', true);

			if ( isset($login_number) and ($login_number > 0) ) {
				$value  = __( 'Yes', self::return_plugin_namespace() );
			}




} elseif ( self::return_plugin_namespace().'-global-login_number' == $column_name ) {

			$value      = __( 'None.', self::return_plugin_namespace() );
			$login_number = (int) get_user_meta( $user_id, self::return_plugin_namespace().'-global-login_number', true);

			if ( isset($login_number) and ($login_number > 0) ) {
				$value  = $login_number;
			}




} elseif ( self::return_plugin_namespace().'-local-has_logged_in' == $column_name ) {

			$value      = __( 'No', self::return_plugin_namespace() );
			$login_number = (int) get_user_meta( $user_id, self::return_plugin_namespace().'-global-login_number', true);

			if ( isset($login_number) and ($login_number > 0) ) {
				$value  = __( 'Yes', self::return_plugin_namespace() );
			}




}


return $value;

}


public function show_network_columns_content($value, $column_name, $user_id) {


		$value = $user_id;

    return $value;
}

function lh_login_logger_local_number_test_shortcode_output($atts, $content = '') {

global $wpdb;

    // define attributes and their defaults
    extract( shortcode_atts( array (
        'login_threshold' => 1
    ), $atts ) );

if ($user = wp_get_current_user()){

$login_number = (int)get_user_meta( $user->ID, $wpdb->prefix.self::return_plugin_namespace().'-local-login_number', true);


if (isset($login_number) and ($login_number <= $login_threshold)){


return do_shortcode($content);


}

}


}

function lh_login_logger_global_number_test_shortcode_output($atts, $content = '') {


    // define attributes and their defaults
    extract( shortcode_atts( array (
        'login_threshold' => 1
    ), $atts ) );

if ($user = wp_get_current_user()){

$login_number = (int) get_user_meta( $user->ID, self::return_plugin_namespace().'-global-login_number', true);

if (isset($login_number) and ($login_number <= $login_threshold)){


return do_shortcode($content);


}


}


}



public function register_shortcodes(){

add_shortcode('lh_login_logger_local_number_test', array($this,"lh_login_logger_local_number_test_shortcode_output"));
add_shortcode('lh_login_logger_global_number_test', array($this,"lh_login_logger_global_number_test_shortcode_output"));

}

public function plugin_init(){
    
//Hook into the login event

add_action( 'wp_login', array($this, 'wp_login'),10,2);


//Add the column content and sortability locally
add_action('manage_users_columns', array($this, 'add_columns'),10,1);
add_filter('manage_users_sortable_columns', array($this,'add_sortable_columns'),10,1);
add_filter('manage_users_custom_column',  array($this,'show_columns_content'), 10, 3);


//Add the column content and sortability for the network
add_filter('wpmu_users_columns', array($this,'add_network_columns'),10,1);
add_filter('manage_users-network_sortable_columns', array($this,'add_network_sortable_columns'),10,1);



//filter the user query
add_action( 'pre_get_users', array($this,'pre_get_users') ,10,1);

//add a shortcodes for testing login conditions
add_action( 'init', array($this,'register_shortcodes'));
    
    
}


  /**
     * Gets an instance of our plugin.
     *
     * using the singleton pattern
     */
    public static function get_instance(){
        if (null === self::$instance) {
            self::$instance = new self();
        }
 
        return self::$instance;
    }


public function __construct() {

    //run our hooks on plugins loaded to as we may need checks       
    add_action( 'plugins_loaded', array($this,'plugin_init'));
              

}



}


//$lh_login_logger_instance = LH_Login_logger_plugin::get_instance();

}

?>