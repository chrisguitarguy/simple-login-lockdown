Simple login lock down is a way to protect your WordPress blog from brute
force login attacks.

How it works:
1. An attacker attempts to login and fails
2. Simple Login Lockdown record that fail login
3. After five failed attemps, further attemps to access the wp-login.php 
   page are blocked for a period of one hour

If you happen to forget your password and make a failed login attemp yourself,
the plugin will clear out the lockdown count data on successful login.

Just to be clear, this plugin is VERY simple: as in, no options page. Install
it, activate it, done.  It is NOT fool proof and I make no garuntees that this
will block all brute force attacks.
