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
 * Admin area functionality (a few fields) for the plugin
 *
 * @todo    At some point when WP requires PHP 5.3+ don't repeat the static
 *          methods and instance crap.
 *
 * @since   0.1
 * @author  Christopher Davis <http://christopherdavis.me>
 */
class Simple_Login_Lockdown_Admin extends Simple_Login_Lockdown
{
    /**
     * Settings section.
     *
     * @since   1.0
     */
    const SECTION = 'simple-login-lockdown';

    /**
     * The page on which the settings reside.
     *
     * @since   1.0
     * @access  private
     * @var     string
     */
    private $page;

    /**
     * Container for the plugin instance.
     *
     * @since   1.0
     * @access  private
     * @var     object (an instance of this class)
     */
    private static $ins = null;

    public function __construct()
    {
        global $wp_version;
        $this->page = version_compare($wp_version, '3.4.2', '<=') ? 'privacy' : 'reading';
    }

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
     * Hooked into `plugins_loaded`.  Adds the rest of the actions.
     *
     * @since   1.0
     * @access  public
     * @uses    add_action
     * @return  void
     */
    public function _setup()
    {
        add_action('admin_init', array($this, 'register'));
        add_action('plugin_action_links_' . CD_SLL_NAME, array($this, 'link'));
        add_action('admin_head-options-reading.php', array($this, 'display_errors'));
    }

    public function display_errors()
    {
        if ( !empty($_GET['simple-login-lockdown-error']) )
            add_settings_error('general', esc_attr( 'invalid_ip' ), $_GET['simple-login-lockdown-error'], 'error');
        elseif ( !empty($_GET['simple-login-lockdown-success']) )
            add_settings_error('general', esc_attr( 'settings_updated' ), $_GET['simple-login-lockdown-success'], 'updated');
    }

    /**
     * Fires on `admin_init`. Registers the settings and settings field.
     *
     * @since   1.0
     * @access  public
     * @uses    register_setting
     * @uses    add_settings_section
     * @uses    add_settings_field
     * @return  void
     */
    public function register()
    {
        register_setting(
            $this->page,
            self::SETTING,
            array($this, 'clean_settings')
        );

        add_settings_section(
            self::SECTION,
            __('Simple Login Lockdown', 'simple-login-lockdown'),
            array($this, 'section_cb'),
            $this->page
        );

        add_settings_field(
            self::SETTING . '[limit]',
            __('Login Attempt Limit', 'simple-login-lockdown'),
            array($this, 'attempts_cb'),
            $this->page,
            self::SECTION,
            array('label_for' => self::SETTING . '[limit]', 'key' => 'limit')
        );

        add_settings_field(
            self::SETTING . '[time]',
            __('Login Lockdown Time', 'simple-login-lockdown'),
            array($this, 'time_cb'),
            $this->page,
            self::SECTION,
            array('label_for' => self::SETTING . '[time]', 'key' => 'time')
        );

        add_settings_field(
            self::SETTING . '[trust_proxy]',
            __('Trust Proxy Data', 'simple-login-lockdown'),
            array($this, 'trust_proxy_cb'),
            $this->page,
            self::SECTION,
            array('label_for' => self::SETTING . '[trust_proxy]', 'key' => 'trust_proxy')
        );

        $lockouts = $this->get_lockouts();
        if ( $lockouts )
            add_settings_field(
                self::SETTING . '[locked_ip_list]',
                 __('Locked Out', 'simple-login-lockdown'),
                array($this, 'locked_ip_list'),
                $this->page,
                self::SECTION,
                array('lockouts' => $lockouts)
            );
    }

    /**
     * Adds a "settings" link to the plugin page.
     *
     * @since   0.2
     * @access  public
     * @return  array
     */
    public function link($links)
    {
        $links['settings'] = sprintf(
            '<a href="%1$s">%2$s</a>',
            admin_url("options-{$this->page}.php"),
            esc_html__('Settings', 'simple-login-lockdown')
        );

        return $links;
    }

    /**
     * Validate the settings on way into the database.
     *
     * @since   0.2
     * @access  public
     * @uses    absint
     * @return  array
     */
    public function clean_settings($in)
    {
        $out = array();

        foreach (array('time', 'limit') as $k) {
            if (!empty($in[$k])) {
                $out[$k] = absint($in[$k]);
            }
        }

        $out['trust_proxy'] = empty($in['trust_proxy']) ? 'off' : 'on';

        return $out;
    }

