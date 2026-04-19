<?php
/**
 * AutoPunish extension for phpBB.
 *
 * @copyright (c) 2026, biochaos
 * @license GNU General Public License, version 2 (GPL-2.0)
 */

namespace biochaos\autopunish\acp;

class main_info
{
	public function module()
	{
		return [
			'filename' => '\biochaos\autopunish\acp\main_module',
			'title'    => 'ACP_AUTOPUNISH',
			'modes'    => [
				'settings' => [
					'title' => 'ACP_AUTOPUNISH_SETTINGS',
					'auth'  => 'ext_biochaos/autopunish && acl_a_board',
					'cat'   => ['ACP_AUTOPUNISH'],
				],
				'tiers' => [
					'title' => 'ACP_AUTOPUNISH_TIERS',
					'auth'  => 'ext_biochaos/autopunish && acl_a_board',
					'cat'   => ['ACP_AUTOPUNISH'],
				],
			],
		];
	}
}
