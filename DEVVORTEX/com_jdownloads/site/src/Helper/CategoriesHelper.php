<?php
/**
 * @package     Joomla.Platform
 * @subpackage  Application
 *
 * @copyright   Copyright (C) 2005 - 2013 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE
 */

namespace JDownloads\Component\JDownloads\Site\Helper;
 
\defined('JPATH_PLATFORM') or die;

use Joomla\CMS\Factory;
use Joomla\Registry\Registry;
use Joomla\CMS\Object\CMSObject;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Language\Multilanguage;
use Joomla\Database\ParameterType;
use JLoader;

use JDownloads\Component\JDownloads\Site\Helper\CategoryHelper;

/**
 * jDownloads Categories Helper Class.
 *
 * @package     Joomla.Platform
 * @subpackage  Application
 * @since       11.1
 */
class CategoriesHelper
{
	/**
	 * Array to hold the object instances
	 *
	 * @var    array
	 * @since  11.1
	 */
	public static $instances = array();

	/**
	 * Array of category nodes
	 *
	 * @var    mixed
	 * @since  11.1
	 */
	protected $_nodes;

	/**
	 * Array of checked categories -- used to save values when _nodes are null
	 *
	 * @var    array
	 * @since  11.1
	 */
	protected $_checkedCategories;

	/**
	 * Name of the extension the categories belong to
	 *
	 * @var    string
	 * @since  11.1
	 */
	protected $_extension = null;

	/**
	 * Name of the linked content table to get category content count
	 *
	 * @var    string
	 * @since  11.1
	 */
	protected $_table = null;

	/**
	 * Name of the category field
	 *
	 * @var    string
	 * @since  11.1
	 */
	protected $_field = null;

	/**
	 * Name of the key field
	 *
	 * @var    string
	 * @since  11.1
	 */
	protected $_key = null;

	/**
	 * Name of the items state field
	 *
	 * @var    string
	 * @since  11.1
	 */
	protected $_statefield = 'published';

	/**
	 * Array of options
	 *
	 * @var    array
	 * @since  11.1
	 */
	protected $_options = [];

	/**
	 * Class constructor
	 *
	 * @param   array  $options  Array of options
	 *
	 * @since   11.1
	 */
	public function __construct($options)
	{
		$this->_table = $options['table'];
		$this->_field = (isset($options['field']) && $options['field']) ? $options['field'] : 'catid';
		$this->_key = (isset($options['key']) && $options['key']) ? $options['key'] : 'id';
		$this->_statefield = (isset($options['statefield'])) ? $options['statefield'] : 'published';
		
        $options['access'] = (isset($options['access'])) ? $options['access'] : 'true';
		$options['published'] = (isset($options['published'])) ? $options['published'] : 1;
        $options['currentlang'] = Multilanguage::isEnabled() ? Factory::getLanguage()->getTag() : 0;
		$options['countItems']  = isset($options['countItems']) ? $options['countItems'] : 1;
        
        $this->_options = $options;

	}

	/**
	 * Returns a reference to a jDownloads Categories object
	 *
	 * @param   string  $extension  Name of the categories extension
	 * @param   array   $options    An array of options
	 *
	 * @return  Categories         Categories object
	 *
	 * @since   11.1
	 */
	public static function getInstance($extension, $options = array())
	{
		$hash = md5(strtolower($extension) . serialize($options));

		if (isset(self::$instances[$hash])){
			return self::$instances[$hash];
		}

		$classname = 'JDownloads\Component\JDownloads\Site\Helper\CategoryHelper';

		if (!class_exists($classname)){
			$path = JPATH_SITE . '/components/com_jdownloads/src/Helper/CategoryHelper.php';
			if (is_file($path)){
				include_once $path;
                \JLoader::register($classname, $path);
			} else {
				return false;
			}
		}

		self::$instances[$hash] = new $classname($options);

		return self::$instances[$hash];
    
	} 

