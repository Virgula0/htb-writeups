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
use Joomla\CMS\Versioning\VersionableModelTrait;
use Joomla\CMS\UCM\UCMType;
use Joomla\CMS\MVC\Factory\MVCFactoryInterface;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Extension\ExtensionHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Access\Rules;
use Joomla\Utilities\ArrayHelper;
use Joomla\Event\DispatcherAwareInterface;
use Joomla\Event\DispatcherAwareTrait;
use Joomla\Event\DispatcherInterface;
use Joomla\String\StringHelper;
use Joomla\CMS\Table\Table;
use Joomla\CMS\Language\Associations;
use Joomla\CMS\Filesystem\File;
use Joomla\CMS\Filesystem\Folder;
use Joomla\CMS\Helper\TagsHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Language\LanguageHelper;
use Joomla\Database\ParameterType;
use Joomla\Registry\Registry;
use Joomla\CMS\MVC\Model\AdminModel;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;
use Joomla\CMS\Filesystem\Path;
use Joomla\CMS\Form\Form;
use Joomla\CMS\Plugin\PluginHelper;
use joomla\cms\Log\Log;
use Joomla\Filter\OutputFilter;
use Joomla\Component\Finder\Administrator\Indexer\Adapter;
use Joomla\Component\Finder\Administrator\Indexer\Helper;
use Joomla\Component\Finder\Administrator\Indexer\Indexer;
use Joomla\Component\Finder\Administrator\Indexer\Result;
use JLoader;

use JDownloads\Component\JDownloads\Administrator\Extension\JDownloadsComponent;
use JDownloads\Component\JDownloads\Administrator\Helper\JDownloadsHelper;
use JDownloads\Component\JDownloads\Administrator\Table\JDCategoryTable;
use JDownloads\Component\JDownloads\Administrator\Helper\JDownloadsAssociationsHelper;

use JDownloads\Component\JDownloads\Site\Helper\CategoriesHelper;
use JDownloads\Component\JDownloads\Site\Helper\QueryHelper;
use JDownloads\Component\JDownloads\Site\Helper\AssociationHelper;

class CategoryModel extends AdminModel
{
    protected $text_prefix = 'COM_JDOWNLOADS';
    
    /**
     * The context used for the associations table
     *
     * @var      string
     * @since    3.4.4
     */
    protected $associationsContext = 'com_jdownloads.category.item';
    
    /**
     * Does an association exist? Caches the result of getAssoc().
     *
     * @var   boolean|null
     * @since 3.10.4
     */
    private $hasAssociation;
    
    public function __construct($config = array(), MVCFactoryInterface $factory = null)
    {
        parent::__construct($config, $factory);
    }    

    /**
     * Method to test whether a record can be deleted.
     *
     * @param    object    A record object.
     * @return    boolean    True if allowed to delete the record. Defaults to the permission set in the component.
     */
    protected function canDelete($record)
    {
        if (empty($record->id))
        {
            return false;
        }
        return Factory::getApplication()->getIdentity()->authorise('core.delete', 'com_jdownloads.category.' . (int) $record->id);        
    }
    
    
    /**
     * Method to test whether a record can have its state changed.
     *
     * @param   object  $record  A record object.
     *
     * @return  boolean  True if allowed to change the state of the record. Defaults to the permission set in the component.
     *
     */
    protected function canEditState($record)
    {
        $user = Factory::getApplication()->getIdentity();

        // Check for existing category.
        if (!empty($record->id))
        {
            return $user->authorise('core.edit.state', 'com_jdownloads.category.' . (int) $record->id);
        }

        // New category, so check against the parent.
        if (!empty($record->parent_id))
        {
            return $user->authorise('core.edit.state', 'com_jdownloads.category.' . (int) $record->parent_id);
        }

        // Default to component settings if neither category nor parent known.
        return $user->authorise('core.edit.state', 'com_jdownloads');
        
    }    
	
    /**
     * Returns a reference to the a Table object, always creating it.
     *
     * @param    type    The table type to instantiate
     * @param    string    A prefix for the table class name. Optional.
     * @param    array    Configuration array for model. Optional.
     * @return    Table    A database object
     */
    public function getTable($type = 'JDCategory', $prefix = 'Administrator', $config = array()) 
    {
        return parent::getTable($type, $prefix, $config);
        
    }
    
    /**
     * Auto-populate the model state.
     *
     * Note. Calling getState in this method will result in recursion.
     *
     * @return  void
     *
     * @since   1.6
     */
    protected function populateState()
    {
        $app = Factory::getApplication();

        $parentId = $app->input->getInt('parent_id');
        $this->setState('category.parent_id', $parentId);

        // Load the User state.
        $pk = $app->input->getInt('id');
        $this->setState($this->getName() . '.id', $pk);

        $extension = $app->input->get('extension', 'com_content');
        $this->setState('category.extension', $extension);
        $parts = explode('.', $extension);

        // Extract the component name
        $this->setState('category.component', $parts[0]);

        // Load the parameters.
        $params = ComponentHelper::getParams('com_jdownloads');
        $this->setState('params', $params);
    }

    /**
     * Method to get a category.
     *
     * @param   integer  $pk  An optional id of the object to get, otherwise the id from the model state is used.
     *
     * @return  mixed    Category data object on success, false on failure.
     *
     * @since   1.6
     */
    public function getItem($pk = null)
    {
        if ($result = parent::getItem($pk)){
            // Prime required properties.
            if (empty($result->id))
            {
                $result->parent_id = $this->getState('category.parent_id');
            
            }
            
            if (!empty($result->id))
            {
                $result->tags = new TagsHelper;
                $result->tags->getTagIds($result->id, 'com_jdownloads.category');
            }
        }

        // Added to support the Joomla Language Associations
        // Load associated Category items        
        $assoc = $this->getAssoc();

        if ($assoc){
            if ($result->id != null){
                $associations = JDownloadsAssociationsHelper::getAssociations('category', $result->id);  
                                
                foreach ($associations as $tag => $association){
                    $result->associations[$tag] = $association->id;
                }
			} else {
				$result->associations = array();
            }
        }

        return $result;
    }
    
