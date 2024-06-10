<?php	header('X-Powered-By: Bearweb 7.1.240609');

	class Bearweb {
		/** User supplied data format check. 
		 * @param string $type Data type
		 * @param string|array $data User supplied data
		 * @param string $key If $data is an array, select the element using this key
		 * @return bool True if format OK, false is bad format or required key not in data array
		 */
		public static function check(string $type, string|array $data, string $key = ''): bool {
			if (is_array($data)) {
				$data = $data[$key] ?? null; # Get null if not exist and return null on is_string
				if (!is_string($data)) return false;
			}
			switch ($type) {
				case 'Always':		return true;
				case 'Never':		return false;

				case 'MD5':		return strlen($data) == 32 && ctype_xdigit($data);
			
				case 'URL':		return $data === '' || ( strlen($data) <= 128 && ctype_alnum( str_replace(['-', '_', ':', '/', '.'], '', $data) ) );

				case 'UserID':		return strlen($data) >= 6 && strlen($data) <= 16 && ctype_alnum( str_replace(['-', '_'], '', $data) ); # Return false for guest ('')
				case 'UserName':	return strlen($data) >= 2 && strlen($data) < 16;
				case 'UserPassword':	return self::check('MD5', $data);
				default:		return false;
			}
		}

		private Bearweb_Session $session;
		private Bearweb_User $user;
		private Bearweb_Site $site;

		/** Use Bearweb to server a resource. 
		 * This function will init the Site module and the Session module. 
		 * This function will invoke the error template if the given URL fail the URL format check or upon error in framework and template execution.
		 * @param string $url Request URL given by client
		 */
		public function __construct(string $url) { $url = Bearweb::check('URL', $url) ? $url : '';
			$BW = $this;

			try {
				Bearweb_Site::init(BW_Config::Site_DB);
				Bearweb_User::init(BW_Config::User_DB);
				Bearweb_Session::init(BW_Config::Session_DB);
				$this->session = new Bearweb_Session($url);
			} catch (Exception $e) {
				error_log('[BW] Fatal error: Module init failed - Cannot connect to DB: '.$e->getMessage());
				http_response_code(500); exit('500 - Server Fatal Error!');
			}
			
			try { ob_start();
				$this->user = Bearweb_User::query($this->session->sUser);
				$this->site = Bearweb_Site::query($url);
				[$stateFlag, $stateInfo] = [substr($this->site->state, 0, 1), substr($this->site->state, 1)];

				// Access control
				if (!$this->site->access($this->user)) {
					if ($stateFlag == 'A') {
						throw BW_Config::Site_HideAuthError ? new BW_ClientError('Not found', 404) : new BW_ClientError('Unauthorized: Controlled resource. Cannot access, please login first', 401);
					} else if ($stateFlag == 'P') {
						throw BW_Config::Site_HideAuthError ? new BW_ClientError('Not found', 404) : new BW_ClientError('Forbidden: Locked resource. Access denied', 403);
					}
					throw new BW_ClientError('Access denied', 403);
				}

				// Redirect resource
				if ( $stateFlag == 'R' ) {
					header('Location: /'.$stateInfo);
					throw new BW_ClientError('301 - Moved Permanently: Resource has been moved permanently to: '.$stateInfo, 301); # To terminate the resource processing
				} else if ( $stateFlag == 'r' ) {
					header('Location: /'.$stateInfo);
					throw new BW_ClientError('302 - Moved Temporarily: Resource has been moved temporarily to: '.$stateInfo, 302);
				}

				// E-tag
				if ($this->site->modify == Bearweb_Site::TIME_NULL) { # Create E-tag for content and auto generated content (and error page), for client-side cache
					header('Last-Modified: '.date('D, j M Y G:i:s').' GMT');
					header('Etag: '.base64_encode(random_bytes(48))); 
					header('Cache-Control: no-store');
				/*} else if ( $_SERVER['HTTP_IF_NONE_MATCH'] ?? false && $_SERVER['HTTP_IF_NONE_MATCH'] == base64_encode($this->site->url).':'.base64_encode($this->site->modify) ) { # Client cache is good
					throw new BW_ClientError('304 - Not Modified', 304);*/
				} else {
					header('Last-Modified: '.date('D, j M Y G:i:s', $this->site->modify).' GMT');
					header('Etag: '.base64_encode($this->site->url).':'.base64_encode($this->site->modify));
					header('Cache-Control: private, max-age=3600');
				}
				
				// Invoke template
				$template = BW_Config::Site_TemplateDir.$this->site->template[0].'.php';
				if (!file_exists($template))
					throw new BW_ServerError('Template not found: '.$this->site->template[0], 500);
				include $template;

			} catch (Exception $e) { ob_clean(); ob_start();
				if ($e instanceof BW_ClientError) {
					$this->site = Bearweb_Site::createErrorPage($e->getCode().' - Client Error', $e->getMessage(), $e->getCode());
				} else if ($e instanceof BW_ServerError) {
					error_log('[BW] Server Error: '.$e);
					$this->site = BW_Config::Site_HideServerError ? Bearweb_Site::createErrorPage('500 - Internal Error', 'Server-side internal error.', 500) : Bearweb_Site::createErrorPage($e->getCode().' - Server Error', $e->getMessage(), $e->getCode());
				} else {
					error_log('[BW] Unknown Error: '.$e);
					$this->site = BW_Config::Site_HideServerError ? $this->site = Bearweb_Site::createErrorPage('500 - Internal Error', 'Server-side internal error.', 500) : $this->site = Bearweb_Site::createErrorPage('500 - Unknown Error', $e->getMessage(), 500);
				}
				$template = BW_Config::Site_TemplateDir.'page.php';
				include $template;
			}
		}
	}


	class Bearweb_Site { use Bearweb_DatabaseBacked;
		const TIME_CURRENT = -1;	# Pass this parameter to let Bearweb use current timestamp
		const TIME_NULL = 0;		# Some resource (like auto generated one) has no create / modify time

		public function __construct(
			public readonly string	$url,			# Resource URL, PK, Unique, Not Null
			public readonly ?string	$category	= null,	# Resource category, for management purpose
			public readonly ?array	$template	= null,	# Template used to process the given resourc
			public readonly ?string	$owner		= null,	# Owner's user ID of the given resource; Only the owner and user in "ADMIN" group can modify this resource
			public readonly ?int	$create		= null,	# Create timestamp
			public readonly ?int	$modify		= null,	# Modify timestamp
			public readonly ?string	$title		= null,	# Resource HTML meta info: title
			public readonly ?string	$keywords	= null,	# Resource HTML meta info: keywords
			public readonly ?string	$description	= null,	# Resource HTML meta info: description
			public readonly ?string	$state		= null,	# State of the given resource
			public readonly ?string	$content	= null,	# Resource content, the content should be directly output to reduce server process load
			public readonly ?array	$aux		= null	# Resource auxiliary data, template defined data array
		) {}

		/** Constructe an error page template. 
		 * Do NOT use! This method is for Bearweb framework use ONLY. 
		 * @param string $title		Error page title
		 * @param string $detail	Detail info about the error
		 * @param int $code		If not 0, HTTP code will be set and send to client
		 * @return Bearweb_Site		A Bearweb site resource
		*/
		public static function createErrorPage(string $title, string $detail, int $code = 0): static {
			if ($code) http_response_code($code);
			return new static(
				url: '', category: '', template: ['page', 'error'],
				owner: '', create: self::TIME_NULL, modify: self::TIME_NULL,
				title: $title, keywords: '', description: $detail, state: 'S', content: $detail, aux: []
			);
		}

		/** Query a resource from sitemap db. 
		 * Note: Data in DB is volatile, instance only reflects the data at time of DB fetch, it may be changed by another transaction (e.g. Resource modify API) and other process. 
		 * @param string $url			Resource URL
		 * @return Bearweb_Site			A Bearweb site resource
		 * @throws BW_DatabaseServerError	Cannot read sitemap DB
		 * @throws BW_ClientError		Resource not found in sitemap DB (HTTP 404)
		*/
		public static function query(string $url): static {
			$site = null;
			try {
				$sql = self::$db->prepare('SELECT `Category`, `Template`, `Owner`, `Create`, `Modify`, `Title`, `Keywords`, `Description`, `State`, `Content`, `Aux` FROM `Sitemap` WHERE URL = ?');
				$sql->bindValue(	1,	$url,	PDO::PARAM_STR	);
				$sql->execute();
				$site = $sql->fetch();
				$sql->closeCursor();
			} catch (Exception $e) {
				error_log('[BW] Cannot read sitemap DB: '.$e->getMessage());
				throw new BW_DatabaseServerError('Cannot read sitemap database: '.$e->getMessage(), 500);
			}
			if (!$site) {
				throw new BW_ClientError('Not Found', 404);
			}

			return new static(
				url:		$url,
				category:	$site['Category']	?? '',
				template:	$site['Template']	?  explode('/', $site['Template'])	: ['object', 'blob'],
				owner:		$site['Owner']		?? '',
				create:		$site['Create']		?? self::TIME_NULL,
				modify:		$site['Modify']		?? self::TIME_NULL,
				title:		$site['Title']		?? '',
				keywords:	$site['Keywords']	?? '',
				description:	$site['Description']	?? '',
				state:		$site['State']		?? 'S',
				content:	$site['Content']	?? '',
				aux:		json_decode($site['Aux'] ?? '{}', true) ?? []
			);
		}

		/** Test user access privilege level. 
		 * @param Bearweb_User $user Bearweb_User object with user ID and group
		 * @return int 0 = No access; 1 = read/execute; -1 = read/execute/write (owner/admin)
		 */
		public function access(Bearweb_User $user): int {
			if ( !$user->isGuest() && ($user->isAdmin() || $user->id==$this->owner) ) return -1; # Note: Admin always have privilege, guest never have privilege; system resource is owned by '' but '' means guest in Bearweb_User
			[$stateFlag, $stateInfo] = [substr($this->state, 0, 1), substr($this->state, 1)];
			return (
				$stateFlag == 'P' ||										# Pending resource: owner only
				( $stateFlag == 'A' && !count(array_intersect( $user->group , explode(',',$stateInfo) )) )	# Auth control: check user groups against privilege groups
			) ? 0 : 1;
		}

		/** Insert this resource into sitemap db.
		 * @throws BW_DatabaseServerError Fail to insert into sitemap db
		 */
		public function insert(): void { try {
			$current = $_SERVER['REQUEST_TIME'];
			$sql = self::$db->prepare('INSERT INTO `Sitemap` (
				`URL`, `Category`, `Template`,
				`Owner`, `Create`, `Modify`, `Title`, `Keywords`, `Description`,
				`State`, `Content`, `Aux`
			) VALUES (	?, ?, ?,	?, ?, ?, ?, ?, ?,	?, ?, ?)');
			$sql->bindValue(1,	$this->url,										PDO::PARAM_STR	);	
			$sql->bindValue(2,	$this->category			?? '',							PDO::PARAM_STR	);
			$sql->bindValue(3,	implode('/', $this->template ?? ['object', 'blob']),					PDO::PARAM_STR	);
			$sql->bindValue(4,	$this->owner			?? '',							PDO::PARAM_STR	);
			$sql->bindValue(5,	self::__vmap($this->create, [[null, $current], [self::TIME_CURRENT, $current]]),	PDO::PARAM_INT	);
			$sql->bindValue(6,	self::__vmap($this->modify, [[null, $current], [self::TIME_CURRENT, $current]]),	PDO::PARAM_INT	);
			$sql->bindValue(7,	$this->title			?? '',							PDO::PARAM_STR	);
			$sql->bindValue(8,	$this->keywords			?? '',							PDO::PARAM_STR	);
			$sql->bindValue(9,	$this->description		?? '',							PDO::PARAM_STR	);
			$sql->bindValue(10,	$this->state			?? 'S',							PDO::PARAM_STR	);
			$sql->bindValue(11,	$this->content			?? '',							PDO::PARAM_STR	);
			$sql->bindValue(12,	$this->aux			?? '{"mime":"text/plain"}',				PDO::PARAM_STR	);
			$sql->execute();
			$sql->closeCursor();
		} catch (Exception $e) { throw new BW_DatabaseServerError('Cannot insert into sitemap database: '.$e->getMessage(), 500); } }

		/** Update this resource in sitemap db. 
		 * It is not necessary to query this resource, create a dummy resource with url and fields to modify (leave other field null to keep orginal data). 
		 * @throws BW_DatabaseServerError Fail to update sitemap db
		 */
		public function update(): void { try {
			$current = $_SERVER['REQUEST_TIME'];
			$sql = self::$db->prepare('UPDATE `Sitemap` SET
				`Category` =	IFNULL(?, `Category`),
				`Template` =	IFNULL(?, `Template`),
				`Owner` =	IFNULL(?, `Owner`),
				`Create` =	IFNULL(?, `Create`),
				`Modify` =	IFNULL(?, `Modify`),
				`Title` =	IFNULL(?, `Title`),
				`Keywords` =	IFNULL(?, `Keywords`),
				`Description` =	IFNULL(?, `Description`),
				`State` =	IFNULL(?, `State`),
				`Content` =	IFNULL(?, `Content`),
				`Aux` =		IFNULL(?, `Aux`)
			WHERE `URL` = ?');
			$sql->bindValue(1,	$this->category,								PDO::PARAM_STR	);
			$sql->bindValue(2,	is_null($this->template) ? null : implode('/', $this->template),		PDO::PARAM_STR	);
			$sql->bindValue(3,	$this->owner,									PDO::PARAM_STR	);
			$sql->bindValue(4,	self::__vmap($this->create, [[null, null], [self::TIME_CURRENT, $current]]),	PDO::PARAM_INT	);
			$sql->bindValue(5,	self::__vmap($this->modify, [[null, null], [self::TIME_CURRENT, $current]]),	PDO::PARAM_INT	);
			$sql->bindValue(6,	$this->title,									PDO::PARAM_STR	);
			$sql->bindValue(7,	$this->keywords,								PDO::PARAM_STR	);
			$sql->bindValue(8,	$this->description,								PDO::PARAM_STR	);
			$sql->bindValue(9,	$this->state,									PDO::PARAM_STR	);
			$sql->bindValue(10,	$this->content,									PDO::PARAM_STR	);
			$sql->bindValue(11,	is_null($this->aux) ? null : json_encode($this->aux),				PDO::PARAM_STR	);
			$sql->bindValue(12,	$this->url,									PDO::PARAM_STR	);
			$sql->execute();
			$sql->closeCursor();
		} catch (Exception $e) { throw new BW_DatabaseServerError('Cannot update sitemap database: '.$e->getMessage(), 500); } }

		/** Delete this resource. 
		 * It is not necessary to query this resource, create a dummy resource with url to specify resource in sitemap db. 
		 * @throws BW_DatabaseServerError Fail to delete from sitemap db
		 */
		public function delete(): void { try {
			$sql = self::$db->prepare('DELETE FROM `Sitemap` WHERE `URL` = ?');
			$sql->bindValue(1,	$this->url,	PDO::PARAM_STR	);
			$sql->execute();
			$sql->closeCursor();
		} catch (Exception $e) { throw new BW_DatabaseServerError('Cannot delete from sitemap database: '.$e->getMessage(), 500); } }
	}


	class Bearweb_User { use Bearweb_DatabaseBacked;
		public function __construct(
			public readonly string	$id,
			public readonly ?string	$name		= null,
			public readonly ?string	$password	= null,
			public readonly ?int	$registerTime	= null,
			public readonly ?array	$group		= null,
			public readonly mixed	$data		= null
		) {}

		/** Query a user. 
		 * In case of error or user control disabled, or the user ID is default value (''), this routine fallback silently and uses default values ('' or 0 or [], all gives false). 
		 * If error, it will be write to system log. 
		 * @param string	$id	User ID
		 * @return Bearweb_User	A Bearweb_User instance
		 */
		public static function query(string $id): static {
			if (!self::$db || !$id) return new static(id: '', name: '', password: '', registerTime: 0, group: [], data: []);

			$user = null;
			try {
				$sql = self::$db->prepare('SELECT `ID`, `Name`, `Password`, `RegisterTime`, `Group`, `Data` FROM `User` WHERE `ID` = ?');
				$sql->bindValue(	1,	$id,	PDO::PARAM_STR	);
				$sql->execute();
				$user = $sql->fetch();
				$sql->closeCursor();
			} catch (Exception $e) {
				error_log('[BW] Cannot query user from DB: '.$e->getMessage());
				return new static(id: '', name: '', password: '', registerTime: 0, group: [], data: []);
			}
			if (!$user) return new static(id: '', name: '', password: '', registerTime: 0, group: [], data: []);

			return new static(
				id:		$id,
				name:		$user['Name']		?? '',
				password:	$user['Password']	?? '',
				registerTime:	$user['RegisterTime']	?? 0,
				group:		explode(',', $user['Group'] ?? ''),
				data:		json_decode($user['Data'] ?? '{}', true)
			);
		}

		/** Verify user supplied password. 
		 * If user control is disabled or not ready, or the user ID is default value (''), this method does nothing and returns false. 
		 * This method will try to upgrade password hash if required. DB access error is silently ignored. This error will be write to system log. 
		 * @param string	$uPass	User supplied password
		 * @return bool		True for correct password, false for incorrect
		 */
		public function checkPassword(string $uPass): bool {
			if (!self::$db || !$this->id || !self::passCheck($uPass, $this->password)) return false;
			
			if (self::passNeedRehash($this->password)) {
				try {
					$sql = self::$db->prepare('UPDATE `User` SET `Password` = ? WHERE `ID` = ?');
					$sql->bindValue(	1,	self::passHash($uPass),	PDO::PARAM_STR	);
					$sql->bindValue(	2,	$this->id,		PDO::PARAM_STR	);
					$sql->execute();
					$sql->closeCursor();
				} catch (Exception $e) { error_log('[BW] Fail to update password rehash, retry next time: '.$e->getMessage()); }
			}

			return true;
		}

		/** Create a new user. 
		 * Internal hash applied to password before write to DB. 
		 * @throws BW_ClientError Non-unique user ID
		 * @throws BW_DatabaseServerError Fail to write user into DB
		 */
		public function insert(): void { try {
			$sql = self::$db->prepare('INSERT INTO `User` (`ID`, `Name`, `Password`, `RegisterTime`, `Group`, `Data`) VALUES (?, ?, ?, ?, ?, ?)');
			$sql->bindValue(1,	$this->id,						PDO::PARAM_STR	);
			$sql->bindValue(2,	$this->name ?? $this->id,				PDO::PARAM_STR	);
			$sql->bindValue(3,	self::passHash($$this->password	 ?? ''),		PDO::PARAM_STR	);
			$sql->bindValue(4,	$this->registerTime ?? $_SERVER['REQUEST_TIME'],	PDO::PARAM_INT	);
			$sql->bindValue(5,	implode(',', $this->group ?? []),			PDO::PARAM_STR	);
			$sql->bindValue(6,	json_encode($this->group ?? []),			PDO::PARAM_STR	);
			$sql->execute();
			$sql->closeCursor();
		} catch (Exception $e) { throw strpos($e->getMessage(), 'UNIQUE') ? new BW_ClientError('User ID has been used', 409) : new BW_DatabaseServerError('Cannot insert new user into DB: '.$e->getMessage(), 500); } }

		/** Update user data. 
		 * Specify fields to modify, leave other fields null to keep orginal value. 
		 * @throws BW_DatabaseServerError Failed to update user DB (server error or no such user)
		 */
		public function update(): void { try {
			$sql = self::$db->prepare('UPDATE `User` SET
				`Name` =		IFNULL(?, `Name`),
				`Password` =		IFNULL(?, `Password`),
				`RegisterTime` =	IFNULL(?, `RegisterTime`),
				`Group` =		IFNULL(?, `Group`),
				`Data` =		IFNULL(?, `Data`),
			WHERE `ID` = ?');
			$sql->bindValue(1,	$this->name,									PDO::PARAM_STR	);
			$sql->bindValue(2,	is_null($this->password)	? null	: self::passHash($this->password),	PDO::PARAM_STR	);
			$sql->bindValue(3,	$this->registerTime,								PDO::PARAM_INT	);
			$sql->bindValue(4,	is_null($this->group)		? null	: implode(',', $this->group),		PDO::PARAM_STR	);
			$sql->bindValue(5,	is_null($this->data)		? null	: json_encode($this->data),		PDO::PARAM_STR	);
			$sql->bindValue(6,	$this->id,									PDO::PARAM_STR	);
			$sql->execute();
			$sql->closeCursor();
		} catch (Exception $e) { throw new BW_DatabaseServerError('Fail to update user in DB: '.$e->getMessage(), 500); } }

		public function isAdmin(): bool { return in_array('ADMIN', $this->group); }
		public function isGuest(): bool { return !$this->id; }

		private function passHash(string $plain): string { return password_hash($plain, PASSWORD_DEFAULT); }
		private function passCheck(string $plain, string $coded): bool { return password_verify($plain, $coded); }
		private function passNeedRehash(string $coded): bool { return password_needs_rehash($coded, PASSWORD_DEFAULT); }
	}


	class Bearweb_Session { use Bearweb_DatabaseBacked;
		public readonly string	$sID;		# Session ID
		public readonly int	$sCreate;	# Session create time
		public readonly string	$sUser;		# Session user ID
		public readonly string	$tID;		# Transaction ID
		public readonly int	$tCreate;	# Transaction create time
		public readonly string	$tIP;		# Transaction client IP
		public readonly string	$tURL;		# Transaction request URL

		const KEY_LENGTH = 48; # 64 char
		private static function keygen(): string { return base64_encode(random_bytes(self::KEY_LENGTH)); }
		private static function keycheck(string|array $data, string $key = ''): bool { return strlen(base64_decode( is_array($data) ? ($data[$key] ?? '') : $data , true)) == self::KEY_LENGTH; }

		/** Constructor. 
		 * Do NOT use! This method is for Bearweb framework use ONLY. 
		 * Read client-side cookies, format check, create/bind session, record transaction, write cookie to client. 
		 * @param string $url Request URL
		 * @throws BW_DatabaseServerError Cannot write session / transaction into DB
		*/
		public function __construct(string $url) {
			$this->tID = self::keygen(); # There may be collision in very low chance. TID may be helpful when tracking a transaction manually. Bearweb never uses TID other than records it to log.
			$this->tCreate = $_SERVER['REQUEST_TIME'];
			$this->tIP = $_SERVER['REMOTE_ADDR'];
			$this->tURL = $url;
			apache_setenv('BW_TID', $this->tID);
			apache_setenv('BW_LOG', '');

			$session = null;

			// Update session if session cookies are valid, found and matched in db
			if ( self::keycheck($_COOKIE, BW_Config::Session_CookieSID) && self::keycheck($_COOKIE, BW_Config::Session_CookieKey) ) {
				$sql = self::$db->prepare('UPDATE `Session` SET `LastUse` = ? WHERE `ID` = ? AND `Key` = ? AND `LastUse` > ? RETURNING `ID`, `Create`, `User`');
				$sql->bindValue(	1,	$this->tCreate,					PDO::PARAM_INT	);
				$sql->bindValue(	2,	$_COOKIE[ BW_Config::Session_CookieSID ],	PDO::PARAM_STR	);
				$sql->bindValue(	3,	$_COOKIE[ BW_Config::Session_CookieKey ],	PDO::PARAM_STR	);
				$sql->bindValue(	4,	$this->tCreate - BW_Config::Session_Expire,	PDO::PARAM_INT	);
				$sql->execute();
				$session = $sql->fetch();
				$sql->closeCursor();
			}

			// Create a new session otherwise
			if (!$session) {
				$sid = '';
				$key = self::keygen();
				$sql = self::$db->prepare('INSERT INTO `Session` (`ID`, `Create`, `LastUse`, `User`, `Key`) VALUES (?,?,?,?,?) RETURNING `ID`, `Create`, `User`');
				$sql->bindParam(	1,	$sid,		PDO::PARAM_STR	); #By reference
				$sql->bindValue(	2,	$this->tCreate,	PDO::PARAM_INT	);
				$sql->bindValue(	3,	$this->tCreate,	PDO::PARAM_INT	);
				$sql->bindValue(	4,	'',		PDO::PARAM_STR	); #New session is always issused to new user, it can only be changed by login API with existed SID
				$sql->bindValue(	5,	$key,		PDO::PARAM_STR	);
				for ($try = 5; ; $try--) {
					try {
						$sid = self::keygen();
						$sql->execute(); # Retry if fail
						$session = $sql->fetch();
						$sql->closeCursor();
						break;
					} catch(Exception $e) {
						if ( strpos($e->getMessage(), 'UNIQUE') === false || $try < 0 ) # Catch and retry if UNIQUE constraint fails within retry times limit
							throw $e;
					}
				}
				setcookie(BW_Config::Session_CookieSID, $sid, 0, '/', '', true, true );
				setcookie(BW_Config::Session_CookieKey, $key, 0, '/', '', true, false );
			}
			
			['ID' => $this->sID, 'Create' => $this->sCreate, 'User' => $this->sUser] = $session;
			apache_setenv('BW_UID', $this->sUser);
			apache_setenv('BW_SID', $this->sID);
		}

		/** Append log to Apache transaction record. 
		 * This method will replace "\n" (new-line) and '-' to "\t" and '_' so it looks nicer in that file. 
		 * @param string	$log	Log to append
		 */
		public function log(string $log): void { apache_setenv('BW_LOG', apache_getenv('BW_LOG').str_replace(["\n", '-'], ["\t", '_'], $log)); }

		/** Bind a user to the session. Effects next transaction. 
		 * @param string	$uid	User ID
		 * @throws BW_DatabaseServerError Cannot update session user in DB
		 */
		public function bindUser(string $uid): void {
			try {
				$sql = self::$db->prepare('UPDATE `Session` SET `User` = ? WHERE ID = ?');
				$sql->bindValue(	1,	$uid,		PDO::PARAM_STR	);
				$sql->bindValue(	2,	$this->sID,	PDO::PARAM_STR	);
				$sql->execute();
				$sql->closeCursor();
			} catch (Exception $e) { throw new BW_DatabaseServerError('Cannot update session user in DB: '.$e->getMessage(), 500); }
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
		/** Database connection resource, null if DB is not ready or module disabled. 
		*/
		public static ?PDO $db = null;

		/** Init Bearweb sitemap module. Do NOT call this routine. This routine should only be called by Bearweb framework for each module at the loading time. 
		 * @param string $dsn Data source name
		 * @throws BW_DatabaseServerError Fail to open db
		 */
		public static function init(string $dsn): void {
			try { self::$db = new PDO($dsn, null, null, [
				PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
				PDO::ATTR_TIMEOUT => 10, #10s waiting time should be far more than enough
				PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
			]); } catch (Exception $e) { throw new BW_DatabaseServerError('Fail to open DB: '.$e->getMessage(), 500); }
		}

		private static function __vmap(mixed $value, array $map): mixed { foreach ($map as [$src, $dest]) { if ($value === $src) return $dest; } return $value; }
	}
?>
