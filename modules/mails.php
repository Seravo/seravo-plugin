<?php
/*
 * Plugin name: Mails
 * Description: ---
 * Version: 1.0
 */

namespace Seravo;

// Deny direct access to this file
if ( ! defined('ABSPATH') ) {
  die('Access denied!');
}

if ( ! class_exists('Mails') ) {

  class Mails {

    public static function load() {
      seravo_add_postbox(
        'mail-forwards',
        __('Mails', 'seravo') . ' (beta)',
        array( __CLASS__, 'mails_postbox' ),
        'tools_page_mails_page',
        'normal'
      );

    }

    public static function mails_postbox() {
      require_once dirname(__FILE__) . '/../lib/mails-page.php';

      ?>
      <div class="mails-postbox">
        <form action="#" method="get" style="width: 100%; margin-bottom: 10px;">
          <input type="hidden" name="page" value="<?php echo htmlspecialchars($_REQUEST['page']); ?>"/>
          <?php list_domains(); ?>
        </form>
        <form>
          <input type="hidden" name="page" value="<?php echo htmlspecialchars($_REQUEST['page']); ?>"/>
          <?php
          if ( ! empty($_GET['domain']) ) {
            display_forwards_table();
          }
          ?>
        </form>
      </div>
      <?php
    }

  }

  /* Only show emails page in production */
  if ( Helpers::is_production() ) {
    Mails::load();
  }
}