    /**
     * Method to get the record form.
     *
     * @param    array    $data        Data for the form.
     * @param    boolean    $loadData    True if the form is to load its own data (default case), false if not.
     * @return    mixed    A JForm object on success, false on failure
     */
    public function getForm($data = array(), $loadData = true) 
    {
        $jinput = Factory::getApplication()->input;
        
        // Get the form.
        $form = $this->loadForm('com_jdownloads.category', 'category', array('control' => 'jform', 'load_data' => $loadData));
        
        if (empty($form)){
            return false;
        }
        
        $user = Factory::getApplication()->getIdentity();

        if (!$user->authorise('core.edit.state', 'com_jdownloads.category.' . $jinput->get('id'))){
            // Disable fields for display.
            $form->setFieldAttribute('ordering', 'disabled', 'true');
            $form->setFieldAttribute('published', 'disabled', 'true');

            // Disable fields while saving.
            // The controller has already verified this is a record you can edit.
            $form->setFieldAttribute('ordering', 'filter', 'unset');
            $form->setFieldAttribute('published', 'filter', 'unset');
        }
        
        // Don't allow to change the created_user_id user if not allowed to access com_users.
        if (!Factory::getUser()->authorise('core.manage', 'com_users'))
        {
            $form->setFieldAttribute('created_user_id', 'filter', 'unset');
        } 
        
        return $form;
    }
    
    /**
     * Method to determine if an association exists
     *
     * @return  boolean  True if the association exists
     *
     * @since   3.0
     */
    public function getAssoc()
    {
        static $assoc = null;

        if (!is_null($assoc))
        {
            return $assoc;
        }

        $assoc = Associations::isEnabled();

        if (!$assoc)
        {
            $assoc = false;
        }
        
        return $assoc;
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
        $app = Factory::getApplication();
        $data = Factory::getApplication()->getUserState('com_jdownloads.edit.category.data', array());

        if (empty($data)){
            $data = $this->getItem();
            
            // Pre-select some filters (Status, Language, Access) in edit form if those have been selected in Category Manager
            if (!$data->id){
                $filters = (array) $app->getUserState('com_jdownloads.categories.filter');

                $data->set(
                    'published',
                    $app->input->getInt(
                        'published',
                        ((isset($filters['published']) && $filters['published'] !== '') ? $filters['published'] : null)
                    )
                );
                $data->set('language', $app->input->getString('language', (!empty($filters['language']) ? $filters['language'] : null)));
                $data->set(
                    'access',
                    $app->input->getInt('access', (!empty($filters['access']) ? $filters['access'] : Factory::getConfig()->get('access')))
                );
            }
        }
        
        $this->preprocessData('com_jdownloads.category', $data);
        
        return $data;
    }

    
    /**
     * Prepare and sanitise the table prior to saving.
     *
     * @since    1.6
     */
    protected function prepareTable($table)
    {
        $date = Factory::getDate();
        $user = Factory::getApplication()->getIdentity();

        $table->title        = htmlspecialchars_decode($table->title, ENT_QUOTES);
        $table->alias        = ApplicationHelper::stringURLSafe($table->alias);

        if (empty($table->alias)){
            $table->alias = ApplicationHelper::stringURLSafe($table->title);
            if (trim(str_replace('-','',$table->alias)) == '') {
                $table->alias = Factory::getDate()->format("Y-m-d-H-i-s");
        	}
        }        
        
        if (!empty($table->password)){
            $table->password_md5 = hash('sha256', $table->password);
        }
        
        if (empty($table->params)){
            $table->params = '{}';
        }

        if (empty($table->id)){
            // Set ordering to the last item if not set - DEPRECATED in jD 2.0 and not really used.
            if (empty($table->ordering)) {
                $db = Factory::getDbo();
                $db->setQuery('SELECT MAX(ordering) FROM #__jdownloads_categories WHERE parent_id = \''.$table->parent_id.'\'');
                $max = $db->loadResult();
                $table->ordering = $max+1;
            } 
        } else {
            // Set the values for an old category
        }
    }
    
    /**
     * Method to preprocess the form.
     *
     * @param   JForm   $form   A JForm object.
     * @param   mixed   $data   The data expected for the form.
     * @param   string  $group  The name of the plugin group to import.
     *
     * @return  mixed
     *
     * @see     JFormField
     * @since   1.6
     * @throws  Exception if there is an error in the form event.
     */
    protected function preprocessForm( Form $form, $data, $group = 'content' )
    {
        // Association category items
        
        $languages = LanguageHelper::getContentLanguages(false, true, null, 'ordering', 'asc');

        if (count($languages) > 1){
            $addform = new \SimpleXMLElement('<form />');
            $fields = $addform->addChild('fields');
            $fields->addAttribute('name', 'associations');
            $fieldset = $fields->addChild('fieldset');
            $fieldset->addAttribute('name', 'item_associations');

            foreach ($languages as $language){
                $field = $fieldset->addChild('field');
                $field->addAttribute('name', $language->lang_code);
                $field->addAttribute('type', 'modal_category');
                $field->addAttribute('language', $language->lang_code);
                $field->addAttribute('label', $language->title);
                $field->addAttribute('translate_label', 'false');
                $field->addAttribute('select', 'true');
                $field->addAttribute('new', 'true');
                $field->addAttribute('edit', 'true');
                $field->addAttribute('clear', 'true');
                $field->addAttribute('propagate', 'true');
            }

            $form->load($addform, false);
        }

        // Trigger the default form events.
        parent::preprocessForm($form, $data, $group);
    }
    