	/**
	 * Loads a specific category and all its children in a JDCategoryNode object
	 *
	 * @param   mixed    $id         an optional id integer or equal to 'root'
	 * @param   boolean  $forceload  True to force  the _load method to execute
	 *
	 * @return  mixed    JDCategoryNode object or null if $id is not valid
	 *
	 * @since   11.1
	 */
	public function get($id = 'root', $forceload = false)
	{
		if ($id !== 'root'){
			$id = (int) $id;

			if ($id == 0){
				$id = 'root';
			}
		}

		// If this $id has not been processed yet, execute the _load method
		if ((!isset($this->_nodes[$id]) && !isset($this->_checkedCategories[$id])) || $forceload){
			$this->_load($id);
		}

		// If we already have a value in _nodes for this $id, then use it.
		if (isset($this->_nodes[$id])){
			return $this->_nodes[$id];
		}
		
		return null;
	}

	/**
	 * Load method
	 *
	 * @param   integer  $id  Id of category to load
	 *
	 * @return  void
	 *
	 * @since   11.1
	 */
	protected function _load($id)
	{
        $db   = Factory::getDbo();
        $app  = Factory::getApplication();
        $user = $app->getIdentity();
        
        $params = ComponentHelper::getParams('com_jdownloads');
        
        $user->authorise('core.admin') ? $is_admin = true : $is_admin = false;
        
        $groups  = implode (',', $user->getAuthorisedViewLevels());
        
        // Define now dates to count correct amount numitems
        $nowDate = $db->Quote(Factory::getDate()->toSql()); // True to return the date string in the local time zone, false to return it in GMT.        
        
        // We need later this value to know what we must compute and show
        $show_empty_categories = $params->get('view_empty_categories', 1);

        if ($id !== 'root'){
            $id = (int) $id;

            if ($id === 0){
                $id = 'root';
            }
        }
        
        // Record that has this $id has been checked
        $this->_checkedCategories[$id] = true;

        $db->setQuery('SET SESSION SQL_BIG_SELECTS = 1');
        $db->execute();

        $query = $db->getQuery(true);
        
        // Right join with c for category
        $query->select('c.*');
        $case_when = ' CASE WHEN ';
        $case_when .= $query->charLength($db->quoteName('c.alias'), '!=', '0');
        $case_when .= ' THEN ';
        $c_id = $query->castAsChar($db->quoteName('c.id'));
        $case_when .= $query->concatenate(array($c_id, $db->quoteName('c.alias')), ':');
        $case_when .= ' ELSE ';
        $case_when .= $c_id . ' END as slug';
        $query->select($case_when);

        //$query->from('#__jdownloads_categories as c');
        
        if (isset($this->_options['category_id'])){
            if ($this->_options['category_id']){
                $query->where($this->_options['category_id']);
            }
        }
        
        if (isset($this->_options['level'])){
            if ($this->_options['level']){
                $query->where('c.level <= '. $db->Quote($this->_options['level']));
            }
        }        

        if ($this->_options['access']){
            $query->where('c.access IN (' . $groups . ')');
        }

        if ($this->_options['published'] == 1){
            $query->where('c.published = 1');
        }

        if (isset($this->_options['ordering']) && $this->_options['ordering'] != ''){
            if ($this->_options['direction'] != ''){
                $query->order($this->_options['ordering'].' '.$this->_options['direction']);
            } else {
                $query->order($this->_options['ordering']);     
            }     
        } else {
           $query->order('c.lft'); 
        }
        
        // Note: s for selected id (from Category)
        if ($id !== 'root') {
            // Get the selected category
            $query->from($db->quoteName('#__jdownloads_categories', 's'))
                ->where($db->quoteName('s.id') . ' = c.id');

            if ($app->isClient('site') && Multilanguage::isEnabled()) {
                // For the most part, we use c.lft column, which index is properly used instead of c.rgt
                $query->join(
                    'INNER',
                    $db->quoteName('#__jdownloads_categories', 'c'),
                    '(' . $db->quoteName('s.lft') . ' < ' . $db->quoteName('c.lft')
                        . ' AND ' . $db->quoteName('c.lft') . ' < ' . $db->quoteName('s.rgt')
                        . ' AND ' . $db->quoteName('c.language')
                        . ' IN (' . implode(',', $query->bindArray([Factory::getLanguage()->getTag(), '*'], ParameterType::STRING)) . '))'
                        . ' OR (' . $db->quoteName('c.lft') . ' <= ' . $db->quoteName('s.lft')
                        . ' AND ' . $db->quoteName('s.rgt') . ' <= ' . $db->quoteName('c.rgt') . ')'
                );
            } else {
                $query->join(
                    'INNER',
                    $db->quoteName('#__jdownloads_categories', 'c'),
                    '(' . $db->quoteName('s.lft') . ' <= ' . $db->quoteName('c.lft')
                        . ' AND ' . $db->quoteName('c.lft') . ' < ' . $db->quoteName('s.rgt') . ')'
                        . ' OR (' . $db->quoteName('c.lft') . ' < ' . $db->quoteName('s.lft')
                        . ' AND ' . $db->quoteName('s.rgt') . ' < ' . $db->quoteName('c.rgt') . ')'
                );
            }
        } else {
            $query->from($db->quoteName('#__jdownloads_categories', 'c'));

            if ($app->isClient('site') && Multilanguage::isEnabled()) {
                $query->whereIn($db->quoteName('c.language'), [Factory::getLanguage()->getTag(), '*'], ParameterType::STRING);
            }
        }

        // Get the number of files per category
        // Note: 'files' for items (downloads table)
        if ($this->_options['countItems'] == 1) {
            $subQuery = $db->getQuery(true)
                ->select('COUNT(' . $db->quoteName($db->escape('files.' . $this->_key)) . ')')
                ->from($db->quoteName($db->escape($this->_table), 'files'))
                ->where($db->quoteName($db->escape('files.' . $this->_field)) . ' = ' . $db->quoteName('c.id'));

            if ($this->_options['published'] == 1) {
                $subQuery->where($db->quoteName($db->escape('files.' . $this->_statefield)) . ' = 1');
            }
            
            if ($this->_options['published'] == 1){
                                    
                if ($user->id > 0){
                    // User is not a guest so we can generally use the user-id to find also the Downloads with single user access
                    if ($is_admin){
                        // User is admin so we should display all possible Downloads - included the Downloads with single user access 
                        $subQuery->where('((files.access IN ('.$groups.') AND files.user_access = 0) OR (files.access != 0 AND files.user_access != 0))');
                    } else {
                        $subQuery->where('((files.access IN ('.$groups.') AND files.user_access = 0) OR (files.access != 0 AND files.user_access = '.$db->quote($user->id). '))');
                    }
                } else {    
                    // Filter by access
                    $subQuery->where('files.access IN ('.$groups.') AND files.user_access = 0');
                }
                
                // Filter by published and catid
                //$subQuery->where('files.catid IN ('.$ids.') AND files.published = 1');
                
                // Filter by start and end dates
                $subQuery->where('(files.publish_up IS NULL OR files.publish_up <= ' . $nowDate . ')');
                $subQuery->where('(files.publish_down IS NULL OR files.publish_down >= ' . $nowDate . ')');
                
            } else {
                
                if ($user->id > 0){
                    // User is not a guest so we can generally use the user-id to find also the Downloads with single user access
                    if ($is_admin){
                        // User is admin so we should display all possible Downloads - included the Downloads with single user access 
                        $subQuery->where('((files.access IN ('.$groups.') AND files.user_access = 0) OR (files.access != 0 AND files.user_access != 0))');
                    } else {
                        $subQuery->where('((files.access IN ('.$groups.') AND files.user_access = 0) OR (files.access != 0 AND files.user_access = '.$db->quote($user->id). '))');
                    }
                } else {    
                    $subQuery->where('files.access IN ('.$groups.') AND files.user_access = 0');
                }
                
                //$subQuery->where('files.catid IN ('.$ids.')');
                
            }
            
            if ($this->_options['currentlang'] !== 0) {
                $subQuery->where(
                    $db->quoteName('files.language')
                    . ' IN (' . implode(',', $query->bindArray([$this->_options['currentlang'], '*'], ParameterType::STRING)) . ')'
                );
            }
            
            $query->select('(' . $subQuery . ') AS ' . $db->quoteName('numitems'));
        }
        
        // Get for every category the number of sub categories (childrens)
        // Note: 'cat' for categories
        $subQueryCat = $db->getQuery(true)
            ->select('COUNT(*)')
            ->from($db->quoteName('#__jdownloads_categories', 'cat'))
            ->where($db->quoteName($db->escape('cat.parent_id')) . ' = ' . $db->quoteName('c.id'));
            
        if ($this->_options['published'] == 1){
            $subQueryCat->where('cat.parent_id > 1 AND cat.published = 1 AND cat.access IN (' . $groups . ')');
        } else {
            $subQueryCat->where('cat.parent_id > 1 AND cat.access IN (' . $groups . ')');
        }
        
        if ($this->_options['currentlang'] !== 0) {
            $subQueryCat->where(
                $db->quoteName('cat.language')
                . ' IN (' . implode(',', $query->bindArray([$this->_options['currentlang'], '*'], ParameterType::STRING)) . ')'
                );
        }
        
        $query->select('(' . $subQueryCat . ') AS ' . $db->quoteName('subcatitems'));
    
        // Check publishing option
        if ($this->_options['published'] == 1){
            $query->leftJoin($db->quoteName($this->_table) . ' AS files ON files.' . $db->quoteName($this->_field) . ' = c.id AND files.' . $this->_statefield . ' = 1');
        } else {
            $query->leftJoin($db->quoteName($this->_table) . ' AS files ON files.' . $db->quoteName($this->_field) . ' = c.id');
        }
        
        // Join on user table.
        if ($params->get('use_real_user_name_in_frontend')){
            $query->select('u.name AS creator');
        } else {
            $query->select('u.username AS creator');
        }    
        $query->join('LEFT', '#__users AS u on u.id = files.created_by');
        
        if ($params->get('use_real_user_name_in_frontend')){
            $query->select('u2.name AS modifier');
        } else {
            $query->select('u2.username AS modifier');
        } 
        $query->join('LEFT', '#__users AS u2 on u2.id = files.modified_by');
                                                                                             
        $query->select('menu.id AS menu_itemid');
        $query->join('LEFT', '(SELECT id, link, access, published from #__menu GROUP BY link) AS menu on menu.link LIKE CONCAT(\'index.php?option=com_jdownloads&view=category&catid=\', c.id) AND menu.published = 1 AND menu.access IN (' . implode(',', $user->getAuthorisedViewLevels()) . ')') ;
        
        // Group by
        $query->group('c.id, c.cat_dir, c.cat_dir_parent, c.parent_id, c.lft, c.rgt, c.level, c.title, c.alias, c.access');

        // Get the results
        $db->setQuery($query);
        $cats = $db->loadObjectList('id');
        $childrenLoaded = false;
        
        if (\count($cats))
        {  
            
            // Foreach categories
            foreach ($cats as $cat)
            {    
                // Deal with root category
                if ($cat->id == 1){
                    $cat->id = 'root';
                }

                // Deal with parent_id
                if ($cat->parent_id == 1){
                    $cat->parent_id = 'root';
                }

                // Do here only the job for the root ID, as we need the data from the root ID always in the first position (for every sort order)
                // This is not a very elegant solution but it works - we can rework it in a later release
                if ($cat->id == 'root'){
                    
                    // Create the node
                    if (!isset($this->_nodes[$cat->id])){
                        // Create the JDCategoryNode and add to _nodes
                        $this->_nodes[$cat->id] = new JDCategoryNode($cat, $this);

                        // If this is not root and if the current node's parent is in the list or the current node parent is 0
                        if ($cat->id !== 'root' && (isset($this->_nodes[$cat->parent_id]) || $cat->parent_id == 1)){
                            // Compute relationship between node and its parent - set the parent in the _nodes field
                            $this->_nodes[$cat->id]->setParent($this->_nodes[$cat->parent_id]);
                        }

                        // If the node's parent id is not in the _nodes list and the node is not root (doesn't have parent_id == 0),
                        // then remove the node from the list
                        if (!(isset($this->_nodes[$cat->parent_id]) || $cat->parent_id == 0)){                   // $cat->parent_id != 'root' &&
                            unset($this->_nodes[$cat->id]);
                            continue;
                        }

                        if ($cat->id == $id || $childrenLoaded){
                            $this->_nodes[$cat->id]->setAllLoaded();
                            $childrenLoaded = true;
                        }
                    } elseif ($cat->id == $id || $childrenLoaded){
                        // Create the JDCategoryNode
                        $this->_nodes[$cat->id] = new JDCategoryNode($cat, $this);

                        if ($cat->id !== 'root' && (isset($this->_nodes[$cat->parent_id]) || $cat->parent_id)){
                            // Compute relationship between node and its parent
                            $this->_nodes[$cat->id]->setParent($this->_nodes[$cat->parent_id]);
                        }

                        if (!isset($this->_nodes[$cat->parent_id])){
                            unset($this->_nodes[$cat->id]);
                            continue;
                        }

                        if ($cat->id == $id || $childrenLoaded){
                            $this->_nodes[$cat->id]->setAllLoaded();
                            $childrenLoaded = true;
                        }
                    }
                }
            }
            
            // Do now here only the job for all the other categories 
            foreach ($cats as $cat){
                // Do here nothing with root ID (see above)
                if ($cat->id == 1 || $cat->id == 'root'){
                    continue;
                }

                // Deal with parent_id
                if ($cat->parent_id == 1){
                    $cat->parent_id = 'root';
                }

                // Create the node
                if (!isset($this->_nodes[$cat->id])){
                    // Create the JDCategoryNode and add to _nodes
                    $this->_nodes[$cat->id] = new JDCategoryNode($cat, $this);

                    // If this is not root and if the current node's parent is in the list or the current node parent is 0
                    if ($cat->id != 'root' && (isset($this->_nodes[$cat->parent_id]) || $cat->parent_id == 1)){
                        // Compute relationship between node and its parent - set the parent in the _nodes field
                        $this->_nodes[$cat->id]->setParent($this->_nodes[$cat->parent_id]);
                    }

                    // If the node's parent id is not in the _nodes list and the node is not root (doesn't have parent_id == 0),
                    // then remove the node from the list
                    if (!(isset($this->_nodes[$cat->parent_id]) || $cat->parent_id == 0)){                   // $cat->parent_id != 'root' &&
                        unset($this->_nodes[$cat->id]);
                        continue;
                    }

                    if ($cat->id == $id || $childrenLoaded){
                        $this->_nodes[$cat->id]->setAllLoaded();
                        $childrenLoaded = true;
                    }
                } elseif ($cat->id == $id || $childrenLoaded){
                    // Create the JDCategoryNode
                    $this->_nodes[$cat->id] = new JDCategoryNode($cat, $this);

                    if ($cat->id != 'root' && (isset($this->_nodes[$cat->parent_id]) || $cat->parent_id)){
                        // Compute relationship between node and its parent
                        $this->_nodes[$cat->id]->setParent($this->_nodes[$cat->parent_id]);
                    }

                    if (!isset($this->_nodes[$cat->parent_id])){
                        unset($this->_nodes[$cat->id]);
                        continue;
                    }

                    if ($cat->id == $id || $childrenLoaded){
                        $this->_nodes[$cat->id]->setAllLoaded();
                        $childrenLoaded = true;
                    }
                }
            }            
        } else {
            $this->_nodes[$id] = null;
        }
    }
}

