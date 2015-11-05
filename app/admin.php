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
 <table class="default">
  <thead>
   <tr>
    <th colspan="2">User</th>
    <th>Status</th>
    <th colspan="2">&nbsp;</th>
   </tr>
  </thead>
  <tbody>
CARD;
if ($query = $dbh->query("SELECT uid,username,enabled,admin FROM users ORDER BY username;"))
{
	while (list($uid, $name, $enabled, $admin) = $query->fetch())
	{
		$disabled = $uid==$_SESSION['auth']['uid'] ? 'disabled="disabled"' : '';
		$avatar = $_SERVER['DOCUMENT_ROOT']."/img/common/${uid}.png";
		if (!file_exists($avatar)) $avatar = false;
		if (!$avatar) $avatar = '/img/common/unknown-128.png';
		$avatar = str_replace($_SERVER['DOCUMENT_ROOT'], '', $avatar);
		print <<<USER
		<tr>
		 <td class="icon"><div class="avatar" style="background-image:url('${avatar}');"></div></td>
		 <td>${name}</td>
		 <td>${label}</td>
		 <td class="button"><a href="javascript:update_user('${uid}');" class="btn btn-primary btn-condensed">Edit account</a></td>
		 <td class="button"><a href="javascript:delete_user('${uid}');" class="btn btn-danger btn-condensed" ${disabled}>Delete user</a></td>
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


<script type="text/javascript">

function delete_user(uid)
{

	$.modal({ 'content': 'You are about to delete this user; it cannot be reverted.' });

}

function update_user(uid)
{

	$.modal({ 'content': 'Hello, world!' });

}

</script>
CARD;

?>
