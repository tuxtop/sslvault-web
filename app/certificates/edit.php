<?php

/**
 * My SSL Vault: Create/edit certificate information
 */


# Certificate to edit
$c = new stdClass();
$title = 'New certificate';
$submit = 'Create certificate';
$help = '';
if ($cid!==null)
{
	$c = new SSLCert($cid);
}


# Save certificate
$npath = '/index.php/'.implode('/', $path);
if ($form = new PostData() and $form->_post)
{

	# Get all informations
	$f = array( 'author_uid' => $_SESSION['auth']['uid'] );
	foreach ($form->all_fields() as $key)
	{
		if ($key=='csr') continue;
		$f[$key] = $form->$key;
	}
	if (!isset($k['name']) or !$k['name']) $k['name'] = $k['cn'];

	# Prepare request
	if ($c->name)
	{
		$res = $dbh->update('certificates_catalog', $f, "cid=${cid}");
		$actType = 'update';
	}
	else
	{
		$res = $dbh->insert('certificates_catalog', $f, 'cid');
		$actType = 'create';
	}

	# 
	if ($res)
	{
		if ($actType=='create')
		{
			$cid = $res;
			$c = new SSLCert($res);
		}
		$c = new SSLCert($cid);
		print <<<MSG
		<div class="alert alert-success">
		 Certificate successfully ${actType}d!
		</div>
MSG;
		if ($form->csr)
		{
			print <<<TMP
			<script type="text/javascript">setTimeout(function(){ document.location.href = '/index.php/${path[0]}/${cid}/csr/0/edit'; }, 3000);</script>
TMP;
		}
	}
	else
	{
		$dbh->log_error();
		print <<<MSG
		<div class="alert alert-danger">
		 Failed to ${actType} the certificate!<br />
		 Please contact your System Administrator.
		</div>
MSG;
	}

}


# 
if ($c->name)
{
	$title = 'Edit certificate #'.$cid;
	$submit = 'Save modifications';
	$help = '<p><strong>WARNING:</strong> On editing this certificate, you will change for future emited certificates, the current and past certificates emited will still be the same.</p>';
}


# List of fields
$fields = array(
	'name' => array(
		'label' => 'Catalog Name',
		'field' => '&nbsp;',
		'help' => 'Name of this certificate in the Catalog (list); if blank, it will take the Common Name'
	),
	'cn' => array(
		'label' => 'Common Name',
		'field' => 'CN=',
		'required' => true,
		'help' => 'FQDN you want to secure (e.g.: *.mydomain.com)'
	),
	'o' => array(
		'label' => 'Business name (or Organization)',
		'field' => 'O=',
		'required' => true,
		'help' => 'Usually the legal incorporated name of a company and should include any suffixes such as Ltd., Inc., or Corp.'
	),
	'ou' => array(
		'label' => 'Department name (or Organizational Unit)',
		'field' => 'OU=',
		'required' => true,
		'help' => 'e.g.: HR, Finance, IT, ...'
	),
	'l' => array(
		'label' => 'Location',
		'field' => 'L=',
		'required' => true,
		'help' => 'e.g.: London, Lille, New York, ...'
	),
	's' => array(
		'label' => 'State',
		'field' => 'S=',
		'required' => true,
		'help' => 'e.g.: New Jersey, Nord, Sussex, New Yorkshire, ...'
	),
	'c' => array(
		'label' => 'Country',
		'field' => 'C=',
		'required' => true,
		'help' => 'Two-letter country code, such as FR, DE, US',
		'maxlength' => 2
	),
);


# Start of card
$npath = "/index.php/${path[0]}/${cid}/edit";
print <<<CARD
<div class="card">
 <h2 class="title">${title}</h2>
 <p><a href="/index.php/${path[0]}">&laquo; Back to the list</a></p>
 ${help}
 <form method="post" action="${npath}">
CARD;


# Print form
foreach ($fields as $field=>$finfos)
{
	$xtra = '';
	if (isset($finfos['maxlength'])) $xtra.= "maxlength=\"${finfos['maxlength']}\" ";
	if (isset($finfos['required'])) $xtra.= "required=\"required\" ";
	$value = $c->$field;
	print <<<FIELD
	<div class="input-group">
	 <div class="label">${finfos['label']}</div>
	 <input type="text" name="${field}" value="${value}" class="form-control" ${xtra} />
	</div>
	<p>${finfos['help']}</p>
FIELD;
}


# End of card
print <<<CARD

  <div class="input-group">
   <div class="label">Tags</div>
   <input type="text" name="tags" value="$c->tags" class="form-control" data-role="tags" data-autocomplete="/ajax/tags.php" />
  </div>

  <p class="checkbox-line"><input type="checkbox" name="csr" id="csr" /> <label for="csr">Create a new Certificate Signing Request (CSR)</label></p>
  <p class="text-center"><input type="submit" value="${submit}" class="btn btn-success" /></p>
 </form>
</div>
CARD;

?>
