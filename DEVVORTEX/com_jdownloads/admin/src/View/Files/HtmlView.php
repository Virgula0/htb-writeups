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

namespace JDownloads\Component\JDownloads\Administrator\View\Files;

\defined('_JEXEC') or die();

use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Factory;
use Joomla\CMS\Toolbar\ToolbarHelper;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\MVC\View\GenericDataException;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Filesystem\File;
use Joomla\CMS\Filesystem\Folder;

use JDownloads\Component\JDownloads\Administrator\Helper\JDownloadsHelper;

/**
 * Files View
 *
 * @package    jDownloads
 */
class HtmlView extends BaseHtmlView
{
	protected $items;
    protected $pagination;
    protected $state;
    
    public $filterForm;
    public $activeFilters;
    
    private $isEmptyState = false;
    
    /**
     * list files display method
     * @return void
     **/
    function display($tpl = null)
    {
        $this->state = $this->get('State');
        
        // Get the files data from the model
        $items = $this->get('Items');

        // Assign data to the view
        $this->items        = $items;
        $this->pagination   = $this->get('Pagination');
        $this->state        = $this->get('state');
        
        // The filter form file must exist in the models/forms folder (e.g. filter_files.xml) 
        $this->filterForm    = $this->get('FilterForm');
        $this->activeFilters = $this->get('ActiveFilters');
        
        if (!\count($this->items)){
            $this->setLayout('emptystate');
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
        $app    = Factory::getApplication();
        $canDo  = JDownloadsHelper::getActions();
        $user   = $app->getIdentity();

        $document = Factory::getDocument();
        $document->addStyleSheet('components/com_jdownloads/assets/css/style.css');
        
        ToolBarHelper::title(Text::_('COM_JDOWNLOADS').': '.Text::_('COM_JDOWNLOADS_FILES'), 'copy jdfiles');
        
        ToolBarHelper::link('index.php?option=com_jdownloads', Text::_('COM_JDOWNLOADS_CPANEL'), 'home-2 cpanel');

        ToolBarHelper::custom( 'files.uploads', 'upload.png', 'upload.png', Text::_('COM_JDOWNLOADS_FILESLIST_TITLE_FILES_UPLOAD'), false );
        ToolBarHelper::custom( 'files.downloads', 'stack.png', 'stack.png', Text::_('COM_JDOWNLOADS_DOWNLOADS'), false );
                    
        if ($canDo->get('core.delete')) {
            ToolBarHelper::deleteList(Text::_('COM_JDOWNLOADS_DELETE_LIST_ITEM_CONFIRMATION'), 'files.delete', 'COM_JDOWNLOADS_TOOLBAR_REMOVE');
            ToolBarHelper::divider();
        } 

        ToolBarHelper::divider();
        
        if ($user->authorise('core.admin', 'com_jdownloads') || $user->authorise('core.options', 'com_jdownloads')) {
            ToolBarHelper::preferences('com_jdownloads');
            ToolBarHelper::divider();
        }         
        
        // Add help button - The first integer value must be the corresponding article ID from the documentation
        $help_page = '208&tmpl=jdhelp';  //article is 'Creating Downloads with the Files facility'
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