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
use Joomla\CMS\Form\FormHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Form\Field\FilelistField;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Uri\Uri;


/**
 * Supports an HTML select list of articles
 * @since 1.6
 */
class JDFeaturedIconConfigField extends FileListField
{
     /**
     * The form field type.
     *
     * @var string
     * @since 1.6
     */
     protected $type = 'JDFeaturedIconConfig';

     protected function getInput()
     {
        $params = ComponentHelper::getParams( 'com_jdownloads' );
        $width  = $params->get( 'featured_pic_size' );
        $height = $params->get( 'featured_pic_size_height' );

        $pic_dir_path = Uri::root().'images/jdownloads/featuredimages/';
      
        // add javascript
        $this->onchange = "javascript:if (document.adminForm.jform_featured_pic_filename.options[selectedIndex].value != '-1') {document.imagelib3.src='$pic_dir_path' + document.adminForm.jform_featured_pic_filename.options[selectedIndex].value} else {document.imagelib3.src=''}";
        
        // create icon select box for the symbol
        $pic_list = parent::getInput();
        
        // add javascript
        $pic_list .= '<script language="javascript" type="text/javascript">'."
            if (document.adminForm.jform_featured_pic_filename.options.value != '-1'){
                jsimg=\"".Uri::root().'images/jdownloads/featuredimages/'."\" + getSelectedText( 'adminForm', 'jform_featured_pic_filename' );
            } else {
                jsimg='';
            }
            document.write('<p style=\"margin-top:15px;\"><img src=' + jsimg + ' name=\"imagelib3\" width=\"".$width."\" height=\"".$height."\" border=\"1\" alt=\"\" /></p>');
           </script>";
        
        return $pic_list;    
    }  
}    
     
?>