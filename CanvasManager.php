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

		function getPanels ($ProjectID) {
			$parameters = array();
			$parameters[":ProjectID"] = $ProjectID;

			return $this->DB->getList("SELECT * FROM Canvas WHERE ProjectID = :ProjectID ORDER BY PositionIndex ASC", $parameters);
		}

		function getAssets ($CanvasID) {
			$parameters = array();
			$parameters[":CanvasID"] = $CanvasID;

			$columnList = "Asset2Canvas.ID, Asset.Name, Asset.Filename, Asset2Canvas.AssetID, Asset2Canvas.Index, Asset2Canvas.top, Asset2Canvas.left, Asset2Canvas.scaleX, Asset2Canvas.scaleY, Asset2Canvas.flipX, Asset2Canvas.flipY, Asset2Canvas.angle";

			return $this->DB->getList("SELECT " . $columnList . " FROM Asset2Canvas, Asset WHERE Asset2Canvas.CanvasID = :CanvasID AND Asset2Canvas.AssetID = Asset.ID ORDER BY Asset2Canvas.Index DESC", $parameters);
		}
	}
?>