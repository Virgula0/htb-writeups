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
use Joomla\CMS\Language\Associations;
use Joomla\CMS\Filesystem\File;
use Joomla\CMS\Filesystem\Folder;
use Joomla\Database\DatabaseDriver;
use Joomla\CMS\MVC\Model\AdminModel;
use Joomla\Database\ParameterType;
use Joomla\CMS\MVC\Factory\MVCFactoryInterface;
use Joomla\CMS\Form\FormFactoryInterface;
use Joomla\CMS\Table\TableInterface;
use Joomla\CMS\Form\Form;
use Joomla\CMS\Filter\OutputFilter;
use Joomla\CMS\Plugin\PluginHelper;

use JDownloads\Component\JDownloads\Administrator\Table\LicenseTable;
use JDownloads\Component\JDownloads\Administrator\Helper\JDownloadsHelper;

class LicenseModel extends AdminModel
{
	
    // @var        string    The prefix to use with controller messages.
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
    public function getTable($name = 'license', $prefix = 'Table', $options = array()) 
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
        
        // Get the form.
        $form = $this->loadForm('com_jdownloads.license', 'license',
                                array('control' => 'jform', 'load_data' => $loadData));
        if (empty($form)) 
        {
            return false;
        }
        return $form;
    }
    
    
    /**
     * Method to get the data that should be injected in the form.
     *
     * @return    mixed    The data for the form.
     * @since    1.6
     */
    protected function loadFormData() 
    {
        // Check the session for previously entered form data.
        $data = Factory::getApplication()->getUserState('com_jdownloads.edit.license.data', array());
        if (empty($data)) 
        {
            $data = $this->getItem();
        }
        return $data;
    }

    
    /**
     * Prepare and sanitise the table prior to saving.
     *
     * @since    1.6
     */
    protected function prepareTable($table)
    {
        $app  = Factory::getApplication();
        $date = Factory::getDate();
        $user = $app->getIdentity();

        $table->title = htmlspecialchars_decode($table->title, ENT_QUOTES);
        $table->alias = ApplicationHelper::stringURLSafe($table->alias);

        if (empty($table->alias)) {
            $table->alias = ApplicationHelper::stringURLSafe($table->title);
        }  

        if (empty($table->id)) {
            // Set the values

            // Set ordering to the last item if not set
            if (empty($table->ordering)) {
                $db = Factory::getDbo();
                $db->setQuery('SELECT MAX(ordering) FROM #__jdownloads_licenses');
                $max = $db->loadResult();
                $table->ordering = $max+1;
            }
        }
        else {
            // Set the values
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
        $app        = Factory::getApplication();
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
             $data['title'] = StringHelper::increment($data['title']);
        }               

        // Bind the data.
        if (!$table->bind($data)) {
            $this->setError($table->getError());
            return false;
        }

        // Prepare the row for saving
        $this->prepareTable($table);
        
        // Check the data.
        if (!$table->check($isNew)) {
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