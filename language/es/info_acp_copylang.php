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
	'ACP_COPY_LANG' 			=> 'Copiar paquete de idioma',
	'LANGUAGE_FILES'			=> 'Archivos de idioma',
	'LANGUAGE_PACK'				=> 'Pauqte de idioma',
	'LANGUAGE_KEY'				=> 'Clave de idioma',
	'LANGUAGE_KEY_FROM'			=> 'Claves de idioma desde',
	'LANGUAGE_VARIABLE_FROM'	=> 'Variable de idioma desde',
	'LANGUAGE_VARIABLE_TO'		=> 'Variable de idioma a',
	'ADD_LANGPACK'				=> 'Añadir paquete de idioma',

	'ACP_COPY_LANG_EXPLAIN'		=> 'Copiar paquete de idioma en la carpeta store de un idioma a otro. Esta función copia sólo los parámetros del paquete “desde”, al paquete “a”. Así que su nuevo paquete de idioma tiene los parámetros del “paquete desde”, y los valores traducidos del “paquete a”. El nuevo paquete es almacenado en store/language/{nombre del paquete}.',
	'FH_HELPER_NOTICE'			=> '¡La aplicación Forumhulp helper no existe!<br />Descargar <a href="https://github.com/ForumHulp/helper" target="_blank">forumhulp/helper</a> y copie la carpeta helper dentro de la carpeta de extensión forumhulp.',
	'COPYLANG_NOTICE'			=> '<div class="phpinfo"><p class="entry">Está extensión reside en %1$s » %2$s » %3$s.</p></div>',
));

// Description of Donations extension
$lang = array_merge($lang, array(
	'DESCRIPTION_PAGE'		=> 'Descripción',
	'DESCRIPTION_NOTICE'	=> 'Nota de la extensión',
	'ext_details' => array(
		'details' => array(
			'DESCRIPTION_1'	=> 'Combinar dos paquetes de idiomas',
			'DESCRIPTION_2'	=> 'Traducir',
			'DESCRIPTION_3'	=> 'Muestra los archivos que faltan',
		),
		'note' => array(
			'NOTICE_1'		=> 'Preparado para phpBB 3.2'
		)
	)
));
