<?php
/**
 * AutoPunish extension for phpBB.
 *
 * @copyright (c) 2026, biochaos
 * @license GNU General Public License, version 2 (GPL-2.0)
 */

namespace biochaos\autopunish\service;

class punishment_manager
{
	/** @var \phpbb\db\driver\driver_interface */
	private $db;

	/** @var \phpbb\config\config */
	private $config;

	/** @var \phpbb\group\helper */
	private $group_helper;

	/** @var \phpbb\log\log_interface */
	private $log;

	/** @var \phpbb\notification\manager */
	private $notification_manager;

	/** @var \phpbb\user */
	private $user;

	/** @var tier_manager */
	private $tier_manager;

	/** @var string */
	private $punishments_table;

	/** @var string */
	private $commutations_table;

	/** @var string */
	private $phpbb_root_path;

	/** @var string */
	private $php_ext;

	public function __construct(
		\phpbb\db\driver\driver_interface $db,
		\phpbb\config\config $config,
		\phpbb\group\helper $group_helper,
		\phpbb\log\log_interface $log,
		\phpbb\notification\manager $notification_manager,
		\phpbb\user $user,
		tier_manager $tier_manager,
		$punishments_table,
		$commutations_table,
		$phpbb_root_path,
		$php_ext
	)
	{
		$this->db                   = $db;
		$this->config               = $config;
		$this->group_helper         = $group_helper;
		$this->log                  = $log;
		$this->notification_manager = $notification_manager;
		$this->user                 = $user;
		$this->tier_manager         = $tier_manager;
		$this->punishments_table    = $punishments_table;
		$this->commutations_table   = $commutations_table;
		$this->phpbb_root_path      = $phpbb_root_path;
		$this->php_ext              = $php_ext;
	}

	// -------------------------------------------------------------------------
	// Public API
	// -------------------------------------------------------------------------

	/**
	 * Main entry point called from the warning event.
	 * Handles the full trigger flow per the spec.
	 */
	public function handle_warning(array $user_data)
	{
		if (!$this->config['autopunish_enabled'])
		{
			return;
		}

		$user_id = (int) $user_data['user_id'];

		// Re-fetch warnings from DB — the event fires after add_warning() updates the row,
		// but the $user_row array still holds the pre-warning count.
		$sql    = 'SELECT user_warnings FROM ' . USERS_TABLE . ' WHERE user_id = ' . $user_id;
		$result = $this->db->sql_query($sql);
		$user_warnings = (int) $this->db->sql_fetchfield('user_warnings');
		$this->db->sql_freeresult($result);

		if ($this->is_user_exempt($user_id))
		{
			return;
		}

		$active = $this->get_active_punishment($user_id);

		if ($active)
		{
			$this->handle_warning_during_punishment($user_id, $user_data['username'], $user_warnings, $active);
		}
		else
		{
			$offense_number = $this->get_next_offense_number($user_id);
			$tier           = $this->tier_manager->get_tier_for_offense($offense_number);

			if (!$tier)
			{
				return;
			}

			if ($this->config['autopunish_dry_run'])
			{
				$this->log->add('admin', $this->user->data['user_id'], $this->user->ip, 'LOG_AUTOPUNISH_DRY_RUN', false, [$user_data['username'], $offense_number, $tier['action']]);
				return;
			}

			if ($user_warnings >= $tier['warning_threshold'])
			{
				$this->apply_punishment($user_id, $user_data['username'], $offense_number, $tier);
			}
		}
	}

	/**
	 * Expires all punishments whose end_time has passed.
	 * Called by the cron task.
	 */
	public function expire_punishments()
	{
		if (!$this->config['autopunish_enabled'])
		{
			return;
		}

		$now = time();
		$sql = 'SELECT p.*, u.username
				FROM ' . $this->punishments_table . ' p
				JOIN ' . USERS_TABLE . ' u ON u.user_id = p.user_id
				WHERE p.active = 1 AND p.end_time > 0 AND p.end_time <= ' . $now;

		$result = $this->db->sql_query($sql);
		$rows   = [];
		while ($row = $this->db->sql_fetchrow($result))
		{
			$rows[] = $row;
		}
		$this->db->sql_freeresult($result);

		foreach ($rows as $punishment)
		{
			$this->expire_single_punishment($punishment);
		}
	}

