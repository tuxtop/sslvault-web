<?php

/**
 * SSL Vault: Manage new CSR creation
 */


# Get certificate and order to manage
$cid = intval($path[2]);
$oid = intval($path[4]);
$cert = new SSLCert($cid);


# 
$form = new PostData();
if ($form->_post)
{

	# 
	if ($form->csr)
	{

		# Load private key
		list($priv) = $dbh->select('sslkeys_catalog', 'private', "name = ':selfsigned'");
		$k = openssl_pkey_get_private($priv) or die('pkey: '.openssl_error_string());

		# Prepare distinguish name
		$a = array(
			'countryName' => 'c',
			'stateOrProvinceName' => 's',
			'localityName' => 'l',
			'organizationName' => 'o',
			'organizationalUnitName' => 'ou',
			'commonName' => 'cn',
		);
		$dn = array();
		foreach ($a as $name=>$idx)
		{
			$dn[$name] = $cert->$idx;
		}
		if ($form->email) $dn['emailAddress'] = $form->email;

		# Create CSR
		$csr = openssl_csr_new($dn, $k, array(
			'digest_alg' => 'md5',
			'private_key_bits' => 2048,
			'private_key_type' => OPENSSL_KEYTYPE_RSA
		)) or die('csr: '.openssl_error_string());
		openssl_csr_export($csr, $out);

		# Insert new order
		$data = array(
			'cid' => $cid,
			'author_uid' => $_SESSION['auth']['uid'],
			'status' => 'csr_creation',
			'csr' => $out,
			'email' => $form->email,
			'duration' => $form->duration,
			'provider_name' => $form->provider_name
		);
		if ($oid = $dbh->insert('certificates_orders', $data, 'oid'))
		{

			# Insert first history item
			$dbh->insert('certificates_history', array(
				'oid' => $oid,
				'new_status' => 'csr_creation',
				'author_uid' => $_SESSION['auth']['uid'],
				'action' => 'Certificate Signing Request created'
			));

			# Print message
			print <<<MSG
			<div class="alert alert-success">
			 Order successfully created.
			</div>
MSG;

		}
		else
		{
			print <<<MSG
			<div class="alert alert-danger">
			 Failed to save order request in database!<br />
			 Please contact your System Administrator.
			</div>
MSG;
		}
		
	}

}


