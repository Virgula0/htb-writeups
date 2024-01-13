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

namespace JDownloads\Component\JDownloads\Administrator\View\License;
 
\defined('_JEXEC') or die();

use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Factory;
use Joomla\CMS\Toolbar\Toolbar;
use Joomla\CMS\Toolbar\ToolbarHelper;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\MVC\View\GenericDataException;

use JDownloads\Component\JDownloads\Administrator\Helper\JDownloadsHelper;

/**
 * View to edit a license 
 * 
 * 
 **/
class HtmlView extends BaseHtmlView
{
    protected $state;
    protected $item;
    protected $form;
    protected $canDo;
    
    /**
     * Display the view
     * 
     * 
     */
    public function display($tpl = null)
    {
        $this->form        = $this->get('Form');
        $this->item        = $this->get('Item');
        $this->state       = $this->get('State');
                
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
        $params = ComponentHelper::getParams('com_jdownloads');  
        
        Factory::getApplication()->input->set('hidemainmenu', true);

        $user        = Factory::getApplication()->getIdentity();
        $userId      = $user->id;
        $isNew       = ($this->item->id == 0);
        $checkedOut  = !($this->item->checked_out == 0 || $this->item->checked_out == $userId);
        
        $canDo       = JDownloadsHelper::getActions();
        
        $toolbar = Toolbar::getInstance();
        
        $document = Factory::getDocument();
        $document->addStyleSheet('components/com_jdownloads/assets/css/style.css');
        
        $title = ($isNew) ? Text::_('COM_JDOWNLOADS_LICEDIT_ADD') : Text::_('COM_JDOWNLOADS_LICEDIT_EDIT'); 
        ToolBarHelper::title(Text::_('COM_JDOWNLOADS').': '.$title, 'pencil-2 jdlicenses'); 

        // For new records, check the create permission.
        if ($isNew && $canDo->get('core.create')){
            $toolbar->apply('license.apply');

            $saveGroup = $toolbar->dropdownButton('save-group');

            $saveGroup->configure(
                function (Toolbar $childBar) use ($user)
                {
                    $childBar->save('license.save');

                    $childBar->save2new('license.save2new');
                }
            );

            $toolbar->cancel('license.cancel', 'JTOOLBAR_CLOSE');
        
        } else {
            
            // Since it's an existing record, check the edit permission, or fall back to edit own if the owner.
            $itemEditable = $canDo->get('core.edit') || ($canDo->get('core.edit.own') && $this->item->created_by == $userId);

            if (!$checkedOut && $itemEditable)
            {
                $toolbar->apply('license.apply');
            }

            $saveGroup = $toolbar->dropdownButton('save-group');

            $saveGroup->configure(
                function (Toolbar $childBar) use ($checkedOut, $itemEditable, $canDo, $user)
                {
                    // Can't save the record if it's checked out and editable
                    if (!$checkedOut && $itemEditable)
                    {
                        $childBar->save('license.save');

                        // We can save this record, but check the create permission to see if we can return to make a new one.
                        if ($canDo->get('core.create'))
                        {
                            $childBar->save2new('license.save2new');
                        }
                    }

                    // If checked out, we can still save
                    if ($canDo->get('core.create'))
                    {
                        $childBar->save2copy('license.save2copy');
                    }
                }
            );

            $toolbar->cancel('license.cancel', 'COM_JDOWNLOADS_TOOLBAR_CLOSE');
        }
        
        $toolbar->divider();
        
        // Add help button - The first integer value must be the corresponding article ID from the documentation
        $help_page = '277&tmpl=jdhelp'; // Article 'Including a license in a Download'
        $help_url = $params->get('help_url').$help_page;
        $exists_url = JDownloadsHelper::existsHelpServerURL($help_url);
        
        if ($exists_url){
            ToolBarHelper::help(null, false, $help_url);
        } else {
            ToolbarHelper::help('help.general', true); 
        }
    }    
   
}
?>