    /**
     * Method to save the form data.
     *
     * @param    array    The form data.
     * @param    boolean  The switch for added by monitoring
     * @return    boolean    True on success.
     */
    public function save($data, $auto_added = false)
    {
        $app = Factory::getApplication();
        Table::addIncludePath( JPATH_ADMINISTRATOR . '/components/com_jdownloads/src/Table' ); 
        $params = ComponentHelper::getParams('com_jdownloads');
        $context = $this->option . '.' . $this->name;
        
        // Initialise variables;
        $jinput        = Factory::getApplication()->input;
        $table         = $this->getTable();
        $pk            = (!empty($data['id'])) ? $data['id'] : (int)$this->getState($this->getName().'.id');
        $isNew         = true;
        $catChanged    = false;
        $title_changed = false;
        $cat_dir_changed_manually = false;
        $checked_cat_title = '';
        
        // Include the content plugins for the on save events.
        PluginHelper::importPlugin('content');        
        
        // remove bad input values
        $data['parent_id'] = (int)$data['parent_id'];
        
        // Prevent a notice message when cat_dir not defined (while the 'uncategorised' category will be created)
        if (!isset($data['cat_dir'])){
            $data['cat_dir'] = '';
        }
       
        // Load the row if saving an existing category.
        if ($pk > 0) {
            $table->load($pk);
            $isNew = false;
            if ($table->parent_id != $data['parent_id']){
                // we must be here careful for the case that user has manipulated manually the parent_id
                if ($data['parent_id'] == 0){
                    // invalid value, so we do here nothing and use the old parent_id
                   $data['parent_id'] = $table->parent_id;
                } else {   
                   $catChanged = true;
                }   
            }
        }

        // Parent id must have at minimum a 1 for 'root' category
        if ($data['parent_id'] == 0){
            $data['parent_id'] = 1;
        }         
        
        // Is title changed?
        $org_title = $jinput->get('cat_title_org', '', 'string');
        if ($org_title != '' && $org_title != $data['title']){
            $title_changed = true;
        }
        
        // cat_dir manually changed?
        $old_cat_dir = $jinput->get('cat_dir_org', '', 'string');
        if ($old_cat_dir != '' && $old_cat_dir != $data['cat_dir']){
            $cat_dir_changed_manually = true;
        }
        
        if (!$auto_added){ 
            // We must check first the cat_dir content and remove some critical things
            if ($params->get('create_auto_cat_dir')){
                // Check whether we have a different title and cat_dir (as example when prior was activated the manually category name building)
                if (!$title_changed && ($data['title'] != $data['cat_dir'])){
                    // Activate this switch
                    $title_changed = true;
                } 
                
                // The cat_dir name is managed by jD and builded from category title
                $checked_cat_title = JDownloadsHelper::getCleanFolderFileName($data['title']);
            } else {
                // The cat_dir name is managed by the user and the cat_dir field
                $checked_cat_title = JDownloadsHelper::getCleanFolderFileName($data['cat_dir']);
            }    
            
            $data['cat_dir'] = $checked_cat_title;
        }

        if ($isNew || $title_changed || $cat_dir_changed_manually){
            // Make sure that we have a new (valid) folder name / same when changed title or manually cat_dir field
           $data['cat_dir'] = $this->generateNewFolderName($data['parent_id'], $data['cat_dir'], $data['id']);        
        }
        
        if ($data['cat_dir'] == ''){
            // ERROR - we have an empty category folder name - not possible! 
            $this->setError(Text::_('COM_JDOWNLOADS_BACKEND_CATSEDIT_ERROR_FOLDER_NAME'));
            return false;
        }
        
        // Set the new parent id if parent id not matched OR while New/Save as Copy .
        if ($table->parent_id != $data['parent_id'] || $data['id'] == 0) {
            $table->setLocation($data['parent_id'], 'last-child');
        }

        // Alter the title for save as copy
        if ($jinput->get('task') == 'save2copy') {
            list($title,$alias) = $this->generateNewTitle($data['parent_id'], $data['alias'], $data['title']);
            $data['title']    = $title;
            $data['alias']    = $alias;
        }
        
        if ((!empty($data['tags']) && $data['tags'][0] != '')){
            $table->newTags = $data['tags'];
        }         

        // Bind the data.
        if (!$table->bind($data)){
            $this->setError($table->getError());
            return false;
        }
        
        // Bind the rules.
        if (isset($data['rules'])){
            $rules = new Rules($data['rules']);
            $table->setRules($rules);
        }

        // Prepare the row for saving
        $this->prepareTable($table);
        
        // Check the data.
        if (!$table->checkData($isNew, $auto_added)){
            $this->setError($table->getError());
            return false;
        }

        // Trigger the before save event.
        if (!$auto_added){
            $result = Factory::getApplication()->triggerEvent($this->event_before_save, array($context, &$table, $isNew, $data));
        
            if (in_array(false, $result, true)) {
                $this->setError($table->getError());
                return false;
            }
        }

        // Store the data.
        if (!$table->store()){
            $this->setError($table->getError());
            return false;
        }
        
        // Folder handling functionality - not used for auto added
        if (!$auto_added){
            if (!$table->checkCategoryFolder($isNew, $catChanged, $title_changed, $checked_cat_title, $cat_dir_changed_manually)){
                if ($table->published = 1){
                    $table->published = 0; 
                    $table->store();
                } 
                //return false;
            }
        }            

        $assoc = $this->getAssoc();

        if ($assoc)
        {
            // Adding self to the association
            $associations = isset($data['associations']) ? $data['associations'] : array();

            // Unset any invalid associations
            $associations = ArrayHelper::toInteger($associations);

            foreach ($associations as $tag => $id){
                if (!$id){
                    unset($associations[$tag]);
                }
            }

            // Detecting all item menus
            $allLanguage = $table->language == '*';

            if ($allLanguage && !empty($associations)){
                $app->enqueueMessage( Text::_('COM_JDOWNLOADS_ERROR_ALL_LANGUAGE_ASSOCIATED'), 'notice');
            }

            // Get associationskey for edited item
            $db    = $this->getDbo();
            $query = $db->getQuery(true)
                ->select($db->quoteName('key'))
                ->from($db->quoteName('#__associations'))
                ->where($db->quoteName('context') . ' = ' . $db->quote($this->associationsContext))
                ->where($db->quoteName('id') . ' = ' . (int) $table->id);
            $db->setQuery($query);
            $oldKey = $db->loadResult();

            // Deleting old associations for the associated items
            $query = $db->getQuery(true)
                ->delete($db->quoteName('#__associations'))
                ->where($db->quoteName('context') . ' = ' . $db->quote($this->associationsContext));

            if ($associations){
                $query->where('(' . $db->quoteName('id') . ' IN (' . implode(',', $associations) . ') OR '
                    . $db->quoteName('key') . ' = ' . $db->quote($oldKey) . ')');
            } else {
                $query->where($db->quoteName('key') . ' = ' . $db->quote($oldKey));
            }

            $db->setQuery($query);

            try
            {
                $db->execute();
            }
            catch (RuntimeException $e)
            {
                $this->setError($e->getMessage());
                return false;
            }

            // Adding self to the association
            if (!$allLanguage){
                $associations[$table->language] = (int) $table->id;
            }

            if (count($associations) > 1){
                // Adding new association for these items
                $key = md5(json_encode($associations));
                $query->clear()
                    ->insert('#__associations');

                foreach ($associations as $id){
                    $query->values(((int) $id) . ',' . $db->quote($this->associationsContext) . ',' . $db->quote($key));
                }

                $db->setQuery($query);

                try
                {
                    $db->execute();
                }
                catch (RuntimeException $e)
                {
                    $this->setError($e->getMessage());

                    return false;
                }
            }
        }
        
        // Trigger the AfterSave event.
        if (!$auto_added){
            $result = Factory::getApplication()->triggerEvent($this->event_after_save, array($context, &$table, $isNew, $data));
        }    
        
        // Rebuild the path for the category - but only when it is a sub category (parent_id > 1)
        if ($table->parent_id > 1){
            if (!$table->rebuildPath($table->id)){
                $this->setError($table->getError());
                return false;
            } 
        }
        
        // Rebuild the paths of the category's children:
        if ($table->hasChildren($table->id)){
            if ($table->cat_dir_parent != ''){
                $path = $table->cat_dir_parent.'/'.$table->cat_dir;
            } else {
                $path = $table->cat_dir;
            }
            if (!$table->rebuild($table->id, $table->lft, $table->level, $path)){
                $this->setError($table->getError());
                return false;
            } 
        }
        
        $this->setState($this->getName() . '.id', $table->id);

        if (Factory::getApplication()->input->get('task') == 'editAssociations'){
            return $this->redirectToAssociations($data);
        }

        // Clear the cache
        $this->cleanCache();
        return true;
    }
    
