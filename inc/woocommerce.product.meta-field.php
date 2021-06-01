<?php

class Emfl_Woocommerce_Product_Meta_Field_Definition {

  var $meta_key;

  var $editor_label;

  var $editor_placeholder;

  var $editor_field_type;

  /**
   * Emfl_Woocommerce_Product_Meta_Field_Definition constructor.
   * @param string $meta_key Required. Up to 255 characters.
   * @param string $editor_label Required.
   * @param string $editor_placeholder Optional. Default is "Group ID".
   * @param string $editor_field_type Optional. Any of the basic input types - text, number, email, etc. Default is number.
   */
  function __construct($meta_key, $editor_label, $editor_placeholder = 'Group ID', $editor_field_type = 'number') {
    $this->meta_key = $meta_key;
    $this->editor_label = $editor_label;
    $this->editor_placeholder = $editor_placeholder;
    $this->editor_field_type = $editor_field_type;
  }

}
