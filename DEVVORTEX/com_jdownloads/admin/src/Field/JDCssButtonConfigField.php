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
use Joomla\CMS\Form\Field\ListField;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;
use Joomla\CMS\Component\ComponentHelper;


class JDCssButtonConfigField extends ListField
{
     /**
     * The form field type.
     *
     * @var string
     */
     protected $type = 'JDCssButtonConfig';

     protected function getInput()
     {
        $document = Factory::getDocument();
        $document->addStyleSheet('../components/com_jdownloads/assets/css/jdownloads_buttons.css');
        $document->addStyleSheet('../administrator/components/com_jdownloads/assets/css/style.css');
        
        $hint = $this->element['hint'];
        if ($hint){
            $hint = '<p>'.Text::_($hint).'</p>';
        }

        $type = $this->element['csstype'];
                
        switch ($type) {
            case 'colors':
                 $buttons = $hint;
                 $buttons .= '<span class="jdbutton jblack jmedium">'.Text::_('COM_JDOWNLOADS_BUTTON_BLACK').'</span> <span class="jdbutton jwhite jmedium">'.Text::_('COM_JDOWNLOADS_BUTTON_WHITE').'</span> <span class="jdbutton jgray jmedium">'.Text::_('COM_JDOWNLOADS_BUTTON_GRAY').'</span> <span class="jdbutton jorange jmedium">'.Text::_('COM_JDOWNLOADS_BUTTON_ORANGE').'</span> <span class="jdbutton jred jmedium">'.Text::_('COM_JDOWNLOADS_BUTTON_RED').'</span>'
                            .'<span class="jdbutton jblue jmedium">'.Text::_('COM_JDOWNLOADS_BUTTON_BLUE').'</span> <span class="jdbutton jgreen jmedium">'.Text::_('COM_JDOWNLOADS_BUTTON_GREEN').'</span> <span class="jdbutton jrosy jmedium">'.Text::_('COM_JDOWNLOADS_BUTTON_ROSY').'</span> <span class="jdbutton jpink jmedium">'.Text::_('COM_JDOWNLOADS_BUTTON_PINK').'</span>';
            break;
            
            case 'status':
                 $buttons = $hint;
                 $buttons .= '<span class="jdbutton jblack jstatus">'.Text::_('COM_JDOWNLOADS_BUTTON_BLACK').'</span> <span class="jdbutton jwhite jstatus">'.Text::_('COM_JDOWNLOADS_BUTTON_WHITE').'</span> <span class="jdbutton jgray jstatus">'.Text::_('COM_JDOWNLOADS_BUTTON_GRAY').'</span> <span class="jdbutton jorange jstatus">'.Text::_('COM_JDOWNLOADS_BUTTON_ORANGE').'</span> <span class="jdbutton jred jstatus">'.Text::_('COM_JDOWNLOADS_BUTTON_RED').'</span>'
                            .'<span class="jdbutton jblue jstatus">'.Text::_('COM_JDOWNLOADS_BUTTON_BLUE').'</span> <span class="jdbutton jgreen jstatus">'.Text::_('COM_JDOWNLOADS_BUTTON_GREEN').'</span> <span class="jdbutton jrosy jstatus">'.Text::_('COM_JDOWNLOADS_BUTTON_ROSY').'</span> <span class="jdbutton jpink jstatus">'.Text::_('COM_JDOWNLOADS_BUTTON_PINK').'</span>';
            break;
            
            case 'sizes':
                 $buttons = $hint;
                 $buttons .= '<span class="jdbutton jred">'.Text::_('COM_JDOWNLOADS_BUTTON_STANDARD').'</span> <span class="jdbutton jred jmedium">'.Text::_('COM_JDOWNLOADS_BUTTON_MEDIUM').'</span>'
                             .'<span class="jdbutton jred jsmall">'.Text::_('COM_JDOWNLOADS_BUTTON_SMALL').'</span>';
            break;
         }

        $list = parent::getInput();
        $list .= '<p>'.$buttons.'</p>';
        
        return $list;    
    }  
}    
     
?>