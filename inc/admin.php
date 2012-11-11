<?php
class simpleLoginLockdownAdmin
{
    private $setting = 'cd_sll_options';
    
    function __construct()
    {
        add_action( 'admin_init', array( $this, 'init' ) );
        add_action( 'load-options-privacy.php', array( $this, 'load' ) );
        add_action( 'plugin_action_links_' . CD_SLL_NAME, array( $this, 'link' ), 10, 1 );
    }
    
    /**
     * Fires on admin_init and register our setting
     */
    function init()
    {
        register_setting(
            'reading',
            $this->setting,
            array( $this, 'clean_settings' )
        );
    }
    
    /**
     * Adds the settings fields to the options-privacy.php page in the
     * WordPress admin
     * 
     * @since 0.2
     */
    function load()
    {   
        add_settings_section(
            'cd_sll_settings',
            __( 'Simple Login Lockdown', 'cdsll' ),
            array( $this, 'section_cb' ),
            'reading'
        );
        
        add_settings_field(
            'cd_sll_attempts',
            __( 'Login attempt limit', 'cdsll' ),
            array( $this, 'attempts_cb' ),
            'reading',
            'cd_sll_settings',
            array( 'label_for' => 'cd_sll_attempts' )
        );
        
        add_settings_field(
            'cd_sll_time',
            __( 'Login lockdown time', 'cdsll' ),
            array( $this, 'time_cb' ),
            'reading',
            'cd_sll_settings',
            array( 'label_for' => 'cd_sll_time' )
        );
    }
    
    /**
     * The callback for the Simple Login Lockdown settings section
     * 
     * @since 0.2
     */
    function section_cb()
    {
        echo '<p class="description">';
        _e( 'These options were added by Simple Login Lockdown and control access to your login form.', 'cdsll' );
        echo '</p>';
    }
    
    /**
     * The callback for the attempt allowance settings field
     * 
     * @since 0.2
     */
    function attempts_cb()
    {
        $opts = get_option( $this->setting );
        $limit = isset( $opts['limit'] ) && $opts['limit'] ? $opts['limit'] : 5;
        echo '<select id="cd_sll_attempts" name="' . $this->setting . '[limit]">';
        foreach( range( 5, 20 ) as $i )
        {
                echo "<option value='{$i}' " . selected( $i, absint( $limit ), false ) . ">{$i}</option>";
        }
        echo '</select>';
    }
    
    /**
     * The callback for the time limit settings field
     * 
     * @since 0.2
     */
    function time_cb()
    {
        $opts = get_option( $this->setting );
        $time = isset( $opts['time'] ) && $opts['time'] ? $opts['time'] : 60;
        
        $options = array(
            30      => __( '30 Minutes', 'cdsll' ),
            60      => __( '60 Minutes', 'cdsll' ),
            120     => __( '2 Hours', 'cdsll' ),
            180     => __( '3 Hours', 'cdsll' ),
            240     => __( '4 Hours', 'cdsll' ),
            480     => __( '8 Hours', 'cdsll' ),
            1440    => __( '24 Hours', 'cdsll' )
        );
        echo '<select id="cd_sll_time" name="' . $this->setting . '[time]">';
        foreach( $options as $t => $label )
        {
            echo "<option value='{$t}' ". selected( $t, absint( $time ), false ) . ">" . esc_html( $label ) . "</option>";
        }
        echo '</select>';
        echo '<p class="description">';
        _e( 'After the number of failed login attempts (specified above), how long should the user be locked out?', 'cdsll' );
        echo '</p>';
    }
    
    /**
     * Cleans our settings on the way into the database
     * 
     * @since 0.2
     * 
     * @return array
     */
    function clean_settings( $in )
    {
        $out = array();
        $out['time'] = isset( $in['time'] ) && $in['time'] ? absint( $in['time'] ) : 60;
        $out['limit'] = isset( $in['limit'] ) && $in['limit'] ? absint( $in['limit'] ) : 5;
        return $out;
    }
    
    /**
     * Adds a "settings" link to the plugin page.
     * 
     * @since 0.2
     * 
     * @return array
     */
    function link( $links )
    {
        $links['settings'] = '<a href="' . admin_url( 'options-reading.php' ) . '">' . __( 'Settings', 'cdsll' ) . '</a>';
        return $links;
    }
    
} // end class

new simpleLoginLockdownAdmin();
