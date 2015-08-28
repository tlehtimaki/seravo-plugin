<?php
/*
 * Plugin name: WP-palvelu Helpers
 * Description: Contains custom helpers and small fixes
 * Version: 1.1
 */

namespace WPPalvelu;

if (!class_exists('Helpers')) {
  class Helpers {

    /*
     * Loads WP-palvelu features
     */
    public static function load() {

      add_action( 'admin_notices', array(__CLASS__, 'showAdminNotification') );
      add_action( 'admin_menu', array(__CLASS__, 'hideUpdateNotifications') );
      add_filter( 'wp_get_update_data', array(__CLASS__, 'hideUpdateData') );
      add_action( 'wp_login_failed', array(__CLASS__, 'changeHttpCodeToUnauthorized') );
    }

    /**
     * This is used to add notifications from wp-palvelu for users
     */
    public static function showAdminNotification() {
      // get notification
      if ( false === ( $response = get_transient( 'seravo_notification' ) ) || ( isset($_SERVER['HTTP_PRAGMA']) && $_SERVER['HTTP_PRAGMA'] == 'no-cache' ) ) { 

        // Download notification
        $response = self::getGlobalNotification();
        set_transient( 'seravo_notification', $response, HOUR_IN_SECONDS );
        // allow some html tags but strip most
        $message = ''; 
        if( isset($response->message) ) { 
          $message = $response->message;
          $message = strip_tags( trim($message),"<br><br/><a><b><strong><i>" );
        }   
        // control alert type
        $type = ''; 
        if( isset($response->type) ) { 
          $type = $response->type;
        }   
      }
      if (!empty($message) ) { 
      ?>  
        <div class="<?php esc_attr_e($type) ?> notice is-dismissible">
          <p><?php echo $message; ?> <button type="button" class="notice-dismiss"></button></p>
        </div>
      <?php
      }
    }

    /**
     * Removes core update notifications
     */
    public static function hideUpdateNotifications() {
      remove_action( 'admin_notices', 'update_nag', 3 );
    }

    /**
     * Removes red update bubbles from admin menus
     */
    public static function hideUpdateData($update_data, $titles='') {
      return array (
        'counts' => array(
          'plugins' => 0,
          'themes' => 0,
          'wordpress' => 0,
          'translations' => 0,
          'total' => 0
        ),
        'title' => ''
      );
    }

    /**
     * Return better http status code (401 unauthorized) after failed login.
     * Then failed login attempts (brute forcing) can be noticed in access.log
     * WP core ticket: https://core.trac.wordpress.org/ticket/25446
     */
    public static function changeHttpCodeToUnauthorized() {
      status_header( 401 );
    }

    /**
     * Loads global Notification message from central server
     */
    public static function getGlobalNotification() {
      // use @file_get_contents to suppress the warning when network is down.
      // Usually this happens on offline local development
      return json_decode( @file_get_contents('https://wp-palvelu.fi/ilmoitus/') );
    }

  }

  Helpers::load();
}