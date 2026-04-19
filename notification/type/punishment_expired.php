<?php
/**
 * AutoPunish extension for phpBB.
 *
 * @copyright (c) 2026, biochaos
 * @license GNU General Public License, version 2 (GPL-2.0)
 */

namespace biochaos\autopunish\notification\type;

class punishment_expired extends base_punishment
{
	static public $notification_option = [
		'lang'  => 'NOTIFICATION_TYPE_AUTOPUNISH_EXPIRED',
		'group' => 'NOTIFICATION_GROUP_MODERATION',
	];

	public function get_type()
	{
		return 'biochaos.autopunish.notification.punishment_expired';
	}

	protected function get_title_lang_key()
	{
		return 'AUTOPUNISH_NOTIFY_EXPIRED_TITLE';
	}
}
