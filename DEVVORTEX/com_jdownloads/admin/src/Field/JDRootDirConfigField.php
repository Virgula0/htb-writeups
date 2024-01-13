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
use Joomla\CMS\Form\Field\TextField;
use Joomla\CMS\Component\ComponentHelper;

class JDRootDirConfigField extends TextField
{
     /**
     * The form field type.
     *
     * @var string
     */
     protected $type = 'JDRootDirConfig';

     protected function getInput()
     {
        $params = ComponentHelper::getParams( 'com_jdownloads' );
        $path  = $params->get( 'files_uploaddir' );

        if (!$path){
            $this->value = JPATH_ROOT.'/jdownloads';
        }
        
        $text = parent::getInput();
         
        return $text;
    }  
}    
?>