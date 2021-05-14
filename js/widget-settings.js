// Multiple instances of the widget may exist on the widget settings page.
// This is completely functional programming.
emfluenceEmailerWidget = {

  init: function() {
    $container = jQuery('.wp-emfluence');
    $container.accordion({
      heightStyle: "content",
      collapsible: true,
      active: false
    });
    jQuery('.wp-emfluence .type-selector').each(function(index, el) {
      emfluenceEmailerWidget.fields.typeSelected(jQuery(el).parents('.type-section'));
    });
    $container.on('change', '.type-selector', function() {
      emfluenceEmailerWidget.fields.typeSelected(jQuery(this).parents('.type-section'));
    });
    emfluenceEmailerWidget.initValidationRedirectField();
  },

  initValidationRedirectField: function() {
    var $redirectField = jQuery('.wp-emfluence .redirect-url');
    $redirectField.change(function() {
      // Validate
      var $this = jQuery(this);
      if(('' === $this.val()) || emfluenceEmailerWidget.validURL($this.val())) {
        $this.siblings('.validation-error').remove();
      } else {
        if(0 === $this.siblings('.validation-error').length) $this.after('<span class="validation-error">Invalid URL format</span>');
      }
    });
    $redirectField.keyup(function() {
      // If already invalid, eagerly validate
      var $this = jQuery(this);
      if(0 === $this.siblings('.validation-error').length) return;
      if(('' === $this.val()) || emfluenceEmailerWidget.validURL($this.val())) {
        $this.siblings('.validation-error').remove();
      }
    });
    $redirectField.each(function() {
      // Maybe add a validation error if one didn't bootstrap (the JS validation is a little stricter than the server-side validation)
      var $this = jQuery(this);
      if(0 !== $this.siblings('.validation-error').length) return;
      if(('' !== $this.val()) && !emfluenceEmailerWidget.validURL($this.val())) {
        $this.after('<span class="validation-error">Invalid URL format</span>');
      }
    });
  },

  validURL: function(str) {
    return /^(https?|s?ftp):\/\/(((([a-z]|\d|-|\.|_|~|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])|(%[\da-f]{2})|[!\$&'\(\)\*\+,;=]|:)*@)?(((\d|[1-9]\d|1\d\d|2[0-4]\d|25[0-5])\.(\d|[1-9]\d|1\d\d|2[0-4]\d|25[0-5])\.(\d|[1-9]\d|1\d\d|2[0-4]\d|25[0-5])\.(\d|[1-9]\d|1\d\d|2[0-4]\d|25[0-5]))|((([a-z]|\d|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])|(([a-z]|\d|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])([a-z]|\d|-|\.|_|~|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])*([a-z]|\d|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])))\.)+(([a-z]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])|(([a-z]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])([a-z]|\d|-|\.|_|~|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])*([a-z]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])))\.?)(:\d*)?)(\/((([a-z]|\d|-|\.|_|~|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])|(%[\da-f]{2})|[!\$&'\(\)\*\+,;=]|:|@)+(\/(([a-z]|\d|-|\.|_|~|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])|(%[\da-f]{2})|[!\$&'\(\)\*\+,;=]|:|@)*)*)?)?(\?((([a-z]|\d|-|\.|_|~|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])|(%[\da-f]{2})|[!\$&'\(\)\*\+,;=]|:|@)|[\uE000-\uF8FF]|\/|\?)*)?(#((([a-z]|\d|-|\.|_|~|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])|(%[\da-f]{2})|[!\$&'\(\)\*\+,;=]|:|@)|\/|\?)*)?$/i.test(str);
  },

  groups: {
    add: function(element){
      $widget = jQuery(element).parents('.widget');
      $input = this.getInput($widget);

      // Validate the value
      var values = this.processValue($input.val());
      if( !values ){
        return;
      }

      $checkboxesContainer = this.getCheckboxesContainer($widget);

      // Prevent adding existing checkboxes back in
      if( $checkboxesContainer.find('input[type="checkbox"][value="' + values.id + '"]').length > 0 ){
        alert(values.name + ' is already in the selected groups.');
        return;
      }

      $checkboxesContainer.append( this.createCheckbox($widget, values) );
      $input.val(null);
    }
    ,getInput: function($widget){
      return $widget.find('.groups input[list]');
    }
    ,getCheckboxesContainer: function($widget){
      return $widget.find('.groups .selected');
    }
    ,processValue: function(value){
      // Seperate the name and id
      // It should be in the format of name [##]
      var matches = value.match(/(.+)\s\[(\d+)\]/);
      if( !matches ){
        return false;
      }
      return {
        name: matches[1]
        ,id: matches[2]
      }
    }
    ,createCheckbox: function($widget, values){
      var widgetId = $widget.attr('id').slice(-1);
      var id = 'groups-' + widgetId + '-' + values.id;
      var html =
        '<div><label for="' + id + '">\
          <input id="' + id + '" type="checkbox" value="' + values.id + '" name="groups[]" checked /> ' + values.name + '\
        </label></div>';
      return html;
    }

  },

  fields: {
    add: function(element){
      $widget = jQuery(element).parents('.widget');
      $input = this.getInput($widget);

      // Validate the value
      var fieldValue = $input.val();
      var fieldSettings = $input.find('option:selected').data('settings');
      if( !fieldValue || !fieldSettings ) return console.error('Field not found.');

      $container = this.getContainer($widget);

      // Prevent adding existing fields
      if( $container.find('[data-variable-key="' + fieldValue + '"]').length > 0 ){
        alert(fieldSettings['name'] + ' is already in the selected contact fields.');
        return;
      }

      $container.append( this.createSection($widget, fieldValue, fieldSettings) );
      $input.val(null);
    }
    ,getInput: function($widget){
      return $widget.find('.basic-fields-adder select');
    }
    ,getContainer: function($widget){
      return $widget.find('.basic_contact_fields');
    }
    ,getCustomTemplate: function($widget) {
      return $widget
        .find('.basic_contact_field_template')
        .html();
    }
    ,createSection: function($widget, fieldKey, fieldSettings){
      var topOrder = 1;
      $widget.find('.basic_contact_fields .contact-field input.order').each(function(i, el) {
        var myOrder = parseInt(el.value);
        if(!isNaN(myOrder)) topOrder = Math.max(topOrder, myOrder);
      });
      var html = this.getCustomTemplate($widget)
        .replace(new RegExp('CONTACT_FIELD_NAME', 'g'), fieldSettings['name'])
        .replace(new RegExp('CONTACT_FIELD_KEY', 'g'), fieldKey)
        .replace(new RegExp('CONTACT_FIELD_REQUIRED_MESSAGE', 'g'), fieldSettings['required_message'])
        .replace(new RegExp('CONTACT_FIELD_LABEL', 'g'), fieldSettings['label'])
        .replace(new RegExp('CONTACT_FIELD_ORDER', 'g'), topOrder+1);
      return html;
    }
    ,typeSelected: function($selectionContainer) {
      var theType = $selectionContainer.find('.type-selector option:selected').text();
      $selectionContainer.find('.hidden-value').hide();
      switch(theType) {
        case 'hidden':
          $selectionContainer.find('.hidden-value').show();
          break;
      }
    }

  },

  variables: {
    add: function(element){
      $widget = jQuery(element).parents('.widget');
      $input = this.getInput($widget);

      // Validate the value
      var variableNumber = $input.val();
      if( !variableNumber ) return;

      $customVariablesContainer = this.getCustomVariablesContainer($widget);

      // Prevent adding existing checkboxes back in
      if( $customVariablesContainer.find('[data-variable-key="custom_' + variableNumber + '"]').length > 0 ){
        alert(variableNumber + ' is already in the selected custom variables.');
        return;
      }

      $customVariablesContainer.append( this.createVariableSection($widget, variableNumber) );
      $input.val(null);
    }
    ,getInput: function($widget){
      return $widget.find('.custom-variable-adder input');
    }
    ,getCustomVariablesContainer: function($widget){
      return $widget.find('.custom_variables');
    }
    ,getCustomVariablesTemplate: function($widget) {
      return $widget
        .find('.custom_variable_template')
        .html();
    }
    ,createVariableSection: function($widget, variableNumber){
      var html = this.getCustomVariablesTemplate($widget)
        .replace(new RegExp('CUSTOM_VARIABLE_NUMBER', 'g'), variableNumber);
      return html;
    }

  }

};

jQuery(function() {
  $ = jQuery;

  emfluenceEmailerWidget.init();

  $(document).ajaxSuccess(emfluenceEmailerWidget.init);
});
