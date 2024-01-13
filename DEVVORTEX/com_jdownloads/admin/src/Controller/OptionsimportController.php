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
use Joomla\CMS\MVC\Controller\AdminController;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;
use Joomla\CMS\Session\Session;
use Joomla\CMS\Filesystem\File;
use Joomla\CMS\Filesystem\Folder;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\Utilities\ArrayHelper;

use JDownloads\Component\JDownloads\Administrator\Helper\JDownloadsHelper;

/**
 * jDownloads options import Controller
 *
 */
class OptionsimportController extends AdminController
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
	 * logic to import the options from a file
	 *
	 */
	public function runImport()
    {
        $params          = ComponentHelper::getParams('com_jdownloads');
        $files_uploaddir = $params->get('files_uploaddir');
        
        if (is_dir($files_uploaddir)){
            $root_dir = $files_uploaddir;
        } else {    
            $root_dir = $params->get('root_dir');
        }
        
        $tempdir         = $params->get('tempzipfiles_folder_name');
        $preview_dir     = $params->get('preview_files_folder_name');   
        
        // Check for request forgeries.
        Session::checkToken() or jexit(Text::_('JINVALID_TOKEN'));

        $app = Factory::getApplication();
        
        // Access check. 
        if (!$app->getIdentity()->authorise('core.admin','com_jdownloads')){            
            Factory::getApplication()->enqueueMessage( Text::_('JERROR_ALERTNOAUTHOR'), 'warning');
            $this->setRedirect(ROUTE::_('index.php?option=com_jdownloads', true));
            
        } else {       
        
            $db = Factory::getDBO();
            $user = $app->getIdentity();
            
            ini_set('max_execution_time', '600');
            if (function_exists('ignore_user_abort')) {
                ignore_user_abort(true);
            }
            flush(); 
            
            $output = '';
            $log = '';

            // Get the import file informations
            $file = ArrayHelper::getValue($_FILES, 'options_import_file', array('tmp_name' => ''));
            
            if ($file['tmp_name'] != ''){
                
                // Check file extension and type
                if (strtolower(File::getExt($file['name'])) == 'txt' && $file['type'] == 'text/plain'){
                
                    // Store it in jD temp folder
                    $upload_path = $files_uploaddir.'/'.$tempdir.'/'.$file['name'];
                    
                    // since Joomla 3.4 we need additional params to allow unsafe file (backup file contains php content)
                    if (!File::upload($file['tmp_name'], $upload_path, false, true)){
                         $this->setRedirect( ROUTE::_('index.php?option=com_jdownloads'), Text::_('COM_JDOWNLOADS_RESTORE_MSG_STORE_ERROR'), 'error');
                    }
                
                    // Write new params db table
                    $new_params = file_get_contents($files_uploaddir.'/'.$tempdir.'/'.$file['name']);
                    
                    // We must check the file content first
                    $check_params = json_decode($new_params);
                    
                    if (isset($check_params->files_uploaddir)){
                        
                        // We must store again the original pathes in params
                        $check_params->files_uploaddir           = $files_uploaddir;
                        $check_params->root_dir                  = $root_dir;
                        $check_params->tempzipfiles_folder_name  = $tempdir;
                        $check_params->preview_files_folder_name = $preview_dir;
                        
                        $new_params = $db->escape(json_encode($check_params));
                        
                    
                        $db->setQuery("UPDATE #__extensions SET `params` = '".$new_params."' WHERE `type` = 'component' AND `element` = 'com_jdownloads' AND `enabled` = 1");
                        if ($db->execute()){
                            // Delete the file in temp folder
                            File::delete($upload_path);
                            $this->setRedirect( ROUTE::_('index.php?option=com_jdownloads'), Text::_('COM_JDOWNLOADS_OPTIONS_IMPORT_DONE') );    
                            
                        } else {
                            // We could not save the new data
                            // Delete the file in temp folder
                            File::delete($upload_path);
                            $this->setRedirect( ROUTE::_('index.php?option=com_jdownloads'), Text::_('Aborted! Could not save the imported options data!'), 'error');
                        }
                    } else {
                        // Seems to be a file with wrong content?
                        // Delete the file in temp folder
                        File::delete($upload_path);
                        $this->setRedirect( ROUTE::_('index.php?option=com_jdownloads'),  Text::_('Aborted! The uploaded file seems not to have the correct content! Please choose the right file!'), 'error');
                    }
                
                } else {
                    // Invalid file?
                    $this->setRedirect( ROUTE::_('index.php?option=com_jdownloads'),  Text::_('Aborted! The selected file seems not to be correct!'), 'error');
                }
            }
        } 
    }   
}
?>