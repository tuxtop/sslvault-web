<?php

/**
 * SSL Vault: Manage certificates orders
 */


# Get certificate to manage
$cid = isset($path[2]) and preg_match('/^\d+$/', $path[2]) ? intval($path[2]) : null;
$action = isset($path[3]) ? $path[3] : 'list';
if ($cid===null) $action = ':unknown';


# Prepare path to file
$file = $_SERVER['DOCUMENT_ROOT']."/app/${path[0]}/${path[1]}-${action}.php";
if (!file_exists($file)) $action = ':notfound';


# 
switch ($action)
{
	case ':unknown':
		header('Status: 400 Bad Request', true, 400);
		print <<<MSG
		<div class="alert alert-danger">
		 The certificate ID is not a valid one...
		</div>
		<p><a href="/index.php/${path[0]}">&laquo; Back to the list</a></p>
MSG;
		break;
	case ':notfound':
		header('Status: 404 Not Found', true, 404);
		print <<<MSG
		<div class="alert alert-danger">
		 The action you try to do on this certificate is not available...
		</div>
		<p><a href="/index.php/${path[0]}">&laquo; Back to the list</a></p>
MSG;
		break;
	default:
		require_once($file);
}

?>
