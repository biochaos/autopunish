<?php
/**
 * AutoPunish extension for phpBB.
 *
 * @copyright (c) 2026, biochaos
 * @license GNU General Public License, version 2 (GPL-2.0)
 */

namespace biochaos\autopunish\acp;

use biochaos\autopunish\service\tier_manager;

class main_module
{
	/**
	 * The settings that hold free text, each with the language key of its label.
	 * They live in the config table, whose values hold 255 characters.
	 */
	const SETTING_TEXTS = [
		'autopunish_notify_subject'         => 'AUTOPUNISH_NOTIFY_SUBJECT',
		'autopunish_notify_footer'          => 'AUTOPUNISH_NOTIFY_FOOTER',
		'autopunish_notify_expiry_subject'  => 'AUTOPUNISH_NOTIFY_EXPIRY_SUBJECT',
		'autopunish_notify_expiry_text'     => 'AUTOPUNISH_NOTIFY_EXPIRY_TEXT',
		'autopunish_notify_commute_subject' => 'AUTOPUNISH_NOTIFY_COMMUTE_SUBJECT',
		'autopunish_notify_commute_text'    => 'AUTOPUNISH_NOTIFY_COMMUTE_TEXT',
	];

	public $u_action;
	public $tpl_name;
	public $page_title;

	public function main($id, $mode)
	{
		global $phpbb_container, $request, $template, $user, $config, $phpbb_root_path, $phpEx;

		$phpbb_container->get('language')->add_lang('common', 'biochaos/autopunish');

		$db = $phpbb_container->get('dbal.conn');

		/** @var \biochaos\autopunish\service\tier_manager $tier_manager */
		$tier_manager = $phpbb_container->get('biochaos.autopunish.tier_manager');

		switch ($mode)
		{
			case 'settings':
				$this->page_title = 'ACP_AUTOPUNISH_SETTINGS';
				$this->tpl_name   = 'acp_autopunish_settings';
				$this->handle_settings($request, $template, $config, $user, $db, $phpbb_container);
			break;

			case 'tiers':
				$this->page_title = 'ACP_AUTOPUNISH_TIERS';
				$this->tpl_name   = 'acp_autopunish_tiers';
				$this->handle_tiers($request, $template, $config, $user, $tier_manager, $db);
			break;

		}
	}

	// -------------------------------------------------------------------------
	// Settings page
	// -------------------------------------------------------------------------

