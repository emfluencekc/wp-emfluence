<?php

/**
 * TODO: note below from Tiffany, based on feedback from a client.
 *  Only public groups are pulled into the widget. However, I think
 *  it would be best to make them enter in the list IDs they want
 *  because making a group public also displays it on the unsubscribe
 *  page which people may not want. Just add this as a note for when
 *  this is cleaned up for giving out to clients.
 */


/**
 * Wordpress Hooks
 */

function emfluence_load_widgets(){
	register_widget( 'emfluence_email_signup' );
}

class emfluence_email_signup extends WP_Widget {
  function emfluence_email_signup(){
    /* Widget settings. */
    $widget_ops = array( 'classname' => 'emfluence_email_signup', 'description' => 'Creates an email signup form for your visitors.' );

    /* Widget control settings. */
    $control_ops = array( 'width' => 400, 'id_base' => 'emfluence_email_signup' );

    /* Create the widget. */
    $this->WP_Widget( 'emfluence_email_signup', 'emfluence Email Signup', $widget_ops, $control_ops );
  }

  function widget( $args, $instance ) {
    extract( $args );

    // Setup some defaults
    $messages = array();
    $values = array(
      'first_name' => '',
      'last_name' => '',
      'title' => '',
      'company' => '',
      'email' => '',
      'custom_1' => '',
      'custom_2' => '',
      'custom_3' => '',
      'custom_4' => '',
      'custom_5' => '',
      'custom_6' => '',
      'custom_7' => '',
      'custom_8' => '',
      'custom_9' => '',
      'custom_10' => '',
    );

    if( !empty( $instance[ 'groups' ] ) ) $lists = implode(',', $instance['groups'] );
    $title = apply_filters( 'Email Signup', empty( $instance[ 'title' ] ) ? __( 'Email Signup' ) : $instance[ 'title' ] );

    // Ensure we can't sign people up without lists
    if( empty($lists) ){
      $output = '';

      /* Before widget (defined by themes). */
      $output .= $before_widget . '<form class="mail-form" method="post"><div class="holder"><div class="frame">';

      /* Title of widget (before and after defined by themes). */
      if ( $title )
        $output .= $before_title . '<span>' . $title . '</span>' . $after_title;

      $output .= '<p>' . __('Please select lists visitors may sign up for.') . '</p>' . "\n";
      $output .= '<p>' . __('Powered by emfluence.') . '</p>' . "\n";

      $output .= '</div></div></form>' . $after_widget;

      print $output;
      return;
    }

    /**
     * Form processing
     */

    if( !empty($_POST) && $_POST['action'] == 'email_signup' ){
      $valid = TRUE;
      // Set the field values in case there's an error
      foreach( $_POST as $key => $value ){
      	$values[$key] = htmlentities( trim( $value ) );
      }

      foreach( $instance['fields'] as $key => $field ){
      	if( $field['required'] && empty($_POST[$key]) ){
          $valid = FALSE;
          $messages[] = array( 'type' => 'error', 'value' => __( $field['required_message'] ) );
      	} elseif ( $key == 'email' && !emfluence_validate_email($value) ){
          $valid = FALSE;
          $messages[] = array( 'type' => 'error', 'value' => __('Invalid email address.') );
      	}
      }

      if( $valid ){
      	// Try to subscribe them
        $result = emfluence_api_subscription_start($_POST);
        if( !$result['success'] ){
          $messages += $result['messages'];
        } else {
        	$messages[] = array( 'type' => 'success', 'value' => __('Subscription started!') );
          $output = '';

          /* Before widget (defined by themes). */
					$output .= $before_widget . '<form class="mail-form" method="post"><div class="holder"><div class="frame">';

          /* Title of widget (before and after defined by themes). */
          if ( $title )
						$output .= $before_title . '<span>' . $title . '</span>' . $after_title;

          // Output all messages
          if( !empty($messages) ){
            $output .= '<ul class="messages">';
            foreach($messages as $message){
              $output .= '<li class="message ' . $message['type'] . '">' . translate($message['value']) . '</li>';
            }
            $output .= '</ul>';
          }

					$output .= '</div></div></form>' . $after_widget;

          print $output;
          return;
        }
      }
    }

    $output = '';

    /* Before widget (defined by themes). */
		$output .= $before_widget . '<form class="mail-form" method="post"><div class="holder"><div class="frame">';

    /* Title of widget (before and after defined by themes). */
    if ( $title )
			$output .= $before_title . '<span>' . $title . '</span>' . $after_title;

    // Output all messages
    if( !empty($messages) ){
      $output .= '<ul class="messages">';
      foreach($messages as $message){
        $output .= '<li class="message ' . $message['type'] . '">' . translate($message['value']) . '</li>';
      }
      $output .= '</ul>';
    }

    $output .= wpautop( $instance['text'] );

    $current_page_url = remove_query_arg('sucess', emfluence_get_current_page_url());
    $output .= '<form action="' . $current_page_url . '" method="POST">' . "\n";
    $output .= '<input type="hidden" name="action" value="email_signup" />' . "\n";
    $output .= '<input type="hidden" name="source" value="' . $current_page_url . '" />' . "\n";
    $output .= '<input type="hidden" name="lists" value="' . $lists . '" />' . "\n";

    usort($instance['fields'], 'emfluence_field_order_sort');
    foreach( $instance['fields'] as $key => $field ){
      if( $field['display'] ){
        $label = translate($field['label']);
        $placeholder = translate( str_replace(':', '', $field['label']) );
        switch( $field['type'] ){
        	case 'text':
          default:
            $output .= '<div class="field row">' . "\n";
              $output .= '<label for="emfluence_' . $key . '">' . $label . '';
              if( $field['required'] ){
                $output .= '<span class="required">*</span>';
              }
              $input_type = ($field['field_name']=='email') ? 'email' : 'text';
              $output .= '</label>' . "\n";
              $output .=   '<input placeholder="' . $placeholder . '" type="' . $input_type . '" name="' . $field['field_name'] . '" id="emfluence_' . $key . '" value="' . $values[$field['field_name']] . '" />' . "\n";
            $output .= '</div>' . "\n";
          break;
        }
      }
    }

    $output .= '<div class="row"><input type="submit" class="submit" value="' . htmlentities( $instance['submit'], ENT_QUOTES ) . '" /></div>' . "\n";
    $output .= '</form>' . "\n";

    /* After widget (defined by themes). */
		$output .= '</div></div></form>' . $after_widget;

    echo $output;

    return;
  }

