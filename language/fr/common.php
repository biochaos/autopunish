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

$lang = array_merge($lang, [
	// ACP module titles
	'ACP_AUTOPUNISH'          => 'AutoPunish',
	'ACP_AUTOPUNISH_SETTINGS' => 'Paramètres',
	'ACP_AUTOPUNISH_TIERS'    => 'Niveaux de sanction',
	'ACP_AUTOPUNISH_USER'     => 'Gestion des utilisateurs',
	// Label shown in the Manage Users mode dropdown
	'ACP_USER_AUTOPUNISH'     => 'AutoPunish',

	// Settings page
	'AUTOPUNISH_GENERAL'                => 'Paramètres généraux',
	'AUTOPUNISH_ENABLED'                => 'Activer AutoPunish',
	'AUTOPUNISH_WARN_DURING_PUNISHMENT' => 'Avertissement pendant une sanction active',
	'AUTOPUNISH_WDP_RESTART'            => 'Réinitialiser le minuteur',
	'AUTOPUNISH_WDP_NORMAL'             => 'Normal (appliquer les règles de seuil)',
	'AUTOPUNISH_WDP_ESCALATE'           => 'Escalader immédiatement',
	'AUTOPUNISH_CRON_INTERVAL'          => 'Intervalle de vérification d\'expiration',
	'AUTOPUNISH_MINUTES'                => 'minutes',
	'AUTOPUNISH_EXEMPT_GROUPS'          => 'Groupes exemptés',
	'AUTOPUNISH_DRY_RUN'                => 'Mode simulation',
	'AUTOPUNISH_DRY_RUN_EXPLAIN'        => 'Lorsqu\'activé, les sanctions sont journalisées mais non appliquées. Utilisez ceci pour vérifier les paramètres de seuil avant la mise en production.',
	'AUTOPUNISH_RETROACTIVE'            => 'Lancer une analyse rétroactive',
	'AUTOPUNISH_RETROACTIVE_EXPLAIN'    => 'Lorsque coché et sauvegardé, une analyse unique sera effectuée pour sanctionner les utilisateurs qui atteignent déjà le seuil. Cette case se réinitialise automatiquement après l\'analyse.',
	'ACP_AUTOPUNISH_SETTINGS_SAVED'     => 'Paramètres sauvegardés.',
	'AUTOPUNISH_REQUIRES_PHPBB_330'     => 'AutoPunish nécessite phpBB 3.3.0 ou supérieur.',

	// Notifications section
	'AUTOPUNISH_NOTIFICATIONS'          => 'Notifications',
	'AUTOPUNISH_NOTIFY_FORUM'           => 'Activer les notifications du forum (icône cloche)',
	'AUTOPUNISH_NOTIFY_PM'              => 'Activer les notifications par message privé',
	'AUTOPUNISH_NOTIFY_EMAIL'           => 'Activer les notifications par e-mail',
	'AUTOPUNISH_NOTIFY_SENDER'          => 'Nom d\'utilisateur de l\'expéditeur MP/e-mail',
	'AUTOPUNISH_NOTIFY_SENDER_EXPLAIN'  => 'Requis lorsque les notifications par MP ou e-mail sont activées.',
	'AUTOPUNISH_SENDER_REQUIRED'        => 'Un nom d\'utilisateur expéditeur est requis lorsque les notifications par MP ou e-mail sont activées.',
	'AUTOPUNISH_SENDER_MISSING_WARNING' => 'Attention : le compte expéditeur configuré n\'existe plus. Les notifications par MP et e-mail ne sont actuellement pas envoyées.',
	'AUTOPUNISH_NOTIFY_SUBJECT'         => 'Objet du MP/e-mail',
	'AUTOPUNISH_NOTIFY_SUBJECT_EXPLAIN' => 'Laisser vide pour utiliser le texte de notification du niveau comme objet.',
	'AUTOPUNISH_NOTIFY_FOOTER'                  => 'Texte de pied de page MP/e-mail',
	'AUTOPUNISH_NOTIFY_EXPIRY_SUBJECT'          => 'Objet du MP/e-mail d\'expiration',
	'AUTOPUNISH_NOTIFY_EXPIRY_SUBJECT_EXPLAIN'  => 'Laisser vide pour utiliser le titre de la notification d\'expiration comme objet.',
	'AUTOPUNISH_NOTIFY_EXPIRY_TEXT'             => 'Texte de notification d\'expiration',
	'AUTOPUNISH_NOTIFY_COMMUTE_SUBJECT'         => 'Objet du MP/e-mail de fin anticipée',
	'AUTOPUNISH_NOTIFY_COMMUTE_SUBJECT_EXPLAIN' => 'Laisser vide pour utiliser le titre de la notification de fin anticipée comme objet.',
	'AUTOPUNISH_NOTIFY_COMMUTE_TEXT'            => 'Texte de notification de remise de peine',
	'AUTOPUNISH_PLACEHOLDERS_EXPLAIN'   => 'Variables disponibles : {USERNAME}, {DURATION}, {REASON}, {OFFENSE_NUMBER}. Le BBCode est pris en charge dans les textes MP/e-mail.',

	// Tiers page
	'AUTOPUNISH_TIERS_EXPLAIN'          => 'Chaque ligne est un niveau de sanction. Position de la ligne = numéro d\'infraction. Le dernier niveau est utilisé pour toutes les infractions au-delà du nombre configuré.',
	'AUTOPUNISH_WARNING_THRESHOLD'      => 'Seuil d\'avertissements (≥)',
	'AUTOPUNISH_ACTION'                 => 'Action',
	'AUTOPUNISH_ACTION_GROUP'           => 'Ajouter au groupe',
	'AUTOPUNISH_ACTION_DEACTIVATE'      => 'Désactiver le compte',
	'AUTOPUNISH_GROUP'                  => 'Groupe',
	'AUTOPUNISH_DURATION'               => 'Durée',
	'AUTOPUNISH_DURATION_ZERO'          => '0 = permanent',
	'AUTOPUNISH_DAYS'                   => 'Jours',
	'AUTOPUNISH_WEEKS'                  => 'Semaines',
	'AUTOPUNISH_MONTHS'                 => 'Mois',
	'AUTOPUNISH_REASON_TEXT'            => 'Raison (visible par les admins)',
	'AUTOPUNISH_NOTIFICATION_TEXT'      => 'Texte de notification',
	'AUTOPUNISH_REMOVE'                 => 'Supprimer',
	'AUTOPUNISH_REMOVE_CONFIRM'         => 'Supprimer ce niveau ?',
	'AUTOPUNISH_ADD_TIER'               => 'Ajouter un niveau',
	'ACP_AUTOPUNISH_TIERS_SAVED'        => 'Niveaux sauvegardés.',

	// User management page
	'AUTOPUNISH_SELECT_USER'            => 'Sélectionner un utilisateur',
	'AUTOPUNISH_STATUS_SUMMARY'         => 'Statut AutoPunish',
	'AUTOPUNISH_CURRENT_WARNINGS'       => 'Avertissements actifs',
	'AUTOPUNISH_TOTAL_PUNISHMENTS'      => 'Total des sanctions',
	'AUTOPUNISH_COMMUTED_OFFENSES'      => 'Infractions remises jusqu\'à présent',
	'AUTOPUNISH_EFFECTIVE_OFFENSES'     => 'Nombre d\'infractions effectif',
	'AUTOPUNISH_NEXT_OFFENSE'           => 'La prochaine infraction serait',
	'AUTOPUNISH_CURRENT_PUNISHMENT'     => 'Sanction active actuelle',
	'AUTOPUNISH_THRESHOLD'              => 'se déclenche à',
	'AUTOPUNISH_REPEATING'              => 'répétition du dernier niveau',
	'AUTOPUNISH_NO_TIERS'               => 'Aucun niveau configuré',
	'AUTOPUNISH_OFFENSE'                => 'Infraction',
	'AUTOPUNISH_EXPIRES'                => 'Expire',
	'AUTOPUNISH_PERMANENT'              => 'Permanent',
	'AUTOPUNISH_END_EARLY'              => 'Terminer la sanction anticipément',
	'AUTOPUNISH_END_PUNISHMENT'         => 'Terminer la sanction maintenant',
	'AUTOPUNISH_END_EARLY_CONFIRM'      => 'Ceci supprimera la sanction immédiatement. L\'infraction sera toujours comptabilisée dans le dossier. Continuer ?',
	'AUTOPUNISH_STEP_DOWN'              => 'Appliquer également le niveau précédent comme nouvelle sanction',
	'AUTOPUNISH_COMMUTE_OFFENSES'       => 'Remise d\'infractions',
	'AUTOPUNISH_COMMUTE_EXPLAIN'        => 'Entrez le nombre d\'infractions à retirer du compteur effectif actuel. La prochaine sanction utilisera un niveau correspondamment inférieur. Cela n\'affecte pas la sanction active actuelle.',
	'AUTOPUNISH_COMMUTE_SET'            => 'Réduire le compteur effectif de',
	'AUTOPUNISH_PUNISHMENT_HISTORY'     => 'Historique des sanctions',
	'AUTOPUNISH_NO_HISTORY'             => 'Aucun historique de sanctions.',
	'AUTOPUNISH_STARTED'                => 'Commencé',
	'AUTOPUNISH_STATUS'                 => 'Statut',
	'AUTOPUNISH_ACTIVE'                 => 'Actif',
	'AUTOPUNISH_EXPIRED_STATUS'         => 'Expiré',
	'ACP_AUTOPUNISH_PUNISHMENT_ENDED'   => 'Sanction terminée.',
	'ACP_AUTOPUNISH_COMMUTATION_SAVED'  => 'Remise de peine sauvegardée.',

	// Duration strings (used in {DURATION} placeholder and admin log)
	'AUTOPUNISH_LOG_DURATION_UNTIL'  => '%1$s, jusqu\'au %2$s',
	'AUTOPUNISH_DURATION_PERMANENT'  => 'permanent',
	'AUTOPUNISH_DURATION_DAYS'       => [1 => '1 jour',    2 => '%d jours'],
	'AUTOPUNISH_DURATION_WEEKS'      => [1 => '1 semaine', 2 => '%d semaines'],
	'AUTOPUNISH_DURATION_MONTHS'     => [1 => '1 mois',    2 => '%d mois'],

	// Notification titles and default body text
	'NOTIFICATION_TYPE_AUTOPUNISH_APPLIED'  => 'AutoPunish : Sanction appliquée',
	'NOTIFICATION_TYPE_AUTOPUNISH_EXPIRED'  => 'AutoPunish : Sanction expirée',
	'NOTIFICATION_TYPE_AUTOPUNISH_COMMUTED' => 'AutoPunish : Sanction levée',

	'AUTOPUNISH_NOTIFY_APPLIED_TITLE'         => 'Vous avez reçu une sanction.',
	'AUTOPUNISH_NOTIFY_EXPIRED_TITLE'         => 'Votre sanction a expiré.',
	'AUTOPUNISH_NOTIFY_COMMUTED_TITLE'        => 'Votre sanction a été levée anticipément.',
	'AUTOPUNISH_NOTIFY_APPLIED_DEFAULT_BODY'  => 'Vous avez reçu la sanction #{OFFENSE_NUMBER} (durée : {DURATION}).',
	'AUTOPUNISH_NOTIFY_EXPIRED_DEFAULT_BODY'  => 'Votre sanction #{OFFENSE_NUMBER} a expiré.',
	'AUTOPUNISH_NOTIFY_COMMUTED_DEFAULT_BODY' => 'Votre sanction #{OFFENSE_NUMBER} a été levée anticipément.',

	// Admin log messages
	'LOG_AUTOPUNISH_APPLIED'        => '<strong>AutoPunish :</strong> utilisateur &quot;%1$s&quot; sanctionné — infraction n°%2$d, action : %3$s, groupe : %4$s, durée : %5$s, raison : %6$s',
	'LOG_AUTOPUNISH_EXPIRED'        => '<strong>AutoPunish :</strong> sanction de l\'utilisateur &quot;%1$s&quot; expirée — infraction n°%2$d',
	'LOG_AUTOPUNISH_ENDED_EARLY'    => '<strong>AutoPunish :</strong> sanction de l\'utilisateur &quot;%1$s&quot; terminée anticipément — infraction n°%2$d',
	'LOG_AUTOPUNISH_COMMUTED'       => '<strong>AutoPunish :</strong> compteur d\'infractions de l\'utilisateur &quot;%1$s&quot; réduit — %2$d infraction(s) remise(s) (total remis : %3$d, effectif : %4$d)',
	'LOG_AUTOPUNISH_RESTARTED'      => '<strong>AutoPunish :</strong> minuteur de sanction de l\'utilisateur &quot;%1$s&quot; réinitialisé — infraction n°%2$d',
	'LOG_AUTOPUNISH_ESCALATED'      => '<strong>AutoPunish :</strong> sanction de l\'utilisateur &quot;%1$s&quot; escaladée — infraction n°%2$d → n°%3$d',
	'LOG_AUTOPUNISH_DRY_RUN'        => '<strong>AutoPunish [SIMULATION] :</strong> l\'utilisateur &quot;%1$s&quot; aurait été sanctionné — infraction n°%2$d, action : %3$s',
	'LOG_AUTOPUNISH_RETROACTIVE'    => '<strong>AutoPunish :</strong> analyse rétroactive terminée — %1$d utilisateurs sanctionnés',
	'LOG_AUTOPUNISH_SENDER_MISSING' => '<strong>AutoPunish :</strong> notification MP/e-mail ignorée — l\'expéditeur (ID %1$d) n\'existe plus. Mettez à jour l\'expéditeur dans les paramètres AutoPunish.',
]);
