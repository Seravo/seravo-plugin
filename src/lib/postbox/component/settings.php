<?php

namespace Seravo\Postbox;

/**
 * Class Settings
 *
 * Settings class is an easy to use wrapper for WordPress core
 * settings API. The wrapper is always more compatible with Seravo postboxes.
 */
class Settings {

  /**
   * @var string Field type for textfield.
   */
  const FIELD_TYPE_STRING = 'string';
  /**
   * @var string Field type for checkbox.
   */
  const FIELD_TYPE_BOOLEAN = 'boolean';
  /**
   * @var string Field type for number field with whole numbers.
   */
  const FIELD_TYPE_INTEGER = 'integer';
  /**
   * @var string Field type for number field with any numbers.
   */
  const FIELD_TYPE_NUMBER = 'number';

  /**
   * @var string      Unique ID for the section.
   */
  private $section;
  /**
   * @var string|null Title for the section. This field is optional.
   */
  private $title;
  /**
   * @var string|null Postbox ID for the section. This replaces pages in WordPress setting API.
   */
  private $postbox;
  /**
   * @var array<mixed, array<string, mixed>> Fields to be added on register().
   */
  private $fields = array();

  /**
   * Constructor for Settings. Will be called on new instance.
   * @param string $section Unique id/slug for the section.
   * @param string $title   Title for the section. Set null/empty for no title.
   */
  public function __construct( $section, $title = null ) {
    $this->section = $section;
    $this->title = $title;
  }

  /**
   * Register the section and fields. The settings have already been automatically
   * registered but the fields and section must be registered seperately for the WordPress
   * settings API. This is called automatically when section is added for a postbox.
   * @return void
   */
  public function register() {
    if ( $this->postbox === null ) {
      return;
    }

    \add_settings_section(
      $this->section,
      $this->title !== null ? $this->title : '',
      function() {
        return '';
      },
      'seravo-' . $this->postbox
    );

    foreach ( $this->fields as $field ) {
      \add_settings_field(
        $field['name'],
        $field['title'],
        $field['callback'],
        $this->postbox,
        $this->section,
        $field['args']
      );
    }
  }

  /**
   * Add a field for the section.
   * @param string        $name        Name of the field. Should be prefixed with "seravo-". This is used for the field name and option name.
   * @param string        $title       Label for the field. Use empty for no field (not recommended).
   * @param string        $placeholder The placeholder text for the field. This is not supported by all the field types.
   * @param string        $description Description for the field. This may be empty. Description is printed above the field.
   * @param string        $type        Type of the field. Use type constants in Settings::FIELD_TYPE_*.
   * @param mixed         $default     Default data for the option.
   * @param callable|null $sanitizer   Sanitizer function for the field. Most field types have one built-in but custom may be set.
   * @param callable|null $build_func  Function for building the field. Using this is optional and not recommended. Should return \Seravo\Postbox\Component.
   * @return void
   */
  public function add_field( $name, $title, $placeholder, $description, $type, $default = null, $sanitizer = null, $build_func = null ) {
    $this->determine_type($name, $type, $default, $sanitizer, $build_func);

    \register_setting(
      $this->section,
      $name,
      array(
        'type' => $type,
        'description' => '',
        'sanitize_callback' => $sanitizer,
        'show_in_rest' => false,
        'default' => $default,
      )
    );

    $this->fields[] = array(
      'name' => $name,
      'title' => $title,
      'callback' => $build_func,
      'args' => array(
        'description' => $description,
        'placeholder' => $placeholder,
      ),
    );
  }

  /**
   * @param string        $name       Name of the field. Should be prefixed with "seravo-". This is used for the field name and option name.
   * @param string        $type       Type of the field. Use type constants in Settings::FIELD_TYPE_*.
   * @param mixed         $default    Default data for the option.
   * @param callable|null $sanitizer  Sanitizer function for the field. Most field types have one built-in but custom may be set.
   * @param callable|null $build_func Function for building the field. Using this is optional and not recommended. Should return \Seravo\Postbox\Component.
   * @return void
   */
  private function determine_type( &$name, &$type, &$default, &$sanitizer, &$build_func ) {
    if ( $build_func === null ) {
      // No build function, determine from type
      switch ( $type ) {
        case self::FIELD_TYPE_STRING:
          $build_func = array( __CLASS__, 'build_string_field' );
          break;
        case self::FIELD_TYPE_BOOLEAN:
          $build_func = array( __CLASS__, 'build_boolean_field' );
          $sanitizer = $sanitizer === null ? function( $value ) {
            return $this->sanitize_boolean_field($value);
          } : $sanitizer;
          break;
        case self::FIELD_TYPE_INTEGER:
          $build_func = array( __CLASS__, 'build_integer_field' );
          $sanitizer = $sanitizer === null ? function( $value ) use ( $default ) {
            return $this->sanitize_integer_field($value, $default);
          } : $sanitizer;
          break;
        case self::FIELD_TYPE_NUMBER:
          $build_func = array( __CLASS__, 'build_number_field' );
          $sanitizer = $sanitizer === null ? function( $value ) use ( $default ) {
            return $this->sanitize_number_field($value, $default);
          } : $sanitizer;
          break;
        default:
      }
    }
  }

