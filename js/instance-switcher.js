// phpcs:disable PEAR.Functions.FunctionCallSignature
document.addEventListener('DOMContentLoaded', function() {
  // Select all links for entering shadow in admin bar dropdown
  var shadow_links = jQuery('#wp-admin-bar-instance-switcher li.shadow-link > a');
  // Select all links for exiting shadow (dropdown or banner)
  var exit_links = jQuery('li.shadow-exit > a, a.shadow-exit');

  // Listener for entering shadow
  var shadow_listener = function(e) {
    e.preventDefault();

    var new_location = location.href;
    var target = jQuery(this).attr('href');
    if (target.startsWith('#')) {
      // Then access shadow with cookies
      // Match all strings eg. #abc123 (shadow ID)
      var instance = target.match(/#([a-z0-9]+)/);
      // instance[0] = #abc123, instance[1] = abc123
      if (instance && instance[1] && instance[1].length === 6) {
        // Set the cookies, 43200 seconds is 12 hours
        document.cookie = "seravo_shadow=" + instance[1] + "; Max-Age=43200; Path=/";
        // Clear potential old shadow query string
        new_location = new_location.replace(/[a-z]+_shadow=[a-z0-9]+/, '');
      }
    } else  {
      // Else access shadow with domain
      var current_host = location.protocol + '//' + location.hostname;
      new_location = new_location.replace(current_host, target);
      // Clean away potential old seravo_production param.
      new_location = new_location.replace(/(\?|\b)seravo_production=.*?(?=&|$)/, '');
      // Add seravo_production param with current hostname
      new_location = new_location.replace(/#.*/, '');
      new_location += new_location.indexOf('?') != -1 ? '&' : '?';
      new_location += 'seravo_production=' + location.hostname;
    }
    // Reload / redirect page
    location.href = new_location;
  }

  // Listener for exiting shadow
  var exit_listener = function(e) {
    e.preventDefault();

    var new_location = location.href;
    var target = jQuery(this).attr('href');
    if (target === '#exit') {
      // Then using cookies to access shadow, clear them
      document.cookie = "seravo_shadow=; Max-Age=0; Path=/";
      // Clear potential old shadow query string
      new_location = new_location.replace(/[a-z]+_shadow=[a-z0-9]+/, '');
    } else {
      // Else using domain
      if (target.endsWith('/')) {
        // Only use hostname so redirecting works
        // Used with DEFAULT_DOMAIN
        new_location = target;
      } else {
        // Otherwise keep protocol, path and query string
        // Clean away potential old seravo_production param.
        new_location = new_location.replace(/(\?|\b)seravo_production=.*?(?=&|$)/, '');
        new_location = new_location.replace(location.protocol + '//' + location.hostname, target);
      }
    }

    // Reload / redirect page
    location.href = new_location;
  }

  // Add right listener for the links found

  var limit = shadow_links.length;
  for (var i = 0; i < limit; i++) {
    shadow_links[i].addEventListener('click', shadow_listener);
  }

  limit = exit_links.length;
  for (var i = 0; i < limit; i++) {
    exit_links[i].addEventListener('click', exit_listener);
  }

});
