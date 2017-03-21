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

if (!isset($called_by_script_server)) {
	array_shift($_SERVER["argv"]);
	print call_user_func_array("ss_ucd_mem",$_SERVER["argv"]);
}

function ss_ucd_mem($hostname, $snmp_auth) {
	$oids = array(
		"memTotalSwap" => "1.3.6.1.4.1.2021.4.3.0",
		"memAvailSwap" => "1.3.6.1.4.1.2021.4.4.0",
		"memTotalReal" => "1.3.6.1.4.1.2021.4.5.0",
		"memAvailReal" => "1.3.6.1.4.1.2021.4.6.0",
		"memTotalFree" => "1.3.6.1.4.1.2021.4.11.0",
		"memMinimumSwap" => "1.3.6.1.4.1.2021.4.12.0",
		"memShared" => "1.3.6.1.4.1.2021.4.13.0",
		"memBuffer" => "1.3.6.1.4.1.2021.4.14.0",
		"memCached" => "1.3.6.1.4.1.2021.4.15.0",
	);

	$result = array();

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

	foreach ($oids as $label => $oid) {
		$tmp = cacti_snmp_get($hostname, $snmp_community, $oid, $snmp_version, $snmp_auth_username, $snmp_auth_password, $snmp_auth_protocol, $snmp_priv_passphrase, $snmp_priv_protocol, $snmp_context, $snmp_port, $snmp_timeout, $ping_retries, SNMP_POLLER);
		if (is_numeric($tmp)) {
			$result[$label] = $tmp;
		} elseif (preg_match('/(\d+)\s*kB/',$tmp,$matches)) {
			$result[$label] = $matches[0]; 
		} else {
			$result[$label] = 0;
		}
	}

	// Get Swap used
	$used_swap = $result['memTotalSwap'] - $result['memAvailSwap'];

	// Get Memory used
	$used_mem = $result['memTotalReal'] - ($result["memCached"] + $result["memBuffer"] + $result["memShared"] + $result["memAvailReal"]);

	// Return
	return trim(sprintf("total:%d buffer:%d cached:%d shared:%d free:%d used:%d swap_used:%d",
		$result['memTotalReal'],
		$result["memBuffer"],
		$result["memCached"],
		$result["memShared"],
		$result["memAvailReal"],
		$used_mem,
		$used_swap));
}

/* vim: set tabstop=4 noexpandtab: */

?>
