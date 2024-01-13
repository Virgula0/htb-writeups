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

use Joomla\CMS\Application\CMSApplication;
use Joomla\Input\Input;
use Joomla\Utilities\ArrayHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Controller\AdminController;
use Joomla\CMS\MVC\Factory\MVCFactoryInterface;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;
use Joomla\CMS\Session\Session;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Filesystem\File;
use Joomla\CMS\Filesystem\Folder;

use JDownloads\Component\JDownloads\Administrator\Helper\JDownloadsHelper;

/**
 * jDownloads layoutinstall Controller
 *
 */
class LayoutinstallController extends AdminController
{
    /**
     * Constructor
     */
    function __construct()
    {
        parent::__construct();
    }
    
	/**
	 * logic to store the data from the layout file in the database
	 *
	 */
	public function install()
    {
        $params = ComponentHelper::getParams('com_jdownloads');
        $files_uploaddir = $params->get('files_uploaddir');
        $tempdir         = $params->get('tempzipfiles_folder_name');
        
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
            
            $original_upload_dir = $files_uploaddir;

            // Get layout file
            $file = ArrayHelper::getValue($_FILES,'install_file',array('tmp_name'=>''));
            
            // When file is not valid exit
            if (!$file['type'] == 'text/xml'){
                $app->enqueueMessage(Text::_('COM_JDOWNLOADS_LAYOUTS_IMPORT_MSG_WRONG_FILE_ERROR'), 'error');
                $app->redirect(ROUTE::_('index.php?option=com_jdownloads&view=layouts', false));
            }
            
            // Save it in tempzipfile folder
            $upload_path = $files_uploaddir.'/'.$tempdir.'/'.$file['name'];
            
            // Check whether a file with the same name already exist
            if (File::exists($upload_path)){
                $res = File::delete($upload_path);
            }
            
            // Since Joomla 3.4 we need additional params to allow unsafe file (backup file contains php content)
            // if (!File::upload($file['tmp_name'], $upload_path, false, true)){
            // We need unfiltered data in this case           
            if (!move_uploaded_file ($file['tmp_name'], $upload_path)){
                $app->enqueueMessage(Text::_('COM_JDOWNLOADS_LAYOUTS_IMPORT_MSG_STORE_ERROR'), 'error');
                $app->redirect(ROUTE::_('index.php?option=com_jdownloads&view=layouts', false));
            }
            
            $xml = simplexml_load_file($upload_path);
            if ($xml->template_typ){
                if ($xml->targetjdownloads){
                    // Versions check
                    $current_version = JDownloadsHelper::getjDownloadsVersion();
                    $result = version_compare($xml->targetjdownloads, $current_version, '<=');
                    if (!$result){
                        // Installed version is to old for this layout
                        $app->enqueueMessage(Text::_('COM_JDOWNLOADS_LAYOUTS_IMPORT_MSG_WRONG_VERSION_ERROR'), 'error');
                        $app->redirect(ROUTE::_('index.php?option=com_jdownloads&view=layouts', false));
                    }
                }
                
                switch ($xml->template_typ) {
                    case 'categories':
                        $xml->template_typ = '1';
                        break; 
                    case 'category':
                        $xml->template_typ = '4';                    
                        break;
                    case 'files':
                        $xml->template_typ = '2';                                        
                        break;
                    case 'downloads':
						//CAM recognise both 'files' and 'downloads' as type 2  (files are pre 3.9, downloads are 3.9 and above)
                        $xml->template_typ = '2';                                        
                        break;
                    case 'details':
                        $xml->template_typ = '5';                                        
                        break;
                    case 'summary':
                        $xml->template_typ = '3';                                        
                        break;
                    case 'search':
                        $xml->template_typ = '7';                                        
                        break;
                    case 'subcats':
                        $xml->template_typ = '8';                                        
                        break;
                    default:
                        // wrong layout type
                        $app->enqueueMessage(Text::_('COM_JDOWNLOADS_LAYOUTS_IMPORT_MSG_WRONG_FILE_ERROR'), 'error');
                        $app->redirect(ROUTE::_('index.php?option=com_jdownloads&view=layouts', false));
                }

                if ($xml->author != ''){
                    $note = $xml->note."\r\n{".Text::_('COM_JDOWNLOADS_BACKEND_FILESLIST_AUTHOR').': '.$xml->author;
                    if ($xml->creation_date != ''){
                        $note .= ' - '.$xml->creation_date.'}';
                    } else {
                        $note .= '}';
                    }
                } else {
                    $note = preg_replace( "/\r|\n/", "", $xml->note );
                }
                
                // Add the missing fields from v3.8 when not exists (by older layouts)
                if (!isset($xml->uses_bootstrap)){
                    $xml->uses_bootstrap = 0;
                    $xml->uses_w3css = 0;
                }
                
                $db->setQuery("INSERT INTO #__jdownloads_templates (`id`, `template_name`, `template_typ`, `template_header_text`, `template_subheader_text`, `template_footer_text`, `template_before_text`, `template_text`, `template_after_text`, `template_active`, `locked`, `note`, `cols`, `uses_bootstrap`, `uses_w3css`, `checkbox_off`, `use_to_view_subcats`, `symbol_off`, `language`)
                      VALUES ( NULL, ".$db->quote($xml->template_name).", ".$db->quote((int)$xml->template_typ).", ".$db->quote($xml->template_header_text).", ".$db->quote($xml->template_subheader_text).", ".$db->quote($xml->template_footer_text).", ".$db->quote($xml->template_before_text).", ".$db->quote($xml->template_text).", ".$db->quote($xml->template_after_text).", ".$db->quote((int)$xml->template_active).", ".$db->quote((int)$xml->locked).", ".$db->quote($note).", ".$db->quote((int)$xml->cols).", ".$db->quote((int)$xml->uses_bootstrap).", ".$db->quote((int)$xml->uses_w3css).", ".$db->quote((int)$xml->checkbox_off).", ".$db->quote((int)$xml->use_to_view_subcats).", ".$db->quote((int)$xml->symbol_off).", ".$db->quote($xml->language).")");
                $result = $db->execute();
                if (!$result){
                    // MySQL error
                    $app->enqueueMessage(Text::_('COM_JDOWNLOADS_LAYOUTS_IMPORT_MSG_MYSQL_ERROR'), 'error');
                    $app->redirect(ROUTE::_('index.php?option=com_jdownloads&view=layouts', false));
                } 
            } else {
                // invalid file
                $app->enqueueMessage(Text::_('COM_JDOWNLOADS_LAYOUTS_IMPORT_MSG_WRONG_FILE_ERROR'), 'error');
                $app->redirect(ROUTE::_('index.php?option=com_jdownloads&view=layouts', false));
            }                        
        }
        $app->enqueueMessage(Text::_('COM_JDOWNLOADS_LAYOUTS_IMPORT_MSG_SUCCESSFUL'));
        $app->redirect(ROUTE::_('index.php?option=com_jdownloads&view=layouts', false));
    }
}
?>