<?php

/**
 * Integrates this plugin with WP Store Locator plugin
 * @see https://wordpress.org/plugins/wp-store-locator/
 */
class Emfl_Widget_Store_Locator {

  protected $zip_field_name = 'store_locator_your_zip';
  protected $store_field_name = 'store_locator_store';

  function is_store_locator_plugin_active() {
    // Note: is_plugin_active() is not available in the init action or in the public part of a site, since it gets loaded later as part of the admin area.
    return class_exists( 'WP_Store_locator' );
  }

  function __construct() {
    static $hooked = FALSE;
    if(TRUE === $hooked) return;
    $hooked = TRUE;
    // Put all further hooks in init(), so that they are only added if the Store Locator plugin is active.
    add_action('init', array($this, 'init'));
  }

  function init() {
    if(!$this->is_store_locator_plugin_active()) return;
    add_filter('emfl_widget_custom_field_types', array($this, 'widget_form_add_field_type'), 10, 1);
    add_filter('emfl_widget_render_custom_field_type', array($this, 'widget_render_custom_field_type'), 10, 3);
    add_filter('emfl_widget_before_contact_save', array($this, 'widget_before_contact_save'), 10, 2);
    add_action('wp_ajax_emfl_form_store_search', array( $this, 'ajax_lookup_stores' ));
    add_action('wp_ajax_nopriv_emfl_form_store_search', array( $this, 'ajax_lookup_stores' ));
  }

  function widget_form_add_field_type($allowed_types) {
    $allowed_types = array_merge($allowed_types, array(
      'preferred store hidden field: user state',
      'preferred store hidden field: user city',
      'preferred store hidden field: user zip',
      'preferred store hidden field: store zip',
      'preferred store hidden field: store street address',
      'preferred store hidden field: store latitude',
      'preferred store hidden field: store longitude',
      'preferred store hidden field: distance to store',
      'preferred store hidden field: store name',
      'preferred store hidden field: store slug',
      'preferred store hidden field: store URL',
    ));
    return $allowed_types;
  }

  function widget_render_custom_field_type($field, $key, $value) {
    // NOTE: We are rendering this wherever the first field is positioned.
    static $has_rendered = FALSE;
    if(TRUE === $has_rendered) return '';
    $has_rendered = TRUE;

    $label = __('Your preferred store', 'emfl_form');
    $placeholder = __('Search By Zip Code', 'emfl_form');
    $zip_value = isset($_POST[$this->zip_field_name]) ? sanitize_text_field($_POST[$this->zip_field_name]) : '';
    $store_value = isset($_POST[$this->store_field_name]) ? intval($_POST[$this->store_field_name]) : '';

    $stores_posts = $store_ids = get_posts( array(
        'numberposts' => 50,
        'post_type'   => 'wpsl_stores',
        'post_status' => 'publish',
    ) );
    $default_options = $searched_options = array();
    foreach($stores_posts as $post) {
      $default_options[$post->ID] = $post->post_title;
    }
    asort($default_options);
    $default_option_html = '<option value="">Select a store</option>';
    foreach ($default_options as $option_id => $option_value) {
      $selected = (intval($store_value) === intval($option_id)) ? 'selected="selected"' : '';
      $default_option_html .= '<option value="' . intval($option_id) . '" ' . $selected . '>' . esc_html($option_value) . '</option>';
    }

    if(!empty($zip_value)) {
      try {
        $address = $this->geocode_zip_to_address($zip_value);
        if (!empty($address['lat'])) {
          global $wpsl;
          $store_data = $wpsl->frontend->find_nearby_locations(array(
              'lat' => $address['lat'],
              'lng' => $address['lng']
          ));
          foreach($store_data as $store) {
            $searched_options[$store['id']] = array('label' => $store['store'], 'distance' => $store['distance']);
          }
        }
      } catch(Exception $e) {
        d($e);
        die();
      }
    }

    wp_enqueue_script(
        'emfl-form-store-locator',
        plugins_url('js/store_locator.js', EMFLUENCE_EMAILER_PATH . 'emfluence.php'),
        array('jquery'),
        filemtime(EMFLUENCE_EMAILER_PATH . 'js/store_locator.js'),
        TRUE
    );

    // TODO: Should this be coming from the first field setting, or a new form setting?
    // TODO: Should the store selection be required too? Maybe only if the zip isn't empty and there are stores nearby?
    $required = $field['required']? 'required' : '';

    $output = '<div class="field row field-emfl-store-locator">' . PHP_EOL;
    $output .= '<label for="emfl_store_locator_your_zip">' . esc_html($label) . '';
    if( $field['required'] ){
      $output .= '<span class="required">*</span>';
    }
    $output .= '</label>' . PHP_EOL;
    $output .=   '<input placeholder="' . esc_attr($placeholder) . '" type="number" name="' . esc_attr($this->zip_field_name) . '" id="emfl_store_locator_your_zip" class="zip-code" value="' . esc_attr($zip_value) . '" ' . $required . ' />' . PHP_EOL;
    $output .=   '<select class="store-options" name="' . esc_attr($this->store_field_name) . '" data-default-html="' . esc_attr($default_option_html) . '">';
    if(empty($searched_options)) {
      $output .= $default_option_html;
    } else {
      $output .= '<option value="">Select a store</option>';
      foreach ($searched_options as $option_id => $option_settings) {
        $selected = (intval($store_value) === intval($option_id)) ? 'selected="selected"' : '';
        $output .= '<option value="' . intval($option_id) . '" data-distance="' . esc_attr($option_settings['distance']) . '" ' . $selected . '>' . esc_html($option_settings['label']) . '</option>';
      }
    }
    $output .=   '</select>';
    $output .= '</div>' . PHP_EOL;
    $output .= '<script> var emfl_form_store_locator_ajax_url = emfl_form_store_locator_ajax_url || "' . admin_url( 'admin-ajax.php' ) . '"; </script>';
    return $output;
  }

