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
use Joomla\CMS\MVC\Model\BaseDatabaseModel;
use Joomla\CMS\Session\Session;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Installer\Installer;
use Joomla\Input\Input;

/**
 * jDownloads Uninstall Controller
 *
 */
class UninstallController extends FormController
{   
	/**
	 * Constructor
	 *
	*/
	public function __construct($config = array())
    {
        parent::__construct($config);
	}

	/**
	 * logic to use the uninstall options to uninstall jD via Joomla uninstaller
	 *
	 */
	public function runUninstall()
    {
        $db         = Factory::getDBO();
        $session    = Factory::getSession();
        $app        = Factory::getApplication();
        $jinput     = Factory::getApplication()->input;
        
        // Check for request forgeries.
        Session::checkToken() or jexit(Text::_('JINVALID_TOKEN'));
        
        $app = Factory::getApplication();

        // Access check.
        if (!$app->getIdentity()->authorise('core.admin','com_jdownloads')){            
            $app->enqueueMessage( Text::_('JERROR_ALERTNOAUTHOR'), 'warning');
            $this->setRedirect(ROUTE::_('index.php?option=com_jdownloads', true));
            
        } else {       
            // Get the form data
            $formData = new Input($jinput->get('jform', '', 'array'));
            // Get data
            $del_images = $formData->getInt('images', 1);
            $del_files  = $formData->getInt('files', 1);
            $del_tables = $formData->getInt('tables', 1);
            
            $session->set('del_jd_images', $del_images);
            $session->set('del_jd_files', $del_files);
            $session->set('del_jd_tables', $del_tables);
            
            $db->setQuery('SELECT `extension_id` FROM #__extensions WHERE `element` = "com_jdownloads" AND `type` = "component"');
            $id = $db->loadResult();
            if($id){
                $installer = new Installer;
                $result = $installer->uninstall('component', $id, 1);
                $result_msg = array('name'=>'jDownloads Component','client'=>'site', 'result'=>$result);
            }
            $msg = $session->get('jd_uninstall_msg');
            if ($msg){
                $this->setRedirect(ROUTE::_('index.php?option=com_installer&view=manage', false), $msg);
            } else {  
                $this->setRedirect(ROUTE::_('index.php?option=com_installer&view=manage', false));
            }
        }    
    }
    
    /**
     * cancel the uninstall process
     *
     */
    public function cancelUninstall()
    {
        $session  = Factory::getSession();        
        $session->clear('del_jd_images');
        $session->clear('del_jd_files');
        $session->clear('del_jd_tables');
        
        $cancel_msg = Text::_('COM_JDOWNLOADS_UNINSTALL_CANCEL_MSG');
        $this->setRedirect(ROUTE::_('index.php?option=com_installer&view=manage', false), $cancel_msg);
    }
}
?>