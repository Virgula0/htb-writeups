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
use Joomla\CMS\Form\Field\NoteField;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\Component\ComponentHelper;

class JDPlaceholderImageConfigField extends NoteField
{
     /**
     * The form field type.
     *
     * @var string
     */
     protected $type = 'JDPlaceholderImageConfig';

     protected function getInput()
     {
         $params = ComponentHelper::getParams( 'com_jdownloads' );
         $sizeh  = $params->get( 'thumbnail_size_height' );
         $sizew  = $params->get( 'thumbnail_size_width' );
         
         $image_path = Uri::root().'images/jdownloads/screenshots/thumbnails/no_pic.gif'; 
         
         $text = parent::getInput();
        
         $text .= '<p style="padding-top:5px;"><img src="'.$image_path.'" align="middle" width="'.$sizew.'" height="'.$sizeh.'" border="0" alt="" /> ';
        
         return $text;
    }  
}    
?>