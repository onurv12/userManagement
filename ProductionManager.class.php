<?php
	require_once("../userManagement/config.php");

	class ProductionManager {
		private $DB;

		public function __construct ($DB) {
			$this->DB = $DB;
		}

		public function getAllProjects () {
			return $this->DB->getList("SELECT * FROM " . PROJECT_TABLE);
		}
		
		public function deleteProject ($projectID) {
			// TODO: Warning! Remove related data, like uploaded images and so on?
			$parameters = Array();
			$parameters[":id"] = $projectID;

			return $this->DB->query("DELETE FROM " . PROJECT_TABLE . " WHERE ID = :id", $parameters);
		}

		public function create ($name, $creatorID) {
			$parameters = Array();
			$parameters[":name"] = $name;

			$this->DB->query("INSERT INTO " . PROJECT_TABLE . " (Name) VALUES (:name)");
			$this->addUser2Project($creatorID, $this->DB->getLastInsertID, "Director");
		}

		public function openProject($projectID) {
			$parameters = Array();
			$parameters[":projectID"] = $projectID;
			return $this->DB->query("UPDATE " . PROJECT_TABLE . " SET Approved = 0 WHERE ID = :projectID", $parameters);
		}
		
		public function closeProject($projectID) {
			$parameters = Array();
			$parameters[":projectID"] = $projectID;
			return $this->DB->query("UPDATE " . PROJECT_TABLE . " SET Approved = 1 WHERE ID = :projectID", $parameters);
		}

		public function getProjectUsers ($projectID) {
			// TODO: return users, associated to a specific project
			// I think only the user IDs shall be returned  ( or shall we join?! )
			$parameters = Array();
			$parameters[":projectID"] = $projectID;

			return $this->DB->getList("SELECT UserID, Role FROM " . USERSINPROJECTS_TABLE . " WHERE ProjectID = :projectID");
		}

		public function addUser2Project ($userID, $projectID, $role) {
			$parameters = Array();
			$parameters[":userID"] = $userID;
			$parameters[":projectID"] = $projectID;
			$parameters[":role"] = $role;

			// TODO: CHECL IF USER IS ALREADY IN THIS TEAM
			$this->DB->query("INSERT INTO " . USERSINPROJECTS_TABLE . " (UserID, ProjectID, Role) VALUES (:userID, :projectID, :role)");
		}

		public function changeUserRole ($userID, $projectID, $role) {
			$parameters = Array();
			$parameters[":userID"] = $userID;
			$parameters[":projectID"] = $projectID;
			$parameters[":role"] = $role;

			$this->DB->query("UPDATE " . USERSINPROJECTS_TABLE . " SET Role = :role WHERE UserID = :userID AND ProjectID = :projectID", $parameters);
		}

		public function removeUserFromProject ($userID, $projectID) {
			$parameters = Array();
			$parameters[":userID"] = $userID;
			$parameters[":projectID"] = $projectID;

			$this->DB->query("DELETE FROM " . USERSINPROJECTS_TABLE . " WHERE UserID = :userID AND ProjectID = :projectID");
		}

		// Gets the projects the user is associated with and the roles he/she has // TODO: come up with a better name for this method
		public function getBelongedProjects ($userID) {
			$parameters = Array();
			$parameters[":userID"] = $userID;
			return $this->DB->getList("SELECT Projects.* FROM " . PROJECT_TABLE . " JOIN " . USERSINPROJECTS_TABLE . " ON Projects.ID = UsersInProjects.ProjectID WHERE UserID = :userID", $parameters);
		}
	}
?>