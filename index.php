<?php
	date_default_timezone_set('UTC'); //Always use UTC!

	const BW_Config = [
		'Error_LogFile'		=> './error.log',		// Critical error log file
		'Site_DB'		=> 'sqlite:./bw_site.db',	// Sitemap database file
		'Site_HideAuthError'	=> false,			// Hide resource that requires auth. Show 404 instead of 401/403 code
		'Site_HideServerError'	=> false,			// Hide server error. Show 500 Internal error for types of BW_ServerError
		'Site_TemplateDir'	=> './template/',		// Template dir
		'Site_ResourceDir'	=> './resource/',		// Resource dir
		'Site_IndexExpire'	=> (1 * 3600),			// Index / table expire
		'User_DB'		=> 'sqlite:./bw_user.db',	// User control database file, comment to disable
		'Session_DB'		=> 'sqlite:./bw_session.db',	// Session control database file, comment to disable
		'Session_CookieSID'	=> 'BW_SessionID',		// Client-side cookie name for session ID, visible to client-side JS
		'Session_CookieKey'	=> 'BW_SessionKey',		// Client-side cookie name for session key, prevent XSS attack by client-side JS
		'Session_Expire'	=> (7 * 24 * 3600),		// Session expiry time in second
	];

	include_once './bearweb.class.php';
	$bw = new Bearweb( trim($_SERVER["SCRIPT_URL"], " \/\\\t\n\r\0\x0B") ); #Provided by apache2 rewrite engine
?>
