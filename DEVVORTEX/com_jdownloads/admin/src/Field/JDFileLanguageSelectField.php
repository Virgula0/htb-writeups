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
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Form\FormHelper;
use Joomla\CMS\HTML;
use Joomla\CMS\Form\Field;
use Joomla\CMS\Form\Field\ListField;
use Joomla\CMS\Component\ComponentHelper;

use JDownloads\Component\JDownloads\Administrator\Helper\JDownloadsHelper;
use JDownloads\Component\JDownloads\Site\Helper\JDHelper;


/**
 * Form Field class for the Joomla Framework.
 *
 * @package		Joomla.Administrator
 * @since		1.6
 */
class JDFileLanguageSelectField extends ListField
{
	/**
	 * The form field type.
	 *
	 * @var		string
	 * @since	1.6
	 */
	protected $type = 'JDFileLanguageSelect';

	/**
	 * Method to get the field options.
	 *
	 * @return	array	The field option objects.
	 * @since	1.6
	 */
	protected function getOptions()
	{
		$params = ComponentHelper::getParams('com_jdownloads');
        
        $app = Factory::getApplication();
        
        if ($app->isClient('administrator')){
            HTMLHelper::addIncludePath(JPATH_COMPONENT_ADMINISTRATOR . '/helpers');        
        } else {
            HTMLHelper::addIncludePath(JPATH_COMPONENT . '/helpers');        
        }    
		
		// Initialise variables.
		$options = array();
        $file_lang_values = '';

        // build file language listbox 
        if ($app->isClient('administrator')){
            $file_lang_values = explode(',' , JDownloadsHelper::getOnlyLanguageSubstring($params->get('language_list')));
        } else {
            $file_lang_values = explode(',' , JDHelper::getOnlyLanguageSubstring($params->get('language_list')));
        }    
        
        for ($i=0; $i < count($file_lang_values); $i++) {
            $options[] = HtmlHelper::_('select.option',  $i, $file_lang_values[$i] );
        }
		
        return $options;
	}
}