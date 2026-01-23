<?php
	date_default_timezone_set('UTC'); //Always use UTC!
	$_SERVER['SCRIPT_URL'] = substr($_SERVER['SCRIPT_URL'], 1);

	include_once './bearweb.class.php';

	class Bearweb extends _Bearweb {
		const HideServerError = false;

		protected function invokeTemplate(): void {
			$BW = $this;
			if (substr($this->site->template[0], 0, 4) == 'page') {
				header('Content-Type: text/html');
				$template = Bearweb_Site::Dir_Template.$this->site->template[0].'.php';
				if (!file_exists($template))
					throw new BW_WebServerError('Template not found: '.$this->site->template[0], 500);
				include $template;
			} else if ($this->site->template[0] == 'object') {
				header('Content-Type: '.($this->site->aux['mime'] ?? 'text/plain'));
				if ($this->site->template[1] == 'blob') {
					ob_end_clean();
					$this->site->dumpContent(-1, true);
					ob_start();
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
			'favicon.ico' => ['category' => 'Web', 'create' => 1768423263, 'modify' => 1768423263, 'content' => null, 'aux' => ['mime' => 'image/x-icon']],
			'web/style.css' => ['category' => 'Web', 'create' => 1768423263, 'modify' => 1768423263, 'content' => null, 'aux' => ['mime' => 'text/css']],
			'web/bearapi.js' => ['category' => 'Web', 'create' => 1768423263, 'modify' => 1768423263, 'content' => null, 'aux' => ['mime' => 'application/javascript']],
			'web/bearweb.js' => ['category' => 'Web', 'create' => 1768423263, 'modify' => 1768423263, 'content' => null, 'aux' => ['mime' => 'application/javascript']],
			'web/strip.svg' => ['category' => 'Web', 'create' =>  1768423263, 'modify' =>  1768423263, 'content' => '<svg viewBox="0 0 8 8" width="45px" height="45px" xmlns="http://www.w3.org/2000/svg"><path d="M 0 0 h 8 v 8 h -8 z" fill="#CCC" /><path d="M 0 0 h 2 l -2 2 z" fill="pink" /><path d="M 8 8 h -2 l 2 -2 z" fill="pink" /><path d="M 6 0 h 4 l -8 8 h -4 z" fill="pink" /></svg>', 'aux' => ['mime' => 'image/svg+xml']],
			'web/rss.svg' => ['category' => 'Web', 'create' =>  1768423263, 'modify' =>  1768423263, 'content' => '<svg viewBox="0 0 8 8" width="1ch" height="1ch" xmlns="http://www.w3.org/2000/svg"><path d="M 0 0 h 8 v 8 h -8 z" fill="orange" /><circle cx="1.5" cy="6.5" r="1" /><path d="M 1 4 A 3 3 0 0 1 4 7" fill="transparent" stroke="#000" stroke-width="1" /><path d="M 1 2 A 5 5 0 0 1 6 7" fill="transparent" stroke="#000" stroke-width="1" /></svg>', 'aux' => ['mime' => 'image/svg+xml']],
			'web/banner.jpeg' => ['category' => 'Web', 'create' => 1769055184, 'modify' => 1769055184, 'content' => null, 'aux' => ['mime' => 'image/jpeg']],
			'web/banner.thumb.jpeg' => ['category' => 'Web', 'create' => 1769055184, 'modify' => 1769055184, 'content' => null, 'aux' => ['mime' => 'image/jpeg']],

			/* // Uncomment to enable APIs
			'api/resource/get' => ['category' => 'API', 'template' => ['api','resource'], 'meta' => ['task' => 'get', 'access' => [1]]],
			'api/resource/create' => ['category' => 'API', 'template' => ['api','resource'], 'meta' => ['task' => 'create', 'access' => [1]]],
			'api/resource/update' => ['category' => 'API', 'template' => ['api','resource'], 'meta' => ['task' => 'update', 'access' => [1]], 'aux' => ['type' => [ // Add yours ------------------------------------------------
				// List of allowed template
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
			*/
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
			new class extends BearIndex_SitemapRss {
				public function __construct() { parent::__construct('rss.xml', [], 'https://bearweb.captdam.com/', 'Bearweb CMS', 'Bearweb Content Management System', 'Copyright Captdam | MIT License', 'admin@example_com'); }
				public function add(array $r): bool { return !static::dontIndex($r) && parent::add($r); }
			},
			new class extends BearIndex_SitemapTxt {
				public function __construct() { parent::__construct('sitemap.txt', [], 'https://bearweb.captdam.com/'); }
				public function add(array $r): bool { return !static::dontIndex($r) && parent::add($r); }
			},
			new class extends BearIndex_SitemapXml {
				public function __construct() { parent::__construct('sitemap.xml', [], 'https://bearweb.captdam.com/'); }
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