/**
 * Helper class to load Categorytree
 *
 * @package     Joomla.Platform
 * @subpackage  Application
 * @since       11.1
 */
class JDCategoryNode extends CMSObject
{

	/**
	 * Primary key
	 *
	 * @var    integer
	 * @since  11.1
	 */
	public $id = null;

	/**
	 * The id of the category in the asset table
	 *
	 * @var    integer
	 * @since  11.1
	 */
	public $asset_id = null;

	/**
	 * The id of the parent of category in the asset table, 0 for category root
	 *
	 * @var    integer
	 * @since  11.1
	 */
	public $parent_id = null;

	/**
	 * The lft value for this category in the category tree
	 *
	 * @var    integer
	 * @since  11.1
	 */
	public $lft = null;

	/**
	 * The rgt value for this category in the category tree
	 *
	 * @var    integer
	 * @since  11.1
	 */
	public $rgt = null;

	/**
	 * The depth of this category's position in the category tree
	 *
	 * @var    integer
	 * @since  11.1
	 */
	public $level = null;

	/**
	 * The extension this category is associated with
	 *
	 * @var    integer
	 * @since  11.1
	 */
	public $extension = null;

	/**
	 * The menu title for the category (a short name)
	 *
	 * @var string
	 * @since  11.1
	 */
	public $title = null;

