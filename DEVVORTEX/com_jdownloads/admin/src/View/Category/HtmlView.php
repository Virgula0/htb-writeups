<?php
/* @component jDownloads
 * @version 4.0  
 * @copyright (C) 2007 - 2021 - Arno Betz - www.jdownloads.com
 * @license http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.txt
 * 
 * jDownloads is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 */

namespace JDownloads\Component\JDownloads\Administrator\View\Category;
 
\defined('_JEXEC') or die;

use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Helper\ContentHelper;
use Joomla\CMS\Helper\TagsHelper;
use Joomla\CMS\Language\Associations;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\GenericDataException;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Toolbar\ToolbarHelper;
use Joomla\CMS\UCM\UCMType;

use JDownloads\Component\JDownloads\Administrator\Helper\JDownloadsHelper;

/**
 * View to edit a category 
 * 
 * 
 **/

class HtmlView extends BaseHtmlView
{
    protected $state;
    protected $item;
    protected $form;
    protected $canDo;
    protected $assoc;
    protected $checkTags = false;
    
    /**
     * Display the view
     * 
     * 
     */
    public function display($tpl = null)
    {
        $app = Factory::getApplication();
        $app->setUserState('type', 'category');  
        
        $this->form        = $this->get('Form');
        $this->item        = $this->get('Item');
        $this->state       = $this->get('State');
        
        // Get the users Permissions
        $this->canDo = ContentHelper::getActions('com_jdownloads', 'category', $this->item->id);
        
        $this->assoc = $this->get('Assoc');

        // Check for errors.
        if (count($errors = $this->get('Errors')))
        {
            throw new GenericDataException(implode("\n", $errors), 500);
        }
        
        // Check for tag type
        if (!empty(TagsHelper::getTypes('objectList', array($this->state->get('category.extension') . '.category'), true)))
        {
            $this->checkTags = true;
        }
        
        Factory::getApplication()->input->set('hidemainmenu', true);
        
        // If we are forcing a language in modal (used for associations).
        if ($this->getLayout() === 'modal' && $forcedLanguage = Factory::getApplication()->input->get('forcedLanguage', '', 'cmd'))
        {
            // Set the language field to the forcedLanguage and disable changing it.
            $this->form->setValue('language', null, $forcedLanguage);
            $this->form->setFieldAttribute('language', 'readonly', 'true');

            // Only allow to select categories with All language or with the forced language.
            $this->form->setFieldAttribute('parent_id', 'language', '*,' . $forcedLanguage);

            // Only allow to select tags with All language or with the forced language.
            $this->form->setFieldAttribute('tags', 'language', '*,' . $forcedLanguage);
        }
        
        // We don't need toolbar in the modal window.
        if ($this->getLayout() !== 'modal') {        
            $this->addToolbar();
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
        $params = ComponentHelper::getParams('com_jdownloads');
        
        $user       = Factory::getApplication()->getIdentity();
        $userId     = $user->id;
        
        $isNew      = ($this->item->id == 0);
        $checkedOut = !(is_null($this->item->checked_out) || $this->item->checked_out == $userId);
        
        // Get the results for each action.
        $canDo = $this->canDo;
        
        // Check to see if the type exists
        $ucmType = new UcmType;
        $this->typeId = $ucmType->getTypeId('com_jdownloads.category');
        
        $document = Factory::getDocument();
        $document->addStyleSheet('components/com_jdownloads/assets/css/style.css');
                                         
        $title = ($isNew) ? Text::_('COM_JDOWNLOADS_EDIT_CAT_ADD') : Text::_('COM_JDOWNLOADS_EDIT_CAT_EDIT'); 
        ToolBarHelper::title(Text::_('COM_JDOWNLOADS').': '.$title, 'pencil-2 jdcategories'); 

        // For new records, check the create permission.
        if ($isNew && (count(JDownloadsHelper::getAuthorisedJDCategories('core.create')) > 0))
        {
            ToolbarHelper::apply('category.apply');
            ToolbarHelper::saveGroup(
                [
                    ['save', 'category.save'],
                    ['save2new', 'category.save2new']
                ],
                'btn-success'
            );

            ToolbarHelper::cancel('category.cancel');
        }

        // If not checked out, can save the item.
        else
        {
            // Since it's an existing record, check the edit permission, or fall back to edit own if the owner.
            $itemEditable = $canDo->get('core.edit') || ($canDo->get('core.edit.own') && $this->item->created_user_id == $userId);

            $toolbarButtons = [];

            // Can't save the record if it's checked out and editable
            if (!$checkedOut && $itemEditable)
            {
                ToolbarHelper::apply('category.apply');

                $toolbarButtons[] = ['save', 'category.save'];

                if ($canDo->get('core.create'))
                {
                    $toolbarButtons[] = ['save2new', 'category.save2new'];
                }
            }

            // If an existing item, can save to a copy.
            if ($canDo->get('core.create'))
            {
                $toolbarButtons[] = ['save2copy', 'category.save2copy'];
            }

            ToolbarHelper::saveGroup(
                $toolbarButtons,
                'btn-success'
            );

            ToolbarHelper::cancel('category.cancel', 'COM_JDOWNLOADS_TOOLBAR_CLOSE');

            /*
            if (Associations::isEnabled() && ComponentHelper::isEnabled('com_associations'))
            {
                ToolbarHelper::custom('category.editAssociations', 'contract', '', 'JTOOLBAR_ASSOCIATIONS', false, false);
            }
            */
        }
        
        ToolBarHelper::divider();
        
        // Add help button - The first integer value must be the corresponding article ID from the documentation
        $help_page = '291&tmpl=jdhelp'; // Article 'Create a Category in Backend'
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