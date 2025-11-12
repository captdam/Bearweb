<?php
	date_default_timezone_set('UTC'); //Always use UTC!
	$_SERVER['SCRIPT_URL'] = substr($_SERVER['SCRIPT_URL'], 1);

	include_once './bearweb.class.php';

	class Bearweb extends _Bearweb {
		const HideServerError = true;

		protected function invokeTemplate(): void {
			$BW = $this;
			if (substr($this->site->template[0], 0, 4) == 'page') {
				header('Content-Type: text/html');
				{
					$domain = 'https://example.com/';
					$sitename = 'Example Site';
					$session_user = htmlspecialchars($BW->session->sUser, ENT_COMPAT);
					$meta_title = htmlspecialchars($BW->site->meta['title'] ?? '', ENT_COMPAT);
					$meta_keywords = htmlspecialchars($BW->site->meta['keywords'] ?? '', ENT_COMPAT);
					$meta_description = htmlspecialchars($BW->site->meta['description'] ?? '', ENT_COMPAT);
					echo '<!DOCTYPE html><html lang="en" data-suser="',$session_user,'"><head>',
					'<title>',$meta_title,' - ',$sitename,'</title>',
					'<meta property="og:title" content="',$meta_title,'" />',
					'<meta property="og:site_name" content="',$sitename,'" />',
					'<meta name="keywords" content="',$meta_keywords,'" />',
					'<meta name="description" content="',$meta_description,'" />',
					'<meta property="og:description" content="',$meta_description,'" />',
					'<meta name="viewport" content="width=device-width, initial-scale=1.0" />',
					'<meta charset="utf-8" />',
					'<link href="/web/style.css" rel="stylesheet" type="text/css" />',
					'<script src="/web/bearapi.js"></script>',
					'<script src="/web/bearweb.js"></script>',
					'<link rel="canonical" href="',$domain,$BW->site->url,'" />',
					'<meta property="og:url" content="',$domain,$BW->site->url,'" />',
					( array_key_exists('robots', $BW->site->meta) ? ('<meta name="robots" content="'.htmlspecialchars($BW->site->meta['robots'], ENT_COMPAT).'" />') : '' ),
					( array_key_exists('bgimg', $BW->site->meta) ? ('<meta property="__og:image" content="'.htmlspecialchars($BW->site->meta['bgimg'], ENT_COMPAT).'" />') : '' ),
					( $BW->site->owner ? ('<meta name="author" content="'.htmlspecialchars($BW->site->owner, ENT_COMPAT).'" />') : '' ),
					( array_key_exists('lang-en', $BW->site->meta) ? ('<link rel="alternate" hreflang="en" href="/'.htmlspecialchars($BW->site->aux['lang-en'], ENT_COMPAT).'" type="text/html" />') : '' ),
					( array_key_exists('lang-zh', $BW->site->meta) ? ('<link rel="alternate" hreflang="en" href="/'.htmlspecialchars($BW->site->aux['lang-zh'], ENT_COMPAT).'" type="text/html" />') : '' ),
					'</head><body>';
				}
				$template = Bearweb_Site::Dir_Template.$this->site->template[0].'.php';
				if (!file_exists($template))
					throw new BW_WebServerError('Template not found: '.$this->site->template[0], 500);
				include $template;
				echo '</body></html>';
			} else if ($this->site->template[0] == 'object') {
				header('Content-Type: '.($this->site->aux['mime'] ?? 'text/plain'));
				if ($this->site->template[1] == 'blob') {
					echo $this->site->content;
				} else {
					$template = Bearweb_Site::Dir_Template.'object_'.$this->site->template[1].'.php';
					if (!file_exists($template)) throw new BW_WebServerError('Secondary object template not found: '.$this->site->template[1], 500);
					include $template;
				}
			} else if ($this->site->template[0] == 'api') {
				header('Content-Type: application/json');
				$template = Bearweb_Site::Dir_Template.'api_'.$this->site->template[1].'.php';
				if (!file_exists($template)) throw new BW_WebServerError('Secondary object template not found: '.$this->site->template[1], 500);
				$data = include $template;
				//$data['BW_Session'] = ['sID' => $this->session->sID, 'tID' => $this->session->tID, 'sUser' => $this->session->sUser, 'http' => http_response_code()];
				echo json_encode($data);
			} else {
				parent::invokeTemplate();
			}
		}

		protected function createErrorPage(string $title, string $detail, int $code = 0): void {
			if ($code) http_response_code($code);
			$this->site = new Bearweb_Site(template: ['page-en', 'error'], meta: ['title' => $title, 'keywords' => '', 'description' => $detail, 'robots' => 'noindex, nofollow'], content: $detail);
		}

		//protected function throwClientError_auth(BW_Error $e) { throw new BW_ClientError('Not found', 404); } // Hide detail reason and return 404 for 401 and 403 (auth required)
	}

	class Bearweb_Site extends _Bearweb_Site {
		// const Dir_Template = './template/';	# Template dir
		// const Dir_Resource = './resource/';	# Resource dir

		const FixedMap = [
			'web/style.css' => ['category' => 'Web', 'create' => 1666331474, 'modify' => 1666331474, 'content' => null, 'aux' => ['mime' => 'text/css']],
			'web/bearapi.js' => ['category' => 'Web', 'create' => 1666333333, 'modify' => 1760656813, 'content' => null, 'aux' => ['mime' => 'application/javascript']],
			'web/bearweb.js' => ['category' => 'Web', 'create' => 1666333333, 'modify' => 1760656813, 'content' => null, 'aux' => ['mime' => 'application/javascript']],

			'api/resource/get' => ['category' => 'API', 'template' => ['api','resource'], 'meta' => ['task' => 'get', 'access' => [1]]],
			'api/resource/create' => ['category' => 'API', 'template' => ['api','resource'], 'meta' => ['task' => 'create', 'access' => [1]]],
			'api/resource/update' => ['category' => 'API', 'template' => ['api','resource'], 'meta' => ['task' => 'update', 'access' => [1]], 'aux' => ['type' => [ // Add yours ------------------------------------------------
				/* List of allowed template */
				// 'Name' => ['sitemap->category', sitemap->template[]]
				'Content' => ['Content',['object','blob']]
			]]],
			'api/resource/delete' => ['category' => 'API', 'template' => ['api','resource'], 'meta' => ['task' => 'delete', 'access' => [1]]],
			'api/resource/reindex' => ['category' => 'API', 'template' => ['api','resource'], 'meta' => ['task' => 'reindex', 'access' => [1]]],

			'api/user/get' => ['category' => 'API', 'template' => ['api','user'], 'meta' => ['task' => 'get']],
			'api/user/logoff' => ['category' => 'API', 'template' => ['api','user'], 'meta' => ['task' => 'logoff']],
			'api/user/login' => ['category' => 'API', 'template' => ['api','user'], 'meta' => ['task' => 'login']],
			'api/user/register' => ['category' => 'API', 'template' => ['api','user'], 'meta' => ['task' => 'register']],

			'user.html' => ['category' => 'CMD', 'template' => ['page-en','direct'], 'meta' => ['title' => 'User page', 'robots' => 'noindex, nofollow'], 'content' => null],
			'editor.html' => ['category' => 'CMD', 'template' => ['page-en','direct'], 'meta' => [
				'title' => 'Moderator workspace',
				'access' => [1],
				'robots' => 'noindex, nofollow',
				'keywords' => 'Content, grey, Name1, color1, Name2, color2' // Add yours ------------------------------------------------
			], 'content' => null],
			'thumb.html' => ['category' => 'CMD', 'template' => ['page-en','direct'], 'meta' => [
				'title' => 'Thumbnail uploader',
				'access' => [1],
				'robots' => 'noindex, nofollow',
				'keywords' => 'Name1, color1, Name2, color2' // Add yours ------------------------------------------------
			], 'content' => null],
		];

		public static function init(): void {
			try { static::$db = new PDO('sqlite:./bw_site.db', null, null, [
				PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
				PDO::ATTR_TIMEOUT => 10, #10s waiting time should be far more than enough
				PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
			]); } catch (Exception $e) { throw new BW_DatabaseServerError('Fail to open DB: '.$e->getMessage(), 500); }
		}
	}

	class Bearweb_Session extends _Bearweb_Session {
		// const CookieSID = 'SessionID';
		// const CookieKey = 'SessionKey';
		// const Expire = 7 * 24 * 3600;

		public static function init(): void {
			try { static::$db = new PDO('sqlite:./bw_session.db', null, null, [
				PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
				PDO::ATTR_TIMEOUT => 10, #10s waiting time should be far more than enough
				PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
			]); } catch (Exception $e) { throw new BW_DatabaseServerError('Fail to open DB: '.$e->getMessage(), 500); }
		}
	}

	class Bearweb_User extends _Bearweb_User {
		public static function init(): void {
			try { static::$db = new PDO('sqlite:./bw_user.db', null, null, [
				PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
				PDO::ATTR_TIMEOUT => 10, #10s waiting time should be far more than enough
				PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
			]); } catch (Exception $e) { throw new BW_DatabaseServerError('Fail to open DB: '.$e->getMessage(), 500); }
		}
	}
	
	
	function bear_reindex() {
		_bear_reindex([
			new class extends BearIndex_Catalog {
				public function __construct() { parent::__construct('catalog', ['page-en', 'catalog'], ['title' => 'Index', 'keywords' => 'keywords', 'description' => 'My site index', 'lang' => 'en', 'bgimg' => 'url(\'/web/banner.png\')'], ['lang-en' => '/catalog']); }
				public function add(array $r): bool { return in_array($r['category'], ['Category1', 'Category2']) && !static::dontIndex($r) && parent::add($r); }
			},
			new class extends BearIndex_SitemapRss {
				public function __construct() { parent::__construct('rss.xml', [], 'https://example.com/', 'Example Sitemap', 'Example site', 'Copyright Example | CC BY-SA', 'admin@example.com'); }
				public function add(array $r): bool { return !static::dontIndex($r) && parent::add($r); }
			},
			new class extends BearIndex_SitemapTxt {
				public function __construct() { parent::__construct('sitemap.txt', [], 'https://example.com/'); }
				public function add(array $r): bool { return !static::dontIndex($r) && parent::add($r); }
			},
			new class extends BearIndex_SitemapXml {
				public function __construct() { parent::__construct('sitemap.xml', [], 'https://example.com/'); }
				public function add(array $r): bool { return !static::dontIndex($r) && parent::add($r); }
			}
		]);
	}

	if (!Bearweb_Site::validURL($_SERVER["SCRIPT_URL"])) {
		http_response_code(400);
		exit('Bad URL');
	}
	$bw = new Bearweb($_SERVER["SCRIPT_URL"]); #Provided by apache2 rewrite engine
?>