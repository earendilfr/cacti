<?php

/* do NOT run this script through a web browser */
if (!isset($_SERVER["argv"][0]) || isset($_SERVER['REQUEST_METHOD'])  || isset($_SERVER['REMOTE_ADDR'])) {
	die("<br><strong>This script is only meant to run at the command line.</strong>");
}

$no_http_headers = true;

/* display ALL errors */
error_reporting(0);

include_once(dirname(__FILE__) . "/../include/global.php");
include_once(dirname(__FILE__) . "/../lib/snmp.php");

$oid = array(
    "entPhysicalName" => "1.3.6.1.2.1.47.1.1.1.1.7",
    "entPhysicalDescr" => "1.3.6.1.2.1.47.1.1.1.1.2",
    "hh3cEntityExtCpuUsage" => "1.3.6.1.4.1.25506.2.6.1.1.1.1.6",
    "hh3cEntityExtMemUsage" => "1.3.6.1.4.1.25506.2.6.1.1.1.1.8",
);

if (!isset($called_by_script_server)) {
	array_shift($_SERVER["argv"]);
	print call_user_func_array("ss_hp_cpu_mem",$_SERVER["argv"]);
}

function ss_hp_cpu_mem($hostname, $snmp_auth, $cmd, $arg1 = "", $arg2 = "") {
	$snmp = explode(":", $snmp_auth);
	$snmp_version   = $snmp[0];
	$snmp_port      = $snmp[1];
	$snmp_timeout   = $snmp[2];
	$ping_retries   = $snmp[3];
	$max_oids       = $snmp[4];
	
	$snmp_auth_username     = "";
	$snmp_auth_password     = "";
	$snmp_auth_protocol     = "";
	$snmp_priv_passphrase   = "";
	$snmp_priv_protocol     = "";
	$snmp_context           = "";
	$snmp_community         = "";
	
	if ($snmp_version == 3) {
		$snmp_auth_username   = $snmp[6];
		$snmp_auth_password   = $snmp[7];
		$snmp_auth_protocol   = $snmp[8];
		$snmp_priv_passphrase = $snmp[9];
		$snmp_priv_protocol   = $snmp[10];
		$snmp_context         = $snmp[11];
	}else{
		$snmp_community = $snmp[5];
	}

	$result = "";
	switch ($cmd) {
		case 'numindex':
			return count(__index($hostname, $snmp_community, $snmp_version, $snmp_auth_username, $snmp_auth_password, $snmp_auth_protocol, $snmp_priv_passphrase, $snmp_priv_protocol, $snmp_context, $snmp_port, $snmp_timeout, $ping_retries, $max_oids));
		case 'index':
			$result_array = __index($hostname, $snmp_community, $snmp_version, $snmp_auth_username, $snmp_auth_password, $snmp_auth_protocol, $snmp_priv_passphrase, $snmp_priv_protocol, $snmp_context, $snmp_port, $snmp_timeout, $ping_retries, $max_oids);
			foreach ($result_array as $index) { $result .= "$index\n"; }
			break;
		case 'query':
			$index_array = __index($hostname, $snmp_community, $snmp_version, $snmp_auth_username, $snmp_auth_password, $snmp_auth_protocol, $snmp_priv_passphrase, $snmp_priv_protocol, $snmp_context, $snmp_port, $snmp_timeout, $ping_retries, $max_oids);
			$result_array = __query($hostname, $snmp_community, $snmp_version, $snmp_auth_username, $snmp_auth_password, $snmp_auth_protocol, $snmp_priv_passphrase, $snmp_priv_protocol, $snmp_context, $snmp_port, $snmp_timeout, $ping_retries, $max_oids, $arg1, $index_array);
			foreach ($result_array as $index => $value) { $result .= "$index:$value\n"; }
			break;
		case 'get':
			$result = __get_value($hostname, $snmp_community, $snmp_version, $snmp_auth_username, $snmp_auth_password, $snmp_auth_protocol, $snmp_priv_passphrase, $snmp_priv_protocol, $snmp_context, $snmp_port, $snmp_timeout, $ping_retries, $max_oids, $arg1, $arg2);
			break;
		default:
			fwrite(STDERR,"ERROR unknown command $cmd");
			return '';
	}
	return trim($result);
}

function __index($hostname, $snmp_community, $snmp_version, $snmp_auth_username, $snmp_auth_password, $snmp_auth_protocol, $snmp_priv_passphrase, $snmp_priv_protocol, $snmp_context, $snmp_port, $snmp_timeout, $ping_retries, $max_oids) {
	global $oid;

	$all_index = cacti_snmp_walk($hostname, $snmp_community, $oid['entPhysicalName'], $snmp_version, $snmp_auth_username, $snmp_auth_password, $snmp_auth_protocol, $snmp_priv_passphrase, $snmp_priv_protocol, $snmp_context, $snmp_port, $snmp_timeout, $ping_retries, $max_oids, SNMP_POLLER);
	
	$result = array();
	foreach($all_index as $index => $value) {
		if ($value['value'] == "Board" && preg_match("/".preg_quote($oid['entPhysicalName'])."\.(.*)/",$value['oid'],$tmp)) {
			array_push($result,$tmp[1]);
		}
	}
	return $result;
}

function __query($hostname, $snmp_community, $snmp_version, $snmp_auth_username, $snmp_auth_password, $snmp_auth_protocol, $snmp_priv_passphrase, $snmp_priv_protocol, $snmp_context, $snmp_port, $snmp_timeout, $ping_retries, $max_oids, $name,$indexes) {
	global $oid;

	$result = array();
	switch($name) {
		case 'index':
			foreach ($indexes as $index) { $result[$index] = $index; }
			return $result;
		case 'entPhysicalDescr':
		case 'entPhysicalName':
			foreach ($indexes as $index) {
				$result[$index] = cacti_snmp_get($hostname, $snmp_community, $oid[$name].".".$index, $snmp_version, $snmp_auth_username, $snmp_auth_password, $snmp_auth_protocol, $snmp_priv_passphrase, $snmp_priv_protocol, $snmp_context, $snmp_port, $snmp_timeout, $ping_retries, $max_oids, SNMP_POLLER);
			}
			return $result;
		default:
			fwrite(STDERR,"ERROR: unknown command $name");
			return '';
	}
	return '';
}

function __get_value($hostname, $snmp_community, $snmp_version, $snmp_auth_username, $snmp_auth_password, $snmp_auth_protocol, $snmp_priv_passphrase, $snmp_priv_protocol, $snmp_context, $snmp_port, $snmp_timeout, $ping_retries, $max_oids, $name, $index) {
	global $oid;
	
	switch($name) {
		case 'mem':
			return cacti_snmp_get($hostname, $snmp_community, $oid['hh3cEntityExtMemUsage'].".$index", $snmp_version, $snmp_auth_username, $snmp_auth_password, $snmp_auth_protocol, $snmp_priv_passphrase, $snmp_priv_protocol, $snmp_context, $snmp_port, $snmp_timeout, $ping_retries, $max_oids, SNMP_POLLER);
		case 'cpu':
			return cacti_snmp_get($hostname, $snmp_community, $oid['hh3cEntityExtCpuUsage'].".$index", $snmp_version, $snmp_auth_username, $snmp_auth_password, $snmp_auth_protocol, $snmp_priv_passphrase, $snmp_priv_protocol, $snmp_context, $snmp_port, $snmp_timeout, $ping_retries, $max_oids, SNMP_POLLER);
		default:
			fwrite(STDERR,"ERROR: unknown command $name");
			return '';
	}
}

/* vim: set tabstop=4 noexpandtab: */


?>
