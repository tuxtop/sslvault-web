<?php

/**
 * SSL Vault: SSL Keys page
 * Display all SSL Keys in catalog
 */


# Name aliases for keys
$keys_aliases = array(
	':selfsigned' => 'SSL Key for self-signed certificate (required for CSR)'
);


# Print page title
print '<h1 class="title">SSL Keys</h1>';


# Manage subpage
if (!isset($path[1])) $path[1] = 'list';
$file = $_SERVER['DOCUMENT_ROOT']."/app/${path[0]}/${path[1]}.php";
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
