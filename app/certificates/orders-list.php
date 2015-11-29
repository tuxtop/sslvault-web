<?php

/**
 * SSL Vault: list orders for one certificate
 */


# 
$ssl = new SSLCert($cid);
print <<<CARD
<div class="card">
 <h2 class="title">Orders for certificate &quot;<em>$ssl->name</em>&quot;</h2>
 <p><a href="/index.php/${path[0]}">&laquo; Back to certificates list</a></p>
 <p>
  On this page you will find all orders made to create or renew that certificate.
 </p>
 <p>
  <div class="input-group input-group-line">
   <a href="/index.php/${path[0]}/${cid}/orders/0/edit" class="btn btn-default"><span class="fa fa-plus"></span> New order</a>
  </div>
  <div class="input-group input-group-line">
   <a href="/index.php/${path[0]}/${cid}/csr" class="btn btn-default">CSR</a>
  </div>
 </p>
CARD;


# Load all order made for the certificate
$request = <<<REQ
SELECT o.oid,u.username,o.creation,o.status,c.csr,o.certificate,o.provider_name,o.provider_oid
FROM
	certificates_orders AS o,
	certificates_csr AS c,
	users AS u
WHERE o.cid = $cid
	AND c.csid = o.csid
	AND o.author_uid = u.uid
ORDER BY o.creation DESC
REQ;
if ($query = $dbh->query($request))
{
	if ($query->rowCount())
	{
		print <<<TABLE
		<table class="default">
		 <thead>
		  <tr>
		   <th>#</th>
		   <th colspan="2">Provider infos</th>
		   <th>Status</th>
		   <th>Author</th>
		   <th>Creation</th>
		   <th>&nbsp;</th>
		  </tr>
		 </thead>
		 <tbody>
TABLE;
		$first = true;
		while (list($oid, $author, $creation, $status, $csr, $certificate, $provider, $poid) = $query->fetch())
		{
			$c = new DateTime($creation);
			$creation = $c->format($conf['date_format']);
			$status = $ssl->_s[$status];
			if ($status['code']=='csr_canceled') $first = null;
			$jsdata = array(
				'oid' => $oid,
				'csr' => str_replace("\n", "\\n", $csr),
				'cert2disp' => $certificate ? '' : 'style="display:none;"',
				'certificate' => str_replace("\n", "\\n", $certificate)
			);
			$jsdata = str_replace('"', '&quot;', json_encode($jsdata));
			$infos = openssl_csr_get_subject($csr);
			$a = array( '' );
			foreach ($infos as $item=>$value) { $a[]= "$item=$value"; }
			$a = implode('/', $a);
			$fcl = $first ? ' order-first' : '';
/*			print <<<ORDER
			<div class="order${fcl}">
			 <div class="input-group group-right">
			  <a href="/index.php/${path[0]}/${path[1]}/${cid}/edit/${oid}" class="btn btn-default">Edit</a>
			  <span data-role="dropdown" data-template="dropdown-order" data-infos="${jsdata}" class="btn btn-default">Quick actions <span class="caret">&nbsp;</span></span>
			 </div>
			 <h3>Order #${oid} <span class="label label-${status['label']} text-small">${status['text']}</span></h3>
			 <p>Created the ${creation}, by ${author}</p>
			 <p>${a}</p>
			</div>
ORDER;*/
			print <<<ORDER
			<tr>
			 <td>${oid}</td>
			 <td>${provider}</td>
			 <td>${poid}</td>
			 <td><span class="label label-${status['label']} text-small">${status['text']}</span></td>
			 <td>${author}</td>
			 <td>${creation}</td>
			 <td class="button">
			  <span data-role="dropdown" data-template="dropdown-order" data-infos="${jsdata}" class="btn btn-default btn-sm">Actions <span class="caret"></span></span>
			 </td>
			</tr>
ORDER;
			$first = $first===null ? true : false;
		}
		print <<<TABLE
		 </tbody>
		</table>
TABLE;
	}
	else
	{
		print <<<MSG
		<div class="alert alert-info">
		 No order found for this certificate...
		</div>
MSG;
	}
	$query->closeCursor();
}
else
{
	error_log("Failed to load orders data: ".$dbh->log_error());
	print <<<MSG
	<div class="alert alert-danger">
	 Failed to load orders data!<br />
	 Please contact your System Administrator.
	</div>
MSG;
}


# 
print <<<CARD
</div>

<div class="dropdown-template" id="dropdown-order">
 <ul>
  <li><a href="/index.php/${path[0]}/${cid}/orders/{:oid}/edit">Manage &amp; check order infos</a></li>
  <li class="separator"></li>
  <li><a href="javascript:display_csr('{:csr}');">View CSR</a></li>
  <li {:cert2disp}><a href="javascript:display_certificate('{:certificate}');">View certificate</a></li>
 </ul>
</div>

<script type="text/javascript">

function display_csr(csr)
{
	$.modal({ 'content': $.heredoc(function(){/*TAG
	 <p><pre>{:csr}</pre></p>
	 <p class="text-right">
	  <span class="btn btn-primary" onclick="javascript:copy_to_clipboard(this);">Copy to clipboard</span>
	  <span class="btn btn-default" data-role="close">Close</span>
	 </p>
	TAG*/},{ 'csr':csr }) });
}


function display_certificate(certificate)
{
	$.modal({ 'content': $.heredoc(function(){/*TAG
	 <p><pre>{:certificate}</pre></p>
	 <p class="text-right">
	  <span class="btn btn-primary" onclick="javascript:copy_to_clipboard(this);">Copy to clipboard</span>
	  <span class="btn btn-default" data-role="close">Close</span>
	 </p>
	TAG*/},{ 'certificate':certificate }) });
}

</script>
CARD;

?>
