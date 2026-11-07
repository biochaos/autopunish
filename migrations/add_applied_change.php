<?php
/**
 * AutoPunish extension for phpBB.
 *
 * @copyright (c) 2026, biochaos
 * @license GNU General Public License, version 2 (GPL-2.0)
 */

namespace biochaos\autopunish\migrations;

/**
 * Records whether a punishment itself made its change to the account: added
 * the user to the group, or deactivated the account. Ending the punishment
 * undoes only a change it made, so a user who was already in the group keeps
 * that membership, and an account an administrator had already deactivated
 * stays deactivated.
 *
 * Punishments recorded before this column existed are taken to have made
 * their change, which is how they were treated until now.
 */
class add_applied_change extends \phpbb\db\migration\migration
{
	public function effectively_installed()
	{
		return $this->db_tools->sql_column_exists($this->table_prefix . 'autopunish_punishments', 'applied_change');
	}

	public static function depends_on()
	{
		return ['\biochaos\autopunish\migrations\make_deactivation_permanent'];
	}

	public function update_schema()
	{
		return [
			'add_columns' => [
				$this->table_prefix . 'autopunish_punishments' => [
					'applied_change' => ['BOOL', 1],
				],
			],
		];
	}

	public function revert_schema()
	{
		return [
			'drop_columns' => [
				$this->table_prefix . 'autopunish_punishments' => ['applied_change'],
			],
		];
	}
}