	private function handle_settings($request, $template, $config, $user, $db, $phpbb_container)
	{
		global $phpbb_root_path, $phpEx;

		add_form_key('acp_autopunish_settings');

		if ($request->is_set_post('submit'))
		{
			if (!check_form_key('acp_autopunish_settings'))
			{
				trigger_error($user->lang('FORM_INVALID') . adm_back_link($this->u_action), E_USER_WARNING);
			}

			// Resolve and validate the sender before anything is written, so a
			// rejected form leaves every setting as it was.
			$sender_username = $request->variable('autopunish_notify_sender_username', '', true);
			$notify_pm    = $request->variable('autopunish_notify_pm', 0);
			$notify_email = $request->variable('autopunish_notify_email', 0);
			$sender_id = 0;
			if ($sender_username !== '')
			{
				$sql = 'SELECT user_id FROM ' . USERS_TABLE . ' WHERE username_clean = \'' . $db->sql_escape(utf8_clean_string($sender_username)) . '\'';
				$result = $db->sql_query($sql);
				$sender_id = (int) $db->sql_fetchfield('user_id');
				$db->sql_freeresult($result);
				if (!$sender_id)
				{
					trigger_error($user->lang('NO_USER') . adm_back_link($this->u_action), E_USER_WARNING);
				}
			}
			else if ($notify_pm || $notify_email)
			{
				trigger_error($user->lang('AUTOPUNISH_SENDER_REQUIRED') . adm_back_link($this->u_action), E_USER_WARNING);
			}

			// The texts live in the config table, whose values hold 255 characters,
			// counted after escaping and encoding. One that is too long would end
			// the save with an SQL error half way through, so they are all checked
			// first.
			$texts  = [];
			$errors = [];
			foreach (self::SETTING_TEXTS as $key => $label)
			{
				$texts[$key] = tier_manager::encode_text($request->variable($key, '', true));
				$length      = utf8_strlen($texts[$key]);
				if ($length > tier_manager::MAX_TEXT_LENGTH)
				{
					$errors[] = $user->lang('AUTOPUNISH_TEXT_TOO_LONG', $user->lang($label), tier_manager::MAX_TEXT_LENGTH, $length);
				}
			}
			if ($errors)
			{
				trigger_error(implode('<br />', $errors) . adm_back_link($this->u_action), E_USER_WARNING);
			}

			// An unknown mode keeps the one already set.
			$warn_mode = $request->variable('autopunish_warn_during_punishment', '');
			if (!in_array($warn_mode, ['restart', 'normal', 'escalate'], true))
			{
				$warn_mode = $config['autopunish_warn_during_punishment'];
			}

			// All or nothing, so that a failure part way leaves the settings as they were.
			$db->sql_transaction('begin');

			$config->set('autopunish_enabled',                $request->variable('autopunish_enabled', 0));
			$config->set('autopunish_warn_during_punishment', $warn_mode);
			$config->set('autopunish_cron_interval',          max(1, $request->variable('autopunish_cron_interval', 60)));
			$config->set('autopunish_dry_run',                $request->variable('autopunish_dry_run', 0));
			$config->set('autopunish_notify_forum',           $request->variable('autopunish_notify_forum', 0));
			$config->set('autopunish_notify_pm',              $notify_pm);
			$config->set('autopunish_notify_email',           $notify_email);
			$config->set('autopunish_notify_sender_id',       $sender_id);

			foreach ($texts as $key => $value)
			{
				$config->set($key, $value);
			}

			// Exempt groups — stored as comma-separated group IDs
			$exempt = $request->variable('autopunish_exempt_groups', [0]);
			$config->set('autopunish_exempt_groups', implode(',', array_map('intval', $exempt)));

			$db->sql_transaction('commit');

			// Retroactive toggle — run scanner then auto-disable
			if ($request->variable('autopunish_retroactive', 0))
			{
				/** @var \biochaos\autopunish\service\retroactive_scanner $scanner */
				$scanner = $phpbb_container->get('biochaos.autopunish.retroactive_scanner');
				$scanner->run();
				$config->set('autopunish_retroactive', 0);
			}

			trigger_error($user->lang('ACP_AUTOPUNISH_SETTINGS_SAVED') . adm_back_link($this->u_action));
		}

		// Look up the stored sender's username for display
		$sender_id = (int) $config['autopunish_notify_sender_id'];
		$sender_username = '';
		$sender_missing  = false;
		if ($sender_id)
		{
			$sql = 'SELECT username FROM ' . USERS_TABLE . ' WHERE user_id = ' . $sender_id;
			$result = $db->sql_query($sql);
			$sender_username = (string) $db->sql_fetchfield('username');
			$db->sql_freeresult($result);
			if (!$sender_username)
			{
				$sender_missing = true;
			}
		}

		// Build groups list for exempt groups selector
		$groups = $this->get_all_groups($db);
		$exempt_ids = array_filter(explode(',', $config['autopunish_exempt_groups']));

		foreach ($groups as $group)
		{
			$template->assign_block_vars('groups', [
				'ID'       => $group['group_id'],
				'NAME'     => $group['display_name'],
				'SELECTED' => in_array($group['group_id'], $exempt_ids),
			]);
		}

		$template->assign_vars([
			'U_ACTION'                          => $this->u_action,
			'U_LIVE_SEARCH'                     => $config['allow_live_searches'] ? append_sid("{$phpbb_root_path}memberlist.{$phpEx}", 'mode=livesearch') : false,
			'S_AUTOPUNISH_ENABLED'              => $config['autopunish_enabled'],
			'AUTOPUNISH_WARN_DURING_PUNISHMENT' => $config['autopunish_warn_during_punishment'],
			'AUTOPUNISH_CRON_INTERVAL'          => $config['autopunish_cron_interval'],
			'S_AUTOPUNISH_DRY_RUN'              => $config['autopunish_dry_run'],
			'S_AUTOPUNISH_NOTIFY_FORUM'         => $config['autopunish_notify_forum'],
			'S_AUTOPUNISH_NOTIFY_PM'            => $config['autopunish_notify_pm'],
			'S_AUTOPUNISH_NOTIFY_EMAIL'         => $config['autopunish_notify_email'],
			'AUTOPUNISH_NOTIFY_SENDER_USERNAME' => $sender_username,
			'S_AUTOPUNISH_SENDER_MISSING'       => $sender_missing,
			'AUTOPUNISH_NOTIFY_SUBJECT'         => $config['autopunish_notify_subject'],
			'AUTOPUNISH_NOTIFY_FOOTER'          => $config['autopunish_notify_footer'],
			'AUTOPUNISH_NOTIFY_EXPIRY_SUBJECT'  => $config['autopunish_notify_expiry_subject'],
			'AUTOPUNISH_NOTIFY_EXPIRY_TEXT'     => $config['autopunish_notify_expiry_text'],
			'AUTOPUNISH_NOTIFY_COMMUTE_SUBJECT' => $config['autopunish_notify_commute_subject'],
			'AUTOPUNISH_NOTIFY_COMMUTE_TEXT'    => $config['autopunish_notify_commute_text'],
		]);
	}

