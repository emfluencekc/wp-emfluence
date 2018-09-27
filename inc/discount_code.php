<?php

class Emfl_Widget_Discount_code {

  protected $settings_section = 'discount_codes';
  protected $option_name = 'emfl_widget_discount_codes';
  protected $widget_field_machine_name = 'hidden discount code';
  protected $notify_threshold = 50;

  function __construct() {
    static $hooked;
    if(TRUE === $hooked) return;
    $hooked = TRUE;
    add_action('admin_init', array($this, 'admin_init'));
    add_filter('emfl_widget_custom_field_types', array($this, 'widget_form_add_field_type'), 10, 1);
    add_filter('emfl_widget_before_contact_save', array($this, 'widget_submit'), 10, 2);
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
        The site admin and the widget notification recipient will receive a notification
        by email when this list falls below 50 remaining codes.
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

  function widget_form_add_field_type($allowed_types) {
    $allowed_types[] = 'hidden discount code';
    return $allowed_types;
  }

  function widget_submit($custom_contact, $widget_instance) {
    $options = $this->get_options();
    $discount_codes = explode(PHP_EOL, $options['discount_codes']);
    $discount_codes = array_filter($discount_codes);
    if(empty($discount_codes)) return $custom_contact;
    foreach($widget_instance['fields'] as $key=>$field) {
      if($field['type'] === $this->widget_field_machine_name) {
        if(empty($discount_codes)) continue;
        $key = str_replace('_', '', $key);
        $custom_contact['customFields'][$key] = array('value' => array_shift($discount_codes));
      }
    }

    $options['discount_codes'] = implode(PHP_EOL, $discount_codes);
    update_option($this->option_name, $options);

    if(count($discount_codes) <= $this->notify_threshold) {
      $email_addresses = array(get_bloginfo('admin_email'));
      if(!empty($widget_instance['notify'])) $email_addresses[] = trim($widget_instance['notify']);
      $email_addresses = array_unique($email_addresses);
      $message = 'This notification was sent because your website uses an emfluence signup form widget. ' .
        'The form is set up to assign discount codes to new signups. ' .
        'However, the list of discount codes available in the plugin settings has fallen to ' . intval(count($discount_codes)) . '. ' .
        'Please sign in to ' . admin_url('options-general.php?page=emfluence_emailer') . ' and add more discount codes, or remove the discount code field from the widget that uses it.';
      wp_mail(
          $email_addresses,
          'Signup forms are running low on discount codes',
          $message
      );
    }

    return $custom_contact;
  }

}

new Emfl_Widget_Discount_code();
