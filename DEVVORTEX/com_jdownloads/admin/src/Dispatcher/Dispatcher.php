<?php
/**
 * @package     Joomla.Administrator
 * @subpackage  com_categories
 *
 * @copyright   (C) 2019 Open Source Matters, Inc. <https://www.joomla.org>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace JDownloads\Component\JDownloads\Administrator\Dispatcher;

\defined('_JEXEC') or die;

use Joomla\CMS\Access\Exception\NotAllowed;
use Joomla\CMS\Dispatcher\ComponentDispatcher;

/**
 * ComponentDispatcher class for com_jdownloads
 *
 * @since  4.0.0
 */
class Dispatcher extends ComponentDispatcher
{
	/**
	 * Categories have to check for extension permission
	 *
	 * @return  void
	 */
	protected function checkAccess()
	{
		$extension = 'com_jdownloads';

		$parts = explode('.', $extension);

		// Check the user has permission to access this component if in the backend
		if ($this->app->isClient('administrator') && !$this->app->getIdentity()->authorise('core.manage', $parts[0]))
		{
			throw new NotAllowed($this->app->getLanguage()->_('JERROR_ALERTNOAUTHOR'), 403);
		}
	}
}
