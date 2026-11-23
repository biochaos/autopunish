<?php
/**
 * AutoPunish extension for phpBB.
 *
 * @copyright (c) 2026, biochaos
 * @license GNU General Public License, version 2 (GPL-2.0)
 */

namespace biochaos\autopunish\migrations;

/**
 * Marks the cron task's last-run time as a dynamic config value.
 *
 * install_config added it as an ordinary value, which phpBB keeps in its config
 * cache, and the cron task writes it without refreshing that cache, as it does
 * for something that changes often. The two together meant that every request
 * after a run read the time from before it, so the task counted as due again
 * on every cron check instead of once per interval. phpBB reads dynamic values
 * from the database each time, and that is what this one needs to be.
 */
class make_cron_timestamp_dynamic extends \phpbb\db\migration\container_aware_migration
{
	public static function depends_on()
	{
		return ['\biochaos\autopunish\migrations\localize_default_tiers'];
	}

	public function update_data()
	{
		return [
			['custom', [[$this, 'flag_dynamic']]],
		];
	}

	public function flag_dynamic()
	{
		$this->db->sql_query('UPDATE ' . CONFIG_TABLE . "
			SET is_dynamic = 1
			WHERE config_name = 'autopunish_cron_last_run'");

		// The cached copy still holds the old value, and would go on supplying it.
		$this->container->get('cache.driver')->destroy('config');
	}
}
