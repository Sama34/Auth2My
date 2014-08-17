<?php
/**
 * Auth2My Plugin for MyBB
 * Copyright © 2012 kojis
 * Version: 1.0
 */

// Disallow direct access to this file for security reasons
if(!defined("IN_MYBB")) {
	die("Direct initialization of this file is not allowed.<br /><br />Please make sure IN_MYBB is defined.");
}

if(defined('IN_ADMINCP'))
{
	require_once MYBB_ROOT."inc/3rdparty/auth2my_class.php";

	$plugins->add_hook('admin_config_action_handler','auth2my_admin_action');
	$plugins->add_hook('admin_config_menu','auth2my_admin_config_menu');
	$plugins->add_hook('admin_load','auth2my_admin');
	$plugins->add_hook('admin_page_show_login_end','auth2my_show_login');
}

function auth2my_info() {
	return array(
		'name'=> 'Auth2My',
		'description'   => 'Auth2My 2-step verification.',
		'website'       => 'http://community.mybb.com/',
		'author'        => 'kojis',
		'authorsite'    => '',
		'version'       => '1.0',
		'guid'          => '116b957286dfd7a34df98169155b39e0',
		'compatibility' => '17*'
	);
}

function auth2my_install() {
	global $mybb, $db, $page;

	$db->write_query("
		CREATE TABLE ".TABLE_PREFIX."auth2my (
			`id` int(1) unsigned NOT NULL,
			`auth2my_key` char(16) NOT NULL,
			`auth2my_active` char(3) NOT NULL,
			PRIMARY KEY (auth2my_key)
		) ENGINE=MyISAM;
	");

	$auth2my_new_key = Google2FA::generate_secret_key();
	$query = $db->insert_query("auth2my", array("id" => "1","auth2my_key" => $auth2my_new_key, "auth2my_active" => "yes"));
}

function auth2my_is_installed() {
	global $db;

	return $db->table_exists('auth2my');
}

function auth2my_uninstall() {
	global $db;
	$db->drop_table('auth2my');
}

function auth2my_activate() {
	global $db;

	if(!function_exists('curl_version'))
	{
		flash_message('cURL need to be enabled in your server for Auth2My to be installed.', 'error');
		admin_redirect("index.php?module=config-plugins");
	}

	$db->update_query("auth2my", array('auth2my_active' => 'yes'));

}

function auth2my_deactivate() {
	global $db;
	$db->update_query("auth2my", array('auth2my_active' => 'no'));
}

function auth2my_admin_action(&$action) {
	$action['auth2my'] = array('active'=>'auth2my');
}

function auth2my_admin_config_menu(&$admim_menu) {

	end($admim_menu);

	$key = (key($admim_menu)) + 10;

	$admim_menu[$key] = array
	(
		'id' => 'auth2my',
		'title' => 'Auth2My',
		'link' => 'index.php?module=config-auth2my'
	);

}

function auth2my_admin() {
	global $settings, $mybb, $db, $page;

	if ($page->active_action != 'auth2my') {
		return false;
	}

	$query = $db->simple_select("auth2my", "auth2my_key", "id='1'");
	$auth2my = $db->fetch_array($query);
	$auth2my_key = htmlspecialchars_uni($auth2my['auth2my_key']);

	$page->add_breadcrumb_item('Auth2My');
	$page->output_header('Auth2My');

	$table = new Table;
	$table->construct_cell('Scan QR-image below with your "Google Authenticator":<br /><br />'.get_qr_code($settings['bbname'],$auth2my_key).'<br /><br /><b>Authentication key:</b> '.$auth2my_key.'<br /><br /><br /><small>If you have problems with image, <a href="https://www.google.com/chart?chs=200x200&chld=M|0&cht=qr&chl=otpauth://totp/'.$settings['bbname'].'?secret='.$auth2my_key.'" target="_blank">go this link</a> and reload url.</small>', array('colspan' => 5));
	$table->construct_row();
	$table->output('Auth2My settings:');

	$page->output_footer();
}

function auth2my_show_login(&$args)
{
	global $lang;

	$login_label_width = isset($lang->login_field_width) ? ' style="width: '.((int)$lang->login_field_width+100).'px;"' : '';

	$auth2my = '			<div class="label"'.$login_label_width.'><label for="auth2my">Auth2:</label></div>
			<div class="field"><input type="password" name="auth2my" id="auth2my" class="text_input" /></div>';

	$args['login_page'] = str_replace('id="password" class="text_input" /></div>', 'id="password" class="text_input" /></div>'.$auth2my, $args['login_page']);
}