<?php
/**
* @version 4.0
* @package JDownloads
* @copyright (C) 2022 www.jdownloads.com
* @license http://www.gnu.org/copyleft/gpl.html GNU/GPL
*
* Plugin to handle the Joomla tag feature in jDownloads correct also with categories.
 */

\defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Plugin\CMSPlugin;


/**
 */
class PlgContentJDownloads_Tags_Fix extends CMSPlugin
{
	/**
	 * @param   string   $context  The context of the content passed to the plugin 
	 * @param   object   $data     A JTableContent object
	 * @param   boolean  $isNew    If the content is just about to be created
	 *
	 */
	public function onContentAfterSave($context, $data, $isNew): void
	{
		// Check we are handling a jDownloads content
		if ($context == 'com_jdownloads.download' || $context == 'com_jdownloads.category'){

			$db = Factory::getDbo();
			$query = $db->getQuery(true)
				->update($db->quoteName('#__ucm_content'))
				->set($db->quoteName('core_catid') . ' = 0')
				    ->where($db->quoteName('core_type_alias').' = '.$db->quote('com_jdownloads.download'))
	                ->orWhere($db->quoteName('core_type_alias').' = '.$db->quote('com_jdownloads.category'));
			$db->setQuery($query)->execute();
			
        } 
	}
}
	