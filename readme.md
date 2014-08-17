Upload files to your MyBB server root.

## Patch
In `. / admin / index.php` find:
```PHP
	if($loginhandler->verify_username() !== false && $loginhandler->verify_password() !== false)
	{
		$mybb->user = get_user($loginhandler->login_data['uid']);
	}
```

Add after:
```PHP
	require_once MYBB_ROOT."inc/3rdparty/auth2my_class.php";
	$query = $db->simple_select("auth2my", "*", "id='1'");
	$auth2my = $db->fetch_array($query);

	$auth2my_verify = Google2FA::verify_key($auth2my['auth2my_key'], $mybb->input['auth2my']);

	if($auth2my_verify == false && $auth2my['auth2my_active'] == "yes")
	{
		$default_page->show_login("Invalid Auth2","error");    
	}
```