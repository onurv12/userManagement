<?php
	
	abstract class projectPermission {
		private static $permissions = Array("Director", "Supervisor", "Artist", "Observer");

		public static function atLeast ($minRole, isRole) {
			$minRole = array_search($minRole, $this->permissions);
			$isRole = array_search($minRole, $this->permissions);
			
			return $isRole <= $minRole;
		}
	}
?>