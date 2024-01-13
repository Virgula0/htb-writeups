<?php
/**
 * @package jDownloads
 * @version 4.0  
 * @copyright (C) 2007 - 2022 Arno Betz - www.jdownloads.com
 * @license http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.txt
 * 
 * jDownloads is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 */

namespace JDownloads\Component\JDownloads\Administrator\Table;
 
\defined('_JEXEC') or die;

use Joomla\Utilities\ArrayHelper;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Factory; 
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Table\Table;
use Joomla\CMS\Filesystem\File;
use Joomla\CMS\Filesystem\Folder;
use Joomla\Registry\Registry;
use Joomla\Database\DatabaseDriver;
use Joomla\Input\Input;
use Joomla\CMS\Access\Rules;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;
use Joomla\CMS\Event\AbstractEvent;
use Joomla\Event\Dispatcher;
use Joomla\Event\Event;
use Joomla\CMS\Tag\TaggableTableTrait;
use Joomla\CMS\Tag\TaggableTableInterface;
use Joomla\Database\ParameterType;

use JDownloads\Component\JDownloads\Administrator\Helper\JDownloadsHelper;

Table::addIncludePath(JPATH_ADMINISTRATOR.'/components/com_jdownloads/src/Table');
 
/**
 * Categories Table class
 */
class JDCategoryTable extends Table implements TaggableTableInterface
{
    use TaggableTableTrait;
	
/**
     * Object property holding the primary key of the parent node.  Provides
     * adjacency list data for nodes.
     *
     * @var    integer
     * @since  11.1
     */
    public $parent_id;

    /**
     * Object property holding the depth level of the node in the tree.
     *
     * @var    integer
     * @since  11.1
     */
    public $level;

    /**
     * Object property holding the left value of the node for managing its
     * placement in the nested sets tree.
     *
     * @var    integer
     * @since  11.1
     */
    public $lft;

    /**
     * Object property holding the right value of the node for managing its
     * placement in the nested sets tree.
     *
     * @var    integer
     * @since  11.1
     */
    public $rgt;

    /**
     * Object property holding the alias of this node used to constuct the
     * full text path, forward-slash delimited.
     *
     * @var    string
     * @since  11.1
     */
    public $alias;

    /**
     * Object property to hold the location type to use when storing the row.
     * Possible values are: ['before', 'after', 'first-child', 'last-child'].
     *
     * @var    string
     * @since  11.1
     */
    protected $_location;

    /**
     * Object property to hold the primary key of the location reference node to
     * use when storing the row.  A combination of location type and reference
     * node describes where to store the current node in the tree.
     *
     * @var integer
     * @since  11.1
     */
    protected $_location_id;

    /**
     * An array to cache values in recursive processes.
     *
     * @var   array
     * @since  11.1
     */
    protected $_cache = array();

    /**
     * Debug level
     *
     * @var    integer
     * @since  11.1
     */
    protected $_debug = 0;
    
    /**
     * Cache for the root ID
     *
     * @var    integer
     * @since  3.3
     */
    protected static $root_id = 0;

    /**
     * Array declaring the valid location values for moving a node
     *
     * @var    array
     * @since  3.7.0
     */
    private $_validLocations = array('before', 'after', 'first-child', 'last-child');

    /**
     * Sets the debug level on or off
     *
     * @param   integer  $level  0 = off, 1 = on
     */
    public function debug($level)
    {
        $this->_debug = (int)$level;
    }    
    
    /**
	 * Constructor
	 *
	 * @param object Database connector object
	 */
	public function __construct(DatabaseDriver $db) 
	{
		parent::__construct('#__jdownloads_categories', 'id', $db);
	}
    
    /**
     * Overloaded check method to ensure data integrity.
     *
     * @param   boolean  $isNew         true when the category is new
     *
     * @return    boolean    True on success.
     */
    public function checkData($isNew, $auto_added = false)
    {
        $date = Factory::getDate()->toSql();
        $user = Factory::getApplication()->getIdentity();
        $db   = Factory::getDBO();
        
        $jinput = Factory::getApplication()->input;
        
        // Verify that the alias is unique
        $jDownloadsComponent = Factory::getApplication()->bootComponent('com_jdownloads');
        $table = $jDownloadsComponent->getMVCFactory()->createTable('JDCategory', 'Table', []);
        
        if ($table->load(array('alias' => $this->alias, 'parent_id' => (int) $this->parent_id)) 
            && ($table->id != $this->id || $this->id == 0)){
                
            if ($auto_added){
                // When automatically added make it automatically unique
                $this->alias .= mt_rand(100,999);    
            } else {
                $this->setError(Text::_('COM_JDOWNLOADS_BACKEND_ERROR_CATEGORY_UNIQUE_ALIAS'));
                return false;
            }
        }        
        
        /**
        * @desc  check icon upload field
        *        if pic selected for upload:
        *           - check image typ
        *           - check whether filename exists. If so, rename the new file. 
        *           - move new file to catimages
        */          
        
        if (!$auto_added){
	        // We neeed also the jform files data
	        $jFileInput = new Input($_FILES);
	        $file = $jFileInput->get('jform',array(),'array');
	        
	        if (isset($file['tmp_name']['picnew'])){
	        
		        if ($file['tmp_name']['picnew'] != ''){
		            $pic['tmp_name']   = $file['tmp_name']['picnew'];
		            $pic['name']       = $file['name']['picnew'];
		            $pic['type']       = $file['type']['picnew'];
		            $pic['size']       = $file['size']['picnew'];
		            
		            if (JDownloadsHelper::fileIsPicture($pic['name'])){
		            	$upload_dir = JPATH_SITE.'/images/jdownloads/catimages/'; 
		
		                $pic['name'] = File::makeSafe($pic['name']);
		
		                // Replace all spaces with underscores to prevent problems
		                $pic['name'] = str_replace(' ', '_', $pic['name']);
		                
		                if (!File::upload($pic['tmp_name'], $upload_dir.$pic['name'], false, true)){
		                    $this->setError(Text::_('COM_JDOWNLOADS_ERROR_CAN_NOT_MOVE_UPLOADED_IMAGE'));
		                	return false;
		            	} else {
		                	// Move ok - set new file name as selected
		                    $this->pic = $pic['name'];
		            	}        
		            } 
		        } else {
		            // Check selected pic
		            $picname = $jinput->get('pic');
		            if (isset($picname)){
		                $this->pic = $jinput->get('pic');    
		            }
		        }       
            }       
        }        
        
        // It the category new and we have a parent category, set new level, access and path values for cat_dir and cat_dir_parent
        if ($this->parent_id > 1){
            if ($isNew){
                $query = "SELECT * FROM #__jdownloads_categories WHERE id = '$this->parent_id'";
                $db->setQuery( $query );
                $parent_cat = $db->loadObject();
                
                $this->level = $parent_cat->level + 1;

                if ($parent_cat->cat_dir_parent != ''){
                    $this->cat_dir_parent = $parent_cat->cat_dir_parent.'/'.$parent_cat->cat_dir;
                } else {
                    $this->cat_dir_parent = $parent_cat->cat_dir;
                }
                
                if (!$auto_added){
                    // Use access value from parent category
                    $this->access = $parent_cat->access;
                }
            }
        } else {
            // Has no parent category so we must delete the cat_dir_parent field
            $this->cat_dir_parent = '';
        }       
        
        // Check date and user id fields
        if (!$isNew){
            // Set user id in modified field
            $this->modified_user_id = $user->id; 
            // Fill out modified date field
            $this->modified_time = $date;
        } else {
             // Fill out created date field 
            $this->created_time = $date;
            
            if (!$this->created_user_id && !$auto_added){
                $this->created_user_id = $user->id;
            }    
        }
        
        if ($this->robots == null)          $this->robots       = '';
        if ($this->metakey == null)         $this->metakey      = '';
        if ($this->metadesc == null)        $this->metadesc     = '';    
        if ($this->notes == null)           $this->metadesc     = '';
        if ($this->params == null)          $this->metadesc     = '';
        if ($this->description == null)     $this->description  = '';
        if ($this->password == null)        $this->password     = '';
        if ($this->password_md5 == null)    $this->password_md5 = '';
        if ($this->pic == null)             $this->pic          = '';
        
        return true;
    }
    
