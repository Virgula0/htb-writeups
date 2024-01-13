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
 
namespace JDownloads\Component\JDownloads\Administrator\View\Templates;
 
\defined('_JEXEC') or die();

use Joomla\CMS\Language\Text;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Toolbar\Toolbar;
use Joomla\CMS\Toolbar\ToolbarHelper;
use Joomla\CMS\Session\Session;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\MVC\View\GenericDataException;

use JDownloads\Component\JDownloads\Administrator\Helper\JDownloadsHelper;

//HtmlHelper::_('jquery.framework');

class HtmlView extends BaseHtmlView
{
    protected $items;
    protected $pagination;
    protected $state;

    public $filterForm;
    public $activeFilters;

    /**
	 * templates view display method
	 * @return void
	 **/
	function display($tpl = null)
	{
        $this->state        = $this->get('State');
        $this->items        = $this->get('Items');
        $this->pagination   = $this->get('Pagination');
        
        // the filter form file must exist in the models/forms folder (e.g. filter_files.xml) 
        $this->filterForm    = $this->get('FilterForm');
        $this->activeFilters = $this->get('ActiveFilters');        
        
        $params = $this->state->params;        

        // Check for errors.
        if (count($errors = $this->get('Errors'))) {
            throw new GenericDataException(implode("\n", $errors));
        }
   
        // get the template type from session to handle the correct output for every type.
        $session = Factory::getSession();
        $type    = (int) $session->get( 'jd_tmpl_type', '' );
        $this->jd_tmpl_type = $type;
            
        // array with template typ name 
        $temp_type = array();
        $temp_type[1] = Text::_('COM_JDOWNLOADS_BACKEND_TEMP_TYP1');
        $temp_type[2] = Text::_('COM_JDOWNLOADS_BACKEND_TEMP_TYP2');
        $temp_type[3] = Text::_('COM_JDOWNLOADS_BACKEND_TEMP_TYP3');
        $temp_type[4] = Text::_('COM_JDOWNLOADS_BACKEND_TEMP_TYP4');
        $temp_type[5] = Text::_('COM_JDOWNLOADS_BACKEND_TEMP_TYP5');
        $temp_type[6] = Text::_('COM_JDOWNLOADS_BACKEND_TEMP_TYP6');
        $temp_type[7] = Text::_('COM_JDOWNLOADS_BACKEND_TEMP_TYP7');
        $temp_type[8] = Text::_('COM_JDOWNLOADS_BACKEND_TEMP_TYP8');
        $this->temp_type_name = $temp_type;    
        
        if ($params->get('use_lightbox_function')){
            $document = Factory::getDocument();
            $document->addScript(Uri::base().'components/com_jdownloads/assets/lightbox/src/js/lightbox.js');
            $document->addStyleSheet( Uri::base()."components/com_jdownloads/assets/lightbox/src/css/lightbox.css", 'text/css', null, array() );
        }    
        
        $this->addToolbar();
        parent::display($tpl);
    }    
        
