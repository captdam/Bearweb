<?php	header('X-Powered-By: Bearweb 7.1.240502');

	class Bearweb {
		/** User supplied data format check. 
		 * @param string $type Data type
		 * @param string $data User supplied data
		 * @return bool True if format OK, false is bad format
		 */
		public static function check(string $type, string $data): bool {
			switch ($type) {
				case 'MD5':		return strlen($data) == 32 && ctype_xdigit($data);
			
				case 'URL':		return $data === '' || ( strlen($data) <= 128 && ctype_alnum( str_replace(['-', '_', ':', '/', '.'], '', $data) ) );

				case 'UserID':		return strlen($data) >= 6 && strlen($data) <= 16 && ctype_alnum( str_replace(['-', '_'], '', $data) ); # Return false for guest ('')
				case 'UserName':	return strlen($data) >= 2 && strlen($data) < 16;
				case 'UserPassword':	return self::check('MD5', $data);
				default:		return false;
			}
		}

		private ?Bearweb_Session $session = null;
		private Bearweb_Site $site;

		/** Use Bearweb to server a resource. 
		 * This function will init the Site module and the Session module. 
		 * This function will invoke the error template if the given URL fail the URL format check or upon error in framework and template execution.
		 * @param string $url Request URL given by client
		 */
		public function __construct(string $url) {
			$BW = $this;
			
			try { ob_start();
				if (!Bearweb::check('URL', $url))
					throw new BW_ClientError('Bad URL', 400);
				
				$this->session = new Bearweb_Session($url); //Fallback to default if disabled or error
				$this->site = Bearweb_Site::queue($url);
				$stateFlag = substr($this->site->state, 0, 1);
				$stateInfo = substr($this->site->state, 1);

				// Access control
				if (!$this->site->access($this->session->user)) {
					if ($stateFlag == 'A') {
						if (BW_Config['Site_HideAuthError'])
							throw new BW_ClientError('Not found', 404);
						else
							throw new BW_ClientError('Unauthorized: Controlled resource. Cannot access, please login first', 401);
					} else if ($stateFlag == 'P') {
						if (BW_Config['Site_HideAuthError'])
							throw new BW_ClientError('Not found', 404);
						else
							throw new BW_ClientError('Forbidden: Locked resource. Access denied', 403);
					}
					throw new BW_ClientError('Access denied', 403);
				}

				// Redirect resource
				if ( $stateFlag == 'R' ) {
					header('Location: /'.$stateInfo);
					throw new BW_ClientError('301 - Moved Permanently: Resource has been moved permanently to: '.$stateInfo, 301); # To terminate the resource processing
				}
				if ( $stateFlag == 'r' ) {
					header('Location: /'.$stateInfo);
					throw new BW_ClientError('302 - Moved Temporarily: Resource has been moved temporarily to: '.$stateInfo, 302);
				}

				// E-tag
				if ($this->site->modify == 0) { # Create E-tag for content and auto generated content (and error page), for client-side cache
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
				$template = BW_Config['Site_TemplateDir'].$this->site->template[0].'.php';
				if (!file_exists($template))
					throw new BW_ServerError('Template not found: '.$this->site->template[0], 500);
				include $template;

			} catch (Exception $e) { ob_clean(); ob_start();
				if ($e instanceof BW_ClientError) {
					$this->site = Bearweb_Site::createErrorPage($e->getCode().' - Client Error', $e->getMessage(), $e->getCode()); //Bearweb::setError($e->getCode().' - Client Error', $e->getMessage(), $e->getCode());
				} else if ($e instanceof BW_ServerError) {
					error_log('[BW] Server Error: '.$e);
					if (BW_Config['Site_HideServerError'])
						$this->site = Bearweb_Site::createErrorPage('500 - Internal Error', 'Server-side internal error.', 500); //Bearweb::setError('500 - Internal Error', 'Server-side internal error.', 500);
					else
						$this->site = Bearweb_Site::createErrorPage($e->getCode().' - Server Error', $e->getMessage(), $e->getCode()); //Bearweb::setError($e->getCode().' - Server Error', $e->getMessage(), $e->getCode());
				} else {
					error_log('[BW] Unknown Error: '.$e);
					if (BW_Config['Site_HideServerError'])
						$this->site = Bearweb_Site::createErrorPage('500 - Internal Error', 'Server-side internal error.', 500); //Bearweb::setError('500 - Internal Error', 'Server-side internal error.', 500);
					else
						$this->site = Bearweb_Site::createErrorPage('500 - Unknown Error', $e->getMessage(), 500); //Bearweb::setError('500 - Unknown Error', $e->getMessage(), 500);
				}
				$template = BW_Config['Site_TemplateDir'].'page.php';
				include $template;
			}
			
		}
	}

	/** Bearweb sitemap ********************************************************************
	 * Bearweb is a database-driven website framework. Requesting a webpage or any other 
	 * resource is in fact fetching data from the database, with access control and some 
	 * data manipulation. This DB is called Sitemap because the table is a map of website. 
	 * ************************************************************************************/
	class Bearweb_Site { use Bearweb_DatabaseBacked;
		const TIME_CURRENT = -1;	# Pass this parameter to let Bearweb use current timestamp
		const TIME_NULL = 0;		# Some resource (like auto generated one) has no create / modify time

		private function __construct(
			public readonly string	$url,					# Resource URL, PK, Unique, Not Null
			public readonly ?string	$category	= '',			# Resource category, for management purpose; Default '' No category
			public readonly ?array	$template	= ['object', 'blob'],	# Template used to process the given resource; Default ['object', 'blob'] for plain text file object
			public readonly ?string	$owner		= '',			# Owner's user ID of the given resource; Only the owner and user in "ADMIN" group can modify this resource; Default '' for system-owned
			public readonly ?int	$create		= self::TIME_NULL,	# Create timestamp; Default TIME_NULL for no actual time
			public readonly ?int	$modify		= self::TIME_NULL,	# Modify timestamp; Default TIME_NULL for no actual time
			public readonly ?string	$title		= '',			# Resource HTML meta info: title; Default ''
			public readonly ?string	$keywords	= '',			# Resource HTML meta info: keywords; Default ''
			public readonly ?string	$description	= '',			# Resource HTML meta info: description; Default ''
			public readonly ?string	$state		= 'S',			# State of the given resource; Default 'S' special (open to public, SEO no index)
			public readonly ?string	$content	= '',			# Resource content, the content should be directly output to reduce server process load; Default ''
			public readonly ?string	$aux		= 'text/plain'		# Resource auxiliary data, the template decide how to use it, such as extra info or a JSON data; Default 'text/plain' for object template MIME type
		) {}

		/** Constructe an error page template. 
		 * This method is for Bearweb framework. 
		 * @param string $title		Error page title
		 * @param string $detail	Detail info about the error
		 * @param int $code		If not 0, HTTP code will be set and send to client
		*/
		public static function createErrorPage(string $title, string $detail, int $code = 0): static {
			return new static(url: '', template: ['page', 'error'], title: $title, description: $detail, content: $detail, aux: '');
		}

		/** Queue a resource from sitemap db. 
		 * Note: Data in DB is volatile, instance only reflects the data at time of DB fetch, it may be changed by another transaction (e.g. Resource modify API) and other process. 
		 * @param string	$url	Resource URL
		 * @throws BW_DatabaseServerError Cannot read sitemap DB
		 * @throws BW_ClientError Resource not found in sitemap DB (HTTP 404)
		*/
		public static function queue(string $url): static {
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
				aux:		$site['Aux']		?? ''
			);
		}

		/** Test user access privilege level. 
		 * @param array $user An array conatins user info in [string ID, array Group], this can be directly feed from Bearweb_Session->user.
		 * @return int 0 = No access; 1 = read/execute; -1 = read/execute/write (owner/admin)
		 */
		public function access(array $user): int {
			if (
				in_array('ADMIN', $user['Group']) ||			# Admin always has the right
				( $user['ID'] != '' && $user['ID'] == $this->owner )	# Owner of resource (Note: '' means guest in session control, but '' means system in sitemap)
			) return -1;

			$stateFlag = substr($this->state, 0, 1);
			$stateInfo = substr($this->state, 1);
			$groups = explode(',', $stateInfo);
			if (
				$stateFlag == 'P' ||								# Pending resource: owner only
				( $stateFlag == 'A' && !count(array_intersect( $user['Group'] , $groups )) )	# Auth control: check user groups against privilege groups
			) return 0;

			return 1;
		}

		/** Insert this resource into sitemap db.
		 * @throws BW_DatabaseServerError Fail to insert into sitemap db
		 */
		public function insert(): void {
			$url =		$this->url;
			$category =	$this->category ?? '';
			$template =	implode('/', $this->template ?? ['object', 'blob']);
			$owner =	$this->owner ?? '';
			$create =	self::__vmap($this->create, [[null, $_SERVER['REQUEST_TIME']], [self::TIME_CURRENT, $_SERVER['REQUEST_TIME']]]);
			$modify =	self::__vmap($this->modify, [[null, $_SERVER['REQUEST_TIME']], [self::TIME_CURRENT, $_SERVER['REQUEST_TIME']]]);
			$title =	$this->title ?? '';
			$keywords =	$this->keywords ?? '';
			$description =	$this->description ?? '';
			$state =	$this->state ?? 'S';
			$content =	$this->content ?? '';
			$aux =		$this->aux ?? 'text/plain';
			try {
				$sql = self::$db->prepare('INSERT INTO `Sitemap` (
					`URL`, `Category`, `Template`,
					`Owner`, `Create`, `Modify`, `Title`, `Keywords`, `Description`,
					`State`, `Content`, `Aux`
				) VALUES (
					?, ?, ?,
					?, ?, ?, ?, ?, ?,
					?, ?, ?
				)');
				$sql->bindValue(1,	$url,		PDO::PARAM_STR	);	
				$sql->bindValue(2,	$category,	PDO::PARAM_STR	);
				$sql->bindValue(3,	$template,	PDO::PARAM_STR	);
				$sql->bindValue(4,	$owner,		PDO::PARAM_STR	);
				$sql->bindValue(5,	$create,	PDO::PARAM_INT	);
				$sql->bindValue(6,	$modify,	PDO::PARAM_INT	);
				$sql->bindValue(7,	$title,		PDO::PARAM_STR	);
				$sql->bindValue(8,	$keywords,	PDO::PARAM_STR	);
				$sql->bindValue(9,	$description,	PDO::PARAM_STR	);
				$sql->bindValue(10,	$state,		PDO::PARAM_STR	);
				$sql->bindValue(11,	$content,	PDO::PARAM_STR	);
				$sql->bindValue(12,	$aux,		PDO::PARAM_STR	);
				$sql->execute();
				$sql->closeCursor();
			} catch (Exception $e) { throw new BW_DatabaseServerError('Cannot insert into sitemap database: '.$e->getMessage(), 500); }
		}

		/** Update this resource in sitemap db. 
		 * It is not necessary to queue this resource, create a dummy resource with url and fields to modify (leave other field null). 
		 * @throws BW_DatabaseServerError Fail to update sitemap db
		 */
		public function update(): void {
			$url =		$this->url;
			$category =	$this->category;
			$template =	$this->template === null ? null : implode('/', $this->template);
			$owner =	$this->owner;
			$create =	self::__vmap($this->create, [[null, null], [self::TIME_CURRENT, $_SERVER['REQUEST_TIME']]]);
			$modify =	self::__vmap($this->modify, [[null, null], [self::TIME_CURRENT, $_SERVER['REQUEST_TIME']]]);
			$title =	$this->title;
			$keywords =	$this->keywords;
			$description =	$this->description;
			$state =	$this->state;
			$content =	$this->content;
			$aux =		$this->aux;
			try {
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
				$sql->bindValue(1,	$category,	PDO::PARAM_STR	);
				$sql->bindValue(2,	$template,	PDO::PARAM_STR	);
				$sql->bindValue(3,	$owner,		PDO::PARAM_STR	);
				$sql->bindValue(4,	$create,	PDO::PARAM_INT	);
				$sql->bindValue(5,	$modify,	PDO::PARAM_INT	);
				$sql->bindValue(6,	$title,		PDO::PARAM_STR	);
				$sql->bindValue(7,	$keywords,	PDO::PARAM_STR	);
				$sql->bindValue(8,	$description,	PDO::PARAM_STR	);
				$sql->bindValue(9,	$state,		PDO::PARAM_STR	);
				$sql->bindValue(10,	$content,	PDO::PARAM_STR	);
				$sql->bindValue(11,	$aux,		PDO::PARAM_STR	);
				$sql->bindValue(12,	$url,		PDO::PARAM_STR	);
				$sql->execute();
				$sql->closeCursor();
			} catch (Exception $e) { throw new BW_DatabaseServerError('Cannot update sitemap database: '.$e->getMessage(), 500); }
		}

		/** Delete this resource. 
		 * It is not necessary to queue this resource, create a dummy resource with url to specify resource in sitemap db. 
		 * @throws BW_DatabaseServerError Fail to delete from sitemap db
		 */
		public function delete(): void {
			try {
				$sql = self::$db->prepare('DELETE FROM `Sitemap` WHERE `URL` = ?');
				$sql->bindValue(1,	$this->url,	PDO::PARAM_STR	);
				$sql->execute();
				$sql->closeCursor();
			} catch (Exception $e) {
				throw new BW_DatabaseServerError('Cannot delete from sitemap database: '.$e->getMessage(), 500);
			}
		}

		private static function __vmap(mixed $value, array $map): mixed {
			foreach ($map as [$src, $dest]) {
				if ($value === $src)
					return $dest;
			}
			return $value;
		}
	}

	/** Session control ********************************************************************
	 * A session is a set of requests made by the same user (session id and key). With
	 * session control enabled, the Session ID (auto-generated unique ID) is recorded with 
	 * following info: 
	 *   - Create time and last use time. Session automatically expire after a period after 
	 * 	the last use time (inactive time); 
	 *   - User ID for which user is bind to this session; 
	 *   - Key (an invisible cookie to client-side js script, server uses it to verify SID). 
	 * With session control enabled, each time a request is received, the Request ID 
	 * (auto-generated unique ID) is recorded with following info: 
	 *   - Request URL; 
	 *   - Timestamp when the request is received on server side; 
	 *   - Client IP (IPv4 or v6; in case of VPN, records VPN's IP instead of user's IP); 
	 *   - Assoicated session ID; 
	 *   - State (response HTTP code sent to client); 
	 *   - Server log (for debug use). 
	 * A session can be bind to an user's ID. A table is used to store user's profile with 
	 * the user ID (unique ID) and:
	 *   - Name (nickname); 
	 *   - Hashed password, use PHP's built-in password hash; 
	 *   - Register time; 
	 *   - Group (An array of groups, giving user access to some controlled resources); 
	 *   - Data (Any extra info stored in JSON)
	 * Using session control needs access to a db. This db will grow, it may get too big and 
	 * too slow at some day. Admin should delete out-of-date records periodically. 
	 * ************************************************************************************/

	class Bearweb_Session { use Bearweb_DatabaseBacked;

		/** Create a user. 
		 * Internal has is applied to password before write to DB. 
		 * @param string	$id		User ID
		 * @param string	$name		Name
		 * @param string	$password	Password
		 * @throws BW_ClientError Non-unique user ID
		 * @throws BW_DatabaseServerError Fail to write user into DB
		 */
		public static function createUser(string $id, string $name, string $password): void {
			try {
				$sql = self::$db->prepare('INSERT INTO `User` (`ID`, `Name`, `Password`, `RegisterTime`, `Group`, `Data`) VALUES (?, ?, ?, ?, ?, ?)');
				$sql->bindValue(1,	$id,						PDO::PARAM_STR	);
				$sql->bindValue(2,	$name,						PDO::PARAM_STR	);
				$sql->bindValue(3,	password_hash($password, PASSWORD_DEFAULT),	PDO::PARAM_STR	);
				$sql->bindValue(4,	$_SERVER['REQUEST_TIME'],			PDO::PARAM_INT	);
				$sql->bindValue(5,	'',						PDO::PARAM_STR	);
				$sql->bindValue(6,	'',						PDO::PARAM_STR	);
				$sql->execute();
				$sql->closeCursor();
			} catch (Exception $e) {
				if (strpos($e->getMessage(), 'UNIQUE'))
					throw new BW_ClientError('User ID has been used', 409);
				else
					throw new BW_DatabaseServerError('Cannot insert new user into DB: '.$e->getMessage(), 500);
			}
		}

		/** Get a user. 
		 * If $password is given, also verify the password. 
		 * @param string	$id		User ID
		 * @param string	$password	If supplied, verify password
		 * @return array	User info: [string ID, string name, int register time, array group, array data]
		 * @throws BW_ClientError User not found
		 * @throws BW_ClientError Wrong password (if $password is given)
		 * @throws BW_DatabaseServerError Fail to read user from DB
		 */
		public static function getUser(string $id, string $password = ''): array {
			try {
				$sql = self::$db->prepare('SELECT `ID`, `Name`, `Password`, `RegisterTime`, `Group`, `Data` FROM `User` WHERE `ID` = ?');
				$sql->bindValue(	1,	$id,	PDO::PARAM_STR	);
				$sql->execute();
				$user = $sql->fetch();
				$sql->closeCursor();

				if (!$user)
					throw new BW_ClientError('User not found', 404);

				if ($password) {
					if (!password_verify($password, $user['Password'])) {
						throw new BW_ClientError('Wrong password', 400);
					} else if (password_needs_rehash($user['Password'], PASSWORD_DEFAULT)) {
						try {
							$new = password_hash($password, PASSWORD_DEFAULT);
							$sql = self::$db->prepare('UPDATE `USer` SET `Password` = ? WHERE `ID` = ?');
							$sql->bindValue(	1,	$new,	PDO::PARAM_STR	);
							$sql->bindValue(	2,	$id,	PDO::PARAM_STR	);
							$sql->execute();
							$sql->closeCursor();
						} catch (Exception $e) {
							error_log('[BW] Fail to update password rehash, retry next time: '.$e->getMessage());
						}
					} // else, password check pass, no action to perform
				}

				return [
					'ID' => $user['ID'], 'Name' => $user['Name'],
					'RegisterTime' =>	$user['RegisterTime'] ?? 0,
					'Group' =>		$user['Group'] ? explode(',', $user['Group']) : [],
					'Data' =>		json_decode($user['Data'] ? $user['Data'] : '{}', true) # Note: ?? only checks for null, json_decode('' ?? '{}') gives json_decode('') gives null instead of empty array []
				];
			} catch (BW_ClientError $e) {
				throw $e;
			} catch (Exception $e) {
				throw new BW_DatabaseServerError('Failed to read User DB: '.$e->getMessage(), 500);
			}
		}

		/** Update user data. 
		 * This function throws BW_DatabaseServerError if there is no such user. The template should make sure the user existed. 
		 * @param string	$id		User ID
		 * @param ?string	$name		Name; pass null to use orginal value
		 * @param ?array	$group		An array of string representing group; pass null to use orginal value
		 * @param ?mixed	$data		Any data except null (use false as a workaround); pass null to use orginal value
		 * @param ?string	$password	Password; pass null to use orginal value
		 * @throws BW_DatabaseServerError Failed to update user DB (server error or no such user)
		 */
		public static function setUser(string $id, ?string $name, ?array $group, ?array $data, ?string $password): void {
			$group =	is_null($group) ?	null : implode(',', $group);
			$data =		is_null($data) ?	null : json_encode($data);
			$password =	is_null($password) ?	null : password_hash($password, PASSWORD_DEFAULT);
			try {
				$sql = self::$db->prepare('UPDATE `User` SET
					`Name` =	IFNULL(?, `Name`),
					`Group` =	IFNULL(?, `Group`),
					`Data` =	IFNULL(?, `Data`),
					`Password` =	IFNULL(?, `Password`)
				WHERE `URL` = ?');
				$sql->bindValue(1,	$name,		PDO::PARAM_STR	);
				$sql->bindValue(2,	$group,		PDO::PARAM_STR	);
				$sql->bindValue(3,	$data,		PDO::PARAM_STR	);
				$sql->bindValue(4,	$password,	PDO::PARAM_STR	);
				$sql->bindValue(5,	$id,		PDO::PARAM_STR	);
				$sql->execute();
				$sql->closeCursor();
			} catch (Exception $e) {
				throw new BW_DatabaseServerError('Fail to update user in DB: '.$e->getMessage(), 500);
			}
		}

		public readonly array	$session;	# ID, Create (create time), User
		public readonly array	$transaction;	# ID, Client IP
		public readonly array	$user;		# ID, Name, RegisterTime, Group, Data
		private string	$log = '';		# Cached log (to minimize DB transaction) Will be synch to DB at the end of script.

		private const	DEFAULT_SESSION = [
			'ID' =>			'',
			'Create' =>		0,
			'User' =>		''
		];
		private const	DEFAULT_TRANSACTION = [
			'ID' =>			'',
			'IP' =>			''
		];
		private const	DEFAULT_USER = [
			'ID' =>			'',
			'Name' =>		'',
			'RegisterTime' =>	0,
			'Group' =>		[],
			'Data' =>		[]
		];

		/** Constructor. 
		 * If session control disabled, this function does nothing; otherwise, this function will automatically read client-side cookies, create/bind session, 
		 * record transaction, write cookie to client, with built-in user-supplied data check. 
		 * In case of error or session control disabled, this routine fallback silently and uses default values ('' or 0 or [], all gives false). 
		 * @param string $url Request URL
		*/
		public function __construct(string $url) {
			if (!self::$db) {
				$this->session = self::DEFAULT_SESSION;
				$this->user = self::DEFAULT_USER;
				$this->transaction = self::DEFAULT_TRANSACTION;
				return;
			}

			try {
				self::$db->beginTransaction();
				$session = null;
				$transaction = null;
				$user = null;

				// Update session if cookie valid and session found and matched in db
				if (
					isset($_COOKIE[ BW_Config['Session_CookieSID'] ]) &&
					isset($_COOKIE[ BW_Config['Session_CookieKey'] ]) &&
					strlen(base64_decode($_COOKIE[ BW_Config['Session_CookieSID'] ], true)) == 96 && #strict mode, return false if not base64, then strlen will be 0
					strlen(base64_decode($_COOKIE[ BW_Config['Session_CookieKey'] ], true)) == 96
				) {
					$sql = self::$db->prepare('UPDATE `Session` SET `LastUse` = ? WHERE `ID` = ? AND `Key` = ? AND `LastUse` > ? RETURNING `ID`, `Create`, `User`');
					$sql->bindValue(	1,	$_SERVER['REQUEST_TIME'],				PDO::PARAM_INT	);
					$sql->bindValue(	2,	$_COOKIE[ BW_Config['Session_CookieSID'] ],		PDO::PARAM_STR	);
					$sql->bindValue(	3,	$_COOKIE[ BW_Config['Session_CookieKey'] ],		PDO::PARAM_STR	);
					$sql->bindValue(	4,	$_SERVER['REQUEST_TIME'] - BW_Config['Session_Expire'],	PDO::PARAM_INT	);
					$sql->execute();
					$session = $sql->fetch();
					$sql->closeCursor();
				}

				// Session found and valid: read user info
				if ($session) {
					if ($session['User']) { # User is not '' (guest)
						$sql = self::$db->prepare('SELECT `ID`, `Name`, `RegisterTime`, `Group` FROM `User` WHERE `ID` = ?');
						$sql->bindValue(	1,	$session['User'],	PDO::PARAM_STR	);
						$sql->execute();
						$user = $sql->fetch();
						$sql->closeCursor();
						if ($user) {
							$user['RegisterTime'] = $user['RegisterTime'] ?? 0;
							$user['Group'] = explode(',', $user['Group']);
							$user['Data'] = $user['Data'] ?? [];
						} else {
							error_log('[BW] Broken forgine key in User DB: '.$session['User'].' (SID: '.$session['ID'].')');
							$user = self::DEFAULT_USER;
						}
					} else {
						$user = self::DEFAULT_USER;
					}
					

				// Create a new session
				} else {
					$id = '';
					$key = base64_encode(random_bytes(96));
					$sql = self::$db->prepare('INSERT INTO `Session` (`ID`, `Create`, `LastUse`, `User`, `Key`) VALUES (?,?,?,?,?) RETURNING `ID`, `Create`, `User`');
					$sql->bindParam(	1,	$id,				PDO::PARAM_STR	); #By reference
					$sql->bindValue(	2,	$_SERVER['REQUEST_TIME'],	PDO::PARAM_INT	);
					$sql->bindValue(	3,	$_SERVER['REQUEST_TIME'],	PDO::PARAM_INT	);
					$sql->bindValue(	4,	'',				PDO::PARAM_STR	); #New session is always issused to new user, it can only be changed by login API with existed SID
					$sql->bindValue(	5,	$key,				PDO::PARAM_STR	);
					for ($try = 5; ; $try--) {
						try {
							$id = base64_encode(random_bytes(96));
							$sql->execute(); # Retry if fail
							$session = $sql->fetch();
							$sql->closeCursor();
							break;
						} catch(Exception $e) {
							if ( strpos($e->getMessage(), 'UNIQUE') === false || $try < 0 ) # Catch and retry if UNIQUE constraint fails within retry times limit
								throw $e;
						}
					}
					setcookie(Bearweb_Session_CookieSID, $id, 0, '/', '', true, true );
					setcookie(Bearweb_Session_CookieKey, $key, 0, '/', '', true, false );

					$user = [ # New session has no user bind
						'ID' =>			'',
						'Name' =>		'',
						'RegisterTime' =>	0,
						'Group' =>		[],
						'Data' =>		[]
				];
				}

				// Record transaction
				$transaction = [
					'ID' =>			'',
					'IP' =>			$_SERVER['REMOTE_ADDR']
				];
				$sql = self::$db->prepare('INSERT INTO `Request` (`ID`, `URL`, `Time`, `IP`, `SID`, `State`, `Log`) VALUES (?,?,?,?,?,?,?)');
				$sql->bindParam(	1,	$transaction['ID'],		PDO::PARAM_STR	); #By reference
				$sql->bindValue(	2,	$url,				PDO::PARAM_STR	);
				$sql->bindValue(	3,	$_SERVER['REQUEST_TIME'],	PDO::PARAM_INT	);
				$sql->bindValue(	4,	$transaction['IP'],		PDO::PARAM_STR	);
				$sql->bindValue(	5,	$session['ID'],			PDO::PARAM_STR	);
				$sql->bindValue(	6,	0,				PDO::PARAM_INT	);
				$sql->bindValue(	7,	'',				PDO::PARAM_STR	);
				for ($try = 5; ; $try--) {
					try {
						$transaction['ID'] = base64_encode(random_bytes(96)); #128 characters
						$sql->execute();
						$sql->closeCursor();
						break;
					} catch(Exception $e) {
						if ( strpos($e->getMessage(), 'UNIQUE') === false || $try < 0 ) # Catch and retry if UNIQUE constraint fails within retry times limit
							throw $e;
					}
				}

				$this->session = $session;
				$this->transaction = $transaction;
				$this->user = $user;

				self::$db->commit();
			} catch (Exception $e) {
				self::$db->rollBack();
				error_log('Session control fail: '.$e);

				$this->session = self::DEFAULT_SESSION;
				$this->user = self::DEFAULT_USER;
				$this->transaction = self::DEFAULT_TRANSACTION;
			}
		}

		/** Destructor. 
		 * If session control disabled, this function does nothing; otherwise, it: 
		 * - Sets response state code (HTTP code) at the end of the request. 
		 * - Syncs the transaction log to DB. 
		 * Error is sliently ignored. 
		 */
		public function __destruct() {
			if (!self::$db) return;

			try {
				$sql = self::$db->prepare('UPDATE Request SET `State` = ?, `Log` = `Log` || ? WHERE ID = ?');
				$sql->bindValue(	1,	http_response_code(),		PDO::PARAM_INT	);
				$sql->bindValue(	2,	$this->log,			PDO::PARAM_STR	);
				$sql->bindValue(	3,	$this->transaction['ID'],	PDO::PARAM_STR	);
				$sql->execute();
				$sql->closeCursor();
			} catch (Exception $e) {
				error_log('[BW] Cannot sync session info to DB at the end of transaction: '.$e->getMessage());
			}
		}

		/** Bind a user to the session. 
		 * Does nothing if session disabled. 
		 * In effect in next transaction. 
		 * @param string $uid User ID
		 * @throws BW_DatabaseServerError Failed to write the user ID into session DB
		 */
		public function bindUser(string $uid) {
			if (!self::$db) return;

			try {
				$sql = self::$db->prepare('UPDATE `Session` SET `User` = ? WHERE ID = ?');
				$sql->bindValue(	1,	$uid,			PDO::PARAM_STR	);
				$sql->bindValue(	2,	$this->session['ID'],	PDO::PARAM_STR	);
				$sql->execute();
				$sql->closeCursor();
				return;
			} catch (Exception $e) {
				throw new BW_DatabaseServerError('Cannot update session user in DB: '.$e->getMessage(), 500);
			}
		}

		/** Append log to transaction record. 
		 * Does nothing if session control disabled, and returns false in all cases. 
		 * This function will append the given log to the cached log in this instance. If $db is set to true, it will also attempt to write the cached log to DB. 
		 * Furthermore, log will be write to the DB at the end of transaction (in the destructor); however, there is a slight chance that will fail. 
		 * User may write a series of intermediate log during an operation, and synch them at the end of that operation by using $db, to minimize DB transaction. 
		 * @param string $log Log to append
		 * @param bool $db If set to true, will attemp to write the log into database
		 * @return bool True if success, false if DB write failed
		 */
		public function log(string $log, $db = false) {
			if (!self::$db) return false;

			$this->log .= $s;

			if (!$db) return true; # Synch not required
			if (!$this->log) return true; # No need to synch empty cache

			try {
				$sql = self::$db->prepare('UPDATE `Request` SET `Log` = `Log` || ? WHERE ID = ?');
				$sql->bindValue(	1,	$this->cached[self::FIELD_TLOG],	PDO::PARAM_STR	);
				$sql->bindValue(	2,	$this->cached[self::FIELD_SID],		PDO::PARAM_STR	);
				$sql->execute();
				$sql->closeCursor();
				$this->log = '';
				return true;
			} catch (Exception $e) {
				error_log('[BW] Cannot write transaction log into DB: '.$e->getMessage());
				return false;
			}
		}
	}

	
	set_error_handler(function($no, $str, $file, $line){ if (error_reporting() == 0) { return false; } throw new ErrorException($str, 0, $no, $file, $line); });
	class BW_Error extends Exception { function __construct(string $msg, int $code) { parent::__construct( get_class($this).' - '.$msg , $code ); } } // Base Bearweb error
	class BW_ServerError extends BW_Error {} // Base server-end error
	class BW_WebServerError extends BW_ServerError{} // Server-side front-end error: PHP script error
	class BW_DatabaseServerError extends BW_ServerError{} // Server-side back-end error: Database (local) error
	class BW_ExternalServerError extends BW_ServerError{} // Server-side cloud-end error: External server error, such as token server, object storage server...
	class BW_ClientError extends BW_Error {} // Client-side error: Bad request from client
	
	try { Bearweb_Site::init(BW_Config['Site_DB']); } catch (Exception $e) { error_log('[BW] Fatal error: Bearweb_Site module init failed!'); http_response_code(500); exit('500 - Server Fatal Error!'); }
	try { if (array_key_exists('Session_DB', BW_Config)) Bearweb_Session::init(BW_Config['Session_DB']); } catch (Exception $e) { error_log('[BW] Cannot connect to Session DB'); }
	trait Bearweb_DatabaseBacked{
		/** Database connection resource, null if DB is not ready (should use fallback methods). 
		*/
		public static ?PDO $db = null;

		/** Init Bearweb sitemap module. Do NOT call this routine. This routine should only be called by Bearweb framework for each module at the loading time. 
		 * @param string $dsn Data source name
		 * @throws BW_DatabaseServerError Fail to open sitemap db: Since Bearweb_Site module is mandatory, this is a critical error. Should retry or terminate!
		 */
		public static function init(string $dsn): void {
			try { self::$db = new PDO($dsn, null, null, [
				PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
				PDO::ATTR_TIMEOUT => 10, #10s waiting time should be far more than enough
				PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
			]); } catch (Exception $e) { throw new BW_DatabaseServerError('Fail to open DB: '.$e->getMessage(), 500); }
		}
	}
?>
