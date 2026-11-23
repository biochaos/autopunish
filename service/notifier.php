<?php
/**
 * AutoPunish extension for phpBB.
 *
 * @copyright (c) 2026, biochaos
 * @license GNU General Public License, version 2 (GPL-2.0)
 */

namespace biochaos\autopunish\service;

/**
 * Tells a user about a punishment: the forum bell, a PM and an email, each
 * worded in the recipient's own language.
 */
class notifier
{
	/** @var \phpbb\db\driver\driver_interface */
	private $db;

	/** @var \phpbb\config\config */
	private $config;

	/** @var \phpbb\log\log_interface */
	private $log;

	/** @var \phpbb\notification\manager */
	private $notification_manager;

	/** @var \phpbb\user */
	private $user;

	/** @var string */
	private $phpbb_root_path;

	/** @var string */
	private $php_ext;

	public function __construct(
		\phpbb\db\driver\driver_interface $db,
		\phpbb\config\config $config,
		\phpbb\log\log_interface $log,
		\phpbb\notification\manager $notification_manager,
		\phpbb\user $user,
		$phpbb_root_path,
		$php_ext
	)
	{
		$this->db                   = $db;
		$this->config               = $config;
		$this->log                  = $log;
		$this->notification_manager = $notification_manager;
		$this->user                 = $user;
		$this->phpbb_root_path      = $phpbb_root_path;
		$this->php_ext              = $php_ext;
	}

	/**
	 * Sends the notifications that are switched on: the bell, and a PM and an
	 * email in the recipient's language.
	 *
	 * @param string $type    One of the extension's notification types
	 * @param int    $user_id The recipient
	 * @param array  $data    punishment_id, offense_number, notification_tpl,
	 *                        duration_seconds and placeholders
	 */
	public function notify($type, $user_id, array $data)
	{
		// Forum bell. Its text is worded when it is displayed, in the reader's
		// own language, so it is handed on as a template with its placeholders.
		if ($this->config['autopunish_notify_forum'])
		{
			$this->notification_manager->add_notifications($type, array_merge($data, ['user_id' => $user_id]));
		}

		if (!$this->config['autopunish_notify_pm'] && !$this->config['autopunish_notify_email'])
		{
			return;
		}

		// Load recipient's language file once — used for body, DURATION, and subject
		$recipient_lang_data = $this->load_lang_array($this->get_user_lang($user_id));

		$placeholders = $data['placeholders'] ?? [];
		if (isset($data['duration_seconds']))
		{
			$placeholders['DURATION'] = $this->format_duration_from_lang($data['duration_seconds'], $recipient_lang_data);
		}

		static $type_defaults = [
			'biochaos.autopunish.notification.punishment_applied'  => [
				'title' => 'AUTOPUNISH_NOTIFY_APPLIED_TITLE',
				'body'  => 'AUTOPUNISH_NOTIFY_APPLIED_DEFAULT_BODY',
			],
			'biochaos.autopunish.notification.punishment_expired'  => [
				'title' => 'AUTOPUNISH_NOTIFY_EXPIRED_TITLE',
				'body'  => 'AUTOPUNISH_NOTIFY_EXPIRED_DEFAULT_BODY',
			],
			'biochaos.autopunish.notification.punishment_commuted' => [
				'title' => 'AUTOPUNISH_NOTIFY_COMMUTED_TITLE',
				'body'  => 'AUTOPUNISH_NOTIFY_COMMUTED_DEFAULT_BODY',
			],
		];

		// Body: configured template → language file default → skip
		$tpl = $data['notification_tpl'] ?? '';
		if ($tpl !== '')
		{
			$pm_text = placeholders::replace($tpl, $placeholders);
		}
		else if (isset($type_defaults[$type], $recipient_lang_data[$type_defaults[$type]['body']]))
		{
			$pm_text = placeholders::replace($recipient_lang_data[$type_defaults[$type]['body']], $placeholders);
		}
		else
		{
			return;
		}

		$footer = placeholders::replace($this->config['autopunish_notify_footer'], $placeholders);
		$body   = $pm_text . ($footer ? "\n\n" . $footer : '');

		// Subject: type-specific template → language file title → body text
		static $type_subject_config = [
			'biochaos.autopunish.notification.punishment_applied'  => 'autopunish_notify_subject',
			'biochaos.autopunish.notification.punishment_expired'  => 'autopunish_notify_expiry_subject',
			'biochaos.autopunish.notification.punishment_commuted' => 'autopunish_notify_commute_subject',
		];
		$subject_config_key = $type_subject_config[$type] ?? 'autopunish_notify_subject';
		$subject_tpl = $this->config[$subject_config_key] ?? '';
		if (!empty($subject_tpl))
		{
			$subject = placeholders::replace($subject_tpl, $placeholders);
		}
		else if (isset($type_defaults[$type], $recipient_lang_data[$type_defaults[$type]['title']]))
		{
			$subject = $recipient_lang_data[$type_defaults[$type]['title']];
		}
		else
		{
			$subject = $pm_text;
		}

		if ($this->config['autopunish_notify_pm'])
		{
			$this->send_pm($user_id, $subject, $body);
		}

		if ($this->config['autopunish_notify_email'])
		{
			$this->send_email($user_id, $subject, $body);
		}
	}

