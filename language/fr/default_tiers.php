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
	'AUTOPUNISH_DEFAULT_TIER_1_REASON' => '1re infraction — restriction de 7 jours',
	'AUTOPUNISH_DEFAULT_TIER_1_NOTIFY' => 'Vous avez reçu votre première sanction ({DURATION}). Raison : {REASON}.',

	'AUTOPUNISH_DEFAULT_TIER_2_REASON' => '2e infraction — restriction de 30 jours',
	'AUTOPUNISH_DEFAULT_TIER_2_NOTIFY' => 'Vous avez reçu votre deuxième sanction ({DURATION}). Raison : {REASON}.',

	'AUTOPUNISH_DEFAULT_TIER_3_REASON' => '3e infraction — restriction de 180 jours',
	'AUTOPUNISH_DEFAULT_TIER_3_NOTIFY' => 'Vous avez reçu votre troisième sanction ({DURATION}). Raison : {REASON}.',

	'AUTOPUNISH_DEFAULT_TIER_4_REASON' => '4e infraction — désactivation du compte',
	'AUTOPUNISH_DEFAULT_TIER_4_NOTIFY' => 'Votre compte a été désactivé en raison d’infractions répétées.',
]);
