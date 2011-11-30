<?php
/*
Plugin Name: Simple Login Lockdown
Plugin URI: http://www.christopherguitar.net/wordpress
Description: A simple way to prevent brute force login attemps on your WordPress installation.
Version: 0.4
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

define( 'CD_SLL_PATH', plugin_dir_path( __FILE__ ) );
define( 'CD_SLL_NAME', plugin_basename( __FILE__ ) );

if( is_admin() && ( ! defined( 'DOING_AJAX' ) || ! DOING_AJAX )  )
{
	require_once( CD_SLL_PATH . 'inc/admin.php' );
}
else
{
	require_once( CD_SLL_PATH . 'inc/login.php' );
}

register_activation_hook( __FILE__, 'cd_sll_plugin_activation' );
/**
 * Activation hook.  Adds default settings.
 * 
 * @since 0.2
 * 
 * @return none
 */
function cd_sll_plugin_activation()
{
	add_option(
		'cd_sll_options',
		array(
			'limit'	=> 5,
			'time'	=> 60
		)
	);
}

