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

\defined( '_JEXEC' ) or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Filesystem\File;
use Joomla\CMS\Filesystem\Folder;
use Joomla\CMS\Form\Field;
use Joomla\CMS\Form\FormField;
use Joomla\CMS\Form\FormHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\HTML\HTMLHelper;

use JDownloads\Component\JDownloads\Administrator\Helper\JDownloadsHelper;

/**
 * Supports an HTML select list of articles
 * @since 1.6
 */
class JDFileIconField extends FormField
{
     /**
     * The form field type.
     *
     * @var string
     * @since 1.6
     */
     protected $type = 'JDFileIcon';

     protected function getInput()
     {
        $params = ComponentHelper::getParams('com_jdownloads');
        
        // Path to the mime type image folder (for file symbols) 
        switch ($params->get('selected_file_type_icon_set'))
        {
            case 1:
                $file_pic_folder = 'images/jdownloads/fileimages/';
                $file_pic_default_filename = $params->get('file_pic_default_filename');
                break;
            case 2:
                $file_pic_folder = 'images/jdownloads/fileimages/flat_1/';
                $file_pic_default_filename = $params->get('file_pic_default_filename1');
                break;
            case 3:
                $file_pic_folder = 'images/jdownloads/fileimages/flat_2/';
                $file_pic_default_filename = $params->get('file_pic_default_filename2');
                break;
        }
        
        // Create icon select box for file (mime) symbol        
        $pic_dir = '/'.$file_pic_folder;
        $pic_dir_path = Uri::root().$file_pic_folder;
        $pic_files = Folder::files( JPATH_SITE.$pic_dir );
        $pic_list[] = HtmlHelper::_('select.option', '', Text::_('COM_JDOWNLOADS_BACKEND_SETTINGS_FRONTEND_FPIC_TEXT'));
        foreach ($pic_files as $file) {
            if (@preg_match( "/(gif|jpg|png)/i", $file )){
                $pic_list[] = HtmlHelper::_('select.option',  $file );
            }
        } 
        
        // Use the default icon when is selected in params (and use symbols is active and we have a new Download)
        $pic_default = '';
        $pic_default = $this->form->getValue('file_pic');
        
        if ($pic_default === NULL){
            $pic_default = $this->value;
        }
        
        if ($params->get('use_file_type_symbols') && $file_pic_default_filename && !$pic_default && !$this->form->getValue('id')) {
            $pic_default = $file_pic_default_filename;
        }    
      
        $inputbox_pic = HtmlHelper::_('select.genericlist', $pic_list, 'file_pic', "class=\"form-select form-select-color-state form-select-success valid form-control-success\" size=\"1\" aria-invalid=\"false\""
      . " onchange=\"javascript:if (document.adminForm.file_pic.options[selectedIndex].value!='') {document.imagelib.src='$pic_dir_path' + document.adminForm.file_pic.options[selectedIndex].value} else {document.imagelib.src=''}\"", 'value', 'text', $pic_default );
          
        return $inputbox_pic;
    }  
}    
     
?>