<?php
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
 
namespace JDownloads\Component\JDownloads\Administrator\Controller; 
 
\defined( '_JEXEC' ) or die;

use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Controller\FormController;
use Joomla\Input\Input;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;
use Joomla\CMS\Access\Access;

use JDownloads\Component\JDownloads\Administrator\Helper\JDownloadsHelper;

/**
 * User view level controller class.
 *
 * @package		Joomla.Administrator
 * @subpackage	com_users
 * @since		1.6
 */
class GroupController extends FormController
{
    function __construct()
    {
        parent::__construct();

        // Register Extra task
        $this->registerTask( 'apply', 'save' );
        
    }
    
    public function getModel($name = 'group', $prefix = 'jdownloadsModel', $config = array('ignore_request' => true))
    {
        $model = parent::getModel($name, $prefix, $config);
        return $model;
    }    

	/**
     * Method to check if you can add a new record.
     *
     * Extended classes can override this if necessary.
     *
     * @param   array  $data  An array of input data.
     *
     * @return  boolean
     *
     * @since   1.6
     */
    protected function allowAdd($data = [])
    {
        $user = $this->app->getIdentity();

        return $user->authorise('core.create', 'com_jdownloads') || \count($user->getAuthorisedCategories('com_jdownloads', 'core.create'));
    }    
    
    /**
	 * Method to check if you can save a new or existing record.
	 *
	 * Overrides FormController::allowSave to check the core.admin and other jD permissions.
	 *
	 * @param	array	An array of input data.
	 * @param	string	The name of the key for the primary key.
	 *
	 * @return	boolean
	 */
	protected function allowSave($data, $key = 'id')
	{
		$recordId = $data[$key] ?? '0';

        if ($recordId) {
            return $this->allowEdit($data, $key);
        } else {
            return $this->allowAdd($data);
        }

	}

	/**
	 * Method to check if you can edit an existing record.
     * Overrides JControllerForm::allowEdit
	 *
	 * @param	array	An array of input data.
	 * @param	string	The name of the key for the primary key.
	 *
	 * @return	boolean
	 */
	protected function allowEdit($data = [], $key = 'id')
	{
		return $this->app->getIdentity()->authorise('core.edit', 'com_jdownloads');

	}

}