    /**
     * Method to save the reordered nested set tree.
     * First we save the new order values in the lft values of the changed ids.
     * Then we invoke the table rebuild to implement the new ordering.
     *
     * @param   array    $idArray    An array of primary key ids.
     * @param   integer  $lft_array  The lft value
     *
     * @return  boolean  False on failure or error, True otherwise
     *
    */
    public function saveorder($idArray = null, $lft_array = null)
    {
        // Get an instance of the table object.
        $table = $this->getTable();

        if (!$table->saveorder($idArray, $lft_array)){
            $this->setError($table->getError());
            return false;
        }

        // Clear the cache
        $this->cleanCache();
        return true;
    }
    
    /**
     * Batch copy categories to a new category.
     *
     * @param   integer  $value     The new category.
     * @param   array    $pks       An array of row IDs.
     * @param   array    $contexts  An array of item contexts.
     *
     * @return  mixed  An array of new IDs on success, boolean false on failure.
     *
     */
    protected function batchCopy($value, $pks, $contexts)
    {
        $params = ComponentHelper::getParams('com_jdownloads');
        
        $type = new UcmType;
        $this->type = $type->getTypeByAlias($this->typeAlias);
        
        $root_folder = $params->get('files_uploaddir');
        $new_parent_id = (int) $value;

        $table = $this->getTable();
        $db = $this->getDbo();
        $user = Factory::getApplication()->getIdentity();

        // Check at first, that it is not already a other batch job in progress
        if ($params->get('categories_batch_in_progress') || $params->get('downloads_batch_in_progress')){
            // Generate the warning and return
            Factory::getApplication()->enqueueMessage( Text::_('COM_JDOWNLOADS_BATCH_IS_ALWAYS_STARTED'), 'warning');
            return false;
        } else {
            // Update at first the batch progress setting in params 
            $result = JDownloadsHelper::changeParamSetting('categories_batch_in_progress', '1');
        }
        
        $i      = 0;
        $newId  = 0;
        $new_parent_dir_part = '';
        $changed_cat_dir = '';
        
        $old_cat_dir        = '';
        $old_cat_dir_parent = '';
        
        // Base category directory name - changed or not
        $new_target_base_folder_name = '';

        // Check that the parent exists
        if ($new_parent_id){
            if (!$table->load($new_parent_id)){
                if ($error = $table->getError()){
                    // Fatal error
                    $this->setError($error);
                    return false;
                } else {
                    // Non-fatal error
                    $this->setError(Text::_('COM_JDOWNLOADS_BATCH_MOVE_PARENT_NOT_FOUND'));
                    $new_parent_id = 0;
                }
            }
            
            // Check that user has create permission for parent category
            if ($new_parent_id == $table->getRootId()){
                $canCreate = $user->authorise('core.create', 'com_jdownloads');
            } else {
                $canCreate = $user->authorise('core.create', 'com_jdownloads' . '.category.' . $new_parent_id);
            }

            if (!$canCreate){
                // Error since user cannot create in parent category
                $this->setError(Text::_('COM_JDOWNLOADS_BATCH_CANNOT_CREATE'));
                return false;
            }
        }

        // If the parent is 0, set it to the ID of the root item in the tree
        if (empty($new_parent_id)){
            if (!$new_parent_id = $table->getRootId()){
                $this->setError($this->table->getError());
                return false;
            } 
                // Make sure we can create in root
                elseif (!$user->authorise('core.create', 'com_jdownloads')){
                    $this->setError(Text::_('COM_JDOWNLOADS_BATCH_CANNOT_CREATE'));
                    return false;
            }
        }

        // We need to log the parent ID
        $parents = array();

        // Calculate the emergency stop count as a precaution against a runaway loop bug
        $query = $db->getQuery(true);
        $query->select('COUNT(id)');
        $query->from($db->quoteName('#__jdownloads_categories'));
        $db->setQuery($query);
        
        try
        {
            $count = $db->loadResult();
        }
        catch (\RuntimeException $e){
            $this->setError($e->getMessage());
            return false;
        }
        
        // Parent exists so we let's proceed
        while (!empty($pks) && $count > 0){
            // Pop the first id off the stack
            $pk = array_shift($pks);
            $table->reset();

            // Check that the row actually exists
            if (!$table->load($pk)){
                if ($error = $table->getError()){
                    // Fatal error
                    $this->setError($error);
                    return false;
                } else {
                    // Not fatal error
                    $this->setError(Text::sprintf('COM_JDOWNLOADS_BATCH_MOVE_ROW_NOT_FOUND', $pk));
                    continue;
                }
            }

            // Copy is a bit tricky, because we also need to copy the children
            $lft = (int) $table->lft;
            $rgt = (int) $table->rgt;
            $query->clear()
                ->select($db->quoteName('id'))
                ->from($db->quoteName('#__jdownloads_categories'))
                ->where($db->quoteName('lft') . ' > :lft')
                ->where($db->quoteName('rgt') . ' < :rgt')
                ->bind(':lft', $lft, ParameterType::INTEGER)
                ->bind(':rgt', $rgt, ParameterType::INTEGER);
            $db->setQuery($query);
            $childIds = $db->loadColumn();            

            // Add child ID's to the array only if they aren't already there.
            foreach ($childIds as $childId){
                if (!in_array($childId, $pks)){
                    $pks[] = $childId;
                }
            }

            // Make a copy of the old ID, Parent ID and Asset ID
            $oldId = $table->id;
            $oldParentId = $table->parent_id;
            $oldAssetId  = $table->asset_id;
            
            // Make a copy of the old category folder path
            $old_cat_dir = $table->cat_dir;
            $old_cat_dir_parent = $table->cat_dir_parent;
            if ($old_cat_dir_parent != ''){
                $old_cat_path = $old_cat_dir_parent.'/'.$old_cat_dir;
            } else {
                $old_cat_path = $old_cat_dir;
            }

            
            // Reset the id because we are making a copy.
            $table->id = 0;

            // If we a copying children, the Old ID will turn up in the parents list
            // otherwise it's a new top level item
            $table->parent_id = isset($parents[$oldParentId]) ? $parents[$oldParentId] : $new_parent_id;
            if ($table->parent_id == 1){
                $table->cat_dir_parent = '';
            }    

            // Set the new location in the tree for the node.
            $table->setLocation($table->parent_id, 'last-child');

            $table->level = null;
            $table->asset_id = null;
            $table->lft = null;
            $table->rgt = null;

            // Alter the title & alias when we have the first cat
            list($title, $alias) = $this->generateNewTitle($table->parent_id, $table->alias, $table->title);
            $table->title = $title;
            $table->alias = $alias;
            
            // Unpublish because we are making a copy
            $table->published = 0;
        
            // build new cat_dir from the old one
            $cat_dir = $this->generateNewFolderName($table->parent_id, $table->cat_dir, $table->id);

            $replace = array ( '(' => '', ')' => '' );
            $cat_dir = strtr ( $cat_dir, $replace );
            
            if ($cat_dir != $table->cat_dir){
                $changed_cat_dir = $cat_dir;
            }
            
            // we need the correct path for the field cat_dir_parent
            if ($table->parent_id > 1 || $oldParentId > 1){
                $new_parent_cat_path = $table->getParentCategoryPath($table->parent_id);
            } else {
                // root cat
                $new_parent_cat_path = '';
            }            
            
            // build the new parent cat path
            $table->cat_dir_parent = $new_parent_cat_path;

            // make sure that we have not twice the same category folder name (but not for childrens)
            if (!in_array($oldParentId, $parents) && !$parents[$oldParentId]){
                if ($new_parent_cat_path != ''){
                    while (Folder::exists($root_folder.'/'.$new_parent_cat_path.'/'.$cat_dir)){
                        $title = StringHelper::increment($cat_dir);
                        $cat_dir = strtr ( $title, $replace );
                    }
                } else {
                    while (Folder::exists($root_folder.'/'.$cat_dir)){
                        $title = StringHelper::increment($cat_dir);
                        $cat_dir = strtr ( $title, $replace );
                    }
                }
            }            
            $table->cat_dir = $cat_dir;
            
            // Store the row
            if (!$table->store()){
                $this->setError($table->getError());
                return false;
            }
            
            // Build the new cat_dir_parent part for the childrens
            if ($newId == 0 && $table->cat_dir_parent != ''){
               $new_parent_dir_part = $table->cat_dir_parent.'/'.$table->cat_dir; 
            }
            
            $newId = $table->get('id');

            // Add the new ID to the array
            $newIds[$i] = $newId;
            $i++;

            // Copy rules
            $query->clear()
                ->update($db->quoteName('#__assets', 't'))
                ->join('INNER',
                    $db->quoteName('#__assets', 's'),
                    $db->quoteName('s.id') . ' = :oldid'
                )
                ->bind(':oldid', $oldAssetId, ParameterType::INTEGER)
                ->set($db->quoteName('t.rules') . ' = ' . $db->quoteName('s.rules'))
                ->where($db->quoteName('t.id') . ' = :assetid')
                ->bind(':assetid', $this->table->asset_id, ParameterType::INTEGER);
            $db->setQuery($query)->execute();
            
            // Now we log the old 'parent' to the new 'parent'
            $parents[$oldId] = $table->id;
            $count--;

            // Rebuild the hierarchy.
            if (!$table->rebuild()){
                $this->setError($table->getError());
                return false;
            }

            // Rebuild the tree path.
            if (!$table->rebuildPath($table->id)){
                $this->setError($table->getError());
                return false;
            }
            
            // Build the source path 
            if ($old_cat_dir != ''){
                $source_dir = $root_folder.'/'.$old_cat_path;
            } else {
                $source_dir = $root_folder;
            }
            // Build the target path 
            if ($new_parent_cat_path != ''){
                $target_dir = $root_folder.'/'.$new_parent_cat_path.'/'.$cat_dir;
            } else {
                $target_dir = $root_folder.'/'.$cat_dir;
            }

            // Copy only the dir when we have it not already copied with parent folder 
            if (!in_array($oldParentId, $parents) && !$parents[$oldParentId]){
                
                // Move now the complete category folder to the new location!
                // The path must have at the end a slash
                $message = '';
                JDownloadsHelper::moveDirs($source_dir.'/', $target_dir.'/', $message, true, false, true, true );
                if ($message){
                    Factory::getApplication()->enqueueMessage( $message, 'warning');
                }             
            }                
        
        } 
        
        // Actualize at last the batch progress setting 
        $result = JDownloadsHelper::changeParamSetting('categories_batch_in_progress', '0');
        
        return $newIds;
    }

