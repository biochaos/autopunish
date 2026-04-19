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
	'ACP_AUTOPUNISH_SETTINGS' => 'Configurações',
	'ACP_AUTOPUNISH_TIERS'    => 'Níveis de punição',
	'ACP_AUTOPUNISH_USER'     => 'Gestão de utilizadores',
	// Label shown in the Manage Users mode dropdown
	'ACP_USER_AUTOPUNISH'     => 'AutoPunish',

	// Settings page
	'AUTOPUNISH_GENERAL'                => 'Configurações gerais',
	'AUTOPUNISH_ENABLED'                => 'Ativar AutoPunish',
	'AUTOPUNISH_WARN_DURING_PUNISHMENT' => 'Aviso durante punição ativa',
	'AUTOPUNISH_WDP_RESTART'            => 'Reiniciar temporizador',
	'AUTOPUNISH_WDP_NORMAL'             => 'Normal (aplicar regras de limiar)',
	'AUTOPUNISH_WDP_ESCALATE'           => 'Escalar imediatamente',
	'AUTOPUNISH_CRON_INTERVAL'          => 'Intervalo de verificação de expiração',
	'AUTOPUNISH_MINUTES'                => 'minutos',
	'AUTOPUNISH_EXEMPT_GROUPS'          => 'Grupos isentos',
	'AUTOPUNISH_DRY_RUN'                => 'Modo simulação',
	'AUTOPUNISH_DRY_RUN_EXPLAIN'        => 'Quando ativado, as punições são registadas mas não aplicadas. Use isto para verificar as configurações de limiar antes de ativar.',
	'AUTOPUNISH_RETROACTIVE'            => 'Executar análise retroativa',
	'AUTOPUNISH_RETROACTIVE_EXPLAIN'    => 'Quando marcado e guardado, será executada uma análise única para punir utilizadores que já cumprem o limiar. Esta caixa é reiniciada automaticamente após a análise.',
	'ACP_AUTOPUNISH_SETTINGS_SAVED'     => 'Configurações guardadas.',
	'AUTOPUNISH_REQUIRES_PHPBB_330'     => 'AutoPunish requer phpBB 3.3.0 ou superior.',

	// Notifications section
	'AUTOPUNISH_NOTIFICATIONS'          => 'Notificações',
	'AUTOPUNISH_NOTIFY_FORUM'           => 'Ativar notificações do fórum (ícone de sino)',
	'AUTOPUNISH_NOTIFY_PM'              => 'Ativar notificações por mensagem privada',
	'AUTOPUNISH_NOTIFY_EMAIL'           => 'Ativar notificações por e-mail',
	'AUTOPUNISH_NOTIFY_SENDER'          => 'Nome de utilizador do remetente de MP/e-mail',
	'AUTOPUNISH_NOTIFY_SENDER_EXPLAIN'  => 'Necessário quando as notificações por MP ou e-mail estão ativas.',
	'AUTOPUNISH_SENDER_REQUIRED'        => 'É necessário um nome de utilizador remetente quando as notificações por MP ou e-mail estão ativas.',
	'AUTOPUNISH_SENDER_MISSING_WARNING' => 'Aviso: a conta do remetente configurado já não existe. As notificações por MP e e-mail não estão a ser enviadas.',
	'AUTOPUNISH_NOTIFY_SUBJECT'         => 'Assunto do MP/e-mail',
	'AUTOPUNISH_NOTIFY_SUBJECT_EXPLAIN' => 'Deixar em branco para usar o texto de notificação do nível como assunto.',
	'AUTOPUNISH_NOTIFY_FOOTER'                  => 'Texto de rodapé do MP/e-mail',
	'AUTOPUNISH_NOTIFY_EXPIRY_SUBJECT'          => 'Assunto do MP/e-mail de expiração',
	'AUTOPUNISH_NOTIFY_EXPIRY_SUBJECT_EXPLAIN'  => 'Deixar em branco para usar o título da notificação de expiração como assunto.',
	'AUTOPUNISH_NOTIFY_EXPIRY_TEXT'             => 'Texto de notificação de expiração',
	'AUTOPUNISH_NOTIFY_COMMUTE_SUBJECT'         => 'Assunto do MP/e-mail de encerramento antecipado',
	'AUTOPUNISH_NOTIFY_COMMUTE_SUBJECT_EXPLAIN' => 'Deixar em branco para usar o título da notificação de encerramento antecipado como assunto.',
	'AUTOPUNISH_NOTIFY_COMMUTE_TEXT'            => 'Texto de notificação de comutação',
	'AUTOPUNISH_PLACEHOLDERS_EXPLAIN'   => 'Variáveis disponíveis: {USERNAME}, {DURATION}, {REASON}, {OFFENSE_NUMBER}. BBCode é suportado nos textos de MP/e-mail.',

	// Tiers page
	'AUTOPUNISH_TIERS_EXPLAIN'          => 'Cada linha é um nível de punição. Posição da linha = número de infração. O último nível é usado para todas as infrações além do número configurado.',
	'AUTOPUNISH_WARNING_THRESHOLD'      => 'Limiar de avisos (≥)',
	'AUTOPUNISH_ACTION'                 => 'Ação',
	'AUTOPUNISH_ACTION_GROUP'           => 'Adicionar ao grupo',
	'AUTOPUNISH_ACTION_DEACTIVATE'      => 'Desativar conta',
	'AUTOPUNISH_GROUP'                  => 'Grupo',
	'AUTOPUNISH_DURATION'               => 'Duração',
	'AUTOPUNISH_DURATION_ZERO'          => '0 = permanente',
	'AUTOPUNISH_DAYS'                   => 'Dias',
	'AUTOPUNISH_WEEKS'                  => 'Semanas',
	'AUTOPUNISH_MONTHS'                 => 'Meses',
	'AUTOPUNISH_REASON_TEXT'            => 'Motivo (visível para administradores)',
	'AUTOPUNISH_NOTIFICATION_TEXT'      => 'Texto de notificação',
	'AUTOPUNISH_REMOVE'                 => 'Remover',
	'AUTOPUNISH_REMOVE_CONFIRM'         => 'Remover este nível?',
	'AUTOPUNISH_ADD_TIER'               => 'Adicionar nível',
	'ACP_AUTOPUNISH_TIERS_SAVED'        => 'Níveis guardados.',

	// User management page
	'AUTOPUNISH_SELECT_USER'            => 'Selecionar utilizador',
	'AUTOPUNISH_STATUS_SUMMARY'         => 'Estado do AutoPunish',
	'AUTOPUNISH_CURRENT_WARNINGS'       => 'Avisos ativos',
	'AUTOPUNISH_TOTAL_PUNISHMENTS'      => 'Total de punições',
	'AUTOPUNISH_COMMUTED_OFFENSES'      => 'Infrações comutadas até agora',
	'AUTOPUNISH_EFFECTIVE_OFFENSES'     => 'Número efetivo de infrações',
	'AUTOPUNISH_NEXT_OFFENSE'           => 'A próxima infração seria',
	'AUTOPUNISH_CURRENT_PUNISHMENT'     => 'Punição ativa atual',
	'AUTOPUNISH_THRESHOLD'              => 'ativa-se em',
	'AUTOPUNISH_REPEATING'              => 'repetindo o último nível',
	'AUTOPUNISH_NO_TIERS'               => 'Nenhum nível configurado',
	'AUTOPUNISH_OFFENSE'                => 'Infração',
	'AUTOPUNISH_EXPIRES'                => 'Expira',
	'AUTOPUNISH_PERMANENT'              => 'Permanente',
	'AUTOPUNISH_END_EARLY'              => 'Terminar punição antecipadamente',
	'AUTOPUNISH_END_PUNISHMENT'         => 'Terminar punição agora',
	'AUTOPUNISH_END_EARLY_CONFIRM'      => 'Isto irá remover a punição imediatamente. A infração continuará a contar no registo. Continuar?',
	'AUTOPUNISH_STEP_DOWN'              => 'Aplicar também o nível anterior como nova punição',
	'AUTOPUNISH_COMMUTE_OFFENSES'       => 'Comutar infrações',
	'AUTOPUNISH_COMMUTE_EXPLAIN'        => 'Introduza o número de infrações a remover do contador efetivo atual. A próxima punição usará um nível correspondentemente inferior. Isto não afeta a punição ativa atual.',
	'AUTOPUNISH_COMMUTE_SET'            => 'Reduzir o contador efetivo em',
	'AUTOPUNISH_PUNISHMENT_HISTORY'     => 'Histórico de punições',
	'AUTOPUNISH_NO_HISTORY'             => 'Sem histórico de punições.',
	'AUTOPUNISH_STARTED'                => 'Iniciado',
	'AUTOPUNISH_STATUS'                 => 'Estado',
	'AUTOPUNISH_ACTIVE'                 => 'Ativo',
	'AUTOPUNISH_EXPIRED_STATUS'         => 'Expirado',
	'ACP_AUTOPUNISH_PUNISHMENT_ENDED'   => 'Punição terminada.',
	'ACP_AUTOPUNISH_COMMUTATION_SAVED'  => 'Comutação guardada.',

	// Duration strings (used in {DURATION} placeholder and admin log)
	'AUTOPUNISH_LOG_DURATION_UNTIL'  => '%1$s, até %2$s',
	'AUTOPUNISH_DURATION_PERMANENT'  => 'permanente',
	'AUTOPUNISH_DURATION_DAYS'       => [1 => '1 dia',    2 => '%d dias'],
	'AUTOPUNISH_DURATION_WEEKS'      => [1 => '1 semana', 2 => '%d semanas'],
	'AUTOPUNISH_DURATION_MONTHS'     => [1 => '1 mês',    2 => '%d meses'],

	// Notification titles and default body text
	'NOTIFICATION_TYPE_AUTOPUNISH_APPLIED'  => 'AutoPunish: Punição aplicada',
	'NOTIFICATION_TYPE_AUTOPUNISH_EXPIRED'  => 'AutoPunish: Punição expirada',
	'NOTIFICATION_TYPE_AUTOPUNISH_COMMUTED' => 'AutoPunish: Punição encerrada',

	'AUTOPUNISH_NOTIFY_APPLIED_TITLE'         => 'Recebeu uma punição.',
	'AUTOPUNISH_NOTIFY_EXPIRED_TITLE'         => 'A sua punição expirou.',
	'AUTOPUNISH_NOTIFY_COMMUTED_TITLE'        => 'A sua punição foi levantada antecipadamente.',
	'AUTOPUNISH_NOTIFY_APPLIED_DEFAULT_BODY'  => 'Recebeu a punição #{OFFENSE_NUMBER} (duração: {DURATION}).',
	'AUTOPUNISH_NOTIFY_EXPIRED_DEFAULT_BODY'  => 'A sua punição #{OFFENSE_NUMBER} expirou.',
	'AUTOPUNISH_NOTIFY_COMMUTED_DEFAULT_BODY' => 'A sua punição #{OFFENSE_NUMBER} foi levantada antecipadamente.',

	// Admin log messages
	'LOG_AUTOPUNISH_APPLIED'        => '<strong>AutoPunish:</strong> utilizador &quot;%1$s&quot; punido — infração n.º%2$d, ação: %3$s, grupo: %4$s, duração: %5$s, motivo: %6$s',
	'LOG_AUTOPUNISH_EXPIRED'        => '<strong>AutoPunish:</strong> punição do utilizador &quot;%1$s&quot; expirada — infração n.º%2$d',
	'LOG_AUTOPUNISH_ENDED_EARLY'    => '<strong>AutoPunish:</strong> punição do utilizador &quot;%1$s&quot; terminada antecipadamente — infração n.º%2$d',
	'LOG_AUTOPUNISH_COMMUTED'       => '<strong>AutoPunish:</strong> contador de infrações do utilizador &quot;%1$s&quot; reduzido — %2$d infração(ões) comutada(s) (total comutado: %3$d, efetivo: %4$d)',
	'LOG_AUTOPUNISH_RESTARTED'      => '<strong>AutoPunish:</strong> temporizador de punição do utilizador &quot;%1$s&quot; reiniciado — infração n.º%2$d',
	'LOG_AUTOPUNISH_ESCALATED'      => '<strong>AutoPunish:</strong> punição do utilizador &quot;%1$s&quot; escalada — infração n.º%2$d → n.º%3$d',
	'LOG_AUTOPUNISH_DRY_RUN'        => '<strong>AutoPunish [SIMULAÇÃO]:</strong> o utilizador &quot;%1$s&quot; teria sido punido — infração n.º%2$d, ação: %3$s',
	'LOG_AUTOPUNISH_RETROACTIVE'    => '<strong>AutoPunish:</strong> análise retroativa concluída — %1$d utilizadores punidos',
	'LOG_AUTOPUNISH_SENDER_MISSING' => '<strong>AutoPunish:</strong> notificação MP/e-mail ignorada — o remetente (ID %1$d) já não existe. Atualize o remetente nas configurações do AutoPunish.',
]);
