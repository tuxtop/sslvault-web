<?php

/**
 * SSL Vault: Create/add new SSL keys
 */


# Open card
print <<<DIV
<div class="card">
 <h2 class="title">New SSL Key</h2>
 <p><a href="/index.php/${path[0]}">&laquo; Back to the list</a></p>
DIV;


# Generate
if ($form = new PostData() and $form->_post)
{

	# Prepare key
	$ins = array();
	$ins['name'] = $form->name;
	if ($form->mode=='add')
	{
		$ins['public'] = $form->public;
		$ins['private'] = $form->private;
	}
	else
	{
		$t = array(
			'rsa' => OPENSSL_KEYTYPE_RSA,
			'dsa' => OPENSSL_KEYTYPE_DSA,
			'dh' => OPENSSL_KEYTYPE_DH,
		);
		$res = openssl_pkey_new(array(
			'private_key_bits' => $form->size,
			'private_key_type' => $t[$form->size],
		));
		$tmp = openssl_pkey_get_details($res);
		$pubKey = $tmp['key'];
		openssl_pkey_export($res, $privKey);
		$ins['public'] = $pubKey;
		$ins['private'] = $privKey;
	}

	# Insert
	foreach($ins as $k=>$v)
	{
		$ins[$k] = $v===null ? "NULL" : "'$v'";
	}
	if ($dbh->exec("INSERT INTO sslkeys_catalog (".implode(",", array_keys($ins)).") VALUES (".implode(",", array_values($ins)).");"))
	{
		print <<<MSG
		<div class="alert alert-success">
		 SSL Keys pair added.
		</div>
MSG;
	}
	else
	{
		print <<<MSG
		<div class="alert alert-danger">
		 Failed to add your SSL Keys pair.<br />
		 Please contact your System Administrator.
		</div>
MSG;
	}

}


# Print form
print <<<FORM
 <form method="post" action="">
  <p>You can choose to <a href="javascript:open_panel('add');">save an existing key</a> or to <a href="javascript:open_panel('generate');">generate a new one</a>.</p>
  <div class="input-group">
   <div class="label">Name of the key:</div>
   <input type="text" name="name" placeholder="e.g.: joe_ssh, crypt_docs, ..." class="default" />
  </div>
  <input type="hidden" name="mode" value="generate" />
  <div id="add" style="display:none;">
   <h3 class="title">Add existing key to the Catalog</h3>
   <p>Private key:</p>
   <p><textarea name="private" class="default" cols="100" rows="15">${private}</textarea></p>
   <p>Public key:</p>
   <p><textarea name="public" class="default" cols="100" rows="5">${public}</textarea></p>
  </div>
  <div id="generate">
   <h3 class="title">Generate an new key</h3>
   <div class="input-group">
    <div class="label">Type of key:</div>
    <select name="type" class="default">
     <option value="rsa">RSA</option>
     <option value="dsa">DSA</option>
     <option value="dh">Diffie-Hellman</option>
    </select>
   </div>
   <div class="input-group">
    <div class="label">Size of key:</div>
    <select name="size" class="default">
     <option value="1024">1024</option>
     <option value="2048" selected="selected">2048</option>
     <option value="4096">4096</option>
    </select>
   </div>
  </div>
  <p class="text-center"><input class="btn btn-success" type="submit" value="Save modifications" />${delete}</p>  
 </form>
FORM;


# Close card
print <<<DIV
</div>

<script type="text/javascript">

	function open_panel(id)
	{
		var alternate = { 'add':'generate', 'generate':'add' };
		$('#'+alternate[id]).hide();
		$('#'+id).show();
		$('input[type=hidden][name=mode]').val(id);
	}

</script>
DIV;


?>
