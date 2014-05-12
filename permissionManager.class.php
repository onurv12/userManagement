<?php

require_once "../userManager/teamPermission.php";

/**
 *	This class handles permissions of users in groups
 */
abstract class permissionManager
{
	private $DB;
	private $userID;

	/**
	* Check if the provided role is allowed to perform the action associated with the permission key
	*/
	static function check ($context, $permissionKey, $role) {
		if ($context == "team")
			return teamPermission::check($permissionKey, $role);
		else if ($context == "system")
			return systemPermission::check($permissionKey, $role);
	}
}

?>