     /**
     * Method to set the location of a node in the tree object.  This method does not
     * save the new location to the database, but will set it in the object so
     * that when the node is stored it will be stored in the new location.
     *
     * @param   integer  $referenceId  The primary key of the node to reference new location by.
     * @param   string   $position     Location type string. ['before', 'after', 'first-child', 'last-child']
     *
     * @return  boolean  True on success.
     *
     * @link    http://docs.joomla.org/TableNested/setLocation
     * @since   11.1
     */
    public function setLocation($referenceId, $position = 'after')
    {
        // Make sure the location is valid.
        if (!\in_array($position, $this->_validLocations)){
            throw new \InvalidArgumentException(
                sprintf('Invalid location "%1$s" given, valid values are %2$s', $position, implode(', ', $this->_validLocations))
            );
        }

        // Set the location properties.
        $this->_location = $position;
        $this->_location_id = $referenceId;
    }
    
   
    /**
     * Method to rebuild the node's path field from the alias values of the
     * nodes from the current node to the root node of the tree.
     *
     * @param   integer  $pk  Primary key of the node for which to get the path.
     *
     * @return  boolean  True on success.
     *
     * @link    http://docs.joomla.org/TableNested/rebuildPath
     * @since   11.1
     */
    public function rebuildPath($pk = null)
    {
        $fields = $this->getFields();

        // If there is no alias or path field, just return true.
        if (!\array_key_exists('alias', $fields) || !\array_key_exists('path', $fields))
        {
            return true;
        }

        $k = $this->_tbl_key;
        $pk = (\is_null($pk)) ? $this->$k : $pk;

        // Get the aliases for the path from the node to the root node.
        $query = $this->_db->getQuery(true);
        $query->select('p.cat_dir');
        $query->from($this->_tbl.' AS n, '.$this->_tbl.' AS p');
        $query->where('n.lft BETWEEN p.lft AND p.rgt');
        $query->where('n.'.$this->_tbl_key.' = '. (int) $pk);
        $query->order('p.lft');
        $this->_db->setQuery($query);

        $segments = $this->_db->loadColumn();

        // Make sure to remove the root path if it exists in the list.
        if ($segments[0] == ''){
            array_shift($segments);
        }
        
        // remove the cat dir from current sub cat in the list. 
        array_pop($segments);

        // Build the path.
        $path = trim(implode('/', $segments), ' /\\');

        // Update the path field for the node.
        $query = $this->_db->getQuery(true);
        $query->update($this->_tbl);
        $query->set('cat_dir_parent = '.$this->_db->quote($path));
        $query->where($this->_tbl_key.' = '.(int) $pk);
        $this->_db->setQuery($query);

        // Check for a database error.
        if (!$this->_db->execute()){
            $e = new JException(Text::sprintf('JLIB_DATABASE_ERROR_REBUILDPATH_FAILED', get_class($this), $this->_db->getErrorMsg()));
            $this->setError($e);
            
            return false;
        }

        // Update the current record's path to the new one:
        $this->cat_dir_parent = $path;
        return true;
    }    
    
    public function rebuild($parentId = null, $leftId = 0, $level = 0, $path = '')
    {
        // If no parent is provided, try to find it.
        if ($parentId === null){
            // Get the root item.
            $parentId = $this->getRootId();

            if ($parentId === false){
                return false;
            }
        }

        $query = $this->_db->getQuery(true);

        // Build the structure of the recursive query.
        if (!isset($this->_cache['rebuild.sql'])){
            $query->select($this->_tbl_key.', cat_dir, cat_dir_parent');
            //$query->select($this->_tbl_key.', alias');
            $query->from($this->_tbl);
            $query->where('parent_id = %d');
            $query->order('parent_id, lft');

            // If the table has an ordering field, use that for ordering.
            /*if (property_exists($this, 'ordering')) {
                $query->order('parent_id, ordering, lft');
            } else { 
                $query->order('parent_id, lft');
            } */

            $this->_cache['rebuild.sql'] = (string) $query;
        }

        // Make a shortcut to database object.

        // Assemble the query to find all children of this node.
        $this->_db->setQuery(sprintf($this->_cache['rebuild.sql'], (int) $parentId));

        $children = $this->_db->loadObjectList();

        // The right value of this node is the left value + 1
        $rightId = $leftId + 1;

        // Execute this function recursively over all children
        foreach ($children as $node){
            /*
             * $rightId is the current right value, which is incremented on recursion return.
             * Increment the level for the children.
             * Add this item's alias to the path (but avoid a leading /)
             */
            $rightId = $this->rebuild($node->{$this->_tbl_key}, $rightId, $level + 1, $path . (empty($path) ? '' : '/') . $node->cat_dir);

            // If there is an update failure, return false to break out of the recursion.
            if ($rightId === false){
                return false;
            }
        }

        // We've got the left value, and now that we've processed
        // The children of this node we also know the right value.
        $path = substr($path, 0, strrpos($path, '/') );
        $query = $this->_db->getQuery(true);
        $query->update($this->_tbl);
        $query->set('lft = '. (int) $leftId);
        $query->set('rgt = '. (int) $rightId);
        $query->set('level = '.(int) $level);
        $query->set('cat_dir_parent = '.$this->_db->quote($path));
        $query->where($this->_tbl_key.' = '. (int)$parentId);
        $this->_db->setQuery($query);
        
        // If there is an update failure, return false to break out of the recursion.
        if (!$this->_db->execute()){
            $e = new JException(Text::sprintf('JLIB_DATABASE_ERROR_REBUILD_FAILED', get_class($this), $this->_db->getErrorMsg()));
            $this->setError($e);
            
            return false;
        }

        // Return the right value of this node + 1.
        return $rightId + 1;
    }  
    
