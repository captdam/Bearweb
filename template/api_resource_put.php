<?php
	if ( !in_array(substr($BW->site->state, 0, 1), ['A', 'P']) || !$BW->session->user['ID'] ) { # Explicity check
		throw new BW_ServerError('Fatal config error: This API must not be opened to public', 500);
	}

	define('SITE', 'https://captdam.com/');
	define('WEBMASTER', 'admin@beardle.com');
	define('TYPE', array( # User should not be able to set custom category or template for security reason: User set resource template to API and open that API to public
		'Embedded' =>	['Embedded', ['page', 'content']],
		'Computer' =>	['Computer', ['page', 'content']],
		'Content' =>	['Content', ['object', 'blob']]
	));

	// 1 - Modify the specific resource

	if (!isset($_POST['URL']) || !Bearweb::check('URL', $_POST['URL']))
		throw new BW_ClientError('Missing or Bad URL', 400);

	try { //Update if existed
		$site = new Bearweb_Site($_POST['URL']);

		if ($site->owner != $BW->session->user['ID']) # This API does not account for admin, use another API with state = AADMIN
			throw new BW_ClientError('No ownership', 409);

		$type = [];
		if (!isset($_POST['Type']))				
			$type = [null, null];
		else if (!in_array($_POST['Type'], TYPE))
			$type = TYPE[$_POST['Type']];
		else
			throw new BW_ClientError('Unsupport Type', 403);
		
		$site->set(
			category:	$type[0],
			template:	$type[1],
			create:		null,
			modify:		-1,
			title:		$_POST['Title'] ?? null,
			keywords:	$_POST['Keywords'] ?? null,
			description:	$_POST['Description'] ?? null,
			state:		$_POST['State'] ?? null,
			content:	$_POST['Content'] ?? null,
			aux:		$_POST['Aux'] ?? null
		);
		http_response_code(202);
		return null;
	} catch (BW_ClientError $e) { //If not existed, throw BW_ClientError 404, create new
		$type = [];
		if (!isset($_POST['Type']))				
			throw new BW_ClientError('Missing Type', 400);
		if (!in_array($_POST['Type'], TYPE))
			throw new BW_ClientError('Unsupport Type', 403);
		$type = TYPE[$_POST['Type']];

		Bearweb_Site::create(
			url:		$_POST['URL'],
			category:	$type[0],
			template:	$type[1],
			owner:		$BW->session->user['ID'],
			title:		$_POST['Title'] ?? $_POST['URL'],
			keywords:	$_POST['Keywords'] ?? '',
			description:	$_POST['Description'] ?? '',
			state:		$_POST['State'] ?? 'P',
			content:	$_POST['Content'] ?? '',
			aux:		$_POST['Aux'] ?? ''
		);
		http_response_code(201);
		return null;
	}

	// 2 - Rebuid index

	$db = Bearweb::db_connect(Bearweb_Site_SiteDB);
	$sitemap_rss = '<?xml version="1.0" encoding="UTF-8" ?><rss version="2.0"><channel>'.
		'<title>Captdam\'s Blog</title>'.
		'<link>'.SITE.'</link>'.
		'<description>Captdam\'s blog</description>'.
		'<copyright>Copyright Captdam | CC BY-SA</copyright>'.
		'<generator>Bearweb</generator>'.
		'<image><link>'.SITE.'</link><title>Captdam\'s Blog</title><url>'.SITE.'/web/logo.png</url></image>'.
		'<lastBuildDate>'.date(DATE_RSS, $_SERVER['REQUEST_TIME']).'</lastBuildDate>'.
		'<webMaster>'.WEBMASTER.'</webMaster>';
	$sitemap_xml = '<?xml version="1.0" encoding="UTF-8"?><urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';
	$sitemap_txt = '';
	$list_blog = '';
	$list_embedded = '';
	$list_computer = '';

	$sql = $db->prepare('SELECT `URL`, `Category`, `Owner`, `Create`, `Modify`, `Title`, `Keywords`, `Description` FROM `Sitemap` WHERE `State` = \'O\' ORDER BY `Modify` DESC');
	$sql->execute();
	while ($x = $sql->fetch()) {
		$language = null;
		//if (is_array($aux) && array_key_exists('lang-en', $aux))

		if (in_array($x['Category'], ['Blog', 'Embedded', 'Computer'])) { # Do not include content (images)
			$sitemap_rss .= '<item>'.
				'<title>'.$x['Title'].'</title>'.
				'<link>'.SITE.$x['URL'].'</link>'.
				'<guid>'.SITE.$x['URL'].'</guid>'.
				'<author>'.$x['Owner'].'</author>'.
				'<category>'.$x['Category'].'</category>'.
				'<description>'.$x['Description'].'</description>'.
				'<pubDate>'.date(DATE_RSS, $x['Modify']).'</pubDate>'.
				'</item>';
			
			$list_new = '<div><a href="/'.$x['URL'].'">'.
				'<h2>'.$x['Title'].'</h2>'.
				'<p>'.$x['Description'].'</p>'.
				'<p class="content_keywords">'.$x['Keywords'].'</p>'.
				'<p><i>--by '.$x['Owner'].' @ '.date(DATE_RFC7231, $x['Modify']).' GMT </i></p>'.
				'</a></div>';
			$list_blog .= $list_new;
			if ($x['Category'] == 'Embedded')
				$list_embedded .= $list_new;
			if ($x['Category'] == 'Computer')
				$list_computer .= $list_new;
		}

		$sitemap_xml .= '<url>'.
			'<loc>'.SITE.$x['URL'].'</loc>'.
			'<lastmod>'.date(DATE_W3C, $x['Modify']).'</lastmod>'.
			'</url>';

		$sitemap_txt .= SITE.$x['URL'];
	}
	$sql->closeCursor();

	$sitemap_rss .= '</channel></rss>';
	$sitemap_xml .= '</urlset>';
	if (!$list_blog)
		$list_blog = '<div><p style="text-align: center">No Content For Now...</p></div>';
	if (!$list_embedded)
		$list_embedded = '<div><p style="text-align: center">No Content For Now...</p></div>';
	if (!$list_computer)
		$list_computer = '<div><p style="text-align: center">No Content For Now...</p></div>';


	function insertOrUpdateIndex($url, $content, $category, $template, $title, $keywords, $description) {
		try { //Update if existed
			$index = new Bearweb_Site($url);
			$index->set(modify: -1, content: $content);
		} catch (BW_ClientError $e) { //If not existed, throw BW_ClientError 404, create new
			Bearweb_Site::create(
				url:		$url,
				category:	$category,
				template:	$template,
				create:		0,	# No create time (generated)
				modify:		-1,	# Last modify is now
				title:		$title,
				keywords:	$keywords,
				description:	$description,
				state:		'O',
				content:	$content
			);
		} //Throw BW_DatabaseError if DB action failed
	}
	insertOrUpdateIndex('rss.xml', $sitemap_rss, 'SEO', ['object', 'blob'], 'RSS feed', '', '');
	insertOrUpdateIndex('sitemap.xml', $sitemap_xml, 'SEO', ['object', 'blob'], 'Sitemap XML', '', '');
	insertOrUpdateIndex('sitemap.txt', $sitemap_txt, 'SEO', ['object', 'blob'], 'Sitemap TXT', '', '');
	insertOrUpdateIndex('blog', $list_blog, 'Index', ['page', 'direct'], 'My Blogs: All categories', 'Captdam, Blog', 'Captdam\'s blog in all categories');
	insertOrUpdateIndex('embedded', $list_embedded, 'Index', ['page', 'direct'], 'My Blogs: Embedded System Dev', 'Captdam, Blog, Embedded system, Development', 'Captdam\'s development projects in embedded system');
	insertOrUpdateIndex('computer', $list_computer, 'Index', ['page', 'direct'], 'My Blogs: Computer Program Dev', 'Captdam, Blog, Computer program, Development', 'Captdam\'s development projects in computer program');
?>