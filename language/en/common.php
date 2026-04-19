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
	'ACP_AUTOPUNISH_SETTINGS' => 'Settings',
	'ACP_AUTOPUNISH_TIERS'    => 'Punishment Tiers',
	'ACP_AUTOPUNISH_USER'     => 'User Management',
	// Label shown in the Manage Users mode dropdown
	'ACP_USER_AUTOPUNISH'     => 'AutoPunish',

	// Settings page
	'AUTOPUNISH_GENERAL'                => 'General Settings',
	'AUTOPUNISH_ENABLED'                => 'Enable AutoPunish',
	'AUTOPUNISH_WARN_DURING_PUNISHMENT' => 'Warning during active punishment',
	'AUTOPUNISH_WDP_RESTART'            => 'Restart timer',
	'AUTOPUNISH_WDP_NORMAL'             => 'Normal (apply threshold rules)',
	'AUTOPUNISH_WDP_ESCALATE'           => 'Escalate immediately',
	'AUTOPUNISH_CRON_INTERVAL'          => 'Expiry check interval',
	'AUTOPUNISH_MINUTES'                => 'minutes',
	'AUTOPUNISH_EXEMPT_GROUPS'          => 'Exempt groups',
	'AUTOPUNISH_DRY_RUN'                => 'Dry run mode',
	'AUTOPUNISH_DRY_RUN_EXPLAIN'        => 'When enabled, punishments are logged but not applied. Use this to verify threshold settings before going live.',
	'AUTOPUNISH_RETROACTIVE'            => 'Run retroactive scan',
	'AUTOPUNISH_RETROACTIVE_EXPLAIN'    => 'When checked and saved, a one-time scan will run to punish users who already meet the threshold. This checkbox resets automatically after the scan.',
	'ACP_AUTOPUNISH_SETTINGS_SAVED'     => 'Settings saved.',
	'AUTOPUNISH_REQUIRES_PHPBB_330'     => 'AutoPunish requires phpBB 3.3.0 or later.',

	// Notifications section
	'AUTOPUNISH_NOTIFICATIONS'          => 'Notifications',
	'AUTOPUNISH_NOTIFY_FORUM'           => 'Enable forum notifications (bell icon)',
	'AUTOPUNISH_NOTIFY_PM'              => 'Enable private message notifications',
	'AUTOPUNISH_NOTIFY_EMAIL'           => 'Enable email notifications',
	'AUTOPUNISH_NOTIFY_SENDER'          => 'Username of PM/Email sender',
	'AUTOPUNISH_NOTIFY_SENDER_EXPLAIN'    => 'Required when PM or email notifications are enabled.',
	'AUTOPUNISH_SENDER_REQUIRED'          => 'A sender username is required when PM or email notifications are enabled.',
	'AUTOPUNISH_SENDER_MISSING_WARNING'   => 'Warning: the configured sender account no longer exists. PM and email notifications are currently not being sent.',
	'AUTOPUNISH_NOTIFY_SUBJECT'         => 'PM/Email subject line',
	'AUTOPUNISH_NOTIFY_SUBJECT_EXPLAIN' => 'Leave blank to use the tier\'s notification text as the subject.',
	'AUTOPUNISH_NOTIFY_FOOTER'                  => 'PM/Email footer text',
	'AUTOPUNISH_NOTIFY_EXPIRY_SUBJECT'          => 'Expiry PM/Email subject line',
	'AUTOPUNISH_NOTIFY_EXPIRY_SUBJECT_EXPLAIN'  => 'Leave blank to use the expiry notification title as the subject.',
	'AUTOPUNISH_NOTIFY_EXPIRY_TEXT'             => 'Expiry notification text',
	'AUTOPUNISH_NOTIFY_COMMUTE_SUBJECT'         => 'Early end PM/Email subject line',
	'AUTOPUNISH_NOTIFY_COMMUTE_SUBJECT_EXPLAIN' => 'Leave blank to use the early end notification title as the subject.',
	'AUTOPUNISH_NOTIFY_COMMUTE_TEXT'            => 'Commutation notification text',
	'AUTOPUNISH_PLACEHOLDERS_EXPLAIN'   => 'Supported placeholders: {USERNAME}, {DURATION}, {REASON}, {OFFENSE_NUMBER}. BBCode is supported in PM/email texts.',

	// Tiers page
	'AUTOPUNISH_TIERS_EXPLAIN'          => 'Each row is a punishment tier. Row position = offense number. The last tier is used for all offenses beyond the configured count.',
	'AUTOPUNISH_WARNING_THRESHOLD'      => 'Warning threshold (≥)',
	'AUTOPUNISH_ACTION'                 => 'Action',
	'AUTOPUNISH_ACTION_GROUP'           => 'Add to group',
	'AUTOPUNISH_ACTION_DEACTIVATE'      => 'Deactivate account',
	'AUTOPUNISH_GROUP'                  => 'Group',
	'AUTOPUNISH_DURATION'               => 'Duration',
	'AUTOPUNISH_DURATION_ZERO'          => '0 = permanent',
	'AUTOPUNISH_DAYS'                   => 'Days',
	'AUTOPUNISH_WEEKS'                  => 'Weeks',
	'AUTOPUNISH_MONTHS'                 => 'Months',
	'AUTOPUNISH_REASON_TEXT'            => 'Reason (admin-facing)',
	'AUTOPUNISH_NOTIFICATION_TEXT'      => 'Notification text',
	'AUTOPUNISH_REMOVE'                 => 'Remove',
	'AUTOPUNISH_REMOVE_CONFIRM'         => 'Remove this tier?',
	'AUTOPUNISH_ADD_TIER'               => 'Add tier',
	'ACP_AUTOPUNISH_TIERS_SAVED'        => 'Tiers saved.',

	// User management page
	'AUTOPUNISH_SELECT_USER'            => 'Select user',
	'AUTOPUNISH_STATUS_SUMMARY'         => 'AutoPunish Status',
	'AUTOPUNISH_CURRENT_WARNINGS'       => 'Active warnings',
	'AUTOPUNISH_TOTAL_PUNISHMENTS'      => 'Total punishments',
	'AUTOPUNISH_COMMUTED_OFFENSES'      => 'Offenses commuted so far',
	'AUTOPUNISH_EFFECTIVE_OFFENSES'     => 'Effective offense count',
	'AUTOPUNISH_NEXT_OFFENSE'           => 'Next offense would be',
	'AUTOPUNISH_CURRENT_PUNISHMENT'     => 'Current active punishment',
	'AUTOPUNISH_THRESHOLD'              => 'triggers at',
	'AUTOPUNISH_REPEATING'              => 'repeating last tier',
	'AUTOPUNISH_NO_TIERS'               => 'No tiers configured',
	'AUTOPUNISH_OFFENSE'                => 'Offense',
	'AUTOPUNISH_EXPIRES'                => 'Expires',
	'AUTOPUNISH_PERMANENT'              => 'Permanent',
	'AUTOPUNISH_END_EARLY'              => 'End Punishment Early',
	'AUTOPUNISH_END_PUNISHMENT'         => 'End punishment now',
	'AUTOPUNISH_END_EARLY_CONFIRM'      => 'This will remove the punishment immediately. The offense will still count toward the record. Continue?',
	'AUTOPUNISH_STEP_DOWN'              => 'Also apply the previous tier as a new punishment',
	'AUTOPUNISH_COMMUTE_OFFENSES'       => 'Commute Offense Count',
	'AUTOPUNISH_COMMUTE_EXPLAIN'        => 'Enter the number of offenses to remove from the current effective count. The next punishment will use a correspondingly lower tier. This does not affect the current active punishment.',
	'AUTOPUNISH_COMMUTE_SET'            => 'Reduce effective count by',
	'AUTOPUNISH_PUNISHMENT_HISTORY'     => 'Punishment History',
	'AUTOPUNISH_NO_HISTORY'             => 'No punishment history.',
	'AUTOPUNISH_STARTED'                => 'Started',
	'AUTOPUNISH_STATUS'                 => 'Status',
	'AUTOPUNISH_ACTIVE'                 => 'Active',
	'AUTOPUNISH_EXPIRED_STATUS'         => 'Expired',
	'ACP_AUTOPUNISH_PUNISHMENT_ENDED'   => 'Punishment ended.',
	'ACP_AUTOPUNISH_COMMUTATION_SAVED'  => 'Commutation saved.',

	// Duration strings (used in {DURATION} placeholder and admin log)
	'AUTOPUNISH_LOG_DURATION_UNTIL'  => '%1$s, until %2$s',
	'AUTOPUNISH_DURATION_PERMANENT' => 'permanent',
	'AUTOPUNISH_DURATION_DAYS'      => [1 => '1 day',   2 => '%d days'],
	'AUTOPUNISH_DURATION_WEEKS'     => [1 => '1 week',  2 => '%d weeks'],
	'AUTOPUNISH_DURATION_MONTHS'    => [1 => '1 month', 2 => '%d months'],

	// UCP notification preference labels
	'NOTIFICATION_TYPE_AUTOPUNISH_APPLIED'  => 'AutoPunish: Punishment applied',
	'NOTIFICATION_TYPE_AUTOPUNISH_EXPIRED'  => 'AutoPunish: Punishment expired',
	'NOTIFICATION_TYPE_AUTOPUNISH_COMMUTED' => 'AutoPunish: Punishment ended early',

	// Notification titles and default body text (used when no custom text is configured)
	'AUTOPUNISH_NOTIFY_APPLIED_TITLE'        => 'You have received a punishment.',
	'AUTOPUNISH_NOTIFY_EXPIRED_TITLE'        => 'Your punishment has expired.',
	'AUTOPUNISH_NOTIFY_COMMUTED_TITLE'       => 'Your punishment has been ended early.',
	'AUTOPUNISH_NOTIFY_APPLIED_DEFAULT_BODY' => 'You have received punishment #{OFFENSE_NUMBER} (duration: {DURATION}).',
	'AUTOPUNISH_NOTIFY_EXPIRED_DEFAULT_BODY' => 'Your punishment #{OFFENSE_NUMBER} has expired.',
	'AUTOPUNISH_NOTIFY_COMMUTED_DEFAULT_BODY'=> 'Your punishment #{OFFENSE_NUMBER} has been ended early.',

	// Admin log messages
	'LOG_AUTOPUNISH_APPLIED'      => '<strong>AutoPunish:</strong> user &quot;%1$s&quot; punished — offense #%2$d, action: %3$s, group: %4$s, duration: %5$s, reason: %6$s',
	'LOG_AUTOPUNISH_EXPIRED'      => '<strong>AutoPunish:</strong> user &quot;%1$s&quot; punishment expired — offense #%2$d',
	'LOG_AUTOPUNISH_ENDED_EARLY'  => '<strong>AutoPunish:</strong> user &quot;%1$s&quot; punishment ended early — offense #%2$d',
	'LOG_AUTOPUNISH_COMMUTED'     => '<strong>AutoPunish:</strong> user &quot;%1$s&quot; offense count reduced — commuted %2$d offense(s) (total commuted: %3$d, effective: %4$d)',
	'LOG_AUTOPUNISH_RESTARTED'    => '<strong>AutoPunish:</strong> user &quot;%1$s&quot; punishment timer restarted — offense #%2$d',
	'LOG_AUTOPUNISH_ESCALATED'    => '<strong>AutoPunish:</strong> user &quot;%1$s&quot; punishment escalated — offense #%2$d → #%3$d',
	'LOG_AUTOPUNISH_DRY_RUN'      => '<strong>AutoPunish [DRY RUN]:</strong> user &quot;%1$s&quot; would be punished — offense #%2$d, action: %3$s',
	'LOG_AUTOPUNISH_RETROACTIVE'    => '<strong>AutoPunish:</strong> Retroactive scan completed — %1$d users punished',
	'LOG_AUTOPUNISH_SENDER_MISSING' => '<strong>AutoPunish:</strong> PM/email notification skipped — sender user ID %1$d no longer exists. Update the sender in AutoPunish settings.',
]);
