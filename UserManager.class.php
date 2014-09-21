<?php
	session_start();
	
	require_once "../userManagement/config.php";	// TODO: This might be a problem later, we have to solve. I think we should specify, that the user has to migrate some settings into his config file in his root folder.
	require "../userManagement/PermissionManager.class.php";

	class UserManager {
		// A dbWrapper instance
		private $DB;
		private $timezone;

		// This functions needs to be given a dbWrapper instance
		public function __construct ($DB, $timezone = "Europe/Berlin") {
			// Check if a session exists - if it does, get the users Data.
			// Check if session is still valid by the token and if the max time is exceeded

			// Apply the given dbWrapper instance
			$this->DB = $DB;
			$this->timezone = new DateTimeZone($timezone); // TODO: Parametrize this
		}

		public function login ($username, $password) {
			// generate new token
			// create session
			// return the userdata

			$parameters = Array();
			$parameters[":username"] = $username;
			$parameters[":password"] = $this->hash($password);
			
			// Get the user data
			$userdata = $this->DB->getRow("SELECT " . USER_TABLE . ".*, ".
			                                      "EXISTS(SELECT " . USER_TABLE . ".ID FROM " . ADMIN_TABLE . " WHERE " . USER_TABLE . ".ID = " . ADMIN_TABLE . ".UserID) AS isAdmin, " . 
			                                      "EXISTS(SELECT " . USER_TABLE . ".ID FROM " . ADMIN_TABLE . " WHERE " . USER_TABLE . ".ID = " . ADMIN_TABLE . ".UserID AND " . ADMIN_TABLE . ".Deleteable = 1) AS isDeleteable " .
			                                      "FROM " . USER_TABLE ." WHERE Name = :username AND PasswordHash = :password", $parameters);

			// If userdata was found, apply it to the session
			if ($userdata) {
				if ($userdata["Suspended"] == 1) {
					throw new Exception("User suspended");
				}

				$userdata["Token"] = $this->updateToken($userdata["ID"]);
				$_SESSION["userdata"] = $userdata;

				return $userdata;
			} else {
				return false;
			}
		}

		public function logout () {
			// remove the session
			// remove token from DB

			if ($_SESSION["userdata"]) {
				// set a new token (which invalidates the old one)
				$this->updateToken($_SESSION["userdata"]["ID"]);
				// and remove the userdata from the session
				unset($_SESSION["userdata"]);
			}
		}

		public function createUser ($username, $fullname, $password, $email, $gravatarEmail) {
			// check if the username or email adress is already registered
			$parameters = Array();
			$parameters[":username"] = $username;
			$parameters[":fullname"] = $fullname;
			$parameters[":passwordHash"] = $this->hash($password);
			$parameters[":email"] = $email;
			$parameters[":gravatarEmail"] = $gravatarEmail;

			$this->DB->query("INSERT INTO " . USER_TABLE . " (Name, Fullname, PasswordHash, Email, GravatarEmail) VALUES (:username, :fullname, :passwordHash, :email, :gravatarEmail)", $parameters);
		}

		public function changePassword ($userID, $newPassword) {
			$parameters = Array();
			$parameters[":userID"] = $userID;
			$parameters[":passwordHash"] = $this->hash($newPassword);

			$this->DB->query("UPDATE " . USER_TABLE . " SET PasswordHash = :passwordHash WHERE ID = :userID");
		}

		public function changeUsername ($userID, $newUsername) {
			// TODO: Evaluate if the name is already taken!
			$parameters = Array();
			$parameters[":userID"] = $userID;
			$parameters[":username"] = $newUsername; // TODO: html entities?!

			$this->DB->query("UPDATE " . USER_TABLE . " SET Name = :username WHERE ID = :userID");
		}
		
		public function confirmPassword($pass) {
			$data = Array();
			$data[":id"] = $this->getSession()["ID"];
			$pass2 = $this->DB->query("SELECT PasswordHash FROM " . USER_TABLE . " WHERE ID = :id", $data);
			return $pass == $pass2;
		}
		
		public function activateUser ($username) {
			$parameters = Array();
			$parameters[":username"] = $username;

			$this->DB->query("UPDATE " . USER_TABLE . " SET Suspended = 0 WHERE Name = :username", $parameters);
			
			// Returning the affected user's ID	
			return $this->getUserID($username);
		}

		public function getUserID ($username) {
			$parameters = Array();
			$parameters[":Username"] = $username;

			$result = $this->DB->getRow("SELECT ID FROM " . USER_TABLE . " WHERE Name = :Username", $parameters);
			
			if (isset($result["ID"]))
				return $result["ID"];
			else
				return false;
		}

		public function getLoginState () {
			return $this->checkLoginState();
		}
		
		public function getAllActiveUsers() {
			return $this->DB->getList("SELECT ID, Name, Fullname, Email, GravatarEmail, Deleteable, (" . ADMIN_TABLE . ".Deleteable IS NOT NULL) AS Admin FROM " . USER_TABLE . " LEFT JOIN " . ADMIN_TABLE . " ON " . USER_TABLE . ".ID = " . ADMIN_TABLE . ".UserID WHERE Suspended=0");
		}
		
		public function getAllSuspendedUsers() {
			return $this->DB->getList("SELECT ID, Name, Fullname, Email, GravatarEmail, (" . ADMIN_TABLE . ".Deleteable IS NOT NULL) AS Admin  FROM " . USER_TABLE . " LEFT JOIN " . ADMIN_TABLE . " ON " . USER_TABLE . ".ID = " . ADMIN_TABLE . ".UserID WHERE Suspended=1");
		}

		public function getSession () {
			if ($this->checkLoginState())
				return $_SESSION["userdata"];
			else 
				return false;
		}

		/// Internal functions ///
		// TODO: Can we replace this hash function by something better?
		private function hash ($value) {
			// Hash the given value with the md5 algorithm
			return md5($value);
		}

		private function generateToken () {
			// Generate a token
			return uniqid('', true);
		}

		private function updateToken ($userID) {
			$parameters = Array();
			$parameters[":userID"] = $userID;
			$parameters[":token"] = $this->generateToken();
			$parameters[":expiration"] = $this->computeExpiration();

			if ($this->tokenExists($userID))
				$this->DB->query("UPDATE " . SESSION_TABLE . " SET Token = :token, Expiration = :expiration WHERE UserID = :userID", $parameters);
			else 
				$this->DB->query("INSERT INTO " . SESSION_TABLE . " (UserID, Token, Expiration) VALUES (:userID, :token, :expiration)", $parameters);
			return $parameters[":token"];
		}

		private function tokenExists ($userID) {
			$parameters = Array();
			$parameters[":userID"] = $userID;

			$result = $this->DB->getRow("SELECT EXISTS(SELECT * FROM Session WHERE UserID = :userID)", $parameters);
			return array_values($result)[0];
		}

		private function computeExpiration () {
			$date = new DateTime("now", $this->timezone);
			$date->add(new DateInterval('PT10H')); // Add 10 hours

			return $date->format("Y-m-d H:i:s");
		}

		private function checkExpiration ($date) {
			
			if (gettype($date) == "string")
				// IF $date is a string, convert it into da DateTime Object
				$date = new DateTime($date, $this->timezone);

			$now = new DateTime("now", $this->timezone);

			return (($now->getTimestamp() - $date->getTimestamp()) < 0); // negative value if $date is in the future
		}

		public function getUserData ($userID) {
			$parameters = Array();
			$parameters[":userID"] = $userID;

			return $this->DB->getRow("SELECT *, ".
											"EXISTS(SELECT " . USER_TABLE . ".ID FROM " . ADMIN_TABLE . " WHERE " . USER_TABLE . ".ID = " . ADMIN_TABLE . ".UserID) AS isAdmin, " .
											"EXISTS(SELECT " . USER_TABLE . ".ID FROM " . ADMIN_TABLE . " WHERE " . USER_TABLE . ".ID = " . ADMIN_TABLE . ".UserID AND " . ADMIN_TABLE . ".Deleteable = 1) AS isDeleteable " .
											"FROM " . USER_TABLE . " WHERE ID = :userID", $parameters);
		}
		
		private function checkLoginState () {
			// TODO
			// compare session token and db token
			// see if token is expired (ONLY BY DB, NOT THE SESSION)
			if (isset($_SESSION["userdata"], $_SESSION["userdata"]["Token"])) {
				// TODO: is the token still valid?
				$parameters = Array();
				$parameters[":userID"] = $_SESSION["userdata"]["ID"];

				$result = $this->DB->getRow("SELECT Token, Expiration FROM " . SESSION_TABLE . " WHERE UserID = :userID", $parameters);

				if (is_array($result) && $result["Token"] == $_SESSION["userdata"]["Token"] && $this->checkExpiration($result["Expiration"])) {
					return true;
				} 
			}

			return false;
		}
		
		//Checks if the current user is an administrator
		public function checkAdmin() {
			return (bool) $this->getSession()["isAdmin"];
		}
		
		//Promotes or demotes a user according to the given role(0=user, 1=admin, 2=god)
		public function changeRole($userID, $role) {
			$currentUserID = $this->getSession()["ID"];
			$parameters = Array();
			$parameters[":userID"] = $userID;
			if (PermissionManager::isSuperior($currentUserID, $userID)) {
				switch ($role) {
					case 0:
						if (PermissionManager::isAdmin($userID) && PermissionManager::isDeleteable($userID)) {
							$this->DB->query("DELETE FROM " . ADMIN_TABLE . " WHERE UserID = :userID", $parameters);
							return true;
						} else {
							return false;
						}
					case 1:
						if (!PermissionManager::isAdmin($userID)) {
							$this->DB->query("INSERT INTO " . ADMIN_TABLE . "(UserID, Deleteable) VALUES (:userID, 1)", $parameters);
							return true;
						} else {
							return false;
						}
					case 2:
						if (!PermissionManager::isDeleteable($currentUserID)) {
							if (PermissionManager::isAdmin($userID)) {
								$this->DB->query("UPDATE " . ADMIN_TABLE . " SET Deleteable = 0 WHERE UserID = :userID", $parameters);
							} else {
								$this->DB->query("INSERT INTO " . ADMIN_TABLE . " (UserID, Deleteable) VALUES (:userID, 0)", $parameters);
							}
							return true;
						} else {
							return false;
						}
				}
			} else {
				return false;
			}
		}
		
		public function updateUser($userID, $action, $newValue) {
			$parameters = Array();
			$parameters[":userID"] = $userID;
			//$parameters[":action"] = $action;
			$parameters[":newValue"] = $newValue;
			switch($action) {
				case "Email":
					//TODO: Getting a magical syntax error when I write :action instead of Email. Fixing that would make this method a lot easier and prettier
					$result = $this->DB->query("UPDATE " . USER_TABLE . " SET Email = :newValue WHERE ID = :userID", $parameters);
					break;
				case "GravatarEmail":
					$result = $this->DB->query("UPDATE " . USER_TABLE . " SET GravatarEmail = :newValue WHERE ID = :userID", $parameters);
					break;
				case "Password":
					$result = $this->DB->query("UPDATE " . USER_TABLE . " SET PasswordHash = :newValue WHERE ID = :userID", $parameters);
			}
			if($result) {
				return true;
			} else {
				return false;
			}			
		}
	}
?>
