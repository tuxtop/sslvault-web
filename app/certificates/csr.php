<?php

/**
 * SSL Vault: Manage certificates CSRs
 */


# Get certificate to manage
$action = isset($path[4]) ? $path[4] : 'list';
if ($cid===null) $action = ':unknown';


# Get CSR to manage
$csr = isset($path[3]) ? $path[3] : null;


# Prepare path to file
$file = $_SERVER['DOCUMENT_ROOT']."/app/${path[0]}/csr-${action}.php";
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
