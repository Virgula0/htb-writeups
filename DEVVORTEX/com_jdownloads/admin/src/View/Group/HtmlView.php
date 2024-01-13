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

namespace JDownloads\Component\JDownloads\Administrator\View\Group;
 
\defined('_JEXEC') or die;

use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Factory;
use Joomla\CMS\Toolbar\ToolbarHelper;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\MVC\View\GenericDataException;

use JDownloads\Component\JDownloads\Administrator\Helper\JDownloadsHelper;

/**
 * View to edit jDownloads limits from a Joomla user group.
 *
 */
class HtmlView extends BaseHtmlView
{
	protected $form;
	protected $item;
	protected $state;
    protected $canDo;

	/**
	 * Display the view
	 */
	public function display($tpl = null)
	{
        $this->state	= $this->get('State');
		$this->item		= $this->get('Item');
        $this->group_id = $this->item->group_id;
		$this->form		= $this->get('Form');

		// Check for errors.
		if (count($errors = $this->get('Errors'))) {
			throw new GenericDataException(implode("\n", $errors));
		}

        $this->form->title = JDownloadsHelper::getUserGroupInfos($this->item->group_id);

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
        Factory::getApplication()->input->set('hidemainmenu', true);

		$user		= Factory::getApplication()->getIdentity();
		$isNew		= ($this->item->id == 0);
        $checkedOut = !($this->item->checked_out == 0 || $this->item->checked_out == $user->get('id'));        
		$canDo		= JDownloadsHelper::getActions();
        
        $document = Factory::getDocument();
        $document->addStyleSheet('components/com_jdownloads/assets/css/style.css');

		ToolBarHelper::title(Text::_('COM_JDOWNLOADS').': '.Text::_('COM_JDOWNLOADS_USERGROUP_EDIT_TITLE'), 'pencil-2 jdgroups');

		if ($canDo->get('edit.user.limits')) {
			ToolBarHelper::apply('group.apply');
			ToolBarHelper::save('group.save');
		}

        ToolBarHelper::cancel('group.cancel', 'JTOOLBAR_CLOSE');

		ToolBarHelper::divider();
        
        // Add help button - The first integer value must be the corresponding article ID from the documentation
        $help_page = '90&tmpl=jdhelp';  //article 'Which User-Group is Used? (v39 & v40)'
        $help_url = $params->get('help_url').$help_page;
        $exists_url = JDownloadsHelper::existsHelpServerURL($help_url);
        if ($exists_url){
            ToolBarHelper::help(null, false, $help_url);
        } else {
            ToolBarHelper::help('help.general', true); 
        }
	}
}
