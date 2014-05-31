<?php
/**
*
* @package Copy Lang
* @copyright (c) 2014 ForumHulp.com
* @license http://opensource.org/licenses/gpl-2.0.php GNU General Public License v2
*
*/

namespace forumhulp\copy_lang\migrations;

class install_copy_lang extends \phpbb\db\migration\migration
{
	public function effectively_installed()
	{
		return isset($this->config['copy_lang_version']) && version_compare($this->config['copy_lang_version'], '3.1.0', '>=');
	}

	static public function depends_on()
	{
		 return array('\phpbb\db\migration\data\v310\dev');
	}

	public function update_data()
	{
		return array(
			array('module.add', array(
				'acp',
				'ACP_LANGUAGE',
				array(
					'module_basename'	=> '\forumhulp\copy_lang\acp\copy_lang_module',
					'module_langname'	=> 'ACP_COPY_LANG',
					'module_mode'		=> 'index'
				)
			)),

			array('config.add', array('copy_lang_version', '3.1.0')),
		);
	}
}
