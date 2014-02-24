<?php
function simple_login_lockdown_human_time_diff( $since ) {

	// Array of time period chunks
	$chunks = array(
		array( 60 * 60 * 24 * 365 , __( 'year', 'simple-login-lockdown' ), __( 'years', 'simple-login-lockdown' ) ),
		array( 60 * 60 * 24 * 30 , __( 'month', 'simple-login-lockdown' ), __( 'months', 'simple-login-lockdown' ) ),
		array( 60 * 60 * 24 * 7, __( 'week', 'simple-login-lockdown' ), __( 'weeks', 'simple-login-lockdown' ) ),
		array( 60 * 60 * 24 , __( 'day', 'simple-login-lockdown' ), __( 'days', 'simple-login-lockdown' ) ),
		array( 60 * 60 , __( 'hour', 'simple-login-lockdown' ), __( 'hours', 'simple-login-lockdown' ) ),
		array( 60 , __( 'minute', 'simple-login-lockdown' ), __( 'minutes', 'simple-login-lockdown' ) ),
		array( 1, __( 'second', 'simple-login-lockdown' ), __( 'seconds', 'simple-login-lockdown' ) )
	);

	// Difference in seconds
	$since = time() - $since;
	$future = false;

	// Something went wrong with date calculation and we ended up with a negative date.
	if ( $since < 0 )
	{
		$since = $since * -1;
		$future = true;
	}

	/**
	 * We only want to output one chunks of time here, eg:
	 * x years
	 * xx months
	 * so there's only one bit of calculation below:
	 */

	//Step one: the first chunk
	for ( $i = 0, $j = count($chunks); $i < $j; $i++) {
		$seconds = $chunks[$i][0];

		// Finding the biggest chunk (if the chunk fits, break)
		if ( ( $count = floor($since / $seconds) ) != 0 )
			break;
	}

	// Set output var
	$output = ( 1 == $count ) ? '1 '. $chunks[$i][1] : $count . ' ' . $chunks[$i][2];


	if ( !(int)trim($output) ){
		$output = '0 ' . __( 'seconds', 'simple-login-lockdown' );
	}

	if ( $future )
		$output = __('For another ', 'simple-login-lockdown') . $output;
	else
		$output .= __(' ago', 'simple-login-lockdown');

	return $output;
}