	// -------------------------------------------------------------------------
	// Tiers page
	// -------------------------------------------------------------------------

	private function handle_tiers($request, $template, $config, $user, $tier_manager, $db)
	{
		add_form_key('acp_autopunish_tiers');

		$groups    = $this->get_all_groups($db);
		$group_ids = array_map('intval', array_column($groups, 'group_id'));
		$errors    = [];
		$tiers     = null;

		if ($request->is_set_post('submit'))
		{
			if (!check_form_key('acp_autopunish_tiers'))
			{
				trigger_error($user->lang('FORM_INVALID') . adm_back_link($this->u_action), E_USER_WARNING);
			}

			$raw_tiers = $request->variable('tiers', [['' => '']], true);
			$tiers     = [];

			foreach ($raw_tiers as $tier)
			{
				if (empty($tier['warning_threshold']) && empty($tier['reason_text']))
				{
					continue;
				}
				// Capped so that the length in seconds stays an integer; anything
				// near the cap is refused below as too long anyway.
				$duration_value = min(1000000, max(0, (int) ($tier['duration_value'] ?? 0)));
				$tiers[] = [
					'warning_threshold' => max(1, (int) ($tier['warning_threshold'] ?? 3)),
					'action'            => ($tier['action'] ?? 'group') === 'deactivate' ? 'deactivate' : 'group',
					'group_id'          => (int) ($tier['group_id'] ?? 0),
					'duration_seconds'  => $this->parse_duration($duration_value, $tier['duration_unit'] ?? 'days'),
					'reason_text'       => (string) ($tier['reason_text'] ?? ''),
					'notification_text' => (string) ($tier['notification_text'] ?? ''),
				];
			}

			// A group tier must name a group that exists, and the lengths must fit
			// their columns. On error the submitted rows are shown again, so
			// nothing the administrator typed is lost.
			foreach ($tiers as $i => $tier)
			{
				if ($tier['action'] === 'group' && !in_array($tier['group_id'], $group_ids, true))
				{
					$errors[] = $user->lang('AUTOPUNISH_TIER_GROUP_REQUIRED', $i + 1);
				}

				if ($tier['duration_seconds'] > tier_manager::MAX_DURATION)
				{
					$errors[] = $user->lang('AUTOPUNISH_DURATION_TOO_LONG', $i + 1);
					$tiers[$i]['duration_seconds'] = tier_manager::MAX_DURATION;
				}

				$length = tier_manager::stored_length($tier['reason_text']);
				if ($length > tier_manager::MAX_TEXT_LENGTH)
				{
					$errors[] = $user->lang('AUTOPUNISH_TEXT_TOO_LONG', $user->lang('AUTOPUNISH_REASON_TEXT') . ' (#' . ($i + 1) . ')', tier_manager::MAX_TEXT_LENGTH, $length);
				}
			}

			if (empty($errors))
			{
				$tier_manager->save_tiers($tiers);
				trigger_error($user->lang('ACP_AUTOPUNISH_TIERS_SAVED') . adm_back_link($this->u_action));
			}
		}
		else
		{
			$tiers = $tier_manager->get_all_tiers();

			// Stored group tiers without a valid group, such as the default tiers
			// before their groups are chosen, are inactive until one is set.
			$inactive = [];
			foreach ($tiers as $i => $tier)
			{
				if ($tier['action'] === 'group' && !in_array((int) $tier['group_id'], $group_ids, true))
				{
					$inactive[] = $i + 1;
				}
			}
			if ($inactive)
			{
				$errors[] = $user->lang('AUTOPUNISH_TIERS_INACTIVE', implode(', ', $inactive));
			}
		}

		foreach ($tiers as $i => $tier)
		{
			[$duration_value, $duration_unit] = $this->seconds_to_duration($tier['duration_seconds']);

			$tier_tpl = [
				'TIER_ID'           => $tier['tier_id'] ?? 0,
				// TIER_ORDER is the number shown to the admin; TIER_INDEX is the
				// form array key. They must not be the same value — the JS that
				// appends a row indexes from zero, so a one-based key would make
				// the new row collide with the last existing one and overwrite it.
				'TIER_ORDER'        => $i + 1,
				'TIER_INDEX'        => $i,
				'WARNING_THRESHOLD' => $tier['warning_threshold'],
				'ACTION'            => $tier['action'],
				'GROUP_ID'          => $tier['group_id'],
				'DURATION_VALUE'    => $duration_value,
				'DURATION_UNIT'     => $duration_unit,
				// Stored the way phpBB's request class returns text, HTML-escaped;
				// the template escapes for its own context, so decode once here or
				// every save would escape the text again.
				'REASON_TEXT'       => tier_manager::decode_text($tier['reason_text']),
				'NOTIFICATION_TEXT' => tier_manager::decode_text($tier['notification_text']),
			];
			$template->assign_block_vars('tiers', $tier_tpl);

			foreach ($groups as $group)
			{
				$template->assign_block_vars('tiers.groups', [
					'ID'       => $group['group_id'],
					'NAME'     => $group['display_name'],
					'SELECTED' => $group['group_id'] == $tier['group_id'],
				]);
			}
		}

		// Pass groups for new-row JS template
		foreach ($groups as $group)
		{
			$template->assign_block_vars('groups', [
				'ID'   => $group['group_id'],
				'NAME' => $group['display_name'],
			]);
		}

		$template->assign_vars([
			'U_ACTION'          => $this->u_action,
			'AUTOPUNISH_ERRORS' => implode('<br />', $errors),
		]);
	}

