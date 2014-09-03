<?php
/**
*
* @package Copy Lang
* @copyright (c) 2014 ForumHulp.com
* @license http://opensource.org/licenses/gpl-2.0.php GNU General Public License v2
*
*/

namespace forumhulp\copylang\event;

/**
* @ignore
*/
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
* Event listener
*/
class listener implements EventSubscriberInterface
{
    /* @var \phpbb\controller\helper */
    protected $helper;
  
    /**
    * Constructor
    *
    * @param \phpbb\controller\helper    $helper        Controller helper object
    */
    public function __construct(\phpbb\controller\helper $helper)
    {
        $this->helper = $helper;
    }

    static public function getSubscribedEvents()
    {
        return array(
			'core.user_setup'					=> 'load_language_on_setup'
		);
    }

	public function load_language_on_setup($event)
	{
		$lang_set_ext = $event['lang_set_ext'];
		$lang_set_ext[] = array(
			'ext_name' => 'forumhulp/copy_lang',
			'lang_set' => 'copy_lang_common',
		);
		$event['lang_set_ext'] = $lang_set_ext;
	}
}
