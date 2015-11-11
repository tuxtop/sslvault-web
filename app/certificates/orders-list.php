<?php

/**
 * SSL Vault: list orders for one certificate
 */


# Get certificate
$cid = intval($path[2]);


# 
$ssl = new SSLCert($cid);
print <<<CARD
<div class="card">
 <h2 class="title">Orders for certificate &quot;<em>$ssl->name</em>&quot;</h2>
 <p><a href="/index.php/${path[0]}">&laquo; Back to the list</a></p>
 <p><a href="/index.php/${path[0]}/${path[1]}/${cid}/edit/0" class="btn btn-default">New Certificate Signing Request (new order)</a></p>
CARD;


# Load all order made for the certificate
$request = <<<REQ
SELECT o.oid,u.username,o.creation,o.status,o.csr,o.certificate
FROM certificates_orders AS o
LEFT JOIN users AS u
	ON o.author_uid = u.uid
WHERE o.cid = $cid
ORDER BY o.creation DESC
REQ;
if ($query = $dbh->query($request))
{
	if ($query->rowCount())
	{
		$first = true;
		while (list($oid, $author, $creation, $status, $csr, $certificate) = $query->fetch())
		{
			$c = new DateTime($creation);
			$creation = $c->format($conf['date_format']);
			$status = $ssl->_s[$status];
			if ($status['code']=='csr_canceled') $first = null;
			$jsdata = array(
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
			print <<<ORDER
			<div class="order${fcl}">
			 <div class="input-group group-right">
			  <a href="/index.php/${path[0]}/${path[1]}/${cid}/edit/${oid}" class="btn btn-default">Edit</a>
			  <span data-role="dropdown" data-template="dropdown-order" data-infos="${jsdata}" class="btn btn-default">Quick actions <span class="caret">&nbsp;</span></span>
			 </div>
			 <h3>Order #${oid} <span class="label label-${status['label']} text-small">${status['text']}</span></h3>
			 <p>Created the ${creation}, by ${author}</p>
			 <p>${a}</p>
			</div>
ORDER;
			$first = $first===null ? true : false;
		}
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


function copy_to_clipboard(e)
{
	var container = $(e).parents('.wmodal-content').find('pre');
	var selection = window.getSelection();
        var range = document.createRange();
        range.selectNodeContents(container[0]);
        selection.removeAllRanges();
        selection.addRange(range);
	document.execCommand('copy');
	alert('Data copied to your clipboard.');
}

</script>
CARD;

?>
