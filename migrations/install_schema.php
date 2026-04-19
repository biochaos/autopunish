<?php
/**
 * AutoPunish extension for phpBB.
 *
 * @copyright (c) 2026, biochaos
 * @license GNU General Public License, version 2 (GPL-2.0)
 */

namespace biochaos\autopunish\migrations;

class install_schema extends \phpbb\db\migration\migration
{
	public function effectively_installed()
	{
		return $this->db_tools->sql_table_exists($this->table_prefix . 'autopunish_tiers');
	}

	public static function depends_on()
	{
		return ['\phpbb\db\migration\data\v330\v330'];
	}

	public function update_schema()
	{
		return [
			'add_tables' => [
				$this->table_prefix . 'autopunish_tiers' => [
					'COLUMNS' => [
						'tier_id'            => ['UINT', null, 'auto_increment'],
						'tier_order'         => ['UINT', 1],
						'warning_threshold'  => ['UINT', 3],
						'action'             => ['VCHAR:20', 'group'],
						'group_id'           => ['UINT', 0],
						'duration_seconds'   => ['UINT', 0],
						'reason_text'        => ['VCHAR:255', ''],
						'notification_text'  => ['TEXT_UNI', ''],
					],
					'PRIMARY_KEY' => 'tier_id',
					'KEYS' => [
						'tier_order' => ['INDEX', 'tier_order'],
					],
				],
				$this->table_prefix . 'autopunish_punishments' => [
					'COLUMNS' => [
						'punishment_id'  => ['UINT', null, 'auto_increment'],
						'user_id'        => ['UINT', 0],
						'offense_number' => ['UINT', 1],
						'action'         => ['VCHAR:20', 'group'],
						'group_id'       => ['UINT', 0],
						'reason_text'    => ['VCHAR:255', ''],
						'start_time'     => ['TIMESTAMP', 0],
						'end_time'       => ['TIMESTAMP', 0],
						'active'         => ['BOOL', 1],
					],
					'PRIMARY_KEY' => 'punishment_id',
					'KEYS' => [
						'user_id'         => ['INDEX', 'user_id'],
						'active_end_time' => ['INDEX', ['active', 'end_time']],
					],
				],
				$this->table_prefix . 'autopunish_commutations' => [
					'COLUMNS' => [
						'user_id'           => ['UINT', 0],
						'commuted_offenses' => ['UINT', 0],
					],
					'PRIMARY_KEY' => 'user_id',
				],
			],
		];
	}

	public function revert_schema()
	{
		return [
			'drop_tables' => [
				$this->table_prefix . 'autopunish_tiers',
				$this->table_prefix . 'autopunish_punishments',
				$this->table_prefix . 'autopunish_commutations',
			],
		];
	}
}
