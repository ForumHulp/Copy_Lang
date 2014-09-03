<?php
/**
*
* @package Copy Lang
* @copyright (c) 2014 ForumHulp.com
* @license http://opensource.org/licenses/gpl-2.0.php GNU General Public License v2
*
*/

/**
* @package module_install
*/

namespace forumhulp\copylang\acp;

class copy_lang_info
{
	function module()
	{
		return array(
			'filename'	=> '\forumhulp\copylang\acp\copy_lang_module',
			'title'		=> 'ACP_COPY_LANG',
			'version'	=> '3.1.0',
			'modes'     => array('index' => array('title' => 'ACP_COPY_LANG', 'auth' => 'acl_a_language', 'cat' => array('ACP_LANGUAGE')),
			),
		);
	}
}
