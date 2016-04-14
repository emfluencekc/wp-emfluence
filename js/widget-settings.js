// Multiple instances of the widget may exist on the widget settings page.
// This is completely functional programming.
emfluenceEmailerWidget = {

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
