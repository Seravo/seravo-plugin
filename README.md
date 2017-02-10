![WP-palvelu.fi](https://wp-palvelu.fi/wp-content/uploads/2015/01/wp-palvelu-header.jpg)

# WP-palvelu Must-use Plugin

Enhances WordPress with [Seravo.com](https://seravo.com/) specific features and integrations (also known as WP-palvelu in Finnish).

# Installation

In order to use this with composer you need to add this mu-plugin:

https://github.com/roots/bedrock/blob/master/web/app/mu-plugins/bedrock-autoloader.php

This is because WordPress won't use mu-plugins from their own folders.

Then add this to your composer:

```json
{
  "require": {
    "seravo/wp-palvelu-plugin": "*"
  },
  "extra": {
    "installer-paths": {
      "htdocs/wp-content/mu-plugins/{$name}/": ["type:wordpress-muplugin"]
    }
  }
}
```

# Features

* Shows notifications from WP-Palvelu
* Returns 401 (unauthorized) http status code after failed login.
* Hides Update nagging since that is handled by WP-Palvelu

* Uses nocache headers if the site is in development mode

* Adds Purge Cache -button in adminbar

* Make urls in content relative for easier migration, but turn relative urls into absolute urls when using feeds (rss,atom...)

# Changelog

## 1.3
* Added translations
* Added Purge Cache -button to adminbar
* Use headers which disallow browser cache when the site is in development
* Fixes relative urls problems with Feeds (RSS,Atom...)

## 1.2
* Revamped the structure to allow developers to disable any functionality in the plugin using filters
* Added relative urls module
