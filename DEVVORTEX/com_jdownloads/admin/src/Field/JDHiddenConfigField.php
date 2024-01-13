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
use Joomla\CMS\Form\Field\HiddenField;
use Joomla\CMS\Component\ComponentHelper;

class JDHiddenConfigField extends HiddenField
{
     /**
     * The form field type.
     *
     * @var string
     */
     protected $type = 'JDHiddenConfig';
     
     protected function getInput()
     {
         $params = ComponentHelper::getParams( 'com_jdownloads' );
         $name = $this->element['name'];
        
         switch ($name[0]){
            case 'root_dir':
                if ($params->get( 'files_uploaddir' )){
                    $default = $params->get( 'files_uploaddir' );
                } else {
                    $default = JPATH_ROOT.'/'.'jdownloads';
                }
                break;
            case 'preview_dir':
                if ($params->get( 'preview_files_folder_name' )){
                    $default = $params->get( 'preview_files_folder_name' );
                } else {
                    $default = '_preview_files';
                }
                break;
            case 'temp_dir':
                if ($params->get( 'tempzipfiles_folder_name' )){
                    $default = $params->get( 'tempzipfiles_folder_name' );
                } else {
                    $default = '_tempzipfiles';
                }
                break;
            case 'help_url':
                $default = 'https://www.jdownloads.net/help-server/{language}/';
                break;
         }
         
         $this->value = $default;
         
         $text = parent::getInput();
         
         return $text;
    }
    
}    
?>