	/**
	 * Ends an active punishment early (commutation of the sentence).
	 */
	public function end_punishment_early($punishment_id, $admin_id)
	{
		$punishment = $this->get_punishment_by_id($punishment_id);
		if (!$punishment || !$punishment['active'])
		{
			return false;
		}

		$this->db->sql_query('UPDATE ' . $this->punishments_table . ' SET active = 0 WHERE punishment_id = ' . (int) $punishment_id);

		if ($punishment['action'] === 'group' && $punishment['group_id'])
		{
			$this->remove_from_group($punishment['user_id'], $punishment['group_id']);
		}
		else if ($punishment['action'] === 'deactivate')
		{
			$this->reactivate_user($punishment['user_id']);
		}

		// Fetch username for logging
		$sql    = 'SELECT username FROM ' . USERS_TABLE . ' WHERE user_id = ' . (int) $punishment['user_id'];
		$result = $this->db->sql_query($sql);
		$username = $this->db->sql_fetchfield('username');
		$this->db->sql_freeresult($result);

		$this->log->add('admin', $admin_id, $this->user->ip, 'LOG_AUTOPUNISH_ENDED_EARLY', false, [$username, $punishment['offense_number']]);

		$commute_placeholders = [
			'USERNAME'       => $username,
			'OFFENSE_NUMBER' => $punishment['offense_number'],
			'DURATION'       => $this->format_duration($punishment['end_time'] > 0 ? $punishment['end_time'] - $punishment['start_time'] : 0),
			'REASON'         => $punishment['reason_text'],
		];
		$commute_text = $this->config['autopunish_notify_commute_text'];
		$this->send_notification('biochaos.autopunish.notification.punishment_commuted', $punishment['user_id'], [
			'punishment_id'     => $punishment['punishment_id'],
			'offense_number'    => $punishment['offense_number'],
			'notification_text' => !empty($commute_text) ? $this->replace_placeholders($commute_text, $commute_placeholders) : '',
			'notification_tpl'  => $commute_text,
			'duration_seconds'  => $punishment['end_time'] > 0 ? $punishment['end_time'] - $punishment['start_time'] : 0,
			'placeholders'      => $commute_placeholders,
		]);

		return true;
	}

	/**
	 * Applies the tier for $step_to_offense as a new punishment (used for step-down after early end).
	 */
	public function apply_stepdown_punishment($user_id, $step_to_offense)
	{
		$tier = $this->tier_manager->get_tier_for_offense($step_to_offense);
		if (!$tier)
		{
			return;
		}

		$sql      = 'SELECT username FROM ' . USERS_TABLE . ' WHERE user_id = ' . (int) $user_id;
		$result   = $this->db->sql_query($sql);
		$username = (string) $this->db->sql_fetchfield('username');
		$this->db->sql_freeresult($result);

		$offense_number = $this->get_next_offense_number($user_id);
		$this->apply_punishment($user_id, $username, $offense_number, $tier);
	}

