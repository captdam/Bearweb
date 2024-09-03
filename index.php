<?php
	date_default_timezone_set('UTC'); //Always use UTC!
	$_SERVER['SCRIPT_URL'] = substr($_SERVER['SCRIPT_URL'], 1);

	class BW_Config {
		const Error_LogFile		= './error.log';				// Critical error log file
		const Site_DB			= ['sqlite:./bw_site.db', null, null];		// Sitemap database file
		const Site_HideAuthError	= false;					// Hide resource that requires auth. Show 404 instead of 401/403 code
		const Site_HideServerError	= false;					// Hide server error. Show 500 Internal error for types of BW_ServerError
		const Site_TemplateDir		= './template/';				// Template dir
		const Site_ResourceDir		= './resource/';				// Resource dir
		const User_DB			= ['sqlite:./bw_user.db', null, null];		// User control database file
		const Session_DB		= ['sqlite:./bw_session.db', null, null];	// Session control database file
		const Session_CookieSID		= 'BW_SessionID';				// Client-side cookie name for session ID, visible to client-side JS
		const Session_CookieKey		= 'BW_SessionKey';				// Client-side cookie name for session key, prevent XSS attack by client-side JS
		const Session_Expire		= (7 * 24 * 3600);				// Session expiry time in second
	};

	include_once './bearweb.class.php';
	if (!Bearweb_Site::validURL($_SERVER["SCRIPT_URL"])) {
		http_response_code(400);
		exit('Bad URL');
	}
	$bw = new Bearweb($_SERVER["SCRIPT_URL"]); #Provided by apache2 rewrite engine
?>