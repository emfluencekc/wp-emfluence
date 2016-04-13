<?php

class emfluence_email_signup extends WP_Widget {

  function __construct(){
    /* Widget settings. */
    $widget_ops = array( 'classname' => 'emfluence_email_signup', 'description' => 'Creates an email signup form for your visitors.' );

    /* Widget control settings. */
    $control_ops = array( 'width' => 400, 'id_base' => 'emfluence_email_signup' );

    /* Create the widget. */
    $this->WP_Widget( 'emfluence_email_signup', 'emfluence Marketing Platform Email Signup', $widget_ops, $control_ops );
  }

  /**
   * Provides a static cache for groups
   */
  static function get_groups(){
    static $groups = NULL;

    $options = get_option('emfluence_global');
    $api = emfluence_get_api($options['api_key']);

    if( $groups === NULL ){
      $groups = array();
      $more = TRUE;
      $page_number = 1;
      while($more){
        $response = $api->groups_search(array(
            'rpp' => 50,
            'page' => $page_number,
        ));
        if( !$response || !$response->success ){
          $more = FALSE;
          break;
        }
        foreach( $response->data->records as $group ){
          $groups[$group->groupID] = $group;
        }
        if( !$response->data->paging->nextUrl ){
          $more = FALSE;
        } else {
          ++$page_number;
        }
      }
    }
    return $groups;
  }

  /**
   * Validates an email
   *
   * @param string $email
   * @return boolean
   */
  protected function validate_email($email) {
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

  protected function get_current_page_url() {
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
      $output = '';
      $valid = TRUE;
      // Set the field values in case there's an error
      foreach( $_POST as $key => $value ){
        $values[$key] = htmlentities( trim( $value ) );
      }

      foreach( $instance['fields'] as $key => $field ){
        if( $field['required'] && empty( $values[$key] ) ){
          $valid = FALSE;
          $messages[] = array( 'type' => 'error', 'value' => __( $field['required_message'] ) );
        } elseif ( $key == 'email' && !$this->validate_email( $values[$key] ) ){
          $valid = FALSE;
          $messages[] = array( 'type' => 'error', 'value' => __('Invalid email address.') );
        }
      }

      if( $valid ){
        $success = FALSE;
        $messages = array();
        // Try to subscribe them
        $options = get_option('emfluence_global');
        $api = emfluence_get_api($options['api_key']);

        $data = array();
        $data['groupIDs'] = !empty($_POST['groups'])? $_POST['groups'] : '';
        $data['originalSource'] = trim( $_POST['source'] );
        $data['firstName'] = !empty( $_POST['first_name'] )? trim( $_POST['first_name'] ) : '';
        $data['lastName'] = !empty( $_POST['last_name'] )? trim( $_POST['last_name'] ) : '';
        $data['title'] = !empty( $_POST['title'] )? trim( $_POST['title'] ) : '';
        $data['company'] = !empty( $_POST['company'] )? trim( $_POST['company'] ) : '';
        // $data['phone'] = trim( $form_data['phone_number'] );
        $data['email'] = trim( $_POST['email'] );
        $data['customFields'] = array();
        for( $i = 1; $i <= 10; $i++ ){
          $field = 'custom' . $i;
          $parameter = 'custom_' . $i;
          if( empty($_POST[$parameter]) ){
            continue;
          }
          $data['customFields'][$field] = array(
              'value' => trim( $_POST[$parameter] ),
          );
        }
        $result = $api->contacts_save($data);

        if( empty($result->success) ){
          $messages[] = array('type' => 'error', 'value' => __('An error occurred contacting the email service.'));
        } else {
          /* Before widget (defined by themes). */
          $output .= $before_widget . '<form class="mail-form" method="post"><div class="holder"><div class="frame">';

          /* Title of widget (before and after defined by themes). */
          if ( $title ){
            $output .= $before_title . '<span>' . $title . '</span>' . $after_title;

            ob_start();
            get_template_part('emfluence/success');
            $message = ob_get_clean();
            if(empty($message)) $message = file_get_contents( 'theme/success.php', TRUE);
            $output .= $message;

            $output .= '</div></div></form>' . $after_widget;

            print $output;
            return;
          }
        }
      }
    }

    /* Before widget (defined by themes). */
    $output .= $before_widget . '<form class="mail-form" method="post"><div class="holder"><div class="frame">';

    /* Title of widget (before and after defined by themes). */
    if ( $title ) {
      $output .= $before_title . '<span>' . $title . '</span>' . $after_title;
    }

    // Output all messages
    if( !empty($messages) ){
      $output .= '<ul class="messages">';
      foreach($messages as $message){
        $output .= '<li class="message ' . $message['type'] . '">' . __($message['value']) . '</li>';
      }
      $output .= '</ul>';
    }

    if( !empty($instance['text']) ) {
      $output .= '<div class="lead">' . wpautop($instance['text']) . '</div>';
    }

    $current_page_url = remove_query_arg('sucess', $this->get_current_page_url());
    $output .= '<form action="' . $current_page_url . '" method="POST">' . "\n";
    $output .= '<input type="hidden" name="action" value="email_signup" />' . "\n";
    $output .= '<input type="hidden" name="source" value="' . $current_page_url . '" />' . "\n";
    $output .= '<input type="hidden" name="groups" value="' . implode(',', $instance['groups']) . '" />' . "\n";

    usort($instance['fields'], function($a, $b){
      if($a['order'] == $b['order']){
        return 0;
      }
      return ($a['order'] < $b['order']) ? -1 : 1;
    });
    foreach( $instance['fields'] as $key => $field ){
      if( $field['display'] ){
        $label = __($field['label']);
        $placeholder = __( str_replace(':', '', $field['label']) );
        $required = $field['required']? 'required' : '';
        switch( $field['type'] ){
          case 'text':
          default:
            $output .= '<div class="field row field-' . $key . '">' . "\n";
            $output .= '<label for="emfluence_' . $key . '">' . $label . '';
            if( $field['required'] ){
              $output .= '<span class="required">*</span>';
            }
            $input_type = ($field['field_name']=='email') ? 'email' : 'text';
            $output .= '</label>' . "\n";
            $output .=   '<input placeholder="' . $placeholder . '" type="' . $input_type . '" name="' . $field['field_name'] . '" id="emfluence_' . $key . '" value="' . $values[$field['field_name']] . '" ' . $required . ' />' . "\n";
            $output .= '</div>' . "\n";
            break;
        }
      }
    }

    $output .= '<div class="row actions"><input type="submit" class="submit" value="' . htmlentities( $instance['submit'], ENT_QUOTES ) . '" /></div>' . "\n";
    $output .= '</form>' . "\n";

    /* After widget (defined by themes). */
    $output .= '</div></div></form>' . $after_widget;

    echo $output;

    return;
  }

