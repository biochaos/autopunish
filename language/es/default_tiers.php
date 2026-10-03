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

// The punishment tiers installed by the extension's migrations. They are
// user-editable in the ACP afterwards -- these are only the starting values,
// and they install in the administrator's language where one is available.
$lang = array_merge($lang, [
	'AUTOPUNISH_DEFAULT_TIER_1_REASON' => '1.ª infracción — restricción de 7 días',
	'AUTOPUNISH_DEFAULT_TIER_1_NOTIFY' => 'Ha recibido su primera sanción ({DURATION}). Motivo: {REASON}.',

	'AUTOPUNISH_DEFAULT_TIER_2_REASON' => '2.ª infracción — restricción de 30 días',
	'AUTOPUNISH_DEFAULT_TIER_2_NOTIFY' => 'Ha recibido su segunda sanción ({DURATION}). Motivo: {REASON}.',

	'AUTOPUNISH_DEFAULT_TIER_3_REASON' => '3.ª infracción — restricción de 180 días',
	'AUTOPUNISH_DEFAULT_TIER_3_NOTIFY' => 'Ha recibido su tercera sanción ({DURATION}). Motivo: {REASON}.',

	'AUTOPUNISH_DEFAULT_TIER_4_REASON' => '4.ª infracción — desactivación de la cuenta',
	'AUTOPUNISH_DEFAULT_TIER_4_NOTIFY' => 'Su cuenta ha sido desactivada por infracciones reiteradas.',
]);
