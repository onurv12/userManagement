<?php
	
	abstract class projectPermission {
		private static $permissions = Array("Director", "Supervisor", "Artist", "Observer");

		public static function atLeast ($atLeastRole, $currentRole) {
			$atLeastRole = array_search($atLeastRole, $this->permissions);
			$currentRole = array_search($currentRole, $this->permissions);
			
			return $currentRole <= $atLeastRole;
		}
	}
?>