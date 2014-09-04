<?php
/**
*
* @package Copy Lang
* @copyright (c) 2014 ForumHulp.com
* @license http://opensource.org/licenses/gpl-2.0.php GNU General Public License v2
*
*/

namespace forumhulp\copylang\acp;

class copylang_module
{
	public $u_action;

	function main($id, $mode)
	{
		global $db, $config, $user, $cache, $template, $request, $phpbb_root_path, $phpbb_extension_manager, $phpbb_container, $phpEx;

		$new_ary = $iso = array();
		$action = $request->variable('action', '');

		switch ($action)
		{
			case 'details':

			$user->add_lang(array('install', 'acp/extensions', 'migrator'));
			$ext_name = 'forumhulp/copylang';
			$md_manager = new \phpbb\extension\metadata_manager($ext_name, $config, $phpbb_extension_manager, $template, $user, $phpbb_root_path);
			try
			{
				$this->metadata = $md_manager->get_metadata('all');
			}
			catch(\phpbb\extension\exception $e)
			{
				trigger_error($e, E_USER_WARNING);
			}

			$md_manager->output_template_data();

			try
			{
				$updates_available = $this->version_check($md_manager, $request->variable('versioncheck_force', false));

				$template->assign_vars(array(
					'S_UP_TO_DATE'		=> empty($updates_available),
					'S_VERSIONCHECK'	=> true,
					'UP_TO_DATE_MSG'	=> $user->lang(empty($updates_available) ? 'UP_TO_DATE' : 'NOT_UP_TO_DATE', $md_manager->get_metadata('display-name')),
				));

				foreach ($updates_available as $branch => $version_data)
				{
					$template->assign_block_vars('updates_available', $version_data);
				}
			}
			catch (\RuntimeException $e)
			{
				$template->assign_vars(array(
					'S_VERSIONCHECK_STATUS'			=> $e->getCode(),
					'VERSIONCHECK_FAIL_REASON'		=> ($e->getMessage() !== $user->lang('VERSIONCHECK_FAIL')) ? $e->getMessage() : '',
				));
			}

			$template->assign_vars(array(
				'U_BACK'				=> $this->u_action . '&amp;action=list',
			));

			$this->tpl_name = 'acp_ext_details';
		break;

		default:
			$dp = @opendir("{$phpbb_root_path}store");
			if ($dp)
			{
				while (($file = readdir($dp)) !== false)
				{
					if ($file[0] == '.' || !is_dir($phpbb_root_path . 'store/' . $file))
					{
						continue;
					}

					if (file_exists("{$phpbb_root_path}store/$file/iso.txt"))
					{
						if ($iso = file("{$phpbb_root_path}store/$file/iso.txt"))
						{
							if (sizeof($iso) == 3)
							{
								$new_ary[$file] = array(
									'iso'		=> $file,
									'name'		=> trim($iso[0]),
									'local_name'=> trim($iso[1]),
									'author'	=> trim($iso[2])
								);
							}
						}
					}
				}
				closedir($dp);
			}

			if (sizeof($new_ary))
			{
				$s_lang_options = $s_lang_copy_to = '<option value="" class="sep">' . $user->lang['LANGUAGE_PACK'] . '</option>';
				foreach ($new_ary as $iso => $lang_ary)
				{
					$template->assign_block_vars('languages', array(
						'ISO'			=> htmlspecialchars($lang_ary['iso']),
						'LOCAL_NAME'	=> htmlspecialchars($lang_ary['local_name'], ENT_COMPAT, 'UTF-8'),
						'NAME'			=> htmlspecialchars($lang_ary['name'], ENT_COMPAT, 'UTF-8'))
					);
					$selected = (htmlspecialchars($lang_ary['iso']) == request_var('language_from', '')) ? ' selected="selected"' : '';
					$selected_copy_to = (htmlspecialchars($lang_ary['iso']) == request_var('language_to', '')) ? ' selected="selected"' : '';
					$s_lang_options .= '<option value="' . htmlspecialchars($lang_ary['iso']) . '"' . $selected . '>' . htmlspecialchars($lang_ary['local_name']) . '</option>';
					$s_lang_copy_to .= '<option value="' . htmlspecialchars($lang_ary['iso']) . '"' . $selected_copy_to . '>' . htmlspecialchars($lang_ary['local_name']) . '</option>';
				}
				$template->assign_vars(array('S_LANG_OPTIONS' =>  $s_lang_options, 'S_LANG_COPY_FROM' =>  $s_lang_copy_to));
			}
			$template->assign_vars(array('U_ACTION' =>  $this->u_action));

			if (request_var('language_from', '') && request_var('language_to', '') && request_var('submit', ''))
			{
			$this->language_file_header = '<?php
/**
*
* {FILENAME} [{LANG_NAME}]
*
* @package language
* @copyright (c) ' . date('Y') . ' phpBB Group
* @author {CHANGED} - {AUTHOR}
* @license http://opensource.org/licenses/gpl-2.0.php GNU General Public License v2
*
*/

/**
* DO NOT CHANGE
*/
if (!defined(\'IN_PHPBB\'))
{
	exit;
}

{FILETYPE}
// DEVELOPERS PLEASE NOTE
//
// All language files should use UTF-8 as their encoding and the files must not contain a BOM.
//
// Placeholders can now contain order information, e.g. instead of
// \'Page %s of %s\' you can (and should) write \'Page %1$s of %2$s\', this allows
// translators to re-order the output of data while ensuring it remains correct
//
// You do not need this where single placeholders are used, e.g. \'Message %d\' is fine
// equally where a string contains only two placeholders which are used to wrap text
// in a url you again do not need to specify an order e.g., \'Click %sHERE%s\' is fine
';

		$this->lang_header = '
$lang = array_merge($lang, array(
';
		$this->help_header = '
$help = array(
';
		$this->word_header = '
$words = array(
';
		$this->synonyms_header = '
$synonyms = array(
';
		$this->lang_type = '
if (empty($lang) || !is_array($lang))
{
	$lang = array();
}

';
				$from_iso = request_var('language_from', '');
				$to_iso = request_var('language_to', '');
				$this->main_files = filelist($phpbb_root_path . 'store/' . $from_iso, '', $phpEx);

				$dir = $phpbb_root_path . 'store/language/' . $to_iso . '/';
				@mkdir($dir, 0777, true);

				$iso = file("{$phpbb_root_path}store/$to_iso/iso.txt");

				$fp = @fopen($dir . 'iso.txt', 'wb');
				$header = $iso[1] . $iso[0] . "ForumHulp.com";
				fwrite($fp, $header);
				fclose($fp);

				foreach ($this->main_files as $key => $files)
				{
					if (is_array($files) && sizeof($files) && $key != '')
					{
						$dir = $dir . $key;
						if (!@mkdir($dir, 0777))
						{
							trigger_error("Could not create directory $dir", E_USER_ERROR);
						}
						@chmod($dir, 0777);
					}

					foreach ($files as $file)
					{
						$filename = $dir . $file;
						$this->language_file = $file;
						$this->language_directory = $phpbb_root_path . 'store/' . $from_iso . '/' . (($key != '') ? $key . '/' : '');
						include($this->language_directory . $this->language_file);

						$langtype = isset($lang);
						$entry_value = (isset($lang) ? $lang : (isset($help) ? $help : (isset($words) ? $words : $synonyms)));

						$header = str_replace(array('{FILENAME}', '{LANG_NAME}', '{CHANGED}', '{AUTHOR}', '{FILETYPE}'),
									array($file, $to_iso, date('Y-m-d', time()), 'ForumHulp.com', (($langtype) ? $this->lang_type : '')), $this->language_file_header);
						$this->lang_header = (isset($lang) ? $this->lang_header : (isset($help) ? $this->help_header : (isset($words) ? $this->word_header : $this->synonyms_header)));
						$header .= $this->lang_header;
						unset($lang, $help, $words, $synonyms);
						$fp = @fopen($filename, 'wb');
						fwrite($fp, $header);

						$this->language_copy_directory = $phpbb_root_path . 'store/' . $to_iso . '/' . (($key != '') ? $key . '/' : '');
						if (file_exists($this->language_copy_directory . $this->language_file))
						{
							include($this->language_copy_directory . $this->language_file);
							$copy_lang = (isset($lang) ? $lang : (isset($help) ? $help : (isset($words) ? $words : $synonyms)));
						} else
						{
							$copy_lang = array();
						}
						unset($lang, $help, $words, $synonyms);

						$tpl = $this->language_entries($entry_value, $copy_lang, '');
						fwrite($fp, $tpl);

					/*	foreach ($entry_value as $key => $value)
						{
							$entry = $this->format_lang_array(htmlspecialchars_decode($key), (isset($copy_lang[$key]) ? htmlspecialchars_decode($copy_lang[$key]) : ''));
							fwrite($fp, $entry);
						}
	*/
						$footer = ($langtype) ? "));\n" : ");\n";
						fwrite($fp, $footer);
						fclose($fp);
					}
				}
			}

			if (request_var('language_from', '') && request_var('language_to', ''))
			{
				$from_iso = request_var('language_from', '');
				$this->main_files = filelist($phpbb_root_path . 'store/' . $from_iso, '', $phpEx);
				$this->copy_files = filelist($phpbb_root_path . 'store/' . request_var('language_to', ''), '', $phpEx);
				$missing_files = array();
				$s_lang_option = '<option value="" class="sep">' . $user->lang['LANGUAGE_FILES'] . '</option>';
				foreach ($this->main_files as $key => $files)
				{
					if (is_array($files) && sizeof($files))
					{
						$s_lang_option .= '<option value="" class="sep">' . (($key != '') ? $key : 'common') . '</option>';
					}

					foreach ($files as $file)
					{
						$selected = ($key . $file == request_var('language_show', '')) ? ' selected="selected"' : '';
						$s_lang_option .= '<option value="' . (($key != '') ? $key : '') . $file . '"' . $selected . '>&nbsp;&nbsp;' . $file . '</option>';
						if (!in_array($file, $this->copy_files[$key]))
						{
							$missing_files[$key][] = $file;
						}
					}
				}
				$missing_file = '';

				foreach ($missing_files as $key => $files)
				{
					$missing_file .= $key . ' => ';
					foreach ($files as $file)
					{
						$missing_file .= $key.$file . ', ';
					}
					$missing_file .= '<br />';
				}
				$template->assign_vars(array('S_LANG_SHOW' =>  $s_lang_option, 'LANG_FROM' => request_var('language_from', ''),
											'LANG_TO' => request_var('language_to', ''), 'MISSING_FILES' => $missing_file));

				if (request_var('language_show', ''))
				{
					$this->language_file = request_var('language_show', '');
					$this->language_directory = $phpbb_root_path . 'store/' . $from_iso . '/';
					include($this->language_directory . $this->language_file);

					$or_lang = (isset($lang) ? $lang : (isset($help) ? $help : (isset($words) ? $words : $synonyms)));
					unset($lang, $help, $words, $synonyms);

					$to_iso = request_var('language_to', '');
					$this->language_copy_directory = $phpbb_root_path . 'store/' . $to_iso . '/';
					if (file_exists($this->language_copy_directory . $this->language_file))
					{
						include($this->language_copy_directory . $this->language_file);
						$copy_lang = (isset($lang) ? $lang : (isset($help) ? $help : (isset($words) ? $words : $synonyms)));
						unset($lang, $help, $words, $synonyms);
					} else
					{
						$copy_lang = array();
					}
					$tpl = $this->print_language_entries($or_lang, $copy_lang, '');

					$template->assign_var('TPL', $tpl);
					unset($tpl);
				}
			}

			$this->tpl_name = 'acp_copy_lang';
			$this->page_title = 'ACP_COPY_LANG';
		}
	}

	/**
	* Print language entries
	*/
	function print_language_entries(&$lang_ary, $copy_lang, $key_prefix = '', $input_field = false)
	{
		$tpl = '';

		foreach ($lang_ary as $key => $value)
		{
			if (is_array($value))
			{
				// Write key
				$tpl .= '
				<tr>
					<td class="row3" colspan="3" valign="top">' . htmlspecialchars($key_prefix, ENT_COMPAT, 'UTF-8') . '<strong>' . htmlspecialchars($key, ENT_COMPAT, 'UTF-8') . '</strong></td>
				</tr>';

				foreach ($value as $_key => $_value)
				{
					if (is_array($_value))
					{
						// Write key
						$tpl .= '
							<tr>
								<td class="row3" colspan="3" valign="top">' . htmlspecialchars($key_prefix, ENT_COMPAT, 'UTF-8') . '&nbsp; &nbsp;<strong>' . htmlspecialchars($_key, ENT_COMPAT, 'UTF-8') . '</strong></td>
							</tr>';

						foreach ($_value as $__key => $__value)
						{
							// Write key
							$tpl .= '
								<tr>
									<td class="row1" style="white-space: nowrap;" valign="top">' . htmlspecialchars($key_prefix, ENT_COMPAT, 'UTF-8') . '<strong>' . htmlspecialchars($__key, ENT_COMPAT, 'UTF-8') . '</strong></td>
									<td class="row2" valign="top">';

							$tpl .= $__value;

							$tpl .= '</td>
							<td class="row2" valign="top">';

							$tpl .= (isset($copy_lang[$key][$_key][$__key])) ? $copy_lang[$key][$_key][$__key] : '';

							$tpl .= '</td>
								</tr>';
						}
					}
					else
					{
						// Write key
						$tpl .= '
							<tr>
								<td class="row1" style="white-space: nowrap;" valign="top">' . htmlspecialchars($key_prefix, ENT_COMPAT, 'UTF-8') . '<strong>' . htmlspecialchars($_key, ENT_COMPAT, 'UTF-8') . '</strong></td>
								<td class="row2" valign="top">';

						$tpl .= $_value;

						$tpl .= '</td>

								<td class="row2" valign="top">';

						$tpl .= (isset($copy_lang[$key][$_key])) ? $copy_lang[$key][$_key] : '';

						$tpl .= '</td>
							</tr>';
					}
				}

				$tpl .= '
				<tr>
					<td class="spacer" colspan="3">&nbsp;</td>
				</tr>';
			}
			else
			{
				// Write key
				$tpl .= '
				<tr>
					<td class="row1" style="white-space: nowrap;" valign="top">' . htmlspecialchars($key_prefix, ENT_COMPAT, 'UTF-8') . '<strong>' . htmlspecialchars($key, ENT_COMPAT, 'UTF-8') . '</strong></td>
					<td class="row2" valign="top">';

				$tpl .= $value;

				$tpl .= '</td>
					
					<td class="row2" valign="top">';

				$tpl .= (isset($copy_lang[$key])) ? $copy_lang[$key] : '';

				$tpl .= '</td>
					</tr>';
			}
		}

		return $tpl;
	}

	/**
	* Print language entries
	*/
	function language_entries(&$lang_ary, $copy_lang, $key_prefix = '')
	{
		$tpl = '';

		foreach ($lang_ary as $key => $value)
		{
			if (is_array($value))
			{
				// Write key
				$tpl .= "\t" . '\'' . htmlspecialchars($key, ENT_COMPAT, 'UTF-8') . '\' => array(' . "\n";

				foreach ($value as $_key => $_value)
				{
					if (is_array($_value))
					{
						// Write key
						$tpl .= "\t\t" . '\'' . htmlspecialchars($_key, ENT_COMPAT, 'UTF-8') . '\' => array(' . "\n";

						foreach ($_value as $__key => $__value)
						{
							// Write key
							$tpl .= (isset($copy_lang[$key][$_key][$__key])) ? $this->format_lang_array(htmlspecialchars($__key, ENT_COMPAT, 'UTF-8'), $copy_lang[$key][$_key][$__key], "\t\t\t" ) : $this->format_lang_array( htmlspecialchars($__key, ENT_COMPAT, 'UTF-8'),'');
						}
						$tpl .= "\t\t" . '),' . "\n";
					}
					else
					{
						// Write key
						$tpl .= (isset($copy_lang[$key][$_key])) ? $this->format_lang_array(htmlspecialchars($_key, ENT_COMPAT, 'UTF-8'), $copy_lang[$key][$_key], "\t\t") : $this->format_lang_array(htmlspecialchars($_key, ENT_COMPAT, 'UTF-8'), '');
					}
				}
				$tpl .= "\t" . '),' . "\n";
			}
			else
			{
				// Write key
				$tpl .= (isset($copy_lang[$key])) ? $this->format_lang_array(htmlspecialchars($key, ENT_COMPAT, 'UTF-8'), $copy_lang[$key]) : $this->format_lang_array(htmlspecialchars($key, ENT_COMPAT, 'UTF-8'), '');
			}
		}
		return $tpl;
	}

	/**
	* Return language string value for storage
	*/
	function prepare_lang_entry($text, $store = true)
	{
		$text = (STRIP) ? stripslashes($text) : $text;

		// Adjust for storage...
		if ($store)
		{
			$text = str_replace("'", "\\'", str_replace('\\', '\\\\', $text));
		}

		return $text;
	}

	/**
	* Format language array for storage
	*/
	function format_lang_array($key, $value, $tabs = "\t")
	{
		$entry = '';

		if (!is_array($value))
		{
			$entry .= "{$tabs}'" . $this->prepare_lang_entry($key) . "'\t=> '" . $this->prepare_lang_entry($value) . "',\n";
		}
		else
		{
			$_tabs = $tabs . "\t";
			$entry .= "\n{$tabs}'" . $this->prepare_lang_entry($key) . "'\t=> array(\n";

			foreach ($value as $_key => $_value)
			{
				$entry .= $this->format_lang_array($_key, $_value, $_tabs);
			}

			$entry .= "{$tabs}),\n\n";
		}

		return $entry;
	}

	/**
	* Check the version and return the available updates.
	*
	* @param \phpbb\extension\metadata_manager $md_manager The metadata manager for the version to check.
	* @param bool $force_update Ignores cached data. Defaults to false.
	* @param bool $force_cache Force the use of the cache. Override $force_update.
	* @return string
	* @throws RuntimeException
	*/
	protected function version_check(\phpbb\extension\metadata_manager $md_manager, $force_update = false, $force_cache = false)
	{
		global $cache, $config, $user;
		$meta = $md_manager->get_metadata('all');

		if (!isset($meta['extra']['version-check']))
		{
			throw new \RuntimeException($this->user->lang('NO_VERSIONCHECK'), 1);
		}

		$version_check = $meta['extra']['version-check'];

		$version_helper = new \phpbb\version_helper($cache, $config, $user);
		$version_helper->set_current_version($meta['version']);
		$version_helper->set_file_location($version_check['host'], $version_check['directory'], $version_check['filename']);
		$version_helper->force_stability($config['extension_force_unstable'] ? 'unstable' : null);

		return $updates = $version_helper->get_suggested_updates($force_update, $force_cache);
	}
}