  public function form( $instance ) {
    $options = get_option('emfluence_global');
    $api = emfluence_get_api($options['api_key']);

    if( !$api->ping() ){
      $output = '<h3>' . __('Authentication Failed') . '</h3>';
      $output .= '<p>' . __('Please check your api key to continue.') . '</p>';
      print $output;
      return;
    }

    // Pull back the groups
    $groups = emfluence_email_signup::get_groups();

    /* Set up some default widget settings. */
    $defaults = array(
        'title' => __('Email Signup'),
        'text' => '',
        'groups' => array(),
        'submit' => __('Signup'),
        'fields' => array(
            'first_name' => array(
                'name' => __('First Name'),
                'display' => 1,
                'required' => 1,
                'required_message' => __('First name is required.'),
                'label' => __('First Name:'),
                'order' => 1,
            ),
            'last_name' => array(
                'name' => __('Last Name'),
                'display' => 1,
                'required' => 1,
                'required_message' => __('Last name is required.'),
                'label' => __('Last Name:'),
                'order' => 2,
            ),
            'title' => array(
                'name' => __('Title'),
                'display' => 0,
                'required' => 0,
                'required_message' => __('Title is required.'),
                'label' => __('Title:'),
                'order' => 3,
            ),
            'company' => array(
                'name' => __('Company'),
                'display' => 0,
                'required' => 0,
                'required_message' => __('Company is required.'),
                'label' => __('Company:'),
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
                'name' => __('Custom 2'),
                'display' => 0,
                'required' => 0,
                'required_message' => __('Custom 2 is required.'),
                'label' => __('Custom 2:'),
                'order' => 7,
            ),
            'custom_3' => array(
                'name' => __('Custom 3'),
                'display' => 0,
                'required' => 0,
                'required_message' => __('Custom 3 is required.'),
                'label' => __('Custom 3:'),
                'order' => 8,
            ),
            'custom_4' => array(
                'name' => __('Custom 4'),
                'display' => 0,
                'required' => 0,
                'required_message' => __('Custom 4 is required.'),
                'label' => __('Custom 4:'),
                'order' => 9,
            ),
            'custom_5' => array(
                'name' => __('Custom 5'),
                'display' => 0,
                'required' => 0,
                'required_message' => __('Custom 5 is required.'),
                'label' => __('Custom 5:'),
                'order' => 10,
            ),
            'custom_6' => array(
                'name' => __('Custom 6'),
                'display' => 0,
                'required' => 0,
                'required_message' => __('Custom 6 is required.'),
                'label' => __('Custom 6:'),
                'order' => 11,
            ),
            'custom_7' => array(
                'name' => __('Custom 7'),
                'display' => 0,
                'required' => 0,
                'required_message' => __('Custom 7 is required.'),
                'label' => __('Custom 7:'),
                'order' => 12,
            ),
            'custom_8' => array(
                'name' => __('Custom 8'),
                'display' => 0,
                'required' => 0,
                'required_message' => __('Custom 8 is required.'),
                'label' => __('Custom 8:'),
                'order' => 13,
            ),
            'custom_9' => array(
                'name' => __('Custom 9'),
                'display' => 0,
                'required' => 0,
                'required_message' => __('Custom 9 is required.'),
                'label' => __('Custom 9:'),
                'order' => 14,
            ),
            'custom_10' => array(
                'name' => __('Custom 10'),
                'display' => 0,
                'required' => 0,
                'required_message' => __('Custom 10 is required.'),
                'label' => __('Custom 10:'),
                'order' => 15,
            ),
        ),
    );

    $instance = wp_parse_args( (array) $instance, $defaults );

    $output = '';
    $output .= '<h3>' . __('Text Display') . '</h3>' . "\n";
    $output .= '<p>' . "\n";
    $output .=  '<label for="' . $this->get_field_id( 'title' ) . '">' . __('Title') . ':</label>' . "\n";
    $output .=  '<input type="text" id="' . $this->get_field_id( 'title' ) . '" name="' . $this->get_field_name( 'title' ) . '" value="' . $instance['title'] . '" style="width:100%;" />' . "\n";
    $output .= '</p>' . "\n";
    $output .= '<p>' . "\n";
    $output .=  '<label for="' . $this->get_field_id( 'text' ) . '">' . __('Text') . ':</label>' . "\n";
    $output .=  '<textarea id="' . $this->get_field_id( 'text' ) . '" name="' . $this->get_field_name( 'text' ) . '" style="width:100%;" >' . $instance['text'] . '</textarea>' . "\n";
    $output .= '</p>' . "\n";
    $output .= '<p>' . "\n";
    $output .=  '<label for="' . $this->get_field_id( 'submit' ) . '">' . __('Submit button') . ':</label>' . "\n";
    $output .=  '<input type="text" id="' . $this->get_field_id( 'submit' ) . '" name="' . $this->get_field_name( 'submit' ) . '" value="' . $instance['submit'] . '" style="width:100%;" />' . "\n";
    $output .= '</p>' . "\n";

    $output .= '<div class="groups">';
    $output .= '<h3>' . __('Groups') . '</h3>' . "\n";
    $output .= '  <div class="filter">' . "\n";
    $output .= '    <p>' . __('Search for any group by name to add them to the list of options for users.') . '</p>' . "\n";
    $output .= '    <p>';
    $output .= '      <input list="emfluence-emailer-groups-list"/>' . "\n";
    $output .= '      <button type="button" onclick="emfluenceEmailerWidget.groups.add(this)">' . __('Add') . '</button>';
    $output .= '    </p>';
    $output .= '  </div>';
    $output .= '  <div class="selected">' . "\n";
    if( !empty($instance['groups']) ) {
      foreach ($instance['groups'] as $groupID) {
        $group = $groups[$groupID];
        $id = 'groups-' . $this->number . '-' . $groupID;
        $output .=
            '<div><label for="' . $id . '">
            <input id="' . $id . '" type="checkbox" value="' . $groupID . '" name="groups[]" checked /> ' . $group->groupName . '
          </label></div>';
      }
    }
    $output .= '  </div>';
    $output .= '</div>';

    $output .= '<h3>' . __('Fields') . '</h3>' . "\n";
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

      $output .= '<h3>' . __($field['name']) . '</h3>' . "\n";
      $output .= '<p>' . "\n";
      $output .=  '<label for="' . $display_input['id'] . '">' . "\n";
      $output .=   '<input type="checkbox" id="' . $display_input['id'] . '" name="' . $display_input['name'] . '" value="1" ' . $display_input['checked'] . ' ' . $display_input['disabled'] . ' />' . "\n";
      $output .=   __('Display');
      $output .=   '</label>' . "\n";
      $output .=   ' ';
      $output .=  '<label for="' . $required_input['id'] . '">' . "\n";
      $output .=   '<input type="checkbox" id="' . $required_input['id'] . '" name="' . $required_input['name'] . '" value="1" ' . $required_input['checked'] . ' ' . $display_input['disabled'] . ' />' . "\n";
      $output .=   __('Required');
      $output .=   '</label>' . "\n";
      $output .= '</p>' . "\n";
      $output .= '<p>' . "\n";
      $output .=  '<label for="' . $required_message_input['id'] . '">' . __('Required Message') . '</label>' . "\n";
      $output .=  '<input type="text" id="' . $required_message_input['id'] . '" name="' . $required_message_input['name'] . '" value="' . $required_message_input['value'] . '" style="width:100%;" />' . "\n";
      $output .= '</p>' . "\n";
      $output .= '<p>' . "\n";
      $output .=  '<label for="' . $label_input['id'] . '">' . __('Label') . '</label>' . "\n";
      $output .=  '<input type="text" id="' . $label_input['id'] . '" name="' . $label_input['name'] . '" value="' . $label_input['value'] . '" style="width:100%;" />' . "\n";
      $output .= '</p>' . "\n";
      $output .= '<p>' . "\n";
      $output .=  '<label for="' . $order_input['id'] . '">' . __('Order') . '</label>' . "\n";
      $output .=  '<input type="text" id="' . $order_input['id'] . '" name="' . $order_input['name'] . '" value="' . $order_input['value'] . '" style="width:100%;" />' . "\n";
      $output .= '</p>' . "\n";
    }

