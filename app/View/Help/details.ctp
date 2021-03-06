<?php
/**
 *
 * View class for APP/help/create for the SourceKettle system
 * Display the help page for creating new projects
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     SourceKettle Development Team 2012
 * @link          https://github.com/SourceKettle/sourcekettle
 * @package       SourceKettle.View.Help
 * @since         SourceKettle v 0.1
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
?>
  
<div class="well">
  <h3>Your account details</h3>

  <p>
    To edit your account details, you must be logged in.  Click on the dropdown at the top right of the screen and select 'Account Settings'.
  </p>

  <h4>Internal vs. External accounts</h4>
  <p>
    SourceKettle has its own user account system ("internal accounts"), but can also be integrated with your organisation's existing account system if you have one ("external accounts").
  </p>

  <p>
    If you are using an "external" account, some settings cannot be changed - the email address and password are not managed within SourceKettle, so you do not have the option to change them.
  </p>

  <h4>Your name</h4>
  <p>
    You can customise this how you like, it does not affect the login process.  When you log in with an external account for the first time, the name will be set to something sensible based on your organisational account.
  </p>

  <h4>Email address</h4>
  <p>If you have an "internal" account, you may change your email address with a few caveats:
    <ul>
      <li>You can't set it to an address somebody else is using</li>
      <li>The next time you log in, you will have to use the <strong>new</strong> address - don't forget!</li>
    </ul>
  </p>

  <h4>SSH keys</h4>
  <p>
    Now you've set up your acount, you'll probably want to add some SSH keys - <a href='addkey'>click here</a> for more information!
  </p>

</div>
