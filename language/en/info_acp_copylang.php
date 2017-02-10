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
	'ACP_COPY_LANG' 			=> 'Copy language pack',
	'LANGUAGE_FILES'			=> 'Language files',
	'LANGUAGE_PACK'				=> 'Language pack',
	'LANGUAGE_KEY'				=> 'Language key',
	'LANGUAGE_KEY_FROM'			=> 'Language keys from',
	'LANGUAGE_VARIABLE_FROM'	=> 'Language variable from',
	'LANGUAGE_VARIABLE_TO'		=> 'Language variable to',
	'ADD_LANGPACK'				=> 'Add language pack',

	'ACP_COPY_LANG_EXPLAIN'		=> 'Copy language pack in store folder from one language to another. This function copies only parameters from the "from" pack to the "to" pack. So your new language pack has the parameters of the "from pack" and the translated values of the "to pack". The new language pack is stored in store/language/{pack name}.',
	'FH_HELPER_NOTICE'			=> 'Forumhulp helper application does not exist!<br />Download <a href="https://github.com/ForumHulp/helper" target="_blank">forumhulp/helper</a> and copy the helper folder to your forumhulp extension folder.',
	'COPYLANG_NOTICE'			=> '<div class="phpinfo"><p class="entry">This extension resides in %1$s » %2$s » %3$s.</p></div>',
));

// Description of Donations extension
$lang = array_merge($lang, array(
	'DESCRIPTION_PAGE'		=> 'Description',
	'DESCRIPTION_NOTICE'	=> 'Extension note',
	'ext_details' => array(
		'details' => array(
			'DESCRIPTION_1'	=> 'Merge two languagepack',
			'DESCRIPTION_2'	=> 'Translate',
			'DESCRIPTION_3'	=> 'Shows missing files',
		),
		'note' => array(
			'NOTICE_1'		=> 'phpBB 3.2 ready'
		)
	)
));
