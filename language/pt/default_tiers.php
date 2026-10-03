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
	'AUTOPUNISH_DEFAULT_TIER_1_REASON' => '1.º infração — restrição de 7 dias',
	'AUTOPUNISH_DEFAULT_TIER_1_NOTIFY' => 'Recebeu a sua primeira punição ({DURATION}). Motivo: {REASON}.',

	'AUTOPUNISH_DEFAULT_TIER_2_REASON' => '2.º infração — restrição de 30 dias',
	'AUTOPUNISH_DEFAULT_TIER_2_NOTIFY' => 'Recebeu a sua segunda punição ({DURATION}). Motivo: {REASON}.',

	'AUTOPUNISH_DEFAULT_TIER_3_REASON' => '3.º infração — restrição de 180 dias',
	'AUTOPUNISH_DEFAULT_TIER_3_NOTIFY' => 'Recebeu a sua terceira punição ({DURATION}). Motivo: {REASON}.',

	'AUTOPUNISH_DEFAULT_TIER_4_REASON' => '4.º infração — desativação da conta',
	'AUTOPUNISH_DEFAULT_TIER_4_NOTIFY' => 'A sua conta foi desativada devido a infrações repetidas.',
]);
