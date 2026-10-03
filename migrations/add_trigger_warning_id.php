<?php
/**
 * AutoPunish extension for phpBB.
 *
 * @copyright (c) 2026, biochaos
 * @license GNU General Public License, version 2 (GPL-2.0)
 */

namespace biochaos\autopunish\migrations;

class add_trigger_warning_id extends \phpbb\db\migration\migration
{
	public function effectively_installed()
	{
		return $this->db_tools->sql_column_exists($this->table_prefix . 'autopunish_punishments', 'trigger_warning_id');
	}

	public static function depends_on()
	{
		return ['\biochaos\autopunish\migrations\install_schema'];
	}

	public function update_schema()
	{
		return [
			'add_columns' => [
				$this->table_prefix . 'autopunish_punishments' => [
					'trigger_warning_id' => ['UINT', 0],
				],
			],
		];
	}

	public function revert_schema()
	{
		return [
			'drop_columns' => [
				$this->table_prefix . 'autopunish_punishments' => ['trigger_warning_id'],
			],
		];
	}

	public function update_data()
	{
		return [
			['custom', [[$this, 'backfill_trigger_warning_ids']]],
		];
	}

	/**
	 * Stamps existing punishments with the newest warning that was already on the
	 * account when they were applied. Without this, every punishment predating the
	 * upgrade would look as though it had been issued for no warning at all, and
	 * the first retroactive scan afterwards would punish those users a second time
	 * for warnings they have already served.
	 */
	public function backfill_trigger_warning_ids()
	{
		$punishments_table = $this->table_prefix . 'autopunish_punishments';
		$warnings_table    = $this->table_prefix . 'warnings';

		// A warning issued in the same second as the punishment is the one that
		// caused it, so the comparison has to be inclusive. A punishment with no
		// warning at or before its start time has nothing to be stamped with and
		// keeps 0, which has_unpunished_warning() reads as "never punished for a
		// warning" -- the same state a pruned account reports.
		$this->db->sql_query('UPDATE ' . $punishments_table . '
			SET trigger_warning_id = COALESCE((
				SELECT MAX(w.warning_id)
				FROM ' . $warnings_table . ' w
				WHERE w.user_id = ' . $punishments_table . '.user_id
					AND w.warning_time <= ' . $punishments_table . '.start_time
			), 0)
			WHERE trigger_warning_id = 0');
	}
}