    /**
     * Batch move categories to a other category.
     *
     * @param   integer  $value     The new category ID.
     * @param   array    $pks       An array of row IDs.
     * @param   array    $contexts  An array of item contexts.
     *
     * @return  boolean  True on success.
     *
     */
    protected function batchMove($value, $pks, $contexts)
    {
        $params = ComponentHelper::getParams('com_jdownloads');
        
        $root_folder = $params->get('files_uploaddir');
        $new_parent_id = (int) $value;
        
        $user = Factory::getApplication()->getIdentity();
        $table = $this->getTable();
        $db = $this->getDbo();
        $query = $db->getQuery(true);
        
        // Check at first, that it is not always run a other batch job
        if ($params->get('categories_batch_in_progress') || $params->get('downloads_batch_in_progress')){
            // Generate the warning and return
            Factory::getApplication()->enqueueMessage( Text::_('COM_JDOWNLOADS_BATCH_IS_ALWAYS_STARTED'), 'warning');
            return false;
        } else {
            // Actualize at first the batch progress setting 
            $result = JDownloadsHelper::changeParamSetting('categories_batch_in_progress', '1');
        }     

        // Check that the parent exists.
        if ($new_parent_id){
            if (!$table->load($new_parent_id)){
                if ($error = $table->getError()){
                    // Fatal error
                    $this->setError($error);
                    return false;
                } else {
                    // Non-fatal error
                    $this->setError(Text::_('COM_JDOWNLOADS_BATCH_MOVE_PARENT_NOT_FOUND'));
                    $new_parent_id = 0;
                }
            }
            
            // Check that user has create permission for parent category.
            if ($new_parent_id == $table->getRootId()){
                $canCreate = $user->authorise('core.create', 'com_jdownloads');
            } else {
                $canCreate = $user->authorise('core.create', 'com_jdownloads' . '.category.' . $new_parent_id);
            }

            if (!$canCreate){
                // Error since user cannot create in parent category
                $this->setError(Text::_('COM_JDOWNLOADS_BATCH_CANNOT_CREATE'));
                return false;
            }
            
            // Check that user has edit permission for every category being moved
            // Note that the entire batch operation fails if any category lacks edit permission
            foreach ($pks as $pk){
                if (!$user->authorise('core.edit', 'com_jdownloads' . '.category.' . $pk)){
                    // Error since user cannot edit this category
                    $this->setError(Text::_('COM_JDOWNLOADS_BATCH_CANNOT_EDIT'));
                    return false;
                }
            }
        }
        
        // We are going to store all the children and just move the category
        $children = array();

        // Parent exists so we let's proceed
        foreach ($pks as $pk){
            // Check that the row actually exists
            if (!$table->load($pk)){
                if ($error = $table->getError()){
                    // Fatal error
                    $this->setError($error);
                    return false;
                } else {
                    // Not fatal error
                    $this->setError(Text::sprintf('COM_JDOWNLOADS_BATCH_MOVE_ROW_NOT_FOUND', $pk));
                    continue;
                }
            }

            //$oldParentId = $table->parent_id;
            $oldParentId = 0;
            
            // Make a copy of the old category folder path
            $old_cat_dir = $table->cat_dir;
            $old_cat_dir_parent = $table->cat_dir_parent;
            if ($old_cat_dir_parent != ''){
                $old_cat_path = $old_cat_dir_parent.'/'.$old_cat_dir;
            } else {
                $old_cat_path = $old_cat_dir;
            }   

            $cat_dir = $table->cat_dir;             

            // Set the new location in the tree for the node.
            $table->setLocation($new_parent_id, 'last-child');

            // Check if we are moving to a different parent
            if ($new_parent_id != $table->parent_id){
                
                // Add the child node ids to the children array.
                $lft = (int) $table->lft;
                $rgt = (int) $table->rgt;

                // Add the child node ids to the children array.
                $query->clear()
                    ->select($db->quoteName('id'))
                    ->from($db->quoteName('#__jdownloads_categories'))
                    ->where($db->quoteName('lft') . ' BETWEEN :lft AND :rgt')
                    ->bind(':lft', $lft, ParameterType::INTEGER)
                    ->bind(':rgt', $rgt, ParameterType::INTEGER);
                $db->setQuery($query);

                try
                {
                    $children = array_merge($children, (array) $db->loadColumn());
                }
                catch (\RuntimeException $e)
                {
                    $this->setError($e->getMessage());

                    return false;
                }
                
            }

            if ($new_parent_id > 1 || $oldParentId > 1){
                $new_parent_cat_path = $table->getParentCategoryPath($new_parent_id);
            } else {
                // Root cat
                $new_parent_cat_path = '';
            }
            
            // Build the new parent cat path
            $table->cat_dir_parent = $new_parent_cat_path;

            // Build new cat_dir name when it exists allways
            $replace = array ( '(' => '', ')' => '' );
            if ($new_parent_id > 1){
                while (Folder::exists($root_folder.'/'.$new_parent_cat_path.'/'.$cat_dir)){
                    $title = StringHelper::increment($cat_dir);
                    $cat_dir = strtr ( $title, $replace );
                }
            } else {
                while (Folder::exists($root_folder.'/'.$cat_dir)){
                    $title = StringHelper::increment($cat_dir);
                    $cat_dir = strtr ( $title, $replace );
                }                
            }    
            
            $table->cat_dir = $cat_dir;            
            
            // Store the row
            if (!$table->store()){
                $this->setError($table->getError());
                return false;
            }
            
            // Rebuild the hierarchy.
            if (!$table->rebuild()){
                $this->setError($table->getError());
                return false;
            }            

            // Rebuild the tree path.
            if (!$table->rebuildPath()){
                $this->setError($table->getError());
                return false;
            }
            
            // Build the source path 
            if ($old_cat_dir != ''){
                $source_dir = $root_folder.'/'.$old_cat_path;
            } else {
                $source_dir = $root_folder;
            }
            // Build the target path 
            if ($new_parent_cat_path != ''){
                $target_dir = $root_folder.'/'.$new_parent_cat_path.'/'.$cat_dir;
            } else {
                $target_dir = $root_folder.'/'.$cat_dir;
            }

            // Move now the complete category folder to the new location!
            // The path must have at the end a slash
            $message = '';
            JDownloadsHelper::moveDirs($source_dir.'/', $target_dir.'/', $message, true, true, false, false);
            if ($message == ''){
                // Check the really result:
                if (Folder::exists($target_dir) && !Folder::exists($source_dir)){
                    // Factory::getApplication()->enqueueMessage( Text::sprintf('COM_JDOWNLOADS_BATCH_CAT_MOVED_MSG', $source_dir), 'warning');
                } else {
                    if (Folder::exists($source_dir)){
                        $res = JDownloadsHelper::delete_dir_and_allfiles($source_dir);
                        if (Folder::exists($source_dir)){
                            Factory::getApplication()->enqueueMessage( Text::sprintf('COM_JDOWNLOADS_BATCH_CAT_NOT_MOVED_MSG', $source_dir), 'warning');
                        }    
                    }    
                }
            } else {
                Factory::getApplication()->enqueueMessage( $message, 'warning');
            }                                
        }

        // Process the child rows
        if (!empty($children)){
            // Remove any duplicates and sanitize ids.
            $children = array_unique($children);
            $children = ArrayHelper::toInteger($children);
        }
        
        // Actualize at last the batch progress setting 
        $result = JDownloadsHelper::changeParamSetting('categories_batch_in_progress', '0');

        return true;
    }    
    
