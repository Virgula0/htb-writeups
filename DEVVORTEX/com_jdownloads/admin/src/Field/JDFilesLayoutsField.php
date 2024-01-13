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
use Joomla\CMS\Form\Field\ListField;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;

/**
 * Form Field class for the Joomla Framework.
 *
 * @package		Joomla.Administrator
 */
class JDFilesLayoutsField extends ListField
{
	/**
	 * The form field type.
	 *
	 * @var		string
	 */
	protected $type = 'JDFilesLayouts';

	/**
	 * Method to get the field options.
	 *
	 * @return	array	The field option objects.
	 * @since	1.6
	 */
	protected function getOptions()
	{
		$options = array();
        
		$db		= Factory::getDbo();
		$query	= $db->getQuery(true);

        $db->setQuery('SELECT template_name  FROM #__jdownloads_templates WHERE template_typ = 2');
        
        // Check for a database error.
        try
            {
                $rows = $db->loadObjectList();
            }
            catch (\RuntimeException $e)
            {
                Factory::getApplication()->enqueueMessage($e->getMessage(), 'error');
                return false;
            }

        foreach ($rows as $row) {
            $options[] = HtmlHelper::_('select.option', $row->template_name, $row->template_name);
        }

		return $options;
	}
}