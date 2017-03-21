<?php

/* do NOT run this script through a web browser */
if (!isset($_SERVER["argv"][0]) || isset($_SERVER['REQUEST_METHOD'])  || isset($_SERVER['REMOTE_ADDR'])) {
	die("<br><strong>This script is only meant to run at the command line.</strong>");
}

$no_http_headers = true;

/* display ALL errors */
error_reporting(0);

if (!isset($called_by_script_server)) {
	include_once(dirname(__FILE__) . "/../include/global.php");
	array_shift($_SERVER["argv"]);
	if (sizeof($_SERVER["argv"]) > 0) {
		print call_user_func_array("ss_mysql",$_SERVER["argv"]);
	} else {
		print call_user_func_array("ss_mysql",array("cache"))."\n";
		print call_user_func_array("ss_mysql",array("q_cache"))."\n";
		print call_user_func_array("ss_mysql",array("command"))."\n";
		print call_user_func_array("ss_mysql",array("thread"))."\n";
		print call_user_func_array("ss_mysql",array("traffic"))."\n";
	}
}

function ss_mysql($cmd, $hostaccess = "", $spec_stat = "") {
	global $database_username;
	global $database_password;
	global $database_hostname;

	$result = explode(':',$hostaccess);

	$hostname = !empty($result[0]) ? $result[0] : $database_hostname;
	$username = !empty($result[1]) ? $result[1] : $database_username;
	$password = !empty($result[2]) ? $result[2] : $database_password;

	$link = mysqli_connect($hostname, $username, $password);
	if(!mysqli_connect_errno()) {
		if ($_result = mysqli_query($link,"SHOW GLOBAL STATUS")) {
			while ($_fld_stat = mysqli_fetch_row($_result)) { $status[$_fld_stat[0]] = $_fld_stat[1]; }
		}
		if ($_result = mysqli_query($link,"SHOW GLOBAL VARIABLES")) {
			while ($_fld_var = mysqli_fetch_row($_result)) { $variables[$_fld_var[0]] = $_fld_var[1]; }
		}
		mysqli_close($link);
	} else {
		fwrite(STDERR,"ERROR: unable to connect to the MySQL Server $hostname\n");
		return;
	}

	if (!is_array($status) || !is_array($variables)) {
		fwrite(STDERR,"ERROR: Can't get MySQL statistic\n");
		return;
	}

	switch($cmd) {
		case 'cache':
			$used = $variables["query_cache_size"]-$status["Qcache_free_memory"];
			return trim(sprintf("available:%d used:%d",
				$status["Qcache_free_memory"],
				$used));
			break;
		case 'q_cache':
			return trim(sprintf("hits:%d inserts:%d l_prunes:%d not_cached:%d nr_in_cache:%d",
				$status["Qcache_hits"],
				$status["Qcache_inserts"],
				$status["Qcache_lowmem_prunes"],
				$status["Qcache_not_cached"],
				$status["Qcache_queries_in_cache"]));
			break;
		case 'command':
			return trim(sprintf("delete:%d read_first:%d read_key:%d read_next:%d read_prev:%d read_rnd:%d read_rnd_next:%d update:%d write:%d",
				$status["Handler_delete"],
				$status["Handler_read_first"],
				$status["Handler_read_key"],
				$status["Handler_read_next"],
				$status["Handler_read_prev"],
				$status["Handler_read_rnd"],
				$status["Handler_read_rnd_next"],
				$status["Handler_update"],
				$status["Handler_write"]));
			break;
		case 'thread':
			return trim(sprintf("connected:%d running:%d cached:%d",
				$status["Threads_connected"],
				$status["Threads_running"],
				$status["Threads_cached"]));
			break;
		case 'traffic':
			return trim(sprintf("in:%d out:%d",
				$status["Bytes_received"],
				$status["Bytes_sent"]));
			break;
		case 'status':
			if (!isset($spec_stat) || $spec_stat == "") { 
				fwrite(STDERR,"ERROR: you should add a parameter to use status command\n"); 
				return;
			}
			return trim(sprintf("%s",$status[$spec_stat]));
			break;
		default:
			fwrite(STDERR,"ERROR: unkown command $cmd\n");
			return;
	}
	return;
}

/* ex: set tabstop=4 noexpandtab: */

?>