    /**
     * Method to change the title & alias.
     *
     * @param   integer  $parent_id  The id of the parent.
     * @param   string   $alias      The alias.
     * @param   string   $title      The title.
     *
     * @return  array  Contains the modified title and alias.
     */
    protected function generateNewTitle($parent_id, $alias, $title)
    {
        // Alter the title & alias
        $table = $this->getTable();
        
        while ($table->load(array('alias' => $alias, 'parent_id' => $parent_id))){
            $title = StringHelper::increment($title);
            $alias = StringHelper::increment($alias, 'dash');
        }
        return array($title, $alias);
    } 
    
    /**
     * Method to get a valid category directory name, which not yet exists for the new created category
     *
     * @param   integer  $parent_id  The id of the parent category.
     * @param   string   $cat_dir    The given folder name
     * @param   integer  $id         The id of the category   
     *
     * @return  string  Contains the modified category name
     */    
    protected function generateNewFolderName($parent_id, $cat_dir, $id)
    {
        $params = ComponentHelper::getParams('com_jdownloads');
        
        $table = $this->getTable();
        
        if ($table->load(array('cat_dir' => $cat_dir, 'parent_id' => $parent_id)) && ($table->id != $id)){
            // Do it only when the $table->id is not the same as the current - otherwise it found it always 
            while ($table->load(array('cat_dir' => $cat_dir, 'parent_id' => $parent_id))){
                // Use the settings from config
                if ($params->get('fix_upload_filename_blanks')){
                    $cat_dir = StringHelper::increment($cat_dir, 'dash');
                } else {
                    $cat_dir = StringHelper::increment($cat_dir, 'default');
                }    
            } 
        }
        return $cat_dir;
    }
    