  public function form( $instance ) {

    // Todo: Prevent plugin from displaying if not authenticated
    if( !get_option('emfluence_authenticated', FALSE) ){
      $output = '<h3>Authentication Failed</h3>';
      $otuput .= '<p>Please enter your client and api keys to continue.</p>';
      print $output;
      return;
    }

    $groups_search = emfluence_api_groups_search();
    if(!$groups_search['success']){
      $messages[] = $groups_search['messages'];
      $available_groups = array();
    } else {
      $available_groups = $groups_search['groups'];
    }

    /* Set up some default widget settings. */
    $defaults = array(
      'title' => 'Email Signup',
      'text' => '',
      'groups' => array(),
      'submit' => 'Signup',
      'fields' => array(
        'first_name' => array(
          'name' => 'First Name',
          'display' => 1,
          'required' => 1,
          'required_message' => 'First name is required.',
          'label' => 'First Name:',
          'order' => 1,
        ),
        'last_name' => array(
          'name' => 'Last Name',
          'display' => 1,
          'required' => 1,
          'required_message' => 'Last name is required.',
          'label' => 'Last Name:',
          'order' => 2,
        ),
        'title' => array(
          'name' => 'Title',
          'display' => 0,
          'required' => 0,
          'required_message' => 'Title is required.',
          'label' => 'Title:',
          'order' => 3,
        ),
        'company' => array(
          'name' => 'Company',
          'display' => 0,
          'required' => 0,
          'required_message' => 'Company is required.',
          'label' => 'Company:',
          'order' => 4,
        ),
        'email' => array(
          'name' => 'Email',
          'display' => 1,
          'required' => 1,
          'required_message' => 'Email address is required.',
          'label' => 'Email:',
          'order' => 5,
        ),
        'custom_1' => array(
          'name' => 'Custom 1',
          'display' => 0,
          'required' => 0,
          'required_message' => 'Custom 1 is required.',
          'label' => 'Custom 1:',
          'order' => 6,
        ),
        'custom_2' => array(
          'name' => 'Custom 2',
          'display' => 0,
          'required' => 0,
          'required_message' => 'Custom 2 is required.',
          'label' => 'Custom 2:',
          'order' => 7,
        ),
        'custom_3' => array(
          'name' => 'Custom 3',
          'display' => 0,
          'required' => 0,
          'required_message' => 'Custom 3 is required.',
          'label' => 'Custom 3:',
          'order' => 8,
        ),
        'custom_4' => array(
          'name' => 'Custom 4',
          'display' => 0,
          'required' => 0,
          'required_message' => 'Custom 4 is required.',
          'label' => 'Custom 4:',
          'order' => 9,
        ),
        'custom_5' => array(
          'name' => 'Custom 5',
          'display' => 0,
          'required' => 0,
          'required_message' => 'Custom 5 is required.',
          'label' => 'Custom 5:',
          'order' => 10,
        ),
        'custom_6' => array(
          'name' => 'Custom 6',
          'display' => 0,
          'required' => 0,
          'required_message' => 'Custom 6 is required.',
          'label' => 'Custom 6:',
          'order' => 11,
        ),
        'custom_7' => array(
          'name' => 'Custom 7',
          'display' => 0,
          'required' => 0,
          'required_message' => 'Custom 7 is required.',
          'label' => 'Custom 7:',
          'order' => 12,
        ),
        'custom_8' => array(
          'name' => 'Custom 8',
          'display' => 0,
          'required' => 0,
          'required_message' => 'Custom 8 is required.',
          'label' => 'Custom 8:',
          'order' => 13,
        ),
        'custom_9' => array(
          'name' => 'Custom 9',
          'display' => 0,
          'required' => 0,
          'required_message' => 'Custom 9 is required.',
          'label' => 'Custom 9:',
          'order' => 14,
        ),
        'custom_10' => array(
          'name' => 'Custom 10',
          'display' => 0,
          'required' => 0,
          'required_message' => 'Custom 10 is required.',
          'label' => 'Custom 10:',
          'order' => 15,
        ),
      ),
    );

    $instance = wp_parse_args( (array) $instance, $defaults );

    $output = '';
    $output .= '<h3>' . translate('Text Display') . '</h3>' . "\n";
    $output .= '<p>' . "\n";
    $output .=  '<label for="' . $this->get_field_id( 'title' ) . '">' . translate('Title') . ':</label>' . "\n";
    $output .=  '<input type="text" id="' . $this->get_field_id( 'title' ) . '" name="' . $this->get_field_name( 'title' ) . '" value="' . $instance['title'] . '" style="width:100%;" />' . "\n";
    $output .= '</p>' . "\n";
    $output .= '<p>' . "\n";
    $output .=  '<label for="' . $this->get_field_id( 'text' ) . '">' . translate('Text') . ':</label>' . "\n";
    $output .=  '<textarea id="' . $this->get_field_id( 'text' ) . '" name="' . $this->get_field_name( 'text' ) . '" style="width:100%;" >' . $instance['text'] . '</textarea>' . "\n";
    $output .= '</p>' . "\n";
    $output .= '<p>' . "\n";
    $output .=  '<label for="' . $this->get_field_id( 'submit' ) . '">' . translate('Submit button') . ':</label>' . "\n";
    $output .=  '<input type="text" id="' . $this->get_field_id( 'submit' ) . '" name="' . $this->get_field_name( 'submit' ) . '" value="' . $instance['submit'] . '" style="width:100%;" />' . "\n";
    $output .= '</p>' . "\n";

    $output .= '<h3>' . translate('Groups') . '</h3>';
    $output .= '<table>' . "\n";
    // $output .=   '<thead>' . "\n";
    // $output .=     '<tr>' . "\n";
    // $output .=       '<th>&nbsp;</th>' . "\n";
    // $output .=       '<th>Description</th>' . "\n";
    // $output .=     '</tr>' . "\n";
    // $output .=   '</thead>' . "\n";
    $output .=   '<tbody>' . "\n";
    foreach( $available_groups as $available_group ){
      $input = array(
        'id' => $this->get_field_id( 'groups-' . $available_group->id ),
        'name' => $this->get_field_name(  'groups][' . $available_group->id ),
        'checked' => is_array( $instance['groups'] ) && in_array($available_group->id, $instance['groups'])? 'checked="checked"' : "",
      );
      $output .=     '<tr>' . "\n";
      $output .=       '<td><input type="checkbox" name="' . $input['name'] . '" id="' . $input['id'] . '" value="1" ' . $input['checked'] . ' /></td>' . "\n";
      $output .=       '<td>' . "\n";
      $output .=        '<p>' . "\n";
      $output .=          '<label for="' . $input['id'] . '" style="font-weight: bold;">' . $available_group->name . '</label>' . "\n";
      if( !empty($available_group->description) ){
        $output .=          '<br />' . translate($available_group->description) . "\n";
      }
      $output .=       '</td>' . "\n";
      $output .=     '</tr>' . "\n";
    }
    $output .=   '</tbody>' . "\n";
    $output .=   '</table>' . "\n";

    $output .= '<h3>' . translate('Fields') . '</h3>' . "\n";
    foreach( $defaults['fields'] as $key => $field ){
      $display_input = array(
        'id' => $this->get_field_id( $key . '_display' ),
        'name' => $this->get_field_name(  $key . '_display' ),
        'checked' => $instance['fields'][$key]['display'] == 1? 'checked="checked"' : '',
        'disabled' => $key == 'email'? 'disabled="disabled"' : '',
      );
      $required_input = array(
        'id' => $this->get_field_id( $key . '_required' ),
        'name' => $this->get_field_name(  $key . '_required' ),
        'checked' => $instance['fields'][$key]['required'] == 1? 'checked="checked"' : '',
        'disabled' => $key == 'email'? 'disabled="disabled"' : '',
      );
      $required_message_input = array(
        'id' => $this->get_field_id( $key . '_required_message' ),
        'name' => $this->get_field_name(  $key . '_required_message' ),
        'value' => $instance['fields'][$key]['required_message'],
      );
      $label_input = array(
        'id' => $this->get_field_id( $key . '_label' ),
        'name' => $this->get_field_name(  $key . '_label' ),
        'value' => $instance['fields'][$key]['label'],
      );
      $order_input = array(
        'id' => $this->get_field_id( $key . '_order' ),
        'name' => $this->get_field_name(  $key . '_order' ),
        'value' => $instance['fields'][$key]['order'],
      );

      $output .= '<h5>' . translate($field['name']) . '</h5>' . "\n";
      $output .= '<p>' . "\n";
      $output .=  '<label for="' . $display_input['id'] . '">' . "\n";
      $output .=   '<input type="checkbox" id="' . $display_input['id'] . '" name="' . $display_input['name'] . '" value="1" ' . $display_input['checked'] . ' ' . $display_input['disabled'] . ' />' . "\n";
      $output .=   translate('Display');
      $output .=   '</label>' . "\n";
      $output .=   ' ';
      $output .=  '<label for="' . $required_input['id'] . '">' . "\n";
      $output .=   '<input type="checkbox" id="' . $required_input['id'] . '" name="' . $required_input['name'] . '" value="1" ' . $required_input['checked'] . ' ' . $display_input['disabled'] . ' />' . "\n";
      $output .=   translate('Required');
      $output .=   '</label>' . "\n";
      $output .= '</p>' . "\n";
      $output .= '<p>' . "\n";
      $output .=  '<label for="' . $required_message_input['id'] . '">' . translate('Required Message') . '</label>' . "\n";
      $output .=  '<input type="text" id="' . $required_message_input['id'] . '" name="' . $required_message_input['name'] . '" value="' . $required_message_input['value'] . '" style="width:100%;" />' . "\n";
      $output .= '</p>' . "\n";
      $output .= '<p>' . "\n";
      $output .=  '<label for="' . $label_input['id'] . '">' . translate('Label') . '</label>' . "\n";
      $output .=  '<input type="text" id="' . $label_input['id'] . '" name="' . $label_input['name'] . '" value="' . $label_input['value'] . '" style="width:100%;" />' . "\n";
      $output .= '</p>' . "\n";
      $output .= '<p>' . "\n";
      $output .=  '<label for="' . $order_input['id'] . '">' . translate('Order') . '</label>' . "\n";
      $output .=  '<input type="text" id="' . $order_input['id'] . '" name="' . $order_input['name'] . '" value="' . $order_input['value'] . '" style="width:100%;" />' . "\n";
      $output .= '</p>' . "\n";
    }

    print $output;
  }

