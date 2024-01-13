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

namespace JDownloads\Component\JDownloads\Administrator\Model;
 
\defined( '_JEXEC' ) or die;

use Joomla\CMS\Application\ApplicationHelper;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\Registry\Registry;
use Joomla\String\StringHelper;
use Joomla\Utilities\ArrayHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\Table\Table;
use Joomla\Database\DatabaseDriver;
use Joomla\CMS\MVC\Model\AdminModel;
use Joomla\Database\ParameterType;
use Joomla\CMS\MVC\Factory\MVCFactoryInterface;
use Joomla\CMS\Form\FormFactoryInterface;
use Joomla\CMS\Table\TableInterface;
use Joomla\CMS\Form\Form;
use Joomla\CMS\Session\Session;
use Joomla\CMS\Filter\OutputFilter;
use Joomla\CMS\Access\Access;
use Joomla\CMS\Plugin\PluginHelper;

use JDownloads\Component\JDownloads\Administrator\Helper\JDownloadsHelper;

class TemplateModel extends AdminModel
{
    protected $text_prefix = 'COM_JDOWNLOADS';
    
    public function __construct($config = array(), MVCFactoryInterface $factory = null, FormFactoryInterface $formFactory = null)
    {   
        parent::__construct($config, $factory, $formFactory);
    }    

    /**
     * Method to test whether a record can be deleted.
     *
     * @param    object    A record object.
     * @return    boolean    True if allowed to delete the record. Defaults to the permission set in the component.
     * @since    1.6
     */
    protected function canDelete($record)
    {
        return parent::canDelete($record);
    }
    
    /**
     * Method to test whether a record can have its state changed.
     *
     * @param    object    A record object.
     * @return    boolean    True if allowed to change the state of the record. Defaults to the permission set in the component.
     * @since    1.6
     */
    protected function canEditState($record)
    {
        return parent::canEditState($record);
    }
	
    /**
     * Returns a reference to the a Table object, always creating it.
     *
     * @param    type    The table type to instantiate
     * @param    string    A prefix for the table class name. Optional.
     * @param    array    Configuration array for model. Optional.
     * @return    JTable    A database object
     * @since    1.6
     */
    public function getTable($name = 'template', $prefix = 'Table', $options = array()) 
    {
        if ($table = $this->_createTable($name, $prefix, $options))
        {
            return $table;
        }
    }
    
    /**
     * Method to get the record form.
     *
     * @param    array    $data        Data for the form.
     * @param    boolean    $loadData    True if the form is to load its own data (default case), false if not.
     * @return    mixed    A JForm object on success, false on failure
     * @since    1.6
     */
    public function getForm($data = array(), $loadData = true) 
    {
        // Initialise variables.
        $app    = Factory::getApplication();
        $result = array();
        
        // Get the form.
        $form = $this->loadForm('com_jdownloads.template', 'template', array('control' => 'jform', 'load_data' => $loadData));
        
        if (empty($form)) 
        {
            return false;
        }
        
        // get id from loaded data set. It is null, we will add a new layout
        $id = $form->getValue('id');
        
        // new (empty) layout?
        if ($id == null)
        {
            // need also the layout type when we will add a layout
            $session = Factory::getSession();
            $type    = (int) $session->get( 'jd_tmpl_type', '' );        
        
        
            // add new layout - set default values
            
            // get first the default layouts from file
            require_once(JPATH_SITE.'/administrator/components/com_jdownloads/src/Helper/StandardLayouts.php');
            
            $result[] = $form->setValue('template_typ',             $group=null,  $type);
            $result[] = $form->setValue('language',                 $group=null,  '*');
            $result[] = $form->setValue('locked',                   $group=null,  0);
            $result[] = $form->setValue('template_active',          $group=null,  0);
            $result[] = $form->setValue('checkbox_off',             $group=null,  1);
            $result[] = $form->setValue('symbol_off',               $group=null,  1);
            $result[] = $form->setValue('cols',                     $group=null,  1);                                    
            $result[] = $form->setValue('note',                     $group=null,  '');            

            // we need different content (preallocation) for every layout type
            switch ($type){
                case 1:
                    // layout for categories                                              
                    $result[] = $form->setValue('template_text',            $group=null,  $JLIST_BACKEND_SETTINGS_TEMPLATES_CATS_DEFAULT);                
                    $result[] = $form->setValue('template_header_text',     $group=null,  $cats_header);
                    $result[] = $form->setValue('template_subheader_text',  $group=null,  $cats_subheader);
                    $result[] = $form->setValue('template_footer_text',     $group=null,  $cats_footer);                                                
                    break;
                case 2:
                    // layout for files list
                    $result[] = $form->setValue('template_text',            $group=null,  $JLIST_BACKEND_SETTINGS_TEMPLATES_FILES_DEFAULT);
                    $result[] = $form->setValue('template_header_text',     $group=null,  $files_header);
                    $result[] = $form->setValue('template_subheader_text',  $group=null,  $files_subheader);
                    $result[] = $form->setValue('template_footer_text',     $group=null,  $files_footer);
                    break;
                case 3:
                    // layout for summary page
                    $result[] = $form->setValue('template_text',            $group=null,  $JLIST_BACKEND_SETTINGS_TEMPLATES_SUMMARY_DEFAULT);
                    $result[] = $form->setValue('template_header_text',     $group=null,  $summary_header);
                    $result[] = $form->setValue('template_subheader_text',  $group=null,  $summary_subheader);
                    $result[] = $form->setValue('template_footer_text',     $group=null,  $summary_footer);
                    break;
                case 4:
                    // layout for single category page
                    $result[] = $form->setValue('template_text',            $group=null,  $JLIST_BACKEND_SETTINGS_TEMPLATES_CAT_DEFAULT);                
                    $result[] = $form->setValue('template_header_text',     $group=null,  $cat_header);
                    $result[] = $form->setValue('template_subheader_text',  $group=null,  $cat_subheader);
                    $result[] = $form->setValue('template_footer_text',     $group=null,  $cat_footer);                                                
                    break;        
                case 5:
                    // layout for download details page
                    $result[] = $form->setValue('template_text',            $group=null,  $JLIST_BACKEND_SETTINGS_TEMPLATES_DETAILS_DEFAULT);
                    $result[] = $form->setValue('template_header_text',     $group=null,  $details_header);
                    $result[] = $form->setValue('template_subheader_text',  $group=null,  $details_subheader);
                    $result[] = $form->setValue('template_footer_text',     $group=null,  $details_footer);                                                    
                    break;        
                case 6:
                    // layout for upload form
                    $result[] = $form->setValue('template_text',            $group=null,  $JLIST_BACKEND_SETTINGS_TEMPLATES_UPLOAD_DEFAULT);
                    $result[] = $form->setValue('template_header_text',     $group=null,  $upload_header);
                    $result[] = $form->setValue('template_subheader_text',  $group=null,  $upload_subheader);
                    $result[] = $form->setValue('template_footer_text',     $group=null,  $upload_footer);                                                    
                    break;        
                case 7:
                    // layout for search results
                    $result[] = $form->setValue('template_text',            $group=null,  $JLIST_BACKEND_SETTINGS_TEMPLATES_SEARCH_DEFAULT);
                    $result[] = $form->setValue('template_header_text',     $group=null,  $search_header);
                    $result[] = $form->setValue('template_subheader_text',  $group=null,  $search_subheader);
                    $result[] = $form->setValue('template_footer_text',     $group=null,  $search_footer);                                                    
                    break;
            }
            $this->new_layout = true;                     
        }
        
        // get id from loaded data set. It is null, we will add a new layout
        $locked = $form->getValue('locked');
        if ($locked) {
            $form->setFieldAttribute( 'template_name', 'readonly', 'true' );
        } 
        
        return $form;
    }
    