    /**
     * Method to run categories tree rebuild
     *
     * @return  boolean  True on success.
     */        
    public function rebuildCategories()
    {
        $table = $this->getTable();
        
        // Rebuild the hierarchy.
        if (!$table->rebuild()){
            $this->setError($table->getError());
            return false;
        }            

        // Rebuild the tree path.
        if (!$table->rebuildPath()){
            $this->setError($table->getError());
            return false;
        }        
        return true;
    }
    
    /**
     * Method rebuild the entire nested set tree. Started by categories toolbar.
     *
     * @return  boolean  False on failure or error, true otherwise.
     *
     */
    public function rebuild()
    {
        // Get an instance of the table object.
        $table = $this->getTable();

        if (!$table->rebuild()){
            $this->setError($table->getError());
            return false;
        }

        // Clear the cache
        $this->cleanCache();
        return true;
    }
    
    /**
    * Method to create a new category 
    * 
    * @param string     $name           The category name.
    * @param string     $note           Text for the 'note' field.
    * @param mixed      $description    Text for the 'description' field.
    * @param int        $parent_id      The ID from the parent category (when exist) otherwise 1.
    * @param int        $published      1 when he shall be published, otherwise 0.
    * @param boolean    $example        False if it is not run the data example creation process in the DB tables, otherwise true.
    *  
    * @return CategoryNode
    */
    public function createCategory( $name, $note, $description, $parent_id = 1, $published = 1, $example = false )
    {
        $params = ComponentHelper::getParams('com_jdownloads');
        $date = Factory::getDate()->toSql();
        
        //$cat_model = BaseDatabaseModel::getInstance('Category', 'jdownloadsModel');

        $data = array (
            'id' => 0,
            'parent_id' => $parent_id,
            'title' => $name,
            'alias' => '',
            'notes' => $note,
            'description' => $description,
            'pic' => $params->get('cat_pic_default_filename'),
            'published' => $published,
            'access' => '1',
            'metadesc' => '',
            'metakey' => '',
            'created_user_id' => '0',
            'language' => '*',
            'modified_time' => null,
            'created_time' => $date,
            'checked_out_time' => null,
            'rules' => array(
                'core.create' => array(),
                'core.delete' => array(),
                'core.edit' => array(),
                'core.edit.state' => array(),
                'core.edit.own' => array(),
                'download' => array(),
            ),
            'params' => array(),
        );

        if (!$this->save($data)){
            return false;
        }
        
        return true;  
        
    }                      