	/**
	 * The the alias for the category
	 *
	 * @var    string
	 * @since  11.1
	 */
	public $alias = null;

	/**
	 * Description of the category.
	 *
	 * @var string
	 * @since  11.1
	 */
	public $description = null;

	/**
	 * The publication status of the category
	 *
	 * @var    boolean
	 * @since  11.1
	 */
	public $published = null;

	/**
	 * Whether the category is or is not checked out
	 *
	 * @var boolean
	 * @since  11.1
	 */
	public $checked_out = 0;

	/**
	 * The time at which the category was checked out
	 *
	 * @var    time
	 * @since  11.1
	 */
	public $checked_out_time = 0;

	/**
	 * Access level for the category
	 *
	 * @var integer
	 * @since  11.1
	 */
	public $access = null;

	/**
	 * JSON string of parameters
	 *
	 * @var string
	 * @since  11.1
	 */
	public $params = null;

	/**
	 * Metadata description
	 *
	 * @var string
	 * @since  11.1
	 */
	public $metadesc = null;

	/**
	 * Key words for meta data
	 *
	 * @var string
	 * @since  11.1
	 */
	public $metakey = null;

	/**
	 * JSON string of other meta data
	 *
	 * @var string
	 * @since  11.1
	 */
	public $robots = null;

