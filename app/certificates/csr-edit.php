<?php

/**
 * SSL Vault: Create new CSR
 */


# Certificate to edit
$c = new SSLCert($cid);


# Save certificate
$npath = '/index.php/'.implode('/', $path);
if ($form = new PostData() and $form->_post)
{

	# Get all informations
	$errstr = null;
	$aliases = array( 'ktype'=>'key_type', 'ksize'=>'key_length' );
	$f = array(
		'author_uid' => $_SESSION['auth']['uid'],
		'cid' => $cid,
	);
	foreach ($form->all_fields() as $key)
	{
		$field = isset($aliases[$key]) ? $aliases[$key] : $key;
		$f[$field] = $form->$key;
	}

	# Generate public/private key
	$t = array(
		'rsa' => OPENSSL_KEYTYPE_RSA,
		'dsa' => OPENSSL_KEYTYPE_DSA,
		'dh' => OPENSSL_KEYTYPE_DH,
	);
	$openssl_config = array(
		'private_key_bits' => $form->ksize,
		'private_key_type' => $t[$form->ktype],
	);
	$res = openssl_pkey_new($openssl_config);
	$tmp = openssl_pkey_get_details($res);
	$pubKey = $tmp['key'];
	openssl_pkey_export($res, $privKey);
	$f['public_key'] = $pubKey;
	$f['private_key'] = $privKey;

	# Generate CSR
	$k = openssl_pkey_get_private($privKey) or $errstr = openssl_error_string();
	$dn = array();
	foreach (explode('/', "emailAddress=${f['email']}".$c->toString()) as $k)
	{
		list($a, $b) = explode('=', $k);
		$dn[$a] = $b;
	}
	$csr = openssl_csr_new($dn, $k, $openssl_config) or $errstr = openssl_error_string();
	openssl_csr_export($csr, $out) or $errstr = openssl_error_string();
	$f['csr'] = $out;

	# Prepare request
	$csid = $errstr ? null : $dbh->insert('certificates_csr', $f, 'csid');
	if ($csid)
	{
		print <<<MSG
		<div class="alert alert-success">
		 CSR successfully created!
		</div>
		<script type="text/javascript">setTimeout(function(){ document.location.href = '/index.php/${path[0]}/${cid}/csr'; }, 3000);</script>
MSG;
		return null;
	}
	else
	{
		error_log("Failed to generate CSR: ".($errstr ? $errstr : $dbh->errorInfo()[2]));
		print <<<MSG
		<div class="alert alert-danger">
		 Failed to create the CSR!<br />
		 Please contact your System Administrator.
		</div>
MSG;
	}

}


# Start of card
$npath = "/index.php/${path[0]}/${cid}/edit";
$locked = '';
$cstr = $c->toString();
print <<<CARD
<div class="card">
 <h2 class="title">Create new CSR</h2>
 <p><a href="/index.php/${path[0]}/${cid}/csr">&laquo; Back to the list</a></p>
 <p>Creating a new certificate will create a new public/private key pair dedicated to that CSR.</p>
 <p>The CSR will be signed with information below:<br /><strong>${cstr}</strong></p>
 <form method="post" action="">
CARD;
if (isset($_GET['keypair_catalog']))
{
	$kc = array();
	if ($query = $dbh->query("SELECT kid,name FROM keypairs_catalog ORDER BY name;"))
	{
		while (list($kid, $name) = $query->fetch())
		{
			$kc[$kid] = $name;
		}
		$query->closeCursor();
	}
	if (!count($kc))
	{
		$locked = 'disabled="disabled"';
		print <<<ALERT
		<div class="alert alert-warning">
		 No keypair found in database!<br />
		 You can <a href="/index.php/keypairs/0/edit">create a new one</a> on the Keypairs catalog or <a href="?">create a CSR with his dedicated keypair</a>.
		</div>
ALERT;
	}
	print <<<CARD
	  <div class="input-group">
	   <div class="label">Keypair to use:</div>
	   <select name="kid" class="form-control" ${locked}>
CARD;
	foreach ($kc as $kid=>$kname)
	{
		print "<option value=\"${kid}\">${kname}</option>";
	}
	print <<<CARD
	   </select>
	  </div>
CARD;
}
else
{
	print <<<CARD
	  <div class="input-group">
	   <div class="label">Key type:</div>
	   <select name="ktype" class="form-control">
	    <option value="rsa">RSA</option>
	    <option value="dsa">DSA</option>
	    <option value="dh">Diffie-Hellman</option>
	   </select>
	  </div>
	  <div class="input-group">
	   <div class="label">Key size:</div>
	   <select name="ksize" class="form-control">
	    <option value="512">512</option>
	    <option value="1024">1024</option>
	    <option value="2048" selected="selected">2048</option>
	    <option value="4096">4096</option>
	   </select>
	  </div>
CARD;
}
print <<<CARD
  <div class="input-group">
   <div class="label">Contact e-mail:</div>
   <input type="email" name="email" placeholder="(not required)" class="form-control" />
  </div>
  <p class="text-center"><input type="submit" value="Create the CSR" class="btn btn-success" ${locked} /></p>
 </form>
</div>
CARD;

?>