    /**
     * Method to store a node in the database table.
     *
     * @param   boolean  True to update null values as well.
     *
     * @return  boolean  True on success.
     *
     * @link    http://docs.joomla.org/TableNested/store
     * @since   11.1
     */
    public function store($updateNulls = false)
    {
        $date = Factory::getDate()->toSql();
        $user = Factory::getApplication()->getIdentity();
        
        // Set created date if not set.
        if (!(int) $this->created_time){
            $this->created_time = $date;
        }

        if ($this->id){
            // Existing category
            $this->modified_user_id = $user->get('id');
            $this->modified_time    = $date;
        } else {
            if (!(int) ($this->modified_time)){
                $this->modified_time = $this->created_time;
            }

            // Field created_user_id can be set by the user, so we don't touch it if it's set.
            if (empty($this->created_user_id)){
                $this->created_user_id = $user->get('id');
            }

            if (empty($this->modified_user_id)){
                $this->modified_user_id = $this->created_user_id;
            }
        }
        
        // Initialise variables.
        $k = $this->_tbl_key;

        if ($this->_debug){
            echo "\n".get_class($this)."::store\n";
            $this->_logtable(true, false);
        }
        
        /*
         * If the primary key is empty, then we assume we are inserting a new node into the
         * tree.  From this point we would need to determine where in the tree to insert it.
         */
        if (empty($this->$k)){
            /*
             * We are inserting a node somewhere in the tree with a known reference
             * node.  We have to make room for the new node and set the left and right
             * values before we insert the row.
             */
            if ($this->_location_id >= 0){
                // Lock the table for writing.
                if (!$this->_lock()){
                    // Error message set in lock method.
                    return false;
                }

                // We are inserting a node relative to the last root node.
                if ($this->_location_id == 0){
                    // Get the last root node as the reference node.
                    $query = $this->_db->getQuery(true);
                    $query->select($this->_tbl_key.', parent_id, level, lft, rgt');
                    $query->from($this->_tbl);
                    $query->where('parent_id = 0');
                    $query->order('lft DESC');
                    $this->_db->setQuery($query, 0, 1);

                    // Check for a database error.
                    try
                    {
                        $reference = $this->_db->loadObject();
                    }
                    catch (\RuntimeException $e)
                    {
                        Factory::getApplication()->enqueueMessage($e->getMessage(), 'error');
                        $this->_unlock();
                        return false;
                    }
                    
                    if ($this->_debug){
                        $this->_logtable(false);
                    }
                } else {
                    // We have a real node set as a location reference.
                    
                    // Get the reference node by primary key.
                    if (!$reference = $this->_getNode($this->_location_id)){
                        // Error message set in getNode method.
                        $this->_unlock();
                        return false;
                    }
                }

                // Get the reposition data for shifting the tree and re-inserting the node.
                if (!($repositionData = $this->_getTreeRepositionData($reference, 2, $this->_location))){
                    // Error message set in getNode method.
                    $this->_unlock();
                    return false;
                }

                // Create space in the tree at the new location for the new node in left ids.
                $query = $this->_db->getQuery(true);
                $query->update($this->_tbl);
                $query->set('lft = lft + 2');
                $query->where($repositionData->left_where);
                $this->_runQuery($query, 'JLIB_DATABASE_ERROR_STORE_FAILED');

                // Create space in the tree at the new location for the new node in right ids.
                $query = $this->_db->getQuery(true);
                $query->update($this->_tbl);
                $query->set('rgt = rgt + 2');
                $query->where($repositionData->right_where);
                $this->_runQuery($query, 'JLIB_DATABASE_ERROR_STORE_FAILED');

                // Set the object values.
                $this->parent_id    = $repositionData->new_parent_id;
                $this->level        = $repositionData->new_level;
                $this->lft          = $repositionData->new_lft;
                $this->rgt          = $repositionData->new_rgt;
            
            } else {
                
                // Negative parent ids are invalid
                $e = new JException(Text::_('JLIB_DATABASE_ERROR_INVALID_PARENT_ID'));
                $this->setError($e);
                return false;
            }

        } else {
            
            /*
             * If we have a given primary key then we assume we are simply updating this
             * node in the tree.  We should assess whether or not we are moving the node
             * or just updating its data fields.
             */
                
            // If the location has been set, move the node to its new location.
            if ($this->_location_id > 0){
                if (!$this->moveByReference($this->_location_id, $this->_location, $this->$k)){
                    // Error message set in move method.
                    return false;
                }
            }

            // Lock the table for writing.
            /*  if (!$this->_lock()) {
                // Error message set in lock method.
                return false;
            } */
        }

        // Store the row to the database.
        if (!parent::store($updateNulls)){
            $this->_unlock();
            return false;
        }
        
        if ($this->_debug){
            $this->_logtable();
        }

        // Unlock the table for writing.
        $this->_unlock();

        return true;
    } 
    
    /**
     * Method to get nested set properties for a node in the tree.
     *
     * @param   integer  $id   Value to look up the node by.
     * @param   string   $key  Key to look up the node by.
     *
     * @return  mixed    Boolean false on failure or node object on success.
     *
     * @since   11.1
     */
    protected function _getNode($id, $key = null)
    {
        // Determine which key to get the node base on.
        switch ($key)
        {
            case 'parent':
                $k = 'parent_id';
                break;
            case 'left':
                $k = 'lft';
                break;
            case 'right':
                $k = 'rgt';
                break;
            default:
                $k = $this->_tbl_key;
                break;
        }

        // Get the node data.
        $query = $this->_db->getQuery(true)
            ->select($this->_tbl_key . ', parent_id, level, lft, rgt')
            ->from($this->_tbl)
            ->where($k . ' = ' . (int) $id);

        $row = $this->_db->setQuery($query, 0, 1)->loadObject();        
        
        // Check for no $row returned
        if (empty($row)){
            $e = new JException(Text::sprintf('JLIB_DATABASE_ERROR_GETNODE_FAILED', get_class($this), $this->_db->getErrorMsg()));
            $this->setError($e);
            return false;
        }

        // Do some simple calculations.
        $row->numChildren = (int) ($row->rgt - $row->lft - 1) / 2;
        $row->width = (int) $row->rgt - $row->lft + 1;

        return $row;
    } 
    
    /**
     * Method to get various data necessary to make room in the tree at a location
     * for a node and its children.  The returned data object includes conditions
     * for SQL WHERE clauses for updating left and right id values to make room for
     * the node as well as the new left and right ids for the node.
     *
     * @param   object   $referenceNode  A node object with at least a 'lft' and 'rgt' with
     *                                   which to make room in the tree around for a new node.
     * @param   integer  $nodeWidth      The width of the node for which to make room in the tree.
     * @param   string   $position       The position relative to the reference node where the room
     *                                     should be made.
     *
     * @return  mixed    Boolean false on failure or data object on success.
     *
     * @since   11.1
     */
    protected function _getTreeRepositionData($referenceNode, $nodeWidth, $position = 'before')
    {
        // Make sure the reference an object with a left and right id.
        if (!is_object($referenceNode) && isset($referenceNode->lft) && isset($referenceNode->rgt)){
            return false;
        }

        // A valid node cannot have a width less than 2.
        if ($nodeWidth < 2) return false;

        // Initialise variables.
        $k = $this->_tbl_key;
        $data = new \stdClass;

        // Run the calculations and build the data object by reference position.
        switch ($position)
        {
            case 'first-child':
                $data->left_where        = 'lft > '.$referenceNode->lft;
                $data->right_where        = 'rgt >= '.$referenceNode->lft;

                $data->new_lft            = $referenceNode->lft + 1;
                $data->new_rgt            = $referenceNode->lft + $nodeWidth;
                $data->new_parent_id    = $referenceNode->$k;
                $data->new_level        = $referenceNode->level + 1;
                break;

            case 'last-child':
                $data->left_where        = 'lft > '.($referenceNode->rgt);
                $data->right_where        = 'rgt >= '.($referenceNode->rgt);

                $data->new_lft            = $referenceNode->rgt;
                $data->new_rgt            = $referenceNode->rgt + $nodeWidth - 1;
                $data->new_parent_id    = $referenceNode->$k;
                $data->new_level        = $referenceNode->level + 1;
                break;

            case 'before':
                $data->left_where        = 'lft >= '.$referenceNode->lft;
                $data->right_where        = 'rgt >= '.$referenceNode->lft;

                $data->new_lft            = $referenceNode->lft;
                $data->new_rgt            = $referenceNode->lft + $nodeWidth - 1;
                $data->new_parent_id    = $referenceNode->parent_id;
                $data->new_level        = $referenceNode->level;
                break;

            default:
            case 'after':
                $data->left_where        = 'lft > '.$referenceNode->rgt;
                $data->right_where        = 'rgt > '.$referenceNode->rgt;

                $data->new_lft            = $referenceNode->rgt + 1;
                $data->new_rgt            = $referenceNode->rgt + $nodeWidth;
                $data->new_parent_id    = $referenceNode->parent_id;
                $data->new_level        = $referenceNode->level;
                break;
        }

        if ($this->_debug){
            echo "\nRepositioning Data for $position" .
                    "\n-----------------------------------" .
                    "\nLeft Where:    $data->left_where" .
                    "\nRight Where:   $data->right_where" .
                    "\nNew Lft:       $data->new_lft" .
                    "\nNew Rgt:       $data->new_rgt".
                    "\nNew Parent ID: $data->new_parent_id".
                    "\nNew Level:     $data->new_level" .
                    "\n";
        }

        return $data;
    }
    
/**
     * Method to move a row in the ordering sequence of a group of rows defined by an SQL WHERE clause.
     * Negative numbers move the row up in the sequence and positive numbers move it down.
     *
     * @param   integer  $delta  The direction and magnitude to move the row in the ordering sequence.
     * @param   string   $where  WHERE clause to use for limiting the selection of rows to compact the
     *                           ordering values.
     *
     * @return  mixed    Boolean true on success.
     *
     * @link    http://docs.joomla.org/Table/move
     * @since   11.1
     */
    public function move($delta, $where = '')
    {
        // Initialise variables.
        $k = $this->_tbl_key;
        $pk = $this->$k;

        $query = $this->_db->getQuery(true);
        $query->select($k);
        $query->from($this->_tbl);
        $query->where('parent_id = '.$this->parent_id);
        if ($where) {
            $query->where($where);
        }
        $position = 'after';
        
        if ($delta > 0){
            $query->where('rgt > '.$this->rgt);
            $query->order('rgt ASC');
            $position = 'after';
        } else {
            $query->where('lft < '.$this->lft);
            $query->order('lft DESC');
            $position = 'before';
        }

        $this->_db->setQuery($query);
        $referenceId = $this->_db->loadResult();

        if ($referenceId){
            return $this->moveByReference($referenceId, $position, $pk);
        } else {
            return false;
        }
    }

