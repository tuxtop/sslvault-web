<?php

/**
 * SSL Vault: Certificates in Catalog
 */


# Start of card
print <<<DIV
<div class="card">
 <h2 class="title">CSRs linked to the certificates #${cid}</h2>
 <p><a href="/index.php/${path[0]}/">&laquo; Back to certificates list</a></p>
 <p>
  On this page you will find all CSRs referenced for that certificate.<br />
  These CSRs can be reused for any later orders.
 </p>
 <p>
  <div class="input-group input-group-line">
   <a class="btn btn-default" href="/index.php/${path[0]}/${cid}/csr/0/edit"><span class="fa fa-plus"></span> Create new CSR</a>
   <a class="btn btn-default" href="/index.php/${path[0]}/${cid}/csr/0/edit?keypair_catalog">with a referenced keypair</a>
  </div>
  <div class="input-group input-group-line">
   <a class="btn btn-default" href="/index.php/${path[0]}/${cid}/orders">Orders</a>
  </div>
 </p>
DIV;


# Manage CSRs
$post = new PostData();
if ($post->delete)
{
	if ($dbh->delete('certificates_csr', "csid=$post->delete"))
	{
		print <<<MSG
		<div class="alert alert-success">
		 The CSR #$post->delete is now removed.
		</div>
MSG;
	}
	else
	{
		$dbh->log_error();
		print <<<MSG
		<div class="alert alert-danger">
		 Failed to remove CSR #$post->delete.<br />
		 Please contact your System Administrator.
		</div>
MSG;
	}
}


# Load certificates
$request = "SELECT csid,csr,key_length,key_type,private_key,public_key,creation FROM certificates_csr WHERE cid='${cid}' ORDER BY creation DESC;";
if ($query = $dbh->query($request))
{
	print <<<TABLE
	<table class="default" id="cert-catalog">
	 <thead>
	  <tr>
	   <th>#</th>
	   <th>CSR infos</th>
	   <th>Creation</th>
	   <th>&nbsp;</th>
	  </tr>
	 </thead>
	 <tbody>
TABLE;
	if ($query->rowCount())
	{
		while (list($csid, $csr, $kln, $ktype, $pubKey, $privKey, $creation) = $query->fetch())
		{
			$jsdata = array(
				'csid' => $csid,
				'csr' => str_replace("\n", "\\n", $csr),
				'pubKey' => str_replace("\n", "\\n", $pubKey),
				'privKey' => str_replace("\n", "\\n", $privKey),
			);
			$jsdata = str_replace('"', '&quot;', json_encode($jsdata));
			$creation = new DateTime($creation);
			$creation = $creation->format($conf['date_format']);
			$infos = openssl_csr_get_subject($csr);
			$str = '';
			foreach ($infos as $a=>$b)
			{
				$str.= "/$a=$b";
			}
			print <<<ROW
			<tr>
			 <td>${csid}</td>
			 <td>${str}</td>
			 <td>${creation}</td>
			 <td class="button"><span data-role="dropdown" data-infos="${jsdata}" data-template="dropdown-cert" class="btn btn-default btn-sm">Actions <span class="carret">&#9660;</span></span></td>
			</tr>
ROW;
		}
	}
	else
	{
		print <<<ROW
		<tr>
		 <td colspan="4" class="text-center"><em>No CSR in catalog for that certificate...</em></td>
		</tr>
ROW;
	}
	$query->closeCursor();
	print <<<TABLE
	 </tbody>
	</table>
TABLE;
}


# End of card
print <<<DIV
</div>

<div class="dropdown-template" id="dropdown-cert">
 <ul>
  <li><a href="javascript:clip_modal('{:csr}');">View CSR</a></li>
  <li><a href="javascript:clip_modal('{:privKey}');">View private key</a></li>
  <li><a href="javascript:clip_modal('{:pubKey}');">View public key</a></li>
  <li class="separator"></li>
  <li><a href="javascript:delete_csr({:csid},0);">Delete CSR</a></li>
 </ul>
</div>

<script type="text/javascript">

	function delete_csr(csid, step)
	{
		if (step === undefined) step = '0';
		var cnt = '';
		switch (step)
		{
			case 0:
				cnt = '<p>This action will remove ALL data linked to that CSR, including orders and certificates signed by your CA.</p>';
				cnt+= '<p>This action cannot be canceled.</p>';
				cnt+= '<p>Are you sure you want to delete this CSR?</p>';
				break
			case 1:
				cnt+= '<p>Really sure?</p>';
				break;
			case 2:
				$.AutoPostForm({ 'delete': csid });
				break;
				
		}
		cnt+= '<p class="text-center"><a class="btn btn-success" href="javascript:delete_csr('+csid+','+(step+1)+');">Yes, I do</a> <span data-role="close" class="btn btn-danger">Cancel</span></p>';
		$.modal({
			content: cnt
		});

	}

</script>
DIV;

?>
