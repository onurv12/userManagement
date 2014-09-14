<?php
	require_once("../userManagement/config.php");

	class CanvasManager {
		private $DB;

		public function __construct ($DB) {
			$this->DB = $DB;
		}

		function getCanvas ($ProjectID, $CanvasID) {
			$parameters = array();
			$parameters[":ProjectID"] = $ProjectID;
			$parameters[":CanvasID"] = $CanvasID;

			return $this->DB->getRow("SELECT * FROM Canvas WHERE ProjectID = :ProjectID AND ID = :CanvasID", $parameters);
		}

		function removeCanvas ($ProjectID, $CanvasID) {
			$parameters = array();
			$parameters[":ProjectID"] = $ProjectID;
			$parameters[":CanvasID"] = $CanvasID;

			return $this->DB->query("DELETE FROM Canvas WHERE ProjectID = :ProjectID AND ID = :CanvasID", $parameters);
		}

		function removeAll ($ProjectID) {
			$parameters = array();
			$parameters[":CanvasID"] = $CanvasID;

			return $this->DB->query("DELETE FROM Canvas WHERE ProjectID = :ProjectID", $parameters);
		}

		function getPanels ($ProjectID) {
			$parameters = array();
			$parameters[":ProjectID"] = $ProjectID;

			return $this->DB->getList("SELECT * FROM Canvas WHERE ProjectID = :ProjectID ORDER BY PositionIndex ASC", $parameters);
		}

		function getAssets ($CanvasID) {
			$parameters = array();
			$parameters[":CanvasID"] = $CanvasID;

			return $this->DB->getList("SELECT * FROM Asset2Canvas JOIN Asset ON Asset2Canvas.AssetID = Asset.ID WHERE Asset2Canvas.CanvasID = :CanvasID ORDER BY Asset2Canvas.Index DESC", $parameters);
		}
	}
?>