	public $created_user_id = null;

	/**
	 * The time at which the category was created
	 *
	 * @var    time
	 * @since  11.1
	 */
	public $created_time = null;

	public $modified_user_id = null;

	/**
	 * The time at which the category was modified
	 *
	 * @var    time
	 * @since  11.1
	 */
	public $modified_time = null;

	/**
	 * Nmber of times the category has been viewed
	 *
	 * @var    integer
	 * @since  11.1
	 */
	public $hits = null;

	/**
	 * The language for the category in xx-XX format
	 *
	 * @var    time
	 * @since  11.1
	 */
	public $language = null;

	/**
	 * Number of items in this category or descendants of this category
	 *
	 * @var    integer
	 * @since  11.1
	 */
	public $numitems = null;

	/**
	 * Number of children items
	 *
	 * @var
	 * @since  11.1
	 */
	public $childrennumitems = null;

	/**
	 * Slug fo the category (used in URL)
	 *
	 * @var    string
	 * @since  11.1
	 */
	public $slug = null;

	/**
	 * Array of  assets
	 *
	 * @var    array
	 * @since  11.1
	 */
	public $assets = null;

	/**
	 * Parent Category object
	 *
	 * @var    object
	 * @since  11.1
	 */
	protected $_parent = null;

	/**
	 * @var Array of Children
	 * @since  11.1
	 */
	protected $_children = array();

