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

		$sql = 'SELECT punishment_id, user_id, start_time
				FROM ' . $punishments_table . '
				WHERE trigger_warning_id = 0';
		$result      = $this->db->sql_query($sql);
		$punishments = [];
		$user_ids    = [];
		while ($row = $this->db->sql_fetchrow($result))
		{
			$punishments[]                   = $row;
			$user_ids[(int) $row['user_id']] = true;
		}
		$this->db->sql_freeresult($result);

		if (empty($punishments))
		{
			return;
		}

		// Pull the warnings of every affected user in one query rather than per punishment.
		$warnings = [];
		$sql = 'SELECT user_id, warning_id, warning_time
				FROM ' . $warnings_table . '
				WHERE ' . $this->db->sql_in_set('user_id', array_keys($user_ids));
		$result = $this->db->sql_query($sql);
		while ($row = $this->db->sql_fetchrow($result))
		{
			$warnings[(int) $row['user_id']][] = [
				'warning_id'   => (int) $row['warning_id'],
				'warning_time' => (int) $row['warning_time'],
			];
		}
		$this->db->sql_freeresult($result);

		// A warning issued at the same second as the punishment is the one that caused
		// it, so the comparison has to be inclusive.
		$by_warning_id = [];
		foreach ($punishments as $punishment)
		{
			$user_id    = (int) $punishment['user_id'];
			$start_time = (int) $punishment['start_time'];
			$highest    = 0;

			foreach (isset($warnings[$user_id]) ? $warnings[$user_id] : [] as $warning)
			{
				if ($warning['warning_time'] <= $start_time && $warning['warning_id'] > $highest)
				{
					$highest = $warning['warning_id'];
				}
			}

			if ($highest)
			{
				$by_warning_id[$highest][] = (int) $punishment['punishment_id'];
			}
		}

		// One statement per distinct warning id instead of one per punishment row.
		foreach ($by_warning_id as $warning_id => $punishment_ids)
		{
			$this->db->sql_query('UPDATE ' . $punishments_table . '
				SET trigger_warning_id = ' . (int) $warning_id . '
				WHERE ' . $this->db->sql_in_set('punishment_id', $punishment_ids));
		}
	}
}
