<?php
/**
 * @copyright    Copyright (C) 2005 - 2013 Open Source Matters, Inc. All rights reserved.
 * @license      GNU General Public License version 2 or later; see LICENSE.txt
 */

/**
 * @package jDownloads
 * @version 4.0  
 * @copyright (C) 2007 - 2013 - Arno Betz - www.jdownloads.com
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

/**
 * Form Field class
 *
 */
class JDCategorySelectField extends ListField
{
	/**
	 * The form field type.
	 *
	 * @var		string
	 */
	protected $type = 'JDCategorySelect';

	/**
	 * Method to get the field options.
	 *
	 * @return	array	The field option objects.
	 */
	protected function getOptions()
	{
        // Initialise variables.
		$options = array();

        $app    = Factory::getApplication();
		$db		= Factory::getDbo();
		$query	= $db->getQuery(true);

		$query->select('a.id AS value, a.title AS text, a.level');
		$query->from('#__jdownloads_categories AS a');
        $query->join('LEFT', '`#__jdownloads_categories` AS b ON a.lft > b.lft AND a.rgt < b.rgt');

		$query->where('a.published IN (0,1)');
		$query->group('a.id');
		$query->order('a.lft ASC');

		// Get the options.
		$db->setQuery($query);

		// Check for a database error.
        try
            {
                $options = $db->loadObjectList();
            }
            catch (\RuntimeException $e)
            {
                Factory::getApplication()->enqueueMessage($e->getMessage(), 'error');
                return false;
            }
        
        // Pad the option text with spaces using depth level as a multiplier.
        for ($i = 0, $n = count($options); $i < $n; $i++)
        {
            // Translate ROOT
            if ($options[$i]->level == 0) {
                $root = array_shift($options); 
                $i++;
                $n--;
            } else {
                if ($options[$i]->level > 1){
                    $options[$i]->text = str_repeat('- ',($options[$i]->level -1)).$options[$i]->text;
                }
            }
        }

        // Initialise variables.
        $user = $app->getIdentity();

        if (empty($id)) {
            // New item, only have to check core.create.
            foreach ($options as $i => $option)
            {
                // Unset the option if the user isn't authorised for it.
                if (!$user->authorise('core.create', 'com_jdownloads.category.'.$option->value)) {
                    unset($options[$i]);
                }
            }
        }
        else {
            // Existing item is a bit more complex. Need to account for core.edit and core.edit.own.
            foreach ($options as $i => $option)
            {
                // Unset the option if the user isn't authorised for it.
                if (!$user->authorise('core.edit', 'com_jdownloads.category.'.$option->value)) {
                    // As a backup, check core.edit.own
                    if (!$user->authorise('core.edit.own', 'com_jdownloads.category.'.$option->value)) {
                        // No core.edit nor core.edit.own - bounce this one
                        unset($options[$i]);
                    }
                    else {
                        // TODO I've got a funny feeling we need to check core.create here.
                        // Maybe you can only get the list of categories you are allowed to create in?
                        // Need to think about that. If so, this is the place to do the check.
                    }
                }
            }
        }
       
		// Merge any additional options in the XML definition.
		$options = array_merge(parent::getOptions(), $options);

		return $options;
	}
}