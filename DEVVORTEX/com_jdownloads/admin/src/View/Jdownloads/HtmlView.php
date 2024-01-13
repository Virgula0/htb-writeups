<?php
/**
 * @package jDownloads
 * @version 4.0  
 * @copyright (C) 2007 - 2021 - Arno Betz - www.jdownloads.com
 * @license http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.txt
 * 
 * jDownloads is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 */

namespace JDownloads\Component\JDownloads\Administrator\View\Jdownloads;

\defined('_JEXEC') or die;

use Joomla\CMS\Application\ApplicationHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Helper\ModuleHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Toolbar\ToolbarHelper;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Filesystem\File;
use Joomla\CMS\Filesystem\Folder;
use Joomla\CMS\Log\Log;

use JDownloads\Component\JDownloads\Administrator\Helper\JDownloadsHelper;


/**
 * View class for the control panel.
 *
 * @since  4.0.0
 */
class HtmlView extends BaseHtmlView
{
    protected $canDo;

	protected $modules = null;

	/**
	 * Execute and display a template script.
	 *
	 * @param   string  $tpl  The name of the template file to parse; automatically searches through the template paths.
	 *
	 * @return  mixed  A string if successful, otherwise an Error object.
	 */
	public function display($tpl = null)
	{
        $params = ComponentHelper::getParams( 'com_jdownloads' );

        $app        = Factory::getApplication();
		$user 		= $app->getIdentity();
        $db         = Factory::getDBO();
        $query      = $db->getQuery(true);
		
        // Check here wether we must still create the 'uncategorised' default category after an UPGRADE from 3.2
        
        $files_upload_dir = $params->get('files_uploaddir');
        
        if (!$files_upload_dir){
            // main path is missing - display message and abort    
            $app->enqueueMessage(Text::_('Error: The files path is not defined in option settings! Check this settings in the jDownloads options and correct it.'), 'warning');
        } else {
            $uncat_created = $params->get('uncat_already_created');
            $new_cat_id = 0;
            $amount     = 0;
            $new_uncat_folder_name = Text::_('COM_JDOWNLOADS_UNCATEGORISED_CATEGORY_NAME');
            $target_path = $files_upload_dir.'/'.$new_uncat_folder_name;
            
            if (!$uncat_created){
                JDownloadsHelper::changeParamSetting('create_auto_cat_dir', '1');
                if (!Folder::exists($files_upload_dir.'/'.$new_uncat_folder_name)){
                    $uncat_succesful_created = $this->createUncatCategory($files_upload_dir);
                    if ($uncat_succesful_created){
                        $db->setQuery('SELECT `id` FROM #__jdownloads_categories WHERE `title` = '.$db->quote($new_uncat_folder_name).' AND `level` = "1"');
                        $new_cat_id = (int)$db->loadResult();
                        
                        JDownloadsHelper::changeParamSetting('uncat_already_created', '1');
                        
                        // Need we still to move the exists uncategorised files to the new created category folder (above)?
                        if (Folder::exists($files_upload_dir.'/_uncategorised_files')){
                            $files = scandir($files_upload_dir.'/_uncategorised_files');
                            if (count($files)){
                                // we must move all files here and update the database
                                foreach ($files as $file){
                                    if ($file == '.' || $file == '..' || $file == 'index.html' || strpos($file, '"') > 0 ){
                                        continue;
                                    }
                                    $result = File::move($files_upload_dir.'/_uncategorised_files/'.$file, $target_path.'/'.$file);
                                    if ($result) $amount++;
                                }

                                // update the db table
                                $db->setQuery('UPDATE #__jdownloads_files SET `catid` = '.$db->quote($new_cat_id).' WHERE `catid` = "1"');
                                $db->execute(); 
                            } 
                            
                            // we must finaly only delete the old folders
                            Folder::delete($files_upload_dir.'/_uncategorised_files');
                            
                            if (Folder::exists($files_upload_dir.'/_private_user_area')){
                                Folder::delete($files_upload_dir.'/_private_user_area');
                            }
                            
                            // Add some messages to the message queue
                            $app->enqueueMessage(Text::sprintf('COM_JDOWNLOADS_UPGRADE32_AMOUNT_UNCAT_FILES_MOVED_MSG', $amount), 'message');
                            $app->enqueueMessage(Text::_('COM_JDOWNLOADS_UPGRADE32_OLD_FOLDERS_DELETED_MSG'), 'message'); 
                            $app->enqueueMessage(Text::_('COM_JDOWNLOADS_UPGRADE32_SUCCESFUL_FINISHED_MSG'), 'message');
                            
                            // Add messages to log
                            $logoptions['text_entry_format'] = '{DATE} {TIME}    {PRIORITY}     {MESSAGE}';
                            $logoptions['text_file'] = 'com_jdownloads_install_logs.php';
                    
                            Log::addLogger($logoptions, Log::ALL, 'jD');

                            try
                            {
                                Log::add(Text::sprintf('COM_JDOWNLOADS_UPGRADE32_AMOUNT_UNCAT_FILES_MOVED_MSG', $amount), Log::INFO, 'jD');
                                Log::add(Text::_('COM_JDOWNLOADS_UPGRADE32_OLD_FOLDERS_DELETED_MSG'), Log::INFO, 'jD');
                                Log::add(Text::_('COM_JDOWNLOADS_UPGRADE32_SUCCESFUL_FINISHED_MSG'), Log::INFO, 'jD');
                            }            
                            catch (RuntimeException $exception)
                            {
                                // Informational log only
                            }
                        }        
                    }
                } 
            }
        }
        
        // Check here wether all option settings are actualized after an UPDATE from 3.9.x
        
        // Help URL 
        $help_url = $params->get('help_url');
        if ($help_url != 'https://www.jdownloads.net/index.php?option=com_content&view=article&id='){
            JDownloadsHelper::changeParamSetting('help_url', 'https://www.jdownloads.net/index.php?option=com_content&view=article&id=');    
        }
        
        // 3 new options added in 3.9.6 - so make sure all has correct default values after update from a prior 3.9.x installation
        $new_options = $params->get('be_amount_of_pics_in_downloads_list');
        if ($new_options === null){
            JDownloadsHelper::changeParamSetting('be_amount_of_pics_in_downloads_list', 3);
            JDownloadsHelper::changeParamSetting('view_preview_file_in_downloads_list', 1);
            JDownloadsHelper::changeParamSetting('view_price_field_in_downloads_list', 1);
        }
        
        // New options added in 3.9.7 - so make sure that all it has the default value after update
        $link_in_symbols = $params->get('link_in_symbols');
        if ($link_in_symbols === null){
            JDownloadsHelper::changeParamSetting('link_in_symbols', 1);
            // New monitoring options
            // For categories
            JDownloadsHelper::changeParamSetting('autopublish_use_cat_default_values', 0);
            JDownloadsHelper::changeParamSetting('autopublish_cat_pic_default_filename', 'folder.png');
            JDownloadsHelper::changeParamSetting('autopublish_default_cat_description', '');
            JDownloadsHelper::changeParamSetting('autopublish_cat_access_level', 1);
            JDownloadsHelper::changeParamSetting('autopublish_cat_language', '*');
            JDownloadsHelper::changeParamSetting('autopublish_cat_tags', '');
            JDownloadsHelper::changeParamSetting('autopublish_cat_created_by', 0);
            // For Downloads
            JDownloadsHelper::changeParamSetting('autopublish_use_default_values', 1);
            JDownloadsHelper::changeParamSetting('autopublish_title_format_option', 0);
            JDownloadsHelper::changeParamSetting('autopublish_default_description', '');
            JDownloadsHelper::changeParamSetting('autopublish_access_level', 1);
            JDownloadsHelper::changeParamSetting('autopublish_language', '*');
            JDownloadsHelper::changeParamSetting('autopublish_tags', '');
            JDownloadsHelper::changeParamSetting('autopublish_created_by', 0);
            JDownloadsHelper::changeParamSetting('autopublish_price', '');
            JDownloadsHelper::changeParamSetting('autopublish_reset_use_default_values', 1);
        }
        
        // Currently we do not support the option to hide empty categories. This must first be overworked in the future.
        // So it must always be enabled
        JDownloadsHelper::changeParamSetting('view_empty_categories', 1);

        // End options check
        
        $this->addToolbar();
 
        $input = Factory::getApplication()->input;

        $input->set('tmpl', 'cpanel');

        // Display the cpanel modules
        $this->modules = ModuleHelper::getModules('jdcpanel');

        parent::display($tpl);
	}
    
