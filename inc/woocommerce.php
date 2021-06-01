<?php

require_once 'woocommerce.product.meta-field.php';

class Emfl_Woocommerce {

  static $add_customer_to_group_field_id = 'emfl-platform-add-customer-to-group';
  static $add_refund_to_group_field_id = 'emfl-platform-add-refund-to-group';

  function __construct() {
    static $hooked = FALSE;
    if($hooked) return;
    $hooked = TRUE;

    add_action('add_meta_boxes', [$this, 'add_product_meta_box']);
    add_action('save_post', [$this, 'save_product_meta_values']);
    add_action('woocommerce_checkout_order_processed', [$this, 'checkout_order_processed'], 10, 3);
    add_action('woocommerce_order_refunded', [$this, 'on_refund'], 10, 2);
  }

  function add_product_meta_box() {
    add_meta_box(
        'emfl_platform_meta_box',
        'emfluence platform',
        [$this, 'meta_box_html'],
        'product',
        'side'
    );
  }

  function meta_box_html($post) {
    $options = get_option('emfluence_global');
    if(empty($options) || empty($options['api_key'])) {
      $url = admin_url('options-general.php?page=emfluence_emailer');
      echo '<p>Please enter an API access token on the <a href="' . esc_url($url) . '">settings page</a>.</p>';
      return;
    }

    foreach($this->get_product_meta_fields() as $field) {
      $field_id = $field->meta_key;
      $value = intval(get_post_meta($post->ID, $field_id, TRUE));
      if(empty($value)) $value = '';
      echo '
      <label for="' . esc_attr($field_id) . '">' . esc_html($field->editor_label) . '</label>
      <p><input type="number" name="' . esc_attr($field_id) . '" id="' . esc_attr($field_id) . '" placeholder="' . esc_attr($field->editor_placeholder) . '" value="' . esc_attr($value) . '" /></p>
      ';
    }

  }

  function save_product_meta_values($post_id) {
    if('product' !== get_post_type($post_id)) return;
    foreach($this->get_product_meta_fields() as $field) {
      $field_id = $field->meta_key;
      if(!array_key_exists($field_id, $_POST)) continue; // phpcs:ignore WordPress.Security.NonceVerification.Missing
      $value = intval($_POST[ $field_id ]); // phpcs:ignore WordPress.Security.NonceVerification.Missing
      if(empty($value)) {
        delete_post_meta($post_id, $field_id);
      } else {
        update_post_meta($post_id, $field_id, $value);
      }
    }
  }

  /**
   * @return Emfl_Woocommerce_Product_Meta_Field_Definition[]
   */
  protected function get_product_meta_fields() {
    $fields = [];
    $fields[self::$add_customer_to_group_field_id] = new Emfl_Woocommerce_Product_Meta_Field_Definition(
        self::$add_customer_to_group_field_id,
        'Add the customer to this group',
        'Group ID'
    );
    $fields[self::$add_refund_to_group_field_id] = new Emfl_Woocommerce_Product_Meta_Field_Definition(
        self::$add_refund_to_group_field_id,
        'Add refunded customers to this group',
        'Group ID'
    );

    /**
     * Themes and plugins can extend the fields by filtering this.
     */
    return apply_filters('emfl_woocommerce_product_meta_fields', $fields);
  }

  function checkout_order_processed( $order_id, $posted_data, $order) {

    $order = wc_get_order($order_id);
    $platform_list_ids = [];
    foreach($order->get_items() as $item) {
      $platform_list_id = intval(get_post_meta($item->get_product_id(), self::$add_customer_to_group_field_id, TRUE));
      if(!empty($platform_list_id)) $platform_list_ids[] = $platform_list_id;
    }
    $platform_list_ids = array_unique($platform_list_ids);
    if(empty($platform_list_ids)) return;

    $user = $order->get_user();
    if(empty($user)) {
      $first_name = $order->get_billing_first_name();
      $last_name = $order->get_billing_last_name();
      $email = $order->get_billing_email();
      $company = $order->get_billing_company();
      $method = 'billing';
    } else {
      $first_name = $user->user_firstname;
      $last_name = $user->last_name;
      $email = $user->user_email;
      $company = get_user_meta($user->ID, apply_filters('emfl_company_name_user_field', 'company_name'), TRUE);
      $method = 'user-' . $user->ID;
    }

    $options = get_option('emfluence_global');
    if(empty($options) || empty($options['api_key'])) return new WP_Error(100, 'No platform API key available');
    $api = emfluence_get_api($options['api_key']);
    $contact = array(
        'email' => $email,
        'firstName' => $first_name,
        'lastName' => $last_name,
        'groupIDs' => array_values($platform_list_ids),
    );
    if(!empty($company)) $contact['company'] = $company;

    $contact = apply_filters('emfl_woocommerce_order_processed_contact_save', $contact, $order, $api);

    $resp = $api->contacts_save($contact);

    if(empty($resp->success)) {
      wp_mail(
          get_bloginfo('admin_email'),
          'Product purchase error on ' . site_url(),
          'Platform returned errors: ' . implode(', ', $resp->errors) . PHP_EOL . PHP_EOL .
          'Transmitted contact data: ' . wp_json_encode($contact) . PHP_EOL . PHP_EOL .
          'Contact details pulled from ' . esc_attr($method)
      );
    }

  }

  function on_refund($order_id, $refund_id) {
    $order = wc_get_order($order_id);
    $platform_list_ids = [];
    foreach($order->get_items() as $item) {
      $platform_list_id = intval(get_post_meta($item->get_product_id(), self::$add_refund_to_group_field_id, TRUE));
      if(!empty($platform_list_id)) $platform_list_ids[] = $platform_list_id;
    }
    $platform_list_ids = array_unique($platform_list_ids);
    if(empty($platform_list_ids)) return;

    $user = $order->get_user();

    $options = get_option('emfluence_global');
    if(empty($options) || empty($options['api_key'])) return new WP_Error(100, 'No platform API key available');
    $api = emfluence_get_api($options['api_key']);
    $contact = array(
        'email' => empty($user) ? $order->get_billing_email() : $user->user_email,
        'groupIDs' => array_values($platform_list_ids),
    );

    $contact = apply_filters('emfl_woocommerce_refund_contact_save', $contact, $order, $api);

    $resp = $api->contacts_save($contact);

    if(empty($resp->success)) {
      wp_mail(
          get_bloginfo('admin_email'),
          'Product refund error on ' . site_url(),
          'Platform returned errors: ' . implode(', ', $resp->errors) . PHP_EOL . PHP_EOL .
          'Transmitted contact data: ' . wp_json_encode($contact)
      );
    }
  }

}

new Emfl_Woocommerce();
