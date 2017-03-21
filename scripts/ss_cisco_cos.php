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
error_reporting(1);

include_once(dirname(__FILE__) . "/../include/global.php");
include_once(dirname(__FILE__) . "/../lib/snmp.php");

$oid = array();

if (!isset($called_by_script_server)) {
	array_shift($_SERVER["argv"]);
	print call_user_func_array("ss_cisco_cos",$_SERVER["argv"]);
}

function ss_cisco_cos($hostname, $snmp_auth, $cmd, $arg1 = "", $arg2 = "") {
	global $oid;
	$oid = array(
		"cbQosIfIndex" => "1.3.6.1.4.1.9.9.166.1.1.1.1.4",
		"ifName" => "1.3.6.1.2.1.31.1.1.1.1",
		"ifSpeed" => "1.3.6.1.2.1.2.2.1.5",
		"ifHighSpeed" => "1.3.6.1.2.1.31.1.1.1.15",
		"cbQosPolicyDirection" => "1.3.6.1.4.1.9.9.166.1.1.1.1.3",
		"cbQosObjectsType" => "1.3.6.1.4.1.9.9.166.1.5.1.1.3",
		"cbQosConfigIndex" => "1.3.6.1.4.1.9.9.166.1.5.1.1.2",
		"cbQosTSCfgRate" => "1.3.6.1.4.1.9.9.166.1.13.1.1.1",
		"cbQosPolicyMapName" => "1.3.6.1.4.1.9.9.166.1.6.1.1.1",
		"cbQosCMName" => "1.3.6.1.4.1.9.9.166.1.7.1.1.1",
		"cbQosCMPrePolicyByte64" => "1.3.6.1.4.1.9.9.166.1.15.1.1.6",
		"cbQosCMPrePolicyPkt64" => "1.3.6.1.4.1.9.9.166.1.15.1.1.3",
		"cbQosCMDropPkt64" => "1.3.6.1.4.1.9.9.166.1.15.1.1.14",
		"cbQosCMDropByte64" => "1.3.6.1.4.1.9.9.166.1.15.1.1.17",
		"cbQosParentObjectsIndex" => "1.3.6.1.4.1.9.9.166.1.5.1.1.4",
	);
	$cisco_cos_db = "/tmp/.cacti_ciscocos.db";

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

	snmp_set_oid_output_format(SNMP_OID_OUTPUT_NUMERIC);

	$result = "";
	switch($cmd) {
		case 'numindex':
			return count(__index($hostname, $snmp_community, $snmp_version, $snmp_auth_username, $snmp_auth_password, $snmp_auth_protocol, $snmp_priv_passphrase, $snmp_priv_protocol, $snmp_context, $snmp_port, $snmp_timeout, $ping_retries, $max_oids));
		case 'index':
			$result_array = __index($hostname, $snmp_community, $snmp_version, $snmp_auth_username, $snmp_auth_password, $snmp_auth_protocol, $snmp_priv_passphrase, $snmp_priv_protocol, $snmp_context, $snmp_port, $snmp_timeout, $ping_retries, $max_oids);
			foreach($result_array as $index => $ifindex) { $result .= "$index\n"; }
			break;
		case 'query':
			if ($result_array = __query($hostname, $snmp_community, $snmp_version, $snmp_auth_username, $snmp_auth_password, $snmp_auth_protocol, $snmp_priv_passphrase, $snmp_priv_protocol, $snmp_context, $snmp_port, $snmp_timeout, $ping_retries, $max_oids, $arg1)) {
				foreach($result_array as $index => $value) { $result .= "$index:$value\n"; }
			}
			break;
		case 'get':
			$result = 0;
			if (!is_numeric($arg2)) { return 'U'; }
			$class_names = explode(',',$arg1);
			$cos_index = $arg2;
			foreach($class_names as $class_name) {
				if ($class_name == "") { continue; }
				if ($result_array = __get_value($hostname, $snmp_community, $snmp_version, $snmp_auth_username, $snmp_auth_password, $snmp_auth_protocol, $snmp_priv_passphrase, $snmp_priv_protocol, $snmp_context, $snmp_port, $snmp_timeout, $ping_retries, $max_oids, $cos_index, $class_name)) {
					$result += array_sum($result_array);
				} else { 
					return 'U';
				}
			}
			break;
		default:
			return 'U';
	}
	
	return trim($result);
}

