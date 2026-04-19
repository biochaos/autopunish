<?php
/**
 * AutoPunish extension for phpBB.
 *
 * @copyright (c) 2026, biochaos
 * @license GNU General Public License, version 2 (GPL-2.0)
 */

namespace biochaos\autopunish\event;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class main_listener implements EventSubscriberInterface
{
	/** @var \phpbb\config\config */
	private $config;

	/** @var \biochaos\autopunish\service\punishment_manager */
	private $punishment_manager;

	/** @var \biochaos\autopunish\service\tier_manager */
	private $tier_manager;

	/** @var \phpbb\template\template */
	private $template;

	/** @var \phpbb\user */
	private $user;

	/** @var \phpbb\request\request */
	private $request;

	public function __construct(
		\phpbb\config\config $config,
		\biochaos\autopunish\service\punishment_manager $punishment_manager,
		\biochaos\autopunish\service\tier_manager $tier_manager,
		\phpbb\template\template $template,
		\phpbb\user $user,
		\phpbb\request\request_interface $request
	)
	{
		$this->config             = $config;
		$this->punishment_manager = $punishment_manager;
		$this->tier_manager       = $tier_manager;
		$this->template           = $template;
		$this->user               = $user;
		$this->request            = $request;
	}

	public static function getSubscribedEvents()
	{
		return [
			'core.user_setup'             => 'on_user_setup',
			'core.mcp_warn_user_after'    => 'on_warn_user',
			'core.mcp_warn_post_after'    => 'on_warn_user',
			'core.acp_users_mode_add'     => 'on_acp_users_mode_add',
		];
	}

	/**
	 * Load our language file during user setup so strings are available
	 * before any module's main() runs (needed for the acp_users mode dropdown).
	 */
	public function on_user_setup($event)
	{
		$lang_set_ext   = $event['lang_set_ext'];
		$lang_set_ext[] = ['ext_name' => 'biochaos/autopunish', 'lang_set' => 'common'];
		$event['lang_set_ext'] = $lang_set_ext;
	}

	// -------------------------------------------------------------------------

	public function on_warn_user($event)
	{
		$user_data = $event['user_row'] ?? null;

		if (!$this->config['autopunish_enabled'])
		{
			return;
		}

		if (!$user_data || empty($user_data['user_id']))
		{
			return;
		}

		$this->punishment_manager->handle_warning($user_data);
	}

	// -------------------------------------------------------------------------

	public function on_acp_users_mode_add($event)
	{
		if ($event['mode'] !== 'autopunish')
		{
			return;
		}

		$user_id  = (int) $event['user_id'];
		$u_action = $event['u_action'];

		\add_form_key('acp_autopunish_user');

		// Handle form submissions
		if ($this->request->is_set_post('end_punishment'))
		{
			if (!\check_form_key('acp_autopunish_user'))
			{
				trigger_error('FORM_INVALID', E_USER_WARNING);
			}
			$punishment_id  = $this->request->variable('punishment_id', 0);
			$step_down      = $this->request->variable('step_down', 0);
			$active_offense = $this->request->variable('active_offense_number', 0);
			$this->punishment_manager->end_punishment_early($punishment_id, $this->user->data['user_id']);
			if ($step_down && $active_offense > 1)
			{
				$this->punishment_manager->apply_stepdown_punishment($user_id, $active_offense - 1);
			}
			trigger_error($this->user->lang('ACP_AUTOPUNISH_PUNISHMENT_ENDED') . adm_back_link($u_action . '&u=' . $user_id));
		}

		if ($this->request->is_set_post('save_commutation'))
		{
			if (!\check_form_key('acp_autopunish_user'))
			{
				trigger_error('FORM_INVALID', E_USER_WARNING);
			}
			$reduce_by       = max(0, $this->request->variable('reduce_offenses', 0));
			$cur_commuted    = $this->punishment_manager->get_commuted_offenses($user_id);
			$this->punishment_manager->set_commuted_offenses($user_id, $cur_commuted + $reduce_by, $this->user->data['user_id']);
			trigger_error($this->user->lang('ACP_AUTOPUNISH_COMMUTATION_SAVED') . adm_back_link($u_action . '&u=' . $user_id));
		}

		// Load punishment data
		$active       = $this->punishment_manager->get_active_punishment($user_id);
		$history      = $this->punishment_manager->get_punishment_history($user_id);
		$total        = $this->punishment_manager->get_total_punishments($user_id);
		$commuted     = $this->punishment_manager->get_commuted_offenses($user_id);
		$next_offense = $this->punishment_manager->get_next_offense_number($user_id);
		$next_tier    = $this->tier_manager->get_tier_for_offense($next_offense);
		$all_tiers    = $this->tier_manager->get_all_tiers();
		$is_repeating = $next_offense > count($all_tiers);

		$this->template->assign_vars([
			'S_AUTOPUNISH_MODE'      => true,
			'USER_WARNINGS'          => $event['user_row']['user_warnings'],
			'TOTAL_PUNISHMENTS'      => $total,
			'COMMUTED_OFFENSES'      => $commuted,
			'EFFECTIVE_OFFENSES'     => max(0, $total - $commuted),
			'NEXT_OFFENSE_NUMBER'    => $next_offense,
			'NEXT_TIER_ACTION'       => $next_tier ? $next_tier['action'] : '',
			'NEXT_TIER_REASON'       => $next_tier ? $next_tier['reason_text'] : '',
			'NEXT_TIER_THRESHOLD'    => $next_tier ? $next_tier['warning_threshold'] : '',
			'S_IS_REPEATING'         => $is_repeating,
			'S_HAS_ACTIVE'           => (bool) $active,
			'ACTIVE_PUNISHMENT_ID'   => $active ? $active['punishment_id'] : 0,
			'ACTIVE_OFFENSE_NUMBER'  => $active ? $active['offense_number'] : 0,
			'S_CAN_STEP_DOWN'        => $active && $active['offense_number'] > 1,
			'ACTIVE_ACTION'          => $active ? $active['action'] : '',
			'ACTIVE_REASON'          => $active ? $active['reason_text'] : '',
			'ACTIVE_END_TIME'        => ($active && $active['end_time']) ? $this->user->format_date($active['end_time']) : '',
			'USER_ID'                => $user_id,
		]);

		foreach ($history as $p)
		{
			$this->template->assign_block_vars('punishment_history', [
				'OFFENSE_NUMBER' => $p['offense_number'],
				'ACTION'         => $p['action'],
				'GROUP_ID'       => $p['group_id'],
				'REASON'         => $p['reason_text'],
				'START_TIME'     => $p['start_time'] ? $this->user->format_date($p['start_time']) : '',
				'END_TIME'       => $p['end_time'] ? $this->user->format_date($p['end_time']) : $this->user->lang('AUTOPUNISH_PERMANENT'),
				'ACTIVE'         => $p['active'],
			]);
		}
	}
}
