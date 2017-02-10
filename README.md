![Seravo.com](https://seravo.com/wp-content/themes/seravo/images/seravo-banner-808x300.png)

# Seravo Must-use Plugin

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

# Features

* Shows notifications from WP-palvelu.fi (@TODO: switch to Seravo.com)
* Returns 401 (unauthorized) http status code after failed login.
* Hides Update nagging since that is handled by Seravo

* Uses nocache headers if the site is in development mode

* Adds Purge Cache -button in adminbar

* Make urls in content relative for easier migration, but turn relative urls into absolute urls when using feeds (rss,atom...)

# Changelog

See git history.
