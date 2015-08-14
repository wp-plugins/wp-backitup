<?php if (!defined ('ABSPATH')) die('No direct access allowed');

// no PHP timeout for running updates
if( ini_get('safe_mode') ){
   @ini_set('max_execution_time', 0);
}else{
   @set_time_limit(0);
}

/**
 * Run the incremental updates one by one.
 *
 * For example, if the current DB version is 3, and the target DB version is 6,
 * this function will execute update routines if they exist:
 *  - wpbackitup_update_routine_4()
 *  - wpbackitup_routine_5()
 *  - wpbackitup_update_routine_6()
 *
 */
function wpbackitup_update_plugin() {
	
	// this is the current database schema version number
	$current_plugin_major_ver = get_option( 'wp-backitup_major_version',0 );

	// this is the target version that we need to reach
	$target_plugin_major_ver = WPBACKITUP__MAJOR_VERSION;

	//Major updates require routine

	// run update routines one by one until the current version number
	// reaches the target version number
	while ( $current_plugin_major_ver < $target_plugin_major_ver ) {

		error_log( 'Run Update plugin routines');
		error_log( 'Current Plugin Major Version:'.$current_plugin_major_ver );
		error_log( 'Target Plugin Major Version:'.$target_plugin_major_ver );

		// increment the current db_ver by one
		$current_plugin_major_ver ++;

		// each version will require a separate update function
		// for example, for ver 3, the function name should be solis_update_routine_3
		$func = "wpbackitup_update_plugin_routine_{$current_plugin_major_ver}";
		if ( function_exists( $func ) ) {
			error_log( 'Run:' .$func);
			call_user_func( $func );
			error_log( 'Run complete:' .$func);
		}

		// update the option in the database, so that this process can always
		// pick up where it left off
		update_option( 'wp-backitup_major_version', $current_plugin_major_ver );
	}

}


/**
 *  Plugin update 0 to 1
 */
function wpbackitup_update_plugin_routine_1(){

	//Need to reset the batch size for this release
	$batch_size = get_option('wp-backitup_backup_batch_size');
	if ($batch_size<100){
		delete_option('wp-backitup_backup_batch_size');
	}

	//Migrate old properties - can be removed in a few releases
	$old_lite_name = get_option('wp-backitup_lite_registration_first_name');
	if ($old_lite_name) {
		update_option('wp-backitup_license_customer_name',$old_lite_name);
		delete_option('wp-backitup_lite_registration_first_name');
	}

	$old_lite_email = get_option('wp-backitup_lite_registration_email');
	if ($old_lite_email) {
		update_option('wp-backitup_license_customer_email',$old_lite_email);
		delete_option('wp-backitup_lite_registration_email');
	}
}

