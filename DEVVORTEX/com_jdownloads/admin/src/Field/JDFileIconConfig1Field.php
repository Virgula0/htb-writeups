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
use Joomla\CMS\Language\Text;
use Joomla\CMS\Form\Field\FilelistField;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Uri\Uri;

/**
 * Supports an HTML select list of articles
 * @since 1.6
 */
class JDFileIconConfig1Field extends FileListField
{
     /**
     * The form field type.
     *
     * @var string
     * @since 1.6
     */
     protected $type = ' JDFileIconConfig1';

     protected function getInput()
     {
     
        $params = ComponentHelper::getParams( 'com_jdownloads' );
        $width  = $params->get( 'file_pic_size' );
        $height = $params->get( 'file_pic_size_height' );        
         
        $pic_dir_path = Uri::root().'images/jdownloads/fileimages/flat_1/';
        
        // add javascript
        $this->onchange = "javascript:if (document.adminForm.jform_file_pic_default_filename1.options[selectedIndex].value != '-1') {document.imagelib21.src='$pic_dir_path' + document.adminForm.jform_file_pic_default_filename1.options[selectedIndex].value} else {document.imagelib21.src=''}";
        
        // create icon select box for the symbol
        $pic_list = parent::getInput();
        
        // add javascript
        $pic_list .= '<script language="javascript" type="text/javascript">'."
            if (document.adminForm.jform_file_pic_default_filename1.options.value != '-1'){
                jsimg=\"".Uri::root().'images/jdownloads/fileimages/flat_1/'."\" + getSelectedText( 'adminForm', 'jform_file_pic_default_filename1' );
            } else {
                jsimg='';
            }
            document.write('<p style=\"margin-top:15px;\"><img src=' + jsimg + ' name=\"imagelib21\" width=\"".$width."\" height=\"".$height."\" border=\"1\" alt=\"\" /></p>');
           </script>";
        
        return $pic_list;
    }  
}    
     
?>