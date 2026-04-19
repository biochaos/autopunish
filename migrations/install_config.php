<?php
/**
 * AutoPunish extension for phpBB.
 *
 * @copyright (c) 2026, biochaos
 * @license GNU General Public License, version 2 (GPL-2.0)
 */

namespace biochaos\autopunish\migrations;

class install_config extends \phpbb\db\migration\migration
{
	public function effectively_installed()
	{
		return isset($this->config['autopunish_enabled']);
	}

	public static function depends_on()
	{
		return ['\biochaos\autopunish\migrations\install_schema'];
	}

	public function update_data()
	{
		return [
			// General
			['config.add', ['autopunish_enabled', 1]],
			['config.add', ['autopunish_warn_during_punishment', 'restart']],
			['config.add', ['autopunish_exempt_groups', '']],   // comma-separated group IDs
			['config.add', ['autopunish_cron_interval', 60]],
			['config.add', ['autopunish_dry_run', 0]],
			['config.add', ['autopunish_retroactive', 0]],

			// Notifications
			['config.add', ['autopunish_notify_forum', 0]],
			['config.add', ['autopunish_notify_pm', 0]],
			['config.add', ['autopunish_notify_email', 0]],
			['config.add', ['autopunish_notify_sender_id', 0]],
			['config.add', ['autopunish_notify_footer', '']],
			['config.add', ['autopunish_notify_expiry_text', '']],
			['config.add', ['autopunish_notify_commute_text', '']],
			['config.add', ['autopunish_cron_last_run', 0]],
		];
	}

	public function revert_data()
	{
		return [
			['config.remove', ['autopunish_enabled']],
			['config.remove', ['autopunish_warn_during_punishment']],
			['config.remove', ['autopunish_exempt_groups']],
			['config.remove', ['autopunish_cron_interval']],
			['config.remove', ['autopunish_dry_run']],
			['config.remove', ['autopunish_retroactive']],
			['config.remove', ['autopunish_notify_forum']],
			['config.remove', ['autopunish_notify_pm']],
			['config.remove', ['autopunish_notify_email']],
			['config.remove', ['autopunish_notify_sender_id']],
			['config.remove', ['autopunish_notify_footer']],
			['config.remove', ['autopunish_notify_expiry_text']],
			['config.remove', ['autopunish_notify_commute_text']],
			['config.remove', ['autopunish_cron_last_run']],
		];
	}
}
