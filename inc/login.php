<?php
/**
 * Simple Login Lockdown
 *
 * The MIT License (MIT)
 *
 * Copyright (c) 2012 Christopher Davis
 *
 * Permission is hereby granted, free of charge, to any person obtaining a
 * copy of this software and associated documentation files (the "Software"),
 * to deal in the Software without restriction, including without limitation
 * the rights to use, copy, modify, merge, publish, distribute, sublicense,
 * and/or sell copies of the Software, and to permit persons to whom the
 * Software is furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING
 * FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER
 * DEALINGS IN THE SOFTWARE.
 *
 * @category    WordPress
 * @package     Simple_Login_Lockdown
 * @copyright   Christopher Davis 2012
 * @license     http://opensource.org/licenses/MIT The MIT License (MIT)
 */

!defined('ABSPATH') && exit;

/**
 * Handles all non-admin functionality for the plugin nd serves as a base
 * class.
 *
 * @since   1.0
 * @author  Christopher Davis <http://christopherdavis.me>
 */
class Simple_Login_Lockdown
{
    /**
     * The option name.
     *
     * @since   1.0
     */
    const SETTING = 'cd_sll_options';

    /**
     * Prefix for the transients/lockdown options.
     *
     * @since   1.0
     */
    const PREFIX = 'cdll2_';

    /**
     * Container for the plugin instance.
     *
     * @since   1.0
     * @access  private
     * @var     object (an instance of this class)
     */
    private static $ins = null;

    /**
     * Get the instance of this class.
     *
     * @since   1.0
     * @access  public
     * @return  Simple_Login_Lockdown
     */
    public static function instance()
    {
        is_null(self::$ins) && self::$ins = new self;
        return self::$ins;
    }

    /**
     * Make it happen. Hook the `_setup` method into `plugins_loaded`.
     *
     * @since   1.0
     * @access  public
     * @uses    add_action
     * @return  void
     */
    public static function init()
    {
        add_action('plugins_loaded', array(self::instance(), '_setup'));
    }

    /**
     * Hooked into `plugins_loaded`. Add actions and such.
     *
     * @since   1.0
     * @access  public
     * @uses    add_action
     * @return  void
     */
    public function _setup()
    {
        add_action('wp_login_failed', array($this, 'failed_login'));
        add_action('login_init', array($this, 'maybe_kill_login'));
        add_action('wp_login', array($this, 'successful_login'));
        add_action('wp_ajax_simple-login-lockdown-unlock', array($this, 'unlock_ip' ));

        load_plugin_textdomain(
            'simple-login-lockdown',
            false,
            dirname(CD_SLL_NAME) . '/lang/'
        );
    }

    /**
     *
     *
     * @return void
     */
    public function unlock_ip()
    {
        global $wp_version;
        $page = version_compare($wp_version, '3.4.2', '<=') ? 'privacy' : 'reading';
        $error = '';

        if ( empty($_GET['ip']) )
            $error = __("Invalid IP address.", 'simple-login-lockdown');
        elseif ( !self::is_locked_down($_GET['ip']) )
            $error = __("IP address is not locked down.", 'simple-login-lockdown');
        else
            self::clear_lockdown($_GET['ip']);

        if ( $error )
            wp_redirect(admin_url('options-'.$page.'.php?settings-updated=true&simple-login-lockdown-error='.urlencode($error)));
        else
            wp_redirect(admin_url('options-'.$page.'.php?settings-updated=true&simple-login-lockdown-success='.urlencode('IP unlocked successfully.')));
    }

    /**
     * Catch failed login attemps due a faulty username/password combination
     *
     * If a login attempt fails, this function will add/update an option with
     * a count of how many times that attempt has failed.
     *
     * @since   0.1
     * @access  public
     * @uses    get_option
     * @uses    update_option
     * @uses    add_option
     * @return  void
     */
    public function failed_login()
    {
        if(!($ip = self::get_ip()))
            return;

        if(apply_filters('simple_login_lockdown_allow_ip', false, $ip))
            return;

        self::inc_count($ip);
    }

    /**
     * Kills the login page via wp_die if login attempt allowance has been
     * exceeded or the IP address is locked down.
     *
     * @since   0.1
     * @access  public
     * @return  void
     */
    public function maybe_kill_login()
    {
        if(!($ip = self::get_ip()))
            return;

        $die = false;
        if(($count = self::get_count($ip)) && $count > absint(self::opt('limit', 5)))
        {
            self::delete_count($ip);
            self::set_lockdown($ip);
            $die = true;
            do_action('simple_login_lockdown_count_reached', $ip);
        }
        elseif(self::is_locked_down($ip))
        {
            $die = true;
            do_action('simple_login_lockdown_attempt', $ip);
        }

        if(apply_filters('simple_login_lockdown_should_die', $die, $ip))
        {
            wp_die(
                __('Too many login attemps from one IP address! Please take a break and try again later', 'simple-login-lockdown'),
                __('Too many login attemps', 'simple-login-lockdown'),
                array('response' => apply_filters('simple_login_lockdown_response', 403))
            );
        }
    }

