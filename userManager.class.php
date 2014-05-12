<?php
	session_start();
	
	require_once "../userManagement/config.php";	// TODO: This might be a problem later, we have to solve. I think we should specify, that the user has to migrate some settings into his config file in his root folder.

	class userManager {
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
			$userdata = $this->DB->getRow("SELECT * FROM " . USER_TABLE . " WHERE name = :username AND password = :password", $parameters);

			// If userdata was found, apply it to the session
			if ($userdata) {
				$userdata["token"] = $this->updateToken($userdata["id"]);
				$_SESSION["userdata"] = $userdata;

				return true;
			} else {
				return false;
			}
		}

		public function logout () {
			// remove the session
			// remove token from DB

			if ($_SESSION["userdata"]) {
				// set a new token (which invalidates the old one)
				$this->updateToken($_SESSION["userdata"]["id"]);
				// and remove the userdata from the session
				unset($_SESSION["userdata"]);
			}
		}

		public function createUser ($username, $password, $email, $fulllname) {
			// check if the username or email adress is already registered
			$parameters = Array();
			$parameters[":username"] = $username;
			$parameters[":password"] = $this->hash($password);
			$parameters[":email"] = $this->$email;

			$this->DB->query("INSERT INTO " . USER_TABLE . " (name, password, email, suspended) VALUES (:username, :password, :email, false)");
		}

		public function changePassword ($userID, $newPassword) {
			$parameters = Array();
			$parameters[":userID"] = $userID;
			$parameters[":password"] = $this->hash($newPassword);

			$this->DB->query("UPDATE " . USER_TABLE . " SET password = :password WHERE id = :userID");
		}

		public function changeUsername ($userID, $newUsername) {
			// TODO: Evaluate if the name is already taken!
			$parameters = Array();
			$parameters[":userID"] = $userID;
			$parameters[":username"] = $newUsername; // TODO: html entities?!

			$this->DB->query("UPDATE " . USER_TABLE . " SET name = :username WHERE id = :userID");
		}

		public function getTeams () {
			if ($this->checkLoginState()) {
				$parameters = Array();
				$parameters[":userID"] = $userdata["id"];

				$this->DB->getList("SELECT * FROM " . TEAM_TABLE . ""); // TODO: Implement SQL, for example get the teammembers and their role
			}
		}

		public function getLoginState() {
			return $this->checkLoginState();
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
			// TODO: set token expire time
			$parameters = Array();
			$parameters[":userID"] = $userID;
			$parameters[":token"] = $this->generateToken();
			$parameters[":expiration"] = $this->computeExpiration();

			$this->DB->query("UPDATE " . USER_TABLE . " SET token = :token, expiration = :expiration WHERE id = :userID", $parameters);

			return $parameters[":token"];
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

		private function getUserData ($userID) {
			$parameters = Array();
			$parameters[":userID"];

			return $this->dbWrapper("SELECT * FROM " . USER_TABLE . " WHERE id = :userID");
		}

		private function checkLoginState () {
			// TODO
			// compare session token and db token
			// see if token is expired (ONLY BY DB, NOT THE SESSION)
			if (isset($_SESSION["userdata"]) && isset($_SESSION["userdata"]["token"])) {
				// TODO: is the token still valid?
				$parameters = Array();
				$parameters[":userID"] = $_SESSION["userdata"]["id"];

				$result = $this->DB->getRow("SELECT token, expiration FROM " . USER_TABLE . " WHERE id = :userID", $parameters);

				if (is_array($result) && $result["token"] == $_SESSION["userdata"]["token"] && $this->checkExpiration($result["expiration"])) {
					return true;
				} 
			}

			return false;
		}
	}
?>