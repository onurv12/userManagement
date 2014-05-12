<?php
	
	abstract class systemPermission {
		private static $permissions;

		public static function check ($permissionKey, $role) {
			if(self::$permissions[$permissionKey]) {
				return self::$permissions[$permissionKey] <= $role;
			} else {
				return false;
			}
		}

		public static function setPermissions () {
			$this->permissions = Array();

			$this->permissions["createStoryboard"] = 3;
			$this->permissions["addUser2Team"] = 2;

			// TODO: Assemble this list with a config file. PHP has a predefined format and methods
		}
	}

	permission::setPermissions();
?>