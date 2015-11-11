<?php

/**
 * SSL Vault: Certificates in Catalog
 */


# Start of card
print <<<DIV
<div class="card">
 <h2 class="title">SSL Certificates in your Catalog</h2>
 <p>On this page you will find all SLL Certificates referenced.</p>
 <p><a class="btn btn-default" href="/index.php/${path[0]}/edit/0">Create new Certificate</a></p>
DIV;


# Hide certificates
$post = new PostData();
if ($post->show)
{
	if ($dbh->update('certificates_catalog', array( 'hidden' => false ), "cid=$post->show"))
	{
		print <<<MSG
		<div class="alert alert-success">
		 The certificate #$post->show is now visible.
		</div>
MSG;
	}
	else
	{
		$dbh->log_error();
		print <<<MSG
		<div class="alert alert-danger">
		 Failed to set certificate #$post->show to visible.<br />
		 Please contact your System Administrator.
		</div>
MSG;
	}
}
if ($post->hide)
{
	if ($dbh->update('certificates_catalog', array( 'hidden' => true ), "cid=$post->hide"))
	{
		print <<<MSG
		<div class="alert alert-success">
		 The certificate #$post->hide is now hidden.
		</div>
MSG;
	}
	else
	{
		$dbh->log_error();
		print <<<MSG
		<div class="alert alert-danger">
		 Failed to hide certificate #$post->hide.<br />
		 Please contact your System Administrator.
		</div>
MSG;
	}
}
if ($post->delete)
{
	if ($dbh->delete('certificates_catalog', "cid=$post->delete"))
	{
		print <<<MSG
		<div class="alert alert-success">
		 The certificate #$post->delete is now removed.
		</div>
MSG;
	}
	else
	{
		$dbh->log_error();
		print <<<MSG
		<div class="alert alert-danger">
		 Failed to remove certificate #$post->delete.<br />
		 Please contact your System Administrator.
		</div>
MSG;
	}
}


# 
$filter = isset($_GET['filter']) ? htmlentities(strip_tags($_GET['filter'])) : null;


# Certificates filter
$filters = array();
foreach (explode(' ', 'valid impending_expiry pending_orders expired hidden') as $a) $filters[$a] = '';
if ($filter) $filters[$filter] = 'btn-primary';
print <<<FILTERS
<p id="filters">
 Predefined filters:
 <span class="btn btn-default btn-xs ${filters['valid']}" data-filter="valid">Valid certificates</span>
 <span class="btn btn-default btn-xs ${filters['impending_expiry']}" data-filter="impending_expiry">Impeding expiry</span>
 <span class="btn btn-default btn-xs ${filters['pending_orders']}" data-filter="pending_orders">Pending orders</span>
 <span class="btn btn-default btn-xs ${filters['expired']}" data-filter="expired">Expired certificates</span>
 <span class="btn btn-default btn-xs ${filters['hidden']}" data-filter="hidden">Hidden certificates</span>
</p>
FILTERS;


# Prepare certificate list
switch ($filter)
{
	case 'hidden':
		$request = "SELECT cid,name,creation,hidden FROM certificates_catalog WHERE hidden = true ORDER BY name;";
		break;
	default:
		$request = "SELECT cid,name,creation,hidden FROM certificates_catalog WHERE hidden = false ORDER BY name;";
}


# Load certificates
if ($query = $dbh->query($request))
{
	print <<<TABLE
	<table class="default" id="cert-catalog">
	 <thead>
	  <tr>
	   <th>Certificate</th>
	   <th>Expiration</th>
	   <th>Status</th>
	   <th>&nbsp;</th>
	  </tr>
	 </thead>
	 <tbody>
TABLE;
	if ($query->rowCount())
	{
		while (list($cid, $name, $creation, $hidden) = $query->fetch())
		{
			$c = new SSLCert($cid);
			$status = "<span class=\"label label-".$c->status['label']."\">".$c->status['text']."</span>";
			$jsdata = array(
				'cid' => $cid,
				'change2hidden' => $hidden ? 'style="display:none;"' : '',
				'change2visible' => $hidden ? '' : 'style="display:none;"',
			);
			$jsdata = str_replace('"', '&quot;', json_encode($jsdata));
			$expiration = '--';
			if ($c->expiration)
			{
				$expiration = new DateTime($c->expiration);
				$expiration = $expiration->format($conf['date_format']);
			}
			if ($c->order_processing) $status.= ' <span class="label label-primary">Order processing</span>';
			print <<<ROW
			<tr>
			 <td>${name} <span class="text-muted">($c->cn)</span></td>
			 <td>${expiration}</td>
			 <td>${status}</td>
			 <td class="button"><span data-role="dropdown" data-infos="${jsdata}" data-template="dropdown-cert" class="btn btn-default btn-sm">Actions <span class="carret">&#9660;</span></span></td>
			</tr>
ROW;
		}
	}
	else
	{
		$txt = $filter ? ' with this filter' : '';
		print <<<ROW
		<tr>
		 <td colspan="4" class="text-center"><em>No certificate in catalog${txt}...</em></td>
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
  <li><a href="/index.php/${path[0]}/edit/{:cid}">Edit Certificate</a></li>
  <li class="separator"></li>
  <li><a href="/index.php/${path[0]}/orders/{:cid}/edit/0">New CSR</a></li>
  <li><a href="/index.php/${path[0]}/orders/{:cid}">View orders</a></li>
  <li class="separator"></li>
  <li {:change2hidden}><a href="javascript:hide_certificate({:cid});">Hide certificate</a></li>
  <li {:change2visible}><a href="javascript:show_certificate({:cid});">Set certificate as visible</a></li>
  <li><a href="javascript:delete_certificate({:cid});">Delete certificate</a></li>
 </ul>
</div>

<script type="text/javascript">

	function show_certificate(cid)
	{
		$.AutoPostForm({ 'show': cid });
	}

	function hide_certificate(cid)
	{

		// Request confirmation
		if (confirm('Hidding a certificate is useful if you no longer use it but you want to keep a trace in your catalog.\\nThe certificate will be hidden from all lists, alerts and dashboard except with "Hidden certificates" filter.\\nAre you sure you want to hide this certificate?'))
		{
			$.AutoPostForm({ 'hide': cid });
		}

	}

	function delete_certificate(cid)
	{

		// Request confirmation
		if (confirm('This action will remove ALL informations about certificate, including current Certificate emitted.\\nThis action cannot be canceled.\\nAre you sure you want to delete this certificate?'))
		{

			// Request confirmation
			if (confirm('REALLY SURE?'))
			{
				$.AutoPostForm({ 'delete': cid });
			}

		}

	}

	$('#filters .btn').on('click',function(){
		var url = '/index.php/${path[0]}';
		if ($(this).data('filter')!='${filter}') url+= '?filter='+$(this).data('filter');
		document.location.href = url;
	});

</script>
DIV;

?>
