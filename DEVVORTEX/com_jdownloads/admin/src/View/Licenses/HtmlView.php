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

namespace JDownloads\Component\JDownloads\Administrator\View\Licenses;
 
\defined('_JEXEC') or die();

use Joomla\Utilities\ArrayHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Helper\ContentHelper;
use Joomla\CMS\Language\Multilanguage;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\GenericDataException;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Pagination\Pagination;
use Joomla\CMS\Toolbar\Toolbar;
use Joomla\CMS\Toolbar\ToolbarHelper;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Uri\Uri;

use JDownloads\Component\JDownloads\Administrator\Helper\JDownloadsHelper;

class HtmlView extends BaseHtmlView
{
    protected $items;
    protected $pagination;
    protected $state;
    protected $sidebar;
    protected $canDo;    
    
    public $filterForm;
    public $activeFilters;
    
    /**
	 * licenses view display method
	 * @return void
	 **/
	function display($tpl = null)
	{
        $this->items         = $this->get('Items');
        $this->pagination    = $this->get('Pagination');
        $this->state         = $this->get('State');
        
        // The filter form file must exist in the models/forms folder (e.g. filter_licenses.xml) 
        $this->filterForm    = $this->get('FilterForm');
        $this->activeFilters = $this->get('ActiveFilters');        
        
        // Check for errors.
        if (count($errors = $this->get('Errors'))) {
            throw new GenericDataException(implode("\n", $errors), 500);
        }
        
        $this->addToolbar();
        
        // We do not need to filter by language when multilingual is disabled
        if (!Multilanguage::isEnabled()) {
            unset($this->activeFilters['language']);
            $this->filterForm->removeField('language', 'filter');
        }
        
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
        
        // Get the toolbar object instance
        $toolbar = Toolbar::getInstance('toolbar');
        
        ToolBarHelper::title(Text::_('COM_JDOWNLOADS').': '.Text::_('COM_JDOWNLOADS_LICENSES'), 'key jdlicenses');
        
        ToolBarHelper::link('index.php?option=com_jdownloads', Text::_('COM_JDOWNLOADS_CPANEL'), 'home-2 cpanel');
        
        if ($canDo->get('core.create')) {
            ToolBarHelper::addNew('license.add');
        }

        if ($canDo->get('core.edit.state')) {
            $dropdown = $toolbar->dropdownButton('status-group')
                ->text('JTOOLBAR_CHANGE_STATUS')
                ->toggleSplit(false)
                ->icon('icon-ellipsis-h')
                ->buttonClass('btn btn-action')
                ->listCheck(true);

            $childBar = $dropdown->getChildToolbar();
            
            if ($canDo->get('core.edit.state')){
                $childBar->publish('licenses.publish')->listCheck(true);
                $childBar->unpublish('licenses.unpublish')->listCheck(true);
            }

            if ($app->getIdentity()->authorise('core.admin')){
                $childBar->checkin('licenses.checkin')->listCheck(true);
            }
            
            if ($canDo->get('core.delete')) {
                $childBar->delete('licenses.delete')
                ->text('COM_JDOWNLOADS_TOOLBAR_REMOVE')
                ->message('COM_JDOWNLOADS_DELETE_LIST_ITEM_CONFIRMATION')
                ->listCheck(true);
            }   
            
             // Add a batch button
            if ($canDo->get('core.create')
                && $canDo->get('core.edit')
                && $canDo->get('core.edit.state'))
            {
                if (Multilanguage::isEnabled()){
                    $childBar->popupButton('batch')
                        ->text('JTOOLBAR_BATCH')
                        ->selector('collapseModal')
                        ->listCheck(true);
                }
            }        
        }
    
        ToolBarHelper::divider();
        
        if ($user->authorise('core.admin', 'com_jdownloads') || $user->authorise('core.options', 'com_jdownloads')) {
            ToolBarHelper::preferences('com_jdownloads');
            ToolBarHelper::divider();
        }         
        
        // Add help button - The first integer value must be the corresponding article ID from the documentation
        $help_page = '277&tmpl=jdhelp'; // Article 'Including a license in a Download'
        $help_url = $params->get('help_url').$help_page;
        $exists_url = JDownloadsHelper::existsHelpServerURL($help_url);
        if ($exists_url){
            ToolBarHelper::help(null, false, $help_url);
        } else {
            ToolBarHelper::help('help.general', true); 
        }

    }
        
}