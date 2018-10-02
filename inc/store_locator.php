<?php

/**
 * Integrates this plugin with WP Store Locator plugin
 * @see https://wordpress.org/plugins/wp-store-locator/
 */
class Emfl_Widget_Store_Locator {

  protected $zip_field_name = 'store_locator_your_zip';
  protected $store_field_name = 'store_locator_store';

  protected $required_field_name = 'preferred_store_required';
  protected $order_field_name = 'preferred_store_order';

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
    add_filter('emfl_widget_before_field', array($this, 'widget_render_before_field'), 10, 5);
    add_action('emfl_widget_before_submit', array($this, 'widget_render_before_submit'), 5, 2);
    add_filter('emfl_widget_before_contact_save', array($this, 'widget_before_contact_save'), 10, 2);
    add_filter('emfl_widget_validate', array($this, 'widget_validate'), 10, 3);
    add_filter('emfl_widget_editor_after_sections', array($this, 'editor_after_sections'), 10, 2);
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

  /**
   * @param emfluence_email_signup $widget
   * @return string
   *  The Preferred Store question
   */
  function widget_render_before_field($next_field, $next_key, $next_value, $instance, $widget) {
    $our_render_order = $instance[$this->order_field_name];
    $next_field_render_order = $next_field['order'];
    if($our_render_order > $next_field_render_order) return '';

    return $this->widget_render($widget->number, $instance[$this->required_field_name]);
  }

  /**
   * Output the Preferred Store question directly
   * @param emfluence_email_signup $widget
   */
  function widget_render_before_submit($instance, $widget) {
    echo $this->widget_render($widget->number, $instance[$this->required_field_name]);
  }

  /**
   * Render the Preferred Store question
   * @param int $widget_id We will only render once per widget render.
   * @param bool $required Whether answering this question is required
   * @return string
   */
  protected function widget_render($widget_id, $required = FALSE) {

    static $has_rendered = array();
    if(!empty($has_rendered[$widget_id])) return '';
    $has_rendered[$widget_id] = TRUE;

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

    $errors = '';
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
        $errors = '<!-- store locator rendering error: ' . esc_html($e->getMessage()) . ' -->';
      }
    }

    wp_enqueue_script(
        'emfl-form-store-locator',
        plugins_url('js/store_locator.js', EMFLUENCE_EMAILER_PATH . 'emfluence.php'),
        array('jquery'),
        filemtime(EMFLUENCE_EMAILER_PATH . 'js/store_locator.js'),
        TRUE
    );

    $required = !empty($instance[$this->required_field_name]);

    $output = '<div class="field row field-emfl-store-locator">' . PHP_EOL;
    $output .= '<label for="emfl_store_locator_your_zip">' . esc_html($label) . '';
    if( $required ) $output .= '<span class="required">*</span>';
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
    $output .= '</div>';
    $output .= '<script> var emfl_form_store_locator_ajax_url = emfl_form_store_locator_ajax_url || "' . admin_url( 'admin-ajax.php' ) . '"; </script>' . PHP_EOL;
    $output .= $errors;

    return $output;
  }

  function widget_validate($messages, $instance, $values) {
    // Remove all validation errors for Preferred Store hidden fields. Should only be any if the "required" checkbox is checked on the field settings.
    foreach($messages as $index=>$message) {
      if(is_string($message)) continue;
      if(FALSE !== strpos($message['field']['type'], 'preferred store')) unset($messages[$index]);
    }
    // Add our own validation errors if applicable.
    $required = !empty($instance[$this->required_field_name]);
    $has_zip = !empty($values[$this->zip_field_name]);
    $has_store = !empty($values[$this->store_field_name]);
    if($required && $has_zip && !$has_store) {
      $messages[] = __('Please select your preferred store', 'emfl_form');
    } elseif($required && $has_store && !$has_zip) {
      $messages[] = __('Please enter your zip code', 'emfl_form');
    } elseif($required && !($has_zip && $has_store)) {
      $messages[] = __('Please enter your zip code and select your preferred store', 'emfl_form');
    }
    return $messages;
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
   * Add a field value to either a general contact field or a
   * custom contact variable, depending on the key.
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
   * @param array $instance
   * @param emfluence_email_signup $widget
   * @return string
   */
  function editor_after_sections($instance, $widget) {
    $output = '<h3>' . esc_html(__('Preferred Store', 'emfl_form')) . '</h3>';
    $output .= '<div class="preferred-store">';
    $output .= '
      <p><b>WP Store Locator plugin integration:</b></p>
      <ul style="list-style: initial">
        <li>Select "preferred store" data points as the field type in general contact fields and custom variable fields.</li>
        <li>The Preferred Store question will only appear on your form if you are using at least 1 data point in a contact field.</li>
      </ul>
    ';

    $key = $this->required_field_name;
    $required_input = array(
        'id' => $widget->get_field_id( $key ),
        'name' => $widget->get_field_name( $key ),
        'checked' => !empty($instance[$key]) ? 'checked="checked"' : '',
    );
    $key = $this->order_field_name;
    $order_input = array(
        'id' => $widget->get_field_id( $key ),
        'name' => $widget->get_field_name( $key ),
        'value' => !empty($instance[$key]) ? intval($instance[$key]) : 0,
    );

    $output .= '
      <p>
        <label for="' . esc_attr($required_input['id']) . '">
          <input type="checkbox" id="' . esc_attr($required_input['id']) . '" name="' . esc_attr($required_input['name']) . '" value="1" ' . $required_input['checked'] . ' />
          ' . __('Require both zip code and preferred store') . '
        </label>
      </p>
      <p>
        <label for="' . esc_attr($order_input['id']) . '">
          <input type="text" id="' . esc_attr($order_input['id']) . '" name="' . esc_attr($order_input['name']) . '" value="' . esc_attr($order_input['value']) . '" class="order" size="2" />
          ' . __('Order') . '
        </label>
      </p>
    ';

    $output .= '</div>';
    return $output;
  }

  /**
   * @param string $zip
   * @return array
   *  May contain all or none of: 'state', 'city'
   * @uses wpsl_call_geocode_api()
   */
  protected function geocode_zip_to_address($zip) {
    $cache = wp_cache_get('geocode_zip:' . $zip);
    if(FALSE !== $cache) return $cache;

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

    wp_cache_set('geocode_zip:' . $zip, $address);
    return $address;
  }

  function ajax_lookup_stores() {
    try {
      $address = $this->geocode_zip_to_address(sanitize_text_field($_GET['zip']));
      if (empty($address['lat'])) {
        wp_send_json(array('status' => FALSE, 'err' => 'No geocode results', 'address' => $address));
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
