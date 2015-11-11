<?php

/**
 * SSL Vault: Administration page
 */


# Title
print '<h1 class="title">Administration</h1>';


# Load users
print <<<CARD
<div class="card">
 <h2 class="title">Users</h2>
CARD;


# Update/new user
$form = new PostData();
if ($form->_post)
{

	# New user
	if ($form->new)
	{
		if ($dbh->insert('users', array( 'username' => $form->username, 'password' => hash('md5', $form->password) )))
		{
			print <<<MSG
			<div class="alert alert-success">
			 Account created!
			</div>
MSG;
		}
		else
		{
			error_log("Failed to create account: ".$dbh->log_error());
			print <<<MSG
			<div class="alert alert-danger">
			 Failed to create account!<br />
			 Please contact your System Administrator.
			</div>
MSG;
		}
	}
	# Change account status
	elseif ($uid = $form->status)
	{
		if ($dbh->update('users', array( 'enable' => $form->disable=='true' ? false : true ), "uid=${uid}"))
		{
			print <<<MSG
			<div class="alert alert-success">
			 Account successfully updated.
			</div>
MSG;
		}
		else
		{
			error_log("Failed to update user account #${uid}: ".$dbh->log_error());
			print <<<MSG
			<div class="alert alert-danger">
			 Failed to update this user account!<br />
			 Please contact your System Administrator.
			</div>
MSG;
		}
	}
	# Delete user
	elseif ($uid = $form->delete)
	{
		if ($dbh->exec("DELETE FROM users WHERE uid=${uid};"))
		{
			print <<<MSG
			<div class="alert alert-success">
			 Account successfully removed.
			</div>
MSG;
		}
		else
		{
			error_log("Failed to remove user account #${uid}: ".$dbh->log_error());
			print <<<MSG
			<div class="alert alert-danger">
			 Failed to remove this user account!<br />
			 Please contact your System Administrator.
			</div>
MSG;
		}
	}
	# Edit user
	elseif ($uid = $form->edit)
	{

		# Save file
		$form->_savefile('avatar', $_SERVER['DOCUMENT_ROOT']."/img/avatars/${uid}.png");

		# Update user account
		$current = $uid==$_SESSION['auth']['uid'];
		$upd = array(
			'email' => $form->email ? $form->email : null
		);
		if (!$current)
		{
			$upd['admin'] = $form->admin ? true : false;
			$upd['enable'] = $form->enable ? true : false;
		}
		$ok = true;
		if ($pw1 = $form->new_password and $pw2 = $form->new_password_conf)
		{
			if ($pw1!=$pw2)
			{
				$ok = false;
				print <<<MSG
				<div class="alert alert-danger">
				 The new password confirmation is invalid!<br />
				 Please retry.
				</div>
MSG;
			}
			else
			{
				$upd['password'] = hash('md5', $pw1);
			}
		}
		if ($ok)
		{
			if ($dbh->update('users', $upd, "uid=${uid}"))
			{
				print <<<MSG
				<div class="alert alert-success">
				 Account successfully updated.
				</div>
MSG;
			}
			else
			{
				error_log("Failed to update user account #${uid}: ".$dbh->log_error());
				print <<<MSG
				<div class="alert alert-danger">
				 Failed to update this user account!<br />
				 Please contact your System Administrator.
				</div>
MSG;
			}
		}

	}

}


# 
print <<<CARD
 <form method="post" action="" id="new-account">
  <input type="hidden" name="new" value="1" />
  <div class="input-group default col-lg-4">
   <input type="text" name="username" class="form-control" autocomplete="off" required="required" value="" placeholder="User name" />
   <input type="password" name="password" class="form-control" autocomplete="off" required="required" value="" placeholder="Password" />
   <input type="submit" value="Add new user" class="btn btn-success" />
  </div>
 </form>
 <table class="default">
  <thead>
   <tr>
    <th colspan="2">User</th>
    <th>Status</th>
    <th>&nbsp;</th>
   </tr>
  </thead>
  <tbody>
