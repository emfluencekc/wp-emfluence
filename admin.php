<?php

// Add settings link on plugin page
function emfluence_emailer_settings_link($links) {
  $settings_link = '<a href="options-general.php?page=emfluence_emailer">Settings</a>';
  array_unshift($links, $settings_link);
  return $links;
}
$plugin = plugin_basename(__FILE__);
add_filter("plugin_action_links_$plugin", 'emfluence_emailer_settings_link' );

// Register the settings page
function emfluence_emailer_admin_menu() {
  add_options_page('emfluence Marketing Platform Global Settings', 'emfluence Marketing Platform', 'manage_options', 'emfluence_emailer', '_emfluence_emailer_options_page');
}
add_action('admin_menu', 'emfluence_emailer_admin_menu');

// Build the settings page
function emfluence_emailer_admin_init(){
  register_setting('emfluence_emailer', 'emfluence_global', '_emfluence_emailer_options_validate');
  add_settings_section(
      'account'
      ,__('emfluence Settings')
      ,'_emfluence_emailer_options_account_description'
      ,'emfluence_emailer'
  );
  add_settings_field(
      'api_key'
      ,__('Access Token')
      ,'_emfluence_emailer_options_api_key_element'
      ,'emfluence_emailer'
      ,'account'
  );
  add_settings_field(
      'blacklist_domains',
      'Blacklist Domains',
      '_emfluence_emailer_options_blacklist_domains_element',
      'emfluence_emailer',
      'account'
  );
}
add_action('admin_init', 'emfluence_emailer_admin_init');

// Register the plugin form's ajax callback
function emfluence_emailer_admin_enqueue_scripts($hook) {
  // Only applies to widgets
  if( !in_array($hook, array('widgets.php', 'customize.php')) ) {
    return;
  }

  wp_enqueue_style(
      'emfluence-widget',
      plugins_url( '/css/widget-settings.css', __FILE__ ),
      array(),
      filemtime(__DIR__ . '/css/widget-settings.css')
  );

  wp_enqueue_script(
    'emfluence-emailer-widget'
    //,plugins_url( '/js/widget-settings.min.js', __FILE__ )
    //Subrata
    ,plugins_url( '/js/widget-settings.js', __FILE__ )
    ,array('jquery', 'jquery-ui-accordion', 'jquery-ui-datepicker')
  );

  // in JavaScript, object properties are accessed as ajax_object.ajax_url, ajax_object.we_value
  // wp_localize_script( 'emfluence-emailer-widget', 'ajax_object', array( ));
}
add_action( 'admin_enqueue_scripts', 'emfluence_emailer_admin_enqueue_scripts' );

/**
 * Handles settings for the administration page
 */
function _emfluence_emailer_options_page() {
  ?>
  <div class="emfluence wrap">
    <h2><?php __( 'emfluence Marketing Platform' ); ?></h2>

    <form action="options.php" method="post">
      <?php settings_fields('emfluence_emailer'); ?>
      <?php do_settings_sections('emfluence_emailer'); ?>

      <input name="Submit" type="submit" value="<?php esc_attr_e('Save Changes'); ?>" />
    </form>

  </div>
  <?php
}

function _emfluence_emailer_options_account_description(){
  echo '<p>Welcome! Please enter your api credentials below to begin. Once authenticated, you can create as many widgets as you need. Settings are saved per widget.</p>';
}

function _emfluence_emailer_options_api_key_element(){
  $options = get_option('emfluence_global');
	if(empty($options) || !is_array($options) || !is_string($options['api_key'])) $options = array('api_key' => '');
  echo "<input id='api_key' name='emfluence_global[api_key]' size='36' type='text' value='{$options['api_key']}' />";
}

function _emfluence_emailer_options_blacklist_domains_element(){
  $options = get_option('emfluence_global');
	if(!is_array($options)) $options = array();
  if(!array_key_exists('blacklist_domains', $options)) $options['blacklist_domains'] = '';
  echo "
    <textarea id='blacklist_domains' name='emfluence_global[blacklist_domains]' rows='10'>{$options['blacklist_domains']}</textarea>
    <p>Form submissions with email address recipients in these domains will be rejected.</p>
    <p>This is useful if you only want B2B leads.</p>
    <p>Enter one domain per line, without commas. For example:<br />
      hotmail.com<br />
      gmail.com<br />
      aol.com
    </p>
    ";
}

/**
 * Validates posted settings page options
 * @param array
 * @return array
 */
function _emfluence_emailer_options_validate($data){
  if(empty($data['api_key'])) return $data;

  try {
    $api = emfluence_get_api($data['api_key'], TRUE);
    // Ensure it works
    $result = $api->ping();
  } catch(Exception $e) {
    $result = FALSE;
    $exception_message = $e->getMessage();
  }
  if( !$result || !$result->success ){
    $message = __('Unable to access the API using the api key provided. Error message: ') .
        (empty($exception_message) ? $api->errors->get_last(): $exception_message);
    add_settings_error( 'api_key', 'api_key', $message , 'error' );
    $data['api_key'] = '';
    delete_transient('emfl-access-token-validation');
  } else {
    set_transient('emfl-access-token-validation', TRUE, 60);
  }

  return $data;
}

/**
 * Display a success message if access token was validated.
 */
function emfl_settings_page_messages() {
  if(empty($_GET['page']) || ($_GET['page'] != 'emfluence_emailer')) return;
  $transient = get_transient('emfl-access-token-validation');
  if(empty($transient)) return;
  delete_transient('emfl-access-token-validation');

  $message = __('Access token validated.');
  $class = 'notice notice-success';

  printf( '<div class="%1$s"><p>' . $message . '</p></div>', $class );
}
add_action('admin_notices', 'emfl_settings_page_messages');
