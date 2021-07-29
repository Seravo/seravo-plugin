<?php

namespace Seravo\Page;

use \Seravo\Postbox;
use \Seravo\Postbox\Toolpage;
use \Seravo\Postbox\Requirements;

/**
 * Class Backups
 *
 * Backups is a page for info
 * and management of backups.
 */
class Backups extends Toolpage {

  /**
   * @var \Seravo\Page\Backups Instance of this page.
   */
  private static $instance;

  /**
   * Function for creating an instance of the page. This should be
   * used instead of 'new' as there can only be one instance at a time.
   * @return \Seravo\Page\Backups Instance of this page.
   */
  public static function load() {
    if ( self::$instance === null ) {
      self::$instance = new Backups();
    }

    return self::$instance;
  }

  /**
   * Constructor for Backups. Will be called on new instance.
   * Basic page details are given here.
   */
  public function __construct() {
    parent::__construct(
      __('Backups', 'seravo'),
      'tools_page_backups_page',
      'backups_page',
      'Seravo\Postbox\seravo_two_column_postboxes_page'
    );
  }

  /**
   * Will be called for page initialization. Includes scripts
   * and enables toolpage features needed for this page.
   * @return void
   */
  public function init_page() {
    self::init_postboxes($this);

    $this->enable_ajax();
  }

  /**
   * Will be called for setting requirements. The requirements
   * must be as strict as possible but as loose as the
   * postbox with the loosest requirements on the page.
   * @param \Seravo\Postbox\Requirements $requirements Instance to set requirements to.
   */
  public function set_requirements( Requirements $requirements ) {
    $requirements->can_be_production = \true;
    $requirements->can_be_staging = \true;
    $requirements->can_be_development = \true;
  }

  /**
   * @return void
   */
  public static function init_postboxes( Toolpage $page ) {
    /**
     * Backup info postbox
     */
    $backups_info = new Postbox\InfoBox('backups-info');
    $backups_info->set_title(__('Backups', 'seravo'));
    $backups_info->add_paragraph(__('Backups are automatically created every night and preserved for 30 days. The data can be accessed on the server in under <code>/data/backups</code>.', 'seravo'));
    $backups_info->set_requirements(array( Requirements::CAN_BE_ANY_ENV => true ));
    $page->register_postbox($backups_info);

    /**
     * Backup create postbox
     */
    $backups_create = new Postbox\SimpleCommand('backups-create');
    $backups_create->set_title(__('Create a New Backup', 'seravo'));
    $backups_create->set_command('wp-backup 2>&1', null, false);
    $backups_create->set_button_text(__('Create a backup', 'seravo'));
    $backups_create->set_spinner_text(__('Creating a backup...', 'seravo'));
    $backups_create->add_paragraph(__('You can also create backups manually by running <code>wp-backup</code> on the command line. We recommend that you get familiar with the command line option that is accessible to you via SSH. That way recovering a backup will be possible whether the WP Admin is accessible or not.', 'seravo'));
    $backups_create->set_requirements(array( Requirements::CAN_BE_ANY_ENV => true ));
    $page->register_postbox($backups_create);

    /**
     * Backup excludes postbox
     */
    $backups_excludes = new Postbox\LazyCommand('backups-excludes', 'side');
    $backups_excludes->set_title(__('Files Excluded from the Backups', 'seravo'));
    $backups_excludes->set_command('cat /data/backups/exclude.filelist', 60, true);
    $backups_excludes->add_paragraph(__('Below are the contents of <code>/data/backups/exclude.filelist</code>.', 'seravo'));
    $backups_excludes->set_empty_message(__('No files are excluded', 'seravo'));
    $backups_excludes->set_requirements(array( Requirements::CAN_BE_ANY_ENV => true ));
    $page->register_postbox($backups_excludes);

    /**
     * Current Backups postbox
     */
    $backups_list = new Postbox\LazyCommand('backups-list', 'side');
    $backups_list->set_title(__('Current Backups', 'seravo'));
    $backups_list->set_command('wp-backup-status 2>&1', 300, false);
    $backups_list->add_paragraph(__('This list is produced by the command <code>wp-backup-status</code>.', 'seravo'));
    $backups_list->set_requirements(array( Requirements::CAN_BE_ANY_ENV => true ));
    $page->register_postbox($backups_list);
  }

}
