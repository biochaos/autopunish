<?php
/**
 * AutoPunish extension for phpBB.
 *
 * @copyright (c) 2026, biochaos
 * @license GNU General Public License, version 2 (GPL-2.0)
 */

namespace biochaos\autopunish\migrations;

class install_default_tiers extends \phpbb\db\migration\container_aware_migration
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
		// The reason and notification texts are user-editable in the ACP once
		// installed, but they should arrive in the administrator's language.
		// phpBB's language loader falls back to the board default and then to
		// English when a translation is missing.
		$language = $this->container->get('language');
		$language->add_lang('default_tiers', 'biochaos/autopunish');

		$tiers = [
			[
				'tier_order'        => 1,
				'warning_threshold' => 3,
				'action'            => 'group',
				'group_id'          => 0,
				'duration_seconds'  => 7 * 86400,   // 7 days
				'reason_text'       => $language->lang('AUTOPUNISH_DEFAULT_TIER_1_REASON'),
				'notification_text' => $language->lang('AUTOPUNISH_DEFAULT_TIER_1_NOTIFY'),
			],
			[
				'tier_order'        => 2,
				'warning_threshold' => 3,
				'action'            => 'group',
				'group_id'          => 0,
				'duration_seconds'  => 30 * 86400,  // 30 days
				'reason_text'       => $language->lang('AUTOPUNISH_DEFAULT_TIER_2_REASON'),
				'notification_text' => $language->lang('AUTOPUNISH_DEFAULT_TIER_2_NOTIFY'),
			],
			[
				'tier_order'        => 3,
				'warning_threshold' => 3,
				'action'            => 'group',
				'group_id'          => 0,
				'duration_seconds'  => 180 * 86400, // 180 days
				'reason_text'       => $language->lang('AUTOPUNISH_DEFAULT_TIER_3_REASON'),
				'notification_text' => $language->lang('AUTOPUNISH_DEFAULT_TIER_3_NOTIFY'),
			],
			[
				'tier_order'        => 4,
				'warning_threshold' => 1,
				'action'            => 'deactivate',
				'group_id'          => 0,
				'duration_seconds'  => 0,           // permanent
				'reason_text'       => $language->lang('AUTOPUNISH_DEFAULT_TIER_4_REASON'),
				'notification_text' => $language->lang('AUTOPUNISH_DEFAULT_TIER_4_NOTIFY'),
			],
		];

		$this->db->sql_multi_insert($this->table_prefix . 'autopunish_tiers', $tiers);

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
