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


# Certificates filter
print <<<FILTERS
<p id="filters">
 Predefined filters:
 <span class="btn btn-disabled btn-condensed" data-filter="valid">Valid certificates</span>
 <span class="btn btn-disabled btn-condensed" data-filter="impeding_expiry">Impeding expiry</span>
 <span class="btn btn-disabled btn-condensed" data-filter="pending_orders">Pending orders</span>
 <span class="btn btn-disabled btn-condensed" data-filter="expired">Expired certificates</span>
 <span class="btn btn-disabled btn-condensed" data-filter="hidden">Hidden certificates</span>
</p>
FILTERS;


# Load certificates
if ($query = $dbh->query("SELECT cid,name,hidden,creation FROM certificates_catalog ORDER BY name;"))
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
		while (list($cid, $name, $hidden) = $query->fetch())
		{
			$c = new SSLCert($cid);
			$status = "<span class=\"label label-".$c->status['label']."\">".$c->status['text']."</span>";
			$jsdata = array(
				'cid' => $cid,
				'change2hidden' => $hidden ? 'style="display:none;"' : '',
				'change2visible' => $hidden ? '' : 'style="display:none;"',
			);
			$jsdata = str_replace('"', '&quot;', json_encode($jsdata));
			$cl = array();
			if ($hidden) $cl[]= 'hidden';
			$cl = implode(' ', $cl);
			print <<<ROW
			<tr class="${cl}">
			 <td>${name} <span class="text-muted">($c->cn)</span></td>
			 <td>$c->expiration</td>
			 <td>${status}</td>
			 <td class="button"><span data-role="dropdown" data-infos="${jsdata}" data-template="dropdown-cert" class="btn btn-default btn-condensed">Actions <span class="carret">&#9660;</span></span></td>
			</tr>
ROW;
		}
	}
	else
	{
		print <<<ROW
		<tr>
		 <td colspan="3" class="text-center"><em>No certificate in catalog...</em></td>
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
  <li {:change2visible}><a href="javascript:show_certificate({:cid});">Set visible certificate</a></li>
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

	var curr = null;
	$('#filters .btn').on('click',function(){
		var item = $(this).data('filter');
		if (!curr || curr!=item)
		{
			if (curr) $('#filters .btn[data-filter="'+curr+'"]').addClass('btn-disabled').removeClass('btn-primary');
			$('#filters .btn[data-filter="'+item+'"]').addClass('btn-primary').removeClass('btn-disabled');
			curr = item;
		}
		else if (curr)
		{
			$('#filters .btn[data-filter="'+curr+'"]').addClass('btn-disabled').removeClass('btn-primary');
			curr = null;
		}
		switch (curr)
		{
			case 'hidden':
				$('#cert-catalog tbody tr').hide();
				$('#cert-catalog tbody tr.hidden').show();
				break;
			default:
				$('#cert-catalog tbody tr').show();
				$('#cert-catalog tbody tr.hidden').hide();
				break;
		}
	});

</script>
DIV;

?>
