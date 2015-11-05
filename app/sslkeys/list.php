<?php

/**
 * SSL Vault: SSL Keys list
 * Display list about SSL Keys
 */


# Load list 
print <<<DIV
<div class="card">
 <h2 class="title">SSL Keys in your Catalog</h2>
 <p>
  On this page you will find all SSL Keys pairs referenced.<br />
  You can, for example, use it to store your keys used to sign or crypt documents.
 </p>
 <a class="btn btn-primary" href="/index.php/${path[0]}/new">Create/add SSL Key</a>
DIV;
if ($form = new PostData() and $form->_post and preg_match('/^\d+$/', $form->delete))
{
	if ($dbh->exec("DELETE FROM sslkeys_catalog WHERE kid='$form->delete';")>0)
	{
		print <<<MSG
		<div class="alert alert-success">
		 SSL keys pair deleted.
		</div>
MSG;
	}
	else
	{
		error_log("Failed to delete SSL Keys pair $form->delete: ".$dbh->errorInfo()[2]);
		print <<<MSG
		<div class="alert alert-danger">
		 Failed to delete the SSL keys pair.<br />
		 Please contact your System Administrator.
		</div>
MSG;
	}
}
if ($query = $dbh->query("SELECT kid,name,creation FROM sslkeys_catalog ORDER BY name;"))
{
	if ($query->rowCount()>0)
	{
		print <<<TABLE
		<table class="default clickable">
		 <thead>
		  <tr>
		   <th>Ref.</th>
		   <th>Creation</th>
		  </tr>
		 </thead>
		 <tbody>
TABLE;
		while (list($kid, $name, $creation) = $query->fetch())
		{
			if (isset($keys_aliases[$name])) $name = $keys_aliases[$name];
			print <<<ROW
			<tr onclick="javascript:document.location.href='/index.php/${path[0]}/view/${kid}';">
			 <td>$name</td>
			 <td>$creation</td>
			</tr>
ROW;
		}
		print <<<TABLE
		 </tbody>
		</table>
TABLE;
	}
	else
	{
		print <<<MSG
		<p>Your SSL Keys Catalog is empty.</p>
		<p>This can be an issue: without self-signed key, you are not able to create CSR.</p>
MSG;
	}
}
else
{
	error_log("Failed to load SSL Keys Catalog: ".$dbh->errorInfo()[2]);
	print <<<MSG
	<h1>Oooops! Could not load catalog!</h1>
	<p>Failed to load data from SSL Keys Catalog! Please contact your system administrator.</p>
MSG;
}
print '</div>';


?>
