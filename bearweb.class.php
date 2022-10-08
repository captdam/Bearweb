<?php
	date_default_timezone_set('UTC'); //Always use UTC!

	set_error_handler(function($no, $str, $file, $line){ if (error_reporting() == 0) { return false; } throw new ErrorException($str, 0, $no, $file, $line); });
	/** Base Bearweb error */
	class BW_Error extends Exception { function __construct(string $msg, int $code) { parent::__construct( get_class($this).' - '.$msg , $code ); } }
	/** Base server-end error */
	class BW_ServerError extends BW_Error {}
	/** Server-side front-end error: PHP script error */
	class BW_WebServerError extends BW_ServerError{}
	/** Server-side back-end error:	Database (local) error */
	class BW_DatabaseServerError extends BW_ServerError{}
	/** Server-side cloud-end error: External server error, such as database (cloud), token server, object storage server... */
	class BW_ExternalServerError extends BW_ServerError{}
	/** Client-side error: Bad request from client */
	class BW_ClientError extends BW_Error {}

	class Bearweb {
		private ?Bearweb_User $user = null;
		private ?Bearweb_Session $session = null;
		private ?Bearweb_Site $site = null;

		/** Use Bearweb to server a resource. 
		 * @param string $url Request URL given by front-end server
		 */
		public function __construct(string $url) {
			$BW = $this;

			header('X-Powered-By: Bearweb 7.0');

			Bearweb_Site::init(Bearweb_Site_SiteDB);
			Bearweb_Session::init(Bearweb_Session_SessionDB);

			$this->session = new Bearweb_Session($url);

			$this->user = new Bearweb_User('');

			$this->site = new Bearweb_Site($url, $this->user->getID(), $this->user->getGroup());
			
			try {
				ob_start();
				$template = Bearweb_Site_TemplateDir.$this->site->getTemplate()[0].'.php';
				if (!file_exists($template)) throw new BW_ServerError('Template not found: '.$this->site->getTemplate()[0], 500);
				include $template;
			} catch (Exception $e) {
				ob_clean(); ob_start();
				if ($e instanceof BW_ClientError) {
					$this->site->setErrorTemplate($e->getCode().' - Client error', $e->getMessage(), $e->getCode());
				} else if ($e instanceof BW_ServerError) {
					if (Bearweb_Site_HideServerError)
						$this->site->setErrorTemplate('500 - Internal Error', 'Server-side internal error.', 500);
					else
						$this->site->setErrorTemplate($e->getCode().' - Server Error', $e->getMessage(), $e->getCode());
				} else {
					if (Bearweb_Site_HideServerError)
						$this->site->setErrorTemplate('500 - Internal Error', 'Server-side internal error.', 500);
					else
						$this->site->setErrorTemplate('500 - Unknown Error', $e->getMessage(), 500);
				}
				$template = Bearweb_Site_TemplateDir.$this->site->getTemplate()[0].'.php';
				include $template;
			}
			
		}
	}

	/** Bearweb sitemap ********************************************************************
	 * Bearweb is a database-driven website framework. Requesting a webpage is in fact 
	 * fetching data from the database, with access control and some data manipulation. This 
	 * DB is called Sitemap because the table is a map of the website. 
	 * ************************************************************************************/

	class Bearweb_Site {

		private static ?PDO $db = null;			# Sitemap DB

		/** Init Bearweb sitemap module. 
		 * This routine must be called before creating any instance. This routine prepares the required for subsequent site operations. 
		 * If this routine failed, the framework will prepare an error template. 
		 * @param string $db Sitemap DB file dir
		 * @throws BW_DatabaseServerError Fail to open sitemap db: Critical error, should retry or terminate! 
		 */
		public static function init(string $dir) {
			try {
				self::$db = new PDO('sqlite:'.$dir);
				self::$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
				self::$db->setAttribute(PDO::ATTR_TIMEOUT, 10); #10s waiting time should be far more than enough
				self::$db->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
			} catch (Exception $e) { throw new BW_DatabaseServerError('Fail to open sitemap db: '.$e->getMessage(), 500); }
		}

		private string	$url = '';			# PK
		private string	$category = '';			# Management purpose (user field, not used by Bearweb)
		private array	$template = ['page', 'error'];	# Process control, default: use error page
		private string	$owner = '';			# Edit control, default: owned by server
		private int	$create = -1, $modify = -1;	# Create/modify time, default: auto generated resource has no such time
		private array	$meta = ['', '', ''];		# Resource meta data: title, keywords and description, separated by '\n'
		private string	$state = '';			# Resource state, default: S (special) for error page
		private ?array	$error = null;			# For error. If set, error template will be used. ['Error title', 'Error detail']

		/** Constructor. 
		 * Fetch resource information from site db (except content and aux data which are too large). 
		 * If DB access fail, no record found or access control failed, a dummy resource info is supplied that can be feed to page/error template. 
		 * HTTP headers are set appropriately based on resource data and result of this routine. 
		 * @param string $url Page URL
		 * @param string $user Session user for resource access control, use '' for guest
		 * @param array $ugroup A list of groups for that session user, use [] for guest
		*/
		public function __construct(string $url, string $user, array $ugroup) {
			$this->url = $url;

			/* Sitemap lookup */
			$site = null;
			try {
				if (!self::$db) throw new Exception();
				$sql = self::$db->prepare('SELECT `Category`, `Template`, `Owner`, `Create`, `Modify`, `Meta`, `State` FROM Sitemap WHERE URL = ?');
				$sql->bindValue(	1,	$url,	PDO::PARAM_STR	);
				$sql->execute();
				$site = $sql->fetch();
				$sql->closeCursor();
			} catch (Exception $e) { /* $site = null for db access fail or table error, $site = false if not found */ }

			/* Resource access control */
			$accessControlPass = false;
			if ($site === null) {
				if (Bearweb_Site_HideServerError)
					$this->site->setErrorTemplate('500 - Internal Error', 'Server-side internal error.', 500);
				else
				$this->setErrorTemplate('500 - Framework Error', 'Cannot access sitemap database.', 500);
			} else if ($site === false) {
				$this->setErrorTemplate('404 - Not Found', 'Resource not found.', 404);
			} else if ( substr($site['State'], 0, 1) == 'R' ) {
				header('Location: /'.substr($site['State'], 1));
				$this->setErrorTemplate('301 - Moved Permanently', 'Resource has been moved permanently.', 301);
			} else if ( substr($site['State'], 0, 1) == 'r' ) {
				header('Location: /'.substr($site['State'], 1));
				$this->setErrorTemplate('302 - Moved Temporarily', 'Resource has been moved temporarily.', 302);
			} else if ($site['State'] == 'A') {
				if (
					$user == '' ||
					( $user != $site['Owner'] && in_array('ADMIN', $ugroup) ) ||
					!count(array_intersect( $this->ugroup , explode(',', substr($site['State'], 1)) ))
				) {
					if (Bearweb_Site_HideAuth)
						$this->setErrorTemplate('404 - Not Found', 'Resource not found.', 404);
					else
						$this->setErrorTemplate('401 - Unauthorized', 'Controled resource. Cannot access, please login first.', 401);
				}
			} else if ($site['State'] == 'P') {
				if (
					$user == '' ||
					( $user != $site['Owner'] && in_array('ADMIN', $ugroup) )
				) {
					if (Bearweb_Site_HideAuth)
						$this->setErrorTemplate('404 - Not Found', 'Resource not found.', 404);
					else
						$this->setErrorTemplate('403 - Forbidden', 'Locked resource. Access denied.', 403);
				}
			}

			if (!$this->error) { # If error template not set (db access ok, resource found, access control passed), send actual page info
				$this->category	= $site['Category'];
				$this->template	= explode('/', $site['Template']);;
				$this->owner	= $site['Owner'];
				$this->create	= $site['Create'];
				$this->modify	= $site['Modify'];
				$this->meta	= explode("\n", $site['Meta'], 3);
				$this->state	= $site['State'];
			}
		}

		public function __destruct() {
			if ($this->modify == -1) { # Create E-tag for content and auto generated content (error page is auto generated), for client-side cache
				header('Last-Modified: '.date('D, j M Y G:i:s').' GMT');
				header('Etag: '.base64_encode(random_bytes(24))); 
				header('Cache-Control: no-store');
			}
			else {
				header('Last-Modified: '.date('D, j M Y G:i:s', $this->modify).' GMT');
				header('Etag: '.base64_encode($this->modify));
				header('Cache-Control: private, max-age=3600');
			}
		}

		/** Get error info set by $this->setErrorTemplate(). 
		 * @return ?array [string Error name, string Detail description of the error]
		 */
		public function getErrorTemplate() { return $this->error; }

		/** Overwrite data in this calss to use error template. 
		 * @param string $title Error name
		 * @param string $detail Detail description of the error
		 * @param ?int $code If not null, HTTP code will be set and send to client
		 */
		public function setErrorTemplate(string $title, string $detail, ?int $code) {
			if ($code)
				http_response_code($code);

			$this->category = '';
			$this->template = ['page', 'error'];
			$this->owner = '';
			$this->create = -1; # No time for auto-generated page
			$this->modify = -1;
			$this->meta = ['Error - '.$title, 'Bearweb', 'Bearweb error page'];
			$this->state = 'S';
			$this->error = [$title, $detail];
		}

		/** Get resource category. 
		 * Used in template to list resource under a specific category. 
		 * @return string Category
		 */
		public function getCategory(): string { return $this->category; }

		/** Get template for this resource: Primay, secondary, ... 
		 * Templates are PHP scripts that controls the actual mainipulation on the data. 
		 * @return array [Primay template[, Secondary template[, ...]]]
		 */
		public function getTemplate(): array { return $this->template; }

		/** Get resource owner. 
		 * Owner can be used for HTML author field. 
		 * Owner can also be used to set edition privillidge check in case user can modify the website. 
		 * Special owner is '' which indicates system. User '' indicates guest.
		 * @return string Owner username
		 */
		public function getOwner(): string { return $this->owner; }

		/** Get timestamp of resource creation and last modification. 
		 * @return array [Create timestamp, Last Modify timestamp]
		 */
		public function getTime(): array { return [$this->create, $this->modify]; }

		/** Get resource Meta data: title, keywords and description. 
		 * Resource may not have description or my not have both keywords and description. Use $x[2] ?? 'default value' to avoid error. 
		 * @return array [Title[, keywords[, description]]]
		 */
		public function getMeta(): array { return $this->meta; }

		/** Get resource state. State can be one of the following: 
		 * R_url:		Redirect moved permanently (301) to _url, where url is a string; 
		 * r_url:		Same as R, but moved temporarily (302); 
		 * A_group1,_group2...:	Unauthorized. Resource is only accessible for owner, user in admin group and user in groups defined by _group; 
		 * P:			Pending / locked. Resource is only accessible for owner and people in admin group; 
		 * S:			Special page, access granted for all user and guest, but not included in SEO sitemap index; 
		 * O:			OK, access granted for all user and guest; 
		 * Others:		Undefined, do not use. 
		 * @return string State
		 */
		public function getState(): string { return $this->state; }

		/** Modify resource data. 
		 * Use an array to construct the modify data, allowed members are: string Category, string[1+] Template, string Owner, string[0-3] Meta, string State. 
		 * If any of the members is not supplied, that member will not be modified. 
		 * This routine will not modify create time. This call will update the modify time. 
		 * @throws BW_DatabaseServerError Fail to update sitemap db
		 */
		public function setSite(array $site) {
			$category	= isset($site['Category'])	? $site['Category']	: $this->category;
			$template	= isset($site['Template'])	? $site['Template']	: $this->template;
			$owner		= isset($site['Owner'])		? $site['Owner']	: $this->owner;
			$meta		= isset($site['Meta'])		? $site['Meta']		: $this->meta;
			$state		= isset($site['State'])		? $site['State']	: $this->state;

			try {
				if (!self::$db) throw new Exception('Sitemap database not connected');
				$sql = self::$db->prepare('UPDATE Site SET Category = ?, Template = ?, Owner = ?, Meta = ?, State = ?, Mofify = ? WHERE URL = ?');
				$sql->bindValue(	1,	$category,			PDO::PARAM_STR	);
				$sql->bindValue(	2,	implode('/', $template),	PDO::PARAM_STR	);
				$sql->bindValue(	3,	$owner,				PDO::PARAM_STR	);
				$sql->bindValue(	4,	implode("\n", $meta),		PDO::PARAM_STR	);
				$sql->bindValue(	5,	$state,			PDO::PARAM_STR	);
				$sql->bindValue(	6,	(int)$_SERVER['REQUEST_TIME'],	PDO::PARAM_STR	);
				$sql->bindValue(	7,	$this->url,			PDO::PARAM_STR	);
				$sql->execute();
				$sql->closeCursor();
			} catch (Exception $e) { throw new BW_DatabaseServerError('Cannot update sitemap database: '.$e->getMessage(), 500); }

			$this->category	= $category;
			$this->template	= $template;
			$this->owner	= $owner;
			$this->meta	= $meta;
			$this->state	= $state;
			$this->modify	= (int)$_SERVER['REQUEST_TIME'];
		}

		/** Fetch resource content or auxiliary data. 
		 * Content can be HTML code for webpage or binary data for object page, actual manipulation determined by template. 
		 * Auxiliary data is data that is less commonly used than content. This can be source code used to generate a HTML content, actual manipulation determined by template. 
		 * Content is designed to be stored saperated from aux data, so it can be directly used without process for performance purpose. 
		 * Content and aux are large; therefore, they are not loaded when construct this object in case they are not required. 
		 * @param string $var 'Content' for content, 'Aux' for aux data. This call can be used to fetch raw data of other information, but not recommended
		 * @return string Content or auxiliary data
		 * @throws BW_DatabaseServerError Fail to read sitemap db
		 */
		public function getData(string $var): ?string {
			try {
				if (!self::$db) throw new Exception('Sitemap database not connected');
				$sql = self::$db->prepare('SELECT '.$var.' FROM Sitemap WHERE URL = ?');
				$sql->bindValue(	1,	$this->url,	PDO::PARAM_STR	);
				$sql->execute();
				$data = $sql->fetch();
				$sql->closeCursor();
			} catch (Exception $e) { throw new BW_DatabaseServerError('Cannot read sitemap database: '.$e->getMessage(), 500); }

			return $data[$var];
		}

		/** Modify resource content or auxiliary data. If fail, resource content and auxiliary data will not be modified. 
		 * This call will update the modify time. 
		 * @param string $var 'Content' for content, 'Aux' for aux data. This call can be used to fetch raw data of other information, but not recommended
		 * @param string $content Content to write
		 * @throws BW_DatabaseServerError Fail to update sitemap db
		 */
		public function setData(string $var, string $content) {
			try {
				if (!self::$db) throw new Exception('Sitemap database not connected');
				$sql = self::$db->prepare('UPDATE Sitemap SET ? = ?, Modify = ? WHERE URL = ?');
				$sql->bindValue(	1,	$var,				PDO::PARAM_STR	);
				$sql->bindValue(	2,	$content,			PDO::PARAM_STR	);
				$sql->bindValue(	3,	(int)$_SERVER['REQUEST_TIME'],	PDO::PARAM_INT	);
				$sql->bindValue(	4,	$this->url,			PDO::PARAM_STR	);
				$sql->execute();
				$sql->closeCursor();
			} catch (Exception $e) { throw new BW_DatabaseServerError('Cannot update sitemap database: '.$e->getMessage(), 500); }

			$this->modify = (int)$_SERVER['REQUEST_TIME'];
		}
	}


	/** Session control ********************************************************************
	 * A session is a set of request made by the same user (session id and key). With
	 * session control enabled, the Session ID (auto-generated unique ID) is recorded with 
	 * following info: 
	 *   - Create time and last use time (session automatically expire after a period), 
	 *   - Username of the client (if user control enabled and client has login), 
	 *   - Key (key is invisible to client-side js script, server use it verify session id), 
	 *   - Salt (for client-side hash). 
	 * With session control enabled, each time a request is received, the Request ID 
	 * (auto-generated unique ID) is recorded with following info: 
	 *   - Request URL, 
	 *   - Timestamp when the request is received on server side, 
	 *   - Client IP, 
	 *   - Assoicated session ID, 
	 *   - State (response HTTP code sent to client), 
	 *   - Server log (for debug use). 
	 * Using session control needs access to a db $SessionDB$. This db will grow, it may get 
	 * too big and too slow at some day. Admin should delete out-of-date sessions and 
	 * request record periodically. 
	 * ************************************************************************************/

	class Bearweb_Session {

		private static  $db = null;

		/** Init and enable Bearweb session module. 
		 * This routine must be called before creating any instance. This routine prepares the required for subsequent session operations. 
		 * If this routine is not called, session control will be disabled. Default session data will be served. 
		 * @param string $db Session DB file dir
		 * @throws BW_DatabaseServerError Fail to open session db: Critical error, should retry or terminate! 
		 */
		public static function init(string $dir) {
			try {
				self::$db = new PDO('sqlite:'.$dir);
				self::$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
				self::$db->setAttribute(PDO::ATTR_TIMEOUT, 10); #10s waiting time should be far more than enough
				self::$db->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
			} catch (Exception $e) { throw new BW_DatabaseServerError('Fail to open session db: '.$e->getMessage(), 500); }
		}

		private string $sID = '';
		private int $sCreate = -1;
		private string $sUser = '';
		private string $sSalt = '';

		private string $tID = '';
		private string $tURL = '';

		/** Constructor. 
		 * This routine also checks client supplied session related cookie for session control. If client has valid session cookie that matched with a record in 
		 * the DB, the transaction will be associated with that session, session LastUse time will be updated; otherwise, new session will be issued. 
		 * If session control is not enabled by call Bearweb_Session::init(), or any error during process, this routine fallback silently and default values will be used. 
		 * @param string $url Request URL
		*/
		public function __construct(string $url) {
			$this->tURL = $url;

			/* Check and lookup client-side session cookie, update session last use if client-side session valid and matched */
			try {
				if (!self::$db) throw new Exception();

				/* Update session if cookie valid and session found in db */
				$session = null;
				if (
					isset($_COOKIE[Bearweb_Session_CookieSID]) &&
					strlen(base64_decode($_COOKIE[Bearweb_Session_CookieSID], true)) == 96 && #strict mode, return false if not base64, strlen will be 0
					isset($_COOKIE[Bearweb_Session_CookieKey]) &&
					strlen(base64_decode($_COOKIE[Bearweb_Session_CookieKey], true)) == 96
				) {
					$sql = self::$db->prepare('UPDATE Session SET `LastUse` = ? WHERE `ID` = ? AND `Key` = ? AND `LastUse` > ? RETURNING *');
					$sql->bindValue(	1,	(int)$_SERVER['REQUEST_TIME'],				PDO::PARAM_INT	);
					$sql->bindValue(	2,	$_COOKIE[Bearweb_Session_CookieSID],			PDO::PARAM_STR	);
					$sql->bindValue(	3,	$_COOKIE[Bearweb_Session_CookieKey],			PDO::PARAM_STR	);
					$sql->bindValue(	4,	(int)$_SERVER['REQUEST_TIME'] - Bearweb_Session_Expire,	PDO::PARAM_INT	);
					$sql->execute();
					$session = $sql->fetch(); # Return false if no session matched
					$sql->closeCursor();
				}

				if ($session) {
					$this->sID	= $session['ID'];
					$this->sCreate	= $session['Create'];
					$this->sUser	= $session['User'];
					$this->sSalt	= $session['Salt'];
				} else {
					$id = '';
					$key = base64_encode(random_bytes(96));
					$salt = base64_encode(random_bytes(96));

					$sql = self::$db->prepare('INSERT INTO `Session` (`ID`, `Create`, `LastUse`, `User`, `Key`, `Salt`) VALUES (?,?,?,?,?,?)');
					$sql->bindParam(	1,	$id,				PDO::PARAM_STR	); #By reference
					$sql->bindValue(	2,	(int)$_SERVER['REQUEST_TIME'],	PDO::PARAM_INT	);
					$sql->bindValue(	3,	(int)$_SERVER['REQUEST_TIME'],	PDO::PARAM_INT	);
					$sql->bindValue(	4,	'',				PDO::PARAM_STR	); #New session is always issused to new user, it can only be changed by login API with existed SID
					$sql->bindValue(	5,	$key,				PDO::PARAM_STR	);
					$sql->bindValue(	6,	$salt,				PDO::PARAM_STR	);
					for ($try = 5; ; $try--) {
						try {
							$id = base64_encode(random_bytes(96));
							$sql->execute();
							$sql->closeCursor();
							break;
						} catch(Exception $e) { # Catch and retry if UNIQUE constraint fails within retry limit times
							if ( strpos($e->getMessage(), 'UNIQUE') === false || $try < 0 )
								throw $e;
						}
					}

					$this->sID	= $id;
					$this->sCreate	= (int)$_SERVER['REQUEST_TIME'];
					$this->sUser	= '';
					$this->sSalt	= $salt;
					setcookie(Bearweb_Session_CookieSID, $id, 0, '/', '', true, true );
					setcookie(Bearweb_Session_CookieKey, $key, 0, '/', '', true, false );
					setcookie(Bearweb_Session_CookieSlt, $salt, 0, '/', '', true, false );
				}
			} catch (Exception $e) {
				$this->sID	= '';
				$this->sCreate	= -1;
				$this->sUser	= '';
				$this->sSalt	= '';
			}

			/* Request */
			try {
				if (!self::$db) throw new Exception();

				$id = '';

				$sql = self::$db->prepare('INSERT INTO `Request` (`ID`, `URL`, `Time`, `IP`, `SID`, `State`, `Log`) VALUES (?,?,?,?,?,?,?)');
				$sql->bindParam(	1,	$id,				PDO::PARAM_STR	); #By reference
				$sql->bindValue(	2,	$this->tURL,			PDO::PARAM_STR	);
				$sql->bindValue(	3,	(int)$_SERVER['REQUEST_TIME'],	PDO::PARAM_INT	);
				$sql->bindValue(	4,	$_SERVER['REMOTE_ADDR'],	PDO::PARAM_STR	);
				$sql->bindValue(	5,	$this->sID,			PDO::PARAM_STR	);
				$sql->bindValue(	6,	-1,				PDO::PARAM_INT	);
				$sql->bindValue(	7,	'',				PDO::PARAM_STR	);
				for ($try = 5; ; $try--) {
					try {
						$id = base64_encode(random_bytes(96)); #128 characters
						$sql->execute();
						$sql->closeCursor();
						break;
					} catch(Exception $e) { //Catch and retry if UNIQUE constraint fails within retry limit times
						if ( strpos($e->getMessage(), 'UNIQUE') === false || $try < 0 )
							throw $e;
					}
				}

				$this->tID = $id;
			} catch (Exception $e) {
				var_dump($e);
				$this->tID = '';
			}
		}

		/** Get session ID: 96/128 byte base64. 
		 * @return string Session ID, or '' if session control failed/disabled
		 */
		public function getSID(): string { return $this->sID; }

		/** Get session create timestamp. 
		 * @return int Session create timestamp, or -1 if session control failed/disabled
		 */
		public function getSCreate(): int { return $this->sCreate; }

		/** Get username associated with this session.
		 * @return string Username associated with this session, or '' for guest or session control failed/disabled
		 */
		public function getSUser(): string { return $this->sUser; }

		/** Associate this session to a user. 
		 * DB action. If failed or disabled, user will not be modified. 
		 * @param string $name Username
		 * @throws BW_DatabaseServerError Fail to update session user
		 */
		public function setSUser(string $name) {
			try {
				if (!self::$db) throw new Exception('Session database not connected');
				$sql = self::$db->prepare('UPDATE Session SET User = ? WHERE ID = ?');
				$sql->bindValue(	1,	$name,		PDO::PARAM_STR	);
				$sql->bindValue(	2,	$this->sID,	PDO::PARAM_STR	);
				$sql->execute();
				$sql->closeCursor();
				$this->sUser = $name;
			} catch (Exception $e) { throw new BW_DatabaseServerError('Cannot set session user: '.$e->getMessage(), 500); }
		}

		/** Get session salt: 96/128 byte base64. 
		 * Session salt is accessible to client-side JS, used for hash. 
		 * @return string Session salt, or '' if session control failed/disabled
		 */
		public function getSSalt(): string { return $this->sSalt; }
		
		/** Get transaction ID: 96/128 byte base64. 
		 * @return string Transaction ID, or '' if session control failed/disabled
		 */
		public function getTID(): string { return $this->tID; }

		/** Get transaction request timestamp. 
		 * @return int Request timestamp
		 */
		public function getTTime(): int { return (int)$_SERVER['REQUEST_TIME']; }

		/** Get transaction request client-side IP, can be IPv4 or IPv6. 
		 * @return string Client-side IP
		 */
		public function getTIP(): string { return $_SERVER['REMOTE_ADDR']; }

		/** Write log to transaction. 
		 * DB action. If failed or disabled, log will not be written. 
		 * @param string $log Log to write
		 * @throws BW_DatabaseServerError Fail to write log into request database
		 */
		public function log(string $s) {
			try {
				if (!self::$db) throw new Exception('Session database not connected');
				$sql = self::$db->prepare('UPDATE Request SET Log = Log || ? WHERE ID = ?');
				$sql->bindValue(	1,	$s,		PDO::PARAM_INT	);
				$sql->bindValue(	2,	$this->tID,	PDO::PARAM_STR	);
				$sql->execute();
				$sql->closeCursor();
			} catch (Exception $e) { throw new BW_DatabaseServerError('Cannot set transaction state '.$e->getMessage(), 500); }
		}

		/** Destructor. 
		 * Set state code at the end of the request. 
		 */
		public function __destruct() {
			try {
				if (!self::$db) throw new Exception();
				$sql = self::$db->prepare('UPDATE Request SET `State` = ? WHERE ID = ?');
				$sql->bindValue(	1,	http_response_code(),	PDO::PARAM_INT	);
				$sql->bindValue(	2,	$this->tID,		PDO::PARAM_STR	);
				$sql->execute();
				$sql->closeCursor();
			} catch (Exception $e) {}
		}
	}

	class Bearweb_User {
		private string $id = '';
		private array $group = [];

		public function __toString(): string { return $this->id; }

		public function __construct(string $id) {
			$this->id = $id;
		}

		public function getID(): string { return $this->id; }

		public function getGroup(): array { return $this->group; }
	}