  /**
   * Update the widget settings.
   */
  function update( $new_instance, $old_instance ) {
    $instance = $new_instance;

    $instance['fields'] = array();
    $instance['fields']['first_name'] = array(
      'field_name' => 'first_name',
      'display' => $new_instance['first_name_display'] == 1? 1 : 0,
      'required' => $new_instance['first_name_required'] == 1? 1  : 0,
      'required_message' => !empty($new_instance['first_name_required_message'])? stripslashes(trim($new_instance['first_name_required_message'])) : 'First name is required.',
      'label' => !empty($new_instance['first_name_label'])? stripslashes(trim($new_instance['first_name_label'])) : 'First Name:',
      'order' => is_numeric($new_instance['first_name_order'])? $new_instance['first_name_order'] : 1,
    );
    $instance['fields']['last_name'] = array(
      'field_name' => 'last_name',
      'display' => $new_instance['last_name_display'] == 1? 1 : 0,
      'required' => $new_instance['last_name_required'] == 1? 1  : 0,
      'required_message' => !empty($new_instance['last_name_required_message'])? stripslashes(trim($new_instance['last_name_required_message'])) : 'Last name is required.',
      'label' => !empty($new_instance['last_name_label'])? stripslashes(trim($new_instance['last_name_label'])) : 'Last Name:',
      'order' => is_numeric($new_instance['last_name_order'])? $new_instance['last_name_order'] : 2,
    );
    $instance['fields']['title'] = array(
      'field_name' => 'title',
      'display' => $new_instance['title_display'] == 1? 1 : 0,
      'required' => $new_instance['title_required'] == 1? 1  : 0,
      'required_message' => !empty($new_instance['title_required_message'])? stripslashes(trim($new_instance['title_required_message'])) : 'Title is required.',
      'label' => !empty($new_instance['title_label'])? stripslashes(trim($new_instance['title_label'])) : 'Title:',
      'order' => is_numeric($new_instance['title_order'])? $new_instance['title_order'] : 3,
    );
    $instance['fields']['company'] = array(
      'field_name' => 'company',
      'display' => $new_instance['company_display'] == 1? 1 : 0,
      'required' => $new_instance['company_required'] == 1? 1  : 0,
      'required_message' => !empty($new_instance['company_required_message'])? stripslashes(trim($new_instance['company_required_message'])) : 'Company is required.',
      'label' => !empty($new_instance['company_label'])? stripslashes(trim($new_instance['company_label'])) : 'Company:',
      'order' => is_numeric($new_instance['company_order'])? $new_instance['company_order'] : 4,
    );
    $instance['fields']['email'] = array(
      'field_name' => 'email',
      'display' => $new_instance['email_display'] = 1, // This cannot be optional
      'required' => $new_instance['email_required'] = 1, // This cannot be optional
      'required_message' => !empty($new_instance['email_required_message'])? stripslashes(trim($new_instance['email_required_message'])) : 'Email address is required.',
      'label' => !empty($new_instance['email_label'])? stripslashes(trim($new_instance['email_label'])) : 'Email:',
      'order' => is_numeric($new_instance['email_order'])? $new_instance['email_order'] : 5,
    );
    $instance['fields']['custom_1'] = array(
      'field_name' => 'custom_1',
      'display' => $new_instance['custom_1_display'] == 1? 1 : 0,
      'required' => $new_instance['custom_1_required'] == 1? 1  : 0,
      'required_message' => !empty($new_instance['custom_1_required_message'])? stripslashes(trim($new_instance['custom_1_required_message'])) : 'Custom 1 is required.',
      'label' => !empty($new_instance['custom_1_label'])? stripslashes(trim($new_instance['custom_1_label'])) : 'Custom 1:',
      'order' => is_numeric($new_instance['custom_1_order'])? $new_instance['custom_1_order'] : 6,
    );
    $instance['fields']['custom_2'] = array(
      'field_name' => 'custom_2',
      'display' => $new_instance['custom_2_display'] == 1? 1 : 0,
      'required' => $new_instance['custom_2_required'] == 1? 1  : 0,
      'required_message' => !empty($new_instance['custom_2_required_message'])? stripslashes(trim($new_instance['custom_2_required_message'])) : 'Custom 2 is required.',
      'label' => !empty($new_instance['custom_2_label'])? stripslashes(trim($new_instance['custom_2_label'])) : 'Custom 2:',
      'order' => is_numeric($new_instance['custom_2_order'])? $new_instance['custom_2_order'] : 7,
    );
    $instance['fields']['custom_3'] = array(
      'field_name' => 'custom_3',
      'display' => $new_instance['custom_3_display'] == 1? 1 : 0,
      'required' => $new_instance['custom_3_required'] == 1? 1  : 0,
      'required_message' => !empty($new_instance['custom_3_required_message'])? stripslashes(trim($new_instance['custom_3_required_message'])) : 'Custom 3 is required.',
      'label' => !empty($new_instance['custom_3_label'])? stripslashes(trim($new_instance['custom_3_label'])) : 'Custom 3:',
      'order' => is_numeric($new_instance['custom_3_order'])? $new_instance['custom_3_order'] : 8,
    );
    $instance['fields']['custom_4'] = array(
      'field_name' => 'custom_4',
      'display' => $new_instance['custom_4_display'] == 1? 1 : 0,
      'required' => $new_instance['custom_4_required'] == 1? 1  : 0,
      'required_message' => !empty($new_instance['custom_4_required_message'])? stripslashes(trim($new_instance['custom_4_required_message'])) : 'Custom 4 is required.',
      'label' => !empty($new_instance['custom_4_label'])? stripslashes(trim($new_instance['custom_4_label'])) : 'Custom 4:',
      'order' => is_numeric($new_instance['custom_4_order'])? $new_instance['custom_4_order'] : 9,
    );
    $instance['fields']['custom_5'] = array(
      'field_name' => 'custom_5',
      'display' => $new_instance['custom_5_display'] == 1? 1 : 0,
      'required' => $new_instance['custom_5_required'] == 1? 1  : 0,
      'required_message' => !empty($new_instance['custom_5_required_message'])? stripslashes(trim($new_instance['custom_5_required_message'])) : 'Custom 5 is required.',
      'label' => !empty($new_instance['custom_5_label'])? stripslashes(trim($new_instance['custom_5_label'])) : 'Custom 5:',
      'order' => is_numeric($new_instance['custom_5_order'])? $new_instance['custom_5_order'] : 10,
    );
    $instance['fields']['custom_6'] = array(
      'field_name' => 'custom_6',
      'display' => $new_instance['custom_6_display'] == 1? 1 : 0,
      'required' => $new_instance['custom_6_required'] == 1? 1  : 0,
      'required_message' => !empty($new_instance['custom_6_required_message'])? stripslashes(trim($new_instance['custom_6_required_message'])) : 'Custom 6 is required.',
      'label' => !empty($new_instance['custom_6_label'])? stripslashes(trim($new_instance['custom_6_label'])) : 'Custom 6:',
      'order' => is_numeric($new_instance['custom_6_order'])? $new_instance['custom_6_order'] : 11,
    );
    $instance['fields']['custom_7'] = array(
      'field_name' => 'custom_7',
      'display' => $new_instance['custom_7_display'] == 1? 1 : 0,
      'required' => $new_instance['custom_7_required'] == 1? 1  : 0,
      'required_message' => !empty($new_instance['custom_7_required_message'])? stripslashes(trim($new_instance['custom_7_required_message'])) : 'Custom 7 is required.',
      'label' => !empty($new_instance['custom_7_label'])? stripslashes(trim($new_instance['custom_7_label'])) : 'Custom 7:',
      'order' => is_numeric($new_instance['custom_7_order'])? $new_instance['custom_7_order'] : 12,
    );
    $instance['fields']['custom_8'] = array(
      'field_name' => 'custom_8',
      'display' => $new_instance['custom_8_display'] == 1? 1 : 0,
      'required' => $new_instance['custom_8_required'] == 1? 1  : 0,
      'required_message' => !empty($new_instance['custom_8_required_message'])? stripslashes(trim($new_instance['custom_8_required_message'])) : 'Custom 8 is required.',
      'label' => !empty($new_instance['custom_8_label'])? stripslashes(trim($new_instance['custom_8_label'])) : 'Custom 8:',
      'order' => is_numeric($new_instance['custom_8_order'])? $new_instance['custom_8_order'] : 13,
    );
    $instance['fields']['custom_9'] = array(
      'field_name' => 'custom_9',
      'display' => $new_instance['custom_9_display'] == 1? 1 : 0,
      'required' => $new_instance['custom_9_required'] == 1? 1  : 0,
      'required_message' => !empty($new_instance['custom_9_required_message'])? stripslashes(trim($new_instance['custom_9_required_message'])) : 'Custom 9 is required.',
      'label' => !empty($new_instance['custom_9_label'])? stripslashes(trim($new_instance['custom_9_label'])) : 'Custom 9:',
      'order' => is_numeric($new_instance['custom_9_order'])? $new_instance['custom_9_order'] : 14,
    );
    $instance['fields']['custom_10'] = array(
      'field_name' => 'custom_10',
      'display' => $new_instance['custom_10_display'] == 1? 1 : 0,
      'required' => $new_instance['custom_10_required'] == 1? 1  : 0,
      'required_message' => !empty($new_instance['custom_10_required_message'])? stripslashes(trim($new_instance['custom_10_required_message'])) : 'Custom 10 is required.',
      'label' => !empty($new_instance['custom_10_label'])? stripslashes(trim($new_instance['custom_10_label'])) : 'Custom 10:',
      'order' => is_numeric($new_instance['custom_10_order'])? $new_instance['custom_10_order'] : 15,
    );

    $instance['groups'] = array_keys($new_instance['groups']);
    //$instance['groups'] = is_array( $new_instance['groups'] )? $new_instance['groups'] : array();

    // Clean up the free-form areas
    $instance['title'] = stripslashes($new_instance['title']);
    $instance['text'] = stripslashes($new_instance['text']);
    $instance['submit'] = stripslashes($new_instance['submit']);

    // If the current user isn't allowed to use unfiltered HTML, filter it
    if ( !current_user_can('unfiltered_html') ) {
      $instance['title'] = strip_tags($new_instance['title']);
      $instance['text'] = strip_tags($new_instance['text']);
      foreach($instance['fields'] as &$field){
      	$field['label'] = strip_tags($field['label']);
        $field['required_message'] = strip_tags($field['required_message']);
      }
    }

    return $instance;
  }
}

