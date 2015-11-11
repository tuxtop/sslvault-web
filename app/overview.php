<?php

/**
 * SSL Vault: Overview
 * Display status of Ceritificates
 */


# 
print '<h1 class="title">Overview</h1>';


# Load status
if ($query = $dbh->query("SELECT cid FROM certificates_catalog WHERE hidden=false;"))
{
	$status = array(
		'total' => array( 'code' => ':total', 'text' => 'Certificates in Catalog', 'label' => 'default', 'count' => 0 ),
		'orders' => array( 'text' => 'Orders processing', 'count' => 0 )
	);
	while (list($cid) = $query->fetch())
	{
		$c = new SSLCert($cid);
		$tag = $c->status['code'];
		if ($tag=='hidden') continue;
		if (!isset($status[$tag]))
		{
			$c->status['count'] = 0;
			$status[$tag] = $c->status;
		}
		$status[$tag]['count']++;
		$status['total']['count']++;
		if ($c->order_processing) $status['orders']['count']++;
	}
	print <<<DIV
	<div class="columns center">
DIV;
	foreach ($status as $i)
	{
		if (!$i['count']) continue;
		print <<<CARD
		<div class="card text-center">
		 <h1>${i['count']}</h1>
		 <p>${i['text']}</p>
		</div>
CARD;
	}
	print <<<DIV
	</div>
DIV;
}
else
{
	error_log("Failed to get certificates status: ".$dbh->errorInfo()[2]);
	print <<<MSG
	<div class="alert alert-warning">
	 Failed to load dashboard.<br />
	 Please contact your System Administrator.
	</div>
MSG;
}

?>