    /**
    * Method to create a new category from monitoring script 
    * 
    * @param mixed $data    
    * @return CategoryNode
    */
    public function createAutoCategory($data)
    {
        Table::addIncludePath( JPATH_ADMINISTRATOR . '/components/com_jdownloads/src/Table' );

        //$cat_model = BaseDatabaseModel::getInstance( 'Category', 'jdownloadsModel' );

        if (!$this->save($data, true)){
            return NULL;
        }
        
        $new_category_id = $this->getState( "category.id" ) ;
        if ($new_category_id > 0){
            return $new_category_id;
        } 
        
        return true;
    }                      

    /**
     * Method to change the published state of one or more records.
     *
     * @param   array    &$pks   A list of the primary keys to change.
     * @param   integer  $value  The value of the published state.
     *
     * @return  boolean  True on success.
     *
     */
    public function publish(&$pks, $value = 1)
    {
        // Initialise variables.
        $user = Factory::getApplication()->getIdentity();
        $table = $this->getTable('JDCategory', '');
        $pks = (array) $pks;

        // Include the plugins for the change of state event.
        PluginHelper::importPlugin($this->events_map['change_state']);

        // Access checks.
        foreach ($pks as $i => $pk){
            $table->reset();

            if ($table->load($pk)){
                if (!$this->canEditState($table)){
                    // Prune items that you can't change.
                    unset($pks[$i]);
                    Log::add(Text::_('JLIB_APPLICATION_ERROR_EDITSTATE_NOT_PERMITTED'), Log::WARNING, 'jerror');
                    return false;
                } else {
                    if ($value == 1){
                        if (!$this->existCategoryFolder($table)){
                            // Prune items which can't be published.
                            $msg = Text::sprintf('COM_JDOWNLOADS_CATS_PUBLISH_NO_FOLDER', (int)$pks[$i]);
                            Log::add($msg, Log::WARNING, 'jerror');
                            unset($pks[$i]);
                            return false;
                        }  
                    }
                } 
                
                // If the table is checked out by another user, drop it and report to the user trying to change its state.
                if (property_exists($table, 'checked_out') && $table->checked_out && ($table->checked_out != $user->id)){
                    Log::add(Text::_('JLIB_APPLICATION_ERROR_CHECKIN_USER_MISMATCH'), Log::WARNING, 'jerror');
                    // Prune items that you can't change.
                    unset($pks[$i]);
                    return false;
                }                
                   
            }
        }

        // Attempt to change the state of the records.
        if (!$table->publish($pks, $value, $user->get('id')))
        {
            $this->setError($table->getError());
            return false;
        }

        $context = $this->option . '.' . $this->name;

        // Trigger the onContentChangeState event.
        $result = Factory::getApplication()->triggerEvent($this->event_change_state, array($context, $pks, $value));

        // Trigger the onCategoryChangeState event.
        Factory::getApplication()->triggerEvent('onCategoryChangeState', array($context, $pks, $value));
        
        if (in_array(false, $result, true)){
            $this->setError($table->getError());
            return false;
        }

        // Clear the component's cache
        $this->cleanCache();
        return true;
    }
    
    /**
     * Method to check the presence from a categories folder
     *
     * @return  boolean  True on success.
     *
     */
    public function existCategoryFolder($table)
    {
        $params = ComponentHelper::getParams('com_jdownloads');

        $root_dir = $params->get('files_uploaddir');
        
        if ($table->cat_dir_parent != ''){
            $path = $root_dir.'/'.$table->cat_dir_parent.'/'.$table->cat_dir;
        } else {
            $path = $root_dir.'/'.$table->cat_dir;
        }
        
        if (!Folder::exists($path)){
            return false;
        } 
        
        return true;    
    }
    
        
}
?>