    /********** Settings Field/Section Callbacks **********/

    /**
     * The callback for the Simple Login Lockdown settings section
     *
     * @since   0.2
     * @access  public
     * @uses    _e
     * @return  void
     */
    public function section_cb()
    {
        echo '<p class="description">';
        _e('These options were added by Simple Login Lockdown and control ' .
            'access to your login form.', 'simple-login-lockdown');
        echo '</p>';
    }

    /**
     * The callback for the attempt allowance settings field
     *
     * @since   0.2
     * @access  public
     * @uses    selected
     * @param   array $args Field arguments from add_settings_field
     * @return  void
     */
    public function attempts_cb($args)
    {
        $limit = self::opt($args['key'], 5);

        printf('<select name="%1$s" id="%1$s">', $args['label_for']);
        foreach(range(5, 20) as $_ => $i)
        {
            printf(
                '<option value="%1$s" %2$s>%1$s</option>',
                esc_attr($i),
                selected($limit, $i, false)
            );
        }
        echo '</select>';
    }

    /**
     * The callback for the time limit settings field
     *
     * @since   0.2
     * @access  public
     * @uses    selected
     * @return  void
     */
    public function time_cb($args)
    {
        $time = self::opt($args['key'], 60);

        $options = apply_filters('simple_login_lockdown_time_values', array(
            30      => __('30 Minutes', 'simple-login-lockdown'),
            60      => __('60 Minutes', 'simple-login-lockdown'),
            120     => __('2 Hours', 'simple-login-lockdown'),
            180     => __('3 Hours', 'simple-login-lockdown'),
            240     => __('4 Hours', 'simple-login-lockdown'),
            480     => __('8 Hours', 'simple-login-lockdown'),
            1440    => __('24 Hours', 'simple-login-lockdown'),
        ));

        printf('<select id="%1$s" name="%1$s">', esc_attr($args['label_for']));
        foreach($options as $t => $label)
        {
            printf(
                '<option value="%1$s" %2$s>%3$s</option>',
                esc_attr($t),
                selected($t, absint($time), false),
                esc_html($label)
            );
        }
        echo '</select>';

        echo '<p class="description">';
        _e('After the number of failed login attempts (specified above), how '.
            'long should the user be locked out?', 'simple-login-lockdown');
        echo '</p>';
    }

    /**
     * Callback for the `trust_proxy` settings field.
     *
     * @access  public
     * @since   0.3
     * @return  void
     */
    public function trust_proxy_cb($args)
    {
        printf(
            '<input type="checkbox" name="%1$s" id="%1$s" value="on" %2$s />',
            esc_attr($args['label_for']),
            checked('on', self::opt('trust_proxy', 'off'), false)
        );
    }

    /**
     * Get an array of lockouts for the current site
     *
     * @return array
     */
    public function get_lockouts()
    {
        global $wpdb;

        return $wpdb->get_results("
            SELECT option_id, option_name, option_value
            FROM  $wpdb->options
            WHERE `option_name` LIKE '_transient_timeout_locked_down_%'
            ORDER BY option_value
        ", ARRAY_A);
    }

    /**
     * Callback for the blocked ip list section.
     *
     * @param  array $args
     *
     * @return void
     */
    public function locked_ip_list($args)
    {
        require_once(dirname(__FILE__).'/helpers.php');

        $lockouts = $args['lockouts'];

        // No lcokouts? Don't display the table
        if ( !$lockouts )
            return;

        ?>
        <style>
            .simple_login_lockout_ip_list th, .simple_login_lockout_ip_list td {padding:5px;margin:0;width:auto;}
            .simple_login_lockout_ip_list .actions {width:50px;}
        </style>
        <table class='simple_login_lockout_ip_list' width='500' border='1' cellspacing='0' cellpadding='5'>
            <thead>
                <th>IP</th>
                <th>&nbsp;</th>
            </thead>
            <tbody>
                <?php
                    foreach ( $lockouts as $lockout ):
                        $ip = explode('_', $lockout['option_name']);
                        $ip = end($ip);
                ?>
                <tr>
                    <td><?= $ip ?> (<?= simple_login_lockdown_human_time_diff($lockout['option_value']) ?>)</td>
                    <td class='actions'><input type="button" value="Unlock" onclick="window.location.href='<?= admin_url('admin-ajax.php') ?>?action=simple-login-lockdown-unlock&ip=<?= $ip ?>';" /></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php
    }
} // end class
