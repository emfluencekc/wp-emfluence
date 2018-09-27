<?php

class Emfl_Widget_Recaptcha {

  function __construct() {
    static $hooked;
    if(TRUE === $hooked) return;
    $hooked = TRUE;
    add_action('emfl_widget_before_submit', array($this, 'add_recaptcha_to_form'));
    add_filter('emfl_widget_validate', array($this, 'validate_recaptcha'), 10, 3);
    add_action('admin_init', array($this, 'admin_init'));
    add_action('emfl_plugin_settings_page', array($this, 'settings_page'));
  }

  function add_recaptcha_to_form($instance) {
    $options = $this->get_options();
    if(empty($options['site_key'])) return;

    echo '
    <div class="g-recaptcha" data-sitekey="' . esc_attr($options['site_key']) . '"></div>
    <script src="https://www.google.com/recaptcha/api.js" async defer></script>
    ';
  }

  function validate_recaptcha($messages, $instance, $values) {
    $options = $this->get_options();
    if(empty($options['secret'])) return $messages;

    if(empty($values['g-recaptcha-response'])) {
      $messages[] = 'This form requires a Recaptcha action';
      return $messages;
    }

    $validation = wp_remote_post('https://www.google.com/recaptcha/api/siteverify', array('body' => array(
        'secret' => $options['secret'],
        'response' => $values['g-recaptcha-response']
    )));

    if(is_wp_error($validation) || (200 !== intval($validation['response']['code']))) {
      $messages[] = 'There was an unexpected error validating your Recaptcha action. The error is not a result of any action you took.';
      // TODO: Email the site owner?
      return $messages;
    }

    $validation = json_decode($validation['body'], TRUE);
    if(isset($validation['success']) && (TRUE === $validation['success'])) {
      return $messages;
    }

    $messages[] = 'This form requires a successful Recaptcha action';
    return $messages;
  }

  function admin_init(){
    register_setting('emfluence_emailer', 'emfl_widget_recaptcha', array($this, 'settings_validate'));
    add_settings_section(
        'recaptcha'
        ,__('ReCAPTCHA Spam Filter')
        ,array($this, 'settings_description')
        ,'emfluence_emailer'
    );

    add_settings_field(
        'site_key',
        __('ReCAPTCHA v2 Site Key'),
        array($this, 'settings_input_element'),
        'emfluence_emailer',
        'recaptcha',
        array('field_id' => 'site_key')
    );
    add_settings_field(
        'secret',
        __('ReCAPTCHA v2 Secret'),
        array($this, 'settings_input_element'),
        'emfluence_emailer',
        'recaptcha',
        array('field_id' => 'secret')
    );
  }

  function settings_description() {
    echo '
      If you would like to add a ReCAPTCHA v2 spam filter to your signup forms, 
      provide <a href="https://www.google.com/recaptcha/admin" target="_blank">credentials</a> 
      as <a href="https://developers.google.com/recaptcha/intro" target="_blank">documented here</a>.
      Note that if you have developed custom styles for your forms, you may need to refine those
      styles to keep the forms looking good once the ReCAPTCHA is added.
      ';
    // TODO: Validate the recaptcha settings
  }

  function settings_input_element($args) {
    $options = $this->get_options();
    echo "<input id='" . esc_attr($args['field_id']) . "' name='emfl_widget_recaptcha[" . esc_attr($args['field_id']) . "]' size='36' type='text' value='" . esc_attr($options[$args['field_id']]) . "' />";
  }

  function settings_validate($data) {
    if(!empty($data['site_key']) && empty($data['secret'])) add_settings_error(
        'site_key',
        'site_key',
        'If you set a Recaptcha site key, you must also set a secret.'
    );
    return $data;
  }

  /**
   * @return array
   *  Contains 'site_key' and 'secret'. One or both could be empty or invalid.
   */
  protected function get_options() {
    $recaptcha_options = get_option('emfl_widget_recaptcha');
    if(empty($recaptcha_options)) $recaptcha_options = array(
        'site_key' => '',
        'secret' => ''
    );
    return $recaptcha_options;
  }

}

new Emfl_Widget_Recaptcha();
