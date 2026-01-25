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

			//'api/resource/reindex' => ['category' => 'API', 'template' => ['api','resource'], 'meta' => ['task' => 'reindex']],

			/** Following are resource to create the example website, you can delete them */
			'robots.txt' => ['category' => 'Web', 'create' => 1768423263, 'modify' => 1768423263, 'content' => "User-agent: *\nSitemap: https://bearweb.captdam.com/sitemap.txt\nSitemap: https://bearweb.captdam.com/sitemap.xml\nSitemap: https://bearweb.captdam.com/rss.xml", 'aux' => ['mime' => 'text/plain']],

			'' => ['category' => 'Home', 'template' => ['page-en','langsel'], 'create' => 1769288622, 'modify' => 1769288622, 'aux' => ['lang-en' => 'en', 'lang-zh' => 'zh']],
			'en' => ['category' => 'Home', 'template' => ['page-en','direct'], 'create' => 1769288622, 'modify' => 1769288622, 'aux' => ['lang-en' => 'en', 'lang-zh' => 'zh'], 'content' => null, 'meta' => [
				'title' => 'Bearweb CMS',
				'description' => 'A light-weight, portable database-driven content management system designed for personal blog and small-size organization website.',
				'keywords' => 'CMS, Centent Management System, PHP 8.4, Apache2, Database-driven, Blog, Web dev'
			]],
			'zh' => ['category' => 'Home', 'template' => ['page-zh','direct'], 'create' => 1769288622, 'modify' => 1769288622, 'aux' => ['lang-en' => 'en', 'lang-zh' => 'zh'], 'content' => null, 'meta' => [
				'title' => 'Bearweb CMS',
				'description' => '轻量化，可移植的数据库驱动内容管理系统。专为个人博客与小型组织网站设计。',
				'keywords' => 'CMS, 内容管理系统, PHP 8.4, Apache2, 数据库驱动, 博客, 网站开发'
			]],

			'template' => ['category' => 'Home', 'template' => ['page-en','langsel'], 'create' => 1769288622, 'modify' => 1769288622, 'aux' => ['lang-en' => 'template/en', 'lang-zh' => 'template/zh']],
			'template/en' => ['category' => 'Home', 'template' => ['page-en','article'], 'create' => 1769288622, 'modify' => 1769288622, 'aux' => ['lang-en' => 'template/en', 'lang-zh' => 'template/zh'], 'content' => null, 'meta' => [
				'title' => 'Bearweb Template',
				'description' => 'Bearweb templates are used to define detail operation when process a request: render a HTML page, output a file, process an API request.',
				'keywords' => 'CMS, Centent Management System, PHP 8.4, Web dev'
			]],
			'template/zh' => ['category' => 'Home', 'template' => ['page-zh','article'], 'create' => 1769288622, 'modify' => 1769288622, 'aux' => ['lang-en' => 'template/en', 'lang-zh' => 'template/zh'], 'content' => null, 'meta' => [
				'title' => 'Bearweb模板',
				'description' => 'Bearweb模板定义了如何具体处理一个请求，包括渲染一个HTML页面，输出一个文件，处理一个API请求。',
				'keywords' => 'CMS, 内容管理系统, PHP 8.4, 网站开发'
			]],

			// Page template - Template and sub-templates

			'page' => ['category' => 'Template', 'create' => 1769055184, 'modify' => 1769055184, 'template' => ['page-en','langsel'], 'aux' => ['lang-en' => 'page/en', 'lang-zh' => 'page/zh']],
			'page/en' => ['category' => 'Template', 'create' => 1769055184, 'modify' => 1769055184, 'template' => ['page-en','article'], 'aux' => ['lang-en' => 'page/en', 'lang-zh' => 'page/zh'], 'content' => null, 'meta' => [
				'title' => 'Bearweb Page Template',
				'description' => 'The Bearweb page template is built on top of the Bearweb CMS framework. The page template provides a simple, responsive design framework to display webpage content (HTML). This article shows how to use the Bearweb page template.',
				'keywords' => 'Web dev, Data structure, HTML, Template'
			]],
			'page/zh' => ['category' => 'Template', 'create' => 1769055184, 'modify' => 1769055184, 'template' => ['page-zh','article'], 'aux' => ['lang-en' => 'page/en', 'lang-zh' => 'page/zh'], 'content' => null, 'meta' => [
				'title' => 'Bearweb网页模板',
				'description' => 'Bearweb网页模板基于Bearweb CMS架构。Bearweb网页模板提供了一套简单、响应式设计的模板来显示网页内容（HTML）。这篇文章展示了如何使用Bearweb网页模板。',
				'keywords' => '网站开发, 数据结构, HTML, 模板'
			]],

			'page/rd-small.png' => ['category' => 'Content', 'create' => 1769055184, 'modify' => 1769055184, 'content' => null, 'aux' => ['mime' => 'image/png']],
			'page/rd-middle.png' => ['category' => 'Content', 'create' => 1769055184, 'modify' => 1769055184, 'content' => null, 'aux' => ['mime' => 'image/png']],
			'page/rd-large.png' => ['category' => 'Content', 'create' => 1769055184, 'modify' => 1769055184, 'content' => null, 'aux' => ['mime' => 'image/png']],

			'page/direct' => ['category' => 'Template', 'create' => 1769055184, 'modify' => 1769055184, 'template' => ['page-en','langsel'], 'aux' => ['lang-en' => 'page/direct/en', 'lang-zh' => 'page/direct/zh']],
			'page/direct/en' => ['category' => 'Template', 'create' => 1769055184, 'modify' => 1769055184, 'template' => ['page-en','direct'], 'aux' => ['lang-en' => 'page/direct/en', 'lang-zh' => 'page/direct/zh'], 'meta' => [
				'title' => 'Example Page',
				'description' => 'An example webpage',
				'keywords' => 'webpage, HTML',
				'img' => 'web/banner.jpeg'
			], 'content' => '<div class="main_title"><h1>Example Page</h1></div><div><h2>Title 1</h2><p>Content 1</p></div><div><h2>Title 2</h2><p>Content 2</p></div>'],
			'page/direct/zh' => ['category' => 'Template', 'create' => 1769055184, 'modify' => 1769055184, 'template' => ['page-zh','direct'], 'aux' => ['lang-en' => 'page/direct/en', 'lang-zh' => 'page/direct/zh'], 'meta' => [
				'title' => '示例页面',
				'description' => '一个示例页面',
				'keywords' => '网页, HTML',
				'img' => 'web/banner.jpeg'
			], 'content' => '<div class="main_title"><h1>示例页面</h1></div><div><h2>标题1</h2><p>内容1</p></div><div><h2>标题2</h2><p>内容2</p></div>'],
			'page/direct.en.png' => ['category' => 'Content', 'create' => 1769055184, 'modify' => 1769055184, 'content' => null, 'aux' => ['mime' => 'image/png']],
			'page/direct.zh.png' => ['category' => 'Content', 'create' => 1769055184, 'modify' => 1769055184, 'content' => null, 'aux' => ['mime' => 'image/png']],

			'page/article' => ['category' => 'Template', 'create' => 1769055184, 'modify' => 1769055184, 'template' => ['page-en','langsel'], 'aux' => ['lang-en' => 'page/article/en', 'lang-zh' => 'page/article/zh']],
			'page/article/en' => ['category' => 'Template', 'create' => 1769055184, 'modify' => 1769055184, 'template' => ['page-en','article'], 'aux' => ['lang-en' => 'page/article/en', 'lang-zh' => 'page/article/zh'], 'meta' => [
				'title' => 'Example Page',
				'description' => 'An example webpage',
				'keywords' => 'webpage, HTML',
				'img' => 'web/banner.jpeg'
			], 'content' => '<div class="main_title"><h1>Example Page</h1></div><div><h2>Title 1</h2><p>Content 1</p></div><div><h2>Title 2</h2><p>Content 2</p></div>'],
			'page/article/zh' => ['category' => 'Template', 'create' => 1769055184, 'modify' => 1769055184, 'template' => ['page-zh','article'], 'aux' => ['lang-en' => 'page/article/en', 'lang-zh' => 'page/article/zh'], 'meta' => [
				'title' => '示例页面',
				'description' => '一个示例页面',
				'keywords' => '网页, HTML',
				'img' => 'web/banner.jpeg'
			], 'content' => '<div class="main_title"><h1>示例页面</h1></div><div><h2>标题1</h2><p>内容1</p></div><div><h2>标题2</h2><p>内容2</p></div>'],
			'page/article.en.png' => ['category' => 'Content', 'create' => 1769055184, 'modify' => 1769055184, 'content' => null, 'aux' => ['mime' => 'image/png']],
			'page/article.zh.png' => ['category' => 'Content', 'create' => 1769055184, 'modify' => 1769055184, 'content' => null, 'aux' => ['mime' => 'image/png']],

			'page/image' => ['category' => 'Photo', 'create' => 1769055184, 'modify' => 1769055184, 'template' => ['page-en','langsel'], 'aux' => ['lang-en' => 'page/image/en', 'lang-zh' => 'page/image/zh']],
			'page/image/en' => ['category' => 'Template', 'create' => 1769055184, 'modify' => 1769055184, 'template' => ['page-en','image'], 'aux' => ['lang-en' => 'page/image/en', 'lang-zh' => 'page/image/zh'], 'meta' => [
				'title' => 'Example Image',
				'description' => 'This is a screenshot of the Bearweb CMS source code, it is used as an example here.',
				'keywords' => 'Source code, Web dev',
				'img' => 'web/banner.thumb.jpeg',
				'hd' => 'web/banner.jpeg',
				'ratio' => 1.33333
			], 'content' => '<div><p>Optional content</p></div>'],
			'page/image/zh' => ['category' => 'Template', 'create' => 1769055184, 'modify' => 1769055184, 'template' => ['page-zh','image'], 'aux' => ['lang-en' => 'page/image/en', 'lang-zh' => 'page/image/zh'], 'meta' => [
				'title' => '示例图片',
				'description' => '该图片展示了Bearweb CMS的源码，这张图片在此被用作一个例子。',
				'keywords' => '源代码, 网站开发',
				'img' => 'web/banner.thumb.jpeg',
				'hd' => 'web/banner.jpeg',
				'ratio' => 1.33333
			], 'content' => '<div><p>可选内容</p></div>'],
			'page/image.en.png' => ['category' => 'Content', 'create' => 1769055184, 'modify' => 1769055184, 'content' => null, 'aux' => ['mime' => 'image/png']],
			'page/image.zh.png' => ['category' => 'Content', 'create' => 1769055184, 'modify' => 1769055184, 'content' => null, 'aux' => ['mime' => 'image/png']],
			
			'page/catalog.en.png' => ['category' => 'Content', 'create' => 1769055184, 'modify' => 1769055184, 'content' => null, 'aux' => ['mime' => 'image/png']],
			'page/catalog.zh.png' => ['category' => 'Content', 'create' => 1769055184, 'modify' => 1769055184, 'content' => null, 'aux' => ['mime' => 'image/png']],
			'page/bulletin.en.png' => ['category' => 'Content', 'create' => 1769055184, 'modify' => 1769055184, 'content' => null, 'aux' => ['mime' => 'image/png']],
			'page/bulletin.zh.png' => ['category' => 'Content', 'create' => 1769055184, 'modify' => 1769055184, 'content' => null, 'aux' => ['mime' => 'image/png']],

			'page/langsel.png' => ['category' => 'Content', 'create' => 1769055184, 'modify' => 1769055184, 'content' => null, 'aux' => ['mime' => 'image/png']],
			'page/error.png' => ['category' => 'Content', 'create' => 1769055184, 'modify' => 1769055184, 'content' => null, 'aux' => ['mime' => 'image/png']],
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
				public function __construct() { parent::__construct('example/catalog/en', ['page-en', 'catalog'], ['title' => 'Catalog', 'keywords' => 'keywords', 'description' => 'Example catalog', 'bgimg' => 'url(\'/web/banner.jpeg\')'], ['lang-en' => 'example/catalog/en', 'lang-zh' => 'example/catalog/zh']); }
				public function add(array $r): bool { return in_array($r['category'], ['Photo-en']) && !static::dontIndex($r) && parent::add($r); }
			},
			new class extends BearIndex_Catalog {
				public function __construct() { parent::__construct('example/catalog/zh', ['page-zh', 'catalog'], ['title' => '索引', 'keywords' => 'keywords', 'description' => '示例索引', 'bgimg' => 'url(\'/web/banner.jpeg\')'], ['lang-en' => 'example/catalog/en', 'lang-zh' => 'example/catalog/zh']); }
				public function add(array $r): bool { return in_array($r['category'], ['Photo-zh']) && !static::dontIndex($r) && parent::add($r); }
			},

			new class extends BearIndex_Bulletin {
				public function __construct() { parent::__construct('example/bulletin/en', ['page-en', 'bulletin'], ['title' => 'Images', 'keywords' => 'keywords', 'description' => 'Example images', 'bgimg' => 'url(\'/web/banner.jpeg\')'], ['lang-en' => 'example/bulletin/en', 'lang-zh' => 'example/bulletin/zh']); }
				public function add(array $r): bool { return in_array($r['category'], ['Photo-en']) && !static::dontIndex($r) && parent::add($r); }
			},
			new class extends BearIndex_Bulletin {
				public function __construct() { parent::__construct('example/bulletin/zh', ['page-zh', 'bulletin'], ['title' => '图片', 'keywords' => 'keywords', 'description' => '示例图片', 'bgimg' => 'url(\'/web/banner.jpeg\')'], ['lang-en' => 'example/bulletin/en', 'lang-zh' => 'example/bulletin/zh']); }
				public function add(array $r): bool { return in_array($r['category'], ['Photo-zh']) && !static::dontIndex($r) && parent::add($r); }
			},

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