	/**
	 * Updates the commuted_offenses count for a user.
	 */
	public function set_commuted_offenses($user_id, $commuted, $admin_id)
	{
		$user_id   = (int) $user_id;
		$commuted  = (int) $commuted;
		$total     = $this->get_total_punishments($user_id);
		$commuted  = min($commuted, $total);

		$existing_sql = 'SELECT commuted_offenses FROM ' . $this->commutations_table . ' WHERE user_id = ' . $user_id;
		$result       = $this->db->sql_query($existing_sql);
		$old_row      = $this->db->sql_fetchrow($result);
		$this->db->sql_freeresult($result);
		$old_commuted = $old_row ? (int) $old_row['commuted_offenses'] : 0;

		if ($old_row)
		{
			$this->db->sql_query('UPDATE ' . $this->commutations_table . ' SET commuted_offenses = ' . $commuted . ' WHERE user_id = ' . $user_id);
		}
		else
		{
			$this->db->sql_query('INSERT INTO ' . $this->commutations_table . ' ' . $this->db->sql_build_array('INSERT', ['user_id' => $user_id, 'commuted_offenses' => $commuted]));
		}

		$sql    = 'SELECT username FROM ' . USERS_TABLE . ' WHERE user_id = ' . $user_id;
		$result = $this->db->sql_query($sql);
		$username = $this->db->sql_fetchfield('username');
		$this->db->sql_freeresult($result);

		$delta     = $commuted - $old_commuted;
		$effective = max(0, $total - $commuted);
		$this->log->add('admin', $admin_id, $this->user->ip, 'LOG_AUTOPUNISH_COMMUTED', false, [$username, $delta, $commuted, $effective]);
	}

	/**
	 * Returns the next offense number for a user.
	 */
	public function get_next_offense_number($user_id)
	{
		$total    = $this->get_total_punishments($user_id);
		$commuted = $this->get_commuted_offenses($user_id);
		return max(1, $total - $commuted + 1);
	}

	/**
	 * Returns the active punishment row for a user, or false.
	 */
	public function get_active_punishment($user_id)
	{
		$sql    = 'SELECT * FROM ' . $this->punishments_table . ' WHERE user_id = ' . (int) $user_id . ' AND active = 1';
		$result = $this->db->sql_query($sql);
		$row    = $this->db->sql_fetchrow($result);
		$this->db->sql_freeresult($result);
		return $row ?: false;
	}

	/**
	 * Returns all punishments for a user ordered by start_time descending.
	 */
	public function get_punishment_history($user_id)
	{
		$rows = [];
		$sql  = 'SELECT * FROM ' . $this->punishments_table . ' WHERE user_id = ' . (int) $user_id . ' ORDER BY start_time DESC';
		$result = $this->db->sql_query($sql);
		while ($row = $this->db->sql_fetchrow($result))
		{
			$rows[] = $row;
		}
		$this->db->sql_freeresult($result);
		return $rows;
	}

	public function get_total_punishments($user_id)
	{
		$sql    = 'SELECT COUNT(*) AS total FROM ' . $this->punishments_table . ' WHERE user_id = ' . (int) $user_id;
		$result = $this->db->sql_query($sql);
		$total  = (int) $this->db->sql_fetchfield('total');
		$this->db->sql_freeresult($result);
		return $total;
	}

	public function get_commuted_offenses($user_id)
	{
		$sql    = 'SELECT commuted_offenses FROM ' . $this->commutations_table . ' WHERE user_id = ' . (int) $user_id;
		$result = $this->db->sql_query($sql);
		$val    = $this->db->sql_fetchfield('commuted_offenses');
		$this->db->sql_freeresult($result);
		return ($val === false) ? 0 : (int) $val;
	}

	// -------------------------------------------------------------------------
	// Internal helpers
	// -------------------------------------------------------------------------

