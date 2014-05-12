<?php
	require_once("../userManagement/config.php");

	class teamManager {
		private $DB;

		public function __construct ($DB) {
			$this->DB = $DB;
		}

		public function getAll () {
			$this->DB->getList("SELECT * FROM " . TEAM_TABLE);
		}

		public function remove ($teamID) {
			// Warning! Remove related data, like uploaded images and so on?
			$parameters = Array();
			$parameters[":id"] = $teamID;

			$this->DB->query("DELETE FROM " . TEAM_TABLE . " WHERE id = :id", $parameters);
		}

		public function create ($title, $ownerID) {
			$parameters = Array();
			$parameters[":title"] = $title;
			$parameters[":ownerID"] = $ownerID;

			$this->DB->query("INSERT INTO " . TEAM_TABLE . " (title, ownerID) VALUES (:title, :ownerID)");
		}

		public function getTeamUsers ($teamID) {
			// TODO: return users, associated to a specific team
			// I think only the user IDs shall be returned  ( or shall we join?! )
			$parameters = Array();
			$parameters[":teamID"] = $teamID;

			return $this->DB->getList("SELECT userID, role FROM " . TEAM_USER2TEAM_TABLE . " WHERE teamID = :teamID");
		}

		public function addUser2Team ($userID, $teamID, $role) {
			$parameters = Array();
			$parameters[":userID"] = $userID;
			$parameters[":teamID"] = $teamID;
			$parameters[":role"] = $role;

			// TODO: CHECL IF USER IS ALREADY IN THIS TEAM
			$this->DB->query("INSERT INTO " . TEAM_USER2TEAM_TABLE . " (userID, teamID, role) VALUES (:userID, :teamID, :role)");
		}

		public function changeUserRole ($userID, $teamID, $role) {
			$parameters = Array();
			$parameters[":userID"] = $userID;
			$parameters[":teamID"] = $teamID;
			$parameters[":role"] = $role;

			$this->DB->query("UPDATE " . TEAM_USER2TEAM_TABLE . " SET role = :role WHERE userID = :userID AND teamID = :teamID", $parameters);
		}

		public function removeUserFromTeam ($userID, $teamID) {
			$parameters = Array();
			$parameters[":userID"] = $userID;
			$parameters[":teamID"] = $teamID;

			$this->DB->query("DELETE FROM " . TEAM_USER2TEAM_TABLE . " WHERE userID = :userID AND teamID = :teamID");
		}

		// Gets the teams the user is associated with and the roles he/she has // TODO: come up with a better name for this method
		public function getUsersTeams ($userID) {
			$parameters = Array();
			$parameters[":userID"] = $userID;

			return $this->DB->getList("SELECT * FROM u2t, t WHERE u2t.userID = :userID FROM " . TEAM_USER2TEAM_TABLE . " u2t INNER JOIN " . TEAM_TABLE . " t ON u2t.teamID = t.id", $parameters);
		}
	}
?>