function __index($hostname, $snmp_community, $snmp_version, $snmp_auth_username, $snmp_auth_password, $snmp_auth_protocol, $snmp_priv_passphrase, $snmp_priv_protocol, $snmp_context, $snmp_port, $snmp_timeout, $ping_retries, $max_oids) {
	global $oid;
	return  __map_cacti_walk($hostname, $snmp_community, $oid["cbQosIfIndex"], $snmp_version, $snmp_auth_username, $snmp_auth_password, $snmp_auth_protocol, $snmp_priv_passphrase, $snmp_priv_protocol, $snmp_context, $snmp_port, $snmp_timeout, $ping_retries, $max_oids, SNMP_POLLER);
}

function __query($hostname, $snmp_community, $snmp_version, $snmp_auth_username, $snmp_auth_password, $snmp_auth_protocol, $snmp_priv_passphrase, $snmp_priv_protocol, $snmp_context, $snmp_port, $snmp_timeout, $ping_retries, $max_oids, $cmd) {
	global $oid;
	$mib_direction = array( 1 => "input", 2 => "output", "input(1)" => "input", "output(2)" => "output" );

	$indexes = __index($hostname, $snmp_community, $snmp_version, $snmp_auth_username, $snmp_auth_password, $snmp_auth_protocol, $snmp_priv_passphrase, $snmp_priv_protocol, $snmp_context, $snmp_port, $snmp_timeout, $ping_retries, $max_oids);
	
	$result = array();
	switch($cmd) {
		case 'cosid':
			foreach($indexes as $index => $ifindex) { $result[$index] = $index; }
			break;
		case 'ifName':
			foreach($indexes as $index => $ifindex) {
				$result[$index] = cacti_snmp_get($hostname, $snmp_community, $oid[$cmd].".$ifindex", $snmp_version, $snmp_auth_username, $snmp_auth_password, $snmp_auth_protocol, $snmp_priv_passphrase, $snmp_priv_protocol, $snmp_context, $snmp_port, $snmp_timeout, $ping_retries, $max_oids, SNMP_POLLER);
			}
			break;
		case 'cbQosPolicyDirection':
			foreach($indexes as $index => $ifindex) {
				$result[$index] = $mib_direction[cacti_snmp_get($hostname, $snmp_community, $oid[$cmd].".$index", $snmp_version, $snmp_auth_username, $snmp_auth_password, $snmp_auth_protocol, $snmp_priv_passphrase, $snmp_priv_protocol, $snmp_context, $snmp_port, $snmp_timeout, $ping_retries, $max_oids, SNMP_POLLER)];
			}
			break;
		case 'ifSpeed':
			foreach($indexes as $index => $ifindex) {
				$result[$index] = __retrieve_ifspeed($hostname, $snmp_community, $snmp_version, $snmp_auth_username, $snmp_auth_password, $snmp_auth_protocol, $snmp_priv_passphrase, $snmp_priv_protocol, $snmp_context, $snmp_port, $snmp_timeout, $ping_retries, $max_oids, $index, $ifindex);
			}
			break;
		case 'cosname':
			foreach($indexes as $index => $ifindex) {
				$tmp_csConfigIndex = cacti_snmp_get($hostname, $snmp_community, $oid["cbQosConfigIndex"].".$index.$index", $snmp_version, $snmp_auth_username, $snmp_auth_password, $snmp_auth_protocol, $snmp_priv_passphrase, $snmp_priv_protocol, $snmp_context, $snmp_port, $snmp_timeout, $ping_retries, $max_oids, SNMP_POLLER);
				$tmp_csConfigIndex = ($tmp_csConfigIndex)?:cacti_snmp_get($hostname, $snmp_community, $oid["cbQosConfigIndex"].".$index.1", $snmp_version, $snmp_auth_username, $snmp_auth_password, $snmp_auth_protocol, $snmp_priv_passphrase, $snmp_priv_protocol, $snmp_context, $snmp_port, $snmp_timeout, $ping_retries, $max_oids, SNMP_POLLER);
				$result[$index] = cacti_snmp_get($hostname, $snmp_community, $oid["cbQosPolicyMapName"].".$tmp_csConfigIndex", $snmp_version, $snmp_auth_username, $snmp_auth_password, $snmp_auth_protocol, $snmp_priv_passphrase, $snmp_priv_protocol, $snmp_context, $snmp_port, $snmp_timeout, $ping_retries, $max_oids, SNMP_POLLER);
			}
			break;
		default:
			return false;
	}
	return $result;
}

