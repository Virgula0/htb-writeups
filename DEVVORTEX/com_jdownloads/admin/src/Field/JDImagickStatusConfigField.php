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

class JDImagickStatusConfigField extends NoteField
{
     /**
     * The form field type.
     *
     * @var string
     */
     protected $type = 'JDImagickStatusConfig';
     
     protected $hiddenLabel = false;

     protected function getLabel()
     {
        $text = parent::getLabel();
        
        if (extension_loaded('imagick')){
            $text .= '<div><span class="badge bg-success">'.Text::_('COM_JDOWNLOADS_BACKEND_IMAGICK_STATE_ON').'</span></div>';
        } else {
            $text .= '<div><span class="badge bg-danger">'.Text::_('COM_JDOWNLOADS_BACKEND_IMAGICK_STATE_OFF').'</span></div>';
        }
        
        return $text;
    }  
}    
?>