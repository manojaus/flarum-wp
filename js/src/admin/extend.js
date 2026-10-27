import Extend from 'flarum/common/extenders';

import {ID} from '../config';

const code = (key, name, info) => () => ({
	setting: `${ID}.${key}`,
	type: 'text',
	label: <span>{name} (<code>{info}</code>)</span>
});

const bool = (key, name) => () => ({
	setting: `${ID}.${key}`,
	type: 'boolean',
	label: name
});

export default [
	new Extend.Admin()
		.setting(code('login_url', 'Login URL', '.../wp-login.php'))
		.setting(code('profile_url', 'Profile URL', '.../wp-admin/profile.php'))
		.setting(code('cookie_name', 'Cookie Name', 'wordpress_logged_in_...'))
		.setting(code('db_host', 'Database Host', 'DB_HOST'))
		.setting(code('db_user', 'Database User', 'DB_USER'))
		.setting(code('db_pass', 'Database Password', 'DB_PASSWORD'))
		.setting(code('db_name', 'Database Name', 'DB_NAME'))
		.setting(code('db_charset', 'Database Charset', 'DB_CHARSET'))
		.setting(code('db_pre', 'Database Prefix', '$table_prefix'))
		.setting(code('logged_in_key', 'Logged In Key', 'LOGGED_IN_KEY'))
		.setting(code('logged_in_salt', 'Logged In Salt', 'LOGGED_IN_SALT'))
		.setting(code('nonce_key', 'Nonce Key', 'NONCE_KEY'))
		.setting(code('nonce_salt', 'Nonce Salt', 'NONCE_SALT'))
		.setting(bool('decode_plus', 'Decode + in cookie (for usernames with spaces from WordPress on PHP < 8)'))
];
