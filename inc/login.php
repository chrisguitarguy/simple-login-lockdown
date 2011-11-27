<?php
/**
 * Get the $_SERVER['REMOTE_ADDR'] value.  Uses apply_filters
 * so plugins/themes can hook into change the value if they're using a 
 * load balancer or behind some other proxy.
 * 
 * @since 0.1
 * 
 * @return unknown
 */
function cd_sll_get_ip()
{
	$ip = isset( $_SERVER['REMOTE_ADDR'] ) ? $_SERVER['REMOTE_ADDR'] : false;
	return apply_filters( 'cd_sll_pre_ip', $ip );
}

add_action( 'wp_login_failed', 'cd_sll_failed_login' );
/**
 * Catch failed login attemps due a faulty username/password combination
 * 
 * If a login attempt fails, this function will add/update an option with
 * a count of how many times that attempt has failed.
 * 
 * @since 0.1
 * 
 * @return none
 */
function cd_sll_failed_login( $username )
{
	$ip = cd_sll_get_ip();
	if( ! $ip ) return;
	
	if( $count = get_option( sprintf( 'cdll2_%s', $ip ) ) )
	{
		update_option( sprintf( 'cdll2_%s', $ip ), ++$count );
	}
	else
	{
		add_option( sprintf( 'cdll2_%s', $ip ), 1, '', 'yes' );
	}
}

add_action( 'login_init', 'cd_sll_maybe_kill_login' );
/**
 * Kills the login page via wp_die if login attempt allowance has been 
 * exceeded or the IP address is locked down.
 * 
 * @since 0.1
 *  
 * @return none
 */
function cd_sll_maybe_kill_login()
{
	$ip = cd_sll_get_ip();
	if( ! $ip ) return;
	
	if( $count = get_option( sprintf( 'cdll2_%s', $ip ) ) )
	{
		$opts = get_option( 'cd_sll_options' );
		$limit = isset( $opts['limit'] ) && $opts['limit'] ? $opts['limit'] : 5;
		
		if( $count >= absint( $limit ) ) 
		{
			$time = isset( $opts['time'] ) && $opts['time'] ? $opts['time'] : 60;
			set_transient( sprintf( 'locked_down_%s', $ip ), True, absint( $time ) * 60 );
			delete_option( sprintf( 'cdll2_%s', $ip ) );
			wp_die(
				__( 'Too many login attemps from one IP address! Please take a break and try again later' ),
				__( 'Too many login attemps' ),
				array( 'response' => 404 )
			);
		}
	}
	elseif( get_transient( sprintf( 'locked_down_%s', $ip ) ) )
	{
		wp_die(
			__( 'Too many login attemps from one IP address! Please take a break and try again later' ),
			__( 'Too many login attemps' ),
			array( 'response' => 404 )
		);
	}
}

add_action( 'wp_login', 'cd_sll_clear_lockdown', 10, 0 );
/**
 * Clears all lockdown data on a successful login.
 * 
 * @since 0.1
 * 
 * @return none
 */
function cd_sll_clear_lockdown()
{
	$ip = cd_sll_get_ip();
	if( ! $ip ) return;
	delete_option( sprintf( 'cdll2_%s', $ip ) );
	delete_transient( sprintf( 'locked_down_%s', $ip ) );
}
