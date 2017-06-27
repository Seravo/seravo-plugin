<div class="wrap">

    <h1><?php _e('Tests', 'seravo'); ?> (beta)</h1>

    <p><?php _e('Here you can test the core functionality of the WordPress installation on your site.
        The same effect can be achieved via command line by running <code>wp-test</code>.
        For more information, check the <a href="https://seravo.com/docs/tests/integration-tests/">
            Seravo documentation for developers</a>.', 'seravo'); ?></p>

    <button type="button" class="button-primary" id="run-wp-tests"><?php _e('Run Tests', 'seravo'); ?></button>

    <div class="seravo-test-result-wrapper">

        <div class="seravo-test-status" id="seravo_tests_status">
            <?php _e('Click "Run Tests" to run the Rspec tests', 'seravo'); ?>
        </div>

        <div id="seravo_test_show_more_wrapper">
            <a href="" id="seravo_test_show_more"><?php _e('Toggle details', 'seravo'); ?>
                <div class="dashicons dashicons-arrow-down-alt2" id="seravo_arrow_show_more">
                </div>
            </a>
        </div>

        <div class="seravo-test-result">
            <pre id="seravo_tests"></pre>
        </div>

    </div>

</div>
