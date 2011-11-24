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

Note: This uses $_SERVER['REMOTE_ADDR'] directly.  If you're behind a proxy
(load balancer, etc), it's not going to work as expected.  Eg. Several folks
could be attempting logins at once, and all fail.  As such, the plugin would
pick up on all those requests coming from the same IP -- the load balancer --
and lock the login down.  No good.  If you're using a load balancer or in
some other situation where you're behind a proxy, use this as an example
and write your own. Or filter the IP as your desire using `cd_sll_pre_ip`
