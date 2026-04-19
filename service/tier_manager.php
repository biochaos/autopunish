<?php
/**
 * AutoPunish extension for phpBB.
 *
 * @copyright (c) 2026, biochaos
 * @license GNU General Public License, version 2 (GPL-2.0)
 */

namespace biochaos\autopunish\service;

class tier_manager
{
	/** @var \phpbb\db\driver\driver_interface */
	private $db;

	/** @var string */
	private $tiers_table;

	public function __construct(\phpbb\db\driver\driver_interface $db, $tiers_table)
	{
		$this->db          = $db;
		$this->tiers_table = $tiers_table;
	}

	/**
	 * Returns all tiers ordered by tier_order ascending.
	 */
	public function get_all_tiers()
	{
		$tiers = [];

		$sql = 'SELECT * FROM ' . $this->tiers_table . ' ORDER BY tier_order ASC';
		$result = $this->db->sql_query($sql);
		while ($row = $this->db->sql_fetchrow($result))
		{
			$tiers[] = $row;
		}
		$this->db->sql_freeresult($result);

		return $tiers;
	}

	/**
	 * Returns the tier for a given offense number (1-based).
	 * If offense_number exceeds the number of tiers, the last tier is returned.
	 * Returns false if no tiers are configured.
	 */
	public function get_tier_for_offense($offense_number)
	{
		$tiers = $this->get_all_tiers();

		if (empty($tiers))
		{
			return false;
		}

		$index = min($offense_number, count($tiers)) - 1;
		return $tiers[$index];
	}

	/**
	 * Saves tiers from an ordered array of tier data.
	 * Replaces the entire tier list.
	 */
	public function save_tiers(array $tiers)
	{
		$this->db->sql_query('DELETE FROM ' . $this->tiers_table);

		foreach ($tiers as $order => $tier)
		{
			$sql_ary = [
				'tier_order'        => (int) $order + 1,
				'warning_threshold' => (int) $tier['warning_threshold'],
				'action'            => ($tier['action'] === 'deactivate') ? 'deactivate' : 'group',
				'group_id'          => (int) ($tier['group_id'] ?? 0),
				'duration_seconds'  => (int) ($tier['duration_seconds'] ?? 0),
				'reason_text'       => (string) ($tier['reason_text'] ?? ''),
				'notification_text' => (string) ($tier['notification_text'] ?? ''),
			];
			$this->db->sql_query('INSERT INTO ' . $this->tiers_table . ' ' . $this->db->sql_build_array('INSERT', $sql_ary));
		}
	}

}
