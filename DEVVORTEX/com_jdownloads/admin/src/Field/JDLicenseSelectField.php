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

\defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Form\Field\ListField;
use Joomla\CMS\Form\FormHelper;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\Database\ParameterType;
use Joomla\Utilities\ArrayHelper;


/**
 * Form Field class for the Joomla Framework.
 *
 * @package		Joomla.Administrator
 * @since		1.6
 */
class JDLicenseSelectField extends ListField
{
	/**
	 * The form field type.
	 *
	 * @var		string
	 * @since	1.6
	 */
	protected $type = 'JDLicenseSelect';

	/**
	 * Method to get the field options.
	 *
	 * @return	array	The field option objects.
	 * @since	1.6
	 */
	protected function getOptions()
	{
		// Initialise variables.
		$options = array();

		$db		= Factory::getDbo();
		$query	= $db->getQuery(true);

		$query->select('a.id AS value, a.title AS text');
		$query->from('#__jdownloads_licenses AS a');
        $query->where('a.published = 1');
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

        // add a 'select' field in first position with zero value 
        $dummy = array();
        $dummy['value'] = '0';
        $dummy['text'] = Text::_('COM_JDOWNLOADS_SELECT');
        
        array_unshift( $options, $dummy );
		
        // Merge any additional options in the XML definition.
		$options = array_merge(parent::getOptions(), $options);

		return $options;
	}
    
    /**
     * Method to attach a Form object to the field.
     *
     * @param   \SimpleXMLElement  $element  The SimpleXMLElement object representing the `<field>` tag for the form field object.
     * @param   mixed              $value    The form field value to validate.
     * @param   string             $group    The field name group control value. This acts as an array container for the field.
     *                                       For example if the field has name="foo" and the group value is set to "bar" then the
     *                                       full field name would end up being "bar[foo]".
     *
     * @return  boolean  True on success.
     *
     * @see     AssocField::setup()
     * @since   4.0.0
     */
    public function setup(\SimpleXMLElement $element, $value, $group = null)
    {
        /*if (!Associations::isEnabled())
        {
            return false;
        } */

        return parent::setup($element, $value, $group);
    }
}