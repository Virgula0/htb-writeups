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
                       
\defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\CMS\MVC\Model\AdminModel;
use Joomla\Registry\Registry;
use Joomla\CMS\Table\Table;

/**
 * jDownloads user group model to change user group settings and limits
 *
 */
class GroupModel extends AdminModel
{

	/**
	 * Returns a reference to the a Table object, always creating it.
	 *
	 * @param	type	The table type to instantiate
	 * @param	string	A prefix for the table class name. Optional.
	 * @param	array	Configuration array for model. Optional.
	 * @return	JTable	A database object
	*/
	public function getTable($type = 'Group', $prefix = 'JTable', $config = array())
	{
		if ($table = $this->_createTable($type, $prefix, $config))
        {
            return $table;
        } 
        return false;
	}

	/**
	 * Method to get the record form.
	 *
	 * @param	array	$data		An optional array of data for the form to interogate.
	 * @param	boolean	$loadData	True if the form is to load its own data (default case), false if not.
	 * @return	JForm	A JForm object on success, false on failure
	 */
	public function getForm($data = array(), $loadData = true)
	{
		$app = Factory::getApplication();

		$form = $this->loadForm('com_jdownloads.group', 'group', array('control' => 'jform', 'load_data' => $loadData));
		if (empty($form)) {
			return false;
		}

		return $form;
	}

	/**
	 * Method to get the data that should be injected in the form.
	 *
	 * @return	mixed	The data for the form.
	 */
	protected function loadFormData()
	{
		// Check the session for previously entered form data.
		$data = Factory::getApplication()->getUserState('com_jdownloads.edit.group.data', array());

		if (empty($data)) {
			$data = $this->getItem();
            
            // Change form_fieldset string back to array
            if ($data && $data->form_fieldset != ''){
                $registry = new Registry;
                $registry->loadString($data->form_fieldset);
                $data->form_fieldset = $registry->toArray();
			}
		}

		return $data;
	}

	/**
	 * Method to save the form data.
	 *
	 * @param	array	The form data.
	 * @return	boolean	True on success.
	 */
	public function save($data)
	{
        $result = false;
        
        // Initialise variables;
        $app          = Factory::getApplication();
        $table        = $this->getTable();
        $pk           = (!empty($data['id'])) ? $data['id'] : (int)$this->getState($this->getName().'.id');
        $isNew        = true;
        
        // Include the content plugins for the on save events.
        PluginHelper::importPlugin('system');
        
        // Load the row if saving an existing download.
        if ($pk > 0) {
            $table->load($pk);
            $isNew = false;
        }

        // Change form_fieldset array to string
        if (isset($data['form_fieldset']) && is_array($data['form_fieldset'])) {
           $registry = new Registry;
           $registry->loadArray($data['form_fieldset']);
           $data['form_fieldset'] = (string) $registry;
        }

        // Bind the data.
        if (!$table->bind($data)) {
            $this->setError($table->getError());
            return false;
        }
        
        // Check the data.
        if (!$table->check()) {
            $this->setError($table->getError());
            return false;
        }
        
        // Trigger the onContentBeforeSave event.
        $result = $app->triggerEvent($this->event_before_save, array($this->option.'.'.$this->name, &$table, $isNew, $data));
        
        // Trigger also this special jD event.
        $result = $app->triggerEvent('onJDUserGroupSettingsBeforeSave', array($this->option.'.'.$this->name, &$table));
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
        
        // Trigger also this special jD event.
        $app->triggerEvent('onJDUserGroupSettingsAfterSave', array($this->option.'.'.$this->name, &$table));
       
        $this->setState($this->getName().'.id', $table->id);

        // Clear the cache
        $this->cleanCache();

        return true;           
	}
    
}
?>