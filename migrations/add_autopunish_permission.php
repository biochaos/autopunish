<?php
/**
 * AutoPunish extension for phpBB.
 *
 * @copyright (c) 2026, biochaos
 * @license GNU General Public License, version 2 (GPL-2.0)
 */

namespace biochaos\autopunish\migrations;

/**
 * Gates the AutoPunish mode of Manage Users behind its own admin permission,
 * a_autopunish, instead of a_board, so it can be granted to groups such as
 * global moderators without also handing them the board settings.
 */
class add_autopunish_permission extends \phpbb\db\migration\container_aware_migration
{
	/** Stock admin roles that receive the permission on install. */
	const ADMIN_ROLES = ['ROLE_ADMIN_FULL', 'ROLE_ADMIN_STANDARD', 'ROLE_ADMIN_FORUM', 'ROLE_ADMIN_USERGROUP'];

	public static function depends_on()
	{
		return ['\biochaos\autopunish\migrations\add_acp_users_mode'];
	}

	public function update_data()
	{
		$data = [
			['permission.add', ['a_autopunish']],
		];

		// A board may have deleted or renamed any of the stock roles.
		foreach (self::ADMIN_ROLES as $role)
		{
			$data[] = ['if', [
				['permission.role_exists', [$role]],
				['permission.permission_set', [$role, 'a_autopunish']],
			]];
		}

		$data[] = ['custom', [[$this, 'set_module_auth'], ['ext_biochaos/autopunish && acl_a_autopunish']]];

		return $data;
	}

	public function revert_data()
	{
		// The reversed update_data already removes a_autopunish itself.
		return [
			['custom', [[$this, 'set_module_auth'], ['ext_biochaos/autopunish && acl_a_board']]],
		];
	}

	public function set_module_auth($module_auth)
	{
		$this->db->sql_query('UPDATE ' . $this->table_prefix . "modules
			SET module_auth = '" . $this->db->sql_escape($module_auth) . "'
			WHERE module_class = 'acp'
				AND module_basename = 'acp_users'
				AND module_mode = 'autopunish'");

		$this->container->get('module.manager')->remove_cache_file('acp');
	}
}
