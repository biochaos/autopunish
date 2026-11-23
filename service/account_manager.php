<?php
/**
 * AutoPunish extension for phpBB.
 *
 * @copyright (c) 2026, biochaos
 * @license GNU General Public License, version 2 (GPL-2.0)
 */

namespace biochaos\autopunish\service;

/**
 * What a punishment does to an account: adds the user to a group, removes
 * them again, deactivates or reactivates the account. phpBB's own functions
 * do the work, and are loaded when first needed.
 */
class account_manager
{
	/** @var \phpbb\db\driver\driver_interface */
	private $db;

	/** @var string */
	private $phpbb_root_path;

	/** @var string */
	private $php_ext;

	/** @var array[]|false[] Each group id looked up so far: its row, or false when it does not exist. */
	private $group_cache = [];

	public function __construct(\phpbb\db\driver\driver_interface $db, $phpbb_root_path, $php_ext)
	{
		$this->db              = $db;
		$this->phpbb_root_path = $phpbb_root_path;
		$this->php_ext         = $php_ext;
	}

	/**
	 * @return array|false The group's row, or false when there is no such group.
	 */
	public function get_group_row($group_id)
	{
		$group_id = (int) $group_id;
		if ($group_id <= 0)
		{
			return false;
		}

		if (!isset($this->group_cache[$group_id]))
		{
			$sql    = 'SELECT group_id, group_name FROM ' . GROUPS_TABLE . ' WHERE group_id = ' . $group_id;
			$result = $this->db->sql_query($sql);
			$this->group_cache[$group_id] = $this->db->sql_fetchrow($result) ?: false;
			$this->db->sql_freeresult($result);
		}

		return $this->group_cache[$group_id];
	}

	public function is_user_inactive($user_id)
	{
		$sql       = 'SELECT user_type FROM ' . USERS_TABLE . ' WHERE user_id = ' . (int) $user_id;
		$result    = $this->db->sql_query($sql);
		$user_type = (int) $this->db->sql_fetchfield('user_type');
		$this->db->sql_freeresult($result);
		return $user_type === USER_INACTIVE;
	}

	private function ensure_user_functions_loaded()
	{
		if (!function_exists('group_user_add'))
		{
			include($this->phpbb_root_path . 'includes/functions_user.' . $this->php_ext);
		}
	}

	/**
	 * @return string|false False when the user was added, otherwise the error
	 *                      key from group_user_add(): GROUP_USERS_EXIST when
	 *                      they were already in the group.
	 */
	public function add_to_group($user_id, $group_id)
	{
		$this->ensure_user_functions_loaded();
		return \group_user_add((int) $group_id, [(int) $user_id]);
	}

	public function remove_from_group($user_id, $group_id)
	{
		$this->ensure_user_functions_loaded();
		\group_user_del($group_id, [$user_id]);
	}

	public function deactivate_user($user_id)
	{
		$this->ensure_user_functions_loaded();
		\user_active_flip('deactivate', $user_id, \INACTIVE_MANUAL);
	}

	public function reactivate_user($user_id)
	{
		$this->ensure_user_functions_loaded();
		\user_active_flip('activate', $user_id);
	}
}