	private function get_user_lang($user_id)
	{
		$sql    = 'SELECT user_lang FROM ' . USERS_TABLE . ' WHERE user_id = ' . (int) $user_id;
		$result = $this->db->sql_query($sql);
		$lang   = $this->db->sql_fetchfield('user_lang');
		$this->db->sql_freeresult($result);
		return $lang ?: 'en';
	}

	private function load_lang_array($lang_code)
	{
		$lang = [];

		// The recipient's language, then the board's default, then English: the
		// order phpBB's own language service falls back in.
		foreach (array_unique([$lang_code, $this->config['default_lang'], 'en']) as $try_lang)
		{
			// The name becomes part of a path.
			if (!preg_match('/^[a-z0-9_-]+$/i', (string) $try_lang))
			{
				continue;
			}

			$file = $this->phpbb_root_path . 'ext/biochaos/autopunish/language/' . $try_lang . '/common.php';
			if (file_exists($file))
			{
				include $file;
				break;
			}
		}
		return $lang;
	}

	/**
	 * A punishment length worded from a recipient's raw language array.
	 */
	private function format_duration_from_lang($seconds, array $lang)
	{
		[$key, $count] = duration::lang_args($seconds);
		$text = $lang[$key] ?? null;

		if ($text === null)
		{
			return (string) $count;
		}
		if (is_array($text))
		{
			$text = ($count === 1) ? ($text[1] ?? '%d') : ($text[2] ?? '%d');
		}

		return sprintf($text, $count);
	}

	private function send_pm($to_user_id, $subject, $body)
	{
		if (!function_exists('submit_pm'))
		{
			include($this->phpbb_root_path . 'includes/functions_privmsgs.' . $this->php_ext);
		}
		if (!function_exists('generate_text_for_storage'))
		{
			include($this->phpbb_root_path . 'includes/functions_content.' . $this->php_ext);
		}

		$sender_id = (int) $this->config['autopunish_notify_sender_id'];
		if (!$sender_id)
		{
			return;
		}

		// Parse BBCode in message body. The uid and bitfield are filled in by the
		// parser, which is the only thing that knows them.
		$bbcode_uid      = '';
		$bbcode_bitfield = '';
		$bbcode_flags    = \OPTION_FLAG_BBCODE | \OPTION_FLAG_SMILIES | \OPTION_FLAG_LINKS;
		\generate_text_for_storage($body, $bbcode_uid, $bbcode_bitfield, $bbcode_flags, true, true, true);

		$pm_data = [
			'from_user_id'    => $sender_id,
			'from_username'   => '',
			'from_user_ip'    => $this->user->ip ?: '0.0.0.0',
			'reply_from_msgid' => 0,
			'icon_id'         => 0,
			'enable_bbcode'   => 1,
			'enable_smilies'  => 1,
			'enable_urls'     => 1,
			'enable_sig'      => 0,
			'message'         => $body,
			'bbcode_bitfield' => $bbcode_bitfield,
			'bbcode_uid'      => $bbcode_uid,
			'address_list'    => ['u' => [$to_user_id => 'to']],
		];

		// Fetch sender username
		$sql      = 'SELECT username FROM ' . USERS_TABLE . ' WHERE user_id = ' . $sender_id;
		$result   = $this->db->sql_query($sql);
		$username = $this->db->sql_fetchfield('username');
		$this->db->sql_freeresult($result);

		if (!$username)
		{
			$this->log->add('admin', $this->user->data['user_id'], $this->user->ip, 'LOG_AUTOPUNISH_SENDER_MISSING', false, [$sender_id]);
			return;
		}

		$pm_data['from_username'] = (string) $username;
		\submit_pm('post', $subject, $pm_data, false);
	}

	private function send_email($to_user_id, $subject, $body)
	{
		$sql    = 'SELECT username, user_email, user_lang FROM ' . USERS_TABLE . ' WHERE user_id = ' . (int) $to_user_id;
		$result = $this->db->sql_query($sql);
		$user   = $this->db->sql_fetchrow($result);
		$this->db->sql_freeresult($result);

		if (!$user || empty($user['user_email']))
		{
			return;
		}

		if (!class_exists('messenger'))
		{
			include($this->phpbb_root_path . 'includes/functions_messenger.' . $this->php_ext);
		}

		$messenger = new \messenger(false);
		$messenger->template('@biochaos_autopunish/notification_email', $user['user_lang']);
		$messenger->to($user['user_email'], $user['username']);
		$messenger->assign_vars([
			'SUBJECT'  => tier_manager::decode_text($subject),
			'MESSAGE'  => $this->to_plain_text($body),
		]);
		$messenger->send(\NOTIFY_EMAIL);
	}

	/**
	 * Turns a PM body into plain text for a plain-text email. The configured
	 * texts are stored HTML-escaped, the way phpBB's request class returns
	 * them, and may contain BBCode: the email gets the BBCode stripped and the
	 * entities decoded, so that "&quot;" and "[b]" do not reach the recipient.
	 */
	private function to_plain_text($text)
	{
		if (!function_exists('generate_text_for_storage'))
		{
			include($this->phpbb_root_path . 'includes/functions_content.' . $this->php_ext);
		}

		$uid = $bitfield = '';
		$flags = 0;
		\generate_text_for_storage($text, $uid, $bitfield, $flags, true, false, false);
		\strip_bbcode($text, $uid);

		return tier_manager::decode_text($text);
	}
}