    // Output the datalist for groups just once
    if( intval($this->number) == 0  ) {
      $output .= '<datalist id="emfluence-emailer-groups-list">';
      foreach ($groups as $group) {
        $output .= '<option>' . $group->groupName . ' [' . $group->groupID . ']' . '</option>';
      }
      $output .= '</datalist>';
    }

    // TODO Current problems:
    // 1. When creating a new instance, no new form is passed through here. (JS has to be super generic)
    // 2. The datalist we're creating works well, but generating checkboxes 'on input' isn't ready.
    // 3. We need to load the current groups of the instance in as checkboxes during form generation

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
        'label' => !empty($new_instance['first_name_label'])? stripslashes(trim($new_instance['first_name_label'])) : __('First Name:'),
        'order' => is_numeric($new_instance['first_name_order'])? $new_instance['first_name_order'] : 1,
    );
    $instance['fields']['last_name'] = array(
        'field_name' => 'last_name',
        'display' => $new_instance['last_name_display'] == 1? 1 : 0,
        'required' => $new_instance['last_name_required'] == 1? 1  : 0,
        'required_message' => !empty($new_instance['last_name_required_message'])? stripslashes(trim($new_instance['last_name_required_message'])) : 'Last name is required.',
        'label' => !empty($new_instance['last_name_label'])? stripslashes(trim($new_instance['last_name_label'])) : __('Last Name:'),
        'order' => is_numeric($new_instance['last_name_order'])? $new_instance['last_name_order'] : 2,
    );
    $instance['fields']['title'] = array(
        'field_name' => 'title',
        'display' => $new_instance['title_display'] == 1? 1 : 0,
        'required' => $new_instance['title_required'] == 1? 1  : 0,
        'required_message' => !empty($new_instance['title_required_message'])? stripslashes(trim($new_instance['title_required_message'])) : 'Title is required.',
        'label' => !empty($new_instance['title_label'])? stripslashes(trim($new_instance['title_label'])) : __('Title:'),
        'order' => is_numeric($new_instance['title_order'])? $new_instance['title_order'] : 3,
    );
    $instance['fields']['company'] = array(
        'field_name' => 'company',
        'display' => $new_instance['company_display'] == 1? 1 : 0,
        'required' => $new_instance['company_required'] == 1? 1  : 0,
        'required_message' => !empty($new_instance['company_required_message'])? stripslashes(trim($new_instance['company_required_message'])) : 'Company is required.',
        'label' => !empty($new_instance['company_label'])? stripslashes(trim($new_instance['company_label'])) : __('Company:'),
        'order' => is_numeric($new_instance['company_order'])? $new_instance['company_order'] : 4,
    );
    $instance['fields']['email'] = array(
        'field_name' => 'email',
        'display' => $new_instance['email_display'] = 1, // This cannot be optional
        'required' => $new_instance['email_required'] = 1, // This cannot be optional
        'required_message' => !empty($new_instance['email_required_message'])? stripslashes(trim($new_instance['email_required_message'])) : 'Email address is required.',
        'label' => !empty($new_instance['email_label'])? stripslashes(trim($new_instance['email_label'])) : __('Email:'),
        'order' => is_numeric($new_instance['email_order'])? $new_instance['email_order'] : 5,
    );
    $instance['fields']['custom_1'] = array(
        'field_name' => 'custom_1',
        'display' => $new_instance['custom_1_display'] == 1? 1 : 0,
        'required' => $new_instance['custom_1_required'] == 1? 1  : 0,
        'required_message' => !empty($new_instance['custom_1_required_message'])? stripslashes(trim($new_instance['custom_1_required_message'])) : 'Custom 1 is required.',
        'label' => !empty($new_instance['custom_1_label'])? stripslashes(trim($new_instance['custom_1_label'])) : __('Custom 1:'),
        'order' => is_numeric($new_instance['custom_1_order'])? $new_instance['custom_1_order'] : 6,
    );
    $instance['fields']['custom_2'] = array(
        'field_name' => 'custom_2',
        'display' => $new_instance['custom_2_display'] == 1? 1 : 0,
        'required' => $new_instance['custom_2_required'] == 1? 1  : 0,
        'required_message' => !empty($new_instance['custom_2_required_message'])? stripslashes(trim($new_instance['custom_2_required_message'])) : 'Custom 2 is required.',
        'label' => !empty($new_instance['custom_2_label'])? stripslashes(trim($new_instance['custom_2_label'])) : __('Custom 2:'),
        'order' => is_numeric($new_instance['custom_2_order'])? $new_instance['custom_2_order'] : 7,
    );
    $instance['fields']['custom_3'] = array(
        'field_name' => 'custom_3',
        'display' => $new_instance['custom_3_display'] == 1? 1 : 0,
        'required' => $new_instance['custom_3_required'] == 1? 1  : 0,
        'required_message' => !empty($new_instance['custom_3_required_message'])? stripslashes(trim($new_instance['custom_3_required_message'])) : 'Custom 3 is required.',
        'label' => !empty($new_instance['custom_3_label'])? stripslashes(trim($new_instance['custom_3_label'])) : __('Custom 3:'),
        'order' => is_numeric($new_instance['custom_3_order'])? $new_instance['custom_3_order'] : 8,
    );
    $instance['fields']['custom_4'] = array(
        'field_name' => 'custom_4',
        'display' => $new_instance['custom_4_display'] == 1? 1 : 0,
        'required' => $new_instance['custom_4_required'] == 1? 1  : 0,
        'required_message' => !empty($new_instance['custom_4_required_message'])? stripslashes(trim($new_instance['custom_4_required_message'])) : 'Custom 4 is required.',
        'label' => !empty($new_instance['custom_4_label'])? stripslashes(trim($new_instance['custom_4_label'])) : __('Custom 4:'),
        'order' => is_numeric($new_instance['custom_4_order'])? $new_instance['custom_4_order'] : 9,
    );
    $instance['fields']['custom_5'] = array(
        'field_name' => 'custom_5',
        'display' => $new_instance['custom_5_display'] == 1? 1 : 0,
        'required' => $new_instance['custom_5_required'] == 1? 1  : 0,
        'required_message' => !empty($new_instance['custom_5_required_message'])? stripslashes(trim($new_instance['custom_5_required_message'])) : 'Custom 5 is required.',
        'label' => !empty($new_instance['custom_5_label'])? stripslashes(trim($new_instance['custom_5_label'])) : __('Custom 5:'),
        'order' => is_numeric($new_instance['custom_5_order'])? $new_instance['custom_5_order'] : 10,
    );
    $instance['fields']['custom_6'] = array(
        'field_name' => 'custom_6',
        'display' => $new_instance['custom_6_display'] == 1? 1 : 0,
        'required' => $new_instance['custom_6_required'] == 1? 1  : 0,
        'required_message' => !empty($new_instance['custom_6_required_message'])? stripslashes(trim($new_instance['custom_6_required_message'])) : 'Custom 6 is required.',
        'label' => !empty($new_instance['custom_6_label'])? stripslashes(trim($new_instance['custom_6_label'])) : __('Custom 6:'),
        'order' => is_numeric($new_instance['custom_6_order'])? $new_instance['custom_6_order'] : 11,
    );
    $instance['fields']['custom_7'] = array(
        'field_name' => 'custom_7',
        'display' => $new_instance['custom_7_display'] == 1? 1 : 0,
        'required' => $new_instance['custom_7_required'] == 1? 1  : 0,
        'required_message' => !empty($new_instance['custom_7_required_message'])? stripslashes(trim($new_instance['custom_7_required_message'])) : 'Custom 7 is required.',
        'label' => !empty($new_instance['custom_7_label'])? stripslashes(trim($new_instance['custom_7_label'])) : __('Custom 7:'),
        'order' => is_numeric($new_instance['custom_7_order'])? $new_instance['custom_7_order'] : 12,
    );
    $instance['fields']['custom_8'] = array(
        'field_name' => 'custom_8',
        'display' => $new_instance['custom_8_display'] == 1? 1 : 0,
        'required' => $new_instance['custom_8_required'] == 1? 1  : 0,
        'required_message' => !empty($new_instance['custom_8_required_message'])? stripslashes(trim($new_instance['custom_8_required_message'])) : 'Custom 8 is required.',
        'label' => !empty($new_instance['custom_8_label'])? stripslashes(trim($new_instance['custom_8_label'])) : __('Custom 8:'),
        'order' => is_numeric($new_instance['custom_8_order'])? $new_instance['custom_8_order'] : 13,
    );
    $instance['fields']['custom_9'] = array(
        'field_name' => 'custom_9',
        'display' => $new_instance['custom_9_display'] == 1? 1 : 0,
        'required' => $new_instance['custom_9_required'] == 1? 1  : 0,
        'required_message' => !empty($new_instance['custom_9_required_message'])? stripslashes(trim($new_instance['custom_9_required_message'])) : 'Custom 9 is required.',
        'label' => !empty($new_instance['custom_9_label'])? stripslashes(trim($new_instance['custom_9_label'])) : __('Custom 9:'),
        'order' => is_numeric($new_instance['custom_9_order'])? $new_instance['custom_9_order'] : 14,
    );
    $instance['fields']['custom_10'] = array(
        'field_name' => 'custom_10',
        'display' => $new_instance['custom_10_display'] == 1? 1 : 0,
        'required' => $new_instance['custom_10_required'] == 1? 1  : 0,
        'required_message' => !empty($new_instance['custom_10_required_message'])? stripslashes(trim($new_instance['custom_10_required_message'])) : 'Custom 10 is required.',
        'label' => !empty($new_instance['custom_10_label'])? stripslashes(trim($new_instance['custom_10_label'])) : __('Custom 10:'),
        'order' => is_numeric($new_instance['custom_10_order'])? $new_instance['custom_10_order'] : 15,
    );

    // Unfortunately, these don't come through $new_instance
    $instance['groups'] = array_values($_POST['groups']);

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
