<?php
/**
*
* @package Copy Lang
* @copyright (c) 2014 ForumHulp.com
* @license http://opensource.org/licenses/gpl-2.0.php GNU General Public License v2
*
*/

namespace forumhulp\copylang\migrations;

class install_copy_lang extends \phpbb\db\migration\migration
{
	static public function depends_on()
	{
		return array('\phpbb\db\migration\data\v310\gold');
	}

	public function update_data()
	{
		return array(
			array('module.add', array(
				'acp',
				'ACP_LANGUAGE',
				array(
					'module_basename'	=> '\forumhulp\copylang\acp\copylang_module',
					'module_langname'	=> 'ACP_COPY_LANG',
					'module_mode'		=> 'index'
				)
			)),
		);
	}
}
