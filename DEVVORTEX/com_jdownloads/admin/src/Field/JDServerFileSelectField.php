<?php
/**
 * @copyright    Copyright (C) 2005 - 2013 Open Source Matters, Inc. All rights reserved.
 * @license      GNU General Public License version 2 or later; see LICENSE.txt
 */

/**
 * @package jDownloads
 * @version 4.0  
 * @copyright (C) 2007 - 2022 - Arno Betz - www.jdownloads.com
 * @license http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.txt
 * 
 * jDownloads is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 */

namespace JDownloads\Component\JDownloads\Administrator\Field;

\defined('JPATH_PLATFORM') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Form\Field\ListField;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Filesystem\File;
use Joomla\CMS\Filesystem\Folder;

/**
 * Form Field class for the Joomla Framework.
 *
 * @package		Joomla.Administrator
 * @since		1.6
 */
class JDServerFileSelectField extends ListField
{
	/**
	 * The form field type.
	 *
	 * @var		string
	 * @since	1.6
	 */
	protected $type = 'JDServerFileSelect';

	/**
	 * Method to get the field options.
	 *
	 * @return	array	The field option objects.
	 * @since	1.6
	 */
	protected function getOptions()
	{
		$params = ComponentHelper::getParams('com_jdownloads');
        
		// Initialise variables.
		$update_files_list = array();
        $update_files = array();
        $update_list_title = '';

        $jinput = Factory::getApplication()->input;
        
        // new download clicked in manage files?
        if (($new_file_name =  $jinput->get('file', '', 'string') != '')) $new_file_from_list = true;
        
        // files list from upload root folder (for updates via ftp or create new from this list)
        $update_files = Folder::files( $params->get('files_uploaddir'), $filter= '.', $recurse=false, $fullpath=false, $exclude=array('index.htm', 'index.html', '.htaccess') );
        if ($update_files){
            $update_list_title = Text::_('COM_JDOWNLOADS_BACKEND_FILESEDIT_UPDATE_LIST_TITLE');
        } else {
            $update_list_title = Text::_('COM_JDOWNLOADS_BACKEND_FILESEDIT_NO_UPDATE_FILE_FOUND');
        }   
        $update_files_list[] = HtmlHelper::_('select.option', '0', $update_list_title);
        foreach ($update_files as $file) {
            $update_files_list[] = HtmlHelper::_('select.option', $file);
        }
        
        return $update_files_list;
	}
}