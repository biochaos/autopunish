<?php
/**
 * AutoPunish extension for phpBB.
 *
 * @copyright (c) 2026, biochaos
 * @license GNU General Public License, version 2 (GPL-2.0)
 */

if (!defined('IN_PHPBB'))
{
	exit;
}

// Reasons for the punishment tiers installed by the extension's migrations. They are
// user-editable in the ACP afterwards -- these are only the starting values,
// and they install in the administrator's language where one is available.
$lang = array_merge($lang, [
	'AUTOPUNISH_DEFAULT_TIER_1_REASON' => '1st offense — 7 day restriction',
	'AUTOPUNISH_DEFAULT_TIER_2_REASON' => '2nd offense — 30 day restriction',
	'AUTOPUNISH_DEFAULT_TIER_3_REASON' => '3rd offense — 180 day restriction',
	'AUTOPUNISH_DEFAULT_TIER_4_REASON' => '4th offense — account deactivation',
]);
