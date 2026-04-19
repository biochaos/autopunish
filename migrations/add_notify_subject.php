<?php
/**
 * AutoPunish extension for phpBB.
 *
 * @copyright (c) 2026, biochaos
 * @license GNU General Public License, version 2 (GPL-2.0)
 */

namespace biochaos\autopunish\migrations;

class add_notify_subject extends \phpbb\db\migration\migration
{
	public function effectively_installed()
	{
		return isset($this->config['autopunish_notify_subject']);
	}

	public static function depends_on()
	{
		return ['\biochaos\autopunish\migrations\install_config'];
	}

	public function update_data()
	{
		return [
			['config.add', ['autopunish_notify_subject', '']],
		];
	}

	public function revert_data()
	{
		return [
			['config.remove', ['autopunish_notify_subject']],
		];
	}
}
