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

namespace JDownloads\Component\JDownloads\Administrator\View\Uninstall; 
 
\defined('_JEXEC') or die;

use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Application\ApplicationHelper;
use Joomla\CMS\Helper\ModuleHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Factory;
use Joomla\CMS\Toolbar\ToolbarHelper;
use Joomla\CMS\Toolbar\Toolbar;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\MVC\View\GenericDataException;

use JDownloads\Component\JDownloads\Administrator\Helper\JDownloadsHelper;

/**
 * Uninstall View
 *
 */
class HtmlView extends BaseHtmlView
{
    protected $form;
    protected $canDo;

	/**
	 * Uninstall display method
	 * @return void
	 **/
	function display($tpl = null)
	{
        $jDownloadsComponent = Factory::getApplication()->bootComponent('com_jdownloads');
        $model               = $jDownloadsComponent->getMVCFactory()->createModel('Uninstall', 'Administrator', ['ignore_request' => true]);
        $this->form = $model->getForm();
        
        // What Access Permissions does this user have? 
        $this->canDo = JDownloadsHelper::getActions();        
        
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
        $user     = Factory::getApplication()->getIdentity();

        $document = Factory::getDocument();
        $document->addStyleSheet('components/com_jdownloads/assets/css/style.css');
        
        ToolBarHelper::title(Text::_('COM_JDOWNLOADS').': '.Text::_('COM_JDOWNLOADS_UNINSTALL'), 'cancel jduninstall');
        
        // Add help button - The first integer value must be the corresponding article ID from the documentation
        $help_page = '253&tmpl=jdhelp'; // Article 'How to Uninstall ALL of jDownloads? (J4)'
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