function __get_value($hostname, $snmp_community, $snmp_version, $snmp_auth_username, $snmp_auth_password, $snmp_auth_protocol, $snmp_priv_passphrase, $snmp_priv_protocol, $snmp_context, $snmp_port, $snmp_timeout, $ping_retries, $max_oids, $cos_index, $class_name) {
	global $oid;
	$get_oid = $oid["cbQosCMPrePolicyByte64"];
	if (preg_match("/(.*)_(drop|packet|packet-drop)$/",$class_name,$tmp)) { 
		$drop_class = preg_match("/drop$/",$tmp[2]);
		if ($tmp[2] == "drop") { $get_oid = $oid["cbQosCMDropByte64"]; }
		elseif($tmp[2] == "packet") { $get_oid = $oid["cbQosCMPrePolicyPkt64"]; }
		elseif($tmp[2] == "packet-drop") { $get_oid = $oid["cbQosCMDropPkt64"]; }
		$class_name = $tmp[1];
	}
	
	$enable_cache = __create_db();
	$result = array();
	$class_ids = false;
	if ($enable_cache && $class_ids = __query_db($hostname,$cos_index, $class_name)) {
		foreach($class_ids as $class_id) {
			if (!strlen($cos_index) || !strlen($class_id)) { continue; }
			$tmp_value = cacti_snmp_get($hostname, $snmp_community, "$get_oid.$cos_index.$class_id", $snmp_version, $snmp_auth_username, $snmp_auth_password, $snmp_auth_protocol, $snmp_priv_passphrase, $snmp_priv_protocol, $snmp_context, $snmp_port, $snmp_timeout, $ping_retries, $max_oids, SNMP_POLLER);
			if (is_numeric($tmp_value)) { array_push($result,$tmp_value); }
		}
	}
	if ((!$class_ids || count($result) != count($class_ids)) && $class_ids = __get_class_index($hostname, $snmp_community, $snmp_version, $snmp_auth_username, $snmp_auth_password, $snmp_auth_protocol, $snmp_priv_passphrase, $snmp_priv_protocol, $snmp_context, $snmp_port, $snmp_timeout, $ping_retries, $max_oids, $cos_index, $class_name)) {
		foreach($class_ids as $class_id) {
			if (!strlen($cos_index) || !strlen($class_id)) { continue; }
			$tmp_value = cacti_snmp_get($hostname, $snmp_community, "$get_oid.$cos_index.$class_id", $snmp_version, $snmp_auth_username, $snmp_auth_password, $snmp_auth_protocol, $snmp_priv_passphrase, $snmp_priv_protocol, $snmp_context, $snmp_port, $snmp_timeout, $ping_retries, $max_oids, SNMP_POLLER);
			if (is_numeric($tmp_value)) { array_push($result,$tmp_value); }
		}
		if (!$class_ids || count($result) != count($class_ids)) { return false; }
		if ($enable_cache) { __update_db($hostname,$cos_index,$class_name,$class_ids); }
	}

	return $result;
}

