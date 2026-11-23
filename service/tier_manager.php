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
	/** What a tier does: adds the user to a group... */
	const ACTION_GROUP = 'group';

	/** ...or deactivates the account. These are the values stored in the tables. */
	const ACTION_DEACTIVATE = 'deactivate';

	/**
	 * The longest punishment a tier can have, 50 years in seconds. The column
	 * holds more, but a punishment's end time is the current time plus this and
	 * has to fit the 32-bit timestamp column too.
	 */
	const MAX_DURATION = 1576800000;

	/** The most characters a stored reason, or a text kept in the config table, can have. */
	const MAX_TEXT_LENGTH = 255;

	/**
	 * Built-in groups a punishment must not add users to: everyone is already
	 * registered, guests and bots are not people to punish, and administrators
	 * and global moderators would be a promotion. Newly registered users stays,
	 * as a plausible restricted group.
	 */
	const UNSUITABLE_GROUPS = ['GUESTS', 'REGISTERED', 'REGISTERED_COPPA', 'BOTS', 'GLOBAL_MODERATORS', 'ADMINISTRATORS'];

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
		return self::pick_tier($this->get_all_tiers(), $offense_number);
	}

	/**
	 * The tier for an offense number from an already loaded tier list, for
	 * callers that look up many offenses against the same tiers.
	 *
	 * @param array $tiers As returned by get_all_tiers()
	 * @return array|false
	 */
	public static function pick_tier(array $tiers, $offense_number)
	{
		if (empty($tiers))
		{
			return false;
		}

		$index = max(1, min((int) $offense_number, count($tiers))) - 1;
		return $tiers[$index];
	}

	/**
	 * Saves tiers from an ordered array of tier data.
	 * Replaces the entire tier list.
	 */
	public function save_tiers(array $tiers)
	{
		$rows = [];

		foreach ($tiers as $order => $tier)
		{
			$action = ($tier['action'] === self::ACTION_DEACTIVATE) ? self::ACTION_DEACTIVATE : self::ACTION_GROUP;

			// The tiers page hides the duration of a deactivate tier but still
			// submits it, so a tier switched from "add to group" would otherwise
			// keep its old duration. Deactivation is always permanent.
			$rows[] = [
				'tier_order'        => (int) $order + 1,
				'warning_threshold' => (int) $tier['warning_threshold'],
				'action'            => $action,
				'group_id'          => (int) ($tier['group_id'] ?? 0),
				'duration_seconds'  => ($action === self::ACTION_DEACTIVATE) ? 0 : min(self::MAX_DURATION, max(0, (int) ($tier['duration_seconds'] ?? 0))),
				'reason_text'       => self::encode_text($tier['reason_text'] ?? ''),
				'notification_text' => self::encode_text($tier['notification_text'] ?? ''),
			];
		}

		// One transaction, so a failed insert leaves the saved tiers as they were
		// instead of an empty table.
		$this->db->sql_transaction('begin');
		$this->db->sql_query('DELETE FROM ' . $this->tiers_table);
		$this->db->sql_multi_insert($this->tiers_table, $rows);
		$this->db->sql_transaction('commit');
	}

	/**
	 * Text for storage. phpBB's request class hands back HTML-escaped text; on
	 * top of that, characters outside the Basic Multilingual Plane, such as
	 * emoji, become numeric references, the way core stores titles, because
	 * MySQL's utf8 columns reject them.
	 */
	public static function encode_text($text)
	{
		return utf8_encode_ucr((string) $text);
	}

	/**
	 * How many characters the text takes once stored, which is what the length
	 * of its column limits: after the request class's escaping and the encoding
	 * of emoji, so "&" counts as five.
	 */
	public static function stored_length($text)
	{
		return utf8_strlen(self::encode_text($text));
	}

	/**
	 * The text as typed, for a template that escapes it itself: the reverse of
	 * encode_text() plus the request class's HTML escaping.
	 */
	public static function decode_text($text)
	{
		return htmlspecialchars_decode(utf8_decode_ncr((string) $text), ENT_COMPAT);
	}
}
