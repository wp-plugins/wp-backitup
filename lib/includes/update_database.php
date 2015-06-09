<?php if (!defined ('ABSPATH')) die('No direct access allowed');


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
function wpbackitup_update_database() {
	// no PHP timeout for running updates
	set_time_limit( 0 );

	// this is the current database schema version number
	$current_db_ver = get_option( 'wp-backitup_db_version',0 );

	// this is the target version that we need to reach
	$target_db_ver = WPBackitup_Admin::DB_VERSION;

	// run update routines one by one until the current version number
	// reaches the target version number
	while ( $current_db_ver < $target_db_ver ) {

		error_log( 'Run Update database routines');
		error_log( 'Current DB version:'.$current_db_ver );
		error_log( 'Target DB version:'.$target_db_ver );

		// increment the current db_ver by one
		$current_db_ver ++;

		// each db version will require a separate update function
		// for example, for db_ver 3, the function name should be solis_update_routine_3
		$func = "wpbackitup_update_database_routine_{$current_db_ver}";
		if ( function_exists( $func ) ) {
			error_log( 'Run:' .$func);
			call_user_func( $func );
			error_log( 'Run complete:' .$func);
		}

		// update the option in the database, so that this process can always
		// pick up where it left off
		update_option( 'wp-backitup_db_version', $current_db_ver );
	}
}


/**
 * DB version 0 to 1 update
 *
 */
function wpbackitup_update_database_routine_1(){
	global $wpdb;

	$charset_collate = $wpdb->get_charset_collate();
	$table_name = $wpdb->prefix . "wpbackitup_job";

	$sql = "CREATE TABLE $table_name (
			  id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			  job_id bigint(20) NOT NULL,
			  batch_id bigint(20) DEFAULT NULL,
			  group_id varchar(15) DEFAULT NULL,
			  item longtext,
			  size_kb bigint(20) DEFAULT NULL,
			  retry_count int(11) NOT NULL DEFAULT '0',
			  status int(11) NOT NULL DEFAULT '0',
			  create_date datetime DEFAULT NULL,
			  update_date datetime DEFAULT NULL,
			  record_type varchar(1) NOT NULL DEFAULT 'I',
			  PRIMARY KEY (id)
			) $charset_collate;";

	require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
	dbDelta( $sql );
}