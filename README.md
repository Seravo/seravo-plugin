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

* Enforces canonical URLs
* Enforces HTTPS, nags if PHP version is too low, shows a notice is object-cache is not enabled etc
* Shows notifications from WP-palvelu.fi (@TODO: switch to Seravo.com)
* Returns 401 (unauthorized) http status code after failed login.
* Logs all login attempts to /data/log/wp-login.log
* Hides Update nagging since that is handled by Seravo
* Uses nocache headers if the site is in development mode
* Adds Purge Cache -button in adminbar
* Automatically shows the shadow instance switcher is there are any shadow instances.
* Allows to list and reset shadow environments
* Finds and suggests cruft files to remove from a site
* Shows information about the database, table sizes etc
* Show information about disk usage, server logs, updates, tests etc
* And lots, lots more!

## Filters

You can insert your own admin notice for users that are in shadow
```php
function my_shadow_admin_notice($admin_notice, $current_screen) {
  return '<div class="notice notice-error"><p>This is staging. All content edited here will be lost. Return to production to create or edit content.</p></div>';
}
add_filter( 'seravo_instance_switcher_admin_notice', 'my_shadow_admin_notice', 10, 2 );
```

Currently the velocity of development is so high that documentation lacks badly behind. To find more filters, just search the source code for `apply_filters`.

## Development

### Using a real site for development

Some of the features in the Seravo Plugin depend on the API that is available only on a real production site, and thus cannot be tested inside a Vagrant box or the like.

In order to have the git repository on your own computer and in your own editor, while still being able to see the code running on a test site (in the production environment) you can use the command below. It will watch all files for changes and automatically rsync them to the remote server:
```
seravo-plugin$ find * | entr rsync -avz -e 'ssh -q -p 12345' * \
example@example.seravo.com:/data/wordpress/htdocs/wp-content/mu-plugins/seravo-plugin/
sending incremental file list
README.md

sent 2,999 bytes  received 64 bytes  2,042.00 bytes/sec
total size is 370,596  speedup is 120.99
```

### Updating translations

Remember to update translations of all public facing string by running inside Vagrant:
```
cd /data/wordpress/htdocs/wp-content/mu-plugins/seravo-plugin
wp i18n make-pot . languages/seravo.pot
```

# Changelog

See git history.
