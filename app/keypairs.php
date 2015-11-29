<?php

/**
 * SSL Vault: Keypairs page
 * Display all Keypairs in catalog
 */


# Print page title
print '<h1 class="title">Keypairs</h1>';


# Manage subpage
if (!isset($path[2])) $path[2] = 'list';
$kid = (isset($path[1]) and preg_match('/^\d+$/', $path[1])) ? intval($path[1]) : 0;
$file = $_SERVER['DOCUMENT_ROOT']."/app/${path[0]}/${path[2]}.php";
if (!file_exists($file))
{
	header('Status: 404 Not Found', true, 404);
	print <<<MSG
	<h1>Oooops! Section Not Found!</h1>
	<p>The section you are looking for does not exist.</p>
	<p><a href="/index.php/${path[0]}">&laquo; Back to list</a></p>
MSG;
}
else
{
	require_once($file);
}


?>
