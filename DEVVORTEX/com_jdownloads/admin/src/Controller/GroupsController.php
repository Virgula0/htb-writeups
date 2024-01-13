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

use Joomla\Input\Input;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Controller\AdminController;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;
use Joomla\CMS\Session\Session;

/**
 * jDownloads groups list controller class.
 *
 */
class GroupsController extends AdminController
{

	/**
	 * Proxy for getModel.
	 */
	public function getModel($name = 'group', $prefix = 'Administrator', $config = array('ignore_request' => true))
	{
        $model = parent::getModel($name, $prefix, $config);
        return $model;	
	}

    /**
     * logic to reset all jD user group limits
     *
     */
    public function resetLimits() 
    {
        $jinput         = Factory::getApplication()->input;
        $session        = Factory::getSession();
        $error          = '';
        $cid            = $this->input->get('cid', array(), 'array');
        
        // run the model methode
        $model = $this->getModel('groups');
        if(!$model->resetLimits($cid)) {
            $msg = Text::_( 'COM_JDOWNLOADS_USERGROUPS_RESET_LIMITS_RESULT_ERROR' );
            $error = 'error';
        } else {                             
            $msg = Text::_( 'COM_JDOWNLOADS_USERGROUPS_RESET_LIMITS_RESULT' );
        }
        $this->setRedirect( 'index.php?option=com_jdownloads&view=groups', $msg, $error);
    } 
}
