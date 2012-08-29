<?php

// Add the Settings link on the Plugins page
function emfluence_settings_link( $links, $file ) {
  if ( $file == 'emfluence/emfluence.php' ){
    $settings_link = '<a href="' . admin_url( '/admin.php?page=emfluence' ) . '">' . translate('Settings') . '</a>';
    array_unshift( $links, $settings_link ); // before other links
  }
  return $links;
}


// Create admin menu for the plugin
function emfluence_admin_menu(){

	wp_enqueue_style( 'emfluence_platform_styles', WP_CONTENT_URL . '/plugins/emfluence-emailer/css/styles.css', false, '1.0', 'all' ); 
	wp_enqueue_script( 'jquery' );
	wp_enqueue_script( 'emfluence_platform_js', WP_CONTENT_URL . '/plugins/emfluence-emailer/js/js.js', false, '1.0', false ); 

	//create new top-level menu
  add_utility_page( 'emfluence Marketing Platform', 'emfluence', 'manage_options', 'emfluence', 'emfluence_settings_page', WP_CONTENT_URL . '/plugins/emfluence-emailer/images/icon.png' );

}

function emfluence_settings_page() {

  global $emfluence_client_key, $emfluence_api_key;
  $messages = array();

  if( !empty( $_POST[ 'action' ] ) ){

    $client_key = trim( $_POST[ 'client_key' ] );
    $api_key = trim( $_POST[ 'api_key' ] );
    $authenticate = emfluence_api_authenticate( $client_key, $api_key );

    if( !$authenticate[ 'success' ] ){

      $messages += $authenticate[ 'messages' ];
      update_option( 'emfluence_authenticated', FALSE );

    } else {

      update_option( 'emfluence_client_key', $client_key );
      update_option( 'emfluence_api_key', $api_key );
      update_option( 'emfluence_authenticated', TRUE );

      $emfluence_client_key = $client_key;
      $emfluence_api_key = $api_key;

    }

  } else {

  	$client_key = $emfluence_client_key;
    $api_key = $emfluence_api_key;

  }

?>

<div class="emfluence wrap">
	<h2><?php _e( 'emfluence Marketing Platform' ); ?></h2>

	<?php if( !empty( $messages ) ): ?>
		<div class="messages">
			<?php foreach( $messages as $message ): ?>
				<div class="message <?php echo $message[ 'type' ]; ?>"><?php _e( '<strong>' . $message[ 'type' ] . '</strong>: ' . $message[ 'value' ] ); ?></div>
			<?php endforeach; ?>
		</div>
	<?php endif; ?>

	<p>Welcome! This is where a good description of what the user can do here would go...</p>

  <form method="post">

		<div id="account-settings" class="section widefat">

			<h3 title="Click to collapse / Expand"><?php _e( 'Account Settings' ); ?></h3>

			<div class="section-block">

				<p>
					<label for="client_key"><?php _e( 'Client Key' ); ?>:</label>
					<input type="text" name="client_key" id="client_key" value="<?php echo $client_key; ?>" size="20">
				</p>

				<p>
					<label for="api_key"><?php _e( 'Api Key' ); ?>:</label>
					<input type="text" name="api_key" id="api_key" value="<?php echo $api_key; ?>" size="20">
				</p>

				<?php if( empty( $emfluence_client_key ) || empty( $emfluence_api_key )  ): ?>
					<p><?php _e( 'Enter your client and api key to continue.' ); ?></p>
				<?php endif; ?>

			</div>

		</div><!-- END Account Settings -->

		<?php

			$widgets = get_option( 'widget_emfluence_email_signup' );
			$widgets[ 2 ][ 'title' ] = 'Join our list! NOW.';
			update_option( 'widget_emfluence_email_signup', $widgets );
			// krumo( $widgets );


		?>

		<p class="submit">
			<input type="submit" name="action" value="<?php _e( 'Save' ); ?>" class="button-primary" />
		</p>

  </form>

</div>

<?php }
