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
use Joomla\CMS\HTML\HTMLHelper;

use JDownloads\Component\JDownloads\Administrator\Helper\JDownloadsHelper;
use JDownloads\Component\JDownloads\Administrator\Model\CategoryModel;
use JDownloads\Component\JDownloads\Administrator\Model\DownloadModel;

/**
 * jDownloads Restore Controller
 *
 */
class RestoreController extends AdminController
{
	/**
	 * Constructor
	 *
	*/
	    public function __construct($config = array())
    {
        parent::__construct($config);
	}
    
    public function getModel($name = '', $prefix = 'Administrator', $config = array('ignore_request' => true))
    {
        BaseDatabaseModel::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_jdownloads/src/Model');
        
        $model = BaseDatabaseModel::getInstance($name, $prefix, $config);
        return $model;
    }

	/**
	 * logic to restore the backup file
	 *
	 */
	public function runrestore()
    {
        $params          = ComponentHelper::getParams('com_jdownloads');
        $files_uploaddir = $params->get('files_uploaddir');
        $tempdir         = $params->get('tempzipfiles_folder_name');
        
        // Check for request forgeries.
        Session::checkToken() or jexit(Text::_('JINVALID_TOKEN'));
        
        $app = Factory::getApplication();

        // Access check.
        if (!$app->getIdentity()->authorise('core.admin','com_jdownloads')){            
            $app->enqueueMessage(Text::_('JERROR_ALERTNOAUTHOR'), 'warning');
            $this->setRedirect(ROUTE::_('index.php?option=com_jdownloads&view=tools', true));
            
        } else {       
        
            $model_category = self::getModel( 'Category', 'jdownloads' );
            $model_download = self::getModel( 'Download', 'jdownloads' );
        
            $db = Factory::getDBO();
            $user = $app->getIdentity();
            
            ini_set('max_execution_time', '600');
            if (function_exists('ignore_user_abort')) {
                ignore_user_abort(true);
            }
            flush(); 
            
            $target_prefix = JDownloadshelper::getCorrectDBPrefix();
            
            $original_upload_dir = $files_uploaddir;
            
            $output = '';
            $log = '';

            // get restore file
            $file = ArrayHelper::getValue($_FILES,'restore_file',array('tmp_name'=>''));
            
            // save it in upload root
            $upload_path = $files_uploaddir.'/'.$file['name'];
            // since Joomla 3.4 we need additional params to allow unsafe file (backup file contains php content)
            if (!File::upload($file['tmp_name'], $upload_path, false, true)){
                $app->enqueueMessage(Text::_('COM_JDOWNLOADS_RESTORE_MSG_STORE_ERROR'), 'error');
                $app->redirect(ROUTE::_('index.php?option=com_jdownloads', false));
            }
            
            if($file['tmp_name']!= ''){

                // write values in db tables
                require_once($upload_path);
                

                // we must restore the original stored upload root dir in params
                $result = JDownloadsHelper::changeParamSetting('files_uploaddir', $original_upload_dir);
                
                // create for every category a data set in the assets table
                // get at first all items
                $query = $db->getQuery(true);
                $query->select('*');
                $query->from('#__jdownloads_categories');
                $query->order('lft ASC');
                $db->setQuery($query);
                $cats = $db->loadObjectList();
                // we need an array
                $cats = json_decode(json_encode($cats), true);          
                // sum of total categories (but compute not the root)
                $cats_sum = count($cats) - 1;
                
                $sum_updated_cats = 0;
                
                if ($cats_sum){
                    
                    foreach ($cats as $cat){
                        
                        if ($cat['id'] > 1){
                            // add the new rules array
                            $cat['rules'] = array(
                                    'core.create' => array(),
                                    'core.delete' => array(),
                                    'core.edit' => array(),
                                    'core.edit.state' => array(),
                                    'core.edit.own' => array(),
                                    'download' => array(),
                            );
                            // save now the category with the new rules
                            $update_result = $model_category->save( $cat, true );
                            if (!$update_result){
                                // error message
                                $log .= 'Category Results: Can not create new asset rules for category ID '.$cat['id'].'<br />';
                            } else {
                                $sum_updated_cats ++;                        
                            }              

                        }
                    }
                    $log .= "New data sets created in 'assets' db table for categories: ".$sum_updated_cats.'<br />';
                }                
                
                // create for every Download a data set in the assets table (added in 3.2.22)
                // get at first all items
                $query = $db->getQuery(true);
                $query->select('*');
                $query->from('#__jdownloads_files');
                $query->order('id ASC');
                $db->setQuery($query);
                $files = $db->loadObjectList();
                // we need an array
                $files = json_decode(json_encode($files), true);          
                // sum of total Downloads
                $files_sum = count($files);
                
                $sum_updated_files = 0;
                
                if ($files_sum){
                    
                    foreach ($files as $file){
                        
                            // add the new rules array
                            $file['rules'] = array(
                                    'core.create' => array(),
                                    'core.delete' => array(),
                                    'core.edit' => array(),
                                    'core.edit.state' => array(),
                                    'core.edit.own' => array(),
                                    'download' => array(),
                            );
                            // save now the download with the new rules
                            $update_result = $model_download->save( $file, true, false, true );
                            if (!$update_result){
                                // error message
                                $log .= 'Downloads Results: Can not create new asset rules for download ID '.$file['id'].'<br />';
                            } else {
                                $sum_updated_files ++;                        
                            }              
                    }
                    $log .= "New data sets created in 'assets' db table for downloads: ".$sum_updated_files.'<br />';
                }                
                
                $datetext = ' <b>'.HTMLHelper::_('date', Factory::getDate(), Text::_('DATE_FORMAT_LC5')).'</b></br>'; 
                $sum = '<font color="green"><b>'.sprintf(Text::_('COM_JDOWNLOADS_RESTORE_MSG'),(int)$i).'</b></font>';
                
                if ($log){
                    $output = $db->escape($datetext.$sum.'<br />'.$output.'<br />'.Text::_('COM_JDOWNLOADS_AFTER_RESTORE_TITLE_3').'<br />'.$log.'<br />'.Text::_('COM_JDOWNLOADS_CHECK_FINISH').'');
                } else {   
                    $output = $db->escape($datetext.$sum.'<br />'.$output.'<br />'.Text::_('COM_JDOWNLOADS_CHECK_FINISH').'');
                }    
                
                // Write protocol to file 
                $x = file_put_contents(JPATH_COMPONENT_ADMINISTRATOR.'/restore_logs.txt', $output, FILE_APPEND | LOCK_EX);
                
                // delete the backup file in temp folder
                File::delete($upload_path);
            }
            $this->setRedirect( 'index.php?option=com_jdownloads',  $datetext.$sum.' '.Text::_('COM_JDOWNLOADS_RESTORE_MSG_2') );
        }    
    }    
	
}
?>