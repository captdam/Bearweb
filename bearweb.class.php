<?php	header('X-Powered-By: Bearweb 7.1.241112');

	class _Bearweb {
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
				Bearweb_Site::init(Bearweb_Config::Site_DB);
				Bearweb_User::init(Bearweb_Config::User_DB);
				Bearweb_Session::init(Bearweb_Config::Session_DB);
				$this->session = new Bearweb_Session($url);
				$this->user = Bearweb_User::query($this->session->sUser);
			} catch (Exception $e) {
				error_log('[BW] Fatal error: Module init failed - Cannot connect to DB: '.$e->getMessage());
				http_response_code(500); exit('500 - Server Fatal Error!');
			}
			
			try { ob_start();
				$this->site = Bearweb_Site::query($url) ?? throw new BW_ClientError('Not found', 404);
				[$stateFlag, $stateInfo] = [substr($this->site->state, 0, 1), substr($this->site->state, 1)];

				// Access control
				if ($this->site->access($this->user) == Bearweb_Site::ACCESS_NONE) {
					if ($stateFlag == 'A') {
						$this->throwClientError_auth(new BW_ClientError('Unauthorized: Controlled resource. Cannot access, please login first', 401));
					} else if ($stateFlag == 'P') {
						$this->throwClientError_auth(new BW_ClientError('Forbidden: Locked resource. Access denied', 403));
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
				$this->invokeTemplate();

			} catch (Exception $e) { ob_clean(); ob_start();
				if ($e instanceof BW_ClientError) {
					$this->createErrorPage($e->getCode().' - Client Error', $e->getMessage(), $e->getCode());
				} else if ($e instanceof BW_ServerError) {
					error_log('[BW] Server Error: '.$e);
					Bearweb_Config::Site_HideServerError ? $this->createErrorPage('500 - Internal Error', 'Server-side internal error.', 500) : $this->createErrorPage($e->getCode().' - Server Error', $e->getMessage(), $e->getCode());
				} else {
					error_log('[BW] Unknown Error: '.$e);
					Bearweb_Config::Site_HideServerError ? $this->createErrorPage('500 - Internal Error', 'Server-side internal error.', 500) : $this->createErrorPage('500 - Unknown Error', $e->getMessage(), 500);
				}
				$this->invokeTemplate();
			}
		}

		protected function invokeTemplate(): void {
			$BW = $this;
			$template = Bearweb_Config::Site_TemplateDir.$this->site->template[0].'.php';
			if (!file_exists($template))
				throw new BW_WebServerError('Template not found: '.$this->site->template[0], 500);
			include $template;
		}

		protected function createErrorPage(string $title, string $detail, int $code = 0): void {
			if ($code) http_response_code($code);
			$this->site = new Bearweb_Site(
				url: '', category: '', template: ['object', 'blob'],
				owner: '', create: Bearweb_Site::TIME_NULL, modify: Bearweb_Site::TIME_NULL,
				meta: ['text/html'], 
				state: 'S', content: '<!DOCTYPE html><h3>'.$title.'</h3><p>'.$detail.'</p>', aux: []
			);
		}

		protected function throwClientError_auth(BW_Error $e) { throw new BW_ClientError($e->getMessage(), $e->getCode()); }
	}


	class _Bearweb_Site { use Bearweb_DatabaseBacked;
		const TIME_CURRENT = -1;	# Pass this parameter to let Bearweb use current timestamp
		const TIME_NULL = 0;		# Some resource (like auto generated one) has no create / modify time
		const ACCESS_NONE = 0;		# No access
		const ACCESS_RO = 1;		# Readonly, and executable
		const ACCESS_RW = -1;		# Read and write

		public readonly ?array	$template;	# Template used to process the given resourc
		public readonly ?array	$meta;		# Meta data
		public readonly ?array	$aux;		# Resource auxiliary data, template defined data array

		public function __construct(
			public readonly string	$url,			# Resource URL, PK, Unique, Not Null
			public readonly ?string	$category	= null,	# Resource category, for management purpose
			null|array|string	$template	= null,	# Template used to process the given resourc, array or / divide string
			public readonly ?string	$owner		= null,	# Owner's user ID of the given resource; Only the owner and user in "ADMIN" group can modify this resource
			public readonly ?int	$create		= null,	# Create timestamp
			public readonly ?int	$modify		= null,	# Modify timestamp
			null|array|string	$meta		= null,	# Meta data, array or \n divide string
			public readonly ?string	$state		= null,	# State of the given resource
			public readonly ?string	$content	= null,	# Resource content, the content should be directly output to reduce server process load
			null|array|string	$aux		= null	# Resource auxiliary data, template defined data array, object or JSON string
		) {
			$this->template	= gettype($template) == 'string'	? explode('/', $template)	: $template;
			$this->meta	= gettype($meta) == 'string'		? explode("\n", $meta)		: $meta;
			$this->aux	= gettype($aux) == 'string'		? json_decode($aux, true)	: $aux;
		}

		/** URL is valid.
		 * URL is 128 length or less, allows A-Za-z0-9 or -_:/.
		 */
		public static function validURL(string $url): bool { return $url === '' || ( strlen($url) <= 128 && ctype_alnum( str_replace(['-', '_', ':', '/', '.'], '', $url) ) ); }

		/** Query a resource from sitemap db. 
		 * Note: Data in DB is volatile, instance only reflects the data at time of DB fetch, it may be changed by another transaction (e.g. Resource modify API) and other process. 
		 * @param string $url			Resource URL
		 * @return Bearweb_Site			A Bearweb site resource
		 * @throws BW_DatabaseServerError	Cannot read sitemap DB
		*/
		public static function query(string $url): ?static {
			$site = null;
			try {
				$sql = self::$db->prepare('SELECT * FROM `Sitemap` WHERE `url` = ?');
				$sql->bindValue(	1,	$url,	PDO::PARAM_STR	);
				$sql->execute();
				$site = $sql->fetch();
				$sql->closeCursor();
				return $site ? new static(...$site) : null;
			} catch (Exception $e) { throw new BW_DatabaseServerError('Cannot read sitemap database: '.$e->getMessage(), 500); }
		}

		/** Test user access privilege level. 
		 * @param Bearweb_User $user Bearweb_User object with user ID and group
		 * @return int ACCESS_NONE, ACCESS_RO or ACCESS_RW (owner/admin)
		 */
		public function access(Bearweb_User $user): int {
			if ( !$user->isGuest() && ($user->isAdmin() || $user->id==$this->owner) ) return self::ACCESS_RW; # Note: Admin always have privilege, guest never have privilege; system resource is owned by '' but '' means guest in Bearweb_User
			[$stateFlag, $stateInfo] = [substr($this->state, 0, 1), substr($this->state, 1)];
			return (
				$stateFlag == 'P' ||										# Pending resource: owner only
				( $stateFlag == 'A' && !count(array_intersect( $user->group , explode(',',$stateInfo) )) )	# Auth control: check user groups against privilege groups
			) ? self::ACCESS_NONE : self::ACCESS_RO;
		}

		/** Insert this resource into sitemap db.
		 * @throws BW_DatabaseServerError Fail to insert into sitemap db
		 */
		public function insert(): void { try {
			$current = $_SERVER['REQUEST_TIME'];
			$sql = self::$db->prepare('INSERT INTO `Sitemap` (
				`url`, `category`, `template`,
				`owner`, `create`, `modify`, `meta`,
				`state`, `content`, `aux`
			) VALUES (	?, ?, ?,	?, ?, ?, ?,	?, ?, ?)');
			$sql->bindValue(1,	$this->url,										PDO::PARAM_STR	);	
			$sql->bindValue(2,	$this->category			?? '',							PDO::PARAM_STR	);
			$sql->bindValue(3,	implode('/', $this->template ?? ['object', 'blob']),					PDO::PARAM_STR	);
			$sql->bindValue(4,	$this->owner			?? '',							PDO::PARAM_STR	);
			$sql->bindValue(5,	self::__vmap($this->create, [[null, $current], [self::TIME_CURRENT, $current]]),	PDO::PARAM_INT	);
			$sql->bindValue(6,	self::__vmap($this->modify, [[null, $current], [self::TIME_CURRENT, $current]]),	PDO::PARAM_INT	);
			$sql->bindValue(7,	$this->meta ? implode("\n", $this->meta) : 'text/plain',				PDO::PARAM_STR	);
			$sql->bindValue(8,	$this->state			?? 'P',							PDO::PARAM_STR	);
			$sql->bindValue(9,	$this->content			?? '',							PDO::PARAM_STR	);
			$sql->bindValue(10,	$this->aux			?? '{}',						PDO::PARAM_STR	);
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
				`category` =	IFNULL(?, `category`),
				`template` =	IFNULL(?, `template`),
				`owner` =	IFNULL(?, `owner`),
				`create` =	IFNULL(?, `create`),
				`modify` =	IFNULL(?, `modify`),
				`meta` =	IFNULL(?, `meta`),
				`state` =	IFNULL(?, `state`),
				`content` =	IFNULL(?, `content`),
				`aux` =		IFNULL(?, `aux`)
			WHERE `URL` = ?');
			$sql->bindValue(1,	$this->category,								PDO::PARAM_STR	);
			$sql->bindValue(2,	is_null($this->template) ? null : implode('/', $this->template),		PDO::PARAM_STR	);
			$sql->bindValue(3,	$this->owner,									PDO::PARAM_STR	);
			$sql->bindValue(4,	self::__vmap($this->create, [[null, null], [self::TIME_CURRENT, $current]]),	PDO::PARAM_INT	);
			$sql->bindValue(5,	self::__vmap($this->modify, [[null, null], [self::TIME_CURRENT, $current]]),	PDO::PARAM_INT	);
			$sql->bindValue(6,	is_null($this->meta) ? null : implode("\n", $this->meta),			PDO::PARAM_STR	);
			$sql->bindValue(7,	$this->state,									PDO::PARAM_STR	);
			$sql->bindValue(8,	$this->content,									PDO::PARAM_STR	);
			$sql->bindValue(9,	is_null($this->aux) ? null : json_encode($this->aux),				PDO::PARAM_STR	);
			$sql->bindValue(10,	$this->url,									PDO::PARAM_STR	);
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
		const TIME_CURRENT = -1;	# Pass this parameter to let Bearweb use current timestamp
		const TIME_NULL = 0;		# Some resource (like auto generated one) has no create / modify time

		public function __construct(
			public readonly string	$id,
			public readonly ?string	$name		= null,
			public readonly ?string	$salt		= null,
			public readonly ?string	$password	= null,
			public readonly ?int	$registerTime	= null,
			public readonly ?int	$lastActive	= null,
			public readonly ?array	$group		= null,
			public readonly ?array	$data		= null,
			public readonly ?string	$avatar		= null
		) {}

		public static function validID(string $uid): bool { return strlen($uid) >= 6 && strlen($uid) <= 16 && ctype_alnum(str_replace(['-', '_'], '', $uid)); }
		public static function validPassword(string $pass): bool { return strlen(base64_decode($pass, true)) == 48; } // 32-byte (256-bit) cipher

		/** Query a user. 
		 * @param string	$id	User ID
		 * @return ?Bearweb_User	A Bearweb_User instance, or null if user not existed
		 * @throws BW_DatabaseServerError Fail to query user info
		 */
		public static function query(string $id): ?static {
			if (!$id) return new static(id: '', name: 'Guest', salt: '', password: '', registerTime: self::TIME_NULL, lastActive: self::TIME_NULL, group: [], data: [], avatar: null);

			$user = null;
			try {
				$sql = self::$db->prepare('SELECT `ID`, `Name`, `Salt`, `Password`, `RegisterTime`, `LastActive`, `Group`, `Data`, `Avatar` FROM `User` WHERE `ID` = ?');
				$sql->bindValue(	1,	$id,	PDO::PARAM_STR	);
				$sql->execute();
				$user = $sql->fetch();
				$sql->closeCursor();
			} catch (Exception $e) { throw new BW_DatabaseServerError('Cannot query user from DB: '.$e->getMessage(), 500); }
			
			return $user ? new static(
				id:		$user['ID'],
				name:		$user['Name']		?? $user['ID'],
				salt:		$user['Salt']		?? '',
				password:	$user['Password']	?? '',
				registerTime:	$user['RegisterTime']	?? self::TIME_NULL,
				lastActive:	$user['LastActive']	?? self::TIME_NULL,
				group:		explode(',', $user['Group'] ?? ''),
				data:		json_decode($user['Data'] ?? '{}', true),
				avatar:		$user['Avatar']
			) : null;
		}

		/** Create a new user. 
		 * Internal hash applied to password before write to DB. 
		 * @throws BW_ClientError Non-unique user ID
		 * @throws BW_DatabaseServerError Fail to write user into DB
		 */
		public function insert(): void { try {
			$current = $_SERVER['REQUEST_TIME'];
			$sql = self::$db->prepare('INSERT INTO `User` (`ID`, `Name`, `Salt`, `Password`, `RegisterTime`, `LastActive`, `Group`, `Data`, `Avatar`) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)');
			$sql->bindValue(1,	$this->id,										PDO::PARAM_STR	);
			$sql->bindValue(2,	$this->name		?? $this->id,							PDO::PARAM_STR	);
			$sql->bindValue(3,	$this->salt		?? ':',								PDO::PARAM_STR	);
			$sql->bindValue(4,	$this->password		?? ':',								PDO::PARAM_STR	);
			$sql->bindValue(5,	self::__vmap($this->registerTime, [[null, $current], [self::TIME_CURRENT, $current]]),	PDO::PARAM_INT	);
			$sql->bindValue(6,	self::__vmap($this->lastActive, [[null, $current], [self::TIME_CURRENT, $current]]),	PDO::PARAM_INT	);
			$sql->bindValue(7,	implode(',', $this->group ?? []),							PDO::PARAM_STR	);
			$sql->bindValue(8,	json_encode($this->group ?? []),							PDO::PARAM_STR	);
			$sql->bindValue(9,	$this->avatar,										PDO::PARAM_LOB	);
			$sql->execute();
			$sql->closeCursor();
		} catch (Exception $e) { throw strpos($e->getMessage(), 'UNIQUE') ? new BW_ClientError('User ID has been used', 409) : new BW_DatabaseServerError('Cannot insert new user into DB: '.$e->getMessage(), 500); } }

		/** Update user data. 
		 * Specify fields to modify, leave other fields null to keep orginal value. 
		 * @throws BW_DatabaseServerError Failed to update user DB (server error or no such user)
		 */
		public function update(): void { try {
			$current = $_SERVER['REQUEST_TIME'];
			$sql = self::$db->prepare('UPDATE `User` SET
				`Name` =		IFNULL(?, `Name`),
				`Salt` =		IFNULL(?, `Salt`),
				`Password` =		IFNULL(?, `Password`),
				`RegisterTime` =	IFNULL(?, `RegisterTime`),
				`LastActive` =		IFNULL(?, `LastActive`),
				`Group` =		IFNULL(?, `Group`),
				`Data` =		IFNULL(?, `Data`),
				`Avatar` =		IFNULL(?, `Avatar`)
			WHERE `ID` = ?');
			$sql->bindValue(1,	$this->name,										PDO::PARAM_STR	);
			$sql->bindValue(2,	is_null($this->salt)		? null	: $this->salt,					PDO::PARAM_STR	);
			$sql->bindValue(3,	is_null($this->password)	? null	: $this->password,				PDO::PARAM_STR	);
			$sql->bindValue(4,	self::__vmap($this->registerTime, [[null, null], [self::TIME_CURRENT, $current]]),	PDO::PARAM_INT	);
			$sql->bindValue(5,	self::__vmap($this->lastActive, [[null, null], [self::TIME_CURRENT, $current]]),	PDO::PARAM_INT	);
			$sql->bindValue(6,	is_null($this->group)		? null	: implode(',', $this->group),			PDO::PARAM_STR	);
			$sql->bindValue(7,	is_null($this->data)		? null	: json_encode($this->data),			PDO::PARAM_STR	);
			$sql->bindValue(8,	$this->avatar,										PDO::PARAM_LOB	);
			$sql->bindValue(9,	$this->id,										PDO::PARAM_STR	);
			$sql->execute();
			$sql->closeCursor();
		} catch (Exception $e) { throw new BW_DatabaseServerError('Fail to update user in DB: '.$e->getMessage(), 500); } }

		public function isAdmin(): bool { return in_array('ADMIN', $this->group); }
		public function isGuest(): bool { return !$this->id; }
	}


	class Bearweb_Session { use Bearweb_DatabaseBacked;
		const TIME_CURRENT = -1;	# Pass this parameter to let Bearweb use current timestamp
		const TIME_NULL = 0;		# Some resource (like auto generated one) has no create / modify time

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
		private static function keygen(): string { return base64_encode(random_bytes(self::KEY_LENGTH)); }
		private static function keycheck(string|array $data, string $key = ''): bool { return strlen(base64_decode( is_array($data) ? ($data[$key] ?? '') : ($data ?? '') , true)) == self::KEY_LENGTH; }

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
			if (!self::$db->beginTransaction()) { throw new Exception ('Cannot start transaction'); }

			$session = null;
			$transaction = null;
			$needToSendSessionCookie = false;

			// Update session if session cookies are valid, found and matched, not expired in db
			if ( self::keycheck($_COOKIE, Bearweb_Config::Session_CookieSID) && self::keycheck($_COOKIE, Bearweb_Config::Session_CookieKey) ) {
				$sql = self::$db->prepare('UPDATE `Session` SET `LastUse` = ? WHERE `ID` = ? AND `Key` = ? AND `LastUse` > ? RETURNING `ID`, `Create`, `LastUse`, `User`, `Key`');
				$sql->bindValue(	1,	$_SERVER['REQUEST_TIME'],					PDO::PARAM_INT	);
				$sql->bindValue(	2,	$_COOKIE[ Bearweb_Config::Session_CookieSID ],			PDO::PARAM_STR	);
				$sql->bindValue(	3,	$_COOKIE[ Bearweb_Config::Session_CookieKey ],			PDO::PARAM_STR	);
				$sql->bindValue(	4,	$_SERVER['REQUEST_TIME'] - Bearweb_Config::Session_Expire,	PDO::PARAM_INT	);
				$sql->execute();
				$session = $sql->fetch();
				$sql->closeCursor();
			}

			// Create a new session otherwise
			if (!$session) {
				$sid = '';
				$sql = self::$db->prepare('INSERT INTO `Session` (`ID`, `Create`, `LastUse`, `User`, `Key`) VALUES (?,?,?,?,?) RETURNING `ID`, `Create`, `LastUse`, `User`, `Key`');
				$sql->bindParam(	1,	$sid,				PDO::PARAM_STR	); #By reference
				$sql->bindValue(	2,	$_SERVER['REQUEST_TIME'],	PDO::PARAM_INT	);
				$sql->bindValue(	3,	$_SERVER['REQUEST_TIME'],	PDO::PARAM_INT	);
				$sql->bindValue(	4,	'',				PDO::PARAM_STR	); #New session is always issused to new user, it can only be changed by User Login API with existed SID
				$sql->bindValue(	5,	self::keygen(),			PDO::PARAM_STR	);
				self::insertRetry(5, function() use (&$sid, $sql) { $sid = self::keygen(); $sql->execute(); }, new Exception('Retry too much for session ID'));
				$session = $sql->fetch();
				$sql->closeCursor();
				$needToSendSessionCookie = true;
			}

			// Create a new transaction
			$tid = '';
			$sql = self::$db->prepare('INSERT INTO `Transaction` (`ID`, `Create`, `IP`, `URL`, `Session`, `Log`) VALUES (?,?,?,?,?,\'\') RETURNING `ID`, `Create`, `IP`, `URL`, `Session`, `Log`');
			$sql->bindParam(	1,	$tid,							PDO::PARAM_STR	); #By reference
			$sql->bindValue(	2,	$_SERVER['REQUEST_TIME'],				PDO::PARAM_INT	);
			$sql->bindValue(	3,	$_SERVER['REMOTE_ADDR'].':'.$_SERVER['REMOTE_PORT'],	PDO::PARAM_STR	);
			$sql->bindValue(	4,	$url,							PDO::PARAM_STR	); #New session is always issused to new user, it can only be changed by User Login API with existed SID
			$sql->bindValue(	5,	$session['ID'],						PDO::PARAM_STR	);
			self::insertRetry(5, function() use (&$tid, $sql) { $tid = self::keygen(); $sql->execute(); }, new Exception('Retry too much for transaction ID'));
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
				setcookie(Bearweb_Config::Session_CookieSID, $this->sID, 0, '/', '', true, true ); # Note: Send cookie to client only if DB write success
				setcookie(Bearweb_Config::Session_CookieKey, $this->sKey, 0, '/', '', true, false );
			}

			if (!self::$db->commit()) { throw new Exception ('Cannot commit transaction'); }
		} catch (Exception $e) { self::$db->rollBack(); throw new BW_DatabaseServerError('Cannot record session control in DB: '.$e->getMessage(), 500); } } # Cannot do anything if rollback fails :(

		/** Append log to transaction record. 
		 * @param string	$log	Log to append
		 * @return string	Log in full after append
		 */
		public function log(string $log): string { return $this->tLog .= $log; }

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

		/** Update session key on both server-side and client-side. Effects next transaction. 
		 * @return string	New session key
		 * @throws BW_DatabaseServerError Cannot update session key in DB
		 */
		public function updateKey(): string {
			$key = self::keygen();
			try {
				$sql = self::$db->prepare('UPDATE `Session` SET `Key` = ? WHERE ID = ?');
				$sql->bindValue(	1,	$key,		PDO::PARAM_STR	);
				$sql->bindValue(	2,	$this->sID,	PDO::PARAM_STR	);
				$sql->execute();
				$sql->closeCursor();
			} catch (Exception $e) { throw new BW_DatabaseServerError('Cannot update session user in DB: '.$e->getMessage(), 500); }
			setcookie(Bearweb_Config::Session_CookieKey, $key, 0, '/', '', true, false );
			return $key;
		}

		/** Destructor. 
		 * Do NOT use! This method is for Bearweb framework use ONLY. 
		 * Commit log to DB. 
		*/
		public function __destruct() {
			$sql = self::$db->prepare('UPDATE `Transaction` SET `Log` = ?, `Status` = ?, `Time` = ? WHERE ID = ?');
			$sql->bindValue(	1,	$this->tLog,							PDO::PARAM_STR	);
			$sql->bindValue(	2,	http_response_code(),						PDO::PARAM_INT	);
			$sql->bindValue(	3,	(microtime(true) - $_SERVER["REQUEST_TIME_FLOAT"]) * 1e6,	PDO::PARAM_STR	);
			$sql->bindValue(	4,	$this->tID,							PDO::PARAM_STR	);
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

		/** Init module by connect its database. Do NOT call this routine. This routine should only be called by Bearweb framework for each module at the loading time. 
		 * @param array $dsn Data source name, username and password
		 * @throws BW_DatabaseServerError Fail to open db
		 */
		public static function init(array $dsn): void {
			try { self::$db = new PDO($dsn[0], $dsn[1], $dsn[2], [
				PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
				PDO::ATTR_TIMEOUT => 10, #10s waiting time should be far more than enough
				PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
			]); } catch (Exception $e) { throw new BW_DatabaseServerError('Fail to open DB: '.$e->getMessage(), 500); }
		}

		private static function __vmap(mixed $value, array $map): mixed { foreach ($map as [$src, $dest]) { if ($value === $src) return $dest; } return $value; }
	}
?>