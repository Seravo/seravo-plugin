![Seravo.com](https://seravo.com/wp-content/themes/seravo/images/seravo-banner-808x300.png)

# Seravo Must-use Plugin

[![Build Status](https://travis-ci.org/Seravo/seravo-plugin.svg?branch=master)](https://travis-ci.org/seravo/seravo-plugin) [![Latest Stable Version](https://poser.pugx.org/seravo/seravo-plugin/v/stable)](https://packagist.org/packages/seravo/seravo-plugin) [![Total Downloads](https://poser.pugx.org/seravo/seravo-plugin/downloads)](https://packagist.org/packages/seravo/seravo-plugin) [![Latest Unstable Version](https://poser.pugx.org/seravo/seravo-plugin/v/unstable)](https://packagist.org/packages/seravo/seravo-plugin) [![License](https://poser.pugx.org/seravo/seravo-plugin/license)](https://packagist.org/packages/seravo/seravo-plugin)

Enhances WordPress with [Seravo.com](https://seravo.com/) specific features and integrations (also known as WP-palvelu.fi in Finland).

# Installation

In order to use this with composer you need to add this mu-plugin:

https://github.com/roots/bedrock/blob/master/web/app/mu-plugins/bedrock-autoloader.php

This is because WordPress won't use mu-plugins from their own folders.

Then add this to your composer:

```json
{
  "require": {
    "seravo/seravo-plugin": "*"
  },
  "extra": {
    "installer-paths": {
      "htdocs/wp-content/mu-plugins/{$name}/": ["type:wordpress-muplugin"]
    }
  }
}
```

Seravo's customers can simply run `wp-seravo-plugin-update` to get the latest (tagged) release. For the adventurous, get the git master head with `wp-seravo-plugin-update --dev`.


# Features

* Shows notifications from WP-palvelu.fi (@TODO: switch to Seravo.com)
* Returns 401 (unauthorized) http status code after failed login.
* Hides Update nagging since that is handled by Seravo

* Uses nocache headers if the site is in development mode

* Adds Purge Cache -button in adminbar

* Automatically shows the shadow instance switcher is there are any shadow instances.

* Make urls in content relative for easier migration, but turn relative urls into absolute urls when using feeds (rss,atom...)

## Filters

You can insert your own admin notice for users that are in shadow
```php
function my_shadow_admin_notice($admin_notice, $current_screen) {
  return '<div class="notice notice-error"><p>This is staging. All content edited here will be lost. Return to production to create or edit content.</p></div>';
}
add_filter( 'seravo_instance_switcher_admin_notice', 'my_shadow_admin_notice', 10, 2 );
```

# Changelog

See git history.
