<?php
/**
 * AutoPunish extension for phpBB.
 *
 * @copyright (c) 2026, biochaos
 * @license GNU General Public License, version 2 (GPL-2.0)
 */

namespace biochaos\autopunish\migrations;

class add_acp_users_mode extends \phpbb\db\migration\migration
{
	public function effectively_installed()
	{
		$sql = 'SELECT module_id FROM ' . $this->table_prefix . 'modules
				WHERE module_class = \'acp\'
				AND module_basename = \'acp_users\'
				AND module_mode = \'autopunish\'';
		$result = $this->db->sql_query($sql);
		$row    = $this->db->sql_fetchrow($result);
		$this->db->sql_freeresult($result);
		return (bool) $row;
	}

	public static function depends_on()
	{
		return ['\biochaos\autopunish\migrations\install_modules'];
	}

	public function update_data()
	{
		return [
			['custom', [[$this, 'add_to_acp_users']]],
		];
	}

	public function revert_data()
	{
		return [
			['custom', [[$this, 'remove_from_acp_users']]],
		];
	}

	public function add_to_acp_users()
	{
		// Try to insert immediately after the 'warnings' mode so the tab
		// appears in the correct position in the Manage Users dropdown.
		$sql = 'SELECT parent_id, right_id FROM ' . $this->table_prefix . 'modules
				WHERE module_class = \'acp\'
				AND module_basename = \'acp_users\'
				AND module_mode = \'warnings\'';
		$result   = $this->db->sql_query($sql);
		$warnings = $this->db->sql_fetchrow($result);
		$this->db->sql_freeresult($result);

		if ($warnings)
		{
			$parent_id   = (int) $warnings['parent_id'];
			$insert_left = (int) $warnings['right_id'] + 1;
		}
		else
		{
			// Fallback: insert as the last child of the acp_users parent category.
			$sql = 'SELECT parent_id FROM ' . $this->table_prefix . 'modules
					WHERE module_class = \'acp\'
					AND module_basename = \'acp_users\'
					AND module_mode = \'overview\'
					ORDER BY left_id DESC';
			$result  = $this->db->sql_query_limit($sql, 1);
			$row     = $this->db->sql_fetchrow($result);
			$this->db->sql_freeresult($result);

			if (!$row)
			{
				return; // acp_users not found — nothing to do
			}

			$parent_id = (int) $row['parent_id'];

			$sql    = 'SELECT right_id FROM ' . $this->table_prefix . 'modules WHERE module_id = ' . $parent_id;
			$result = $this->db->sql_query($sql);
			$parent = $this->db->sql_fetchrow($result);
			$this->db->sql_freeresult($result);

			$insert_left = (int) $parent['right_id'];
		}

		$this->db->sql_transaction('begin');

		// Make room in the nested-set tree for one new leaf node (2 slots)
		$this->db->sql_query('UPDATE ' . $this->table_prefix . 'modules
			SET left_id = left_id + 2
			WHERE module_class = \'acp\' AND left_id >= ' . $insert_left);

		$this->db->sql_query('UPDATE ' . $this->table_prefix . 'modules
			SET right_id = right_id + 2
			WHERE module_class = \'acp\' AND right_id >= ' . $insert_left);

		// Insert the new mode — display=0 keeps it off the sidebar
		$sql_ary = [
			'module_enabled'  => 1,
			'module_display'  => 0,
			'module_basename' => 'acp_users',
			'module_class'    => 'acp',
			'parent_id'       => $parent_id,
			'left_id'         => $insert_left,
			'right_id'        => $insert_left + 1,
			'module_langname' => 'ACP_USER_AUTOPUNISH',
			'module_mode'     => 'autopunish',
			'module_auth'     => 'ext_biochaos/autopunish && acl_a_board',
		];

		$this->db->sql_query('INSERT INTO ' . $this->table_prefix . 'modules ' . $this->db->sql_build_array('INSERT', $sql_ary));

		$this->db->sql_transaction('commit');
	}

	public function remove_from_acp_users()
	{
		$sql = 'SELECT module_id, left_id, right_id FROM ' . $this->table_prefix . 'modules
				WHERE module_class = \'acp\'
				AND module_basename = \'acp_users\'
				AND module_mode = \'autopunish\'';
		$result = $this->db->sql_query($sql);
		$module = $this->db->sql_fetchrow($result);
		$this->db->sql_freeresult($result);

		if (!$module)
		{
			return;
		}

		$left_id  = (int) $module['left_id'];
		$right_id = (int) $module['right_id'];
		$diff     = $right_id - $left_id + 1;

		$this->db->sql_query('DELETE FROM ' . $this->table_prefix . 'modules
			WHERE module_id = ' . (int) $module['module_id']);

		$this->db->sql_query('UPDATE ' . $this->table_prefix . 'modules
			SET left_id = left_id - ' . $diff . '
			WHERE module_class = \'acp\' AND left_id > ' . $right_id);

		$this->db->sql_query('UPDATE ' . $this->table_prefix . 'modules
			SET right_id = right_id - ' . $diff . '
			WHERE module_class = \'acp\' AND right_id > ' . $right_id);
	}
}
