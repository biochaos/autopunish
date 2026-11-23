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
	/** What happens to a user who is warned while already punished: the timer starts again... */
	const MODE_RESTART = 'restart';

	/** ...the usual thresholds apply... */
	const MODE_NORMAL = 'normal';

	/** ...or the next tier applies at once. These are the values of the setting. */
	const MODE_ESCALATE = 'escalate';

	/** Every mode there is. */
	const MODES = [self::MODE_RESTART, self::MODE_NORMAL, self::MODE_ESCALATE];

	/** Expired punishments handled per batch. */
	const EXPIRY_BATCH_SIZE = 100;

	/** Seconds the expiry task works for before leaving the rest to its next run. */
	const TIME_BUDGET = 20;

	/** @var \phpbb\db\driver\driver_interface */
	private $db;

	/** @var \phpbb\config\config */
	private $config;

	/** @var \phpbb\group\helper */
	private $group_helper;

	/** @var \phpbb\log\log_interface */
	private $log;

	/** @var \phpbb\user */
	private $user;

	/** @var tier_manager */
	private $tier_manager;

	/** @var account_manager */
	private $accounts;

	/** @var notifier */
	private $notifier;

	/** @var string */
	private $punishments_table;

	/** @var string */
	private $commutations_table;

	public function __construct(
		\phpbb\db\driver\driver_interface $db,
		\phpbb\config\config $config,
		\phpbb\group\helper $group_helper,
		\phpbb\log\log_interface $log,
		\phpbb\user $user,
		tier_manager $tier_manager,
		account_manager $accounts,
		notifier $notifier,
		$punishments_table,
		$commutations_table
	)
	{
		$this->db                 = $db;
		$this->config             = $config;
		$this->group_helper       = $group_helper;
		$this->log                = $log;
		$this->user               = $user;
		$this->tier_manager       = $tier_manager;
		$this->accounts           = $accounts;
		$this->notifier           = $notifier;
		$this->punishments_table  = $punishments_table;
		$this->commutations_table = $commutations_table;
	}

	// -------------------------------------------------------------------------
	// Public API
	// -------------------------------------------------------------------------

	/**
	 * Main entry point called from the warning event.
	 * Handles the full trigger flow per the spec.
	 *
	 * @return bool True when a punishment was applied, restarted or escalated.
	 */
	public function handle_warning(array $user_data)
	{
		if (!$this->config['autopunish_enabled'])
		{
			return false;
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
			return false;
		}

		// A warning count on its own never says whether those warnings have already
		// been answered for. Once a punishment expires the count stays put, so
		// without this the retroactive scan would punish the same user again — and
		// again at a higher tier — every time it is run.
		if (!$this->has_unpunished_warning($user_id))
		{
			return false;
		}

		$active = $this->get_active_punishment($user_id);

		if ($active)
		{
			return $this->handle_warning_during_punishment($user_id, $user_data['username'], $user_warnings, $active);
		}

		$offense_number = $this->get_next_offense_number($user_id);
		$tier           = $this->tier_manager->get_tier_for_offense($offense_number);

		if (!$tier)
		{
			return false;
		}

		if ($user_warnings < $tier['warning_threshold'])
		{
			return false;
		}

		if (!$this->is_tier_usable($tier))
		{
			$this->log_unusable_tier($user_data['username'], $offense_number);
			return false;
		}

		if ($this->config['autopunish_dry_run'])
		{
			$this->log_dry_run($user_data['username'], $offense_number, $tier);
			return false;
		}

		return $this->apply_punishment($user_id, $user_data['username'], $offense_number, $tier);
	}

	/**
	 * Expires all punishments whose end_time has passed.
	 * Called by the cron task.
	 *
	 * They are taken in batches, within a time budget, because after a mass
	 * punishment, such as a retroactive scan, they all fall due together, and
	 * each one can mean a PM and an email. What is left over is picked up by
	 * the next run.
	 *
	 * @param int $time_budget Seconds after which no further batch is started
	 * @return bool True when nothing that was due is left
	 */
	public function expire_punishments($time_budget = self::TIME_BUDGET)
	{
		if (!$this->config['autopunish_enabled'])
		{
			return true;
		}

		$deadline = time() + $time_budget;

		do
		{
			$sql = 'SELECT p.*, u.username
					FROM ' . $this->punishments_table . ' p
					JOIN ' . USERS_TABLE . ' u ON u.user_id = p.user_id
					WHERE p.active = 1 AND p.end_time > 0 AND p.end_time <= ' . time() . '
					ORDER BY p.end_time ASC';

			$result = $this->db->sql_query_limit($sql, self::EXPIRY_BATCH_SIZE);
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
		while (count($rows) === self::EXPIRY_BATCH_SIZE && time() < $deadline);

		return count($rows) < self::EXPIRY_BATCH_SIZE;
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

		if (!$this->close_punishment($punishment))
		{
			return false;
		}

		// Fetch username for logging
		$sql    = 'SELECT username FROM ' . USERS_TABLE . ' WHERE user_id = ' . (int) $punishment['user_id'];
		$result = $this->db->sql_query($sql);
		$username = $this->db->sql_fetchfield('username');
		$this->db->sql_freeresult($result);

		$this->log->add('admin', $admin_id, $this->user->ip, 'LOG_AUTOPUNISH_ENDED_EARLY', false, [$username, $punishment['offense_number']]);

		$this->notifier->notify('biochaos.autopunish.notification.punishment_commuted', $punishment['user_id'], [
			'punishment_id'     => $punishment['punishment_id'],
			'offense_number'    => $punishment['offense_number'],
			'notification_tpl'  => $this->config['autopunish_notify_commute_text'],
			'duration_seconds'  => $punishment['end_time'] > 0 ? $punishment['end_time'] - $punishment['start_time'] : 0,
			'placeholders'      => [
				'USERNAME'       => $username,
				'OFFENSE_NUMBER' => $punishment['offense_number'],
				'REASON'         => $punishment['reason_text'],
			],
		]);

		return true;
	}

	/**
	 * Applies the tier for $step_to_offense as a new punishment (used for step-down after early end).
	 */
	public function apply_stepdown_punishment($user_id, $step_to_offense)
	{
		$tier = $this->tier_manager->get_tier_for_offense($step_to_offense);
		if (!$tier || $this->is_user_exempt($user_id))
		{
			return;
		}

		$sql      = 'SELECT username FROM ' . USERS_TABLE . ' WHERE user_id = ' . (int) $user_id;
		$result   = $this->db->sql_query($sql);
		$username = (string) $this->db->sql_fetchfield('username');
		$this->db->sql_freeresult($result);

		if (!$this->is_tier_usable($tier))
		{
			$this->log_unusable_tier($username, $step_to_offense);
			return;
		}

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
	 * Removes what AutoPunish keeps about users who have been deleted: their
	 * punishments and their commuted-offense count. phpBB takes their group
	 * memberships and notifications with the account, so there is nothing to
	 * undo, and the records would otherwise stay behind for good.
	 *
	 * @param int[] $user_ids
	 */
	public function delete_user_data(array $user_ids)
	{
		$user_ids = array_map('intval', $user_ids);
		if (empty($user_ids))
		{
			return;
		}

		foreach ([$this->punishments_table, $this->commutations_table] as $table)
		{
			$this->db->sql_query('DELETE FROM ' . $table . ' WHERE ' . $this->db->sql_in_set('user_id', $user_ids));
		}
	}

	/**
	 * A tier can be applied when it deactivates, or when the group it adds
	 * users to exists. A group tier without one (the default tiers until an
	 * administrator chooses their groups, or a tier whose group was deleted)
	 * is inactive.
	 */
	public function is_tier_usable(array $tier)
	{
		if ($tier['action'] === tier_manager::ACTION_DEACTIVATE)
		{
			return true;
		}

		// A tier saved before the Tiers page stopped offering such groups can
		// still name one: adding someone to Administrators is no punishment.
		$group = $this->accounts->get_group_row($tier['group_id']);

		return $group !== false && !in_array($group['group_name'], tier_manager::UNSUITABLE_GROUPS, true);
	}

	/**
	 * For a batch of users, what handle_warning() would look up one user at a
	 * time: the next offense number, and whether any warning is newer than the
	 * last one already punished for. Three queries for the whole batch.
	 *
	 * @param int[] $user_ids
	 * @return array user id => ['next_offense' => int, 'has_unpunished_warning' => bool]
	 */
	public function get_scan_state(array $user_ids)
	{
		$user_ids = array_map('intval', $user_ids);
		if (empty($user_ids))
		{
			return [];
		}

		$total = $last_punished = $commuted = $latest = [];

		$sql = 'SELECT user_id, COUNT(*) AS total, MAX(trigger_warning_id) AS last_punished
				FROM ' . $this->punishments_table . '
				WHERE ' . $this->db->sql_in_set('user_id', $user_ids) . '
				GROUP BY user_id';
		$result = $this->db->sql_query($sql);
		while ($row = $this->db->sql_fetchrow($result))
		{
			$total[(int) $row['user_id']]         = (int) $row['total'];
			$last_punished[(int) $row['user_id']] = (int) $row['last_punished'];
		}
		$this->db->sql_freeresult($result);

		$sql = 'SELECT user_id, commuted_offenses
				FROM ' . $this->commutations_table . '
				WHERE ' . $this->db->sql_in_set('user_id', $user_ids);
		$result = $this->db->sql_query($sql);
		while ($row = $this->db->sql_fetchrow($result))
		{
			$commuted[(int) $row['user_id']] = (int) $row['commuted_offenses'];
		}
		$this->db->sql_freeresult($result);

		$sql = 'SELECT user_id, MAX(warning_id) AS latest_warning_id
				FROM ' . WARNINGS_TABLE . '
				WHERE ' . $this->db->sql_in_set('user_id', $user_ids) . '
				GROUP BY user_id';
		$result = $this->db->sql_query($sql);
		while ($row = $this->db->sql_fetchrow($result))
		{
			$latest[(int) $row['user_id']] = (int) $row['latest_warning_id'];
		}
		$this->db->sql_freeresult($result);

		$state = [];
		foreach ($user_ids as $user_id)
		{
			$state[$user_id] = [
				'next_offense'           => max(1, ($total[$user_id] ?? 0) - ($commuted[$user_id] ?? 0) + 1),
				'has_unpunished_warning' => ($latest[$user_id] ?? 0) > ($last_punished[$user_id] ?? 0),
			];
		}

		return $state;
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
		$sql  = 'SELECT p.*, g.group_name
				FROM ' . $this->punishments_table . ' p
				LEFT JOIN ' . GROUPS_TABLE . ' g ON g.group_id = p.group_id
				WHERE p.user_id = ' . (int) $user_id . '
				ORDER BY p.start_time DESC';
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

	/**
	 * Highest warning id currently on a user's account, or 0 when they have none.
	 */
	public function get_latest_warning_id($user_id)
	{
		$sql    = 'SELECT MAX(warning_id) AS latest_warning_id FROM ' . WARNINGS_TABLE . ' WHERE user_id = ' . (int) $user_id;
		$result = $this->db->sql_query($sql);
		$latest = $this->db->sql_fetchfield('latest_warning_id');
		$this->db->sql_freeresult($result);
		return (int) $latest;
	}

	/**
	 * Highest warning id any of the user's previous punishments was issued for, or 0.
	 */
	public function get_last_punished_warning_id($user_id)
	{
		$sql    = 'SELECT MAX(trigger_warning_id) AS last_punished FROM ' . $this->punishments_table . ' WHERE user_id = ' . (int) $user_id;
		$result = $this->db->sql_query($sql);
		$last   = $this->db->sql_fetchfield('last_punished');
		$this->db->sql_freeresult($result);
		return (int) $last;
	}

	/**
	 * True when the user has picked up at least one warning since their most recent
	 * punishment was issued.
	 *
	 * Warning ids are assigned by auto-increment and are never reused, so comparing
	 * the two high-water marks stays correct even after phpBB's warning pruning has
	 * deleted the underlying rows: pruning can only lower the account's highest id,
	 * which errs towards not punishing. A user with no warnings left reports 0 and
	 * is never punished.
	 */
	public function has_unpunished_warning($user_id)
	{
		return $this->get_latest_warning_id($user_id) > $this->get_last_punished_warning_id($user_id);
	}

	// -------------------------------------------------------------------------
	// Internal helpers
	// -------------------------------------------------------------------------

	/**
	 * @return bool True when the active punishment was restarted or replaced.
	 */
	private function handle_warning_during_punishment($user_id, $username, $user_warnings, array $active)
	{
		$mode = $this->config['autopunish_warn_during_punishment'];

		switch ($mode)
		{
			case self::MODE_RESTART:
				if ($active['end_time'] > 0)
				{
					if ($this->config['autopunish_dry_run'])
					{
						$this->log_admin('LOG_AUTOPUNISH_DRY_RUN_RESTART', [$username, $active['offense_number']]);
						return false;
					}

					$duration = $active['end_time'] - $active['start_time'];
					$now      = time();
					$this->db->sql_query('UPDATE ' . $this->punishments_table . '
						SET start_time = ' . $now . ', end_time = ' . ($now + $duration) . ',
							trigger_warning_id = ' . $this->get_latest_warning_id($user_id) . '
						WHERE punishment_id = ' . (int) $active['punishment_id']);
					$this->log_admin('LOG_AUTOPUNISH_RESTARTED', [$username, $active['offense_number']]);
					return true;
				}
			break;

			case self::MODE_NORMAL:
				$offense_number = $this->get_next_offense_number($user_id);
				$tier           = $this->tier_manager->get_tier_for_offense($offense_number);
				if ($tier && $user_warnings >= $tier['warning_threshold'])
				{
					if (!$this->is_tier_usable($tier))
					{
						$this->log_unusable_tier($username, $offense_number);
						return false;
					}

					if ($this->config['autopunish_dry_run'])
					{
						$this->log_dry_run($username, $offense_number, $tier);
						return false;
					}

					$this->end_active_punishment($active);
					return $this->apply_punishment($user_id, $username, $offense_number, $tier);
				}
			break;

			case self::MODE_ESCALATE:
				$offense_number = $this->get_next_offense_number($user_id);
				$tier           = $this->tier_manager->get_tier_for_offense($offense_number);
				if (!$tier)
				{
					break;
				}
				if (!$this->is_tier_usable($tier))
				{
					$this->log_unusable_tier($username, $offense_number);
					return false;
				}
				if ($this->config['autopunish_dry_run'])
				{
					$this->log_dry_run($username, $offense_number, $tier);
					return false;
				}
				$this->end_active_punishment($active);
				$this->log_admin('LOG_AUTOPUNISH_ESCALATED', [$username, $active['offense_number'], $offense_number]);
				return $this->apply_punishment($user_id, $username, $offense_number, $tier);
			break;
		}

		return false;
	}

	/**
	 * Logs the punishment that dry-run mode is holding back.
	 */
	private function log_dry_run($username, $offense_number, array $tier)
	{
		[$action, $group] = $this->log_labels($tier);
		$this->log_admin('LOG_AUTOPUNISH_DRY_RUN', [$username, $offense_number, $action, $group]);
	}

	private function apply_punishment($user_id, $username, $offense_number, array $tier)
	{
		// Deactivation is always permanent: expiry only ever removes a group
		// membership, so a deactivation given an end time would lapse while
		// leaving the account deactivated.
		if ($tier['action'] === tier_manager::ACTION_DEACTIVATE)
		{
			$tier['duration_seconds'] = 0;
		}

		$now      = time();
		$end_time = ($tier['duration_seconds'] > 0) ? ($now + (int) $tier['duration_seconds']) : 0;

		// Make the change first, so a punishment is only recorded once it has
		// taken effect, and note whether this punishment made it.
		if ($tier['action'] === tier_manager::ACTION_DEACTIVATE)
		{
			// An account that is already inactive was deactivated by someone
			// else, and ending this punishment must leave it that way.
			$applied_change = !$this->accounts->is_user_inactive($user_id);
			if ($applied_change)
			{
				$this->accounts->deactivate_user($user_id);
			}
		}
		else
		{
			$result = $this->accounts->add_to_group($user_id, $tier['group_id']);

			// A user who is already in the group keeps that membership when
			// the punishment ends.
			$applied_change = ($result === false);
			if ($result !== false && $result !== 'GROUP_USERS_EXIST')
			{
				$this->log_admin('LOG_AUTOPUNISH_GROUP_FAILED', [$username, (int) $tier['group_id'], $result]);
				return false;
			}
		}

		$sql_ary = [
			'user_id'            => (int) $user_id,
			'offense_number'     => (int) $offense_number,
			'action'             => $tier['action'],
			'group_id'           => (int) $tier['group_id'],
			'reason_text'        => $tier['reason_text'],
			'start_time'         => $now,
			'end_time'           => $end_time,
			'active'             => 1,
			'trigger_warning_id' => $this->get_latest_warning_id($user_id),
			'applied_change'     => (int) $applied_change,
		];

		$this->db->sql_query('INSERT INTO ' . $this->punishments_table . ' ' . $this->db->sql_build_array('INSERT', $sql_ary));
		$punishment_id = $this->db->sql_nextid();

		$duration_str = ($end_time > 0)
			? sprintf($this->user->lang('AUTOPUNISH_LOG_DURATION_UNTIL'),
				$this->format_duration((int) $tier['duration_seconds']),
				$this->user->format_date($end_time))
			: $this->user->lang('AUTOPUNISH_DURATION_PERMANENT');
		[$action, $group] = $this->log_labels($tier);
		$this->log_admin('LOG_AUTOPUNISH_APPLIED', [$username, $offense_number, $action, $group, $duration_str, $tier['reason_text']]);

		$this->notifier->notify('biochaos.autopunish.notification.punishment_applied', $user_id, [
			'punishment_id'     => $punishment_id,
			'offense_number'    => $offense_number,
			'notification_tpl'  => $tier['notification_text'],
			'duration_seconds'  => (int) $tier['duration_seconds'],
			'placeholders'      => [
				'USERNAME'       => $username,
				'REASON'         => $tier['reason_text'],
				'OFFENSE_NUMBER' => $offense_number,
			],
		]);

		return true;
	}

	private function expire_single_punishment(array $punishment)
	{
		if (!$this->close_punishment($punishment))
		{
			return; // Already ended by another process
		}

		$this->log->add('admin', \ANONYMOUS, '', 'LOG_AUTOPUNISH_EXPIRED', false, [$punishment['username'], $punishment['offense_number']]);

		$this->notifier->notify('biochaos.autopunish.notification.punishment_expired', $punishment['user_id'], [
			'punishment_id'     => $punishment['punishment_id'],
			'offense_number'    => $punishment['offense_number'],
			'notification_tpl'  => $this->config['autopunish_notify_expiry_text'],
			'duration_seconds'  => $punishment['end_time'] > 0 ? $punishment['end_time'] - $punishment['start_time'] : 0,
			'placeholders'      => [
				'USERNAME'       => $punishment['username'],
				'OFFENSE_NUMBER' => $punishment['offense_number'],
				'REASON'         => $punishment['reason_text'],
			],
		]);
	}

	private function end_active_punishment(array $active)
	{
		$this->close_punishment($active);
	}

	/**
	 * Marks a punishment as over and reverses what it did, as one step. Were
	 * they separate, a failure between them would leave a punishment recorded
	 * as ended whose group membership was never removed, and nothing would
	 * ever come back to it.
	 *
	 * The update is conditional, so that a cron run and an administrator, or
	 * two administrators, ending the same punishment at the same moment do not
	 * undo it twice.
	 *
	 * @return bool False when the punishment was already over
	 */
	private function close_punishment(array $punishment)
	{
		$this->db->sql_transaction('begin');

		$this->db->sql_query('UPDATE ' . $this->punishments_table . ' SET active = 0 WHERE punishment_id = ' . (int) $punishment['punishment_id'] . ' AND active = 1');
		$closed = (bool) $this->db->sql_affectedrows();

		if ($closed)
		{
			$this->undo_punishment($punishment);
		}

		$this->db->sql_transaction('commit');

		return $closed;
	}

	/**
	 * Reverses the change a punishment made to the account, but only a change
	 * it made itself: a membership or deactivation that was already there
	 * before the punishment is left alone.
	 */
	private function undo_punishment(array $punishment)
	{
		if (!$punishment['applied_change'])
		{
			return;
		}

		if ($punishment['action'] === tier_manager::ACTION_GROUP && $punishment['group_id'])
		{
			$this->accounts->remove_from_group($punishment['user_id'], $punishment['group_id']);
		}
		else if ($punishment['action'] === tier_manager::ACTION_DEACTIVATE)
		{
			$this->accounts->reactivate_user($punishment['user_id']);
		}
	}

	/**
	 * An admin log entry for something done by the current user: the moderator
	 * whose warning triggered it, or the administrator in the ACP.
	 */
	private function log_admin($operation, array $arguments)
	{
		$this->log->add('admin', $this->user->data['user_id'], $this->user->ip, $operation, false, $arguments);
	}

	private function log_unusable_tier($username, $offense_number)
	{
		$this->log_admin('LOG_AUTOPUNISH_TIER_INACTIVE', [$username, $offense_number]);
	}

	/**
	 * What the admin log shows for a punishment's action and group. The log
	 * keeps its arguments as plain strings, so they are worded now, in the
	 * language of whoever is acting, instead of leaving the identifiers
	 * "group" and "deactivate" and a group number for the log to print.
	 *
	 * @return string[] [action label, group name]
	 */
	private function log_labels(array $tier)
	{
		if ($tier['action'] === tier_manager::ACTION_DEACTIVATE)
		{
			return [$this->user->lang('AUTOPUNISH_ACTION_DEACTIVATE'), '—'];
		}

		$group = $this->accounts->get_group_row($tier['group_id']);

		return [
			$this->user->lang('AUTOPUNISH_ACTION_GROUP'),
			// Built-in groups are stored under identifiers such as REGISTERED.
			$group ? $this->group_helper->get_name($group['group_name']) : '—',
		];
	}

	private function is_user_exempt($user_id)
	{
		// Founders and bots are never punished: phpBB's user_active_flip() skips
		// them, so a deactivation would be recorded and announced without
		// happening, and a group means nothing to a founder.
		$sql       = 'SELECT user_type FROM ' . USERS_TABLE . ' WHERE user_id = ' . (int) $user_id;
		$result    = $this->db->sql_query($sql);
		$user_type = $this->db->sql_fetchfield('user_type');
		$this->db->sql_freeresult($result);
		if ($user_type !== false && in_array((int) $user_type, [USER_FOUNDER, USER_IGNORE], true))
		{
			return true;
		}

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

	/**
	 * A punishment length in the current user's language, for the admin log.
	 */
	private function format_duration($seconds)
	{
		[$key, $count] = duration::lang_args($seconds);
		return $this->user->lang($key, $count);
	}
}