	/**
	 * Path from root to this category
	 *
	 * @var    array
	 * @since  11.1
	 */
	protected $_path = array();

	/**
	 * Category left of this one
	 *
	 * @var    integer
	 * @since  11.1
	 */
	protected $_leftSibling = null;

	/**
	 * Category right of this one
	 *
	 * @var
	 * @since  11.1
	 */
	protected $_rightSibling = null;

	/**
	 * true if all children have been loaded
	 *
	 * @var boolean
	 * @since  11.1
	 */
	protected $_allChildrenloaded = false;

	/**
	 * Constructor of this tree
	 *
	 * @var
	 * @since  11.1
	 */
	protected $_constructor = null;

	/**
	 * Class constructor
	 *
	 * @param   array          $category      The category data.
	 * @param   JDCategoryNode  &$constructor  The tree constructor.
	 *
	 * @since   11.1
	 */
	public function __construct($category = null, &$constructor = null)
	{
		if ($category){
			$this->setProperties($category);
			if ($constructor){
				$this->_constructor = &$constructor;
			}
			return true;
		}
		return false;
	}

	/**
	 * Set the parent of this category
	 *
	 * If the category already has a parent, the link is unset
	 *
	 * @param   mixed  &$parent  JDCategoryNode for the parent to be set or null
	 *
	 * @return  void
	 *
	 * @since   11.1
	 */
	public function setParent(&$parent)
	{
		if ($parent instanceof JDCategoryNode || is_null($parent)){
			if (!is_null($this->_parent)){
				$key = array_search($this, $this->_parent->_children);
				unset($this->_parent->_children[$key]);
			}

			if (!is_null($parent)){
				$parent->_children[] = & $this;
			}

			$this->_parent = & $parent;

			if ($this->id != 'root'){
				if ($this->parent_id != 1){
					$this->_path = $parent->getPath();
				}
				$this->_path[] = $this->id . ':' . $this->alias;
			}

			if (count($parent->_children) > 1){
				end($parent->_children);
				$this->_leftSibling = prev($parent->_children);
				$this->_leftSibling->_rightsibling = &$this;
			}
		}
	}

