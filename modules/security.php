<?php
/*
 * Plugin name: Security settings
 * Description: Enable users to set the maximum security settings for their site.
 * Version: 1.0
 *
 * NOTE! For more fine-grained XML-RPC control, use https://wordpress.org/plugins/manage-xml-rpc/
 */

namespace Seravo;

// Deny direct access to this file
if ( ! defined('ABSPATH') ) {
  die('Access denied!');
}

if ( ! class_exists('Security') ) {
  class Security {

    public static function load() {
      add_action( 'admin_init', array( __CLASS__, 'register_security_settings' ) );
      add_action( 'admin_menu', array( __CLASS__, 'register_security_page' ) );

      if ( get_option( 'seravo-disable-xml-rpc' ) ) {
        add_filter( 'xmlrpc_enabled', '__return_false' );

        // Disable X-Pingback to header
        add_filter( 'wp_headers', 'disable_x_pingback' );
        function disable_x_pingback( $headers ) {
          unset( $headers['X-Pingback'] );
          return $headers;
        }
      }

      if ( get_option( 'seravo-disable-json-user-enumeration' ) ) {
        function disable_user_endpoints($endpoints) {
          // disable list of users
          if (isset($endpoints['/wp/v2/users'])) {
            unset($endpoints['/wp/v2/users']);
          }
          // disable single user
          if (isset($endpoints['/wp/v2/users/(?P<id>[\d]+)'])) {
            unset($endpoints['/wp/v2/users/(?P<id>[\d]+)']);
          }
          return $endpoints;
        }
        add_filter('rest_endpoints', 'disable_user_endpoints', 1000);
      }

      seravo_add_postbox(
        'security',
        __('Security (beta)', 'seravo'),
        array( __CLASS__, 'security_postbox' ),
        'tools_page_reports_page',
        'normal'
      );
    }

    public static function register_security_settings() {
      add_settings_section(
        'seravo-security-settings', '',
        array( __CLASS__, 'security_settings_description' ), 'security_settings'
      );

      register_setting( 'seravo-security-settings-group', 'seravo-disable-xml-rpc' );
      register_setting( 'seravo-security-settings-group', 'seravo-disable-json-user-enumeration' );

      add_settings_field(
        'seravo-security-xmlrpc-field', __( 'Disable XML-RPC', 'seravo' ),
        array( __CLASS__, 'seravo_security_xmlrpc_field' ), 'security_settings', 'seravo-disable-xml-rpc'
      );
      add_settings_field(
        'seravo-disable-json-user-enumeration-field', __( 'Disable WP-JSON user enumeration', 'seravo' ),
        array( __CLASS__, 'seravo_security_json_user_enum_field' ), 'security_settings', 'seravo-disable-json-user-enumeration'
      );    }

    public static function security_settings_description() {
      echo '';
    }

    public static function seravo_security_xmlrpc_field() {
      echo '<input type="checkbox" name="seravo-disable-xml-rpc" id="disable-xmlrpc" ' . checked( 'on', get_option( 'seravo-disable-xml-rpc' ), false ) . '>';
    }

    public static function seravo_security_json_user_enum_field() {
      echo '<input type="checkbox" name="seravo-disable-json-user-enumeration" id="disable-json-user-enumaration" ' . checked( 'on', get_option( 'seravo-disable-json-user-enumeration' ), false ) . '>';
    }

    public static function register_security_page() {
      add_submenu_page(
        'tools.php', __( 'Security', 'seravo' ), __( 'Security', 'seravo' ),
        'manage_options', 'security_page', 'Seravo\seravo_postboxes_page'
      );
    }

    public static function security_postbox() {
      settings_errors();
      echo '<form method="post" action="options.php" class="seravo-general-form">';
      settings_fields( 'seravo-security-settings-group' );
      do_settings_sections( 'security_settings' );
      submit_button( __( 'Save', 'seravo' ), 'primary', 'btnSubmit' );
      echo '</form>';
    }
  }
  Security::load();
}