    /**
     * Method to move a node and its children to a new location in the tree.
     *
     * @param   integer  $referenceId  The primary key of the node to reference new location by.
     * @param   string   $position     Location type string. ['before', 'after', 'first-child', 'last-child']
     * @param   integer  $pk           The primary key of the node to move.
     *
     * @return  boolean  True on success.
     *
     * @link    http://docs.joomla.org/TableNested/moveByReference
     * @since   11.1
     */

    public function moveByReference($referenceId, $position = 'after', $pk = null)
    {
        if ($this->_debug){
            echo "\nMoving ReferenceId:$referenceId, Position:$position, PK:$pk";
        }

        // Initialise variables.
        $k = $this->_tbl_key;
        $pk = (is_null($pk)) ? $this->$k : $pk;

        // Get the node by id.
        if (!$node = $this->_getNode($pk)){
            // Error message set in getNode method.
            return false;
        }

        // Get the ids of child nodes.
        $query = $this->_db->getQuery(true);
        $query->select($k);
        $query->from($this->_tbl);
        $query->where('lft BETWEEN '.(int) $node->lft.' AND '.(int) $node->rgt);
        $this->_db->setQuery($query);

        // Check for a database error.
        try
        {
            $children = $this->_db->loadColumn();
        }
        catch (\RuntimeException $e)
        {
            Factory::getApplication()->enqueueMessage($e->getMessage(), 'error');
            return false;
        }

        if ($this->_debug){
            $this->_logtable(false);
        }

        // Cannot move the node to be a child of itself.
        if (in_array($referenceId, $children)){
            $e = new JException(Text::sprintf('JLIB_DATABASE_ERROR_INVALID_NODE_RECURSION', get_class($this)));
            $this->setError($e);
            return false;
        }

        // Lock the table for writing.
        if (!$this->_lock()){
            return false;
        }

        /*
         * Move the sub-tree out of the nested sets by negating its left and right values.
        */
        $query = $this->_db->getQuery(true);
        $query->update($this->_tbl);
        $query->set('lft = lft * (-1), rgt = rgt * (-1)');
        $query->where('lft BETWEEN '.(int) $node->lft.' AND '.(int) $node->rgt);
        $this->_db->setQuery($query);

        $this->_runQuery($query, 'JLIB_DATABASE_ERROR_MOVE_FAILED');

        /*
         * Close the hole in the tree that was opened by removing the sub-tree from the nested sets.
         */
        // Compress the left values.
        $query = $this->_db->getQuery(true);
        $query->update($this->_tbl);
        $query->set('lft = lft - '.(int) $node->width);
        $query->where('lft > '.(int) $node->rgt);
        $this->_db->setQuery($query);

        $this->_runQuery($query, 'JLIB_DATABASE_ERROR_MOVE_FAILED');

        // Compress the right values.
        $query = $this->_db->getQuery(true);
        $query->update($this->_tbl);
        $query->set('rgt = rgt - '.(int) $node->width);
        $query->where('rgt > '.(int) $node->rgt);
        $this->_db->setQuery($query);

        $this->_runQuery($query, 'JLIB_DATABASE_ERROR_MOVE_FAILED');

        // We are moving the tree relative to a reference node.
        if ($referenceId){
            // Get the reference node by primary key.
            if (!$reference = $this->_getNode($referenceId)){
                // Error message set in getNode method.
                $this->_unlock();
                return false;
            }

            // Get the reposition data for shifting the tree and re-inserting the node.
            if (!$repositionData = $this->_getTreeRepositionData($reference, $node->width, $position)){
                // Error message set in getNode method.
                $this->_unlock();
                return false;
            }
        
        } else {
            
            // We are moving the tree to be the last child of the root node
            
            // Get the last root node as the reference node.
            $query = $this->_db->getQuery(true);
            $query->select($this->_tbl_key.', parent_id, level, lft, rgt');
            $query->from($this->_tbl);
            $query->where('parent_id = 0');
            $query->order('lft DESC');
            $this->_db->setQuery($query, 0, 1);

            // Check for a database error.
            try
            {
                $reference = $this->_db->loadObject();
            }
            catch (\RuntimeException $e)
            {
                Factory::getApplication()->enqueueMessage($e->getMessage(), 'error');
                $this->_unlock();
                return false;
            }

            if ($this->_debug){
                $this->_logtable(false);
            }

            // Get the reposition data for re-inserting the node after the found root.
            if (!$repositionData = $this->_getTreeRepositionData($reference, $node->width, 'last-child')){
                // Error message set in getNode method.
                $this->_unlock();
                return false;
            }
        }

        /*
         * Create space in the nested sets at the new location for the moved sub-tree.
         */
        // Shift left values.
        $query = $this->_db->getQuery(true);
        $query->update($this->_tbl);
        $query->set('lft = lft + '.(int) $node->width);
        $query->where($repositionData->left_where);
        $this->_db->setQuery($query);

        $this->_runQuery($query, 'JLIB_DATABASE_ERROR_MOVE_FAILED');

        // Shift right values.
        $query = $this->_db->getQuery(true);
        $query->update($this->_tbl);
        $query->set('rgt = rgt + '.(int) $node->width);
        $query->where($repositionData->right_where);
        $this->_db->setQuery($query);

        $this->_runQuery($query, 'JLIB_DATABASE_ERROR_MOVE_FAILED');

        /*
         * Calculate the offset between where the node used to be in the tree and
         * where it needs to be in the tree for left ids (also works for right ids).
         */
        $offset = $repositionData->new_lft - $node->lft;
        $levelOffset = $repositionData->new_level - $node->level;

        // Move the nodes back into position in the tree using the calculated offsets.
        $query = $this->_db->getQuery(true);
        $query->update($this->_tbl);
        $query->set('rgt = '.(int) $offset.' - rgt');
        $query->set('lft = '.(int) $offset.' - lft');
        $query->set('level = level + '.(int) $levelOffset);
        $query->where('lft < 0');
        $this->_db->setQuery($query);

        $this->_runQuery($query, 'JLIB_DATABASE_ERROR_MOVE_FAILED');

        // Set the correct parent id for the moved node if required.
        if ($node->parent_id != $repositionData->new_parent_id){
            $query = $this->_db->getQuery(true);
            $query->update($this->_tbl);

            // Update the title and alias fields if they exist for the table.
            if (property_exists($this, 'title') && $this->title !== null){
                $query->set('title = '.$this->_db->Quote($this->title));
            }
            if (property_exists($this, 'alias') && $this->alias !== null){
                $query->set('alias = '.$this->_db->Quote($this->alias));
            }

            $query->set('parent_id = '.(int) $repositionData->new_parent_id);
            $query->where($this->_tbl_key.' = '.(int) $node->$k);
            $this->_db->setQuery($query);

            $this->_runQuery($query, 'JLIB_DATABASE_ERROR_MOVE_FAILED');
        }

        // Unlock the table for writing.
        $this->_unlock();

        // Set the object values.
        $this->parent_id = $repositionData->new_parent_id;
        $this->level = $repositionData->new_level;
        $this->lft = $repositionData->new_lft;
        $this->rgt = $repositionData->new_rgt;

        return true;
    }

