<?php
/**
 * AutoPunish extension for phpBB.
 *
 * @copyright (c) 2026, biochaos
 * @license GNU General Public License, version 2 (GPL-2.0)
 */

namespace biochaos\autopunish\migrations;

class install_default_tiers extends \phpbb\db\migration\migration
{
	public function effectively_installed()
	{
		return isset($this->config['autopunish_default_tiers_installed']);
	}

	public static function depends_on()
	{
		return ['\biochaos\autopunish\migrations\install_schema'];
	}

	public function update_data()
	{
		return [
			['custom', [[$this, 'insert_default_tiers']]],
		];
	}

	public function insert_default_tiers()
	{
		$tiers = [
			[
				'tier_order'        => 1,
				'warning_threshold' => 3,
				'action'            => 'group',
				'group_id'          => 0,
				'duration_seconds'  => 7 * 86400,   // 7 days
				'reason_text'       => '1st offense — 7 day restriction',
				'notification_text' => 'You have received your first punishment ({DURATION}). Reason: {REASON}.',
			],
			[
				'tier_order'        => 2,
				'warning_threshold' => 3,
				'action'            => 'group',
				'group_id'          => 0,
				'duration_seconds'  => 30 * 86400,  // 30 days
				'reason_text'       => '2nd offense — 30 day restriction',
				'notification_text' => 'You have received your second punishment ({DURATION}). Reason: {REASON}.',
			],
			[
				'tier_order'        => 3,
				'warning_threshold' => 3,
				'action'            => 'group',
				'group_id'          => 0,
				'duration_seconds'  => 180 * 86400, // 180 days
				'reason_text'       => '3rd offense — 180 day restriction',
				'notification_text' => 'You have received your third punishment ({DURATION}). Reason: {REASON}.',
			],
			[
				'tier_order'        => 4,
				'warning_threshold' => 1,
				'action'            => 'deactivate',
				'group_id'          => 0,
				'duration_seconds'  => 0,            // permanent
				'reason_text'       => '4th offense — account deactivation',
				'notification_text' => 'Your account has been deactivated due to repeated violations.',
			],
		];

		foreach ($tiers as $tier)
		{
			$this->db->sql_query('INSERT INTO ' . $this->table_prefix . 'autopunish_tiers ' . $this->db->sql_build_array('INSERT', $tier));
		}

		$this->config->set('autopunish_default_tiers_installed', 1);
	}

	public function revert_data()
	{
		return [
			['custom', [[$this, 'remove_default_tiers']]],
		];
	}

	public function remove_default_tiers()
	{
		$this->db->sql_query('DELETE FROM ' . $this->table_prefix . 'autopunish_tiers');
		$this->config->delete('autopunish_default_tiers_installed');
	}
}
