<?php	header('X-Powered-By: Bearweb 7.2.251117');

	class _Bearweb {
		const HideServerError = false;

		protected Bearweb_Session $session;
		protected Bearweb_User $user;
		protected Bearweb_Site $site;

		/** Use Bearweb to server a resource. 
		 * This function will init the Site module and the Session module. 
		 * This function will invoke the error template if the given URL fail the URL format check or upon error in framework and template execution.
		 * @param string $url Request URL given by client
		 */
		public function __construct(string $url) {
			try {
				Bearweb_Site::init();
				Bearweb_User::init();
				Bearweb_Session::init();
				$this->session = new Bearweb_Session($url);
				$this->user = Bearweb_User::query($this->session->sUser, Bearweb_User::QUERY_UPDATE_LASTACTIVE) ?? Bearweb_User::query('');
			} catch (Exception $e) {
				error_log('[BW] Fatal error: Module init failed - Cannot connect to DB: '.$e->getMessage());
				http_response_code(500); exit('500 - Server Fatal Error!');
			}
			
			try { ob_start();
				$this->site = Bearweb_Site::query($url) ?? throw new BW_ClientError('Not found', 404);

				// Access control
				if ($this->site->access($this->user) == Bearweb_Site::ACCESS_NONE) {
					throw new BW_ClientError('Access denied', 403);
				}

				// Redirect resource
				if (array_key_exists('r301', $this->site->meta)) {
					header('Location: /'.$this->site->meta['r301']);
					throw new BW_ClientError('301 - Moved Permanently: Resource has been moved permanently to: '.$this->site->meta['r301'], 301); # To terminate the resource processing
				} else if (array_key_exists('r302', $this->site->meta)) {
					header('Location: /'.$this->site->meta['r302']);
					throw new BW_ClientError('302 - Moved Temporarily: Resource has been moved temporarily to: '.$this->site->meta['r302'], 302);
				}

				// E-tag
				if ($this->site->create == Bearweb_Site::TIME_NULL) { # Create random E-tag for auto generated contents, and disable client-side cache
					header('Last-Modified: '.date('D, j M Y G:i:s').' GMT');
					header('Etag: '.base64_encode(random_bytes(48))); 
					header('Cache-Control: no-store');
				/*} else if ( $_SERVER['HTTP_IF_NONE_MATCH'] ?? false && $_SERVER['HTTP_IF_NONE_MATCH'] == base64_encode($this->site->url).':'.base64_encode($this->site->modify) ) { # Client cache is good
					throw new BW_ClientError('304 - Not Modified', 304);*/
				} else {
					header('Last-Modified: '.date('D, j M Y G:i:s', $this->site->modify).' GMT');
					header('Etag: '.$this->site->url.'@'.$this->site->modify);
					header('Cache-Control: private, max-age=3600');
				}

				// Aux headers
				header('X-Robots-Tag: '.($this->site->meta['robots'] ?? 'all'));
				
				// Invoke template
				$this->invokeTemplate();
				
			} catch (Exception $e) { ob_clean(); ob_start();
				$this->session->log($e->getMessage());
				if ($e instanceof BW_ClientError) {
					$this->createErrorPage($e->getCode().' - Client Error', $e->getMessage(), $e->getCode());
				} else if ($e instanceof BW_ServerError) {
					error_log('[BW] Server Error: '.$e);
					static::HideServerError ? $this->createErrorPage('500 - Internal Error', 'Server-side internal error.', 500) : $this->createErrorPage($e->getCode().' - Server Error', $e->getMessage(), $e->getCode());
				} else {
					error_log('[BW] Unknown Error: '.$e);
					static::HideServerError ? $this->createErrorPage('500 - Internal Error', 'Server-side internal error.', 500) : $this->createErrorPage('500 - Unknown Error', $e->getMessage(), 500);
				}
				$this->invokeTemplate();
			}

			ob_end_flush();
		}

		protected function invokeTemplate(): void {
			$BW = $this;
			$template = Bearweb_Site::Dir_Template.$this->site->template[0].'.php';
			if (!file_exists($template))
				throw new BW_WebServerError('Template not found: '.$this->site->template[0], 500);
			include $template;
		}

		protected function createErrorPage(string $title, string $detail, int $code = 0): void {
			if ($code) http_response_code($code);
			$this->site = new Bearweb_Site(meta: ['robots'=> 'noindex, nofollow'], content: '<!DOCTYPE html><meta name="robots" content="noindex" /><h1>'.$title.'</h1><p>'.$detail.'</p>', aux: ['mime' => 'text/html']);
		}

		protected function throwClientError_auth(BW_Error $e) { throw new BW_ClientError($e->getMessage(), $e->getCode()); }
	}


	class _Bearweb_Site { use Bearweb_DatabaseBacked;
		const Dir_Template = './template/';	# Template dir
		const Dir_Resource = './resource/';	# Resource dir
		const Size_FileBlob = 100000;		# Threshold for storing blob in DB instead of file system (100kB as https://www.sqlite.org/intern-v-extern-blob.html)

		final const TIME_CURRENT = -1;		# Pass this parameter to let Bearweb use current timestamp
		final const TIME_NULL = 0;		# Some resource (like auto generated one) has no create / modify time
		final const ACCESS_NONE = 0;		# No access
		final const ACCESS_RO = 1;		# Readonly, and executable
		final const ACCESS_RW = -1;		# Read and write

		/** Resource not saved in DB (resource with fixed content and metadata like APIs, JSs, CSSs...) */
		const FixedMap = [];

		/** Resource URL, PK */
		public string $url;

		/** Resource category, for management purpose */
		public string $category;

		/** Template used to process the given resourc [main_template, sub_template, 2nd_sub_template...] */
		public array $template {
			set (string|array $value) => is_string($value) ? (json_decode($value, false) ?? ['object', 'blob']) : $value;
		}

		/** Owner's user ID of the given resource; Only the owner and user in "ADMIN" group can modify this resource */
		public string $owner;

		/** Create timestamp */
		public int $create {
			set => $value == self::TIME_CURRENT ? $_SERVER['REQUEST_TIME'] : $value;
		}

		/** Modify timestamp */
		public int $modify {
			set => $value == self::TIME_CURRENT ? $_SERVER['REQUEST_TIME'] : $value;
		}

		/** Meta data, used by framework and index [meta => data...] */
		public array $meta {
			set (string|array $value) => is_string($value) ? (json_decode($value, true) ?? []) : $value;
		}

		/** Resource content (always get string), the content should be directly output to reduce server process load, consider use $this->dumpContent() to directly dump to output to save RAM */
		public mixed $content { // FEATURE REQUEST: resource|string
			get => is_string(get_mangled_object_vars($this)['content']) ? $this->content : $this->__file_read(-1);
		}

		/** Resource auxiliary data, template defined data array [key => value...] */
		public array $aux {
			set (string|array $value) => is_string($value) ?(json_decode($value, true) ?? []) : $value;
		}

		/** Create a resource object. 
		 * @param string	$url		Resource URL, PK. Default ''
		 * @param string 	$category	Resource category, for management purpose. Default ''
		 * @param string|array	$template	Template used to process the given resourc [main_template, sub_template, 2nd_sub_template...]: JSON or array. Default [object, blob] for direct output content
		 * @param string	$owner		Owner's user ID of the given resource; Only the owner and user in "ADMIN" group can modify this resource. Default '' system owned
		 * @param string 	$create		Create timestamp. Default this::TIME_NULL for no actual time, use this::TIME_CURRENT to use current timestamp
		 * @param string 	$modify		Modify timestamp. Default this::TIME_NULL for no actual time, use this::TIME_CURRENT to use current timestamp
		 * @param string|array 	$meta		Meta data, used by framework and index [meta => data...]: JSON or array. Default []
		 * @param ?string	$content	Resource content, the content should be directly output to reduce server process load. Default ''
		 * @param string 	$aux		Resource auxiliary data, template defined data array [key => value...]: JSON or array. Default []
		 */
		public function __construct(
			string		$url = '',
			string		$category = '',
			array|string	$template = ['object', 'blob'],
			string		$owner = '',
			int		$create = self::TIME_NULL,
			int		$modify = self::TIME_NULL,
			array|string	$meta = [],
			mixed		$content = '', // FEATURE REQUEST: resource|string
			array|string	$aux = []
		) {
			$this->url	= $url;
			$this->category	= $category;
			$this->template	= $template;
			$this->owner	= $owner;
			$this->create	= $create;
			$this->modify	= $modify;
			$this->meta	= $meta;
			$this->content	= $content;
			$this->aux	= $aux;
		}

		/** URL is valid. /^[A-Za-z0-9\-\_\:\/\.]{0,128}$/, no './' at any place. 
		 */
		public static function validURL(string $url): bool { return preg_match('/^[A-Za-z0-9\-\_\:\/\.]{0,128}$/', $url) && !preg_match('/\.\//', $url); }

		/** Query a resource from sitemap db. 
		 * Note: Data (in both DB and file) is volatile. This instance only reflects the data at time of DB fetch; file read only reflects the data at time of read for file-backed resource. 
		 * Data may be changed by another transaction (e.g. Resource modify API) or another process. 
		 * @param string $url			Resource URL
		 * @return ?Bearweb_Site		A Bearweb site resource, or null if resource not exist
		 * @throws BW_DatabaseServerError	Cannot read sitemap DB
		*/
		public static function query(string $url): ?static {
			if (array_key_exists($url, static::FixedMap)) {
				$site = static::FixedMap[$url];
				if (array_key_exists('content', $site) && $site['content'] === null)
					$site['content'] = fopen(static::__file_path($url), 'rb');
				return new static(...$site, url: $url);
			}
			try {
				$sql = static::$db->prepare('SELECT * FROM `Sitemap` WHERE `url` = ?');
				$sql->bindValue(	1,	$url,	PDO::PARAM_STR	);
				$sql->execute();
				$site = $sql->fetch();
				$sql->closeCursor();
				if (!$site)
					return null;
				$site['content'] = $site['content'] ?? fopen(static::__file_path($site['url']), 'rb');
				return new static(...$site);
			} catch (Exception $e) { throw new BW_DatabaseServerError('Cannot read sitemap database: '.$e->getMessage(), 500); }
		}

		/** Get content length. 
		 * @return int Content length in bytes
		 */
		public function getContentLength(): int { return is_string(get_mangled_object_vars($this)['content']) ? strlen($this->content) : $this->__file_size(); }

		/** Directly output the content. 
		 * This is useful for large content. Large content is saved in file, using this function prevent loading it into RAM; instead, the content is directly dumpped to output. 
		 * You should use this::getContentLength() to obtain Content-Length header first, then disable output buffer and execute this function to minimize RAM footprint. 
		 * @param int $len = -1 Dump up to len bytes of content, pass -1 to dump all the content
		 * @param bool $header = flase Send Content-Length HTTP header
		 */
		public function dumpContent(int $len = -1, bool $header = false): void {
			if (is_string(get_mangled_object_vars($this)['content'])) {
				if ($header)
					header('Content-Length: '.strlen($this->content));
				echo $this->content;
			} else {
				$this->__file_dump($len, $header);
			}
		}

		/** Test user access privilege level. 
		 * @param Bearweb_User $user Bearweb_User object with user ID and group
		 * @return int ACCESS_NONE, ACCESS_RO or ACCESS_RW (owner/admin)
		 */
		public function access(Bearweb_User $user): int {
			if ( !$user->isGuest() && ($user->isAdmin() || $user->id==$this->owner) ) return self::ACCESS_RW; # Note: Admin always have privilege, guest never have privilege; system resource is owned by '' but '' means guest in Bearweb_User
			if (!array_key_exists('access', $this->meta)) return self::ACCESS_RO;
			foreach ($this->meta['access'] as $whitelist) {
				if ($whitelist == $user->id || in_array($whitelist, $user->group))
					return self::ACCESS_RO;
			}
			return self::ACCESS_NONE;
		}

		/** Insert this resource into sitemap db. 
		 * This function will use transaction. You should not be in a transaction while call this function. 
		 * @throws BW_DatabaseServerError Fail to insert into sitemap db
		 */
		public function insert(): void { try {
			if (array_key_exists($this->url, static::FixedMap))
				throw new Exception('Cannot modify hard-coded resource.');
			if (static::$db->inTransaction())
				throw new Exception('Not allowed in transaction.');
			static::$db->beginTransaction();
			try {
				$sql = static::$db->prepare('INSERT INTO `Sitemap` (
					`url`, `category`, `template`,
					`owner`, `create`, `modify`, `meta`,
					`content`, `aux`
				) VALUES (	?, ?, ?,	?, ?, ?, ?,	?, ?)');
				$sql->bindValue(1,	$this->url,				PDO::PARAM_STR	);
				$sql->bindValue(2,	$this->category,			PDO::PARAM_STR	);
				$sql->bindValue(3,	static::encodeJSON($this->template),	PDO::PARAM_STR	);
				$sql->bindValue(4,	$this->owner,				PDO::PARAM_STR	);
				$sql->bindValue(5,	$this->create,				PDO::PARAM_INT	);
				$sql->bindValue(6,	$this->modify,				PDO::PARAM_INT	);
				$sql->bindValue(7,	static::encodeJSON($this->meta),	PDO::PARAM_STR	);
				$sql->bindValue(9,	static::encodeJSON($this->aux),		PDO::PARAM_STR	);
				if (strlen($this->content) >= static::Size_FileBlob) {
					$sql->bindValue(8, null, PDO::PARAM_NULL);
				} else {
					$sql->bindValue(8, $this->content, PDO::PARAM_STR);
				}
				$sql->execute();
				if (strlen($this->content) >= static::Size_FileBlob) {
					$this->__file_write();
				} else {
					$this->__file_delete();
				}
				static::$db->commit();
			} catch (Exception $e) {
				static::$db->rollBack();
				throw $e;
			}
			$sql->closeCursor();
		} catch (Exception $e) { throw new BW_DatabaseServerError('Cannot insert into sitemap database: '.$e->getMessage(), 500); } }

		/** Update this resource in sitemap db. 
		 * It is not necessary to query this resource, create a dummy resource with url and fields to modify (leave other field null to keep orginal data). 
		 * This function will use transaction. You should not be in a transaction while call this function. 
		 * @throws BW_DatabaseServerError Fail to update sitemap db
		 */
		public function update(): void { try {
			if (array_key_exists($this->url, static::FixedMap))
				throw new Exception('Cannot modify hard-coded resource.');
			if (static::$db->inTransaction())
				throw new Exception('Not allowed in transaction.');
			static::$db->beginTransaction();
			try {
				$sql = static::$db->prepare('UPDATE `Sitemap` SET
					`category` = ?,	`template` = ?,
					`owner` = ?,	`create` = ?,	`modify` = ?,	`meta` = ?,
					`content` = ?,	`aux` =	 ?
				WHERE `URL` = ?');
				$sql->bindValue(1,	$this->category,			PDO::PARAM_STR	);
				$sql->bindValue(2,	static::encodeJSON($this->template),	PDO::PARAM_STR	);
				$sql->bindValue(3,	$this->owner,				PDO::PARAM_STR	);
				$sql->bindValue(4,	$this->create,				PDO::PARAM_INT	);
				$sql->bindValue(5,	$this->modify,				PDO::PARAM_INT	);
				$sql->bindValue(6,	static::encodeJSON($this->meta),	PDO::PARAM_STR	);
				$sql->bindValue(8,	static::encodeJSON($this->aux),		PDO::PARAM_STR	);
				$sql->bindValue(9,	$this->url,				PDO::PARAM_STR	);
				if (strlen($this->content) >= static::Size_FileBlob) {
					$sql->bindValue(7, null, PDO::PARAM_NULL);
				} else {
					$sql->bindValue(7, $this->content, PDO::PARAM_STR);
				}
				$sql->execute();
				if (strlen($this->content) >= static::Size_FileBlob) {
					$this->__file_write();
				} else {
					$this->__file_delete();
				}
				static::$db->commit();
			} catch (Exception $e) {
				static::$db->rollBack();
				throw $e;
			}
			$sql->closeCursor();
		} catch (Exception $e) { throw new BW_DatabaseServerError('Cannot update sitemap database: '.$e->getMessage(), 500); } }

		/** Delete this resource. 
		 * It is not necessary to query this resource, create a dummy resource with url to specify resource in sitemap db. 
		 * This function will use transaction. You should not be in a transaction while call this function. 
		 * @throws BW_DatabaseServerError Fail to delete from sitemap db
		 */
		public function delete(): void { try {
			if (array_key_exists($this->url, static::FixedMap))
				throw new Exception('Cannot modify hard-coded resource.');
			if (static::$db->inTransaction())
				throw new Exception('Not allowed in transaction.');
			static::$db->beginTransaction();
			try {
				$sql = static::$db->prepare('DELETE FROM `Sitemap` WHERE `URL` = ?');
				$sql->bindValue(1,	$this->url,	PDO::PARAM_STR	);
				$sql->execute();
				$this->__file_delete();
				static::$db->commit();
			} catch (Exception $e) {
				static::$db->rollBack();
				throw $e;
			}
			$sql->closeCursor();
		} catch (Exception $e) { throw new BW_DatabaseServerError('Cannot delete blob file from sitemap database: '.$e->getMessage(), 500); } }

		/** Convert into flat filepath. # is not allowed in url (used for tag) but allowed in filename */
		protected static function __file_path(string $url): string { return static::Dir_Resource.str_replace('/', '#', $url); }

		/** Read file-backed resource content into RAM, pass -1 (default) to len for full length */
		protected function __file_read(int $len = -1) {
			$file = get_mangled_object_vars($this)['content'];
			flock($file, LOCK_SH);
			if ($len < 0) {
				fseek($file, 0, SEEK_END);
				$len = ftell($file);
				if ($len === false)
					throw new BW_DatabaseServerError('Cannot get size for blob file: '.$this->url, 500);
			}
			rewind($file);
			$content = fread($file,$len);
			if ($content === false)
				throw new BW_DatabaseServerError('Cannot read from blob file: '.$this->url, 500);
			flock($file, LOCK_UN);
			return $content;
		}
		/** Get file-back resource content size */
		protected function __file_size(): int {
			$file = get_mangled_object_vars($this)['content'];
			flock($file, LOCK_SH);
			fseek($file, 0, SEEK_END);
			$len = ftell($file);
			if ($len === false)
				throw new BW_DatabaseServerError('Cannot get size for blob file: '.$this->url, 500);
			flock($file, LOCK_UN);
			return $len;
		}
		/** Directly dump file-backed resource content to output, pass -1 (default -1) to len for full length, pass true (default false) to header to set Content-Length header */
		protected function __file_dump(int $len = -1, bool $header = false): void {
			$file = get_mangled_object_vars($this)['content'];
			flock($file, LOCK_SH);
			if ($len < 0) {
				fseek($file, 0, SEEK_END);
				$len = ftell($file);
				if ($len === false)
					throw new BW_DatabaseServerError('Cannot get size for blob file: '.$this->url, 500);
			}
			if ($header)
				header('Content-Length: '.$len);
			rewind($file);
			fpassthru($file);
			flock($file, LOCK_UN);
		}

		/** Update a file-backed resource */
		protected function __file_write(): void {
			$file = fopen(static::__file_path($this->url), 'wb');
			flock($file, LOCK_EX);
			rewind($file);
			if (fwrite($file, $this->content) === false)
				throw new BW_DatabaseServerError('Cannot write to blob file: '.$this->url, 500);
			flock($file, LOCK_UN);
			fclose($file);
		}

		/** Delete a file-backed resource. Return true if file not existed or delete success. Note file pointers still in use are still available */
		protected function __file_delete(): void {
			$path = static::__file_path($this->url);
			if (!(is_file($path) ? unlink($path) : true))
				throw new BW_DatabaseServerError('Cannot unlink blob file: '.$this->url, 500);
		}

		public function util_html_head(string $domain = 'https://example.com/', string $sitename = 'Example Site') {
			$meta_title = htmlspecialchars($this->meta['title'] ?? '', ENT_COMPAT);
			$meta_keywords = htmlspecialchars($this->meta['keywords'] ?? '', ENT_COMPAT);
			$meta_description = htmlspecialchars($this->meta['description'] ?? '', ENT_COMPAT);
			echo '<title>',$meta_title,' - ',$sitename,'</title>',
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
			'<link rel="canonical" href="',$domain,$this->url,'" />',
			'<meta property="og:url" content="',$domain,$this->url,'" />',
			( array_key_exists('robots', $this->meta) ? ('<meta name="robots" content="'.htmlspecialchars($this->meta['robots'], ENT_COMPAT).'" />') : '' ),
			( array_key_exists('img', $this->meta) ? ('<meta property="__og:image" content="'.htmlspecialchars($this->meta['img'], ENT_COMPAT).'" />') : '' ),
			( $this->owner ? ('<meta name="author" content="'.htmlspecialchars($this->owner, ENT_COMPAT).'" />') : '' ),
			( array_key_exists('lang-en', $this->meta) ? ('<link rel="alternate" hreflang="en" href="/'.htmlspecialchars($this->aux['lang-en'], ENT_COMPAT).'" type="text/html" />') : '' ),
			( array_key_exists('lang-zh', $this->meta) ? ('<link rel="alternate" hreflang="en" href="/'.htmlspecialchars($this->aux['lang-zh'], ENT_COMPAT).'" type="text/html" />') : '' );
		}

		public function util_html_inplaceEditor() { echo'
			<div style="background:var(--content-bgcolor1)"><form id="editor" onsubmit="event.preventDefault()" dataset-resource=""><h1>Editor</h1>
				<textarea type="text" name="content" id="editor_content" style="width:100%;height:300px;"></textarea>
				<div class="layflat" style="margin-top:1em">
					<button id="editor_reload" type="button">Reload</button>
					<button id="editor_render" type="button">Render</button>
					<button id="editor_submit" type="submit">Submit</button>
				</div>
				<script>
					_(\'#editor_reload\').onclick = async event => { try {
						const resource = await BearAPI_Resource.get(window.location.pathname.substr(1));
						_(\'#editor_content\').value = resource.content;
					dialog_success(200, \'Reloaded\'); } catch (e) { dialog_error(0, \'Reload failed: \' + e); throw e; } };
					_(\'#editor_render\').onclick = event => { try {
						_(\'main\').replaceChildren(...(((src => { // Allows insert into DOM for live view
							const x = (new DOMParser()).parseFromString(src, \'text/html\').body;
							if (_(\'parsererror\', x))
								throw new Error(_(\'parsererror\', x).textContent);
							if (_(\'sourcetext\', x))
								throw new Error(_(\'sourcetext\', x).textContent);
							return x;
						})(_(\'#editor_content\').value)).children));
					dialog_success(200, \'Rendered\'); } catch (e) { dialog_error(0, \'Render failed: \' + e); throw e; } };
					_(\'#editor_submit\').onclick = async event => { try {
						const content = (src => { // Use XML instead of HTML because output is more clean. First child of XML is the inner XML, then get all children
							const x = (new DOMParser()).parseFromString(\'<xml>\' + src + \'</xml>\', \'text/xml\').children[0];
							if (_(\'parsererror\', x))
								throw new Error(_(\'parsererror\', x).textContent);
							if (_(\'sourcetext\', x))
								throw new Error(_(\'sourcetext\', x).textContent);
							return x;
						})(_(\'#editor_content\').value);
						let resource = await BearAPI_Resource.get(window.location.pathname.substr(1));
						const response = await BearAPI_Resource.update({
							url:		resource.url,
							category:	resource.category,
							meta:		JSON.stringify(resource.meta),
							aux:		JSON.stringify(resource.aux),
							content:	new Blob([_(\'#editor_content\').value])
						});
					dialog_success(200, \'Submitted\'); } catch (e) { dialog_error(0, \'Submit failed: \' + e); throw e; } };
				</script>
			</form></div>
		';}
	}


	class _Bearweb_User { use Bearweb_DatabaseBacked;
		final const TIME_CURRENT = -1;		# Pass this parameter to let Bearweb use current timestamp

		/** User ID, PK, 6 to 16 characters [A-Za-z0-9-_] ID must be string (to differentiate from int group), '' for guest */
		public string	$id;
		
		/** User name (nickname) */
		public string	$name;

		/** Salt for password */
		public string	$salt;

		/** Password after salt 32-byte (256-bit) cipher */
		public string	$password;

		/** Register timestamp */
		public int	$registerTime {
			set => $value == self::TIME_CURRENT ? $_SERVER['REQUEST_TIME'] : $value;
		}

		/** Last active timestamp */
		public int	$lastActive {
			set => $value == self::TIME_CURRENT ? $_SERVER['REQUEST_TIME'] : $value;
		}

		/** User group [114, 514, ...], group must be int (to differentiate from string id), group 0 is for admin */
		public array	$group {
			set (string|array $value) => is_string($value) ? (json_decode($value, false) ?? []) : $value;
		}

		/** User data [meta => data...] */
		public array	$data {
			set (string|array $value) => is_string($value) ? (json_decode($value, true) ?? []) : $value;
		}

		/** Create a user object. 
		 * @param string	$id		User ID, PK, 6 to 16 characters [A-Za-z0-9-_] ID must be string (to differentiate from int group). Default '' for guest
		 * @param string	$name		User name (nickname). Default 'Guest' for guest
		 * @param string	$salt		Salt for password. Default ':' invalid
		 * @param string	$password	Password after salt 32-byte (256-bit) cipher. Default ':' invalid
		 * @param int		$registerTime	Register timestamp. Default this::TIME_NULL for no actual time, use this::TIME_CURRENT to use current timestamp
		 * @param int		$lastActive	Last active timestamp. Default this::TIME_NULL for no actual time, use this::TIME_CURRENT to use current timestamp
		 * @param string|array	$group		User group [114, 514, ...], group must be int (to differentiate from string id), group 0 is for admin: JSON or array. Default []
		 * @param string|array	$group		User data [meta => data...]: JSON or array. Default []
		 * @param string	$avatar		User avatar
		*/
		public function __construct(
			string		$id = '',
			string		$name = 'Guest',
			string		$salt = ':',
			string		$password = ':',
			int		$registerTime = self::TIME_CURRENT,
			int		$lastActive = self::TIME_CURRENT,
			string|array	$group = [],
			string|array	$data = [],
			string		$avatar = ''
		) {
			$this->id		= $id;
			$this->name		= $name;
			$this->salt		= $salt;
			$this->password		= $password;
			$this->registerTime	= $registerTime;
			$this->lastActive	= $lastActive;
			$this->group		= $group;
			$this->data		= $data;
		}

		public function isAdmin(): bool { return in_array(0, $this->group); }
		public function isGuest(): bool { return !$this->id; }

		public static function validID(string $uid): bool { return strlen($uid) >= 6 && strlen($uid) <= 16 && ctype_alnum(str_replace(['-', '_'], '', $uid)); } // 6 to 16 characters [A-Za-z0-9-_]
		public static function validPassword(string $pass): bool { return strlen(base64_decode($pass, true)) == 48; } // 48-byte (384-bit) cipher

		final const int QUERY_UPDATE_LASTACTIVE = 0x01;

		/** Query a user. 
		 * @param string	$id	User ID, '' for guest
		 * @param int		$flag	this::QUERY_UPDATE_*
		 * @return ?Bearweb_User	A Bearweb_User instance, or null if user not existed
		 * @throws BW_DatabaseServerError Fail to query user info
		 */
		public static function query(string $id, int $flag = 0): ?static {
			if (!$id) return new static();
			$user = null;
			try {
				$sql = static::$db->prepare('UPDATE `User` SET `lastActive` = IFNULL(?, `lastActive`) WHERE `id` = ? RETURNING *');
				if ($flag & self::QUERY_UPDATE_LASTACTIVE) {
					$sql->bindValue(1, $_SERVER['REQUEST_TIME'], PDO::PARAM_INT);
				} else {
					$sql->bindValue(1, null, PDO::PARAM_NULL);
				}
				$sql->bindValue(2, $id, PDO::PARAM_STR);
				$sql->execute();
				$sql->setFetchMode(PDO::FETCH_CLASS|PDO::FETCH_PROPS_LATE, static::class);
				$user = $sql->fetch();
				$sql->closeCursor();
			} catch (Exception $e) { throw new BW_DatabaseServerError('Cannot query user from DB: '.$e->getMessage(), 500); }
			
			return $user ? $user : null;
		}

		/** Create a new user. 
		 * Internal hash applied to password before write to DB. 
		 * @throws BW_ClientError Non-unique user ID
		 * @throws BW_DatabaseServerError Fail to write user into DB
		 */
		public function insert(): void { try {
			$sql = static::$db->prepare('INSERT INTO `User` (`id`, `name`, `salt`, `password`, `registerTime`, `lastActive`, `group`, `data`) VALUES (?, ?, ?, ?, ?, ?, ?, ?)');
			$sql->bindValue(1,	$this->id,				PDO::PARAM_STR	);
			$sql->bindValue(2,	$this->name,				PDO::PARAM_STR	);
			$sql->bindValue(3,	$this->salt,				PDO::PARAM_STR	);
			$sql->bindValue(4,	$this->password,			PDO::PARAM_STR	);
			$sql->bindValue(5,	$this->registerTime,			PDO::PARAM_INT	);
			$sql->bindValue(6,	$this->lastActive,			PDO::PARAM_INT	);
			$sql->bindValue(7,	static::encodeJSON($this->group),	PDO::PARAM_STR	);
			$sql->bindValue(8,	static::encodeJSON($this->data),	PDO::PARAM_STR	);
			$sql->execute();
			$sql->closeCursor();
		} catch (Exception $e) { throw strpos($e->getMessage(), 'UNIQUE') ? new BW_ClientError('User ID has been used', 409) : new BW_DatabaseServerError('Cannot insert new user into DB: '.$e->getMessage(), 500); } }

		/** Update user data. 
		 * Specify fields to modify, leave other fields null to keep orginal value. 
		 * @throws BW_DatabaseServerError Failed to update user DB (server error or no such user)
		 */
		public function update(): void { try {
			$sql = static::$db->prepare('UPDATE `User` SET
				`name` = ?,
				`salt` = ?,		`password` = ?,
				`registerTime` = ?,	`lastActive` = ?,
				`group` = ?,		`data` = ?
			WHERE `id` = ?');
			$sql->bindValue(1,	$this->name,				PDO::PARAM_STR	);
			$sql->bindValue(2,	$this->salt,				PDO::PARAM_STR	);
			$sql->bindValue(3,	$this->password,			PDO::PARAM_STR	);
			$sql->bindValue(4,	$this->registerTime,			PDO::PARAM_INT	);
			$sql->bindValue(5,	$this->lastActive,			PDO::PARAM_INT	);
			$sql->bindValue(6,	static::encodeJSON($this->group),	PDO::PARAM_STR	);
			$sql->bindValue(7,	static::encodeJSON($this->data),	PDO::PARAM_STR	);
			$sql->bindValue(8,	$this->id,				PDO::PARAM_STR	);
			$sql->execute();
			$sql->closeCursor();
		} catch (Exception $e) { throw new BW_DatabaseServerError('Fail to update user in DB: '.$e->getMessage(), 500); } }
	}


	class _Bearweb_Session { use Bearweb_DatabaseBacked;
		const CookieSID = 'BW_SessionID';	# Client-side cookie name for session ID, visible to client-side JS
		const CookieKey = 'BW_SessionKey';	# Client-side cookie name for session key, non-visible to client-side JS to prevent XSS
		const Expire = 7 * 24 * 3600;		# Session expire time in seconds

		final const TIME_CURRENT = -1;	# Pass this parameter to let Bearweb use current timestamp
		final const TIME_NULL = 0;		# Some resource (like auto generated one) has no create / modify time

		public readonly string	$sID;		# Session ID
		public readonly int	$sCreate;	# Session create time
		public readonly int	$sLastUse;	# Session last use time (always current timestamp)
		public readonly string	$sUser;		# Session user ID
		public readonly string	$sKey;		# Session key for client-side JS
		public readonly string	$tID;		# Transaction ID
		public readonly int	$tCreate;	# Transaction create time (always current timestamp)
		public readonly string	$tIP;		# Transaction client IP and port
		public readonly string	$tURL;		# Transaction request URL
		public readonly string	$tSID;		# Transaction assoicated session ID (same as sID)
		public string		$tLog;		# Transaction log

		const KEY_LENGTH = 48; # 64 char
		private static function keygen(): string { return base64_encode(random_bytes(static::KEY_LENGTH)); }
		private static function keycheck(string|array $data, string $key = ''): bool { return strlen(base64_decode( is_array($data) ? ($data[$key] ?? '') : ($data ?? '') , true)) == static::KEY_LENGTH; }

		private static function insertRetry(int $retry, callable $fn, Exception $err): void {
			for (; $retry; $retry--) {
				try {
					$fn();
					return;
				} catch (Exception $e) {
					if (strpos($e->getMessage(), 'UNIQUE') === false) throw $e;
				}
			} throw $err;
		}

		/** Constructor. 
		 * Do NOT use! This method is for Bearweb framework use ONLY. 
		 * Read client-side cookies, format check, create/bind session, record transaction, write cookie to client. 
		 * @param string $url Request URL
		 * @throws BW_DatabaseServerError Cannot write session / transaction into DB
		*/
		public function __construct(string $url) { try {
			if (!static::$db->beginTransaction()) { throw new Exception ('Cannot start transaction'); }

			$session = null;
			$transaction = null;
			$needToSendSessionCookie = false;

			// Update session if session cookies are valid, found and matched, not expired in db
			if ( static::keycheck($_COOKIE, static::CookieSID) && static::keycheck($_COOKIE, static::CookieKey) ) {
				$sql = static::$db->prepare('UPDATE `Session` SET `LastUse` = ? WHERE `ID` = ? AND `Key` = ? AND `LastUse` > ? RETURNING `ID`, `Create`, `LastUse`, `User`, `Key`');
				$sql->bindValue(	1,	$_SERVER['REQUEST_TIME'],			PDO::PARAM_INT	);
				$sql->bindValue(	2,	$_COOKIE[static::CookieSID],			PDO::PARAM_STR	);
				$sql->bindValue(	3,	$_COOKIE[static::CookieKey],			PDO::PARAM_STR	);
				$sql->bindValue(	4,	$_SERVER['REQUEST_TIME'] - static::Expire,	PDO::PARAM_INT	);
				$sql->execute();
				$session = $sql->fetch();
				$sql->closeCursor();
			}

			// Create a new session otherwise
			if (!$session) {
				$sid = '';
				$sql = static::$db->prepare('INSERT INTO `Session` (`ID`, `Create`, `LastUse`, `User`, `Key`) VALUES (?,?,?,?,?) RETURNING `ID`, `Create`, `LastUse`, `User`, `Key`');
				$sql->bindParam(	1,	$sid,				PDO::PARAM_STR	); #By reference
				$sql->bindValue(	2,	$_SERVER['REQUEST_TIME'],	PDO::PARAM_INT	);
				$sql->bindValue(	3,	$_SERVER['REQUEST_TIME'],	PDO::PARAM_INT	);
				$sql->bindValue(	4,	'',				PDO::PARAM_STR	); #New session is always issused to new user, it can only be changed by User Login API with existed SID
				$sql->bindValue(	5,	static::keygen(),			PDO::PARAM_STR	);
				static::insertRetry(5, function() use (&$sid, $sql) { $sid = static::keygen(); $sql->execute(); }, new Exception('Retry too much for session ID'));
				$session = $sql->fetch();
				$sql->closeCursor();
				$needToSendSessionCookie = true;
			}

			// Create a new transaction
			$tid = '';
			$sql = static::$db->prepare('INSERT INTO `Transaction` (`ID`, `Create`, `IP`, `URL`, `Session`, `Log`) VALUES (?,?,?,?,?,?) RETURNING `ID`, `Create`, `IP`, `URL`, `Session`, `Log`');
			$sql->bindParam(	1,	$tid,							PDO::PARAM_STR	); #By reference
			$sql->bindValue(	2,	$_SERVER['REQUEST_TIME'],				PDO::PARAM_INT	);
			$sql->bindValue(	3,	$_SERVER['REMOTE_ADDR'].':'.$_SERVER['REMOTE_PORT'],	PDO::PARAM_STR	);
			$sql->bindValue(	4,	$url,							PDO::PARAM_STR	);
			$sql->bindValue(	5,	$session['ID'],						PDO::PARAM_STR	); #New session is always issused to new user, it can only be changed by User Login API with existed SID
			$sql->bindValue(	6,	'PID: '.getmypid().PHP_EOL,				PDO::PARAM_STR	);
			static::insertRetry(5, function() use (&$tid, $sql) { $tid = static::keygen(); $sql->execute(); }, new Exception('Retry too much for transaction ID'));
			$transaction = $sql->fetch();
			$sql->closeCursor();

			$this->sID	= $session['ID'];
			$this->sCreate	= $session['Create'];
			$this->sLastUse	= $session['LastUse'];
			$this->sUser	= $session['User'];
			$this->sKey	= $session['Key'];
			$this->tID	= $transaction['ID'];
			$this->tCreate	= $transaction['Create'];
			$this->tIP	= $transaction['IP'];
			$this->tURL	= $transaction['URL'];
			$this->tSID	= $transaction['Session'];
			$this->tLog	= $transaction['Log'];
			if ($needToSendSessionCookie) {
				setcookie(static::CookieSID, $this->sID, 0, '/', '', true, true ); # Note: Send cookie to client only if DB write success
				setcookie(static::CookieKey, $this->sKey, 0, '/', '', true, false );
			}

			if (!static::$db->commit()) { throw new Exception ('Cannot commit transaction'); }
		} catch (Exception $e) { static::$db->rollBack(); throw new BW_DatabaseServerError('Cannot record session control in DB: '.$e->getMessage(), 500); } } # Cannot do anything if rollback fails :(

		/** Append log to transaction record. 
		 * @param string	$log	Log to append
		 * @return string	Log in full after append
		 */
		public function log(string $log): string { return $this->tLog .= $log.PHP_EOL; }

		/** Bind a user to the session. Effects next transaction. 
		 * @param string	$uid	User ID
		 * @throws BW_DatabaseServerError Cannot update session user in DB
		 */
		public function bindUser(string $uid): void {
			try {
				$sql = static::$db->prepare('UPDATE `Session` SET `User` = ? WHERE ID = ?');
				$sql->bindValue(	1,	$uid,		PDO::PARAM_STR	);
				$sql->bindValue(	2,	$this->sID,	PDO::PARAM_STR	);
				$sql->execute();
				$sql->closeCursor();
			} catch (Exception $e) { throw new BW_DatabaseServerError('Cannot update session user in DB: '.$e->getMessage(), 500); }
		}

		/** Update session key on both server-side and client-side. Effects next transaction. 
		 * @return string	New session key
		 * @throws BW_DatabaseServerError Cannot update session key in DB
		 */
		public function updateKey(): string {
			$key = static::keygen();
			try {
				$sql = static::$db->prepare('UPDATE `Session` SET `Key` = ? WHERE ID = ?');
				$sql->bindValue(	1,	$key,		PDO::PARAM_STR	);
				$sql->bindValue(	2,	$this->sID,	PDO::PARAM_STR	);
				$sql->execute();
				$sql->closeCursor();
			} catch (Exception $e) { throw new BW_DatabaseServerError('Cannot update session user in DB: '.$e->getMessage(), 500); }
			setcookie(static::CookieKey, $key, 0, '/', '', true, false );
			return $key;
		}

		/** Destructor. 
		 * Do NOT use! This method is for Bearweb framework use ONLY. Automatically execute when PHP process finished. 
		 * Commit log to DB. 
		*/
		public function __destruct() {
			$sql = static::$db->prepare('UPDATE `Transaction` SET `Log` = ?, `Status` = ?, `Time` = ?, `Memory` = ? WHERE ID = ?');
			$sql->bindValue(	1,	$this->tLog,							PDO::PARAM_STR	);
			$sql->bindValue(	2,	http_response_code(),						PDO::PARAM_INT	);
			$sql->bindValue(	3,	(microtime(true) - $_SERVER["REQUEST_TIME_FLOAT"]) * 1e6,	PDO::PARAM_INT	);
			$sql->bindValue(	4,	memory_get_peak_usage(false) / 1024,				PDO::PARAM_INT	);
			$sql->bindValue(	5,	$this->tID,							PDO::PARAM_STR	);
			$sql->execute();
			$sql->closeCursor();
		}
	}

	
	set_error_handler(function($no, $str, $file, $line){ if (error_reporting() == 0) { return false; } throw new ErrorException($str, 0, $no, $file, $line); });
	class BW_Error extends Exception { function __construct(string $msg, int $code = 0) { parent::__construct( get_class($this).' - '.$msg , $code ); } } # Base Bearweb error
	class BW_ServerError extends BW_Error {}		# Base server-end error
	class BW_WebServerError extends BW_ServerError{}	# Server-side front-end error: PHP script error
	class BW_DatabaseServerError extends BW_ServerError{}	# Server-side back-end error: Database (local) error
	class BW_ExternalServerError extends BW_ServerError{} 	# Server-side cloud-end error: External server error, such as token server, object storage server...
	class BW_ClientError extends BW_Error {}		# Client-side error: Bad request from client

	trait Bearweb_DatabaseBacked{
		/** Database connection resource */
		public static PDO $db;

		/** Init module by connect its database. 
		 * User should override this method to set DB dir, user and password, using try { static::$db = new PDO(...); } catch () { throw new BW_DatabaseServerError(...); }
		 * Do NOT call this method. This method should only be called by Bearweb framework for each module at the loading time. 
		 * @throws BW_DatabaseServerError Fail to open db
		 */
		public static function init(): void {
			throw new BW_DatabaseServerError('No database defined', 500);
		}

		/** Use {} for object and [] for array, '' for empty data */
		public static function encodeJSON(array $x) { return count($x) ? ( array_is_list($x) ? json_encode((array)$x) : json_encode((object)$x) ) : ''; }
	}

	class BearIndex {
		protected Bearweb_Site $site;
		public function __construct(string $url, string $category, array $template, array $meta, string $prepend, array $aux) {
			$this->site = new Bearweb_Site(url: $url, category: $category, template: $template, owner: '', create: Bearweb_Site::TIME_NULL, modify: Bearweb_Site::TIME_CURRENT, meta: $meta, content: $prepend, aux: $aux);
		}
		/** Return an indication string if access controlled, redirected, or noindex / nofollow.
		 * @param array $r The resource to check, directly read from database in array
		 * @return string 'access', 'r301', 'r302', 'robots' for do not index, or '' otherwise
		*/
		public static function dontIndex(array $r): string {
			foreach(['access', 'r301', 'r302', 'robots'] as $robot) {
				if (array_key_exists($robot, $r['meta'])) {
					return $robot;
				}
			}
			return '';
		}
		public function update() { $this->site->update(); }
	}
	class BearIndex_Bulletin extends BearIndex {
		protected ?array $lastAddResource = null;
		protected function divider(?array $resourceLast, array $resourceCurrent): void { return; /* if ($resourceLast != null && $resourceLast['url'] != $resourceCurrent['url']) $this->site->content .= ''; */ }
		public function __construct(string $url, array $template, array $meta, array $aux) { parent::__construct($url, 'Bulletin', $template, $meta, '', $aux); }
		public function add(array $r): bool {
			$this->divider($this->lastAddResource, $r);
			$url		= htmlspecialchars($r['url'], ENT_COMPAT);
			$title		= htmlspecialchars($r['meta']['title'] ?? $r['url'], ENT_COMPAT);
			$description	= htmlspecialchars($r['meta']['description'] ?? '', ENT_COMPAT);
			$thumb		= htmlspecialchars($r['meta']['thumb'] ?? '', ENT_COMPAT);
			$ratio		= $r['meta']['ratio'] ?? 1;
			$this->site->content .= '<a href="/'.$url.'" style="aspect-ratio:'.($ratio).'"><img src="/'.$thumb.'" title="'.$title.'" alt="'.$description.'" loading="lazy" /></a>';
			$this->lastAddResource = $r;
			return true;
		}
	}
	class BearIndex_Catalog extends BearIndex {
		public function __construct(string $url, array $template, array $meta, array $aux) { parent::__construct($url, 'Catalog', $template, $meta, '', $aux); }
		public function add(array $r): bool {
			$url		= htmlspecialchars($r['url'], ENT_COMPAT);
			$img		= htmlspecialchars($r['meta']['img'] ?? '', ENT_COMPAT);
			$title		= htmlspecialchars($r['meta']['title'] ?? $r['url'], ENT_COMPAT);
			$description	= htmlspecialchars($r['meta']['description'] ?? '', ENT_COMPAT);
			$keywords	= htmlspecialchars($r['meta']['keywords'] ?? '', ENT_COMPAT);
			$owner		= htmlspecialchars($r['owner'], ENT_COMPAT);
			$modify		= date('M j, Y',$r['modify']);
			$this->site->content .= '<a href="/'.$url.'" style="--bgimg:url(/'.$img.')"><h2>'.$title.'</h2><p>'.$description.'</p><p class="content_keywords">'.$keywords.'</p><p><i>--by '.$owner.' @ '.$modify.'</i></p></a>';
			return true;
		}
	}
	class BearIndex_User extends BearIndex  {
		public function __construct(string $url, array $meta) { parent::__construct($url, 'Index', ['object', 'blob'], $meta, '<?xml version="1.0" encoding="UTF-8" ?><resourceset>', ['mime' => 'text/xml']); }
		
		public function add(array $r): bool {
			$url		= htmlspecialchars($r['url'], ENT_COMPAT);
			$category	= htmlspecialchars($r['category'], ENT_COMPAT);
			$create		= htmlspecialchars($r['create'], ENT_COMPAT); // Should be number, but in case DB manually modified, it may hold string 
			$modify		= htmlspecialchars($r['modify'], ENT_COMPAT);
			$title		= htmlspecialchars($r['meta']['title'] ?? $r['url'], ENT_COMPAT);
			$robots		= static::dontIndex($r); // No special characters in ['access', 'r301', 'r302', 'robots', '']
			$owner		= htmlspecialchars($r['owner'], ENT_COMPAT);
			$this->site->content .= '<resource><url>'.$url.'</url><category>'.$category.'</category><create>'.$create.'</create><modify>'.$modify.'</modify><title>'.$title.'</title><robots>'.$robots.'</robots><owner>'.$owner.'</owner></resource>';
			return true;
		}
		public function update() {
			$this->site->content .= '</resourceset>';
			parent::update();
		}
	}
	class BearIndex_SitemapRss extends BearIndex {
		protected string $domain;
		public function __construct(string $url, array $meta, string $domain, string $title, string $description, string $copyright, string $email) {
			$this->domain = htmlspecialchars($domain, ENT_COMPAT);
			parent::__construct(
			$url, 'Index', ['object', 'blob'], $meta,
			'<?xml version="1.0" encoding="UTF-8" ?><rss version="2.0"><channel>'.
			'<title>'.htmlspecialchars($title,ENT_COMPAT).'</title><link>'.$this->domain.'</link><description>'.htmlspecialchars($description,ENT_COMPAT).'</description><copyright>'.htmlspecialchars($copyright,ENT_COMPAT).'</copyright><generator>Bearweb</generator>'.
			'<image><link>'.$this->domain.'</link><title>'.htmlspecialchars($title,ENT_COMPAT).'</title><url>'.$this->domain.'favicon.ico</url></image>'.
			'<lastBuildDate>'.date(DATE_RSS,$_SERVER['REQUEST_TIME']).'</lastBuildDate><webMaster>'.htmlspecialchars($email,ENT_COMPAT).'</webMaster>',
			['mime' => 'text/xml']
			);
		}
		public function add(array $r): bool {
			$title		= htmlspecialchars($r['meta']['title'] ?? $r['url'], ENT_COMPAT);
			$url		= htmlspecialchars($r['url'], ENT_COMPAT);
			$owner		= htmlspecialchars($r['owner'], ENT_COMPAT);
			$category	= htmlspecialchars($r['category'], ENT_COMPAT);
			$description	= htmlspecialchars($r['meta']['description'] ?? '', ENT_COMPAT);
			$modify		= date(DATE_RSS,$r['modify']);
			$this->site->content .= '<item><title>'.$title.'</title><link>'.$this->domain.$url.'</link><guid>'.$this->domain.$url.'</guid><author>'.$owner.'</author><category>'.$category.'</category><description>'.$description.'</description><pubDate>'.$modify.'</pubDate></item>';
			return true;
		}
		public function update() {
			$this->site->content .= '</channel></rss>';
			parent::update();
		}
	}
	class BearIndex_SitemapTxt extends BearIndex {
		protected string $domain;
		public function __construct(string $url, array $meta, string $domain) {
			$this->domain = htmlspecialchars($domain, ENT_COMPAT);
			parent::__construct($url, 'Index', ['object', 'blob'], $meta, '', ['mime' => 'text/plain']);
		}
		public function add(array $r): bool {
			if (static::dontIndex($r)) return false;
			$this->site->content .= $this->domain.$r['url'].PHP_EOL;
			return true;
		}
	}
	class BearIndex_SitemapXml extends BearIndex {
		protected string $domain;
		public function __construct(string $url, array $meta, string $domain) {
			$this->domain = htmlspecialchars($domain, ENT_COMPAT);
			parent::__construct(
				$url, 'Index', ['object', 'blob'], $meta,
				'<?xml version="1.0" encoding="UTF-8" ?><urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">',
				['mime' => 'text/xml']
			);
		}
		public function add(array $r): bool {
			if (static::dontIndex($r)) return false;
			$url		= htmlspecialchars($r['url'], ENT_COMPAT);
			$modify		= date(DATE_W3C,$r['modify']);
			$this->site->content .= '<url><loc>'.$this->domain.$url.'</loc><lastmod>'.$modify.'</lastmod></url>';
			return true;
		}
		public function update() {
			$this->site->content .= '</urlset>';
			parent::update();
		}
	}
	function _bear_reindex(array $index) {
		foreach(Bearweb_Site::$db->query('SELECT `url`, `category`, `owner`, `create`, `modify`, `meta` FROM `Sitemap` ORDER BY `modify` DESC') as $r) { // Note: to reduce system load, we will work with raw data
			$r['meta'] = json_decode($r['meta'], true) ?? [];
			foreach ($index as $x) $x->add($r);
		}
		foreach ($index as $x) $x->update($r);
	}
?>