/**
 * Widget Specific
 */

function emfluence_field_order_sort($a, $b){
	if($a['order'] == $b['order']){
		return 0;
	}
  return ($a['order'] < $b['order']) ? -1 : 1;
}

function emfluence_bootsrap(){
  global $emfluence_client_key, $emfluence_api_key, $emfluence_groups_enabled, $emfluence_authenticated;
	$emfluence_client_key = get_option('emfluence_client_key', '');
  $emfluence_api_key = get_option('emfluence_api_key', '');
  $emfluence_groups_enabled = get_option('emfluence_groups_enabled', array());
  $emfluence_authenticated = get_option('emfluence_authenticated', FALSE);
}

function emfluence_get_current_page_url() {
   $pageURL = 'http';
   if (isset($_SERVER["HTTPS"]) AND ($_SERVER["HTTPS"] == "on")) {$pageURL .= "s";}
   $pageURL .= "://";
   if ($_SERVER["SERVER_PORT"] != "80") {
    $pageURL .= $_SERVER["SERVER_NAME"].":".$_SERVER["SERVER_PORT"].$_SERVER["REQUEST_URI"];
   } else {
    $pageURL .= $_SERVER["SERVER_NAME"].$_SERVER["REQUEST_URI"];
   }
   return $pageURL;
}