	private function handle_warning_during_punishment($user_id, $username, $user_warnings, array $active)
	{
		$mode = $this->config['autopunish_warn_during_punishment'];

		switch ($mode)
		{
			case 'restart':
				if ($active['end_time'] > 0)
				{
					$duration = $active['end_time'] - $active['start_time'];
					$now      = time();
					$this->db->sql_query('UPDATE ' . $this->punishments_table . '
						SET start_time = ' . $now . ', end_time = ' . ($now + $duration) . '
						WHERE punishment_id = ' . (int) $active['punishment_id']);
					$this->log->add('admin', $this->user->data['user_id'], $this->user->ip, 'LOG_AUTOPUNISH_RESTARTED', false, [$username, $active['offense_number']]);
				}
			break;

			case 'normal':
				$offense_number = $this->get_next_offense_number($user_id);
				$tier           = $this->tier_manager->get_tier_for_offense($offense_number);
				if ($tier && $user_warnings >= $tier['warning_threshold'])
				{
					$this->end_active_punishment($active);
					$this->apply_punishment($user_id, $username, $offense_number, $tier);
				}
			break;

			case 'escalate':
				$offense_number = $this->get_next_offense_number($user_id);
				$tier           = $this->tier_manager->get_tier_for_offense($offense_number);
				if (!$tier)
				{
					break;
				}
				$this->end_active_punishment($active);
				$this->log->add('admin', $this->user->data['user_id'], $this->user->ip, 'LOG_AUTOPUNISH_ESCALATED', false,
					[$username, $active['offense_number'], $offense_number]);
				$this->apply_punishment($user_id, $username, $offense_number, $tier);
			break;
		}
	}

	private function apply_punishment($user_id, $username, $offense_number, array $tier)
	{
		$now      = time();
		$end_time = ($tier['duration_seconds'] > 0) ? ($now + (int) $tier['duration_seconds']) : 0;

		$sql_ary = [
			'user_id'        => (int) $user_id,
			'offense_number' => (int) $offense_number,
			'action'         => $tier['action'],
			'group_id'       => (int) $tier['group_id'],
			'reason_text'    => $tier['reason_text'],
			'start_time'     => $now,
			'end_time'       => $end_time,
			'active'         => 1,
		];

		$this->db->sql_query('INSERT INTO ' . $this->punishments_table . ' ' . $this->db->sql_build_array('INSERT', $sql_ary));
		$punishment_id = $this->db->sql_nextid();

		if ($tier['action'] === 'deactivate')
		{
			$this->deactivate_user($user_id);
		}
		else
		{
			$this->add_to_group($user_id, $tier['group_id']);
		}

		$duration_str = ($end_time > 0)
			? sprintf($this->user->lang('AUTOPUNISH_LOG_DURATION_UNTIL'),
				$this->format_duration((int) $tier['duration_seconds']),
				$this->user->format_date($end_time))
			: $this->user->lang('AUTOPUNISH_DURATION_PERMANENT');
		$this->log->add('admin', $this->user->data['user_id'], $this->user->ip, 'LOG_AUTOPUNISH_APPLIED', false,
			[$username, $offense_number, $tier['action'], $tier['group_id'], $duration_str, $tier['reason_text']]);

		$placeholders = [
			'USERNAME'       => $username,
			'DURATION'       => $this->format_duration((int) $tier['duration_seconds']),
			'REASON'         => $tier['reason_text'],
			'OFFENSE_NUMBER' => $offense_number,
		];

		$notification_text = !empty($tier['notification_text'])
			? $this->replace_placeholders($tier['notification_text'], $placeholders)
			: '';

		$this->send_notification('biochaos.autopunish.notification.punishment_applied', $user_id, [
			'punishment_id'     => $punishment_id,
			'offense_number'    => $offense_number,
			'notification_text' => $notification_text,
			'notification_tpl'  => $tier['notification_text'],
			'duration_seconds'  => (int) $tier['duration_seconds'],
			'placeholders'      => $placeholders,
		]);
	}

	private function expire_single_punishment(array $punishment)
	{
		$this->db->sql_query('UPDATE ' . $this->punishments_table . ' SET active = 0 WHERE punishment_id = ' . (int) $punishment['punishment_id'] . ' AND active = 1');
		if (!$this->db->sql_affectedrows())
		{
			return; // Already expired by another process
		}

		if ($punishment['action'] === 'group' && $punishment['group_id'])
		{
			$this->remove_from_group($punishment['user_id'], $punishment['group_id']);
		}

		$this->log->add('admin', \ANONYMOUS, '', 'LOG_AUTOPUNISH_EXPIRED', false, [$punishment['username'], $punishment['offense_number']]);

		$expiry_placeholders = [
			'USERNAME'       => $punishment['username'],
			'OFFENSE_NUMBER' => $punishment['offense_number'],
			'DURATION'       => $this->format_duration($punishment['end_time'] > 0 ? $punishment['end_time'] - $punishment['start_time'] : 0),
			'REASON'         => $punishment['reason_text'],
		];
		$expiry_text = $this->config['autopunish_notify_expiry_text'];
		$this->send_notification('biochaos.autopunish.notification.punishment_expired', $punishment['user_id'], [
			'punishment_id'     => $punishment['punishment_id'],
			'offense_number'    => $punishment['offense_number'],
			'notification_text' => !empty($expiry_text) ? $this->replace_placeholders($expiry_text, $expiry_placeholders) : '',
			'notification_tpl'  => $expiry_text,
			'duration_seconds'  => $punishment['end_time'] > 0 ? $punishment['end_time'] - $punishment['start_time'] : 0,
			'placeholders'      => $expiry_placeholders,
		]);
	}

	private function end_active_punishment(array $active)
	{
		$this->db->sql_query('UPDATE ' . $this->punishments_table . ' SET active = 0 WHERE punishment_id = ' . (int) $active['punishment_id']);

		if ($active['action'] === 'group' && $active['group_id'])
		{
			$this->remove_from_group($active['user_id'], $active['group_id']);
		}
		else if ($active['action'] === 'deactivate')
		{
			$this->reactivate_user($active['user_id']);
		}
	}

	private function is_user_exempt($user_id)
	{
		$exempt_groups = array_filter(explode(',', $this->config['autopunish_exempt_groups']));
		if (empty($exempt_groups))
		{
			return false;
		}

		$sql = 'SELECT 1 FROM ' . USER_GROUP_TABLE . '
				WHERE user_id = ' . (int) $user_id . '
				AND ' . $this->db->sql_in_set('group_id', array_map('intval', $exempt_groups)) . '
				AND user_pending = 0';
		$result = $this->db->sql_query_limit($sql, 1);
		$found  = (bool) $this->db->sql_fetchrow($result);
		$this->db->sql_freeresult($result);
		return $found;
	}

	private function get_punishment_by_id($punishment_id)
	{
		$sql    = 'SELECT * FROM ' . $this->punishments_table . ' WHERE punishment_id = ' . (int) $punishment_id;
		$result = $this->db->sql_query($sql);
		$row    = $this->db->sql_fetchrow($result);
		$this->db->sql_freeresult($result);
		return $row ?: false;
	}

	private function ensure_user_functions_loaded()
	{
		if (!function_exists('group_user_add'))
		{
			include($this->phpbb_root_path . 'includes/functions_user.' . $this->php_ext);
		}
	}

	private function add_to_group($user_id, $group_id)
	{
		$this->ensure_user_functions_loaded();
		\group_user_add($group_id, [$user_id]);
	}

	private function remove_from_group($user_id, $group_id)
	{
		$this->ensure_user_functions_loaded();
		\group_user_del($group_id, [$user_id]);
	}

	private function deactivate_user($user_id)
	{
		$this->ensure_user_functions_loaded();
		\user_active_flip('deactivate', $user_id, \INACTIVE_MANUAL);
	}

	private function reactivate_user($user_id)
	{
		$this->ensure_user_functions_loaded();
		\user_active_flip('activate', $user_id);
	}

	private function format_duration($seconds)
	{
		if ($seconds <= 0)
		{
			return $this->user->lang('AUTOPUNISH_DURATION_PERMANENT');
		}
		$days = (int) round($seconds / 86400);
		if ($days % 30 === 0) { $n = (int) ($days / 30); return $this->user->lang('AUTOPUNISH_DURATION_MONTHS', $n); }
		if ($days % 7  === 0) { $n = (int) ($days / 7);  return $this->user->lang('AUTOPUNISH_DURATION_WEEKS',  $n); }
		return $this->user->lang('AUTOPUNISH_DURATION_DAYS', $days);
	}

	private function replace_placeholders($text, array $vars)
	{
		foreach ($vars as $key => $value)
		{
			$text = str_replace('{' . $key . '}', $value, $text);
		}
		return $text;
	}

	private function send_notification($type, $user_id, array $data)
	{
		// Forum bell — always send if enabled, uses pre-formatted text in current language
		if ($this->config['autopunish_notify_forum'])
		{
			$this->notification_manager->add_notifications($type, array_merge($data, ['user_id' => $user_id]));
		}

		if (!$this->config['autopunish_notify_pm'] && !$this->config['autopunish_notify_email'])
		{
			return;
		}

		// Load recipient's language file once — used for body, DURATION, and subject
		$recipient_lang_data = $this->load_lang_array($this->get_user_lang($user_id));

		$placeholders = $data['placeholders'] ?? [];
		if (isset($data['duration_seconds']))
		{
			$placeholders['DURATION'] = $this->format_duration_from_lang($data['duration_seconds'], $recipient_lang_data);
		}

		static $type_defaults = [
			'biochaos.autopunish.notification.punishment_applied'  => [
				'title' => 'AUTOPUNISH_NOTIFY_APPLIED_TITLE',
				'body'  => 'AUTOPUNISH_NOTIFY_APPLIED_DEFAULT_BODY',
			],
			'biochaos.autopunish.notification.punishment_expired'  => [
				'title' => 'AUTOPUNISH_NOTIFY_EXPIRED_TITLE',
				'body'  => 'AUTOPUNISH_NOTIFY_EXPIRED_DEFAULT_BODY',
			],
			'biochaos.autopunish.notification.punishment_commuted' => [
				'title' => 'AUTOPUNISH_NOTIFY_COMMUTED_TITLE',
				'body'  => 'AUTOPUNISH_NOTIFY_COMMUTED_DEFAULT_BODY',
			],
		];

		// Body: configured template → language file default → skip
		$tpl = $data['notification_tpl'] ?? '';
		if ($tpl !== '')
		{
			$pm_text = $this->replace_placeholders($tpl, $placeholders);
		}
		else if (isset($type_defaults[$type], $recipient_lang_data[$type_defaults[$type]['body']]))
		{
			$pm_text = $this->replace_placeholders($recipient_lang_data[$type_defaults[$type]['body']], $placeholders);
		}
		else
		{
			return;
		}

		$footer = $this->replace_placeholders($this->config['autopunish_notify_footer'], $placeholders);
		$body   = $pm_text . ($footer ? "\n\n" . $footer : '');

		// Subject: type-specific template → language file title → body text
		static $type_subject_config = [
			'biochaos.autopunish.notification.punishment_applied'  => 'autopunish_notify_subject',
			'biochaos.autopunish.notification.punishment_expired'  => 'autopunish_notify_expiry_subject',
			'biochaos.autopunish.notification.punishment_commuted' => 'autopunish_notify_commute_subject',
		];
		$subject_config_key = $type_subject_config[$type] ?? 'autopunish_notify_subject';
		$subject_tpl = $this->config[$subject_config_key] ?? '';
		if (!empty($subject_tpl))
		{
			$subject = $this->replace_placeholders($subject_tpl, $placeholders);
		}
		else if (isset($type_defaults[$type], $recipient_lang_data[$type_defaults[$type]['title']]))
		{
			$subject = $recipient_lang_data[$type_defaults[$type]['title']];
		}
		else
		{
			$subject = $pm_text;
		}

		if ($this->config['autopunish_notify_pm'])
		{
			$this->send_pm($user_id, $subject, $body);
		}

		if ($this->config['autopunish_notify_email'])
		{
			$this->send_email($user_id, $subject, $body);
		}
	}

	private function get_user_lang($user_id)
	{
		$sql    = 'SELECT user_lang FROM ' . USERS_TABLE . ' WHERE user_id = ' . (int) $user_id;
		$result = $this->db->sql_query($sql);
		$lang   = $this->db->sql_fetchfield('user_lang');
		$this->db->sql_freeresult($result);
		return $lang ?: 'en';
	}

	private function load_lang_array($lang_code)
	{
		$lang = [];
		foreach ([$lang_code, 'en'] as $try_lang)
		{
			$file = $this->phpbb_root_path . 'ext/biochaos/autopunish/language/' . $try_lang . '/common.php';
			if (file_exists($file))
			{
				include $file;
				break;
			}
		}
		return $lang;
	}

	private function format_duration_from_lang($seconds, array $lang)
	{
		if ($seconds <= 0)
		{
			return $lang['AUTOPUNISH_DURATION_PERMANENT'] ?? 'permanent';
		}

		$days = (int) round($seconds / 86400);
		if ($days % 30 === 0)      { $n = (int) ($days / 30); $arr = $lang['AUTOPUNISH_DURATION_MONTHS'] ?? null; }
		else if ($days % 7 === 0)  { $n = (int) ($days / 7);  $arr = $lang['AUTOPUNISH_DURATION_WEEKS']  ?? null; }
		else                       { $n = $days;               $arr = $lang['AUTOPUNISH_DURATION_DAYS']   ?? null; }

		if ($arr === null)    return (string) $n;
		if (is_array($arr))  return sprintf($n === 1 ? ($arr[1] ?? '%d') : ($arr[2] ?? '%d'), $n);
		return sprintf($arr, $n);
	}

	private function send_pm($to_user_id, $subject, $body)
	{
		if (!function_exists('submit_pm'))
		{
			include($this->phpbb_root_path . 'includes/functions_privmsgs.' . $this->php_ext);
		}
		if (!function_exists('generate_text_for_storage'))
		{
			include($this->phpbb_root_path . 'includes/functions_content.' . $this->php_ext);
		}

		$sender_id = (int) $this->config['autopunish_notify_sender_id'];
		if (!$sender_id)
		{
			return;
		}

		// Parse BBCode in message body
		$bbcode_uid      = \substr(\str_replace('.', '', \uniqid('', true)), -8);
		$bbcode_bitfield = '';
		$bbcode_flags    = \OPTION_FLAG_BBCODE | \OPTION_FLAG_SMILIES | \OPTION_FLAG_LINKS;
		\generate_text_for_storage($body, $bbcode_uid, $bbcode_bitfield, $bbcode_flags, true, true, true);

		$pm_data = [
			'from_user_id'    => $sender_id,
			'from_username'   => '',
			'from_user_ip'    => $this->user->ip ?: '0.0.0.0',
			'reply_from_msgid' => 0,
			'icon_id'         => 0,
			'enable_bbcode'   => 1,
			'enable_smilies'  => 1,
			'enable_urls'     => 1,
			'enable_sig'      => 0,
			'message'         => $body,
			'bbcode_bitfield' => $bbcode_bitfield,
			'bbcode_uid'      => $bbcode_uid,
			'address_list'    => ['u' => [$to_user_id => 'to']],
		];

		// Fetch sender username
		$sql      = 'SELECT username FROM ' . USERS_TABLE . ' WHERE user_id = ' . $sender_id;
		$result   = $this->db->sql_query($sql);
		$username = $this->db->sql_fetchfield('username');
		$this->db->sql_freeresult($result);

		if (!$username)
		{
			$this->log->add('admin', $this->user->data['user_id'], $this->user->ip, 'LOG_AUTOPUNISH_SENDER_MISSING', false, [$sender_id]);
			return;
		}

		$pm_data['from_username'] = (string) $username;
		\submit_pm('post', $subject, $pm_data, false);
	}

	private function send_email($to_user_id, $subject, $body)
	{
		$sql    = 'SELECT username, user_email, user_lang FROM ' . USERS_TABLE . ' WHERE user_id = ' . (int) $to_user_id;
		$result = $this->db->sql_query($sql);
		$user   = $this->db->sql_fetchrow($result);
		$this->db->sql_freeresult($result);

		if (!$user || empty($user['user_email']))
		{
			return;
		}

		if (!class_exists('messenger'))
		{
			include($this->phpbb_root_path . 'includes/functions_messenger.' . $this->php_ext);
		}

		$messenger = new \messenger(false);
		$messenger->template('@biochaos_autopunish/notification_email', $user['user_lang']);
		$messenger->to($user['user_email'], $user['username']);
		$messenger->assign_vars([
			'SUBJECT'  => $subject,
			'MESSAGE'  => $body,
		]);
		$messenger->send(\NOTIFY_EMAIL);
	}
}
