<?php
/**
 * AutoPunish extension for phpBB.
 *
 * @copyright (c) 2026, biochaos
 * @license GNU General Public License, version 2 (GPL-2.0)
 */

namespace biochaos\autopunish\service;

/**
 * Chooses the wording for a punishment length, so that the admin log, PMs and
 * emails, and bell notifications all describe it the same way, each in its
 * own reader's language.
 */
class duration
{
	/**
	 * The language key and count describing a length: whole months (of 30
	 * days) where it divides evenly, else whole weeks, else days. Zero or less
	 * is permanent.
	 *
	 * @param int $seconds
	 * @return array [language key, count]
	 */
	public static function lang_args($seconds)
	{
		if ($seconds <= 0)
		{
			return ['AUTOPUNISH_DURATION_PERMANENT', 0];
		}

		$days = (int) round($seconds / 86400);
		if ($days % 30 === 0)
		{
			return ['AUTOPUNISH_DURATION_MONTHS', intdiv($days, 30)];
		}
		if ($days % 7 === 0)
		{
			return ['AUTOPUNISH_DURATION_WEEKS', intdiv($days, 7)];
		}

		return ['AUTOPUNISH_DURATION_DAYS', $days];
	}
}
