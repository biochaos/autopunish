<?php
/**
 * AutoPunish extension for phpBB.
 *
 * @copyright (c) 2026, biochaos
 * @license GNU General Public License, version 2 (GPL-2.0)
 */

namespace biochaos\autopunish\migrations;

/**
 * Clears the duration left behind on deactivate tiers.
 *
 * The tiers page hides the duration of a deactivate tier but used to save it
 * anyway, so a tier switched from "add to group" kept its old duration. Its
 * punishments were then given an end time, and on expiry they lapsed while
 * leaving the account deactivated — with no active punishment left to end
 * early, the only way to reactivate the account was by hand.
 */
class make_deactivation_permanent extends \phpbb\db\migration\migration
{
	public static function depends_on()
	{
		return ['\biochaos\autopunish\migrations\add_trigger_warning_id'];
	}

	public function update_data()
	{
		return [
			['custom', [[$this, 'clear_deactivate_durations']]],
		];
	}

	public function clear_deactivate_durations()
	{
		$this->db->sql_query('UPDATE ' . $this->table_prefix . "autopunish_tiers
			SET duration_seconds = 0
			WHERE action = 'deactivate'");

		// Only active punishments: one that has already lapsed has no account
		// state left to repair, and its end time is history.
		$this->db->sql_query('UPDATE ' . $this->table_prefix . "autopunish_punishments
			SET end_time = 0
			WHERE action = 'deactivate'
				AND active = 1");
	}
}
