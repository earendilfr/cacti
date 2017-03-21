<?php
/*
################################################################################
#
#    This program is free software: you can redistribute it and/or modify
#    it under the terms of the GNU General Public License as published by
#    the Free Software Foundation, either version 3 of the License, or
#    any later version.
#
#    This program is distributed in the hope that it will be useful,
#    but WITHOUT ANY WARRANTY; without even the implied warranty of
#    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
#    GNU General Public License for more details.
#
#    You should have received a copy of the GNU General Public License
#    along with this program.  If not, see <http://www.gnu.org/licenses/>.
#
####################################################################################
*/

/* do NOT run this script through a web browser */
if (!isset($_SERVER["argv"][0]) || isset($_SERVER['REQUEST_METHOD'])  || isset($_SERVER['REMOTE_ADDR'])) {
	die("<br><strong>This script is only meant to run at the command line.</strong>");
}

$no_http_headers = true;

/* display ALL errors */
error_reporting(0);

include_once(dirname(__FILE__) . "/../include/global.php");
include_once(dirname(__FILE__) . "/../lib/snmp.php");

$oid = array();

if (!isset($called_by_script_server)) {
	array_shift($_SERVER["argv"]);
	print call_user_func_array("ss_cisco_cpu",$_SERVER["argv"]);
}

function ss_cisco_cpu($hostname, $snmp_auth) {
	global $oid;

	$oid = array(
		"cpmCPUTotal5minRev" => "1.3.6.1.4.1.9.9.109.1.1.1.1.8",
		"cpmCPUTotal1minRev" => "1.3.6.1.4.1.9.9.109.1.1.1.1.7",
		"cpmCPUTotal5secRev" => "1.3.6.1.4.1.9.9.109.1.1.1.1.6",
		"cpmCPUTotal5min" => "1.3.6.1.4.1.9.9.109.1.1.1.1.5",
		"cpmCPUTotal1min" => "1.3.6.1.4.1.9.9.109.1.1.1.1.4",
		"cpmCPUTotal5sec" => "1.3.6.1.4.1.9.9.109.1.1.1.1.3",
	);

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

	# Retrieve 5min value
	$cpu5m = __get_cpu_max($hostname, $snmp_community, $oid["cpmCPUTotal5minRev"], $snmp_version, $snmp_auth_username, $snmp_auth_password, $snmp_auth_protocol, $snmp_priv_passphrase, $snmp_priv_protocol, $snmp_context, $snmp_port, $snmp_timeout, $ping_retries, $max_oids, SNMP_POLLER);
	if (!is_numeric($cpu5m)) {
		$cpu5m = __get_cpu_max($hostname, $snmp_community, $oid["cpmCPUTotal5min"], $snmp_version, $snmp_auth_username, $snmp_auth_password, $snmp_auth_protocol, $snmp_priv_passphrase, $snmp_priv_protocol, $snmp_context, $snmp_port, $snmp_timeout, $ping_retries, $max_oids, SNMP_POLLER);
		if (!is_numeric($cpu5m)) { 
			$cpu5m = "U"; 
		}
	}

	# Retrieve 1min value
	$cpu1m = __get_cpu_max($hostname, $snmp_community, $oid["cpmCPUTotal1minRev"], $snmp_version, $snmp_auth_username, $snmp_auth_password, $snmp_auth_protocol, $snmp_priv_passphrase, $snmp_priv_protocol, $snmp_context, $snmp_port, $snmp_timeout, $ping_retries, $max_oids, SNMP_POLLER);
	if (!is_numeric($cpu1m)) {
	    $cpu1m = __get_cpu_max($hostname, $snmp_community, $oid["cpmCPUTotal1min"], $snmp_version, $snmp_auth_username, $snmp_auth_password, $snmp_auth_protocol, $snmp_priv_passphrase, $snmp_priv_protocol, $snmp_context, $snmp_port, $snmp_timeout, $ping_retries, $max_oids, SNMP_POLLER);
	    if (!is_numeric($cpu1m)) {
	        $cpu1m = "U";
	    }
	}

	# Retrieve 5sec value
	$cpu5s = __get_cpu_max($hostname, $snmp_community, $oid["cpmCPUTotal5secRev"], $snmp_version, $snmp_auth_username, $snmp_auth_password, $snmp_auth_protocol, $snmp_priv_passphrase, $snmp_priv_protocol, $snmp_context, $snmp_port, $snmp_timeout, $ping_retries, $max_oids, SNMP_POLLER);
	if (!is_numeric($cpu5s)) {
	    $cpu5s = __get_cpu_max($hostname, $snmp_community, $oid["cpmCPUTotal5sec"], $snmp_version, $snmp_auth_username, $snmp_auth_password, $snmp_auth_protocol, $snmp_priv_passphrase, $snmp_priv_protocol, $snmp_context, $snmp_port, $snmp_timeout, $ping_retries, $max_oids, SNMP_POLLER);
	    if (!is_numeric($cpu5s)) {
	        $cpu5s = "U";
	    }
	}
	
	return trim("cpu5m:$cpu5m cpu1m:$cpu1m cpu5s:$cpu5s");
}

function __get_cpu_max($hostname, $snmp_community, $oid, $snmp_version, $snmp_auth_username, $snmp_auth_password, $snmp_auth_protocol, $snmp_priv_passphrase, $snmp_priv_protocol, $snmp_context, $snmp_port, $snmp_timeout, $ping_retries, $max_oids) {
	$tmp = cacti_snmp_walk($hostname, $snmp_community, $oid, $snmp_version, $snmp_auth_username, $snmp_auth_password, $snmp_auth_protocol, $snmp_priv_passphrase, $snmp_priv_protocol, $snmp_context, $snmp_port, $snmp_timeout, $ping_retries, $max_oids, SNMP_POLLER);
	
	$result = array();
	foreach($tmp as $index => $value) {
		array_push($result,filter_var($value['value'],FILTER_SANITIZE_NUMBER_FLOAT));
	}
	if (sizeof($result) > 0) {
		return max($result);
	} else {
		return "";
	}
}

/* vim: set tabstop=4 noexpandtab: */

?>
