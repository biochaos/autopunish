<?php
/**
 * AutoPunish extension for phpBB.
 *
 * @copyright (c) 2026, biochaos
 * @license GNU General Public License, version 2 (GPL-2.0)
 */

namespace biochaos\autopunish\acp;

class main_module
{
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

			$config->set('autopunish_enabled',               $request->variable('autopunish_enabled', 0));
			$config->set('autopunish_warn_during_punishment', $request->variable('autopunish_warn_during_punishment', 'restart'));
			$config->set('autopunish_cron_interval',          $request->variable('autopunish_cron_interval', 60));
			$config->set('autopunish_dry_run',                $request->variable('autopunish_dry_run', 0));
			$config->set('autopunish_notify_forum',           $request->variable('autopunish_notify_forum', 0));
			$config->set('autopunish_notify_pm',              $request->variable('autopunish_notify_pm', 0));
			$config->set('autopunish_notify_email',           $request->variable('autopunish_notify_email', 0));

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
			$config->set('autopunish_notify_sender_id', $sender_id);

			$config->set('autopunish_notify_subject',          $request->variable('autopunish_notify_subject', '', true));
			$config->set('autopunish_notify_footer',          $request->variable('autopunish_notify_footer', '', true));
			$config->set('autopunish_notify_expiry_subject',  $request->variable('autopunish_notify_expiry_subject', '', true));
			$config->set('autopunish_notify_expiry_text',     $request->variable('autopunish_notify_expiry_text', '', true));
			$config->set('autopunish_notify_commute_subject', $request->variable('autopunish_notify_commute_subject', '', true));
			$config->set('autopunish_notify_commute_text',    $request->variable('autopunish_notify_commute_text', '', true));

			// Exempt groups — stored as comma-separated group IDs
			$exempt = $request->variable('autopunish_exempt_groups', [0]);
			$config->set('autopunish_exempt_groups', implode(',', array_map('intval', $exempt)));

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
				'NAME'     => $group['group_name'],
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
				$duration_value = max(0, (int) ($tier['duration_value'] ?? 0));
				$tiers[] = [
					'warning_threshold' => max(1, (int) ($tier['warning_threshold'] ?? 3)),
					'action'            => ($tier['action'] ?? 'group') === 'deactivate' ? 'deactivate' : 'group',
					'group_id'          => (int) ($tier['group_id'] ?? 0),
					'duration_seconds'  => $this->parse_duration($duration_value, $tier['duration_unit'] ?? 'days'),
					'reason_text'       => (string) ($tier['reason_text'] ?? ''),
					'notification_text' => (string) ($tier['notification_text'] ?? ''),
				];
			}

			$tier_manager->save_tiers($tiers);
			trigger_error($user->lang('ACP_AUTOPUNISH_TIERS_SAVED') . adm_back_link($this->u_action));
		}

		$tiers  = $tier_manager->get_all_tiers();
		$groups = $this->get_all_groups($db);

		foreach ($tiers as $i => $tier)
		{
			[$duration_value, $duration_unit] = $this->seconds_to_duration($tier['duration_seconds']);

			$tier_tpl = [
				'TIER_ID'           => $tier['tier_id'],
				'TIER_ORDER'        => $i + 1,
				'WARNING_THRESHOLD' => $tier['warning_threshold'],
				'ACTION'            => $tier['action'],
				'GROUP_ID'          => $tier['group_id'],
				'DURATION_VALUE'    => $duration_value,
				'DURATION_UNIT'     => $duration_unit,
				'REASON_TEXT'       => $tier['reason_text'],
				'NOTIFICATION_TEXT' => $tier['notification_text'],
			];
			$template->assign_block_vars('tiers', $tier_tpl);

			foreach ($groups as $group)
			{
				$template->assign_block_vars('tiers.groups', [
					'ID'       => $group['group_id'],
					'NAME'     => $group['group_name'],
					'SELECTED' => $group['group_id'] == $tier['group_id'],
				]);
			}
		}

		// Pass groups for new-row JS template
		foreach ($groups as $group)
		{
			$template->assign_block_vars('groups', [
				'ID'   => $group['group_id'],
				'NAME' => $group['group_name'],
			]);
		}

		$template->assign_vars([
			'U_ACTION'    => $this->u_action,
			'TIERS_COUNT' => count($tiers),
		]);
	}

	// -------------------------------------------------------------------------
	// Helpers
	// -------------------------------------------------------------------------

	private function get_all_groups($db)
	{
		$groups = [];
		$sql    = 'SELECT group_id, group_name FROM ' . GROUPS_TABLE . ' ORDER BY group_name ASC';
		$result = $db->sql_query($sql);
		while ($row = $db->sql_fetchrow($result))
		{
			$groups[] = $row;
		}
		$db->sql_freeresult($result);
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
			case 'weeks':  return $value * 7 * 86400;
			case 'months': return $value * 30 * 86400;
			default:       return $value * 86400; // days
		}
	}

	private function seconds_to_duration($seconds)
	{
		if ($seconds <= 0)
		{
			return [0, 'days'];
		}
		$days = $seconds / 86400;
		if ($days % 30 === 0) return [$days / 30, 'months'];
		if ($days % 7  === 0) return [$days / 7,  'weeks'];
		return [$days, 'days'];
	}
}
