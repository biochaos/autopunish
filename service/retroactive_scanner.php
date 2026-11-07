<?php
/**
 * AutoPunish extension for phpBB.
 *
 * @copyright (c) 2026, biochaos
 * @license GNU General Public License, version 2 (GPL-2.0)
 */

namespace biochaos\autopunish\service;

class retroactive_scanner
{
	/** Candidates read and checked per batch. */
	const BATCH_SIZE = 500;

	/** @var \phpbb\db\driver\driver_interface */
	private $db;

	/** @var \phpbb\config\config */
	private $config;

	/** @var punishment_manager */
	private $punishment_manager;

	/** @var tier_manager */
	private $tier_manager;

	/** @var \phpbb\log\log_interface */
	private $log;

	/** @var string */
	private $punishments_table;

	/** @var string */
	private $commutations_table;

	public function __construct(
		\phpbb\db\driver\driver_interface $db,
		\phpbb\config\config $config,
		\phpbb\log\log_interface $log,
		punishment_manager $punishment_manager,
		tier_manager $tier_manager,
		$punishments_table,
		$commutations_table
	)
	{
		$this->db                 = $db;
		$this->config             = $config;
		$this->log                = $log;
		$this->punishment_manager = $punishment_manager;
		$this->tier_manager       = $tier_manager;
		$this->punishments_table  = $punishments_table;
		$this->commutations_table = $commutations_table;
	}

	/**
	 * Runs the retroactive scan.
	 * Finds all users with warnings >= tier 1 threshold who don't already
	 * have an active punishment, applies the appropriate tier, then logs.
	 */
	public function run()
	{
		$tiers = $this->tier_manager->get_all_tiers();
		if (empty($tiers))
		{
			return;
		}

		// Use the lowest threshold across all tiers as the minimum to query
		$min_threshold = min(array_column($tiers, 'warning_threshold'));

		// Build exempt group exclusion subquery
		$exempt_ids = array_filter(explode(',', $this->config['autopunish_exempt_groups']));

		$sql = 'SELECT u.user_id, u.username, u.user_warnings
				FROM ' . USERS_TABLE . ' u
				WHERE u.user_warnings >= ' . (int) $min_threshold . '
				AND u.user_type NOT IN (' . USER_IGNORE . ', ' . USER_FOUNDER . ')
				AND NOT EXISTS (
					SELECT 1 FROM ' . $this->punishments_table . ' p
					WHERE p.user_id = u.user_id AND p.active = 1
				)';

		if (!empty($exempt_ids))
		{
			$sql .= ' AND NOT EXISTS (
				SELECT 1 FROM ' . USER_GROUP_TABLE . ' ug
				WHERE ug.user_id = u.user_id
				AND ' . $this->db->sql_in_set('ug.group_id', array_map('intval', $exempt_ids)) . '
				AND ug.user_pending = 0
			)';
		}

		// Candidates are read in bounded batches, keyed on user id, and each
		// batch's counters are fetched in one go. Only users who pass those
		// checks reach handle_warning(), which re-checks and applies.
		$punished = 0;
		$last_id  = 0;
		do
		{
			$result = $this->db->sql_query_limit($sql . ' AND u.user_id > ' . (int) $last_id . ' ORDER BY u.user_id ASC', self::BATCH_SIZE);
			$users  = [];
			while ($row = $this->db->sql_fetchrow($result))
			{
				$users[(int) $row['user_id']] = $row;
			}
			$this->db->sql_freeresult($result);

			if (empty($users))
			{
				break;
			}
			$last_id = max(array_keys($users));

			$state = $this->punishment_manager->get_scan_state(array_keys($users));
			foreach ($users as $user_id => $user)
			{
				if (!$state[$user_id]['has_unpunished_warning'])
				{
					continue;
				}

				$tier = tier_manager::pick_tier($tiers, $state[$user_id]['next_offense']);
				if (!$tier || $user['user_warnings'] < $tier['warning_threshold'])
				{
					continue;
				}

				if ($this->punishment_manager->handle_warning($user))
				{
					$punished++;
				}
			}
		}
		while (count($users) === self::BATCH_SIZE);

		$this->log->add('admin', ANONYMOUS, '', 'LOG_AUTOPUNISH_RETROACTIVE', false, [$punished]);
	}
}
