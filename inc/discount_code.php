<?php

class Emfl_Widget_Discount_code {

  protected $settings_section = 'discount_codes';
  protected $option_name = 'emfl_widget_discount_codes';

  function __construct() {
    static $hooked;
    if(TRUE === $hooked) return;
    $hooked = TRUE;
    add_action('admin_init', array($this, 'admin_init'));
  }

  function admin_init(){
    register_setting('emfluence_emailer', $this->option_name, array($this, 'settings_sanitize'));
    add_settings_section(
        'discount_codes'
        ,__('Discount Code Field')
        ,array($this, 'settings_description')
        ,'emfluence_emailer'
    );
    add_settings_field(
        'discount_codes',
        __('Discount Codes'),
        array($this, 'settings_input_element'),
        'emfluence_emailer',
        'discount_codes',
        array('field_id' => 'discount_codes')
    );
  }

  function settings_description() {
    echo '
      <p>
        If you would like to add unique discount codes to new signup contacts,
        provide a list of codes here. Codes should be entered one per line below.
        Codes can include letters, numbers and basic special characters like !@#$%^&*()-=_+.
        Each new contact will receive a code from this list, and that code will be
        removed from the list.
      </p>
      <p>
        In your form, use the DISCOUNT CODE field type with a custom variable.
        The field will act like a hidden field; It will not impact the appearance
        of your form.
      </p>
      <p>
        You will receive a notification by email when this list is down to 50 remaining codes.
      </p>
    ';
  }

  function settings_input_element($args) {
    $options = $this->get_options();
    echo "
      <textarea 
        id='" . esc_attr($args['field_id']) . "' 
        name='" . esc_attr($this->option_name) . "[" . esc_attr($args['field_id']) . "]' 
        rows='10'
        >" . wp_kses_post($options[$args['field_id']]) . "</textarea>
      ";
  }

  function settings_sanitize($data) {
    $codes = explode(PHP_EOL, $data['discount_codes']);
    $codes = array_map(function($el) {
      $el = sanitize_text_field($el);
      return trim($el);
    }, $codes);
    $codes = array_filter($codes);
    $codes = implode(PHP_EOL, $codes);
    $data['discount_codes'] = $codes;
    return $data;
  }

  function get_options() {
    $options = get_option($this->option_name);
    if(empty($options)) $options = array('discount_codes' => '');
    return $options;
  }

  // TODO: Widget admin UI

  // TODO: Form output

  // TODO: Form submission

  // TODO: Notification when codes are low

}

new Emfl_Widget_Discount_code();
