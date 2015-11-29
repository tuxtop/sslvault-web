<?php

/**
 * SSL Vault: Show SSL keys pairs
 */


# Require a valid keyid
if (!isset($path[2]) or !preg_match('/^\d+$/', $path[2]))
{
	print <<<MSG
	<h1>Oooops! Invalid keys reference ID!</h1>
	<p>The key pairs ID you try to access is not a valid ID.</p>
	<p><a href="/index.php/${path[0]}">&laquo; Back to the list</a></p>
MSG;
}
else
{

	# Open card
	print <<<DIV
	<div class="card">
	 <h2 class="title">SSL keys pair informations</h2>
	 <p><a href="/index.php/${path[0]}">&laquo; Back to the list</a></p>
DIV;

	# Update keys
	if ($form = new PostData() and $form->_post)
	{
		$upd = array();
		if ($form->private) $upd['private'] = $form->private;
		if ($form->public) $upd['public'] = $form->public;
		if ($form->name) $upd['name'] = $form->name;
		if (count($upd))
		{
			$str = array();
			foreach ($upd as $key=>$val)
			{
				$str[]= "${key}='${val}'";
			}
			$request = "UPDATE sslkeys_catalog SET ".implode(',', $str)." WHERE kid=${path[2]}";
			if ($dbh->exec($request)==1)
			{
				print <<<MSG
				<div class="alert alert-success">
				 Keys successfully updated!
				</div>
MSG;
			}
			else
			{
				error_log("Failed to update SSL keys pair #${path[2]}: ".$dbh->errorInfo()[2]);
				print <<<MSG
				<div class="alert alert-danger">
				 Failed to update SSL Keys.<br />
				 Please contact your System Administrator.
				</div>
MSG;
			}
		}
	}

	# Load data
	if ($query = $dbh->query("SELECT kid,name,private,public,creation FROM sslkeys_catalog WHERE kid=${path[2]};"))
	{
		if ($query->rowCount()==1)
		{
			list($kid, $name, $private, $public, $creation) = $query->fetch();
			$delete = $alias = '';
			if (isset($keys_aliases[$name])) $alias = "<br /><small>${keys_aliases[$name]}</small>";
			if (substr($name,0,1)!=':')
			{
				$delete = ' <a href="javascript:delete_pairs('.$kid.');" class="btn btn-danger">Delete pairs</a>';
				$name = '<input type="text" name="name" class="default" value="'.$name.'" required="required" />';
			}
			print <<<FORM
			<form method="post" action="">
			 <p><strong>${name}</strong>${alias}</p>
			 <p>SSL keys pair #${kid} was created the ${creation}</p>
			 <p>Private key:</p>
			 <p><textarea name="private" class="default" cols="100" rows="15">${private}</textarea></p>
			 <p>Public key:</p>
			 <p><textarea name="public" class="default" cols="100" rows="5">${public}</textarea></p>
			 <p class="text-center"><input class="btn btn-success" type="submit" value="Save modifications" />${delete}</p>
			</form>
FORM;
		}
		else
		{
			print <<<MSG
			<p>This SSL keys pair does not longer exists.</p>
MSG;
		}
	}
	else
	{
		error_log("Failed to load SSL keys pair #${path[2]}: ".$dbh->errorInfo()[2]);
		print <<<MSG
		<h1>Oooops! Could not load SSL keys pair informations!</h1>
		<p>Failed to load data from SSL Keys Catalog! Please contact your system administrator.</p>
MSG;
	}

	# Close card
	print <<<DIV
	</div>

	<script type="text/javascript">

		function delete_pairs(item)
		{
			if (confirm('Are you sure you want to delete this keys pair?'))
			{
				var form = $('<form method="post" action="/index.php/${path[0]}"><input type="hidden" name="delete" value="'+item+'" /></form>');
				$('body').append(form);
				form.trigger('submit');
			}
		}

	</script>
DIV;

}

?>