/**
 * Validates an email
 *
 * @param string $email
 * @return boolean
 */
function emfluence_validate_email($email) {
  // Ensure the basic pattern is correct
  if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    return false;
  } else {
    // Test the domain's MX records to avoid more fake domains in the correct pattern.
    list($user, $domain) = explode('@', $email);
    try {
      if( !checkdnsrr($domain, 'MX') ){
        return false;
      }
    } catch( Exception $e ){
      return false;
    }
  }
  return true;
}

/**
 * Emfluence API Calls
 */

/**
 * @return array
 */
function emfluence_api_authenticate($client_key = NULL, $api_key = NULL){
  $return = array(
    'success' => TRUE,
    'messages' => array(),
  );
  if( $client_key === NULL ){
  	global $emfluence_client_key;
    $client_key = $emfluence_client_key;
  }

  if( $api_key === NULL ){
    global $emfluence_api_key;
    $api_key = $emfluence_api_key;
  }

  if( empty($client_key) ){
    $return['success'] = FALSE;
  	$return['messages'][] = array('type' => 'error', 'value' => 'Client key undefined.');
  }

  if( empty($api_key) ){
    $return['success'] = FALSE;
    $return['messages'][] = array('type' => 'error', 'value' => 'Api key undefined.');
  }

  if( $return['success'] == FALSE ){
  	return $return;
  }

  // Lookup
  $response = wp_remote_get( 'https://emailer.emfluence.com/app/webservices/wp/1.0/authenticate?clientKey=' . $client_key . '&apiKey=' . $api_key  );
  if( !$response['response']['code'] == '200' ){
    $return['success'] = FALSE;
    $return['messages'][] = array('type' => 'error', 'value' => 'Error contacting emfluence platform. Error ' . $response['response']['code'] . ' "' . $response['response']['message'] . '""' );
    return $return;
  } else {
  	$data = json_decode($response['body']);
    if( !$data->success ){
      $return['success'] = FALSE;
      foreach($data->messages as $message){
      	$return['messages'][] = array('type' => 'error', 'value' => $message->text);
      }
      return $return;
    }
  }

  return $return;
}