    /**
     * Method to get the data that should be injected in the form.
     *
     * @return    mixed    The data for the form.
     */
    protected function loadFormData() 
    {
        // Check the session for previously entered form data.
        $data = Factory::getApplication()->getUserState('com_jdownloads.edit.template.data', array());
        if (empty($data)) 
        {
            $data = $this->getItem();
        }
        return $data;
    }

    /**
     * Prepare and sanitise the table prior to saving.
     *
     */
    protected function prepareTable($table)
    {
        $app = Factory::getApplication();
        $user = $app->getIdentity();
        
        $date = Factory::getDate();
        $session = Factory::getSession();

        $type    = (int) $session->get( 'jd_tmpl_type', '' ); 
       
        $table->template_name  = htmlspecialchars_decode($table->template_name, ENT_QUOTES);
        $table->note           = htmlspecialchars_decode($table->note, ENT_QUOTES);
        // Layout type must have a valid value
        if (!$table->template_typ){
            $table->template_typ = $type;
        }
        // Column must have a valid value
        if ($table->cols < 1){
            $table->cols = 1;
        }
        // Column must have a valid value
        if ($table->language == ''){
            $table->language = '*';
        }
        
    }
    
    /**
     * Method to save the form data.
     *
     * @param    array    The form data.
     * @return    boolean    True on success.
     */
    public function save($data)
    {
        // Initialise variables;
        $app = Factory::getApplication();
        $table      = $this->getTable();
        $pk         = (!empty($data['id'])) ? $data['id'] : (int)$this->getState($this->getName().'.id');
        $isNew      = true;

        // Include the content plugins for the on save events.
        PluginHelper::importPlugin('content');
        
        // Load the row if saving an existing item.
        if ($pk > 0) {
            $table->load($pk);
            $isNew = false;
        }

        // Alter the title for save as copy
        if ($app->input->get('task') == 'save2copy') {
             $data['template_name'] = StringHelper::increment($data['template_name']);
             $data['locked']        = 0;
             $data['template_active'] = 0;
        }               

        // Bind the data.
        if (!$table->bind($data)) {
            $this->setError($table->getError());
            return false;
        }

        // Bind the rules.
        /* 
        if (isset($data['rules'])) {
            $rules = new AccessRules($data['rules']);
            $table->setRules($rules);
        } */

        // Prepare the row for saving
        $this->prepareTable($table);
        
        // Check the data.
        if (!$table->checkData($isNew)) {
            $this->setError($table->getError());
            return false;
        }

        // Trigger the onContentBeforeSave event.
        $result = $app->triggerEvent($this->event_before_save, array($this->option.'.'.$this->name, &$table, $isNew, $data));
        if (in_array(false, $result, true)) {
            $this->setError($table->getError());
            return false;
        }

        // Store the data.
        if (!$table->store()) {
            $this->setError($table->getError());
            return false;
        }
        
        // Trigger the onContentAfterSave event.
        $app->triggerEvent($this->event_after_save, array($this->option.'.'.$this->name, &$table, $isNew, $data));

        $this->setState($this->getName().'.id', $table->id);

        // Clear the cache
        $this->cleanCache();

        return true;
    }
    
}
?>