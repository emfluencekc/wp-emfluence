<?php
/*
Plugin Name: emfluence Marketing Platform
Plugin URI:
Description: Plugin for creating an email signup form for visitors
Author: emfluence Digital Marketing
Version: 1.0
Author URI: https://www.emfluence.com
*/

define('EMFLUENCE_EMAILER_PATH', dirname(__FILE__) . '/');
// For language internationalization
// todo: load_plugin_textdomain( 'constant-contact-api', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );

/**
 * Get the singleton instance of the emfluence marketing platform api object
 * @param string $access_token
 * @param boolean $reset = FALSE
 * @return Emfl_Platform_API
 */
function emfluence_get_api($access_token, $reset = FALSE){
  static $api = NULL;
  if( $reset || !$api ){
    require_once( EMFLUENCE_EMAILER_PATH . '/libraries/emfl_platform_api/api.class.inc' );
    $api = new Emfl_Platform_API($access_token);
  }
  return $api;
}

function emfluence_load_widgets(){
  require_once EMFLUENCE_EMAILER_PATH . 'widget.php';
  register_widget( 'emfluence_email_signup' );
}
add_action( 'widgets_init', 'emfluence_load_widgets' );

if(is_admin()) {
  require_once EMFLUENCE_EMAILER_PATH . 'admin.php';
}
