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

namespace JDownloads\Component\JDownloads\Administrator\View\Download; 
 
\defined('_JEXEC') or die;

use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Helper\ContentHelper;
use Joomla\CMS\Language\Associations;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\GenericDataException;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Toolbar\Toolbar;
use Joomla\CMS\Toolbar\ToolbarHelper;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Filter\OutputFilter;
use Joomla\CMS\Session\Session;
use Joomla\CMS\Plugin\PluginHelper;

use JDownloads\Component\JDownloads\Administrator\Helper\JDownloadsHelper;
use JDownloads\Component\JDownloads\Site\Helper\RouteHelper;

/**
 * View to edit a Download 
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
        HTMLHelper::_('bootstrap.framework');  
        
        require_once JPATH_SITE.'/components/com_jdownloads/src/Helper/RouteHelper.php';
        
        $app = Factory::getApplication();
        $app->setUserState('type', 'download');
        
        $this->form        = $this->get('Form');
        $this->item        = $this->get('Item');
        $this->state       = $this->get('State');        
        
        // What Access Permissions does this user have? What can (s)he do?
        $this->canDo = ContentHelper::getActions('com_jdownloads', 'download', $this->item->id);
        
        // get filename when selected in files list
        $session = Factory::getSession();
        $filename = $session->get('jd_filename');
        if ($filename){
            $this->selected_filename =  OutputFilter::cleanText($filename);
        }    

        // Check for errors.
        if (count($errors = $this->get('Errors'))) {
            throw new GenericDataException(implode("\n", $errors));
        }
        
        // Added to support the Joomla Language Associations
        // If we are forcing a language in modal (used for associations).
        if ($this->getLayout() === 'modal' && $forcedLanguage = Factory::getApplication()->input->get('forcedLanguage', '', 'cmd'))
        {
            // Set the language field to the forcedLanguage and disable changing it.
            $this->form->setValue('language', null, $forcedLanguage);
            $this->form->setFieldAttribute('language', 'readonly', 'true');

            // Only allow to select categories with All language or with the forced language.
            $this->form->setFieldAttribute('catid', 'language', '*,' . $forcedLanguage);

            // Only allow to select tags with All language or with the forced language.
            $this->form->setFieldAttribute('tags', 'language', '*,' . $forcedLanguage);
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
        
        $app        = Factory::getApplication();
        $user       = $app->getIdentity();
        $userId     = $user->id;
        $isNew      = ($this->item->id == 0);
        $checkedOut = !(is_null($this->item->checked_out) || $this->item->checked_out == $userId);
        
        // Get the results for each action.
        $canDo = $this->canDo;
        
        $toolbar = Toolbar::getInstance();
        
        $document = Factory::getDocument();
        $document->addStyleSheet('components/com_jdownloads/assets/css/style.css');
        
        $document->addScriptDeclaration('
        // dynamically add a new image file upload field when the prior generated fields is used
        // used in backend edit download page
        function add_new_image_file(field)
        {
            // Get the number of files previously uploaded.
            var count = parseInt(document.getElementById(\'image_file_count\').value);
            var sum = parseInt(document.getElementById(\'sum_listed_images\').value);
            var max = parseInt(document.getElementById(\'max_sum_images\').value);
            
            // Get the name of the file that has just been uploaded.
            var file_name = document.getElementById("file_upload_thumb["+count+"]").value;
           
            // Hide the file upload control containing the information about the picture that was just uploaded.
            document.getElementById(\'new_file_row\').style.display = "none";
            document.getElementById(\'new_file_row\').id = "new_file_row["+count+"]";
           
            // Get a reference to the table containing the uploaded pictures.       
            var table = document.getElementById(\'files_table\');
           
            // Insert a new row with the file name and a delete button.
            var row = table.insertRow(table.rows.length);
            row.id = "inserted_file["+count+"]";
            var cell0 = row.insertCell(0);
            cell0.innerHTML = \'<input type="text" disabled="disabled" name="inserted_file[\'+count+\']" value="\'+file_name+\'" class="form-control" size="40" /><input type="button" name="delete[\'+count+\']" value="'.Text::_('COM_JDOWNLOADS_REMOVE').'" onclick="delete_inserted_image_field(this)">\';
           
            // Increment count of the number of files uploaded.
            ++count;
            if (count+sum < max){
                // Insert a new file upload control in the table.
                var row = table.insertRow(table.rows.length);
                row.id = "new_file_row";
                var cell0 = row.insertCell(0);
                cell0.innerHTML = \'<input type="file" name="file_upload_thumb[\'+count+\']" id="file_upload_thumb[\'+count+\']" class="form-control" size="40" accept="image/gif,image/jpeg,image/jpg,image/png" onchange="add_new_image_file(this)" />\';   
            }
            // Update the value of the file hidden input tag holding the count of files uploaded.
            document.getElementById(\'image_file_count\').value = count;
        }

        // user will remove the files they have previously added
        // used in backend edit download page
        function delete_inserted_image_field(field)
        {
            // Get the field name.
            var name = field.name;
            
            // Extract the file id from the field name.
            var id = name.substr(name.indexOf(\'[\') + 1, name.indexOf(\']\') - name.indexOf(\'[\') - 1);
           
            // Hide the row displaying the uploaded file name.
            document.getElementById("inserted_file["+id+"]").style.display = "none";
               
            // Get a reference to the uploaded file control.
            var control = document.getElementById("file_upload_thumb["+id+"]");
           
            // Remove the new file control.
            control.parentNode.removeChild(control);
            
            // check that we have always a input field when we remove a other file
            var found = false;
            for (var i = 0; i <= 30; i++){
                 if (document.adminForm.elements["file_upload_thumb["+i+"]"]) {
                    found = true;
                 }
            }
            if (!found) add_new_image_file(field);
        }');         
        
        $title = ($isNew) ? Text::_('COM_JDOWNLOADS_BACKEND_FILESEDIT_ADD') : Text::_('COM_JDOWNLOADS_BACKEND_FILESEDIT_EDIT'); 
        ToolBarHelper::title(Text::_('COM_JDOWNLOADS').': '.$title, 'pencil-2 jddownloads'); 

        // For new records, check the create permission.
        if ($isNew && (count(JDownloadsHelper::getAuthorisedJDCategories('core.create')) > 0)) {
            $toolbar->apply('download.apply');

            $saveGroup = $toolbar->dropdownButton('save-group');

            $saveGroup->configure(
                function (Toolbar $childBar) use ($user) {
                    $childBar->save('download.save');

                    $childBar->save2new('download.save2new');
                }
            );

            $toolbar->cancel('download.cancel', 'JTOOLBAR_CLOSE');
        } else {
            // Since it's an existing record, check the edit permission, or fall back to edit own if the owner.
            $itemEditable = $canDo->get('core.edit') || ($canDo->get('core.edit.own') && $this->item->created_by == $userId);

            if (!$checkedOut && $itemEditable) {
                $toolbar->apply('download.apply');
            }

            $saveGroup = $toolbar->dropdownButton('save-group');

            $saveGroup->configure(
                function (Toolbar $childBar) use ($checkedOut, $itemEditable, $canDo, $user) {
                    // Can't save the record if it's checked out and editable
                    if (!$checkedOut && $itemEditable) {
                        $childBar->save('download.save');

                        // We can save this record, but check the create permission to see if we can return to make a new one.
                        if ($canDo->get('core.create')) {
                            $childBar->save2new('download.save2new');
                        }
                    }

                    // If checked out, we can still save
                    if ($canDo->get('core.create')) {
                        $childBar->save2copy('download.save2copy');
                    }
                }
            );

            $toolbar->cancel('download.cancel', 'COM_JDOWNLOADS_TOOLBAR_CLOSE');

            // The "Preview" button should only be displayed when the download is published and already saved
            if (!$isNew && $this->item->published == 1) {
                $url = RouteHelper::getDownloadRoute($this->item->id . ':' . $this->item->alias, $this->item->catid, $this->item->language);

                $toolbar->preview(Route::link('site', $url, true), 'JGLOBAL_PREVIEW')
                    ->bodyHeight(80)
                    ->modalWidth(90);
                    
                if (PluginHelper::isEnabled('system', 'jooa11y')) {
                    $toolbar->jooa11y(Route::link('site', $url . '&jooa11y=1', true), 'JGLOBAL_JOOA11Y')
                        ->bodyHeight(80)
                        ->modalWidth(90);
                }                    
            }
        }        
        
        $toolbar->divider();
        
        // Add help button - The first integer value must be the corresponding article ID from the documentation
        $help_page = '000&tmpl=jdhelp';
        $help_url = $params->get('help_url').$help_page;
        $exists_url = JDownloadsHelper::existsHelpServerURL($help_url);
        if ($exists_url) {
            $toolbar->help(null, false, $help_url);
        } else {
            $toolbar->help('help.general', true); 
        }
    }    
   
}
?>