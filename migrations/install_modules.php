<?php
/**
 * AutoPunish extension for phpBB.
 *
 * @copyright (c) 2026, biochaos
 * @license GNU General Public License, version 2 (GPL-2.0)
 */

namespace biochaos\autopunish\migrations;

class install_modules extends \phpbb\db\migration\migration
{
	public function effectively_installed()
	{
		$sql    = 'SELECT module_id FROM ' . $this->table_prefix . 'modules
					WHERE module_class = \'acp\'
					AND module_langname = \'ACP_AUTOPUNISH\'';
		$result = $this->db->sql_query($sql);
		$row    = $this->db->sql_fetchrow($result);
		$this->db->sql_freeresult($result);
		return (bool) $row;
	}

	public static function depends_on()
	{
		return ['\biochaos\autopunish\migrations\install_config'];
	}

	public function update_data()
	{
		return [
			['module.add', ['acp', 'ACP_CAT_DOT_MODS', 'ACP_AUTOPUNISH']],
			['module.add', ['acp', 'ACP_AUTOPUNISH', [
				'module_basename' => '\biochaos\autopunish\acp\main_module',
				'modes'           => ['settings', 'tiers'],
			]]],
		];
	}

	public function revert_data()
	{
		return [
			['module.remove', ['acp', 'ACP_CAT_DOT_MODS', 'ACP_AUTOPUNISH']],
		];
	}
}
