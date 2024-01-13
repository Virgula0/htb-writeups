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

namespace JDownloads\Component\JDownloads\Administrator\View\Tools; 
 
\defined('_JEXEC') or die();

use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Factory;
use Joomla\CMS\Toolbar\ToolbarHelper;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\MVC\View\GenericDataException;

use JDownloads\Component\JDownloads\Administrator\Helper\JDownloadsHelper;

/**
 * Tools view
 *
 */
class HtmlView extends BaseHtmlView
{
    protected $canDo;
    
    /**
	 * tools display method
	 * @return void
	 **/
	function display($tpl = null)
	{
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
        $user   = $app->getIdentity();
        $canDo  = JDownloadsHelper::getActions();

        $document = Factory::getDocument();
        $document->addStyleSheet('components/com_jdownloads/assets/css/style.css');
        
        ToolBarHelper::title(Text::_('COM_JDOWNLOADS').': '.Text::_('COM_JDOWNLOADS_TOOLS'), 'cogs jdtools');
        
        ToolBarHelper::link('index.php?option=com_jdownloads', Text::_('COM_JDOWNLOADS_CPANEL'), 'home-2 cpanel');
        
        ToolBarHelper::divider();
        
        if ($canDo->get('core.admin', 'com_jdownloads') || $canDo->get('core.options', 'com_jdownloads')) {
            ToolBarHelper::preferences('com_jdownloads');
            ToolBarHelper::divider();
        }         
        
        // Add help button - The first integer value must be the corresponding article ID from the documentation
        $help_page = '209&tmpl=jdhelp'; // Article 'Overview of the jDownloads Tools (J4)'
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