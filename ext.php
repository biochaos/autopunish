<?php
/**
 * AutoPunish extension for phpBB.
 *
 * @copyright (c) 2026, biochaos
 * @license GNU General Public License, version 2 (GPL-2.0)
 */

namespace biochaos\autopunish;

class ext extends \phpbb\extension\base
{
	/** The extension's notification types, as registered in config/services.yml. */
	const NOTIFICATION_TYPES = [
		'biochaos.autopunish.notification.punishment_applied',
		'biochaos.autopunish.notification.punishment_expired',
		'biochaos.autopunish.notification.punishment_commuted',
	];

	/**
	 * 3.3.1 is the first release whose core.acp_users_mode_add event passes
	 * u_action, which the Manage Users tab needs for its return links.
	 */
	public function is_enableable()
	{
		$config = $this->container->get('config');
		if (phpbb_version_compare($config['version'], '3.3.1', '>='))
		{
			return true;
		}

		// The extension's language file is not loaded while it is disabled.
		$language = $this->container->get('language');
		$language->add_lang('common', 'biochaos/autopunish');
		return $language->lang('AUTOPUNISH_REQUIRES_PHPBB_331');
	}

	/**
	 * Notification rows of a type whose service no longer exists make core throw
	 * on every page for the member who received them, so the types are switched
	 * off with the extension and their rows removed with its data.
	 */
	public function enable_step($old_state)
	{
		if ($old_state === false)
		{
			$this->for_each_notification_type('enable_notifications');
			return 'notifications';
		}

		return parent::enable_step($old_state);
	}

	public function disable_step($old_state)
	{
		if ($old_state === false)
		{
			$this->for_each_notification_type('disable_notifications');
			return 'notifications';
		}

		return parent::disable_step($old_state);
	}

	public function purge_step($old_state)
	{
		if ($old_state === false)
		{
			$this->for_each_notification_type('purge_notifications');
			return 'notifications';
		}

		return parent::purge_step($old_state);
	}

	private function for_each_notification_type($method)
	{
		/** @var \phpbb\notification\manager $manager */
		$manager = $this->container->get('notification_manager');
		foreach (self::NOTIFICATION_TYPES as $type)
		{
			$manager->$method($type);
		}
	}
}
