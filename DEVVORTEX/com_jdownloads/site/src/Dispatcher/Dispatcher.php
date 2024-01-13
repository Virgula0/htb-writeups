<?php
/**
 * @package     Joomla.Site
 * @subpackage  com_jdownloads
 *
 * @copyright   (C) 2022 Open Source Matters, Inc. <https://www.joomla.org>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace JDownloads\Component\JDownloads\Site\Dispatcher;

\defined('JPATH_PLATFORM') or die;

use Joomla\CMS\Dispatcher\ComponentDispatcher;
use Joomla\CMS\Language\Text;

/**
 * ComponentDispatcher class for com_jdownloads
 *
 * @since  4.0.0
 */
class Dispatcher extends ComponentDispatcher
{
	/**
	 * Dispatch a controller task. Redirecting the user if appropriate.
	 *
	 * @return  void
	 *
	 * @since   4.0.0
	 */
	public function dispatch()
	{
		// Fix for frontend modal window
        if ($this->input->get('view') === 'featured' || $this->input->get('view') === 'article'){
            $this->input->set('view', 'downloads');
        }
        
		$checkCreateEdit = ($this->input->get('view') === 'downloads' && $this->input->get('layout') === 'modal')
			|| ($this->input->get('view') === 'download' && $this->input->get('layout') === 'pagebreak');

		if ($checkCreateEdit)
		{
			// Can create in any category (component permission) or at least in one category
			$canCreateRecords = $this->app->getIdentity()->authorise('core.create', 'com_jdownloads')
				|| count($this->app->getIdentity()->getAuthorisedCategories('com_jdownloads', 'core.create')) > 0;

			// Instead of checking edit on all records, we can use **same** check as the form editing view
			$values = (array) $this->app->getUserState('com_jdownloads.edit.download.id');
			$isEditingRecords = count($values);
			$hasAccess = $canCreateRecords || $isEditingRecords;

			if (!$hasAccess)
			{
				$this->app->enqueueMessage(Text::_('JERROR_ALERTNOAUTHOR'), 'warning');

				return;
			}
		}

		parent::dispatch();
	}
}
