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
	'ACP_AUTOPUNISH'          => 'ענישה אוטומטית',
	'ACP_AUTOPUNISH_SETTINGS' => 'הגדרות',
	'ACP_AUTOPUNISH_TIERS'    => 'רמות ענישה',
	'ACP_AUTOPUNISH_USER'     => 'ניהול משתמשים',
	// Label shown in the Manage Users mode dropdown
	'ACP_USER_AUTOPUNISH'     => 'ענישה אוטומטית',

	// Settings page
	'AUTOPUNISH_GENERAL'                => 'הגדרות כלליות',
	'AUTOPUNISH_ENABLED'                => 'הפעל ענישה אוטומטית',
	'AUTOPUNISH_WARN_DURING_PUNISHMENT' => 'אזהרה במהלך עונש פעיל',
	'AUTOPUNISH_WDP_RESTART'            => 'אפס טיימר',
	'AUTOPUNISH_WDP_NORMAL'             => 'רגיל (החל חוקי סף)',
	'AUTOPUNISH_WDP_ESCALATE'           => 'הסלמה מיידית',
	'AUTOPUNISH_CRON_INTERVAL'          => 'מרווח בדיקת פקיעת עונשים',
	'AUTOPUNISH_MINUTES'                => 'דקות',
	'AUTOPUNISH_EXEMPT_GROUPS'          => 'קבוצות פטורות',
	'AUTOPUNISH_DRY_RUN'                => 'מצב ניסוי (Dry run)',
	'AUTOPUNISH_DRY_RUN_EXPLAIN'        => 'כאשר מופעל, עונשים נרשמים ביומן אך אינם מוחלים. השתמש בזה לבדיקת הגדרות הסף לפני הפעלה מלאה.',
	'AUTOPUNISH_RETROACTIVE'            => 'הרץ סריקה רטרואקטיבית',
	'AUTOPUNISH_RETROACTIVE_EXPLAIN'    => 'כאשר מסומן ונשמר, תתבצע סריקה חד-פעמית שתעניש משתמשים שכבר עומדים בתנאי הסף. תיבת הסימון מתאפסת אוטומטית לאחר הסריקה.',
	'ACP_AUTOPUNISH_SETTINGS_SAVED'     => 'ההגדרות נשמרו.',
	'AUTOPUNISH_REQUIRES_PHPBB_330'     => 'AutoPunish דורש phpBB 3.3.0 ומעלה.',

	// Notifications section
	'AUTOPUNISH_NOTIFICATIONS'          => 'התראות',
	'AUTOPUNISH_NOTIFY_FORUM'           => 'הפעל התראות פורום (אייקון הפעמון)',
	'AUTOPUNISH_NOTIFY_PM'              => 'הפעל התראות הודעה פרטית',
	'AUTOPUNISH_NOTIFY_EMAIL'           => 'הפעל התראות דואר אלקטרוני',
	'AUTOPUNISH_NOTIFY_SENDER'            => 'שולח ההודעות/מייל (מזהה משתמש)',
	'AUTOPUNISH_NOTIFY_SENDER_EXPLAIN'    => 'מזהה המשתמש שממנו נשלחות ההודעות הפרטיות והמיילים. השאר 0 לברירת המחדל.',
	'AUTOPUNISH_SENDER_REQUIRED'          => 'נדרש שם משתמש שולח כאשר הודעות פרטיות או מייל מופעלים.',
	'AUTOPUNISH_SENDER_MISSING_WARNING'   => 'אזהרה: חשבון השולח המוגדר אינו קיים עוד. הודעות פרטיות ומיילים אינם נשלחים כרגע.',
	'AUTOPUNISH_NOTIFY_SUBJECT'         => 'שורת נושא להודעה פרטית/מייל',
	'AUTOPUNISH_NOTIFY_SUBJECT_EXPLAIN' => 'השאר ריק כדי להשתמש בטקסט ההתראה של הרמה כנושא.',
	'AUTOPUNISH_NOTIFY_FOOTER'                  => 'טקסט כותרת תחתונה להודעות',
	'AUTOPUNISH_NOTIFY_EXPIRY_SUBJECT'          => 'שורת נושא להודעת פקיעה',
	'AUTOPUNISH_NOTIFY_EXPIRY_SUBJECT_EXPLAIN'  => 'השאר ריק כדי להשתמש בכותרת התראת פקיעת העונש כנושא.',
	'AUTOPUNISH_NOTIFY_EXPIRY_TEXT'             => 'טקסט התראת פקיעת עונש',
	'AUTOPUNISH_NOTIFY_COMMUTE_SUBJECT'         => 'שורת נושא להודעת סיום מוקדם',
	'AUTOPUNISH_NOTIFY_COMMUTE_SUBJECT_EXPLAIN' => 'השאר ריק כדי להשתמש בכותרת התראת סיום מוקדם כנושא.',
	'AUTOPUNISH_NOTIFY_COMMUTE_TEXT'            => 'טקסט התראת קיצור עונש',
	'AUTOPUNISH_PLACEHOLDERS_EXPLAIN'   => 'משתני תבנית: {USERNAME}, {DURATION}, {REASON}, {OFFENSE_NUMBER}',

	// Tiers page
	'AUTOPUNISH_TIERS_EXPLAIN'          => 'כל שורה היא רמת עונש. מיקום השורה = מספר העבירה. הרמה האחרונה משמשת לכל העונשים מעבר לרמות המוגדרות.',
	'AUTOPUNISH_WARNING_THRESHOLD'      => 'סף אזהרות (≥)',
	'AUTOPUNISH_ACTION'                 => 'פעולה',
	'AUTOPUNISH_ACTION_GROUP'           => 'הוסף לקבוצה',
	'AUTOPUNISH_ACTION_DEACTIVATE'      => 'השבת חשבון',
	'AUTOPUNISH_GROUP'                  => 'קבוצה',
	'AUTOPUNISH_DURATION'               => 'משך',
	'AUTOPUNISH_DURATION_ZERO'          => '0 = קבוע',
	'AUTOPUNISH_DAYS'                   => 'ימים',
	'AUTOPUNISH_WEEKS'                  => 'שבועות',
	'AUTOPUNISH_MONTHS'                 => 'חודשים',
	'AUTOPUNISH_REASON_TEXT'            => 'סיבה (למנהלים)',
	'AUTOPUNISH_NOTIFICATION_TEXT'      => 'טקסט התראה',
	'AUTOPUNISH_REMOVE'                 => 'הסר',
	'AUTOPUNISH_REMOVE_CONFIRM'         => 'להסיר רמה זו?',
	'AUTOPUNISH_ADD_TIER'               => 'הוסף רמה',
	'ACP_AUTOPUNISH_TIERS_SAVED'        => 'הרמות נשמרו.',

	// User management page
	'AUTOPUNISH_SELECT_USER'            => 'בחר משתמש',
	'AUTOPUNISH_STATUS_SUMMARY'         => 'סטטוס ענישה אוטומטית',
	'AUTOPUNISH_CURRENT_WARNINGS'       => 'אזהרות פעילות',
	'AUTOPUNISH_TOTAL_PUNISHMENTS'      => 'סך כל העונשים שניתנו',
	'AUTOPUNISH_COMMUTED_OFFENSES'      => 'עונשים שנמחקו עד כה',
	'AUTOPUNISH_EFFECTIVE_OFFENSES'     => 'מספר עונשים אפקטיבי',
	'AUTOPUNISH_NEXT_OFFENSE'           => 'העבירה הבאה תהיה',
	'AUTOPUNISH_CURRENT_PUNISHMENT'     => 'עונש פעיל כעת',
	'AUTOPUNISH_THRESHOLD'              => 'מופעל ב',
	'AUTOPUNISH_REPEATING'              => 'חוזר על הרמה האחרונה',
	'AUTOPUNISH_NO_TIERS'               => 'לא הוגדרו רמות',
	'AUTOPUNISH_OFFENSE'                => 'עבירה',
	'AUTOPUNISH_EXPIRES'                => 'תוקף עד',
	'AUTOPUNISH_PERMANENT'              => 'קבוע',
	'AUTOPUNISH_END_EARLY'              => 'סיים עונש מוקדם',
	'AUTOPUNISH_END_PUNISHMENT'         => 'סיים עונש עכשיו',
	'AUTOPUNISH_END_EARLY_CONFIRM'      => 'פעולה זו תסיר את העונש מיידית. העבירה עדיין תיספר בתיעוד. להמשיך?',
	'AUTOPUNISH_STEP_DOWN'              => 'החל גם את הרמה הקודמת כעונש חדש',
	'AUTOPUNISH_COMMUTE_OFFENSES'       => 'מחיקת עונשים',
	'AUTOPUNISH_COMMUTE_EXPLAIN'        => 'הזן את מספר העונשים להפחתה מהספירה האפקטיבית הנוכחית. העונש הבא ישתמש ברמה נמוכה בהתאם. אין השפעה על העונש הפעיל הנוכחי.',
	'AUTOPUNISH_COMMUTE_SET'            => 'הפחת את הספירה האפקטיבית ב',
	'AUTOPUNISH_PUNISHMENT_HISTORY'     => 'היסטוריית עונשים',
	'AUTOPUNISH_NO_HISTORY'             => 'אין היסטוריית עונשים.',
	'AUTOPUNISH_STARTED'                => 'התחיל',
	'AUTOPUNISH_STATUS'                 => 'סטטוס',
	'AUTOPUNISH_ACTIVE'                 => 'פעיל',
	'AUTOPUNISH_EXPIRED_STATUS'         => 'פג תוקף',
	'ACP_AUTOPUNISH_PUNISHMENT_ENDED'   => 'העונש הסתיים.',
	'ACP_AUTOPUNISH_COMMUTATION_SAVED'  => 'מחיקת העונשים נשמרה.',

	// Duration strings (used in {DURATION} placeholder and admin log)
	'AUTOPUNISH_LOG_DURATION_UNTIL'  => '%1$s, עד %2$s',
	'AUTOPUNISH_DURATION_PERMANENT' => 'לצמיתות',
	'AUTOPUNISH_DURATION_DAYS'      => [1 => 'יום',   2 => '%d ימים'],
	'AUTOPUNISH_DURATION_WEEKS'     => [1 => 'שבוע',  2 => '%d שבועות'],
	'AUTOPUNISH_DURATION_MONTHS'    => [1 => 'חודש',  2 => '%d חודשים'],

	// Notification titles and default body text (used when no custom text is configured)
	'NOTIFICATION_TYPE_AUTOPUNISH_APPLIED'  => 'AutoPunish: עונש הוחל',
	'NOTIFICATION_TYPE_AUTOPUNISH_EXPIRED'  => 'AutoPunish: עונש פג',
	'NOTIFICATION_TYPE_AUTOPUNISH_COMMUTED' => 'AutoPunish: עונש הסתיים מוקדם',

	'AUTOPUNISH_NOTIFY_APPLIED_TITLE'        => 'קיבלת עונש.',
	'AUTOPUNISH_NOTIFY_EXPIRED_TITLE'        => 'העונש שלך פג.',
	'AUTOPUNISH_NOTIFY_COMMUTED_TITLE'       => 'העונש שלך הסתיים מוקדם.',
	'AUTOPUNISH_NOTIFY_APPLIED_DEFAULT_BODY' => 'קיבלת עונש מספר #{OFFENSE_NUMBER} (משך: {DURATION}).',
	'AUTOPUNISH_NOTIFY_EXPIRED_DEFAULT_BODY' => 'העונש שלך מספר #{OFFENSE_NUMBER} פג.',
	'AUTOPUNISH_NOTIFY_COMMUTED_DEFAULT_BODY'=> 'העונש שלך מספר #{OFFENSE_NUMBER} הסתיים מוקדם.',

	// Admin log messages
	'LOG_AUTOPUNISH_APPLIED'      => '<strong>AutoPunish:</strong> המשתמש &quot;%1$s&quot; נענש — עבירה #%2$d, פעולה: %3$s, קבוצה: %4$s, משך: %5$s, סיבה: %6$s',
	'LOG_AUTOPUNISH_EXPIRED'      => '<strong>AutoPunish:</strong> עונשו של המשתמש &quot;%1$s&quot; פג — עבירה #%2$d',
	'LOG_AUTOPUNISH_ENDED_EARLY'  => '<strong>AutoPunish:</strong> עונשו של המשתמש &quot;%1$s&quot; הסתיים מוקדם — עבירה #%2$d',
	'LOG_AUTOPUNISH_COMMUTED'     => '<strong>AutoPunish:</strong> ספירת עונשים של המשתמש &quot;%1$s&quot; הופחתה — נמחקו %2$d עונשים (סה״כ שנמחקו: %3$d, אפקטיבי: %4$d)',
	'LOG_AUTOPUNISH_RESTARTED'    => '<strong>AutoPunish:</strong> טיימר העונש של המשתמש &quot;%1$s&quot; אופס — עבירה #%2$d',
	'LOG_AUTOPUNISH_ESCALATED'    => '<strong>AutoPunish:</strong> עונשו של המשתמש &quot;%1$s&quot; הוסלם — עבירה #%2$d → #%3$d',
	'LOG_AUTOPUNISH_DRY_RUN'      => '<strong>AutoPunish [ניסוי]:</strong> המשתמש &quot;%1$s&quot; היה נענש — עבירה #%2$d, פעולה: %3$s',
	'LOG_AUTOPUNISH_RETROACTIVE'    => '<strong>AutoPunish:</strong> סריקה רטרואקטיבית הושלמה — %1$d משתמשים נענשו',
	'LOG_AUTOPUNISH_SENDER_MISSING' => '<strong>AutoPunish:</strong> שליחת התראה בוטלה — חשבון השולח (מזהה %1$d) אינו קיים עוד. עדכן את השולח בהגדרות AutoPunish.',
]);
