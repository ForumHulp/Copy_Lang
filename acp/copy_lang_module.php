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
	var $u_action;

	function main($id, $mode)
	{
		global $config, $db, $user, $auth, $template, $cache;
		global $phpbb_root_path, $phpbb_admin_path, $request, $phpEx;

		$new_ary = $iso = array();

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

if (empty($lang) || !is_array($lang))
{
	$lang = array();
}

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

			$from_iso = request_var('language_from', '');
			$to_iso = request_var('language_to', '');
			$this->main_files = filelist($phpbb_root_path . 'store/' . $from_iso, '', $phpEx);

			$dir = $phpbb_root_path . 'store/language/' . $to_iso . '/';
			mkdir($dir, 0777, true);

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

					$header = str_replace(array('{FILENAME}', '{LANG_NAME}', '{CHANGED}', '{AUTHOR}'),
								array($file, $to_iso, date('Y-m-d', time()), 'ForumHulp.com'), $this->language_file_header);
					$fp = @fopen($filename, 'wb');
					$header .= $this->lang_header;
					fwrite($fp, $header);

					$this->language_file = $file;
					$this->language_directory = $phpbb_root_path . 'store/' . $from_iso . '/' . (($key != '') ? $key . '/' : '');
					include($this->language_directory . $this->language_file);

					$entry_value = $lang;
					unset($lang);

					$this->language_copy_directory = $phpbb_root_path . 'store/' . $to_iso . '/' . (($key != '') ? $key . '/' : '');
					if (file_exists($this->language_copy_directory . $this->language_file))
					{
						include($this->language_copy_directory . $this->language_file);
						$copy_lang = $lang;
					} else
					{
						$copy_lang = array();
					}
					unset($lang);

					$tpl = $this->language_entries($entry_value, $copy_lang, '');
					fwrite($fp, $tpl);

				/*	foreach ($entry_value as $key => $value)
					{
						$entry = $this->format_lang_array(htmlspecialchars_decode($key), (isset($copy_lang[$key]) ? htmlspecialchars_decode($copy_lang[$key]) : ''));
						fwrite($fp, $entry);
					}
*/
					$footer = "));\n\n?>";
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

				$or_lang = $lang;
				unset($lang);

				$to_iso = request_var('language_to', '');
				$this->language_copy_directory = $phpbb_root_path . 'store/' . $to_iso . '/';
				if (file_exists($this->language_copy_directory . $this->language_file))
				{
					include($this->language_copy_directory . $this->language_file);
					$copy_lang = $lang;
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
}