CARD;
if ($query = $dbh->query("SELECT uid,username,enable,admin,email FROM users ORDER BY username;"))
{
	while (list($uid, $name, $enable, $admin, $email) = $query->fetch())
	{
		$current = $uid==$_SESSION['auth']['uid'];
		$avatar = $_SERVER['DOCUMENT_ROOT']."/img/avatars/${uid}.png";
		if (!file_exists($avatar)) $avatar = false;
		if (!$avatar) $avatar = '/img/common/unknown-128.png';
		$avatar = str_replace($_SERVER['DOCUMENT_ROOT'], '', $avatar);
		$jsdata = array(
			'uid' => $uid,
			'current' => $current,
			'name' => $name,
			'email' => $email,
			'enable' => $enable,
			'admin' => $admin,
			'avatar' => array(
				'url' => $avatar,
			),
			'acc2disable' => (!$current and $enable) ? '' : 'style="display:none;"',
			'acc2enable' => ($current or $enable) ? 'style="display:none;"' : '',
			'del' => $current ? 'style="display:none;"' : ''
		);
		$jsdata['usr'] = $jsdata;
		$jsdata = str_replace('"', '&quot;', json_encode($jsdata));
		$label = !$enable ? '<span class="label label-warning">Disabled</span> ' : '';
		$label.= $admin ? '<span class="label label-primary">Administrator</span> ' : '';
		print <<<USER
		<tr>
		 <td class="icon"><div class="avatar" style="background-image:url('${avatar}');"></div></td>
		 <td>${name}</td>
		 <td>${label}</td>
		 <td class="button"><div data-role="dropdown" data-infos="${jsdata}" data-template="dropdown-cert" class="btn btn-default btn-sm">Actions <span class="caret"></span></div></td>
		</tr>
USER;
	}
	$query->closeCursor();
}
else
{
	error_log("Failed to load users list: ".$dbh->errorInfo()[2]);
	print <<<ERROR
	<tr>
	 <td colspan="3"><div class="alert alert-danger">Failed to load users list...</div></td>
	</tr>
ERROR;
}
print <<<CARD
  </tbody>
 </table>
</div>

<div class="dropdown-template" id="dropdown-cert">
 <ul>
  <li><a href="javascript:edit_account({:usr});">Edit account</a></li>
  <li {:acc2enable}><a href="javascript:enable_account('{:uid}',true);">Enable account</a></li>
  <li {:acc2disable}><a href="javascript:enable_account('{:uid}',false);">Disable account</a></li>
  <li {:del}><a href="javascript:delete_account({:uid});">Delete account</a></li>
 </ul>
</div>


<script type="text/javascript">

function delete_account(uid)
{

	$.modal({
		'content': $.heredoc(function(){/*TAG
		<form method="post" action="">
		 <input type="hidden" name="delete" value="{:uid}" />
		 <p>You are about to delete this user; it cannot be reverted.</p>
		 <p class="text-center">
		  <input type="submit" class="btn btn-success" value="Yes, I know, delete it!" />
		  <span data-role="close" class="btn btn-danger">Cancel</span>
		 </p>
		</form>
		TAG*/}, {
			'uid': uid
		})
	});

}

function enable_account(uid, en)
{
	$.AutoPostForm({
		'status': uid,
		'disable': !en
	});
}

function edit_account_avatar_preview(data)
{

	$('.form-account .avatar').attr('style', 'background-image:url(\''+data.target.result+'\');');

}

function edit_account(usr)
{

	$.modal({
		'title': 'Edit account <em>'+usr.name+'</em>',
		'content': $.heredoc(function(){/*TAG
		<form method="post" action="" class="form-account" enctype="multipart/form-data">
		 <input type="hidden" name="edit" value="{:uid}" />
		 <div class="table">
		  <div class="cell userpic">
		   <div class="avatar" style="background-image:url('{:avatar[url]}');"></div>
		   <div class="text-center"><span class="btn btn-default" data-role="inputfile" data-name="avatar" data-accept="image/png" data-preview="edit_account_avatar_preview">Change avatar</span></div>
		  </div>
		  <div class="cell infos">
		   <h1>{:name}</h1>
		   <div class="checkbox-line">
		    <input type="checkbox" id="admin" name="admin" {:admin} {:current} />
		    <label for="admin">Administrator (give access to this zone)</label>
		   </div>
		   <div class="checkbox-line">
		    <input type="checkbox" id="enable" name="enable" {:enable} {:current} />
		    <label for="enable">Enable this account</label>
		   </div>
		   <div class="input-group default">
		    <div class="label">E-mail address:</div>
		    <input type="email" name="email" placeholder="me@foo.bar" class="form-control" value="{:email}" />
		   </div>
		   <div class="input-group default">
		    <div class="label">Change password:</div>
		    <input type="password" name="new_password" placeholder="New password" class="form-control" />
		    <input type="password" name="new_password_conf" placeholder="Confirm new password" class="form-control" />
		   </div>
		  </div>
		 </div>
		 <p class="text-right"><input type="submit" value="Save modifications" class="btn btn-success" /> <span data-role="close" class="btn btn-default">Cancel</span></p>
		</form>
		TAG*/}, {
			'email': usr.email,
			'avatar': usr.avatar,
			'name': usr.name,
			'uid': usr.uid,
			'enable': usr.enable ? 'checked="checked"' : '',
			'admin': usr.admin ? 'checked="checked"' : '',
			'current': usr.current ? 'disabled="disabled"' : ''
		})
	});

}

</script>
CARD;

?>
