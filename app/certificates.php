<?php

/**
 * SSL Vault: SSL Certificates page
 * Manage Certificates
 */


# Print page title
print '<h1 class="title">SSL Certificates</h1>';


# Manage subpage
$cid = (isset($path[1]) and preg_match('/^\d+$/', $path[1])) ? intval($path[1]) : null;
if ($path[1]=='import') $path[2] = 'import';
elseif ($cid===null) $path[2] = 'list';
elseif ($cid!==null and !isset($path[2])) $path[2] = 'orders';
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
