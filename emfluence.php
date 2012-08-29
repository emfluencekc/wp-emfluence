<?php
  /*
  Plugin Name: emfluence Marketing Platform
  Plugin URI:
  Description: Plugin for creating an email signup form for visitors
  Author: Lance Gliser
  Version: 1.0
  Author URI:
  */

  define('EMFLUENCE_PATH', dirname(__FILE__) . '/');
  // For language internationalization
  // todo: load_plugin_textdomain( 'constant-contact-api', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );

  require_once EMFLUENCE_PATH . 'functions.php';
  emfluence_bootsrap();

  // load admin only files
  if(is_admin()) {
    require_once EMFLUENCE_PATH . 'admin.php';

    // Provide setting link on plugins page
    add_filter( 'plugin_action_links', 'emfluence_settings_link', 10, 2 );

    // register admin menu action
    add_action('admin_menu', 'emfluence_admin_menu');
  }

  /* Add our function to the widgets_init hook. */
  add_action( 'widgets_init', 'emfluence_load_widgets' );