    /**
     * Clears all lockdown data on a successful login.
     *
     * @since   0.1
     * @access  public
     * @return  void
     */
    function successful_login()
    {
        if(!($ip = self::get_ip()))
            return;

        self::delete_count($ip);
        self::clear_lockdown($ip);
    }

    /********** Internals **********/

    /**
     * Get the $_SERVER['REMOTE_ADDR'] value.  Uses apply_filters
     * so plugins/themes can hook into change the value if they're using a
     * load balancer or behind some other proxy.
     *
     * @since   0.1
     * @access  private
     * @uses    apply_filters
     * @return  string|bool The IP if it's there, false if not.
     */
    private static function get_ip()
    {
        $ip = false;

        if ($_ip = self::get_proxy_ip()) {
            $ip = $_ip;
        } elseif (isset($_SERVER['REMOTE_ADDR'])) {
            $ip = $_SERVER['REMOTE_ADDR'];
        }

        return apply_filters('simple_login_lockdown_ip', $ip);
    }

    /**
     * If we're trusting proxy data, get the X-Forwarded-For header (if present)
     * and return it's IP.
     *
     * @since   0.3
     * @access  private
     * @uses    apply_filters
     * @return  false|string
     * @static
     */
    private static function get_proxy_ip()
    {
        $ip = false;

        $h = apply_filters('simple_login_lockdown_proxy_ip_header', 'HTTP_X_FORWARDED_FOR');

        if (self::trust_proxy_data() && !empty($_SERVER[$h])) {
            $ip_arr = array_map('trim', explode(',', $_SERVER[$h]));
            $ip = array_shift($ip_arr);
        }

        return apply_filters('simple_login_lockdown_proxy_ip', $ip);
    }

    /**
     * Do we trust proxy data?
     *
     * @since   0.3
     * @access  private
     * @uses    apply_filters
     * @return  boolean
     * @static
     */
    private static function trust_proxy_data()
    {
        return apply_filters(
            'simple_login_lockdown_trust_proxy',
            'on' === static::opt('trust_proxy', 'off')
        );
    }

    /**
     * Get the current login count for a given IP.
     *
     * @since   1.0
     * @access  private
     * @uses    get_transient
     * @param   string $ip The IP address
     * @return  int
     */
    private static function get_count($ip)
    {
        if($c = get_transient(self::get_key($ip)))
            return absint($c);
        return 0;
    }

    /**
     * Increment the count login attemp count for a given $ip
     *
     * @since   1.0
     * @access  private
     * @param   string $ip
     * @uses    set_transient
     * @uses    apply_filters
     * @return  int The incremented count
     */
    private static function inc_count($ip)
    {
        $c = self::get_count($ip) + 1;

        set_transient(self::get_key($ip), $c,
            apply_filters('simple_login_lockdown_timer', 60*60, $ip));

        return $c;
    }

    /**
     * Remove the count.
     *
     * @since   1.0
     * @access  private
     * @uses    delete_transient
     * @return  void
     */
    private static function delete_count($ip)
    {
        delete_transient(self::get_key($ip));
    }

    /**
     * Get the prefixed transient key
     *
     * @since   1.0
     * @access  private
     * @return  string
     */
    private static function get_key($key)
    {
        return self::PREFIX . $key;
    }

    /**
     * Lock down the login for a given IP.
     *
     * @since   1.0
     * @access  private
     * @uses    set_transient
     * @param   string $ip
     * @return  void
     */
    private static function set_lockdown($ip)
    {
        $len = absint(self::opt('time', 60));

        if(!$len || $len < 0)
            $len = 60;

        set_transient(self::get_lockdown_key($ip), true,
            apply_filters('simple_login_lockdown_length', $len * 60));
    }

    /**
     * Is the IP address locked down?
     *
     * @since   1.0
     * @access  private
     * @uses    get_transient
     * @param   string $ip
     * @return  boolean
     */
    private static function is_locked_down($ip)
    {
        return (bool) get_transient(self::get_lockdown_key($ip));
    }

    /**
     * Clear the lockdown for a given $ip
     *
     * @since   1.0
     * @access  private
     * @uses    delete_transient
     * @param   string $ip
     * @return  void
     */
    private static function clear_lockdown($ip)
    {
        delete_transient(self::get_lockdown_key($ip));
    }

    /**
     * Get the lockedown key.
     *
     * @since   1.0
     * @access  private
     * @param   string $key
     * @return  string
     */
    private static function get_lockdown_key($key)
    {
        return 'locked_down_' . $key;
    }

    /**
     * Fetch an option.
     *
     * @since   1.0
     * @access  protected
     * @uses    get_option
     * @param   string $key The option key to fetch
     * @param   mixed $default The default to return (optional)
     * @return  mixed
     */
    protected static function opt($key, $default='')
    {
        $opts = get_option(self::SETTING, array());
        return !empty($opts[$key]) ? $opts[$key] : $default;
    }
} // end class
