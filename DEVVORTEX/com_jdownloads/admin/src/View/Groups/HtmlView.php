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

namespace JDownloads\Component\JDownloads\Administrator\View\Groups; 
 
\defined('_JEXEC') or die;

use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Factory;
use Joomla\CMS\Toolbar\ToolbarHelper;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\MVC\View\GenericDataException;

use JDownloads\Component\JDownloads\Administrator\Helper\JDownloadsHelper;

/**
 * View class for a list of user groups.
 *
 */
class HtmlView extends BaseHtmlView
{
	protected $items;
	protected $pagination;
	protected $state;
    
    public $filterForm;
    public $activeFilters;

	/**
	 * Display the view
	 */
	public function display($tpl = null)
	{
		$this->items		 = $this->get('Items');
		$this->pagination	 = $this->get('Pagination');
		$this->state		 = $this->get('State');
        
        // The filter form file must exist in the models/forms folder (e.g. filter_files.xml) 
        $this->filterForm    = $this->get('FilterForm');
        $this->activeFilters = $this->get('ActiveFilters');

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
	 */
	protected function addToolbar()
	{
        $params = ComponentHelper::getParams('com_jdownloads');
        
        $document = Factory::getDocument();
        $document->addStyleSheet('components/com_jdownloads/assets/css/style.css');
        
        ToolBarHelper::title(Text::_('COM_JDOWNLOADS').': '.Text::_('COM_JDOWNLOADS_USER_GROUPS'), 'users jdgroups');

        ToolBarHelper::link('index.php?option=com_jdownloads', Text::_('COM_JDOWNLOADS_CPANEL'), 'home-2 cpanel');
        
        $canDo = JDownloadsHelper::getActions();
        
        if ($canDo->get('edit.user.limits') || $canDo->get('core.admin')) {
			ToolBarHelper::editList('group.edit', 'COM_JDOWNLOADS_USERGROUPS_CHANGE_LIMITS_TITLE');
			ToolBarHelper::divider();
		}

		if ($canDo->get('core.admin', 'com_jdownloads') || $canDo->get('core.options', 'com_jdownloads')) {
            ToolBarHelper::custom( 'groups.resetLimits', 'refresh.png', 'refresh.png', Text::_('COM_JDOWNLOADS_USERGROUPS_RESET_LIMITS_TITLE'), true, false );
			ToolBarHelper::divider();
		}
        
        if ($canDo->get('core.admin', 'com_jdownloads') || $canDo->get('core.options', 'com_jdownloads')) {
            ToolBarHelper::preferences('com_jdownloads');
            ToolBarHelper::divider();
        }         
        
        // Add help button - The first integer value must be the corresponding article ID from the documentation
        $help_page = '167&tmpl=jdhelp'; // Article 'An Overview of User Groups Settings'
        $help_url = $params->get('help_url').$help_page;
        $exists_url = JDownloadsHelper::existsHelpServerURL($help_url);
        if ($exists_url){
            ToolBarHelper::help(null, false, $help_url);
        } else {
            ToolBarHelper::help('help.general', true); 
        }
        
	}

}    