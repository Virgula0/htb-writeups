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

namespace JDownloads\Component\JDownloads\Administrator\View\Cssedit; 
 
\defined('_JEXEC') or die;

use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Factory;
use Joomla\CMS\Toolbar\ToolbarHelper;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\MVC\View\GenericDataException;

use JDownloads\Component\JDownloads\Administrator\Helper\JDownloadsHelper;

/**
 * Edit CSS File View
 *
 */
class HtmlView extends BaseHtmlView
{
    protected $canDo;

	/**
	 * cssedit display method
	 * @return void
	 **/
	function display($tpl = null)
	{
        $this->setFile();
        $this->addToolbar();
        parent::display($tpl);
	}
    
     /**
     * 
     *
     * 
     */
     protected function setFile(){
        $css_file = JPATH_SITE.'/components/com_jdownloads/assets/css/jdownloads_fe.css';
        @chmod ($css_file, 0755);

        $css_file2 = JPATH_SITE.'/components/com_jdownloads/assets/css/jdownloads_buttons.css';
        @chmod ($css_file2, 0755);        
        
        $css_file3 = JPATH_SITE.'/components/com_jdownloads/assets/css/jdownloads_custom.css';
        @chmod ($css_file3, 0755);        
		
		$css_file4 = JPATH_SITE.'/components/com_jdownloads/assets/css/jdownloads_fe_rtl.css';
        @chmod ($css_file4, 0755);		
        
        clearstatcache();
        

        if ( is_writable( $css_file ) == false ) {
          $css_writable = false;
        } else {
          $css_writable = true;
        }         

        if ( is_writable( $css_file2 ) == false ) {
          $css_writable2 = false;
        } else {
          $css_writable2 = true;
        }        

        if ( is_writable( $css_file3 ) == false ) {
          $css_writable3 = false;
        } else {
          $css_writable3 = true;
        }        
        
		if ( is_writable( $css_file4 ) == false ) {
          $css_writable4 = false;
        } else {
          $css_writable4 = true;
        }
        
        if ($css_writable){
            $f=fopen($css_file,"r");
            $css_text = fread($f, filesize($css_file));
            $this->csstext = htmlspecialchars($css_text);
        } else {
            $this->csstext = '';
        }
        $this->cssfile = $css_file;
        $this->cssfile_writable = $css_writable;         
        
        
        if ($css_writable2){
            $f=fopen($css_file2,"r");
            $css_text2 = fread($f, filesize($css_file2));
            $this->csstext2 = htmlspecialchars($css_text2);
        } else {
            $this->csstext2 = '';
        }
        $this->cssfile2 = $css_file2;
        $this->cssfile_writable2 = $css_writable2;        
        
        if ($css_writable3){
            $f=fopen($css_file3,"r");
            $css_text3 = fread($f, filesize($css_file3));
            $this->csstext3 = htmlspecialchars($css_text3);
        } else {
            $this->csstext3 = '';
        }
        $this->cssfile3 = $css_file3;
        $this->cssfile_writable3 = $css_writable3;        
		
		if ($css_writable4){
            $f=fopen($css_file4,"r");
            $css_text4 = fread($f, filesize($css_file4));
            $this->csstext4 = htmlspecialchars($css_text4);
        } else {
            $this->csstext4 = '';
        }
        $this->cssfile4 = $css_file4;
        $this->cssfile_writable4 = $css_writable4;
        
     }
    
    /**
     * Add the page title and toolbar.
     *
     * 
     */
    protected function addToolbar()
    {
        $params = ComponentHelper::getParams('com_jdownloads');
        
        $app = Factory::getApplication();
        Factory::getApplication()->input->set('hidemainmenu', true);

        $canDo    = JDownloadsHelper::getActions();
        $user     = $app->getIdentity();

        $document = Factory::getDocument();
        $document->addStyleSheet('components/com_jdownloads/assets/css/style.css');
        
        ToolBarHelper::title(Text::_('COM_JDOWNLOADS').': '.Text::_('COM_JDOWNLOADS_BACKEND_EDIT_CSS_TITLE_EDIT'), 'jdlogo');
        
        if ($user->authorise('core.admin', 'com_jdownloads') || $user->authorise('core.options', 'com_jdownloads')) {
            ToolBarHelper::save('cssedit.save');
            ToolBarHelper::cancel('cssedit.cancel');
            ToolBarHelper::divider();
            ToolBarHelper::preferences('com_jdownloads');
            ToolBarHelper::divider();
        }
        
        // Add help button - The first integer value must be the corresponding article ID from the documentation
        $help_page = '000&tmpl=jdhelp';
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