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
	'AUTOPUNISH_DEFAULT_TIER_1_REASON' => 'הפרה ראשונה — הגבלה ל-7 ימים',
	'AUTOPUNISH_DEFAULT_TIER_2_REASON' => 'הפרה שנייה — הגבלה ל-30 ימים',
	'AUTOPUNISH_DEFAULT_TIER_3_REASON' => 'הפרה שלישית — הגבלה ל-180 ימים',
	'AUTOPUNISH_DEFAULT_TIER_4_REASON' => 'הפרה רביעית — השבתת החשבון',
]);
