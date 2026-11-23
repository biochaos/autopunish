<?php
/**
 * AutoPunish extension for phpBB.
 *
 * @copyright (c) 2026, biochaos
 * @license GNU General Public License, version 2 (GPL-2.0)
 */

namespace biochaos\autopunish\migrations;

/**
 * Moves the display text of the default tiers out of install_default_tiers,
 * which seeds it in English exactly as 1.0.0 shipped it.
 *
 * A default tier that still holds its original English notification text gets
 * an empty one, so each recipient receives the built-in text in their own
 * language. One that still holds its original reason gets the reason in the
 * administrator's language. A tier whose text was edited by hand is left alone.
 *
 * On a new board this runs straight after install_default_tiers, so the four
 * default tiers end up with localized reasons and empty notification texts.
 */
class localize_default_tiers extends \phpbb\db\migration\container_aware_migration
{
	/** The texts install_default_tiers seeds: language key => [reason, notification text]. */
	const ORIGINAL_TEXTS = [
		'AUTOPUNISH_DEFAULT_TIER_1_REASON' => [
			'1st offense — 7 day restriction',
			'You have received your first punishment ({DURATION}). Reason: {REASON}.',
		],
		'AUTOPUNISH_DEFAULT_TIER_2_REASON' => [
			'2nd offense — 30 day restriction',
			'You have received your second punishment ({DURATION}). Reason: {REASON}.',
		],
		'AUTOPUNISH_DEFAULT_TIER_3_REASON' => [
			'3rd offense — 180 day restriction',
			'You have received your third punishment ({DURATION}). Reason: {REASON}.',
		],
		'AUTOPUNISH_DEFAULT_TIER_4_REASON' => [
			'4th offense — account deactivation',
			'Your account has been deactivated due to repeated violations.',
		],
	];

	public static function depends_on()
	{
		return ['\biochaos\autopunish\migrations\add_applied_change'];
	}

	public function update_data()
	{
		return [
			['custom', [[$this, 'localize_tiers']]],
		];
	}

	public function localize_tiers()
	{
		$language = $this->container->get('language');
		$language->add_lang('default_tiers', 'biochaos/autopunish');

		$table = $this->table_prefix . 'autopunish_tiers';

		foreach (self::ORIGINAL_TEXTS as $lang_key => $original)
		{
			$this->db->sql_query('UPDATE ' . $table . "
				SET notification_text = ''
				WHERE notification_text = '" . $this->db->sql_escape($original[1]) . "'");

			// Stored the way the Tiers page stores a reason: HTML-escaped, with
			// characters outside the Basic Multilingual Plane as numeric references.
			$reason = utf8_encode_ucr(utf8_htmlspecialchars($language->lang($lang_key)));

			$this->db->sql_query('UPDATE ' . $table . "
				SET reason_text = '" . $this->db->sql_escape($reason) . "'
				WHERE reason_text = '" . $this->db->sql_escape($original[0]) . "'");
		}
	}
}
