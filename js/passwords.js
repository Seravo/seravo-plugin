// phpcs:disable PEAR.Functions.FunctionCallSignature
/*
 * Custom function to disallow weak passwords on the password reset screen.
 *
 * @TODO Fix this upstream in user-profile.js:showOrHideWeakPasswordCheckbox()
 * that is missing the following fixes:
 // Listen to both #submit and #wp-submit
 $submitButtons = $submitButtons.add(' #wp-submit');
 // On the login screen there are multiple class names that must be parsed.
 var classNames = passStrength.className.split(' ');
 passStrength.className = classNames.pop();
*/
(function($){
  'use strict';

  $(document).ready( function() {
    $('#pass1').on( 'input pwupdate', function() {
      var password = this.value;
      var strength = wp.passwordStrength.meter( password, wp.passwordStrength.userInputBlacklist(), password );
      var $submitButton = $('#wp-submit');
      if ( strength > 2 ) {
        $submitButton.prop( 'disabled', false );
      } else {
        $submitButton.prop( 'disabled', true );
      }
    });
    $('#pw-weak').change(function() {
      var $submitButton = $('#wp-submit');
      $submitButton.prop( 'disabled', true );
    });
  });
})(jQuery);