    /**
     * Add the page title and toolbar.
     *
     * 
     */
    protected function addToolbar()
    {
        $app    = Factory::getApplication();
        $params = ComponentHelper::getParams('com_jdownloads');
        
        $state    = $this->get('State');
        $canDo    = JDownloadsHelper::getActions();
        $user     = $app->getIdentity();

        $document = Factory::getDocument();
        $document->addStyleSheet('components/com_jdownloads/assets/css/style.css');
        $document->addScriptDeclaration('function openWindow (url, h, w) {
        params = \'height=\' + h + \', width=\' + w + \', STATUS=YES, DIRECTORIES=NO, MENUBAR=NO, SCROLLBARS=YES, RESIZABLE=NO\';
        scanWindow = window.open(url, "_blank", params);
        scanWindow.focus();
        }');
        
        ToolBarHelper::title(Text::_('COM_JDOWNLOADS').': '.Text::_('COM_JDOWNLOADS_CPANEL'), 'home-2');
        
        ToolBarHelper::link('index.php?option=com_jdownloads', Text::_('COM_JDOWNLOADS_REFRESH'), 'refresh cpanel');        
        
        if ($user->authorise('core.admin', 'com_jdownloads') || $user->authorise('core.options', 'com_jdownloads')) {
            ToolBarHelper::preferences('com_jdownloads');
            ToolBarHelper::divider();
        }

        // Add help button - The first integer value must be the corresponding article ID from the documentation
        $help_page = '302&tmpl=jdhelp'; // Article 'Control Panel Overview (v4)'
        $help_url = $params->get('help_url').$help_page;
        $exists_url = JDownloadsHelper::existsHelpServerURL($help_url);
        if ($exists_url){
            ToolBarHelper::help(null, false, $help_url);
        } else {
            ToolBarHelper::help('help.general', true); 
        }
    }        
    
