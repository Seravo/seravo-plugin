document.addEventListener('DOMContentLoaded', function() {
    var links = document.querySelectorAll('#wp-admin-bar-instance-switcher li > a');
    var listener = function(e){
    var instance = e.target.getAttribute('href').substr(1);
    if (instance === 'exit') {
      e.preventDefault();
      document.cookie = "wpp_shadow=;path=/";
      document.cookie = "seravo_shadow=;path=/";
    } else if (instance.length === 6) {
      e.preventDefault();
      document.cookie = "wpp_shadow=" + instance + ";path=/";
      document.cookie = "seravo_shadow=" + instance + ";path=/";
    }
    location.reload();
    };

    for (var i = 0; i < links.length; i++) {
    links[i].addEventListener('click', listener);
    }
});