    /**
     * Add the page title and toolbar.
     *
     * 
     */                                          
    protected function addToolbar()
    {
        $params = ComponentHelper::getParams('com_jdownloads');
        
        $canDo    = JDownloadsHelper::getActions();
        $app      = Factory::getApplication();
        $user     = $app->getIdentity();

        $document = Factory::getDocument();
        $document->addStyleSheet('components/com_jdownloads/assets/css/style.css');
        
        // Get the toolbar object instance
        $toolbar = Toolbar::getInstance('toolbar');
        
        if ($this->jd_tmpl_type != ''){
            $type = 'type'.(int)$this->jd_tmpl_type;
        } else {
            $type = 'cssedit';
        }
        
        // set the correct text in title for every layout type
        switch ($this->jd_tmpl_type) {
            case '1':  $layout_type = Text::_('COM_JDOWNLOADS_BACKEND_TEMP_TYP1'); $this->active1 = 'active'; break;
            case '2':  $layout_type = Text::_('COM_JDOWNLOADS_BACKEND_TEMP_TYP2'); $this->active2 = 'active'; break;
            case '3':  $layout_type = Text::_('COM_JDOWNLOADS_BACKEND_TEMP_TYP3'); $this->active3 = 'active'; break;
            case '4':  $layout_type = Text::_('COM_JDOWNLOADS_BACKEND_TEMP_TYP4'); $this->active4 = 'active'; break;
            case '5':  $layout_type = Text::_('COM_JDOWNLOADS_BACKEND_TEMP_TYP5'); $this->active5 = 'active'; break;
            case '6':  $layout_type = Text::_('COM_JDOWNLOADS_BACKEND_TEMP_TYP6'); $this->active6 = 'active'; break;
            case '7':  $layout_type = Text::_('COM_JDOWNLOADS_BACKEND_TEMP_TYP7'); $this->active7 = 'active'; break;
            case '8':  $layout_type = Text::_('COM_JDOWNLOADS_BACKEND_TEMP_TYP8'); $this->active8 = 'active'; break;
            default: $layout_type = ''; 
        }
        
        $this->layout_type = $layout_type;
        
        ToolBarHelper::title(Text::_('COM_JDOWNLOADS').': '.Text::_('COM_JDOWNLOADS_BACKEND_CPANEL_TEMPLATES_NAME').': '.$layout_type, 'brush jdlayouts'); 
        
        ToolBarHelper::link('index.php?option=com_jdownloads', Text::_('COM_JDOWNLOADS_CPANEL'), 'home-2 cpanel');
        
        ToolBarHelper::custom( 'templates.cancel', 'list.png', 'list.png', Text::_('COM_JDOWNLOADS_LAYOUTS'), false, false );
        ToolBarHelper::divider();
        
        if ($canDo->get('core.create')) {
            ToolBarHelper::addNew('template.add');
        }

        if ($canDo->get('core.edit.state')) {
            $dropdown = $toolbar->dropdownButton('status-group')
                ->text('JTOOLBAR_CHANGE_STATUS')
                ->toggleSplit(false)
                ->icon('icon-ellipsis-h')
                ->buttonClass('btn btn-action')
                ->listCheck(true);

            $childBar = $dropdown->getChildToolbar();

            if ($canDo->get('core.edit')) {
                $childBar->publish('templates.activate')
                ->text('COM_JDOWNLOADS_BACKEND_TEMPLIST_MENU_TEXT_ACTIVE')
                ->listCheck(true);
            }
            
            if ($app->getIdentity()->authorise('core.admin')){
                $childBar->checkin('templates.checkin')->listCheck(true);
            }
            
            if ($canDo->get('core.delete')) {
                $childBar->delete('templates.delete')
                ->text('COM_JDOWNLOADS_TOOLBAR_REMOVE')
                ->message('COM_JDOWNLOADS_DELETE_LIST_ITEM_CONFIRMATION')
                ->listCheck(true);
            }
            
            if ($canDo->get('core.edit')) {
                ToolBarHelper::custom( 'layouts.install', 'upload.png', 'upload.png', Text::_('COM_JDOWNLOADS_LAYOUTS_IMPORT_LABEL'), false, false); 
                ToolBarHelper::custom( 'templates.export', 'download.png', 'download.png', Text::_('COM_JDOWNLOADS_LAYOUTS_EXPORT_LABEL'), true, false); 
            }   
            
        }
        
        if ($canDo->get('core.admin', 'com_jdownloads') || $canDo->get('core.options', 'com_jdownloads')) {
            ToolBarHelper::preferences('com_jdownloads');
        }
        
        ToolBarHelper::divider();
        
        // Add help button - The first integer value must be the corresponding article ID from the documentation
        $help_page = '138&tmpl=jdhelp';  // Article 'Which Layout is used where?'
        $help_url = $params->get('help_url').$help_page;
        $exists_url = JDownloadsHelper::existsHelpServerURL($help_url);
        if ($exists_url){
            ToolBarHelper::help(null, false, $help_url);
        } else {
            ToolBarHelper::help('help.general', true); 
        }

    }
}