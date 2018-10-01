/**
 * Requires the emfl_form_store_locator_ajax_url variable bootstrapped with the form.
 */
jQuery( document ).ready( function( $ ) {

  var default_store_options = $('.field-emfl-store-locator .store-options').attr('data-default-html');

  $('.field-emfl-store-locator .zip-code').keyup(function() {
    var $this = $(this);
    var $this_form = $this.parents('form');
    var zip = $this.val().trim();
    if(0 === zip.length) {
      $this_form.find('.store-options').html(default_store_options);
      emfl_form_store_locator_inject_origin({}, $this_form);
    }
    if(5 !== zip.length) return;
    // TODO: Don't repeat the AJAX request if it's the same zip that was last used.

    $this_form.find('.store-options').html('<option value="">Loading...</option>');
    emfl_form_store_locator_inject_origin({}, $this_form);
    $.getJSON(emfl_form_store_locator_ajax_url, {
      action: 'emfl_form_store_search',
      zip: zip
    }, function(data) {
      if(('boolean' !== typeof(data.status)) || (true !== data.status)) {
        console.error('Bad response from AJAX store lookup query');
        return;
      }
      emfl_form_store_locator_update_select_options(data.stores, $this_form);
      emfl_form_store_locator_inject_origin(data.origin, $this_form);
    });

  });

  // Inject selected store's distance into the form
  $('.field-emfl-store-locator').each(function() {
    $this_form = $(this).parents('form');
    $this_form.submit(function() {
      var $selected_store = $this_form.find('.store-options option:selected');
      if($selected_store.attr('data-distance').length === 0) return;
      $this_form.append('<input type="hidden" name="store_locator_distance" value="' + $selected_store.attr('data-distance') + '" />')
    });
  });

});

function emfl_form_store_locator_update_select_options(store_objects, $form) {
  var $select = $form.find('.store-options');
  if(!(store_objects.length > 0)) {
    $select.html('<option value="">No stores found</option>');
    return;
  }
  var option_html = '<option value="">Select a store</option>';
  var closest_store = store_objects.shift();
  option_html += '<option value="' + closest_store.id + '" data-distance="' + closest_store.distance + '" selected="selected">' + closest_store.store + '</option>';
  for(var i=0; i<store_objects.length; i++) {
    option_html += '<option value="' + store_objects[i].id + '" data-distance="' + store_objects[i].distance + '">' + store_objects[i].store + '</option>';
  }
  $select.html(option_html);
}

function emfl_form_store_locator_inject_origin(address, $form) {
  $form.find('.injected_origin').remove();
  var inject_html = '';
  for(var field_key in address) {
    inject_html += '<input type="hidden" name="store_locator_origin_' + field_key + '" value="' + address[field_key] + '" />';
  }
  $form.append('<div class="injected_origin">' + inject_html + '</div>');
}
