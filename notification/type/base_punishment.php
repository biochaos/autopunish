<?php
/**
 * AutoPunish extension for phpBB.
 *
 * @copyright (c) 2026, biochaos
 * @license GNU General Public License, version 2 (GPL-2.0)
 */

namespace biochaos\autopunish\notification\type;

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

	public function find_users_for_notification($data, $options = [])
	{
		return [(int) $data['user_id'] => ['notification.method.board' => 'notification.method.board']];
	}

	public function get_title()
	{
		$text = $this->get_data('notification_text') ?: '';
		return $text ?: $this->language->lang($this->get_title_lang_key());
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
		$this->set_data('notification_text', $data['notification_text'] ?? '');
		$this->set_data('offense_number',    $data['offense_number'] ?? 0);
		$data['notification_time'] = time();
		return parent::create_insert_array($data, $pre_create_data);
	}
}