    /**
     * Method to delete a node and, optionally, its child nodes from the table.
     *
     * @param   integer  $pk        The primary key of the node to delete.
     * @param   boolean  $children  True to delete child nodes, false to move them up a level.
     *
     * @return  boolean  True on success.
     *
     * @link    http://docs.joomla.org/TableNested/delete
     */
    public function delete($pk = null, $children = true)
    {
       
        // Initialise variables.
        $k = $this->_tbl_key;
        $pk = (is_null($pk)) ? $this->$k : $pk;
        
        if ($this->hasChildren($pk)){
           // category has children so we can not delete it 
           Factory::getApplication()->enqueueMessage( Text::_('COM_JDOWNLOADS_BE_NO_DEL_SUBCATS_EXISTS'), 'warning');
           return false; 
        }
        
        if ($this->hasDownloads($pk)){
           // category has downloads so we can not delete it 
           Factory::getApplication()->enqueueMessage( Text::_('COM_JDOWNLOADS_BE_NO_DEL_FILES_EXISTS'), 'notice');
           return false; 
        }        
        
        // Pre-processing by observers
        $event = AbstractEvent::create(
            'onTableBeforeDelete',
            [
                'subject'    => $this,
                'pk'        => $pk,
            ]
        );
        
        $this->getDispatcher()->dispatch('onTableBeforeDelete', $event);        
        
        // Lock the table for writing.
        if (!$this->_lock()){
            // Error message set in lock method.
            return false;
        }

        // If tracking assets, remove the asset first.
        if ($this->_trackAssets){
            $name        = $this->_getAssetName();
            $asset       = Table::getInstance('Asset');

            // Lock the table for writing.
            if (!$asset->_lock()){
                // Error message set in lock method.
                return false;
            }

            if ($asset->loadByName($name)){
                // Delete the node in assets table.
                if (!$asset->delete(null, $children)){
                    $this->setError($asset->getError());
                    $asset->_unlock();
                    return false;
                }
                $asset->_unlock();
            } else {
                $this->setError($asset->getError());
                $asset->_unlock();
                return false;
            }
        }

        // Get the node by id.
        if (!$node = $this->_getNode($pk)){
            // Error message set in getNode method.
            $this->_unlock();
            return false;
        }
        
        // get first the folder name, so we can later delete this folder
        $query = $this->_db->getQuery(true);
        $query->select('cat_dir, cat_dir_parent');
        $query->from($this->_tbl);
        $query->where('id = '.(int)$pk);
        $this->_db->setQuery($query);
        $cat_dirs = $this->_db->loadObject();

        // Should we delete all children along with the node?
        if ($children){
            // Delete the node and all of its children.
            $query = $this->_db->getQuery(true);
            $query->delete();
            $query->from($this->_tbl);
            $query->where('lft BETWEEN '.(int) $node->lft.' AND '.(int) $node->rgt);
            $this->_runQuery($query, 'JLIB_DATABASE_ERROR_DELETE_FAILED');

            // Compress the left values.
            $query = $this->_db->getQuery(true);
            $query->update($this->_tbl);
            $query->set('lft = lft - '.(int) $node->width);
            $query->where('lft > '.(int) $node->rgt);
            $this->_runQuery($query, 'JLIB_DATABASE_ERROR_DELETE_FAILED');

            // Compress the right values.
            $query = $this->_db->getQuery(true);
            $query->update($this->_tbl);
            $query->set('rgt = rgt - '.(int) $node->width);
            $query->where('rgt > '.(int) $node->rgt);
            $this->_runQuery($query, 'JLIB_DATABASE_ERROR_DELETE_FAILED');
        
        } else {
            
            // Leave the children and move them up a level.
            
            // Delete the node.
            $query = $this->_db->getQuery(true);
            $query->delete();
            $query->from($this->_tbl);
            $query->where('lft = '.(int) $node->lft);
            $this->_runQuery($query, 'JLIB_DATABASE_ERROR_DELETE_FAILED');

            // Shift all node's children up a level.
            $query = $this->_db->getQuery(true);
            $query->update($this->_tbl);
            $query->set('lft = lft - 1');
            $query->set('rgt = rgt - 1');
            $query->set('level = level - 1');
            $query->where('lft BETWEEN '.(int) $node->lft.' AND '.(int) $node->rgt);
            $this->_runQuery($query, 'JLIB_DATABASE_ERROR_DELETE_FAILED');

            // Adjust all the parent values for direct children of the deleted node.
            $query = $this->_db->getQuery(true);
            $query->update($this->_tbl);
            $query->set('parent_id = '.(int) $node->parent_id);
            $query->where('parent_id = '.(int) $node->$k);
            $this->_runQuery($query, 'JLIB_DATABASE_ERROR_DELETE_FAILED');

            // Shift all of the left values that are right of the node.
            $query = $this->_db->getQuery(true);
            $query->update($this->_tbl);
            $query->set('lft = lft - 2');
            $query->where('lft > '.(int) $node->rgt);
            $this->_runQuery($query, 'JLIB_DATABASE_ERROR_DELETE_FAILED');

            // Shift all of the right values that are right of the node.
            $query = $this->_db->getQuery(true);
            $query->update($this->_tbl);
            $query->set('rgt = rgt - 2');
            $query->where('rgt > '.(int) $node->rgt);
            $this->_runQuery($query, 'JLIB_DATABASE_ERROR_DELETE_FAILED');
        }


        // delete now the folder
        if ($cat_dirs){
            if ($cat_dirs->cat_dir_parent != ''){
                $cat_dir = $cat_dirs->cat_dir_parent.'/'.$cat_dirs->cat_dir;
            } else {
                $cat_dir = $cat_dirs->cat_dir;
            }    
            JDownloadsHelper::deleteCategoryFolder($cat_dir);
        }        
        
        // Unlock the table for writing.
        $this->_unlock();

        // Post-processing by observers
        $event = AbstractEvent::create(
            'onTableAfterDelete',
            [
                'subject'    => $this,
                'pk'        => $pk,
            ]
        );
        
        $this->getDispatcher()->dispatch('onTableAfterDelete', $event);
        
        return true;
    } 
    
