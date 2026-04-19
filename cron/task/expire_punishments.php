<?php
/**
 * AutoPunish extension for phpBB.
 *
 * @copyright (c) 2026, biochaos
 * @license GNU General Public License, version 2 (GPL-2.0)
 */

namespace biochaos\autopunish\cron\task;

class expire_punishments extends \phpbb\cron\task\base
{
	/** @var \phpbb\config\config */
	private $config;

	/** @var \biochaos\autopunish\service\punishment_manager */
	private $punishment_manager;

	public function __construct(
		\phpbb\config\config $config,
		\biochaos\autopunish\service\punishment_manager $punishment_manager
	)
	{
		$this->config             = $config;
		$this->punishment_manager = $punishment_manager;
	}

	public function get_name()
	{
		return 'biochaos.autopunish.cron.expire_punishments';
	}

	public function run()
	{
		$this->punishment_manager->expire_punishments();
		$this->config->set('autopunish_cron_last_run', time(), false);
	}

	public function is_runnable()
	{
		return (bool) $this->config['autopunish_enabled'];
	}

	public function should_run()
	{
		$interval     = max(1, (int) $this->config['autopunish_cron_interval']) * 60;
		$last_run     = (int) ($this->config['autopunish_cron_last_run'] ?? 0);
		return (time() - $last_run) >= $interval;
	}
}