function emfluence_api_groups_search($client_key = NULL, $api_key = NULL){
  $return = array(
    'success' => TRUE,
    'messages' => array(),
    'groups' => array(),
  );

  if( $client_key === NULL ){
    global $emfluence_client_key;
    $client_key = $emfluence_client_key;
  }

  if( $api_key === NULL ){
    global $emfluence_api_key;
    $api_key = $emfluence_api_key;
  }

  if( empty($client_key) ){
    $return['success'] = FALSE;
    $return['messages'][] = array('type' => 'error', 'value' => 'Client key undefined.');
  }

  if( empty($api_key) ){
    $return['success'] = FALSE;
    $return['messages'][] = array('type' => 'error', 'value' => 'Api key undefined.');
  }

  if( $return['success'] == FALSE ){
    return $return;
  }

  // Lookup
	$response = wp_remote_get( 'https://emailer.emfluence.com/app/webservices/wp/1.0/groups/search?clientKey=' . $client_key . '&apiKey=' . $api_key  );
  if( !$response['response']['code'] == '200' ){
    $return['success'] = FALSE;
    $return['messages'][] = array('type' => 'error', 'value' => 'Error contacting emfluence platform. Error ' . $response['response']['code'] . ' "' . $response['response']['message'] . '""' );
    return $return;
  } else {
    $data = json_decode($response['body']);
    if( !$data->success ){
      $return['success'] = FALSE;
      foreach($data->messages as $message){
        $return['messages'][] = array('type' => 'error', 'value' => $message->text);
      }
      return $return;
    } else {
      foreach($data->data as $group){
      	$return['groups'][$group->id] = $group;
      }
    }
  }

  return $return;
}

