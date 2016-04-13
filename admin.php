<?php

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
  echo "<input id='api_key' name='emfluence_global[api_key]' size='36' type='text' value='{$options['api_key']}' />";
}

/**
 * Validates posted settings page options
 * @param array
 * @return array
 */
function _emfluence_emailer_options_validate($data){
  // Create a new instance of the api
  $api = emfluence_get_api($data['api_key'], TRUE);

  // Ensure it works
  $result = $api->ping();
  if( !$result ){
    $message = __('Unable to access the API using the api key provided. Error message: ') . $api->errors->get_last();
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
