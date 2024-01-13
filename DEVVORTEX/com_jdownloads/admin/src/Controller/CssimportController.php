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

use Joomla\Utilities\ArrayHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Factory;
use Joomla\CMS\Session\Session;
use Joomla\CMS\MVC\Controller\AdminController;
use Joomla\CMS\Filesystem\File;
use Joomla\CMS\Filesystem\Folder;
use Joomla\CMS\Router\Route;

/**
 * jDownloads cssimport Controller
 *
 */
class CssimportController extends AdminController
{
    /**
     * Constructor
     */
    function __construct()
    {
        parent::__construct();
    }
    
	/**
	 * logic to store the new css file on the server but make also a backup from the old css file
     * 
	 */
	public function import()
    {
        // Check for request forgeries.
        Session::checkToken() or jexit(Text::_('JINVALID_TOKEN'));

        $app = Factory::getApplication();
        
        // Access check.
        if (!$app->getIdentity()->authorise('core.admin','com_jdownloads')){            
            $app->enqueueMessage( Text::_('JERROR_ALERTNOAUTHOR'), 'warning');
            $app->redirect(ROUTE::_('index.php?option=com_jdownloads&view=layouts', false));
        
        } else {       
        
            $db = Factory::getDBO();
            
            ini_set('max_execution_time', '300');
            if (function_exists('ignore_user_abort')) {
                ignore_user_abort(true);
            }
            flush(); 
            
            $target_upload_dir = JPATH_COMPONENT_SITE.'/assets/css/';
            $file_names = array('jdownloads_custom.css', 'jdownloads_buttons.css', 'jdownloads_fe.css', 'jdownloads_fe_rtl.css');
            $rename_error = false;
            
            // get css file
            $file = ArrayHelper::getValue($_FILES,'install_file',array('tmp_name'=>''));
            
            // when file is not valid exit
            if (!$file['type'] == 'text/css' || (!in_array($file['name'], $file_names))){
                $app->enqueueMessage(Text::_('COM_JDOWNLOADS_CSS_IMPORT_MSG_WRONG_FILE_ERROR'), 'error');
                $app->redirect(ROUTE::_('index.php?option=com_jdownloads&view=layouts', false));
            }
            
            // make at first a backup from the old css file
            if (File::exists($target_upload_dir.$file['name'])){
                $x = 1;
                // get the next free number
                while (File::exists($target_upload_dir.$file['name'].'.backup.'.$x)){
                    $x++;
                    if ($x == 500){
                        $rename_error = true;
                        continue;
                    }
                } 
                if (!$rename_error){
                    if (!File::move($target_upload_dir.$file['name'], $target_upload_dir.$file['name'].'.backup.'.$x)){
                        $rename_error = true;
                    }    
                }
            }
            
            if (!$rename_error){
               // all is correct so we can now move the file
                if (!move_uploaded_file($file['tmp_name'], $target_upload_dir.$file['name'])){
                        $rename_error = true;
                } else {
                    // succesful
                    $app->enqueueMessage( Text::_('COM_JDOWNLOADS_CSS_IMPORT_MSG_SUCCESSFUL'), 'info');
                    $app->redirect(ROUTE::_('index.php?option=com_jdownloads&view=layouts', false));
                }
            } else {
                // not succesful
                $app->enqueueMessage( Text::_('COM_JDOWNLOADS_CSS_IMPORT_MSG_STORE_ERROR'), 'error');
                $app->redirect(ROUTE::_('index.php?option=com_jdownloads&view=layouts', false));
            }
        }
    }
}
?>