    /**
     * Method to move a node one position to the left in the same level.
     *
     * @param   integer  $pk  Primary key of the node to move.
     *
     * @return  boolean  True on success.
     *
     * @link    http://docs.joomla.org/TableNested/orderUp
     */
    public function orderUp($pk)
    {
        // Initialise variables.
        $k = $this->_tbl_key;
        $pk = (is_null($pk)) ? $this->$k : $pk;

        // Lock the table for writing.
        if (!$this->_lock()){
            // Error message set in lock method.
            return false;
        }

        // Get the node by primary key.
        if (!$node = $this->_getNode($pk)){
            // Error message set in getNode method.
            $this->_unlock();
            return false;
        }

        // Get the left sibling node.
        if (!$sibling = $this->_getNode($node->lft - 1, 'right')){
            // Error message set in getNode method.
            $this->_unlock();
            return false;
        }

        // Get the primary keys of child nodes.
        $query = $this->_db->getQuery(true);
        $query->select($this->_tbl_key);
        $query->from($this->_tbl);
        $query->where('lft BETWEEN '.(int) $node->lft.' AND '.(int) $node->rgt);
        $this->_db->setQuery($query);
        
        // Check for a database error.
        try
        {
            $children = $this->_db->loadColumn();
        }
        catch (\RuntimeException $e)
        {
            Factory::getApplication()->enqueueMessage($e->getMessage(), 'error');
            $this->_unlock();
            return false;
        }
    
        // Shift left and right values for the node and it's children.
        $query = $this->_db->getQuery(true);
        $query->update($this->_tbl);
        $query->set('lft = lft - '.(int) $sibling->width);
        $query->set('rgt = rgt - '.(int) $sibling->width);
        $query->where('lft BETWEEN '.(int) $node->lft.' AND '.(int) $node->rgt);
        $this->_db->setQuery($query);

        // Check for a database error.
        if (!$this->_db->execute()){
            $e = new JException(Text::sprintf('JLIB_DATABASE_ERROR_ORDERUP_FAILED', get_class($this), $this->_db->getErrorMsg()));
            $this->setError($e);
            $this->_unlock();
            return false;
        }

        // Shift left and right values for the sibling and it's children.
        $query = $this->_db->getQuery(true);
        $query->update($this->_tbl);
        $query->set('lft = lft + '.(int) $node->width);
        $query->set('rgt = rgt + '.(int) $node->width);
        $query->where('lft BETWEEN '.(int) $sibling->lft.' AND '.(int) $sibling->rgt);
        $query->where($this->_tbl_key.' NOT IN ('.implode(',', $children).')');
        $this->_db->setQuery($query);

        // Check for a database error.
        if (!$this->_db->execute()){
            $e = new JException(Text::sprintf('JLIB_DATABASE_ERROR_ORDERUP_FAILED', get_class($this), $this->_db->getErrorMsg()));
            $this->setError($e);
            $this->_unlock();
            return false;
        }

        // Unlock the table for writing.
        $this->_unlock();

        return true;
    }

    /**
     * Method to move a node one position to the right in the same level.
     *
     * @param   integer  $pk  Primary key of the node to move.
     *
     * @return  boolean  True on success.
     *
     * @link    http://docs.joomla.org/TableNested/orderDown
     */
    public function orderDown($pk)
    {
        // Initialise variables.
        $k = $this->_tbl_key;
        $pk = (is_null($pk)) ? $this->$k : $pk;

        // Lock the table for writing.
        if (!$this->_lock()){
            // Error message set in lock method.
            return false;
        }

        // Get the node by primary key.
        if (!$node = $this->_getNode($pk)){
            // Error message set in getNode method.
            $this->_unlock();
            return false;
        }

        // Get the right sibling node.
        if (!$sibling = $this->_getNode($node->rgt + 1, 'left')){
            // Error message set in getNode method.
            $query->unlock($this->_db);
            $this->_locked=false;
            return false;
        }

        // Get the primary keys of child nodes.
        $query = $this->_db->getQuery(true);
        $query->select($this->_tbl_key);
        $query->from($this->_tbl);
        $query->where('lft BETWEEN '.(int) $node->lft.' AND '.(int) $node->rgt);
        $this->_db->setQuery($query);
        
        // Check for a database error.
        try
        {
            $children = $this->_db->loadColumn();
        }
        catch (\RuntimeException $e)
        {
            Factory::getApplication()->enqueueMessage($e->getMessage(), 'error');
            $this->_unlock();
            return false;
        }
        
        // Shift left and right values for the node and it's children.
        $query = $this->_db->getQuery(true);
        $query->update($this->_tbl);
        $query->set('lft = lft + '.(int) $sibling->width);
        $query->set('rgt = rgt + '.(int) $sibling->width);
        $query->where('lft BETWEEN '.(int) $node->lft.' AND '.(int) $node->rgt);
        $this->_db->setQuery($query);

        // Check for a database error.
        if (!$this->_db->execute()){
            $e = new JException(Text::sprintf('JLIB_DATABASE_ERROR_ORDERDOWN_FAILED', get_class($this), $this->_db->getErrorMsg()));
            $this->setError($e);
            $this->_unlock();
            return false;
        }

        // Shift left and right values for the sibling and it's children.
        $query = $this->_db->getQuery(true);
        $query->update($this->_tbl);
        $query->set('lft = lft - '.(int) $node->width);
        $query->set('rgt = rgt - '.(int) $node->width);
        $query->where('lft BETWEEN '.(int) $sibling->lft.' AND '.(int) $sibling->rgt);
        $query->where($this->_tbl_key.' NOT IN ('.implode(',', $children).')');
        $this->_db->setQuery($query);

        // Check for a database error.
        if (!$this->_db->execute()){
            $e = new JException(Text::sprintf('JLIB_DATABASE_ERROR_ORDERDOWN_FAILED', get_class($this), $this->_db->getErrorMsg()));
            $this->setError($e);
            $this->_unlock();
            return false;
        }

        // Unlock the table for writing.
        $this->_unlock();

        return true;
    }
    
    /**
     * Method to update order of table rows
     *
     * @param   array    $idArray    id numbers of rows to be reordered
     * @param   array    $lft_array  lft values of rows to be reordered
     *
     * @return  integer  1 + value of root rgt on success, false on failure
     */
    public function saveorder($idArray = null, $lft_array = null)
    {
        // Validate arguments
        if (is_array($idArray) && is_array($lft_array) && count($idArray) == count($lft_array)){
            for ($i = 0, $count = count($idArray); $i < $count; $i++){
                // Do an update to change the lft values in the table for each id
                $query = $this->_db->getQuery(true);
                $query->update($this->_tbl);
                $query->where($this->_tbl_key . ' = ' . (int) $idArray[$i]);
                $query->set('lft = ' . (int) $lft_array[$i]);
                $this->_db->setQuery($query);

                // Check for a database error.
                if (!$this->_db->execute()){
                    $e = new JException(Text::sprintf('JLIB_DATABASE_ERROR_REORDER_FAILED', get_class($this), $this->_db->getErrorMsg()));
                    $this->setError($e);
                    $this->_unlock();
                    return false;
                }

                if ($this->_debug){
                    $this->_logtable();
                }

            }

            return $this->rebuild();
        
        } else {
            return false;
        }
    }
    

    /**
     * Gets the ID of the root item in the tree
     *
     * @return  mixed    The ID of the root row, or false and the internal error is set.
     *
     */
    public function getRootId()
    {
        if ((int) self::$root_id > 0){
            return self::$root_id;
        }

        // Get the root item.
        $k = $this->_tbl_key;

        // Test for a unique record with parent_id = 0
        $query = $this->_db->getQuery(true)
            ->select($k)
            ->from($this->_tbl)
            ->where('parent_id = 0');

        $result = $this->_db->setQuery($query)->loadColumn();

        if (\count($result) == 1){
            self::$root_id = $result[0];

            return self::$root_id;
        }

        // Test for a unique record with lft = 0
        $query->clear()
            ->select($k)
            ->from($this->_tbl)
            ->where('lft = 0');

        $result = $this->_db->setQuery($query)->loadColumn();

        if (\count($result) == 1){
            self::$root_id = $result[0];

            return self::$root_id;
        }

        $fields = $this->getFields();

        if (\array_key_exists('alias', $fields)){
            // Test for a unique record alias = root
            $query->clear()
                ->select($k)
                ->from($this->_tbl)
                ->where('alias = ' . $this->_db->quote('root'));

            $result = $this->_db->setQuery($query)->loadColumn();

            if (\count($result) == 1){
                self::$root_id = $result[0];

                return self::$root_id;
            }
        }

        $e = new \UnexpectedValueException(sprintf('%s::getRootId', \get_class($this)));
        $this->setError($e);
        self::$root_id = false;

        return false;
    }       
    