    /**
     * Create the required Uncategorised Category and Folder after installation
     *
     * 
     */ 
    public static function createUncatCategory($root_dir)
    {         
        $jDownloadsComponent = Factory::getApplication()->bootComponent('com_jdownloads');
        $model               = $jDownloadsComponent->getMVCFactory()->createModel('Category', '', ['ignore_request' => true]);
        
        if (!$root_dir){
			$root_dir = JPATH_ROOT.'/jdownloads';
		}
            
        if (Folder::exists($root_dir)) {
            if (is_writable($root_dir)) {      
                // create it only when the folder for the 'Uncategorised' category still not exists
                if (!Folder::exists($root_dir.'/'.Text::_('COM_JDOWNLOADS_UNCATEGORISED_CATEGORY_NAME'))){
                    $create_result = $model->createCategory( Text::_('COM_JDOWNLOADS_UNCATEGORISED_CATEGORY_NAME' ), '', '', '1', 1);
                    if (!$create_result){
                        Factory::getApplication()->enqueueMessage( Text::_('COM_JDOWNLOADS_BATCH_CANNOT_CREATE_FOLDER'), 'warning');
                        return false;
                    } else {
                        return true;
                    }   
                } else {
                    // Factory::getApplication()->enqueueMessage( Text::_('COM_JDOWNLOADS_SAMPLE_DATA_CREATE_ERROR'), 'warning');
                    // return false;
                }                                
            } else {
                // error message: upload folder not writeable
                Factory::getApplication()->enqueueMessage( Text::_('COM_JDOWNLOADS_ROOT_FOLDER_NOT_WRITABLE'), 'warning');
                return false;
            } 
        } else {
            // error message: upload folder not found
            Factory::getApplication()->enqueueMessage( Text::sprintf('COM_JDOWNLOADS_AUTOCHECK_DIR_NOT_EXIST', $root_dir), 'warning');
            return false;
        } 	
    } 
}
