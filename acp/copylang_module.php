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
		global $user, $config, $template, $request, $cache, $phpbb_root_path, $phpbb_container, $phpEx;

		$iso = array();
		$action = ($request->is_set_post('download')) ? 'download' : (($request->is_set_post('add_file')) ? 'upload' : $request->variable('action', ''));
	//	$user->add_lang_ext('forumhulp/copylang', 'info_acp_copylang');
		$this->tpl_name = 'acp_copy_lang';

		$download = false;

		$plupload = $phpbb_container->get('plupload');
		$form_enctype = (@ini_get('file_uploads') == '0' || strtolower(@ini_get('file_uploads')) == 'off') ? '' : ' enctype="multipart/form-data"';
		$max_filesize = @ini_get("upload_max_filesize");

		if (!empty($max_filesize))
		{
			$unit = strtolower(substr($max_filesize, -1, 1));
			$max_filesize = (int) $max_filesize;

			switch ($unit)
			{
				case 'g':
					$max_filesize *= 1024;
				case 'm':
					$max_filesize *= 1024;
				case 'k':
					$max_filesize *= 1024;
			}
		}
		$plupload->configure($cache, $template, $this->u_action, 0, 1);
		$template->assign_vars(array(
			'FILESIZE' 			=>  $max_filesize,
			'S_FORM_ENCTYPE_CL'	=> $form_enctype,
			'S_ATTACH_DATA'		=> json_encode(array())
			));

		switch ($action)
		{
			case 'download':
				include($phpbb_root_path . 'includes/functions_compress.' . $phpEx);
				$compress = new \compress;
				$method = 'zip';
				$archive_filename['physical_filename'] = 'update_languageset_' . time() . '_' . uniqid();
				$file_name = $phpbb_root_path . 'store/' . $archive_filename['physical_filename'] . '.' . $method;

				$compress = new \compress_zip('w', $file_name);
				$compress->add_file('store/language/' .  $request->variable('language_to', '') . '/', 'store/language/');
				$compress->close();

				$mimetype = 'application/zip;';
				$name = $archive_filename['physical_filename'] . '.zip';

				header('Cache-Control: private, no-cache');
				header("Content-Type: $mimetype; name=\"$name\"");
				header("Content-disposition: attachment; filename=$name");

				@set_time_limit(0);
				$fp = @fopen($file_name, 'rb');
				if ($fp !== false)
				{
					while (!feof($fp))
					{
						echo fread($fp, 8192);
					}
					fclose($fp);
				}
				flush();

				$this->delete_files($phpbb_root_path . 'store/language/');
				@unlink($file_name);

				exit();
			break;

			case 'details':
				$phpbb_container->get('forumhulp.helper')->detail('forumhulp/copylang');
				$this->tpl_name = 'acp_ext_details';
			break;

			case 'upload':
				$this->upload_language();

			default:
				$iso_ary = $this->findIso();
				if (sizeof($iso_ary))
				{
					$s_lang_options = $s_lang_copy_to = '<option value="" class="sep">' . $user->lang['LANGUAGE_PACK'] . '</option>';
					foreach ($iso_ary as $iso => $value)
					{
						$selected = (htmlspecialchars($value['iso']) == $request->variable('language_from', '')) ? ' selected="selected"' : '';
						$selected_copy_to = (htmlspecialchars($value['iso']) == $request->variable('language_to', '')) ? ' selected="selected"' : '';
						$s_lang_options .= '<option value="' . htmlspecialchars($value['iso']) . '"' . $selected . '>' . htmlspecialchars($value['local_name']) . '</option>';
						$s_lang_copy_to .= '<option value="' . htmlspecialchars($value['iso']) . '"' . $selected_copy_to . '>' . htmlspecialchars($value['local_name']) . '</option>';
					}
					$template->assign_vars(array('S_LANG_OPTIONS' =>  $s_lang_options, 'S_LANG_COPY_FROM' =>  $s_lang_copy_to));
				}
				$template->assign_vars(array('U_ACTION' =>  $this->u_action));

				if ($request->variable('language_from', '') && $request->variable('language_to', '') && $request->variable('submit', ''))
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
//
// Some characters you may want to copy&paste:
// ’ » “ ” …
//';

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
}';
				$from_iso = $request->variable('language_from', '');
				$to_iso = $request->variable('language_to', '');
				$this->main_files = filelist($phpbb_root_path . 'store/' . $from_iso, '', $phpEx);

				$dir = $phpbb_root_path . 'store/language/' . $to_iso . '/';
				if (!@mkdir($dir, 0777, true))
				{
					$this->delete_files($phpbb_root_path . 'store/language/');
					!@mkdir($dir, 0777, true);
				}

				$iso = file("{$phpbb_root_path}store/$to_iso/iso.txt");

				$fp = @fopen($dir . 'iso.txt', 'wb');
				$header = $iso[1] . $iso[0] . "ForumHulp.com";
				fwrite($fp, $header);
				fclose($fp);

				foreach ($this->main_files as $key => $files)
				{
					if (is_array($files) && sizeof($files) && $key != '')
					{
						if (!@mkdir($dir . $key, 0777))
						{
							trigger_error("Could not create directory $dir . $key", E_USER_ERROR);
						}
						@chmod($dir . $key, 0777);
					}

					foreach ($files as $file)
					{
						$filename = $dir . $key . $file;
						$this->language_file = $file;
						$this->language_directory = $phpbb_root_path . 'store/' . $from_iso . '/' . (($key != '') ? $key . '/' : '');
						include($this->language_directory . $this->language_file);

						$langtype = isset($lang);
						$entry_value = (isset($lang) ? $lang : (isset($help) ? $help : (isset($words) ? $words : $synonyms)));

						$header = str_replace(array('{FILENAME}', '{LANG_NAME}', '{CHANGED}', '{AUTHOR}', '{FILETYPE}'),
									array($file, $to_iso, date('Y-m-d', time()), 'ForumHulp.com', (($langtype) ? $this->lang_type : '')), $this->language_file_header);
						$header .= (isset($lang) ? $this->lang_header : (isset($help) ? $this->help_header : (isset($words) ? $this->word_header : $this->synonyms_header)));
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

						$footer = ($langtype) ? "));\n" : ");\n";
						fwrite($fp, $footer);
						fclose($fp);
					}
				}
				$download = true;
			}

			if ($request->variable('language_from', '') && $request->variable('language_to', ''))
			{
				$from_iso = $request->variable('language_from', '');
				$this->main_files = filelist($phpbb_root_path . 'store/' . $from_iso, '', $phpEx);
				$this->copy_files = (array) filelist($phpbb_root_path . 'store/' . $request->variable('language_to', ''), '', $phpEx);
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
						$selected = ($key . $file == $request->variable('language_show', '')) ? ' selected="selected"' : '';
						$s_lang_option .= '<option value="' . (($key != '') ? $key : '') . $file . '"' . $selected . '>&nbsp;&nbsp;' . $file . '</option>';
						if (!in_array($file, (isset($this->copy_files[$key])) ? $this->copy_files[$key]: array()))
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
				$template->assign_vars(array('S_LANG_SHOW' =>  $s_lang_option, 'LANG_FROM' => $request->variable('language_from', ''),
											'LANG_TO' => $request->variable('language_to', ''), 'MISSING_FILES' => $missing_file, 'S_DOWNLOAD' => $download));

				if ($request->variable('language_show', ''))
				{
					$this->language_file = $request->variable('language_show', '');
					$this->language_directory = $phpbb_root_path . 'store/' . $from_iso . '/';
					include($this->language_directory . $this->language_file);

					$or_lang = (isset($lang) ? $lang : (isset($help) ? $help : (isset($words) ? $words : $synonyms)));
					unset($lang, $help, $words, $synonyms);
					$this->tabs($or_lang);
					$to_iso = $request->variable('language_to', '');
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

					$missing_vars = array_diff(array_keys($or_lang), array_keys($copy_lang));
					$template->assign_vars(array('S_MISSING' => true, 'MISSING_VARS' => implode('<br />', $missing_vars)));
					unset($tpl);
				}
			}

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
					<td class="row3" colspan="3">' . htmlspecialchars($key_prefix, ENT_COMPAT, 'UTF-8') . '<strong>' . htmlspecialchars($key, ENT_COMPAT, 'UTF-8') . '</strong></td>
				</tr>';

				foreach ($value as $_key => $_value)
				{
					if (is_array($_value))
					{
						// Write key
						$tpl .= '
							<tr>
								<td class="row3" colspan="3">' . htmlspecialchars($key_prefix, ENT_COMPAT, 'UTF-8') . '&nbsp; &nbsp;<strong>' . htmlspecialchars($_key, ENT_COMPAT, 'UTF-8') . '</strong></td>
							</tr>';

						foreach ($_value as $__key => $__value)
						{
							// Write key
							$tpl .= '
								<tr>
									<td class="row1">' . htmlspecialchars($key_prefix, ENT_COMPAT, 'UTF-8') . '<strong>' . htmlspecialchars($__key, ENT_COMPAT, 'UTF-8') . '</strong></td>
									<td class="row2">';

							$tpl .= $__value;

							$tpl .= '</td>
							<td class="row2" valign="top">';

							$tpl .= (isset($copy_lang[$key][$_key][$__key])) ? htmlspecialchars($copy_lang[$key][$_key][$__key], ENT_COMPAT, 'UTF-8') : '';

							$tpl .= '</td>
								</tr>';
						}
					}
					else
					{
						// Write key
						$tpl .= '
							<tr>
								<td class="row1">' . htmlspecialchars($key_prefix, ENT_COMPAT, 'UTF-8') . '<strong>' . htmlspecialchars($_key, ENT_COMPAT, 'UTF-8') . '</strong></td>
								<td class="row2">';

						$tpl .= $_value;

						$tpl .= '</td>

								<td class="row2">';

						$tpl .= (isset($copy_lang[$key][$_key])) ? htmlspecialchars($copy_lang[$key][$_key], ENT_COMPAT, 'UTF-8') : '';

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
					<td class="row1">' . htmlspecialchars($key_prefix, ENT_COMPAT, 'UTF-8') . '<strong>' . htmlspecialchars($key, ENT_COMPAT, 'UTF-8') . '</strong></td>
					<td class="row2">';

				$tpl .= $value;

				$tpl .= '</td>
					
					<td class="row2">';

				$tpl .= (isset($copy_lang[$key])) ? htmlspecialchars($copy_lang[$key], ENT_COMPAT, 'UTF-8') : '';

				$tpl .= '</td>
					</tr>';
			}
		}

		return $tpl;
	}


	function tabs($lang_key)
	{
		$maxName = 0;
		$maxTags = array();
		foreach ($lang_key as $key => $value)
		{
			if (strlen($key) > $maxName)
			{
				$maxName = strlen($key);
			}
			$maxTags[$key] = strlen($key);
		}
		$maxName = (ceil($maxName / 4) * 4);

		foreach ($maxTags as $key => $value)
		{
			$maxTags[$key] = (($maxName - $value) / 4);
		}
		return $maxTags;
	}

	/**
	* Print language entries
	*/
	function language_entries(&$lang_ary, $copy_lang, $key_prefix = '')
	{
		$tpl = '';
$tabs = $this->tabs($lang_ary);

		foreach ($lang_ary as $key => $value)
		{
			if (is_array($value))
			{
				// Write key
				$tpl .= (is_numeric($key)) ? "\t" . 'array(' . "\n" : str_repeat("\t", $tabs[$key]) . '\'' . htmlspecialchars($key, ENT_COMPAT, 'UTF-8') . '\' => array(' . "\n";

				foreach ($value as $_key => $_value)
				{
					if (is_array($_value))
					{
						// Write key
						$tpl .= "\t\t" . (is_numeric($_key) ? $_key : '\'' . htmlspecialchars($_key, ENT_COMPAT, 'UTF-8') . '\'') . ' => array(' . "\n";

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
	* Delete all files in directory
	*
	* @param string $dir Directory to remove directory
	* @return bool True on success, false on error
	*/
	protected function delete_files($dirname = '')
	{
		$result = true;

		$dp = @opendir($dirname);

		if ($dp)
		{
			while (($file = readdir($dp)) !== false)
			{
				if ($file == '.' || $file == '..')
				{
					continue;
				}
				$filename = $dirname . '/' . $file;
				if (is_dir($filename))
				{
					if (!$this->delete_files($dirname . '/' . $file))
					{
						$result = false;
					}
				}
				else
				{
					if (!@unlink($filename))
					{
						$result = false;
					}
				}
			}
			closedir($dp);
		}
		if (!@rmdir($dirname))
		{
			return false;
		}

		return $result;
	}

	protected function findIso()
	{
		global $phpbb_root_path;
		$iso_ary = array();
		$dp = @opendir($phpbb_root_path . 'store');
		if ($dp)
		{
			while (($file = readdir($dp)) !== false)
			{
				if ($file[0] == '.' || !is_dir($phpbb_root_path . 'store/' . $file))
				{
					continue;
				}

				if (file_exists($phpbb_root_path . 'store/' . $file . '/iso.txt'))
				{
					if ($iso = file($phpbb_root_path . 'store/' . $file . '/iso.txt'))
					{
						if (sizeof($iso) == 3)
						{
							$iso_ary[$file] = array(
								'iso'		=> $file,
								'name'		=> trim($iso[0]),
								'local_name'=> trim($iso[1]),
								'author'	=> trim($iso[2])
							);
						}
					}
				}
			}
		}
		closedir($dp);
		return $this->array_sort($iso_ary, 'name');
	}

	protected function array_sort($array, $on, $order = SORT_ASC)
	{
		$new_array = array();
		$sortable_array = array();

		if (sizeof($array) > 0)
		{
			foreach ($array as $k => $v)
			{
				if (is_array($v))
				{
					foreach ($v as $k2 => $v2)
					{
						if ($k2 == $on)
						{
							$sortable_array[$k] = $v2;
						}
					}
				} else
				{
					$sortable_array[$k] = $v;
				}
			}

			switch ($order)
			{
				case SORT_ASC:
					asort($sortable_array);
				break;
				case SORT_DESC:
					arsort($sortable_array);
				break;
			}

			foreach ($sortable_array as $k => $v)
			{
				$new_array[$k] = $array[$k];
			}
		}
		return $new_array;
	}

	protected function getIso($dir)
	{
		global $phpbb_root_path;
		try
		{
			$iterator = new \RecursiveIteratorIterator(
				new \phpbb\recursive_dot_prefix_filter_iterator(
					new \RecursiveDirectoryIterator(
						$dir,
						\FilesystemIterator::SKIP_DOTS
					)
				),
				\RecursiveIteratorIterator::LEAVES_ONLY
			);
		}
		catch (\Exception $e)
		{
			return array();
		}

		foreach ($iterator as $file_info)
		{
			if ($file_info->getFilename() == 'iso.txt')
			{
				return str_replace(array(DIRECTORY_SEPARATOR, $phpbb_root_path), array('/', ''), $file_info->getPath()) . '/';
			}
		}

		return false;
	}

	protected function upload_language()
	{
		global $user, $config, $plupload, $request, $phpbb_root_path, $phpbb_container, $phpEx;
		$error = array();
		$this->attachment_data = array();

		if (version_compare($config['version'], '3.2.*', '<'))
		{
			include(phpbb_root_path . 'includes/functions_upload.' . $phpEx);
			$upload = new \fileupload();
			$upload->set_allowed_extensions(array('zip'));
		} else
		{
			$upload = $phpbb_container->get('files.factory')->get('upload')
				->set_error_prefix('AVATAR_')
				->set_allowed_extensions(array('zip'))
				->set_max_filesize(0)
				->set_allowed_dimensions(0,0,0,0)
				->set_disallowed_content((isset($config['mime_triggers']) ? explode('|', $config['mime_triggers']) : false));
		}

		$user->add_lang('posting');
		$upload_dir = $phpbb_root_path . 'store/temp/';
		if (!file_exists($upload_dir))
		{
			mkdir($upload_dir, 0777, true);
		}
		$file = (version_compare($config['version'], '3.2.*', '<')) ? $upload->form_upload('fileupload') : $upload->handle_upload('files.types.form', 'fileupload');
		$file->clean_filename('real');
		$file->move_file(str_replace($phpbb_root_path, '', $upload_dir), true, true, 0775);

		$download_url = $upload_dir . $file->get('realname');
		$new_entry = array(
			'attach_id'		=> rand(1000,10000),
			'is_orphan'		=> 1,
			'real_filename'	=> $file->get('realname'),
			'filesize'		=> $file->get('filesize'),
		);

		if (!class_exists('\compress_zip'))
		{
			include($phpbb_root_path . 'includes/functions_compress.' . $phpEx);
		}
		$zip = new \compress_zip('r', $download_url);
		$zip->extract($upload_dir . 'lang/');
		$zip->close();

		$iso = $this->getIso($upload_dir . 'lang/');
		if (!$iso)
		{
			$this->attachment_data = array_merge(array($new_entry['attach_id'] => $new_entry), $this->attachment_data);
			if (isset($plupload) && $plupload->is_active())
			{
				if ($request->is_ajax())
				{
					$json_response = new \phpbb\json_response();
					$json_response->send(array('data' => $this->attachment_data, 'download_url' => $download_url, 'iso' => $iso));
				}
			}
		} else
		{
			$this->rcopy($phpbb_root_path . $iso, $phpbb_root_path . 'store/' . basename($iso));
			$file->remove();
			$this->delete_files($upload_dir);

			$iso = $this->findIso();
			if ($request->is_ajax())
			{
				$json_response = new \phpbb\json_response();
				$json_response->send(array('iso' =>  $iso));
			}
		}
	}

	// Function to copy folders and files
	protected function rcopy($src, $dst)
	{
		if (file_exists($dst))
		{
			if (!($this->rrmdir($dst)))
			{
				$this->trigger_error($user->lang['NO_UPLOAD_FILE'], E_USER_WARNING);
				return false;
			}
		}
		if (is_dir($src))
		{
			$this->recursive_mkdir($dst, 0755);
			$files = @scandir($src);
			if ($files === false)
			{
				$this->trigger_error($user->lang['NO_UPLOAD_FILE'], E_USER_WARNING);
				return false;
			}
			foreach ($files as $file)
			{
				if ($file != '.' && $file != '..')
				{
					if (!($this->rcopy($src . '/' . $file, $dst . '/' . $file)))
					{
						return false;
					}
				}
			}
		}
		else if (file_exists($src))
		{
			if (!(@copy($src, $dst)))
			{
				$this->trigger_error($user->lang['NO_UPLOAD_FILE'], E_USER_WARNING);
				return false;
			}
		}
		return true;
	}

	protected function recursive_mkdir($path, $mode = 0755)
	{
		$dirs = explode('/', $path);
		$count = sizeof($dirs);
		$path = '.';
		for ($i = 0; $i < $count; $i++)
		{
			$path .= '/' . $dirs[$i];

			if (!is_dir($path))
			{
				@mkdir($path, $mode);
				@chmod($path, $mode);

				if (!is_dir($path))
				{
					return false;
				}
			}
		}
		return true;
	}

	// Function to remove folders and files
	function rrmdir($dir, $no_errors = true)
	{
		if (is_dir($dir))
		{
			$files = @scandir($dir);
			if ($files === false)
			{
				$this->trigger_error($user->lang['NO_UPLOAD_FILE'], E_USER_WARNING);
				return false;
			}
			foreach ($files as $file)
			{
				if ($file != '.' && $file != '..')
				{
					$no_errors = $this->rrmdir($dir . '/' . $file, $no_errors);
				}
			}
			rmdir($dir);
		}
		else if (file_exists($dir))
		{
			if (!(@unlink($dir)))
			{
				$this->trigger_error($user->lang['NO_UPLOAD_FILE'], E_USER_WARNING);
				return false;
			}
		}
		return $no_errors;
	}
}
