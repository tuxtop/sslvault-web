<?php

/**
 * SSL Vault
 * 
 * SSL Vault is your SSL Certificates storage and manager area.
 * 
 * @license GPLv3
 * @author Julien Dumont <julien@dumont.rocks>
 */


/**
 * Classes auto-loaders
 *
 * @param string $class_name Classname tried to be loaded
 */
function __autoload($class_name)
{
	$file = $_SERVER['DOCUMENT_ROOT']."/inc/${class_name}.class.php";
	if (!file_exists($file)) die("<p>Could not found class ${class_name}...</p>");
	require($file);
}


# Start session
session_name('sslvault-webapp');
session_cache_expire(5);
session_start();


# Load configuration
$conf = yaml_parse_file($_SERVER['DOCUMENT_ROOT'].'/config.yaml');
if (!isset($conf['date_format'])) $conf['date_format'] = 'd/m/Y (H:i)';
if (!isset($conf['impending_delay'])) $conf['impending_delay'] = 30;


# Init database handler
$db = $conf['database'];
$dbh = new Database("pgsql:dbname=${db['name']};host=${db['host']};port=5432;sslmode=require", $db['username'], $db['password']);


# Init publicKey for self-signed
$tmp = $dbh->query("SELECT COUNT(*) FROM sslkeys_catalog WHERE name=':selfsigned';");
list($is_self_pkey) = $tmp->fetch();
if (!$is_self_pkey)
{
	$res = openssl_pkey_new();
	$tmp = openssl_pkey_get_details($res);
	$pubKey = $tmp['key'];
	openssl_pkey_export($res, $privKey);
	$dbh->query("INSERT INTO sslkeys_catalog (name,private,public) VALUES (':selfsigned','${privKey}','${pubKey}')") or die("Failed to initialize self-signed keys! (".$dbh->errorInfo()[2].")");
}


# Manage page to display
if (!isset($_SERVER['PATH_INFO'])) $_SERVER['PATH_INFO'] = '/overview';
$path = explode('/', $_SERVER['PATH_INFO']);
array_shift($path);
if (!count($path)) $path = array( 'overview' );


# Start output capture
ob_start();


# Print first part of skeleton
print <<<HTML
<!DOCTYPE html>

<html>
 <head>
  <title>${conf['name']}</title>
  <link href="/css/bootstrap/css/bootstrap.min.css" rel="STYLESHEET" type="text/css" />
  <link href="/css/theme.css" rel="STYLESHEET" type="text/css" />
  <script type="text/javascript" src="/js/jquery-2.1.4.min.js"></script>
  <script type="text/javascript" src="/js/sslvault.js"></script>
 </head>
 <body>
HTML;


# Get content
if (isset($_SESSION['auth']))
{

	# Display header and menu
	$username = $_SESSION['auth']['username'];
	$avatar = $_SESSION['auth']['avatar'];
	print <<<HEAD
	<nav class="page">
	 <header>
	  <div class="userpic" style="background-image:url('${avatar}');"></div>
	  <h1>${conf['name']}</h1>
	  <h2>${username}</h2>
	 </header>
	 <ul class="menu">
	  <li><a href="/">Overview</a></li>
	  <li><a href="/index.php/certificates">Certificates</a></li>
	  <li><a href="/index.php/sslkeys">SSL Keys</a></li>
	  <li><a href="/index.php/about">About</a></li>
	 </ul>
	 <ul class="admin">
HEAD;
	if (isset($_SESSION['auth']['admin']) and $_SESSION['auth']['admin'])
	{
		print <<<ADMIN
		 <li><a href="/index.php/admin">Administration</a></li>
ADMIN;
	}
	print <<<HEAD
	  <li><a href="/index.php/logout">Close my session</a></li>
	 </ul>
	</nav>
HEAD;
	
	# Display application page
	$file = $_SERVER['DOCUMENT_ROOT']."/app/${path[0]}.php";
	print '<div class="page-container">';
	if ($path[0]=='logout')
	{
		session_destroy();
		header('Location: /');
	}
	elseif (!file_exists($file))
	{
		header('Status: 404 Not Found', true, 404);
		print <<<MSG
		<h1>Oooops! Page Not Found!</h1>
		<p>The page you are looking for does not exist.</p>
MSG;
	}
	else
	{
		require($file);
	}
	print '</div>';

}
else
{
	$error = false;
	if ($form = new PostData() and $form->_post)
	{
		if ($form->username and $form->password)
		{
			$form->username = strtoupper($form->username);
			$form->password = hash('md5', $form->password);
			if ($query = $dbh->query("SELECT uid,username,admin FROM users WHERE UPPER(username)='$form->username' AND password='$form->password' AND enable=true"))
			{
				list($uid, $uname, $admin) = $query->fetch();
				if (isset($uid) and $uid)
				{
					$avatar = $_SERVER['DOCUMENT_ROOT']."/img/avatars/${uid}.png";
					if (!file_exists($avatar)) $avatar = false;
					if (!$avatar) $avatar = '/img/common/unknown-128.png';
					$avatar = str_replace($_SERVER['DOCUMENT_ROOT'], '', $avatar);
					$_SESSION['auth'] = array( 'uid'=>$uid, 'username'=>$uname, 'admin'=>$admin, 'avatar'=>$avatar );
					header('Location: /');
				}
				else
				{
					$error = "Your credentials are invalid.";
				}
			}
			else
			{
				error_log("Failed to get login credetials for $form->username: ".$dbh->errorInfo()[2]);
				$error = "Internal error, please contact your administrator.";
			}
		}
	}
	if ($error) $error = '<div class="alert alert-danger">'.$error.'</div>';
	print <<<FORM
	<div class="login-frame">
	 <div class="login-box">
	  <form method="post" action="">
	   <h1>${conf['name']} &raquo; Login</h1>
	   ${error}
	   <div class="field"><input type="text" name="username" placeholder="Username" class="form-control" /></div>
	   <div class="field"><input type="password" name="password" placeholder="Password" class="form-control" /></div>
	   <div class="submit"><input type="submit" value="Log in" class="btn btn-primary" /></div>
	  </form>
	 </div>
	</div>
FORM;
}


# Print second part of skeleton
print <<<HTML
 </body>
</html>
HTML;


# End capture
ob_end_flush();

?>