# Display order steps
if ($oid)
{

	#
	$form = new PostData();
	if ($form->_post)
	{
		if ($status = $form->status)
		{
			list($prevStatus) = $dbh->select('certificates_orders', 'status', "oid = ${oid}");
			$dbh->update('certificates_orders', array( 'status' => $status ), "oid = ${oid}");
			$arr = array(
				'oid' => $oid,
				'previous_status' => $prevStatus,
				'new_status' => $status,
				'author_uid' => $_SESSION['auth']['uid']
			);
			if ($form->comment) $arr['comment'] = str_replace("\n", "\\n", $form->comment);
			$dbh->insert('certificates_history', $arr);
		}
		elseif ($certificate = $form->cert)
		{
			if ($dbh->update('certificates_orders', array( 'certificate' => $certificate ), "oid=${oid}"))
			{
				$dbh->insert('certificates_history', array(
					'oid' => $oid,
					'action' => 'Certificate updated',
					'author_uid' => $_SESSION['auth']['uid']
				)) or error_log("Failed to insert comment: ".$dbh->log_error());
				print <<<MSG
				<div class="alert alert-success">
				 Certificate successfully updated!
				</div>
MSG;
			}
		}
		elseif ($comment = $form->comment)
		{
			if ($ref = $form->ref)
			{
				$uid = $_SESSION['auth']['uid'];
				$dbh->update('certificates_history', array(
					'comment' => str_replace("\n", "\\n", $comment)
				), "oid=${oid} AND author_uid=${uid} AND creation='${ref}'") or error_log("Failed to update comment: ".$dbh->log_error());
			}
			else
			{
				$dbh->insert('certificates_history', array(
					'oid' => $oid,
					'comment' => str_replace("\n", "\\n", $comment),
					'author_uid' => $_SESSION['auth']['uid']
				)) or error_log("Failed to insert comment: ".$dbh->log_error());
			}
		}
	}

	# Load CSR
	list($csr, $certificate, $status, $creation, $uid) = $dbh->select('certificates_orders', 'csr,certificate,status,creation,author_uid', "oid = ${oid}");
	$infos = openssl_csr_get_subject($csr);
	$csr = str_replace("\n", "\\n", $csr);

	# 
	list($username) = $dbh->select('users', 'username', "uid = ${uid}");
	$creation = new DateTime($creation);
	$creation = $creation->format($conf['date_format']);

	# 
	$options = array();
	switch ($status)
	{
		case 'csr_creation':
			$options[]= <<<DATA
			<div class="input-group group-left input-group-line">
			 <a class="btn btn-default btn-sm" href="javascript:action('csr_sent');">CSR sent to CA</a>
			 <a class="btn btn-default btn-sm" href="javascript:action('csr_canceled');">Cancel order</a>
			</div>
DATA;
			break;
		case 'csr_sent':
			$options[]= <<<DATA
			<div class="input-group group-left input-group-line">
			 <a class="btn btn-default btn-sm" href="javascript:action('ca_answer_ok');">Order <strong class="text-success">confirmed</strong> by CA</a>
			 <a class="btn btn-default btn-sm" href="javascript:action('ca_answer_pending');">Action <strong class="text-warning">expected</strong> by CA</a>
			 <a class="btn btn-default btn-sm" href="javascript:action('ca_answer_ko');">Order <strong class="text-danger">refused</strong> by CA</a>
			</div>
			<div class="input-group group-left input-group-line">
			 <a class="btn btn-default btn-sm" href="javascript:action('csr_canceled');">Cancel order</a>
			</div>
DATA;
			break;
		case 'ca_answer_pending':
			$options[]= <<<DATA
			<div class="input-group group-left input-group-line">
			 <a class="btn btn-default btn-sm" href="javascript:action('csr_sent');">Informations sent to CA</a>
			</div>
DATA;
			break;
		case 'csr_canceled':
		case 'ca_answer_ko':
			$options[]= <<<DATA
			<div class="input-group group-left input-group-line">
			 <a class="btn btn-default btn-sm" href="javascript:action('csr_creation');">Reopen order</a>
			</div>
DATA;
			break;
		case 'ca_answer_ok':
			$certificate = str_replace("\n", "\\n", $certificate);
			$options[]= <<<DATA
			<div class="input-group group-left input-group-line">
			 <a class="btn btn-default btn-sm" href="javascript:save_certificate('${certificate}');">Save certificate</a>
			</div>
DATA;
			break;
	}
	$options = implode('', $options);

	# 
	print <<<PAGE
	<div class="card">
	 <h2 class="title">Certificate order #${oid} for certificate &quot;<em>$cert->name</em>&quot;</h2>
	 <p><a href="/index.php/${path[0]}/orders/${cid}">&laquo; Back to the certificate orders</a></p>
	 <table class="table table-condensed">
PAGE;
	foreach ($infos as $item=>$value)
	{
		print <<<ROW
		<tr>
		 <td>${item}</td>
		 <td>${value}</td>
		</tr>
ROW;
	}
	print <<<PAGE
	 </table>
	 <p>
	  ${options}
	  <div class="input-group input-group-line">
	   <a class="btn btn-default btn-sm" href="javascript:comment();">Comment</a>
	   <a class="btn btn-default btn-sm" href="javascript:display_csr('${csr}');">View CSR</a>
	  </div>
	 </p>
PAGE;


	if ($query = $dbh->query("SELECT h.creation,u.uid,u.username,h.comment FROM certificates_history AS h,users AS u WHERE h.author_uid=u.uid AND h.oid='${oid}' AND h.comment IS NOT NULL ORDER BY h.creation DESC;"))
	{
		if ($query->rowCount())
		{
			print '<h3 class="title">Comments</h3>';
			while (list($creation, $uid, $username, $comment) = $query->fetch())
			{
				$opts = '';
				if ($uid==$_SESSION['auth']['uid'])
				{
					$opts = "<br /><a class=\"btn btn-default btn-xs\" href=\"javascript:comment('".addslashes(str_replace('"', '&quot;', $comment))."','${creation}');\">Edit comment</a>";
				}
				$creation = new DateTime($creation);
				$creation = $creation->format($conf['date_format']);
				list($tmp, $in_list) = array('', false);
				foreach (explode("\\n", $comment) as $line)
				{
					if (substr($line, 0, 1)=='-' or substr($line, 0, 1)=='#')
					{
						if (!$in_list)
						{
							$in_list = substr($line, 0, 1)=='#' ? 'ol' : 'ul';
							$tmp.= "<${in_list}>\n";
						}
						$tmp.= "<li>".substr($line, 2)."</li>";
					}
					elseif ($in_list)
					{
						$tmp.= "</${in_list}>";
						$in_list = false;
					}
					else
					{
						$line = preg_replace('/\[([^\|]+)\|([^\]]+)\]/', '<a href="$1" target="_blank">$2</a>', $line);
						$line = preg_replace('/(\s|^|[^">])(https?:\/\/[^\s]+)(\s|[^"<]|$)/', '$1<a href="$2" target="_blank">$2</a>$3', $line);
						$line = preg_replace('/\*([^\*]+)\*/', '<strong>$1</strong>', $line);
						$line = preg_replace('/\'\'([^\/]+)\'\'/', '<em>$1</em>', $line);
						$line = preg_replace('/_([^_]+)_/', '<span style="text-decoration:underline;">$1</span>', $line);
						$tmp.= "$line<br />";
					}
				}
				$comment = $tmp;
				$fpath = "/img/avatars/${uid}.png";
				if (!file_exists($_SERVER['DOCUMENT_ROOT'].$fpath)) $fpath = '/img/common/unknown-128.png';
				print <<<COMMENT
				<blockquote class="comment">
				 <div class="table">
				  <div class="cell userpic">
				   <div class="avatar" style="background-image:url('${fpath}');"></div>
				   <p class="text-center">${username}</p>
				   <p class="text-center text-small text-muted">${creation}${opts}</p>
				  </div>
				  <div class="cell text">${comment}</div>
				 </div>
				</blockquote>
COMMENT;
			}
		}
		$query->closeCursor();
	}

	print <<<PAGE
	 <h3 class="title">History</h3>
	 <table class="table table-bordered table-condensed table-striped table-hover text-small">
	  <thead>
	   <tr>
	    <th>Date</th>
	    <th>User</th>
	    <th>Action</th>
	   </tr>
	  </thead>
	  <tbody>
PAGE;
	$request = <<<REQ
	SELECT h.creation,u.username,h.previous_status,h.new_status,h.action,h.comment
	FROM certificates_history AS h
	LEFT JOIN users AS u
		ON h.author_uid=u.uid
	WHERE
		h.oid=${oid}
	ORDER BY h.creation DESC
REQ;
	if ($query = $dbh->query($request))
	{
		while (list($creation, $uname, $prev, $new, $action, $comment) = $query->fetch())
		{
			$creation = new DateTime($creation);
			$creation = $creation->format($conf['date_format']);
			if (!$action)
			{
				$action = array();
				if ($comment!==null) $action[]= 'New comment';
				if ($prev and $new and $prev!=$new) $action[]= 'Change status from '.$cert->_s[$prev]['text'].' to '.$cert->_s[$new]['text'];
				$action = implode(' + ', $action);
			}
			print <<<ROW
			<tr>
			 <td>${creation}</td>
			 <td>${uname}</td>
			 <td>${action}</td>
			</tr>
ROW;
		}
		$query->closeCursor();
	}
	print <<<PAGE
	  </tbody>
	 </table>

	</div>

	<script type="text/javascript">

	function comment(content, ref)
	{
		if (!content) content = '';
		if (!ref) ref = '';
		$.modal({
			'content': $.heredoc(function(){/*TAG
			<form method="post" action="/index.php/${path[0]}/${path[1]}/${cid}/edit/${oid}">
			 <input type="hidden" name="ref" value="{:ref}" />
			 <p><textarea name="comment" cols="100" rows="15" class="form-control">{:content}</textarea></p>
			 <p class="text-right"><input type="submit" value="Save comment" class="btn btn-success" /> <span class="btn btn-default" data-role="close">Cancel</span></p>
			</form>
			TAG*/},{ 'content': content, 'ref': ref })
		})
	}

	function action(newStatus)
	{
		if (newStatus!='csr_sent')
		{
			$.modal({
				'content': $.heredoc(function(){/*TAG
				<form method="post" action="/index.php/${path[0]}/${path[1]}/${cid}/edit/${oid}">
				 <input type="hidden" name="status" value="{:status}" />
				 <p><textarea name="comment" cols="100" rows="15" class="form-control">{:content}</textarea></p>
				 <p class="text-right"><input type="submit" value="Save comment" class="btn btn-success" /> <span class="btn btn-default" data-role="close">Cancel</span></p>
				</form>
				TAG*/}, { 'status': newStatus })
			});
		}
		else
		{
			$.AutoPostForm({ 'status': newStatus });
		}
	}

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


	function save_certificate(cert)
	{
		if (!cert) cert = '';
		$.modal({
			'content': $.heredoc(function(){/*TAG
			<form method="post" action="/index.php/${path[0]}/${path[1]}/${cid}/edit/${oid}">
			 <p>You can store here the definitive SSL certificate.</p>
			 <p><textarea name="cert" cols="100" rows="15" class="form-control">{:cert}</textarea></p>
			 <p class="text-right"><input type="submit" value="Save certificate" class="btn btn-success" /> <span class="btn btn-default" data-role="close">Cancel</span></p>
			</form>
			TAG*/}, { 'cert': cert })
		});
	}

	</script>
PAGE;

}
else
{

	# 
	print <<<PAGE
	<div class="card">
	 <h2 class="title">New certificate order (CSR) for certificate &quot;<em>$cert->name</em>&quot;</h2>
	 <p><a href="/index.php/${path[0]}/orders/${cid}">&laquo; Back to the certificate orders</a></p>
	 <form method="post" action="">
	  <input type="hidden" name="csr" value="1" />
	  <div class="input-group">
	   <div class="label">Certificates provider:</div>
	   <input type="text" name="provider_name" class="form-control" />
	  </div>
	  <div class="input-group">
	   <div class="label">e-Mail address:</div>
	   <input type="email" name="email" class="form-control" />
	  </div>
	  <div class="input-group">
	   <div class="label">Duration (in days):</div>
	   <input type="number" name="duration" class="form-control" min="1" max="99999" step="1" value="365" required="required" />
	  </div>
	  <p class="text-center"><input type="submit" value="Create new Certificate Signing Request" class="btn btn-success" /></p>
	 </form>
	</div>
PAGE;

}

?>
