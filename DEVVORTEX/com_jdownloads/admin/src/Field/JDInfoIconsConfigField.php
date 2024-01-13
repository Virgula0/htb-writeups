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
use Joomla\CMS\Uri\Uri;

class JDInfoIconsConfigField extends TextField
{
     /**
     * The form field type.
     *
     * @var string
     * @since 1.6
     */
     protected $type = 'JDInfoIconsConfig';

     protected function getInput()
     {
        $params = ComponentHelper::getParams( 'com_jdownloads' );
        $msize  = $params->get( 'info_icons_size' );

        $text = parent::getInput();
         
        $sample_path = Uri::root().'images/jdownloads/miniimages/'; 
        $sample_pic = '<img src="'.$sample_path.'date.png" align="middle" width="'.$msize.'" height="'.$msize.'" border="0" alt="" /> ';
        $sample_pic .= '<img src="'.$sample_path.'language.png" align="middle" width="'.$msize.'" height="'.$msize.'" border="0" alt="" /> ';
        $sample_pic .= '<img src="'.$sample_path.'weblink.png" align="middle" width="'.$msize.'" height="'.$msize.'" border="0" alt="" />';
        $sample_pic .= '<img src="'.$sample_path.'stuff.png" align="middle" width="'.$msize.'" height="'.$msize.'" border="0" alt="" /> ';
        $sample_pic .= '<img src="'.$sample_path.'contact.png" align="middle" width="'.$msize.'" height="'.$msize.'" border="0" alt="" /> ';
        $sample_pic .= '<img src="'.$sample_path.'system.png" align="middle" width="'.$msize.'" height="'.$msize.'" border="0" alt="" />';
        $sample_pic .= '<img src="'.$sample_path.'currency.png" align="middle" width="'.$msize.'" height="'.$msize.'" border="0" alt="" /> ';
        $sample_pic .= '<img src="'.$sample_path.'download.png" align="middle" width="'.$msize.'" height="'.$msize.'" border="0" alt="" />';
        $text .='<p style="margin-top:15px;">'.$sample_pic.'</p>';
                                         
        return $text;
    }  
}    
?>