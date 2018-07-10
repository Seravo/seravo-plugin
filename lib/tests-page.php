<?php
// Deny direct access to this file
if ( ! defined('ABSPATH') ) {
  die('Access denied!');
}
?>

<div class="wrap">

<div id="wpbody-content" aria-label="Main content" tabindex="0">
  <div class="wrap">
    <div class="dashboard-widgets-wrap">
      <div id="dashboard-widgets" class="metabox-holder">
        <div class="postbox-container">
          <div id="normal-sortables" class="meta-box-sortables ui-sortable">
            <!--First postbox:-->
            <div id="dashboard_tests" class="postbox">
              <button type="button" class="handlediv button-link" aria-expanded="true">
                <span class="screen-reader-text">Toggle panel: <?php _e('Database access', 'seravo'); ?></span>
                <span class="toggle-indicator" aria-hidden="true"></span>
              </button>
              <h2 class="hndle ui-sortable-handle">
                <span><?php _e('Tests', 'seravo'); ?> (beta)</span>
              </h2>
              <div class="inside">
                <div class="seravo-section">
                  <p>
                    <?php
                      _e('Here you can test the core functionality of the WordPress installation on your site.
                        The same effect can be achieved via command line by running <code>wp-test</code>.
                        For more information, check the <a href="https://seravo.com/docs/tests/integration-tests/">
                        Seravo documentation for developers</a>.', 'seravo');
                      ?>
                  </p>
                  <button type="button" class="button-primary" id="run-wp-tests"><?php _e('Run Tests', 'seravo'); ?></button>
                  <div class="seravo-test-result-wrapper">
                    <div class="seravo-test-status" id="seravo_tests_status">
                      <?php _e('Click "Run Tests" to run the Rspec tests', 'seravo'); ?>
                    </div>
                    <div class="seravo-test-result">
                      <pre id="seravo_tests"></pre>
                    </div>
                    <div id="seravo_test_show_more_wrapper">
                      <a href="" id="seravo_test_show_more"><?php _e('Toggle details', 'seravo'); ?>
                        <div class="dashicons dashicons-arrow-down-alt2" id="seravo_arrow_show_more">
                        </div>
                      </a>
                    </div>
                  </div>
                </div>
              </div>
            </div>
            <!--First postbox...-->
          </div>
        </div>
      </div>
    </div>
  </div>
</div>
