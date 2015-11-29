<?php

/**
 * SSL Vault: Tags search
 */


# Define AJAX Mode
define('AJAX_MODE',true);
require_once($_SERVER['DOCUMENT_ROOT'].'/index.php');


# Load all known tags
$all = array();
$request = "SELECT tags FROM certificates_catalog WHERE tags IS NOT NULL;";
$sth = $dbh->prepare($request);
$sth->execute();
while (list($tags) = $sth->fetch())
{
	$tags = explode(',', $tags);
	foreach ($tags as $tag)
	{
		if ($tag and !in_array($tag, $all)) $all[]= $tag;
	}
}
$sth->closeCursor();


# Search for tag
$result = array();
$form = new PostData();
$search = strtoupper($form->search);
foreach ($all as $tag)
{

	# 
	$tu = preg_replace('/&(\w)\w+;/', '$1', strtoupper($tag));
	$idx = strpos($tu, $search);
	if ($idx===false or in_array($tag, $_POST['filters'])) continue;

	# 
	$match = substr($tag, 0, $idx).'<strong>'.substr($tag, $idx, strlen($search)).'</strong>'.substr($tag, $idx+strlen($search));
	$value = $tag;

	# 
	$result[]= array( 'match'=>$match, 'value'=>$value );

}


# Return result
if (!count($result)) header('Status: 204 Empty', true, 204);
print json_encode($result);


?>
