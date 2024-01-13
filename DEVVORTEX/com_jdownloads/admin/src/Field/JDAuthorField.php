<?php

namespace JDownloads\Component\JDownloads\Administrator\Field;

\defined( '_JEXEC' ) or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Form\Field\ListField;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\CMS\Form\Field;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;


/**
 * Form Field to load a list of content authors/users
 *
 */
class JDAuthorField extends ListField
{
	/**
	 * The form field type.
	 *
	 * @var    string
	 */
	public $type = 'JDAuthor';

	/**
	 * Cached array of the category items.
	 *
	 * @var    array
	 */
	protected static $options = array();

	/**
	 * Method to get the options to populate list
	 *
	 * @return  array  The field option objects.
	 *
	 */
	protected function getOptions()
	{
		// Accepted modifiers
		$hash = md5($this->element);

		if (!isset(static::$options[$hash]))
		{
			static::$options[$hash] = parent::getOptions();

			$options = array();

			$db = Factory::getDbo();

			// Construct the query
			$query = $db->getQuery(true)
				->select('u.id AS value, u.name AS text')
				->from('#__users AS u')
				->join('INNER', '#__jdownloads_files AS c ON c.created_by = u.id')
				->group('u.id, u.name')
				->order('u.name');

			// Setup the query
			$db->setQuery($query);

			// Return the result
			if ($options = $db->loadObjectList())
			{
				static::$options[$hash] = array_merge(static::$options[$hash], $options);
			}
		}

		return static::$options[$hash];
	}
}