function __get_class_index($hostname, $snmp_community, $snmp_version, $snmp_auth_username, $snmp_auth_password, $snmp_auth_protocol, $snmp_priv_passphrase, $snmp_priv_protocol, $snmp_context, $snmp_port, $snmp_timeout, $ping_retries, $max_oids, $cos_index, $class_name) {
	global $oid;

	$not_ignore_parent = preg_match("/^class-default$/",$class_name);
	
	$tmp_csCMName = __map_cacti_walk($hostname, $snmp_community, $oid["cbQosCMName"], $snmp_version, $snmp_auth_username, $snmp_auth_password, $snmp_auth_protocol, $snmp_priv_passphrase, $snmp_priv_protocol, $snmp_context, $snmp_port, $snmp_timeout, $ping_retries, $max_oids, SNMP_POLLER);
	$tmp_class_id = false;
	asort($tmp_csCMName);
	foreach($tmp_csCMName as $index => $value) {
		if (preg_match("/^$class_name$/i",$value)) { $tmp_class_id = $index; break; }
	}
	
	if (!$tmp_class_id) { return false; }

	$tmp_cbQosConfigIndex = __map_cacti_walk($hostname, $snmp_community, $oid["cbQosConfigIndex"].".$cos_index", $snmp_version, $snmp_auth_username, $snmp_auth_password, $snmp_auth_protocol, $snmp_priv_passphrase, $snmp_priv_protocol, $snmp_context, $snmp_port, $snmp_timeout, $ping_retries, $max_oids, SNMP_POLLER);
	$tmm_cbQosParentObjectsIndex = __map_cacti_walk($hostname, $snmp_community, $oid["cbQosParentObjectsIndex"].".$cos_index", $snmp_version, $snmp_auth_username, $snmp_auth_password, $snmp_auth_protocol, $snmp_priv_passphrase, $snmp_priv_protocol, $snmp_context, $snmp_port, $snmp_timeout, $ping_retries, $max_oids, SNMP_POLLER);
	$tmp_class_indexes = array();
	foreach($tmp_cbQosConfigIndex as $index => $value) {
		if ($value == $tmp_class_id && !($tmm_cbQosParentObjectsIndex["$index"] == $cos_index && $not_ignore_parent)) { array_push($tmp_class_indexes,$index); }
	}

	return $tmp_class_indexes; 
}

function __retrieve_ifspeed($hostname, $snmp_community, $snmp_version, $snmp_auth_username, $snmp_auth_password, $snmp_auth_protocol, $snmp_priv_passphrase, $snmp_priv_protocol, $snmp_context, $snmp_port, $snmp_timeout, $ping_retries, $max_oids, $cos_index, $if_index) {
	global $oid;
	$tmp_cbObjectType = __map_cacti_walk($hostname, $snmp_community, $oid["cbQosObjectsType"].".$cos_index", $snmp_version, $snmp_auth_username, $snmp_auth_password, $snmp_auth_protocol, $snmp_priv_passphrase, $snmp_priv_protocol, $snmp_context, $snmp_port, $snmp_timeout, $ping_retries, $max_oids, SNMP_POLLER);
	$tmp_classType = array_search("6",$tmp_cbObjectType) ?: array_search("trafficShaping(6)",$tmp_cbObjectType);
	if (!$tmp_classType) {
		$speed = cacti_snmp_get($hostname, $snmp_community, $oid["ifHighSpeed"].".$if_index",  $snmp_version, $snmp_auth_username, $snmp_auth_password, $snmp_auth_protocol, $snmp_priv_passphrase, $snmp_priv_protocol, $snmp_context, $snmp_port, $snmp_timeout, $ping_retries, $max_oids, SNMP_POLLER);
		if ($speed && $speed > 20) {
			$result = $speed * 1000000;
		} else {
			$result = cacti_snmp_get($hostname, $snmp_community, $oid["ifSpeed"].".$if_index",  $snmp_version, $snmp_auth_username, $snmp_auth_password, $snmp_auth_protocol, $snmp_priv_passphrase, $snmp_priv_protocol, $snmp_context, $snmp_port, $snmp_timeout, $ping_retries, $max_oids, SNMP_POLLER);
		}
	} else {
		$tmp_shapeClass = cacti_snmp_get($hostname, $snmp_community, $oid["cbQosConfigIndex"].".$cos_index.$tmp_classType", $snmp_version, $snmp_auth_username, $snmp_auth_password, $snmp_auth_protocol, $snmp_priv_passphrase, $snmp_priv_protocol, $snmp_context, $snmp_port, $snmp_timeout, $ping_retries, $max_oids, SNMP_POLLER);
		$result = cacti_snmp_get($hostname, $snmp_community, $oid["cbQosTSCfgRate"].".$tmp_shapeClass", $snmp_version, $snmp_auth_username, $snmp_auth_password, $snmp_auth_protocol, $snmp_priv_passphrase, $snmp_priv_protocol, $snmp_context, $snmp_port, $snmp_timeout, $ping_retries, $max_oids, SNMP_POLLER);
	}
	return $result;
}

