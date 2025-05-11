<?php
	date_default_timezone_set('UTC'); //Always use UTC!
	$_SERVER['SCRIPT_URL'] = substr($_SERVER['SCRIPT_URL'], 1);

	include_once './bearweb.class.php';

	class Bearweb extends _Bearweb {
		const HideServerError = true;

		protected function invokeTemplate(): void {
			$BW = $this;
			if ($this->site->template[0] == 'object') {
				header('Content-Type: '.($this->site->meta[0] ? $this->site->meta[0] : 'text/plain'));
				if ($this->site->template[1] == 'blob') {
					echo $this->site->content;
				} else if ($this->site->template[1] == 'local') {
					$resource = Bearweb_Site::Dir_Resource.($this->site->content ? $this->site->content : $this->site->url);
					if (!file_exists($resource)) throw new BW_WebServerError('Resource not found: '.$resource, 500);
					echo file_get_contents($resource);
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
			$this->site = new Bearweb_Site(
				url: '', category: '', template: ['page-en', 'error'],
				owner: '', create: Bearweb_Site::TIME_NULL, modify: Bearweb_Site::TIME_NULL,
				meta: [$title, '', $detail], 
				state: 'S', content: $detail, aux: []
			);
		}

		//protected function throwClientError_auth(BW_Error $e) { throw new BW_ClientError('Not found', 404); } // Hide detail reason and return 404 for 401 and 403 (auth required)
	}

	class Bearweb_Site extends _Bearweb_Site {
		const Dir_Template = './template/';	# Template dir
		const Dir_Resource = './resource/';	# Resource dir

		const FixedMap = [
			'test' => ['category' => 'Content', 'template' => ['object','blob'], 'meta' => ['text/plain'], 'state' => 'S', 'content' => '123', 'aux' => []],
			
			'api/resource/create' => ['category' => 'API', 'template' => ['api','resource'], 'meta' => ['Create'], 'state' => 'AMOD', 'content' => '', 'aux' => []],
			'api/resource/get' => ['category' => 'API', 'template' => ['api','resource'], 'meta' => ['Get'], 'state' => 'AMOD', 'content' => '', 'aux' => []],
			'api/resource/my' => ['category' => 'API', 'template' => ['api','resource'], 'meta' => ['My'], 'state' => 'AMOD', 'content' => '', 'aux' => []],
			'api/resource/update' => ['category' => 'API', 'template' => ['api','resource'], 'meta' => ['Update'], 'state' => 'AMOD', 'content' => '', 'aux' => ['type' => [
				'Embedded-en' => ['Embedded-en',['page-en','content']],
				'Computer-en' => ['Computer-en',['page-en','content']],
				'Embedded-zh' => ['Embedded-zh',['page-zh','content']],
				'Computer-zh' => ['Computer-zh',['page-zh','content']],
				'Content' => ['Content',['object','blob']]
			]]],

			'api/user/data' => ['category' => 'API', 'template' => ['api','user'], 'meta' => ['Data'], 'state' => 'S', 'content' => '', 'aux' => []],
			'api/user/login' => ['category' => 'API', 'template' => ['api','user'], 'meta' => ['Login'], 'state' => 'S', 'content' => '', 'aux' => []],
			'api/user/loginkey' => ['category' => 'API', 'template' => ['api','user'], 'meta' => ['LoginKey'], 'state' => 'S', 'content' => '', 'aux' => []],
			'api/user/logoff' => ['category' => 'API', 'template' => ['api','user'], 'meta' => ['Logoff'], 'state' => 'S', 'content' => '', 'aux' => []],
			'api/user/my' => ['category' => 'API', 'template' => ['api','user'], 'meta' => ['My'], 'state' => 'S', 'content' => '', 'aux' => []],
			'api/user/register' => ['category' => 'API', 'template' => ['api','user'], 'meta' => ['Register'], 'state' => 'S', 'content' => '', 'aux' => []],
		];

		public static function init(): void {
			try { static::$db = new PDO('sqlite:./bw_site.db', null, null, [
				PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
				PDO::ATTR_TIMEOUT => 10, #10s waiting time should be far more than enough
				PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
			]); } catch (Exception $e) { throw new BW_DatabaseServerError('Fail to open DB: '.$e->getMessage(), 500); }
		}

		public static function query(string $url): ?static {
			return array_key_exists($url, static::FixedMap) ? new static(...static::FixedMap[$url], url: $url, owner: '', create: static::TIME_NULL, modify: static::TIME_NULL) : parent::query($url);
		}

		public function insert(): void {
			if (array_key_exists($this->url, static::FixedMap))
				throw new BW_ClientError('Cannot modify hard-coded resource.', 405);
			parent::insert();
		}

		public function update(): void {
			if (array_key_exists($this->url, static::FixedMap))
				throw new BW_ClientError('Cannot modify hard-coded resource.', 405);
			parent::update();
		}

		public function delete(): void {
			if (array_key_exists($this->url, static::FixedMap))
				throw new BW_ClientError('Cannot modify hard-coded resource.', 405);
			parent::delete();
		}
	}

	class Bearweb_Session extends _Bearweb_Session {
		const CookieSID = 'SessionID';
		const CookieKey = 'SessionKey';
		const Expire = 7 * 24 * 3600;

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

	if (!Bearweb_Site::validURL($_SERVER["SCRIPT_URL"])) {
		http_response_code(400);
		exit('Bad URL');
	}
	$bw = new Bearweb($_SERVER["SCRIPT_URL"]); #Provided by apache2 rewrite engine
?>