    /**
     * Method to create a log table in the buffer optionally showing the query and/or data.
     *
     * @param   boolean  $showData   True to show data
     * @param   boolean  $showQuery  True to show query
     *
     * @return  void
     *
     * @since   11.1
     */
    protected function _logtable($showData = true, $showQuery = true)
    {
        $sep    = "\n".str_pad('', 40, '-');
        $buffer    = '';
        if ($showQuery){
            $buffer .= "\n".$this->_db->getQuery().$sep;
        }

        if ($showData){
            $query = $this->_db->getQuery(true);
            $query->select($this->_tbl_key.', parent_id, lft, rgt, level');
            $query->from($this->_tbl);
            $query->order($this->_tbl_key);
            $this->_db->setQuery($query);

            $rows = $this->_db->loadRowList();
            $buffer .= sprintf("\n| %4s | %4s | %4s | %4s |", $this->_tbl_key, 'par', 'lft', 'rgt');
            $buffer .= $sep;

            foreach ($rows as $row){
                $buffer .= sprintf("\n| %4s | %4s | %4s | %4s |", $row[0], $row[1], $row[2], $row[3]);
            }
            $buffer .= $sep;
        }
        echo $buffer;
    }
    
/**
     * Method to determine if a node is a leaf node in the tree (has no children).
     *
     * @param   integer  $pk  Primary key of the node to check.
     *
     * @return  boolean  True if a leaf node.
     *
     * @link    http://docs.joomla.org/TableNested/isLeaf
     * @since   11.1
     */
    public function isLeaf($pk = null)
    {
        // Initialise variables.
        $k = $this->_tbl_key;
        $pk = (is_null($pk)) ? $this->$k : $pk;

        // Get the node by primary key.
        if (!$node = $this->_getNode($pk)){
            // Error message set in getNode method.
            return false;
        }

        // The node is a leaf node.
        return (($node->rgt - $node->lft) == 1);
    }    
    
    /**
     * Method to run an update query and check for a database error
     *
     * @params  string   $query
     * @param   string   $errorMessage
     *
     * @return  boolean  False on exception
     *
     */
    protected function _runQuery($query, $errorMessage)
    {
        $this->_db->setQuery($query);

        // Check for a database error.
        if (!$this->_db->execute()){
            $e = new JException(Text::sprintf('$errorMessage', get_class($this), $this->_db->getErrorMsg()));
            $this->setError($e);
            $this->_unlock();
            return false;
        }
        
        if ($this->_debug){
            $this->_logtable();
        }
    }
    
    /**
     * Method to handle the category folder actions (create/move/rename)
     *
     * @param   string   $isNew                 true when used
     * @param   string   $catChanged            true when used
     * @param   string   $titleChanged          true when used 
     * @param   string   $checked_cat_title     the new/changed and checked category folder name  
     *
     * @return  boolean  $result True on success
     */
    public function checkCategoryFolder($isNew, $catChanged, $titleChanged, $checked_cat_title, $cat_dir_changed_manually)
    {
       $params = ComponentHelper::getParams('com_jdownloads');
       
       $jinput = Factory::getApplication()->input;
               
       $root_dir_path = $params->get('files_uploaddir');
       
       if (!$isNew && !$catChanged && !$titleChanged && !$cat_dir_changed_manually){
           // Nothing to do
           return true;
       }
       
       if ($isNew){
          // Get parent dir when selected
          if ($this->parent_id > 1){
              $this->cat_dir_parent = $this->getParentCategoryPath($this->parent_id);
          }
          
          if ($this->cat_dir_parent != ''){
              $cat_dir_path = $root_dir_path.'/'.$this->cat_dir_parent.'/'.$this->cat_dir;
          } else {
              $cat_dir_path = $root_dir_path.'/'.$this->cat_dir;
          }

          // Create the new folder when he not exists
          if (!Folder::exists($cat_dir_path)){
              $result = Folder::create($cat_dir_path);
          } else {
              // New category but the given cat_dir exists always... 
              // TODO: problem, we have stored the new category in DB but can not create the new folder - so we have now two categories with the same folder path?   
              $result = false;
          }
           
       } else {
           // Build the new folder path to move or rename the folder when needed
           if ($this->parent_id > 1){
               $this->cat_dir_parent = $this->getParentCategoryPath($this->parent_id);
           } else {
               $this->cat_dir_parent = '';
           }
           if ($this->cat_dir_parent != ''){
               $new_cat_dir_path = $root_dir_path.'/'.$this->cat_dir_parent.'/'.$this->cat_dir;
           } else {
               $new_cat_dir_path = $root_dir_path.'/'.$this->cat_dir;
           }
           
           // We need also the old folder path  
           $old_parent = $jinput->get('cat_dir_parent_org', '', 'string');
           $old_dir    = $jinput->get('cat_dir_org', '', 'string');  
           if ($old_parent != ''){
               $old_cat_dir_path = $root_dir_path.'/'.$old_parent.'/'.$old_dir;
           } else {
               $old_cat_dir_path = $root_dir_path.'/'.$old_dir;
           }           
       
           // Category is not new - so we must at first check, whether the title is changed.
           if ($titleChanged || $cat_dir_changed_manually){
               // Get the old and new cat dir and rename it
               if (Folder::exists($old_cat_dir_path)){
                   $result = Folder::move($old_cat_dir_path, $new_cat_dir_path);
               } else {
                   Factory::getApplication()->enqueueMessage( Text::sprintf('COM_JDOWNLOADS_CATSEDIT_ERROR_CHECK_FOLDER', $old_cat_dir_path ), 'warning');
                   $result = false; 
               }
           }    
           
           // We must only check this, when the user have not changed the category title
           // If so, we must move the category folder complete to the new position
           if ($catChanged && !$titleChanged){
               // Move it to the new location when exists
               if (Folder::exists($old_cat_dir_path)){
                   $result = JDownloadsHelper::moveDirs($old_cat_dir_path.'/', $new_cat_dir_path.'/', $msg, true, true, false, false);
                   if ($result !== true) {
                       // $result has a error message from file/folder operations
                       Factory::getApplication()->enqueueMessage( $result, 'warning');
                       $result = false;                        
                   }  
               } else {
                   $result = false;
               }   
           } 
       }
       return $result;    
    }
    
    // Check whether a category has children
    // @return    boolean    True on success.
    public function hasChildren($pk)
    {
        $query = $this->_db->getQuery(true);
        $query->select('count(*)');
        $query->from('#__jdownloads_categories');
        $query->where('parent_id = '.(int)$pk);
        $this->_db->setQuery($query);
        
        if ($this->_db->loadResult() > 0){
            return true;
        } else {
            return false;
        }
    }
    
    // Check whether a category has downloads
    // @return    boolean    True on success.
    public function hasDownloads($pk)
    {
        $query = $this->_db->getQuery(true);
        $query->select('count(*)');
        $query->from('#__jdownloads_files');
        $query->where('catid = '.(int)$pk);
        $this->_db->setQuery($query);
        
        if ($this->_db->loadResult() > 0){
            return true;
        } else {
            return false;
        }
    } 

