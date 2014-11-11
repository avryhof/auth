<?
 	class Auth {
		var $driver, $db, $loginfunction, $session_cache;
		var $session_storage = "session";
		var $authenticated = false;
		var $session_array = array();
		var $username = "";
		var $opts = array(
			"dsn"			=> "",
			"table" 		=> "users",
			"usernamecol" 	=> "username",
			"passwordcol" 	=> "password"
		);

		public function __construct($driver = "Database", $options, $loginFunction = false, $optional) {
			$this->startSession();
			$this->driver = $driver;
			$this->loginfunction = $loginFunction;
			$this->opts = $this->mergeoptions($this->opts, $options);

			/* We might want to be able to use other classes in the future */
			if (class_exists($this->driver)) {
				$this->db = new $this->driver($this->opts['dsn']);
			}
		}

		function start() {
            $this->startSession();
			$username = $this->db->escape($_POST['username']);
			$password = $this->hasher($_POST['password']);
			$users = $this->db->select(
					"*",
					$this->opts['table'],
					array(
						$this->opts['usernamecol'] => $username,
						$this->opts['passwordcol'] => $password
					)
				);
			if ($users->num_rows > 0) {
				$this->username = $username;
				$this->authenticated = true;
				$this->setSession("_auth_username", $this->username);
				$this->setSession("_auth_authenticated", $this->authenticated);
			} else {
				$this->setSession("_auth_authenticated", $this->authenticated);
				return $this->authenticated;
			}
		}

		function addUser($username, $password) {
			$this->db->insert($this->opts['table'], array(
					$this->opts['usernamecol'] => $username,
					$this->opts['passwordcol'] => $this->hasher($password)
				));
			return ($this->db->errno > 0 ? false: true);
		}

		function changePassword ($username, $password) {
			$this->db->update($this->opts['table'], array(
					$this->opts['passwordcol'] => $this->hasher($password)
				), array(
					$this->opts['usernamecol'] => $username
				));
			return ($this->db->errno > 0 ? false: true);
		}

		function removeUser ($username) {
			$this->db->delete($this->opts['table'], array(
					$this->opts['usernamecol'] => $username
				));
			return ($this->db->errno > 0 ? false: true);
		}

		function listUsers() {
			$users = $this->db->select($this->opts['usernamecol'], $this->opts['table'], array(), "", array($this->opts['usernamecol'] => "ASC"));
			$retn = array();
			while($user = $users->fetch_assoc()) {
				$un_col = $this->opts['usernamecol'];
				$retn[] = $user[$un_col];
			}
			return $retn;
		}

		function logout() {
			$this->username = "";
			$this->authenticated = false;
			$this->setSession("_auth_username", $this->username);
			$this->setSession("_auth_authenticated", $this->authenticated);
		}

		function getUsername() {
			return $this->username;
		}

		function checkAuth() {
			return ($this->authenticated == true ? true : false);
		}

		function setSession($key, $value) {
			if ($this->session_storage == "file") {
				$this->session_array[$key] = $value;
				file_put_contents($this->session_cache, serialize($this->session_array));
			} else {
				$_SESSION[$key] = $value;
			}
		}

		function getSession($key) {
			if ($this->session_storage == "file") {
				$this->session_array = unserialize(file_get_contents($this->session_cache));
				return $this->session_array[$key];
			} else {
				return $_SESSION[$key];
			}
		}

		function startSession() {
			if (session_status() == PHP_SESSION_DISABLED) {
				if (!is_dir(getcwd()."/sessions")) { mkdir(getcwd()."/sessions"); }
				$this->session_cache = getcwd()."/sessions/".md5($_SERVER['REMOTE_ADDR']);
				$this->session_storage = "file";
				if (file_exists($this->session_cache)) {
					$this->session_array = unserialize(file_get_contents($this->session_cache));
					if (is_array($this->session_array)) {
						$this->username = $this->session_array['_auth_username'];
						$this->authenticated = $this->session_array['_auth_authenticated'];
					}
				}
			} elseif (session_status() == PHP_SESSION_NONE) {
				session_start();
				if (isset($_SESSION['_auth_authenticated'])) {
					$this->authenticated = $this->session_array['_auth_authenticated'];
					$this->username = $this->session_array['_auth_username'];
				}
			}
		}

		function hasher($string, $hashfunction = false, $salt = "") {
			if ($hashfunction == "crypt") {
				/* See http://us3.php.net/manual/en/function.crypt.php */
				return(crypt($string,$salt));
			} elseif (function_exists($hashfunction)) {
				/* Your custom hashing function should accept a string, and salt, and return a string */
				return $hashfunction($string, $salt);
			} else {
				/* See http://us3.php.net/manual/en/function.hash.php */
				return(hash((!$hashfunction ? "md5" : $hashfunction), $string));
			}
		}

		function mergeoptions($defaults, $changes) {
			/* Only act if the changes are an array */
			if (is_array($changes) && count($changes) > 0) {
				/* Import defaults */
				$newopts = $defaults;
				foreach($changes as $o_key => $o_val) {
					/* Change the value if it exists, add it if it doesn't */
					if (array_key_exists($o_key,$defaults)) {
						$newopts[$o_key] = $o_val;
					} else {
						$newopts[$o_key] = $o_val;
					}
				}
				return $newopts;
			} else {
				return $defaults;
			}
		}
  }
?>