	/**
	 * Add child to this node
	 *
	 * If the child already has a parent, the link is unset
	 *
	 * @param   JNode  &$child  The child to be added.
	 *
	 * @return  void
	 *
	 * @since   11.1
	 */
	public function addChild(&$child)
	{
		if ($child instanceof JDCategoryNode){
			$child->setParent($this);
		}
	}

	/**
	 * Remove a specific child
	 *
	 * @param   integer  $id  ID of a category
	 *
	 * @return  void
	 *
	 * @since   11.1
	 */
	public function removeChild($id)
	{
		$key = array_search($this, $this->_parent->_children);
		unset($this->_parent->_children[$key]);
	}

	/**
	 * Get the children of this node
	 *
	 * @param   boolean  $recursive  False by default
	 *
	 * @return  array  The children
	 *
	 * @since   11.1
	 */
	public function &getChildren($recursive = false)
	{
		if (!$this->_allChildrenloaded){
			$temp = $this->_constructor->get($this->id, true);
			if ($temp){
				$this->_children = $temp->getChildren();
				$this->_leftSibling = $temp->getSibling(false);
				$this->_rightSibling = $temp->getSibling(true);
				$this->setAllLoaded();
			}
		}

		if ($recursive){
			$items = array();
			foreach ($this->_children as $child){
				$items[] = $child;
				$items = array_merge($items, $child->getChildren(true));
			}
			return $items;
		}

		return $this->_children;
	}

