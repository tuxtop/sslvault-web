<?php

/**
 * SSL Vault: Keypairs list
 * Display list about Keypairs
 */


# Load list 
print <<<DIV
<div class="card">
 <h2 class="title">Keypairs in your Catalog</h2>
 <p>
  On this page you will find all keypairs referenced.<br />
  You can, for example, use it to store your keys used to sign or crypt documents.<br />
  Keypairs used to sign certificates are not stored here, but you can store here keypairs to sign later certificates.
 </p>
 <p><a class="btn btn-default" href="/index.php/${path[0]}/new">Create/add keypairs</a></p>
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
		error_log("Failed to delete Keypairs pair $form->delete: ".$dbh->errorInfo()[2]);
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
			<tr onclick="javascript:document.location.href='/index.php/${path[0]}/${kid}/edit';">
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
		<p>Your Keypairs Catalog is empty.</p>
MSG;
	}
}
else
{
	error_log("Failed to load Keypairs Catalog: ".$dbh->errorInfo()[2]);
	print <<<MSG
	<h1>Oooops! Could not load catalog!</h1>
	<p>Failed to load data from Keypairs Catalog! Please contact your system administrator.</p>
MSG;
}
print '</div>';


?>
