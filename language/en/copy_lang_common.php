<?php
/**
*
* @package Copy Lang
* @copyright (c) 2014 ForumHulp.com
* @license http://opensource.org/licenses/gpl-2.0.php GNU General Public License v2
*
*/

if (!defined('IN_PHPBB'))
{
	exit;
}

if (empty($lang) || !is_array($lang))
{
	$lang = array();
}

$lang = array_merge($lang, array(
	'ACP_COPY_LANG' 	=> 'Copy language pack',
	'LANGUAGE_FILES'	=> 'Language files',
	'LANGUAGE_PACK'	=> 'Language pack',

	'ACP_COPY_LANG_EXPLAIN'	=> 'Copy language pack in store folder from one language to another. This function copies only parameters from the "from" pack to the "to" pack.'
));
