<?php
/**
*
* @package Copy Lang
* @copyright (c) 2014 ForumHulp.com
* @license http://opensource.org/licenses/gpl-2.0.php GNU General Public License v2
*
*/

namespace forumhulp\copylang;

/**
* Main extension class for this extension.
*/
class ext extends \phpbb\extension\base
{
	public function is_enableable()
	{
		if (!class_exists('forumhulp\helper\helper'))
		{
			$this->container->get('user')->add_lang_ext('forumhulp/copylang', 'info_acp_copylang');
			trigger_error($this->container->get('user')->lang['FH_HELPER_NOTICE'], E_USER_WARNING);
		}

		if (!$this->container->get('ext.manager')->is_enabled('forumhulp/helper'))
		{
			$this->container->get('ext.manager')->enable('forumhulp/helper');
		}

		return class_exists('forumhulp\helper\helper');
	}

	function enable_step($old_state)
	{
		switch ($old_state)
		{
			case '': // Empty means nothing has run yet
			if (empty($old_state))
			{
			//	$this->config->increment('assets_version', 1);

				$this->container->get('user')->add_lang_ext('forumhulp/copylang', 'info_acp_copylang');
				$this->container->get('template')->assign_var('L_EXTENSION_ENABLE_SUCCESS', $this->container->get('user')->lang['EXTENSION_ENABLE_SUCCESS'] .
					(isset($this->container->get('user')->lang['COPYLANG_NOTICE']) ?
						sprintf($this->container->get('user')->lang['COPYLANG_NOTICE'],
								$this->container->get('user')->lang['ACP_CAT_CUSTOMISE'],
								$this->container->get('user')->lang['ACP_LANGUAGE'],
								$this->container->get('user')->lang['ACP_COPY_LANG']) : ''));
			}

				// Run parent enable step method
				$this->cache	= $this->container->get('cache.driver');
				$this->cache->purge();
				return parent::enable_step($old_state);

			break;

			default:

				// Run parent enable step method
				return parent::enable_step($old_state);

			break;
		}
	}
}
