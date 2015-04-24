<?php
/*
Plugin Name: emfluence Marketing Platform
Plugin URI:
Description: Plugin for creating an email signup form for visitors
                                                          Author: Lance Gliser
Version: 1.0
Author URI:
*/

define('EMFLUENCE_EMAILER_PATH', dirname(__FILE__) . '/');
// For language internationalization
// todo: load_plugin_textdomain( 'constant-contact-api', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );

require_once EMFLUENCE_EMAILER_PATH . 'functions.php';
emfluence_bootsrap();

/**
 * Wordpress hooks (filters, actions)
 */

function emfluence_load_widgets(){
  register_widget( 'emfluence_email_signup' );
}
add_action( 'widgets_init', 'emfluence_load_widgets' );

// load admin only files
if(is_admin()) {
  require_once EMFLUENCE_EMAILER_PATH . 'admin.php';

  // Register the settings page
  function emfluence_emailer_admin_menu() {
    add_options_page('Emfluence Emailer Global Settings', 'Emfluence Emailer', 'manage_options', 'emfluence_emailer', '_emfluence_emailer_options_page');
  }
  add_action('admin_menu', 'emfluence_emailer_admin_menu');

  // Build the settings page
  function emfluence_emailer_admin_init(){
    register_setting('emfluence_emailer', 'emfluence_global', '_emfluence_emailer_options_validate');
    add_settings_section(
      'account'
      ,__('Account Settings')
      ,'_emfluence_emailer_options_account_description'
      ,'emfluence_emailer'
    );
    add_settings_field(
      'api_key'
      ,__('Api Key')
      ,'_emfluence_emailer_options_api_key_element'
      ,'emfluence_emailer'
      ,'account'
    );
  }
  add_action('admin_init', 'emfluence_emailer_admin_init');

  // Register the plugin form's ajax callback
  function emfluence_emailer_admin_enqueue_scripts($hook) {
    // Only applies to widgets
    if( !in_array($hook, array('widgets.php', 'customize.php')) ) {
      return;
    }

    wp_enqueue_script(
      'emfluence-emailer-widget'
      ,plugins_url( '/js/widget-settings.min.js', __FILE__ )
      ,array('jquery')
    );

    // in JavaScript, object properties are accessed as ajax_object.ajax_url, ajax_object.we_value
    // wp_localize_script( 'emfluence-emailer-widget', 'ajax_object', array( ));
  }
  add_action( 'admin_enqueue_scripts', 'emfluence_emailer_admin_enqueue_scripts' );
}