function emfluence_api_subscription_start($form_data){
  $return = array(
    'success' => TRUE,
    'messages' => array(),
  );

  global $emfluence_client_key, $emfluence_api_key;
  $data = array();
  $data['clientKey'] = $emfluence_client_key;
  $data['apiKey'] = $emfluence_api_key;
  $data['action'] = 'add';
  $data['listID'] = trim( $form_data['lists'] );
  $data['originalSource'] = trim( $form_data['source'] );
  $data['firstName'] = !empty( $form_data['first_name'] )? trim( $form_data['first_name'] ) : '';
  $data['lastName'] = !empty( $form_data['last_name'] )? trim( $form_data['last_name'] ) : '';
  $data['title'] = !empty( $form_data['title'] )? trim( $form_data['title'] ) : '';
  $data['company'] = !empty( $form_data['company'] )? trim( $form_data['company'] ) : '';
  // $data['phone'] = trim( $form_data['phone_number'] );
  $data['email'] = trim( $form_data['email'] );
  $data['custom1'] = !empty( $form_data['custom_1'] )? trim( $form_data['custom_1'] ) : '';
  $data['custom2'] = !empty( $form_data['custom_2'] )? trim( $form_data['custom_2'] ) : '';
  $data['custom3'] = !empty( $form_data['custom_3'] )? trim( $form_data['custom_3'] ) : '';
  $data['custom4'] = !empty( $form_data['custom_4'] )? trim( $form_data['custom_4'] ) : '';
  $data['custom5'] = !empty( $form_data['custom_5'] )? trim( $form_data['custom_5'] ) : '';
  $data['custom6'] = !empty( $form_data['custom_6'] )? trim( $form_data['custom_6'] ) : '';
  $data['custom7'] = !empty( $form_data['custom_7'] )? trim( $form_data['custom_7'] ) : '';
  $data['custom8'] = !empty( $form_data['custom_8'] )? trim( $form_data['custom_8'] ) : '';
  $data['custom9'] = !empty( $form_data['custom_9'] )? trim( $form_data['custom_9'] ) : '';
  $data['custom10'] = !empty( $form_data['custom_10'] )? trim( $form_data['custom_10'] ) : '';

  $url = 'https://emailer.emfluence.com/subscription_handler.cfm';
  $request = new WP_Http;
  $result = $request->request( $url, array( 'method' => 'POST', 'body' => $data) );

  if ( $result['response']['code'] != '200' ){
    $return['success'] = FALSE;
    $return['messages'][] = array('type' => 'error', 'value' => translate('An error occured contacting the email service. Error code: ') . $result['response']['code'] . ' "' . $result['response']['status'] . '."');
    return $return;
  } else {
    // Check if the signup was a success
    $signup_response = explode('|', $result['body']);
    if( $signup_response[0] != '1' ){
      $return['success'] = FALSE;
      $return['messages'][] = array('type' => 'error', 'value' => translate('An error occured starting your subscription: ') . ' "' . $signup_response[2] . '."');
    } else {
    	$return['messages'][] = array('type' => 'success', 'value' => $signup_response[2]);
    }
  }
  return $return;
}