	// -------------------------------------------------------------------------
	// Helpers
	// -------------------------------------------------------------------------

	/**
	 * Every group, with the name to display: phpBB's built-in groups are stored
	 * under identifiers such as REGISTERED, which the group helper translates.
	 * Sorted by that display name.
	 */
	private function get_all_groups($db)
	{
		global $phpbb_container;

		/** @var \phpbb\group\helper $group_helper */
		$group_helper = $phpbb_container->get('group_helper');

		$groups = [];
		$sql    = 'SELECT group_id, group_name FROM ' . GROUPS_TABLE;
		$result = $db->sql_query($sql);
		while ($row = $db->sql_fetchrow($result))
		{
			// Group names are stored HTML-escaped; the templates escape them.
			$row['display_name'] = htmlspecialchars_decode($group_helper->get_name($row['group_name']), ENT_COMPAT);
			$groups[] = $row;
		}
		$db->sql_freeresult($result);

		usort($groups, function ($a, $b) {
			return strnatcasecmp($a['display_name'], $b['display_name']);
		});

		return $groups;
	}

	private function parse_duration($value, $unit)
	{
		if ($value <= 0)
		{
			return 0;
		}
		switch ($unit)
		{
			case 'weeks':
				return $value * 7 * 86400;

			case 'months':
				return $value * 30 * 86400;

			default: // days
				return $value * 86400;
		}
	}

	/**
	 * The form's value and unit for a stored length, in the same units the
	 * length is described in elsewhere.
	 */
	private function seconds_to_duration($seconds)
	{
		static $units = [
			'AUTOPUNISH_DURATION_MONTHS' => 'months',
			'AUTOPUNISH_DURATION_WEEKS'  => 'weeks',
			'AUTOPUNISH_DURATION_DAYS'   => 'days',
		];

		[$key, $count] = \biochaos\autopunish\service\duration::lang_args($seconds);
		return [$count, $units[$key] ?? 'days'];
	}
}
