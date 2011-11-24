<?php
/*
Plugin Name: Simple Login Lockdown
Plugin URI: http://www.christopherguitar.net/wordpress
Description: A simple way to prevent brute force login attemps on your WordPress installation.
Version: 0.1
Author: Christopher Davis
Author URI: http://www.christopherguitar.net/
License: GPL

	Copyright 2011  Christopher Davis  (email : chris@classicalguitar.org)

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License, version 2, as 
    published by the Free Software Foundation.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

function cd_sll_get_ip()
{
	$ip = isset( $_SERVER['REMOTE_ADDR'] ) ? $_SERVER['REMOTE_ADDR'] : false;
	if( ! $ip ) return false;
	return apply_filters( 'cd_sll_pre_ip', $ip );
}

add_action( 'wp_login_failed', 'cd_sll_failed_login' );
function cd_sll_failed_login( $username )
{
	$ip = cd_sll_get_ip();
	if( ! $ip ) return;
	
	if( $count = get_option( sprintf( 'cdll2_%s', $ip ) ) )
	{
		update_option( sprintf( 'cdll2_%s', $ip ), $count + 1);
	}
	else
	{
		add_option( sprintf( 'cdll2_%s', $ip ), 1, '', 'yes' );
	}
}

add_action( 'login_init', 'cd_sll_maybe_kill_login' );
function cd_sll_maybe_kill_login()
{
	$ip = cd_sll_get_ip();
	if( ! $ip ) return;
	
	if( $count = get_option( sprintf( 'cdll2_%s', $ip ) ) )
	{
		if( $count >= 5 ) 
		{
			set_transient( sprintf( 'locked_down_%s', $ip ), True, 60 * 60 );
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
function cd_sll_clear_lockdown()
{
	$ip = cd_sll_get_ip();
	if( ! $ip ) return;
	delete_option( sprintf( 'cdll2_%s', $ip ) );
	delete_transient( sprintf( 'locked_down_%s', $ip ) );
}
