<?php
/**
 * @package     Joomla.Administrator
 * @subpackage  com_associations
 *
 * @copyright   Copyright (C) 2005 - 2019 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 * 
 * Modified 2019 by Arno Betz for jDownloads to make it fully compatible with the jDownloads 3.9 version.
 */

namespace JDownloads\Component\JDownloads\Administrator\Controller; 
 
\defined('_JEXEC') or die;

use Joomla\CMS\Language\Text;
use Joomla\CMS\Factory;
use Joomla\CMS\Router\Route;
use Joomla\CMS\MVC\Controller\AdminController;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;

use JDownloads\Component\JDownloads\Administrator\Helper\AssociationsHelper;
use JDownloads\Component\JDownloads\Administrator\Model\AssociationModel;

BaseDatabaseModel::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_jdownloads/src/Model');

/**
 * Associations controller class.
 *
 * @since  3.7.0
 */
class AssociationsController extends AdminController
{
	/**
	 * The URL view list variable.
	 *
	 * @var    string
	 *
	 * @since  3.7.0
	 */
	protected $view_list = 'associations';
    
    /**
     * Constructor
     *
     */
    function __construct()
    {
        parent::__construct();
  
    }

	/**
	 * Proxy for getModel.
	 *
	 * @param   string  $name    The model name. Optional.
	 * @param   string  $prefix  The class prefix. Optional.
	 * @param   array   $config  The array of possible config values. Optional.
	 *
	 * @return  JModel|boolean
	 *
	 * @since   3.7.0
	 */
	public function getModel($name = 'Associations', $prefix = 'jdownloads', $config = array('ignore_request' => true))
	{
		return parent::getModel($name, $prefix, $config);
	}

	/**
	 * Method to purge the associations table.
	 *
	 * @return  void
	 *
	 * @since   3.7.0
	 */
	public function purge()
	{
		$this->checkToken();
        
        $model = $this->getModel();
        $result = $model->purge();
		$this->setRedirect(ROUTE::_('index.php?option=' . $this->option . '&view=' . $this->view_list, false));
	}

	/**
	 * Method to delete the orphans from the associations table.
	 *
	 * @return  void
	 *
	 * @since   3.7.0
	 */
	public function clean()
	{
		$this->getModel('associations')->clean();
		$this->setRedirect(ROUTE::_('index.php?option=' . $this->option . '&view=' . $this->view_list, false));
	}

	/**
	 * Method to check in an item from the association item overview.
	 *
	 * @return  void
	 *
	 * @since   3.7.1
	 */
	public function checkin()
	{
		// Set the redirect so we can just stop processing when we find a condition we can't process
		$this->setRedirect(ROUTE::_('index.php?option=' . $this->option . '&view=' . $this->view_list, false));

		// Figure out if the item supports checking and check it in
		$type = null;

		list($extensionName, $typeName) = explode('.', $this->input->get('itemtype'));

		$extension = AssociationsHelper::getSupportedExtension($extensionName);
		$types     = $extension->get('types');

		if (!array_key_exists($typeName, $types))
		{
			return;
		}

		if (AssociationsHelper::typeSupportsCheckout($extensionName, $typeName) === false)
		{
			// How on earth we came to that point, eject internet
			return;
		}

		$cid = $this->input->get('cid', array(), 'array');

		if (empty($cid))
		{
			// Seems we don't have an id to work with.
			return;
		}

		// We know the first element is the one we need because we don't allow multi selection of rows
		$id = $cid[0];

		if (AssociationsHelper::canCheckinItem($extensionName, $typeName, $id) === true)
		{
			$item = AssociationsHelper::getItem($extensionName, $typeName, $id);

			$item->checkIn($id);

			return;
		}

		$this->setRedirect(
			ROUTE::_('index.php?option=' . $this->option . '&view=' . $this->view_list),
			Text::_('COM_ASSOCIATIONS_YOU_ARE_NOT_ALLOWED_TO_CHECKIN_THIS_ITEM')
		);

		return;
	}
}