  function widget_before_contact_save($data, $instance) {
    $zip = trim(sanitize_text_field($_POST[$this->zip_field_name]));
    $store_id = intval(trim($_POST[$this->store_field_name]));
    if(empty($zip) || empty($store_id)) return $data;

    $store = get_post($store_id);
    if(empty($store) || ('wpsl_stores' !== $store->post_type)) return $data;
    global $wpsl;
    $store_data = $wpsl->frontend->get_store_meta_data(array($store));
    if(!empty($store_data)) $store_data = array_pop($store_data);

    foreach($instance['fields'] as $key=>$field) {
      $key = str_replace('_', '', $key);
      switch($field['type']) {
        case 'preferred store hidden field: user state':
          if(!empty($_POST['store_locator_origin_state'])) $this->contact_record_inject_field($data, $key, sanitize_text_field($_POST['store_locator_origin_state']));
          break;
        case 'preferred store hidden field: user city':
          if(!empty($_POST['store_locator_origin_city'])) $this->contact_record_inject_field($data, $key, sanitize_text_field($_POST['store_locator_origin_city']));
          break;
        case 'preferred store hidden field: user zip':
          $this->contact_record_inject_field($data, $key, $zip);
          break;
        case 'preferred store hidden field: store zip':
          if(!empty($store_data['zip'])) $this->contact_record_inject_field($data, $key, $store_data['zip']);
          break;
        case 'preferred store hidden field: store street address':
          if(!empty($store_data['address'])) $this->contact_record_inject_field($data, $key, $store_data['address']);
          break;
        case 'preferred store hidden field: store latitude':
          if(!empty($store_data['lat'])) $this->contact_record_inject_field($data, $key, $store_data['lat']);
          break;
        case 'preferred store hidden field: store longitude':
          if(!empty($store_data['lng'])) $this->contact_record_inject_field($data, $key, $store_data['lng']);
          break;
        case 'preferred store hidden field: distance to store':
          if(!empty($_POST['store_locator_distance'])) $this->contact_record_inject_field($data, $key, sanitize_text_field($_POST['store_locator_distance']) . wpsl_get_distance_unit());
          break;
        case 'preferred store hidden field: store name':
          if(!empty($store)) $this->contact_record_inject_field($data, $key, $store->post_title);
          break;
        case 'preferred store hidden field: store slug':
          if(!empty($store)) $this->contact_record_inject_field($data, $key, $store->post_name);
          break;
        case 'preferred store hidden field: store URL':
          if(!empty($store_data['url'])) $this->contact_record_inject_field($data, $key, $store_data['url']);
          break;
      }
    }

    return $data;
  }

  /**
   * @param array $contact Passed by reference.
   * @param string $key
   * @param string $value
   */
  protected function contact_record_inject_field(&$contact, $key, $value) {
    if(FALSE !== strpos($key, 'custom')) {
      $contact['customFields'][$key] = array('value' => $value);
    } else {
      $contact[$key] = $value;
    }
  }

  /**
   * @param string $zip
   * @return array
   *  May contain all or none of: 'state', 'city'
   * @uses wpsl_call_geocode_api()
   */
  protected function geocode_zip_to_address($zip) {
    $address = array();
    $response = wpsl_call_geocode_api( $zip );

    if ( is_wp_error( $response ) ) return $address;
    $response = json_decode( $response['body'], true );

    if ( $response['status'] !== 'OK' ) return $address;

    foreach($response['results'][0]['address_components'] as $component) {
      if(in_array('administrative_area_level_1', $component['types'])) {
        $address['state'] = $component['short_name'];
      } elseif(in_array('locality', $component['types'])) {
        $address['city'] = $component['long_name'];
      }
    }

    $address['lat'] = $response['results'][0]['geometry']['location']['lat'];
    $address['lng'] = $response['results'][0]['geometry']['location']['lng'];

    return $address;
  }

  function ajax_lookup_stores() {
    try {
      $address = $this->geocode_zip_to_address(sanitize_text_field($_GET['zip']));
      if (empty($address['lat'])) {
        wp_send_json(array('status' => FALSE, 'err' => 'No geocode results'));
        die();
      }
      global $wpsl;
      $store_data = $wpsl->frontend->find_nearby_locations(array(
          'lat' => $address['lat'],
          'lng' => $address['lng']
      ));
      wp_send_json(array('status' => TRUE, 'stores' => $store_data, 'origin' => $address));
      die();
    } catch(Exception $e) {
      wp_send_json(array('status' => FALSE, 'err' => $e->getMessage()));
      die();
    }
  }

}

new Emfl_Widget_Store_Locator();
