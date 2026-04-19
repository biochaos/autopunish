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
	'ACP_AUTOPUNISH_SETTINGS' => 'Configuración',
	'ACP_AUTOPUNISH_TIERS'    => 'Niveles de sanción',
	'ACP_AUTOPUNISH_USER'     => 'Gestión de usuarios',
	// Label shown in the Manage Users mode dropdown
	'ACP_USER_AUTOPUNISH'     => 'AutoPunish',

	// Settings page
	'AUTOPUNISH_GENERAL'                => 'Configuración general',
	'AUTOPUNISH_ENABLED'                => 'Activar AutoPunish',
	'AUTOPUNISH_WARN_DURING_PUNISHMENT' => 'Aviso durante una sanción activa',
	'AUTOPUNISH_WDP_RESTART'            => 'Reiniciar temporizador',
	'AUTOPUNISH_WDP_NORMAL'             => 'Normal (aplicar reglas de umbral)',
	'AUTOPUNISH_WDP_ESCALATE'           => 'Escalar inmediatamente',
	'AUTOPUNISH_CRON_INTERVAL'          => 'Intervalo de comprobación de expiración',
	'AUTOPUNISH_MINUTES'                => 'minutos',
	'AUTOPUNISH_EXEMPT_GROUPS'          => 'Grupos exentos',
	'AUTOPUNISH_DRY_RUN'                => 'Modo simulación',
	'AUTOPUNISH_DRY_RUN_EXPLAIN'        => 'Cuando está activado, las sanciones se registran pero no se aplican. Úselo para verificar la configuración de umbrales antes de activar.',
	'AUTOPUNISH_RETROACTIVE'            => 'Ejecutar análisis retroactivo',
	'AUTOPUNISH_RETROACTIVE_EXPLAIN'    => 'Cuando se marca y guarda, se ejecutará un análisis único para sancionar a los usuarios que ya cumplen el umbral. Esta casilla se restablece automáticamente tras el análisis.',
	'ACP_AUTOPUNISH_SETTINGS_SAVED'     => 'Configuración guardada.',
	'AUTOPUNISH_REQUIRES_PHPBB_330'     => 'AutoPunish requiere phpBB 3.3.0 o superior.',

	// Notifications section
	'AUTOPUNISH_NOTIFICATIONS'          => 'Notificaciones',
	'AUTOPUNISH_NOTIFY_FORUM'           => 'Activar notificaciones del foro (icono campana)',
	'AUTOPUNISH_NOTIFY_PM'              => 'Activar notificaciones por mensaje privado',
	'AUTOPUNISH_NOTIFY_EMAIL'           => 'Activar notificaciones por correo electrónico',
	'AUTOPUNISH_NOTIFY_SENDER'          => 'Nombre de usuario del remitente de MP/correo',
	'AUTOPUNISH_NOTIFY_SENDER_EXPLAIN'  => 'Requerido cuando las notificaciones por MP o correo están activadas.',
	'AUTOPUNISH_SENDER_REQUIRED'        => 'Se requiere un nombre de usuario remitente cuando las notificaciones por MP o correo están activadas.',
	'AUTOPUNISH_SENDER_MISSING_WARNING' => 'Advertencia: la cuenta del remitente configurado ya no existe. Las notificaciones por MP y correo no se están enviando actualmente.',
	'AUTOPUNISH_NOTIFY_SUBJECT'         => 'Asunto del MP/correo',
	'AUTOPUNISH_NOTIFY_SUBJECT_EXPLAIN' => 'Dejar en blanco para usar el texto de notificación del nivel como asunto.',
	'AUTOPUNISH_NOTIFY_FOOTER'                  => 'Texto de pie de página del MP/correo',
	'AUTOPUNISH_NOTIFY_EXPIRY_SUBJECT'          => 'Asunto del MP/correo de expiración',
	'AUTOPUNISH_NOTIFY_EXPIRY_SUBJECT_EXPLAIN'  => 'Dejar en blanco para usar el título de la notificación de expiración como asunto.',
	'AUTOPUNISH_NOTIFY_EXPIRY_TEXT'             => 'Texto de notificación de expiración',
	'AUTOPUNISH_NOTIFY_COMMUTE_SUBJECT'         => 'Asunto del MP/correo de fin anticipado',
	'AUTOPUNISH_NOTIFY_COMMUTE_SUBJECT_EXPLAIN' => 'Dejar en blanco para usar el título de la notificación de fin anticipado como asunto.',
	'AUTOPUNISH_NOTIFY_COMMUTE_TEXT'            => 'Texto de notificación de conmutación',
	'AUTOPUNISH_PLACEHOLDERS_EXPLAIN'   => 'Variables disponibles: {USERNAME}, {DURATION}, {REASON}, {OFFENSE_NUMBER}. BBCode está soportado en los textos de MP/correo.',

	// Tiers page
	'AUTOPUNISH_TIERS_EXPLAIN'          => 'Cada fila es un nivel de sanción. Posición de la fila = número de infracción. El último nivel se usa para todas las infracciones más allá del número configurado.',
	'AUTOPUNISH_WARNING_THRESHOLD'      => 'Umbral de avisos (≥)',
	'AUTOPUNISH_ACTION'                 => 'Acción',
	'AUTOPUNISH_ACTION_GROUP'           => 'Añadir al grupo',
	'AUTOPUNISH_ACTION_DEACTIVATE'      => 'Desactivar cuenta',
	'AUTOPUNISH_GROUP'                  => 'Grupo',
	'AUTOPUNISH_DURATION'               => 'Duración',
	'AUTOPUNISH_DURATION_ZERO'          => '0 = permanente',
	'AUTOPUNISH_DAYS'                   => 'Días',
	'AUTOPUNISH_WEEKS'                  => 'Semanas',
	'AUTOPUNISH_MONTHS'                 => 'Meses',
	'AUTOPUNISH_REASON_TEXT'            => 'Motivo (visible para administradores)',
	'AUTOPUNISH_NOTIFICATION_TEXT'      => 'Texto de notificación',
	'AUTOPUNISH_REMOVE'                 => 'Eliminar',
	'AUTOPUNISH_REMOVE_CONFIRM'         => '¿Eliminar este nivel?',
	'AUTOPUNISH_ADD_TIER'               => 'Añadir nivel',
	'ACP_AUTOPUNISH_TIERS_SAVED'        => 'Niveles guardados.',

	// User management page
	'AUTOPUNISH_SELECT_USER'            => 'Seleccionar usuario',
	'AUTOPUNISH_STATUS_SUMMARY'         => 'Estado de AutoPunish',
	'AUTOPUNISH_CURRENT_WARNINGS'       => 'Avisos activos',
	'AUTOPUNISH_TOTAL_PUNISHMENTS'      => 'Total de sanciones',
	'AUTOPUNISH_COMMUTED_OFFENSES'      => 'Infracciones conmutadas hasta ahora',
	'AUTOPUNISH_EFFECTIVE_OFFENSES'     => 'Número efectivo de infracciones',
	'AUTOPUNISH_NEXT_OFFENSE'           => 'La próxima infracción sería',
	'AUTOPUNISH_CURRENT_PUNISHMENT'     => 'Sanción activa actual',
	'AUTOPUNISH_THRESHOLD'              => 'se activa en',
	'AUTOPUNISH_REPEATING'              => 'repitiendo el último nivel',
	'AUTOPUNISH_NO_TIERS'               => 'No hay niveles configurados',
	'AUTOPUNISH_OFFENSE'                => 'Infracción',
	'AUTOPUNISH_EXPIRES'                => 'Expira',
	'AUTOPUNISH_PERMANENT'              => 'Permanente',
	'AUTOPUNISH_END_EARLY'              => 'Terminar sanción anticipadamente',
	'AUTOPUNISH_END_PUNISHMENT'         => 'Terminar sanción ahora',
	'AUTOPUNISH_END_EARLY_CONFIRM'      => 'Esto eliminará la sanción inmediatamente. La infracción seguirá contando en el historial. ¿Continuar?',
	'AUTOPUNISH_STEP_DOWN'              => 'Aplicar también el nivel anterior como nueva sanción',
	'AUTOPUNISH_COMMUTE_OFFENSES'       => 'Conmutar infracciones',
	'AUTOPUNISH_COMMUTE_EXPLAIN'        => 'Introduzca el número de infracciones a restar del contador efectivo actual. La próxima sanción usará un nivel correspondientemente inferior. Esto no afecta a la sanción activa actual.',
	'AUTOPUNISH_COMMUTE_SET'            => 'Reducir el contador efectivo en',
	'AUTOPUNISH_PUNISHMENT_HISTORY'     => 'Historial de sanciones',
	'AUTOPUNISH_NO_HISTORY'             => 'Sin historial de sanciones.',
	'AUTOPUNISH_STARTED'                => 'Iniciado',
	'AUTOPUNISH_STATUS'                 => 'Estado',
	'AUTOPUNISH_ACTIVE'                 => 'Activo',
	'AUTOPUNISH_EXPIRED_STATUS'         => 'Expirado',
	'ACP_AUTOPUNISH_PUNISHMENT_ENDED'   => 'Sanción finalizada.',
	'ACP_AUTOPUNISH_COMMUTATION_SAVED'  => 'Conmutación guardada.',

	// Duration strings (used in {DURATION} placeholder and admin log)
	'AUTOPUNISH_LOG_DURATION_UNTIL'  => '%1$s, hasta el %2$s',
	'AUTOPUNISH_DURATION_PERMANENT'  => 'permanente',
	'AUTOPUNISH_DURATION_DAYS'       => [1 => '1 día',   2 => '%d días'],
	'AUTOPUNISH_DURATION_WEEKS'      => [1 => '1 semana', 2 => '%d semanas'],
	'AUTOPUNISH_DURATION_MONTHS'     => [1 => '1 mes',   2 => '%d meses'],

	// Notification titles and default body text
	'NOTIFICATION_TYPE_AUTOPUNISH_APPLIED'  => 'AutoPunish: Sanción aplicada',
	'NOTIFICATION_TYPE_AUTOPUNISH_EXPIRED'  => 'AutoPunish: Sanción expirada',
	'NOTIFICATION_TYPE_AUTOPUNISH_COMMUTED' => 'AutoPunish: Sanción levantada',

	'AUTOPUNISH_NOTIFY_APPLIED_TITLE'         => 'Ha recibido una sanción.',
	'AUTOPUNISH_NOTIFY_EXPIRED_TITLE'         => 'Su sanción ha expirado.',
	'AUTOPUNISH_NOTIFY_COMMUTED_TITLE'        => 'Su sanción ha sido levantada anticipadamente.',
	'AUTOPUNISH_NOTIFY_APPLIED_DEFAULT_BODY'  => 'Ha recibido la sanción #{OFFENSE_NUMBER} (duración: {DURATION}).',
	'AUTOPUNISH_NOTIFY_EXPIRED_DEFAULT_BODY'  => 'Su sanción #{OFFENSE_NUMBER} ha expirado.',
	'AUTOPUNISH_NOTIFY_COMMUTED_DEFAULT_BODY' => 'Su sanción #{OFFENSE_NUMBER} ha sido levantada anticipadamente.',

	// Admin log messages
	'LOG_AUTOPUNISH_APPLIED'        => '<strong>AutoPunish:</strong> usuario &quot;%1$s&quot; sancionado — infracción n.º%2$d, acción: %3$s, grupo: %4$s, duración: %5$s, motivo: %6$s',
	'LOG_AUTOPUNISH_EXPIRED'        => '<strong>AutoPunish:</strong> sanción del usuario &quot;%1$s&quot; expirada — infracción n.º%2$d',
	'LOG_AUTOPUNISH_ENDED_EARLY'    => '<strong>AutoPunish:</strong> sanción del usuario &quot;%1$s&quot; terminada anticipadamente — infracción n.º%2$d',
	'LOG_AUTOPUNISH_COMMUTED'       => '<strong>AutoPunish:</strong> contador de infracciones del usuario &quot;%1$s&quot; reducido — %2$d infracción(es) conmutada(s) (total conmutado: %3$d, efectivo: %4$d)',
	'LOG_AUTOPUNISH_RESTARTED'      => '<strong>AutoPunish:</strong> temporizador de sanción del usuario &quot;%1$s&quot; reiniciado — infracción n.º%2$d',
	'LOG_AUTOPUNISH_ESCALATED'      => '<strong>AutoPunish:</strong> sanción del usuario &quot;%1$s&quot; escalada — infracción n.º%2$d → n.º%3$d',
	'LOG_AUTOPUNISH_DRY_RUN'        => '<strong>AutoPunish [SIMULACIÓN]:</strong> el usuario &quot;%1$s&quot; habría sido sancionado — infracción n.º%2$d, acción: %3$s',
	'LOG_AUTOPUNISH_RETROACTIVE'    => '<strong>AutoPunish:</strong> análisis retroactivo completado — %1$d usuarios sancionados',
	'LOG_AUTOPUNISH_SENDER_MISSING' => '<strong>AutoPunish:</strong> notificación MP/correo omitida — el remitente (ID %1$d) ya no existe. Actualice el remitente en la configuración de AutoPunish.',
]);