  /**
   * Build the setting section component.
   * @return \Seravo\Postbox\Component Component with the section.
   */
  public function get_component() {
    global $wp_settings_fields;

    $base = new Component('');

    if ( ! isset($wp_settings_fields) || ! isset($wp_settings_fields[ $this->postbox ]) ) {
      // No sections in this postbox
      return $base;
    }

    if ( ! isset($wp_settings_fields[ $this->postbox ][ $this->section ]) ) {
      // This section doesn't exists, did you not register()?
      return $base;
    }

    $form = new Component('', '<form method="post" action="options.php" class="seravo-general-form">', '</form>');

    // Hidden fields
    $form->add_child(Component::from_raw('<input type="hidden" name="option_page" value="' . \esc_attr($this->section) . '"/>'));
    $form->add_child(Component::from_raw('<input type="hidden" name="action" value="update"/>'));

    // Add nonce and referer
    $referer = \esc_attr(\wp_unslash($_SERVER['REQUEST_URI'])) . '&section=' . $this->section;
    $form->add_child(Component::from_raw(\wp_nonce_field("{$this->section}-options", '_wpnonce', false, false)));
    $form->add_child(Component::from_raw('<input type="hidden" name="_wp_http_referer" value="' . $referer . '" />'));

    // Maybe add title
    if ( $this->title !== null ) {
      $form->add_child(Template::section_title($this->title));
    }

    // Add fields
    $fields = new Component('', '<table class="form-table" role="presentation">', '</table>');
    $fields->add_child($this->get_fields());
    $form->add_child($fields);

    // Submit button
    $form->add_child(Component::from_raw(\get_submit_button(__('Save', 'seravo'), 'primary', "{$this->section}-submit")));

    $base->add_child($form);

    return $base;
  }

  /**
   * Get components for the fields only.
   * @return \Seravo\Postbox\Component Component with the fields.
   */
  public function get_fields() {
    global $wp_settings_fields;

    $base = new Component();

    foreach ( $wp_settings_fields[ $this->postbox ][ $this->section ] as $field ) {
      $class = '';

      if ( isset($field['args']['class']) ) {
        $class = ' class="' . \esc_attr($field['args']['class']) . '"';
      }

      if ( isset($field['args']['description']) ) {
        $base->add_child(new Component($field['args']['description'], '<tr class="description ' . $class . '"><td colspan="2">', '</td></tr>'));
      }

      $row = new Component('', '<tr class="' . $class . '">', '</tr>');

      // Add field title
      if ( isset($field['args']['label_for']) ) {
        $row->add_child(new Component($field['title'], '<th scope="row"><label for="' . \esc_attr($field['args']['label_for']) . '">', '</label></th>'));
      } else {
        $row->add_child(new Component($field['title'], '<th scope="row">', '</th>'));
      }

      // Add input element
      $input = new Component('', '<td>', '</td>');

      if ( $field['callback'] === null || ! \is_callable($field['callback']) ) {
        continue;
      }

      $input->add_child(\call_user_func($field['callback'], $field));
      $row->add_child($input);
      $base->add_child($row);
    }

    return $base;
  }

  /**
   * Add a notification for this section. Each code is only used once even
   * if there's multiple notifications using it.
   * @param string $code    Code for identifying the notification.
   * @param string $message The message to be shown for the user.
   * @param string $type    Type of the message. Default is 'error'.
   * @return void
   */
  public function add_notification( $code, $message, $type = 'error' ) {
    \add_settings_error($this->section, $code, $message, $type);
  }