    // Get the path from a given parent_id
    // @return    path    The folder path from the parent category
    public function getParentCategoryPath($parent_id)
    {
        $catpath = '';
        $query = $this->_db->getQuery(true);
        $query->select('cat_dir, cat_dir_parent');
        $query->from('#__jdownloads_categories');
        $query->where('id = '.(int)$parent_id);
        $this->_db->setQuery($query);
        $path = $this->_db->loadObject();
        
        if ($path->cat_dir_parent != ''){
            $catpath = $path->cat_dir_parent.'/'.$path->cat_dir;
        } else {
            $catpath = $path->cat_dir;            
        }
        return $catpath;
    }
    
    /**
     * Overloaded bind function.
     *
     * @param   array   $array   named array
     * @param   string  $ignore  An optional array or space separated list of properties
     * to ignore while binding.
     *
     * @return  mixed   Null if operation was satisfactory, otherwise returns an error
     *
     * @see     Table::bind
     */
    public function bind($array, $ignore = '')
    {
        if (isset($array['params']) && is_array($array['params'])){
            $registry = new Registry;
            $registry->loadArray($array['params']);
            $array['params'] = (string) $registry;
        }

        // Bind the rules.
        if (isset($array['rules']) && is_array($array['rules'])){
            $rules = new Rules($array['rules']);
            $this->setRules($rules);
        }

        return parent::bind($array, $ignore);
    }
    
    
    /**
     * Method to compute the default name of the asset.
     * The default name is in the form `table_name.id`
     * where id is the value of the primary key of the table.
     *
     * @return    string
     * @since    1.6
     */
    protected function _getAssetName()
    {
        $k = $this->_tbl_key;
        return 'com_jdownloads.category.'.(int) $this->$k;
    }

    /**
     * Method to return the title to use for the asset table.
     *
     * @return    string
     * @since    1.6
     */
    protected function _getAssetTitle()
    {
        return $this->title;
    }

    /**
     * Get the parent asset id for the current category
     * @param   Table   $table  A Table object for the asset parent.
     * @param   integer  $id     Id to look up
     * 
     * @return  int      The parent asset id for the category
     */
    protected function _getAssetParentId(Table $table = null, $id = null)
    {
        $assetId = null;
        $this->extension = 'com_jdownloads';
        
        // This is a category under a category.
        if ($this->parent_id > 1){
            // Build the query to get the asset id for the parent category.
            $query = $this->_db->getQuery(true)
                ->select($this->_db->quoteName('asset_id'))
                ->from($this->_db->quoteName('#__jdownloads_categories'))
                ->where($this->_db->quoteName('id') . ' = :parentId')
                ->bind(':parentId', $this->parent_id, ParameterType::INTEGER);

            // Get the asset id from the database.
            $this->_db->setQuery($query);

            if ($result = $this->_db->loadResult()){
                $assetId = (int) $result;
            }
        } elseif ($assetId === null){
            // This is a category that needs to parent with the extension.
            // Build the query to get the asset id for the parent category.
            $query = $this->_db->getQuery(true)
                ->select($this->_db->quoteName('id'))
                ->from($this->_db->quoteName('#__assets'))
                ->where($this->_db->quoteName('name') . ' = :extension')
                ->bind(':extension', $this->extension);

            // Get the asset id from the database.
            $this->_db->setQuery($query);

            if ($result = $this->_db->loadResult()){
                $assetId = (int) $result;
            }
        }

        // Return the asset id.
        if ($assetId){
            return $assetId;
        } else {
            return parent::_getAssetParentId($table, $id);
        }
    }
    
    /**
     * Method to set the publishing state for a row or list of rows in the database
     * table.  The method respects checked out rows by other users and will attempt
     * to checkin rows that it can after adjustments are made.
     *
     * @param   mixed    $pks     An optional array of primary key values to update.  If not set the instance property value is used.
     * @param   integer  $state   The publishing state. eg. [0 = unpublished, 1 = published]
     * @param   integer  $userId  The user id of the user performing the operation.
     *
     * @return  boolean  True on success.
     *
     */
    public function publish($pks = null, $state = 1, $userId = 0)
    {
        // Initialise variables.
        $k = $this->_tbl_key;
        $query = $this->_db->getQuery(true);

        // Sanitize input.
        $pks = ArrayHelper::toInteger($pks);
        $userId = (int) $userId;
        $state = (int) $state;
        
        // If $state > 1, then we allow state changes even if an ancestor has lower state
        // (for example, can change a child state to Archived (2) if an ancestor is Published (1) - not use in jD now
        $compareState = ($state > 1) ? 1 : $state;        

        // If there are no primary keys set check to see if the instance key is set.
        if (empty($pks)){
            if ($this->$k){
                $pks = explode(',', $this->$k);
            } else {
                // Nothing to set publishing state on, return false.
                $e = new JException(Text::_('JLIB_DATABASE_ERROR_NO_ROWS_SELECTED'));
                $this->setError($e);

                return false;
            }
        }
        
        // Determine if there is checkout support for the table.
        $checkoutSupport = (property_exists($this, 'checked_out') || property_exists($this, 'checked_out_time'));
        
        // Iterate over the primary keys to execute the publish action if possible.
        foreach ($pks as $pk){
            if (!$node = $this->_getNode($pk)){
                // Error message set in getNode method.
                return false;
            }
            
            // If the table has checkout support, verify no children are checked out.
            if ($checkoutSupport){
                // Ensure that children are not checked out.
                $query->clear()
                    ->select('COUNT(' . $k . ')')
                    ->from($this->_tbl)
                    ->where('lft BETWEEN ' . (int) $node->lft . ' AND ' . (int) $node->rgt)
                    ->where('(checked_out <> 0 AND checked_out <> ' . (int) $userId . ')');
                $this->_db->setQuery($query);

                // Check for checked out children.
                if ($this->_db->loadResult()){
                    // TODO Convert to a conflict exception when available.
                    $e = new RuntimeException(sprintf('%s::publish(%s, %d, %d) checked-out conflict.', get_class($this), $pks, $state, $userId));

                    $this->setError($e);
                    return false;
                }
            }

            // If any parent nodes have lower published state values, we cannot continue.
            if ($node->parent_id){
                // Get any ancestor nodes that have a lower publishing state.
                $query->clear()
                    ->select('n.' . $k)
                    ->from($this->_db->quoteName($this->_tbl) . ' AS n')
                    ->where('n.lft < ' . (int) $node->lft)
                    ->where('n.rgt > ' . (int) $node->rgt)
                    ->where('n.parent_id > 0')
                    ->where('n.published < ' . (int) $compareState);

                // Just fetch one row (one is one too many).
                $this->_db->setQuery($query, 0, 1);

                $rows = $this->_db->loadColumn();

                if (!empty($rows)){
                    $e = new \UnexpectedValueException(
                        sprintf('%s::publish(%s, %d, %d) ancestors have lower state.', get_class($this), $pks, $state, $userId)
                    );
                    $this->setError($e);
                    return false;
                }
            }
            
            // Update and cascade the publishing state.
            $query->clear()
                ->update($this->_db->quoteName($this->_tbl))
                ->set('published = ' . (int) $state)
                ->where('(lft > ' . (int) $node->lft . ' AND rgt < ' . (int) $node->rgt . ') OR ' . $k . ' = ' . (int) $pk);
                
            $this->_db->setQuery($query)->execute();              
            
            // If checkout support exists for the object, check the row in.
            if ($checkoutSupport){
                $this->checkin($pk);
            }
        }    

        // If the Table instance value is in the list of primary keys that were set, set the instance.
        if (in_array($this->$k, $pks)){
            $this->published = $state;
        }

        $this->setError('');
        return true;            
    }
    
    /**
     * Get the type alias for the history table
     *
     * @return  string  The alias as described above
     *
     * @since   4.0.0
     */
    public function getTypeAlias()
    {
        return 'com_jdownloads.category';
    }

}
?>