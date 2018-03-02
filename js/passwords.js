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
        var pwned_timeout = null;
		$('#pass1').on( 'input pwupdate', function() {
			var password = this.value;
			var strength = wp.passwordStrength.meter( password, wp.passwordStrength.userInputBlacklist(), password );

            /* Check if password hash is listed in pwned passwords
             * Do the query using first 5 characters of SHA1SUM of the
             * passwords.
             * Details: <https://www.troyhunt.com/ive-just-launched-pwned-passwords-version-2/>
             */
            var pwned = false;
            clearTimeout(pwned_timeout);

            pwned_timeout = setTimeout(function() {
                var password_shasum = sha1(password).toUpperCase();
                var pwned_url = 'https://api.pwnedpasswords.com/range/' + password_shasum.substr(0,5);

                $.get(pwned_url, function(data) {
                    console.log('Queried for ' + password_shasum.substr(0, 5));
                    var lines = data.split('\n');
                    var look_for = password_shasum.substr(5);
                    for (var i=0; i<lines.length; i++) {
                        if (lines[i].indexOf(look_for) == 0) {
                            var c = parseInt(lines[i].split(':')[1]);
                            console.log('Match found: ' + password_shasum + ' equals ' + lines[i] + ' with count ' + c);
                            if (c > 5) {
                                console.log('Password matched more than 5 times, blocking it.');
                                pwned = true;
                            }
                            break;
                        }
                    }
                });
                clearTimeout(pwned_timeout);
            }, 500);

			var $submitButton = $('#wp-submit');
			if (( strength > 2 ) && (pwned === false)) {
				$submitButton.prop( 'disabled', false );
			} else {
				$submitButton.prop( 'disabled', true );
			}
		});
	});
})(jQuery);
