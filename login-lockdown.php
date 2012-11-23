<?php
/*
Plugin Name: Simple Login Lockdown
Plugin URI: https://github.com/chrisguitarguy/simple-login-lockdown
Description: A simple way to prevent brute force login attemps on your WordPress installation.
Version: 1.0
Author: Christopher Davis
Author URI: http://christopherdavis.me
License: MIT

    Copyright (c) 2012 Christopher Davis

    Permission is hereby granted, free of charge, to any person obtaining a
    copy of this software and associated documentation files (the "Software"),
    to deal in the Software without restriction, including without limitation
    the rights to use, copy, modify, merge, publish, distribute, sublicense,
    and/or sell copies of the Software, and to permit persons to whom the
    Software is furnished to do so, subject to the following conditions:

    The above copyright notice and this permission notice shall be included in
    all copies or substantial portions of the Software.

    THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
    IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
    FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
    AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
    LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING
    FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER
    DEALINGS IN THE SOFTWARE.
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

