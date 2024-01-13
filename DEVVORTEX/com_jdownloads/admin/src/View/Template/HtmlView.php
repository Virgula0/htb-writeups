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

namespace JDownloads\Component\JDownloads\Administrator\View\Template;
 
\defined('_JEXEC') or die();

use Joomla\CMS\Language\Text;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\Factory;
use Joomla\CMS\Toolbar\Toolbar;
use Joomla\CMS\Toolbar\ToolbarHelper;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Session\Session;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\MVC\View\GenericDataException;

use JDownloads\Component\JDownloads\Administrator\Helper\JDownloadsHelper;

/**
 * View to edit a template 
 * 
 * 
 **/
class HtmlView extends BaseHtmlView
{
    protected $state;
    protected $item;
    protected $form;
    protected $id_type;
    protected $canDo;
    
    /**
     * Display the view
     * 
     * 
     */
    public function display($tpl = null)
    {
        $this->state       = $this->get('State');
        $this->item        = $this->get('Item');
        $this->form        = $this->get('Form');
        
        // Check for errors.
        if (count($errors = $this->get('Errors'))) {
            throw new GenericDataException(implode("\n", $errors));
        }

        $session = Factory::getSession();
        $type    = (int) $session->get( 'jd_tmpl_type', '' );
        $this->jd_tmpl_type = $type;
        
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
        $checkedOut  = !($this->item->checked_out == 0 || $this->item->checked_out == $user->get('id'));
        
        $canDo       = JDownloadsHelper::getActions();
        
        $document = Factory::getDocument();
        $document->addStyleSheet('components/com_jdownloads/assets/css/style.css');
        $document->addStyleSheet( Uri::root().'components/com_jdownloads/assets/css/jdownloads_fe.css');
        $document->addStyleSheet( Uri::root().'components/com_jdownloads/assets/css/jdownloads_buttons.css');
        $document->addStyleSheet( Uri::root().'components/com_jdownloads/assets/css/jdownloads_custom.css');
        
        $layout_type = '';
        
        // set the correct text in title for every layout type
        switch ($this->jd_tmpl_type) {
            case '1':  $layout_type = Text::_('COM_JDOWNLOADS_BACKEND_TEMP_TYP1'); break;
            case '2':  $layout_type = Text::_('COM_JDOWNLOADS_BACKEND_TEMP_TYP2'); break;
            case '3':  $layout_type = Text::_('COM_JDOWNLOADS_BACKEND_TEMP_TYP3'); break;
            case '4':  $layout_type = Text::_('COM_JDOWNLOADS_BACKEND_TEMP_TYP4'); break;
            case '5':  $layout_type = Text::_('COM_JDOWNLOADS_BACKEND_TEMP_TYP5'); break;
            case '6':  $layout_type = Text::_('COM_JDOWNLOADS_BACKEND_TEMP_TYP6'); break;
            case '7':  $layout_type = Text::_('COM_JDOWNLOADS_BACKEND_TEMP_TYP7'); break;
            case '8':  $layout_type = Text::_('COM_JDOWNLOADS_BACKEND_TEMP_TYP8'); break;
        }
        
        $toolbar = Toolbar::getInstance();
        
        $title = ($isNew) ? Text::_('COM_JDOWNLOADS_BACKEND_TEMPEDIT_ADD').': '.$layout_type : Text::_('COM_JDOWNLOADS_BACKEND_TEMPEDIT_EDIT').': '.$layout_type; 
        ToolBarHelper::title(Text::_('COM_JDOWNLOADS').': '.$title, 'pencil-2 jdlayouts'); 

        // For new records, check the create permission.
        if ($isNew && $canDo->get('core.create')){
            $toolbar->apply('template.apply');

            $saveGroup = $toolbar->dropdownButton('save-group');

            $saveGroup->configure(
                function (Toolbar $childBar) use ($user)
                {
                    $childBar->save('template.save');

                    $childBar->save2new('template.save2new');
                }
            );

            $toolbar->cancel('template.cancel', 'JTOOLBAR_CLOSE');
        
        } else {
            
            // Since it's an existing record, check the edit permission, or fall back to edit own if the owner.
            $itemEditable = $canDo->get('core.edit') || ($canDo->get('core.edit.own') && $this->item->created_by == $userId);

            if (!$checkedOut && $itemEditable)
            {
                $toolbar->apply('template.apply');
            }

            $saveGroup = $toolbar->dropdownButton('save-group');

            $saveGroup->configure(
                function (Toolbar $childBar) use ($checkedOut, $itemEditable, $canDo, $user)
                {
                    // Can't save the record if it's checked out and editable
                    if (!$checkedOut && $itemEditable)
                    {
                        $childBar->save('template.save');

                        // We can save this record, but check the create permission to see if we can return to make a new one.
                        if ($canDo->get('core.create'))
                        {
                            $childBar->save2new('template.save2new');
                        }
                    }

                    // If checked out, we can still save
                    if ($canDo->get('core.create'))
                    {
                        $childBar->save2copy('template.save2copy');
                    }
                }
            );

            $toolbar->cancel('template.cancel', 'COM_JDOWNLOADS_TOOLBAR_CLOSE');
        }
        
        $toolbar->divider();
        
        // Add help button - The first integer value must be the corresponding article ID from the documentation
        $help_page = '158&tmpl=jdhelp';  // Article 'Layout - Place Holders'
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