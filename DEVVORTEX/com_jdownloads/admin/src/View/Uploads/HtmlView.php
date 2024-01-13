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
 
namespace JDownloads\Component\JDownloads\Administrator\View\Uploads; 
 
\defined('_JEXEC') or die;

use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Toolbar\ToolbarHelper;
use Joomla\CMS\MVC\View\GenericDataException; 
use Joomla\CMS\Language\Text;
use Joomla\CMS\Filesystem\File;
use Joomla\CMS\Filesystem\Folder;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
                                                
use JDownloads\Component\JDownloads\Administrator\Helper\JDownloadsHelper;
use JDownloads\Component\JDownloads\Administrator\Helper\PLUploadScript;

/**
 * upload manager View
 *
 */
class HtmlView extends BaseHtmlView
{
    protected $canDo;
    
    /**
	 * uploads display method
	 * @return void
	 **/
	function display($tpl = null)
	{
        $params = ComponentHelper::getParams('com_jdownloads');
        
        // What Access Permissions does this user have? What can (s)he do?
        $this->canDo = JDownloadsHelper::getActions();
        
        $language = Factory::getLanguage();
        $lang = $language->getTag();
        
        $langfiles        = JPATH_COMPONENT_ADMINISTRATOR.'/assets/plupload/js/i18n/';        
        $PLdataDir        = Uri::root() . "administrator/components/com_jdownloads/assets/plupload/";
        $PLuploadScript   = new PLuploadScript($PLdataDir);
        $runtimeScript    = $PLuploadScript->runtimeScript;
        $runtime          = $PLuploadScript->runtime;

        // Add default PL css
        $document         = Factory::getDocument();
        $document->addStyleSheet($PLdataDir . 'css/plupload.css');
        
        // Add PL styles and scripts
        $document->addStyleSheet($PLdataDir . 'js/jquery.plupload.queue/css/jquery.plupload.queue.css');
        $document->addScript($PLdataDir . 'js/jquery.min.js');
		$document->addScript($PLdataDir . 'js/plupload.full.min.js');
		
        // Load plupload language file
        if ($lang){
            if (File::exists($langfiles . $lang.'.js')){
                $document->addScript($PLdataDir . 'js/i18n/'.$lang.'.js');      
            } else {
                $document->addScript($PLdataDir . 'js/i18n/en-GB.js');      
            }
        } 
        $document->addScript($PLdataDir . 'js/jquery.plupload.queue/jquery.plupload.queue.js');
        $document->addScriptDeclaration( $PLuploadScript->getScript() );
        
        // Set variables for the template
        $this->enableLog = $params->get('plupload_enable_uploader_log');
        $this->runtime = $runtime;
        $this->currentDir = $params->get('files_uploaddir').'/';
                
        // Set toolbar
        $this->addToolBar();
        
        // Display the template
        parent::display($tpl);
    }
    
    /**
     * Setting the toolbar
     */
    protected function addToolBar() 
    {
        $params = ComponentHelper::getParams('com_jdownloads');
        $app    = Factory::getApplication();
        
        $canDo    = JDownloadsHelper::getActions();
        $user     = $app->getIdentity();

        $document = Factory::getDocument();
        $document->addStyleSheet('components/com_jdownloads/assets/css/style.css');
        
        ToolBarHelper::title(Text::_('COM_JDOWNLOADS').': '.Text::_('COM_JDOWNLOADS_FILESLIST_TITLE_FILES_UPLOAD'), 'upload jdupload');
        
        ToolBarHelper::custom( 'uploads.files', 'upload.png', 'upload.png', Text::_('COM_JDOWNLOADS_FILES'), false, false );
        ToolBarHelper::custom( 'uploads.downloads', 'folder.png', 'folder.png', Text::_('COM_JDOWNLOADS_DOWNLOADS'), false, false );
        
        ToolBarHelper::divider();
        
        // Add help button - The first integer value must be the corresponding article ID from the documentation
        $help_page = '193&tmpl=jdhelp';  // Article 'Upload Options'
        $help_url = $params->get('help_url').$help_page;
        $exists_url = JDownloadsHelper::existsHelpServerURL($help_url);
        if ($exists_url){
            ToolBarHelper::help(null, false, $help_url);
        } else {
            ToolBarHelper::help('help.general', true); 
        }

    }
}
?>