	/**
	 * Get the parent of this node
	 *
	 * @return  mixed  JNode or null
	 *
	 * @since   11.1
	 */
	public function &getParent()
	{
		return $this->_parent;
	}

	/**
	 * Test if this node has children
	 *
	 * @return  boolean  True if there is a child
	 *
	 * @since   11.1
	 */
	public function hasChildren()
	{
		return count($this->_children);
	}

	/**
	 * Test if this node has a parent
	 *
	 * @return  boolean    True if there is a parent
	 *
	 * @since   11.1
	 */
	public function hasParent()
	{
		return $this->getParent() != null;
	}

	/**
	 * Function to set the left or right sibling of a category
	 *
	 * @param   object   $sibling  JDCategoryNode object for the sibling
	 * @param   boolean  $right    If set to false, the sibling is the left one
	 *
	 * @return  void
	 *
	 * @since   11.1
	 */
	public function setSibling($sibling, $right = true)
	{
		if ($right){
			$this->_rightSibling = $sibling;
		} else {
			$this->_leftSibling = $sibling;
		}
	}

	/**
	 * Returns the right or left sibling of a category
	 *
	 * @param   boolean  $right  If set to false, returns the left sibling
	 *
	 * @return  mixed  JDCategoryNode object with the sibling information or
	 *                 NULL if there is no sibling on that side.
	 *
	 * @since   11.1
	 */
	public function getSibling($right = true)
	{
		if (!$this->_allChildrenloaded){
			$temp = $this->_constructor->get($this->id, true);
			$this->_children = $temp->getChildren();
			$this->_leftSibling = $temp->getSibling(false);
			$this->_rightSibling = $temp->getSibling(true);
			$this->setAllLoaded();
		}

		if ($right){
			return $this->_rightSibling;
		} else {
			return $this->_leftSibling;
		}
	}

	/**
	 * Returns the category parameters
	 *
	 * @return  JRegistry
	 *
	 * @since   11.1
	 */
	public function getParams()
	{
		if (!($this->params instanceof JRegistry)){
			$temp = new Registry;
			$temp->loadString($this->params);
			$this->params = $temp;
		}

		return $this->params;
	}

	/**
	 * Returns the category metadata
	 *
	 * @return  JRegistry  A JRegistry object containing the metadata
	 *
	 * @since   11.1
	 */
	public function getMetadata()
	{
		if (!($this->metadata instanceof JRegistry)){
			$temp = new Registry;
			$temp->loadString($this->metadata);
			$this->metadata = $temp;
		}

		return $this->metadata;
	}

	/**
	 * Returns the category path to the root category
	 *
	 * @return  array
	 *
	 * @since   11.1
	 */
	public function getPath()
	{
		return $this->_path;
	}

	/**
	 * Returns the user that created the category
	 *
	 * @param   boolean  $modified_user  Returns the modified_user when set to true
	 *
	 * @return  JUser  A JUser object containing a userid
	 *
	 * @since   11.1
	 */
	public function getAuthor($modified_user = false)
	{
		if ($modified_user){
			return Factory::getUser($this->modified_by);
		}

		return Factory::getUser($this->created_by);
	}

	/**
	 * Set to load all children
	 *
	 * @return  void
	 *
	 * @since 11.1
	 */
	public function setAllLoaded()
	{
		$this->_allChildrenloaded = true;
		foreach ($this->_children as $child){
			$child->setAllLoaded();
		}
	}

	/**
	 * Returns the number of items.
	 *
	 * @param   boolean  $recursive  If false number of children, if true number of descendants
	 *
	 * @return  integer  Number of children or descendants
	 *
	 * @since 11.1
	 */
	public function getNumItems($recursive = false)
	{
		if ($recursive){
			$count = $this->numitems;

			foreach ($this->getChildren() as $child){
				$count = $count + $child->getNumItems(true);
			}

			return $count;
		}

		return $this->numitems;
	}
}
