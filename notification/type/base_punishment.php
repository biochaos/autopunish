<?php
/**
 * AutoPunish extension for phpBB.
 *
 * @copyright (c) 2026, biochaos
 * @license GNU General Public License, version 2 (GPL-2.0)
 */

namespace biochaos\autopunish\notification\type;

use biochaos\autopunish\service\duration;

abstract class base_punishment extends \phpbb\notification\type\base
{
	abstract protected function get_title_lang_key();

	public static function get_item_id($data)
	{
		return $data['punishment_id'];
	}

	public static function get_item_parent_id($data)
	{
		return 0;
	}

	public function is_available()
	{
		return true;
	}

	/**
	 * The punished user, unless they have switched this type off in the UCP.
	 * Only the board method is offered: PMs and emails are AutoPunish's own
	 * channels, switched on in its settings.
	 */
	public function find_users_for_notification($data, $options = [])
	{
		$users = $this->check_user_notification_options([(int) $data['user_id']], $options);

		foreach ($users as $user_id => $methods)
		{
			$users[$user_id] = array_values(array_intersect($methods, ['notification.method.board']));
			if (empty($users[$user_id]))
			{
				unset($users[$user_id]);
			}
		}

		return $users;
	}

	/**
	 * Worded at display time, in the reader's language. Notifications created
	 * before 1.2.0 carry their text already worded, and show it as it is.
	 */
	public function get_title()
	{
		$tpl = $this->get_data('notification_tpl');

		if ($tpl === null)
		{
			$text = $this->get_data('notification_text') ?: '';
			return $text ?: $this->language->lang($this->get_title_lang_key());
		}

		if ($tpl === '')
		{
			return $this->language->lang($this->get_title_lang_key());
		}

		$this->language->add_lang('common', 'biochaos/autopunish');
		[$key, $count] = duration::lang_args((int) $this->get_data('duration_seconds'));

		$vars = (array) $this->get_data('placeholders');
		$vars['DURATION'] = $this->language->lang($key, $count);

		foreach ($vars as $name => $value)
		{
			$tpl = str_replace('{' . $name . '}', $value, $tpl);
		}

		return $tpl;
	}

	public function get_reference()
	{
		return '';
	}

	public function get_url()
	{
		return '';
	}

	public function get_email_template()
	{
		return false;
	}

	public function get_email_template_variables()
	{
		return [];
	}

	public function get_avatar()
	{
		return '';
	}

	public function users_to_query()
	{
		return [];
	}

	public function create_insert_array($data, $pre_create_data = [])
	{
		$this->set_data('notification_tpl',  (string) ($data['notification_tpl'] ?? ''));
		$this->set_data('placeholders',      $data['placeholders'] ?? []);
		$this->set_data('duration_seconds',  (int) ($data['duration_seconds'] ?? 0));
		$this->set_data('offense_number',    $data['offense_number'] ?? 0);
		$data['notification_time'] = time();
		return parent::create_insert_array($data, $pre_create_data);
	}
}
