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
                                                                                                                                                    
use JDownloads\Component\JDownloads\Administrator\Helper\JDownloadsHelper;
                                                                          
/**
 * Supports an HTML select list of articles
 * @since 1.6
 */
class JDFeaturedIconField extends FormField
{
     /**
     * The form field type.
     *
     * @var string
     * @since 1.6
     */
     protected $type = 'JDFeaturedIcon';

     protected function getInput()
     {
        $params = ComponentHelper::getParams( 'com_jdownloads' );
        $symbol = $params->get( 'featured_pic' );
        $width  = $params->get( 'featured_pic_size' );
        $height = $params->get( 'featured_pic_size_height' );

        // create icon select box for the symbol
        $pic_dir = '/images/jdownloads/featuredimages/';
        $pic_dir_path = Uri::root().'images/jdownloads/featuredimages/';
        $pic_files = Folder::files( JPATH_SITE.$pic_dir );
        $pic_list[] = HtmlHelper::_('select.option', '', Text::_('COM_JDOWNLOADS_SELECT_FEATURED_SYMBOL'));
        foreach ($pic_files as $file) {
            if (@preg_match( "/(gif|jpg|png)/i", $file )){
                $pic_list[] = HtmlHelper::_('select.option',  $file );
            }
        } 

        // use the default icon when is selected in configuration
        $pic_default = '';
        $pic_default = $this->form->getValue('featured_pic');
        if ($symbol && !$pic_default) {
            $pic_default = $symbol;
        }    

        $inputbox_pic = HtmlHelper::_('select.genericlist', $pic_list, 'featured_pic', "class=\"inputbox\" size=\"1\""
        . " onchange=\"javascript:if (document.adminForm.featured_pic.options[selectedIndex].value!='') {document.imagelib.src='$pic_dir_path' + document.adminForm.featured_pic.options[selectedIndex].value} else {document.imagelib.src=''}\"", 'value', 'text', $pic_default );
          
        $inputbox_pic .= '<script language="javascript" type="text/javascript">'."
                if (document.adminForm.featured_pic.options.value != ''){
                    jsimg='".Uri::root().'images/jdownloads/featuredimages/'."' + getSelectedText( 'adminForm', 'featured_pic' );
                } else {
                    jsimg='';
                }
                document.write('<img src=' + jsimg + ' name=\"imagelib\" width=\"".$width."\" height=\"".$height."\" border=\"1\" alt=\"".Text::_('COM_JDOWNLOADS_BACKEND_SETTINGS_DEFAULT_CAT_FILE_NO_DEFAULT_PIC')."\" />');
               </script>";

        return $inputbox_pic;
    
     }  
}    
     
?>