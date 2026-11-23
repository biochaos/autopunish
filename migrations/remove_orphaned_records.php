<?php
/**
 * AutoPunish extension for phpBB.
 *
 * @copyright (c) 2026, biochaos
 * @license GNU General Public License, version 2 (GPL-2.0)
 */

namespace biochaos\autopunish\migrations;

/**
 * Removes the records of users who were deleted before AutoPunish started
 * cleaning up after them (the listener on core.delete_user_after).
 */
class remove_orphaned_records extends \phpbb\db\migration\migration
{
	public static function depends_on()
	{
		return ['\biochaos\autopunish\migrations\widen_duration_seconds'];
	}

	public function update_data()
	{
		return [
			['custom', [[$this, 'remove_orphans']]],
		];
	}

	public function remove_orphans()
	{
		foreach (['autopunish_punishments', 'autopunish_commutations'] as $table)
		{
			$this->db->sql_query('DELETE FROM ' . $this->table_prefix . $table . '
				WHERE user_id NOT IN (SELECT user_id FROM ' . USERS_TABLE . ')');
		}
	}
}
