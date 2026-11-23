<?php
/**
 * AutoPunish extension for phpBB.
 *
 * @copyright (c) 2026, biochaos
 * @license GNU General Public License, version 2 (GPL-2.0)
 */

namespace biochaos\autopunish\migrations;

/**
 * Lets a tier last longer than about 194 days.
 *
 * install_schema made duration_seconds a UINT, a three-byte column whose largest
 * value, 16,777,215 seconds, is a little over 194 days. A tier set to seven
 * months or a year ended the save with an SQL error. It becomes a four-byte
 * unsigned integer, which holds over a hundred years; the Tiers page allows 50.
 */
class widen_duration_seconds extends \phpbb\db\migration\migration
{
	public static function depends_on()
	{
		return ['\biochaos\autopunish\migrations\make_cron_timestamp_dynamic'];
	}

	public function update_schema()
	{
		return [
			'change_columns' => [
				$this->table_prefix . 'autopunish_tiers' => [
					'duration_seconds' => ['UINT:11', 0],
				],
			],
		];
	}

	// There is no revert_schema(): narrowing the column again would fail as soon as
	// a tier holds a longer duration, and "Delete data" reverts every migration, so
	// it would then stop half way. The table is dropped by install_schema's revert.
}
