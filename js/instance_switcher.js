(function(){
  var links = document.querySelectorAll('#wp-admin-bar-wpis li > a');
  var listener = function(e){
    e.preventDefault();
    var instance = e.target.getAttribute('href').substr(1);
    if (instance === 'exit') {
      document.cookie = "wpp_shadow=;path=/";
    } else {
      document.cookie = "wpp_shadow=" + instance + ";path=/";
    }
    location.reload();
  };
  
  for(var i = 0; i < links.length; i++){
    links[i].addEventListener('click', listener);
  }
})();
