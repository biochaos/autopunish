<?php
/**
 * AutoPunish extension for phpBB.
 *
 * @copyright (c) 2026, biochaos
 * @license GNU General Public License, version 2 (GPL-2.0)
 */

namespace biochaos\autopunish\service;

/**
 * Fills the {NAME} placeholders of a notification text. The bell notification
 * and the PM and email are worded in different places, from different language
 * sources, and share this, so they cannot disagree about it.
 */
class placeholders
{
	/**
	 * @param string $text A text containing placeholders such as {USERNAME}
	 * @param array  $vars Placeholder name (without braces) => value
	 * @return string
	 */
	public static function replace($text, array $vars)
	{
		$pairs = [];
		foreach ($vars as $name => $value)
		{
			$pairs['{' . $name . '}'] = (string) $value;
		}

		// One pass: a value that happens to contain "{REASON}" is left as it is,
		// where replacing one placeholder after another would fill it in.
		return strtr((string) $text, $pairs);
	}
}
