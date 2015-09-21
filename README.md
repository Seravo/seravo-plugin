![WP-palvelu.fi](https://wp-palvelu.fi/wp-content/uploads/2015/01/wp-palvelu-header.jpg)

# WP-palvelu Must-use Plugin

This plugin enables [WP-palvelu](http://wp-palvelu.fi/) features and customisations.

* Shows notifications from WP-Palvelu
* Returns 401 (unauthorized) http status code after failed login.
* Hides Update nagging since that is handled by WP-Palvelu

* Make urls in content relative for easier migration, but turn relative urls into absolute urls when using feeds (rss,atom...)

* Allows login to wp-admin with secure ssl client certificate. This helps admins and clients which have multiple sites in WP-Palvelu.

# Changelog

## 1.2
* Revamped the structure to allow developers to disable any functionality in the plugin using filters
* Added relative urls module