  /**
   * Get the notification component. This should be added at the
   * top of the postbox. If there's no notifications and a change
   * has been made, a success message is shown automatically.
   * @return \Seravo\Postbox\Component Component with the notifications.
   */
  public function get_notifications() {
    $notifications = \get_settings_errors($this->section);

    if ( $notifications === array() && isset($_REQUEST['settings-updated']) && $_REQUEST['settings-updated'] === 'true' && (isset($_REQUEST['section']) && $_REQUEST['section'] === $this->section) ) {
      $notifications = array(
        array(
          'type' => 'updated',
          'code' => 'ok',
          'message' => __('Settings saved', 'seravo'),
        ),
      );
    }

    // Generate components
    $base = new Component();
    $codes = array();
    foreach ( $notifications as $notification ) {
      if ( \in_array($notification['code'], $codes, true) ) {
        continue;
      }

      $codes[] = $notification['code'];

      if ( 'updated' === $notification['type'] ) {
        $notification['type'] = 'success';
      }

      if ( \in_array($notification['type'], array( 'error', 'success', 'warning', 'info' ), true) ) {
        $notification['type'] = 'notice-' . $notification['type'];
      }

      $id = \sprintf('setting-error-%s', \esc_attr($notification['code']));
      $class = \sprintf('notice %s settings-error is-dismissible', \esc_attr($notification['type']));

      $base->add_child(new Component($notification['message'], '<div id="' . $id . '" class="' . $class . '"><p><strong>', '</strong></p></div>'));
    }

    return $base;
  }

  /**
   * Function for building a textfield component.
   * @param mixed[] $field Field passed from wp_settings_fields.
   * @return \Seravo\Postbox\Component Textfield component.
   */
  public static function build_string_field( $field ) {
    $value = \get_option($field['id']) === false ? '' : \get_option($field['id']);
    $placeholder = isset($field['args']['placeholder']) ? $field['args']['placeholder'] : '';
    return Component::from_raw('<input type="text" placeholder="' . $placeholder . '" name="' . $field['id'] . '" value="' . $value . '"/>');
  }

  /**
   * Function for building a checkbox component.
   * @param mixed[] $field Field passed from wp_settings_fields.
   * @return \Seravo\Postbox\Component Checkbox component.
   */
  public static function build_boolean_field( $field ) {
    $value = \checked('on', \get_option($field['id']), false);
    return Component::from_raw('<input type="checkbox" name="' . $field['id'] . '" ' . $value . '/>');
  }

  /**
   * Function for building a integer component.
   * @param mixed[] $field Field passed from wp_settings_fields.
   * @return \Seravo\Postbox\Component Integer component.
   */
  public static function build_integer_field( $field ) {
    $value = \is_numeric(\get_option($field['id'])) ? \get_option($field['id']) : '';
    $placeholder = isset($field['args']['placeholder']) ? $field['args']['placeholder'] : '';
    return Component::from_raw('<input type="number" step="1" pattern="\d+" placeholder="' . $placeholder . '" name="' . $field['id'] . '" value="' . $value . '"/>');
  }

  /**
   * Function for building a number component.
   * @param mixed[] $field Field passed from wp_settings_fields.
   * @return \Seravo\Postbox\Component Number component.
   */
  public static function build_number_field( $field ) {
    $value = \is_numeric(\get_option($field['id'])) ? \get_option($field['id']) : '';
    $placeholder = isset($field['args']['placeholder']) ? $field['args']['placeholder'] : '';
    return Component::from_raw('<input type="number" placeholder="' . $placeholder . '" name="' . $field['id'] . '" value="' . $value . '"/>');
  }

  /**
   * Function for sanitizing boolean field.
   * @param string $value Value from the form.
   * @return string 'on' for true and 'off' for false.
   */
  public function sanitize_boolean_field( $value ) {
    if ( $value === 'on' ) {
      return 'on';
    }
    return 'off';
  }

  /**
   * Function for sanitizing integer field.
   * @param string $value   Value from the form.
   * @param int    $default The default value to use instead of invalid value.
   * @return int The sanitized integer.
   */
  public function sanitize_integer_field( $value, $default ) {
    // Only accept whole numbers
    if ( ! \is_numeric($value) || \floor((float) $value) !== (float) $value ) {
      $this->add_notification('invalid-integer', __('Invalid integer', 'seravo'));
      return $default;
    }
    return (int) $value;
  }

  /**
   * Function for sanitizing number field.
   * @param string $value   Value from the form.
   * @param float  $default The default value to use instead of invalid value.
   * @return float The sanitized integer.
   */
  public function sanitize_number_field( $value, $default ) {
    if ( ! \is_numeric($value) ) {
      $this->add_notification('invalid-number', __('Invalid number', 'seravo'));
      return $default;
    }
    return (float) $value;
  }

  /**
   * Get the section ID.
   * @return string Section ID.
   */
  public function get_section() {
    return $this->section;
  }

  /**
   * Set the postbox ID.
   * @param string $postbox Postbox ID.
   * @return void
   */
  public function set_postbox( $postbox ) {
    $this->postbox = $postbox;
  }

}