function __map_cacti_walk($hostname, $snmp_community, $oid, $snmp_version, $snmp_auth_username, $snmp_auth_password, $snmp_auth_protocol, $snmp_priv_passphrase, $snmp_priv_protocol, $snmp_context, $snmp_port, $snmp_timeout, $ping_retries, $max_oids) {
	$tmp = cacti_snmp_walk($hostname, $snmp_community, $oid, $snmp_version, $snmp_auth_username, $snmp_auth_password, $snmp_auth_protocol, $snmp_priv_passphrase, $snmp_priv_protocol, $snmp_context, $snmp_port, $snmp_timeout, $ping_retries, $max_oids, SNMP_POLLER);
	$result = array();
	foreach($tmp as $index => $value) {
		$result[preg_filter("/^".preg_quote($oid)."\.(.*)/","$1",$value['oid'])] = $value['value'];
	}
	return $result;
}

function __create_db() {
	$sql_create_table = <<<EOT
CREATE TABLE IF NOT EXISTS `cisco_cos` (
	`index` INT unsigned NOT NULL AUTO_INCREMENT,
	`hostname` VARCHAR(250) NOT NULL,
	`cos_id` INT unsigned NOT NULL,
	`class_name` VARCHAR(50) NOT NULL,
	`class_ids` VARCHAR(250) NOT NULL,
	PRIMARY KEY (`index`),
	UNIQUE KEY `uk_cisco_cos` (`hostname`,`cos_id`,`class_name`)
) ENGINE=MEMORY DEFAULT CHARSET=utf8
EOT;

	if (!db_execute($sql_create_table)) {
		fwrite(STDERR,"ERROR: Unable to create the table for cache in database");
		return FALSE;
	}

	return TRUE;
}

function __query_db($hostname, $index, $class_name) {
	$sql_select = <<<EOT
SELECT class_ids FROM cisco_cos 
WHERE hostname = '$hostname' AND cos_id = '$index' AND class_name = '$class_name'
EOT;

	$result = db_fetch_cell($sql_select);
	if (!$result) { return FALSE; }
	return explode(':',$result);
}

function __update_db($hostname, $index, $class_name, $class_ids) {
	$sql_update = <<<'EOT'
INSERT INTO cisco_cos (hostname,cos_id,class_name,class_ids) VALUES ('%1$s','%2$s','%3$s','%4$s') ON DUPLICATE KEY UPDATE class_ids = '%4$s'
EOT;

	if (!db_execute($sql_update)) {
		fwrite(STDERR,"ERROR: Unable to update cache table");
		return FALSE;
	}

	return TRUE;
}

/* vim: set tabstop=4 noexpandtab: */

?>
