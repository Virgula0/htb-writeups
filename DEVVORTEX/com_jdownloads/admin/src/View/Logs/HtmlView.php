<?php
/*
 * @package Joomla
 * @copyright Copyright (C) 2005 Open Source Matters. All rights reserved.
 * @license http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.txt
 *
 * @component jDownloads
 * @version 4.0  
 * @copyright (C) 2007 - 2022 - Arno Betz - www.jdownloads.com
 * @license http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.txt
 * 
 * jDownloads is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 */

namespace JDownloads\Component\JDownloads\Administrator\View\Logs; 
 
\defined('_JEXEC') or die;

use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Factory;
use Joomla\CMS\Toolbar\ToolbarHelper;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\MVC\View\GenericDataException;

use JDownloads\Component\JDownloads\Administrator\Helper\JDownloadsHelper;

class HtmlView extends BaseHtmlView
{
    protected $items;
    protected $pagination;
    protected $state;
        
    public $filterForm;
    public $activeFilters;
    
    private $isEmptyState = false;
    
    /**
	 * logs view display method
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
        
        if (!\count($this->items) && $this->isEmptyState = $this->get('IsEmptyState'))
        {
            $this->setLayout('emptystate');
        }
        
        $this->logs_header_info = JDownloadsHelper::getLogsHeaderInfo();

        // Check for errors.
        if (count($errors = $this->get('Errors'))) {
            throw new GenericDataException(implode("\n", $errors));
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
        $app    = Factory::getApplication();
        $params = ComponentHelper::getParams('com_jdownloads');
        
        $state    = $this->get('State');
        $canDo    = JDownloadsHelper::getActions();
        $user     = $app->getIdentity();

        $document = Factory::getDocument();
        $document->addStyleSheet('components/com_jdownloads/assets/css/style.css');
        
        ToolBarHelper::title(Text::_('COM_JDOWNLOADS').': '.Text::_('COM_JDOWNLOADS_LOGS_TITLE_HEAD'), 'list-2 jdlogs');
        
        ToolBarHelper::link('index.php?option=com_jdownloads', Text::_('COM_JDOWNLOADS_CPANEL'), 'home-2 cpanel');
        
        if ($canDo->get('core.edit')) {
            ToolBarHelper::custom( 'logs.blockip', 'cancel.png', 'cancel.png', Text::_('COM_JDOWNLOADS_BACKEND_LOG_LIST_BLOCK_IP'), true ); 
        }    
        if ($canDo->get('core.delete')) {
            ToolBarHelper::deleteList(Text::_('COM_JDOWNLOADS_DELETE_LIST_ITEM_CONFIRMATION'), 'logs.delete', 'COM_JDOWNLOADS_TOOLBAR_REMOVE');
            ToolBarHelper::divider();
        } 

        ToolBarHelper::divider();
        
        if ($canDo->get('core.admin', 'com_jdownloads') || $canDo->get('core.options', 'com_jdownloads')) {
            ToolBarHelper::preferences('com_jdownloads');
            ToolBarHelper::divider();
        }         
        
        // Add help button - The first integer value must be the corresponding article ID from the documentation
        $help_page = '279&tmpl=jdhelp'; // Article 'Notes on Global Settings Options'
        $help_url = $params->get('help_url').$help_page;
        $exists_url = JDownloadsHelper::existsHelpServerURL($help_url);
        if ($exists_url){
            ToolBarHelper::help(null, false, $help_url);
        } else {
            ToolBarHelper::help('help.general', true); 
        }

    }
         
}