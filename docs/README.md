# Seravo-plugin developer documentation

This readme is to help in developing, maintaining and updating Seravo-plugin.

## Modularity

Features and tools in the Seravo-plugin are divided into modules. This simplifies developing new features and maintaining existing ones.
Tools provided by the Seravo-plugin are divided into modules within the /modules directory.

### Adding a new page

If you want to use postboxes in a submenu page, use `'Seravo\seravo_postboxes_page'` as the `$function` parameter for `add_submenu_page` when registering the submenu page. With the following example, you can add a submenu under `Tools`.

```
add_submenu_page(
  'tools.php',
  __('Example Tools', 'seravo'),
  __('Example Tools', 'seravo'),
  'manage_options',
  'example_page',
  'Seravo\seravo_postboxes_page'
);
```

## Postboxes

Postboxes were introduced to the Seravo-plugin in [#166](https://github.com/Seravo/seravo-plugin/pull/166).

Postboxes are elements used in the Seravo-plugin Admin tools that behave similarily to the Dashboard widgets in WordPress. They can be dragged in custom orders and opened/closed. The postbox location and opened/closed state is saved to the site database for each individual user to enable custom settings per-user.

Using postboxes gives the plugin a more unified, professional look.

If there is a need to add content before or after the postbox listing (e.g. admin notices), they can be appended by using the filters `'before_seravo_postboxes_' . $current_screen` or `'after_seravo_postboxes_' . $current_screen`, where `$current_screen` is the wanted admin screen (e.g. `tools_page_reports_page`).

### Adding a postbox

Adding a postbox to the Example Tools -page which uses `'Seravo\seravo_postboxes_page'` as a function for outputting the page content can be done using

`modules/example.php`
```
seravo_add_postbox(
  'example_tool_id',
  __('Example Tool 1', 'seravo'),
  array( __CLASS__, 'example_tool_1_postbox' ),
  'tools_page_example_page', // Prefix 'tools_page' must match 'tools.php'
  'normal'
);
```

Defining what is shown in the Postbox is stated in the callback function `example_tool_1_postbox` which shows the `yes.png` image.

`modules/example-tool.php`
```
public static function example_tool_1_postbox() {
  _e('Example Tool 1', 'seravo');
  ?>
    <div id="example_tool_1_yes">
      <img src="/wp-admin/images/yes.png">
    </div>
  <?php
}
```

The DOM which will wrap the Example Tool 1:
`lib/seravo-postbox.php`
```
<!-- Postbox title -->
<h2 class="hndle ui-sortable-handle">
  <span><?php echo $postbox_content['title']; ?></span>
</h2>

<!-- Postbox content -->
<div class="inside">
  <div class="seravo-section">
    <?php call_user_func_array($postbox_content['callback'], $postbox_content['callback_args']); ?>
  </div>
</div>
```
