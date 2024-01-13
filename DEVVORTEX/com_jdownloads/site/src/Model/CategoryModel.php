<?php
/**
 * @package jDownloads
 * @version 4.0  
 * @copyright (C) 2007 - 2022 by Arno Betz - www.jdownloads.com
 * @license http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.txt
 * 
 * jDownloads is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 */

namespace JDownloads\Component\JDownloads\Site\Model;

\defined('_JEXEC') or die;

use Joomla\CMS\Categories\CategoryNode;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Multilanguage;
use Joomla\CMS\MVC\Model\ListModel;
use Joomla\CMS\Table\Table;
use Joomla\Registry\Registry;
use Joomla\Utilities\ArrayHelper;
use Joomla\CMS\Helper\TagsHelper;

use JDownloads\Component\JDownloads\Administrator\Extension\JDownloadsComponent;
use JDownloads\Component\JDownloads\Site\Helper\QueryHelper;
use JDownloads\Component\JDownloads\Site\Helper\CategoriesHelper;
use JDownloads\Component\JDownloads\Site\Helper\CategoryHelper;

/**
 * This models supports retrieving a category, the Downloads associated with the category,
 * sibling, child and parent categories.
 */
class CategoryModel extends ListModel
{
	/**
	 * Category items data
	 *
	 * @var array
	 */
	protected $_item = null;
	protected $_downloads = null;
	protected $_siblings = null;
	protected $_children = null;
	protected $_parent = null;

	/**
	 * Model context string.
	 *
	 * @var		string
	 */
	protected $_context = 'com_jdownloads.category';

	/**
	 * The category that applies.
	 *
	 * @access	protected
	 * @var		object
	 */
	protected $_category = null;

	/**
	 * The list of other newfeed categories.
	 *
	 * @access	protected
	 * @var		array
	 */
	protected $_categories = null;

	/**
	 * Constructor.
	 *
	 * @param	array	An optional associative array of configuration settings.
	 * @see		JController
	 */
	public function __construct($config = array())
	{
		if (empty($config['filter_fields'])) {
			$config['filter_fields'] = array(
				'id', 'a.id',
				'title', 'a.title',
				'alias', 'a.alias',
                'description, a.description',
                'description_long', 'a.description_long',
                'file_pic', 'a.file_pic',
                'images', 'a.images',
                'price', 'a.price',
                'release', 'a.release',
                'file_language', 'a.file_language',
                'system', 'a.system',
                'license', 'a.license',
                'url_license', 'a.url_license',
                'size', 'a.size',
                'created', 'a.created',
                'file_date', 'a.file_date',
                'publish_up', 'a.publish_up',
                'publish_down', 'a.publish_down',                
                'use_timeframe', 'a.use_timeframe',
                'url_download', 'a.url_download',
                'preview_filename', 'a.preview_filename',
                'other_file_id', 'a.other_file_id',
                'md5_value', 'a.md5_value',
                'sha1_value', 'a.sha1_value',
                'extern_file', 'a.extern_file', 
                'extern_site', 'a.extern_site', 
                'mirror_1', 'a.mirror_1', 
                'mirror_2', 'a.mirror_2', 
                'extern_site_mirror-1', 'a.extern_site_mirror_1',
                'extern_site_mirror_2', 'a.extern_site_mirror_2',
                'url_home', 'a.url_home',
                'author', 'a.author',
                'url_author', 'a.url_author',
                'created_by', 'a.created_by',
                'created_mail', 'a.created_mail',
                'modified_by', 'a.modified_by',
                'modified', 'a.modified',
                'submitted_by', 'a.submitted_by',
                'set_aup_points', 'a.set_aup_points',
                'downloads', 'a.downloads',
                'catid', 'a.catid', 'category_title',
                'changelog', 'a.changelog',
                'password', 'a.password',
                'password_md5', 'a.password_md5',
                'views', 'a.views',
                'metakey', 'a.metakey',
                'metadesc', 'a.metadesc',
                'robots', 'a.robots',
                'update_active', 'a.update_active',
                'access', 'a.access', 'access_level',                
                'user_access', 'a.user_access',                
                'language', 'a.language',
                'ordering', 'a.ordering',
                'featured', 'a.featured',                                
                'published', 'a.published',
				'checked_out', 'a.checked_out',
				'checked_out_time', 'a.checked_out_time'
			);
		}

		parent::__construct($config);
	}

	/**
	 * Method to auto-populate the model state.
	 * Note. Calling getState in this method will result in recursion.
	 * return	void
	 */
	protected function populateState($ordering = null, $direction = null)
	{
        $app    = Factory::getApplication();
        $jinput = Factory::getApplication()->input;
        $pk     = $app->input->getInt('catid');
        
		$this->setState('category.id', $pk);

		// Load the parameters. Merge Global and Menu Item params into new object
        $params = $app->getParams();

        if ($menu = $app->getMenu()->getActive())
        {
            $menuParams = $menu->getParams();
        }
        else
        {
            $menuParams = new Registry;
        }

        $mergedParams = clone $menuParams;
        $mergedParams->merge($params);
        $this->setState('params', $mergedParams);
        
		$user		= Factory::getUser();
				
        // Create a new query object.
		$db		= $this->getDbo();
		$query	= $db->getQuery(true);
		$groups	= implode(',', $user->getAuthorisedViewLevels());
        $menu_params = $this->state->params;

		// Filter by start and end dates.
        if ((!$user->authorise('core.edit.state', 'com_jdownloads')) &&  (!$user->authorise('core.edit', 'com_jdownloads'))){
			
            // Limit to published for people who can't edit or edit.state.
			$this->setState('filter.published', 1);
			
			// Define now dates to count correct amount numitems
            $nowDate = $db->Quote(Factory::getDate()->toSql()); // True to return the date string in the local time zone, false to return it in GMT.        

            $query->where('(a.publish_up IS NULL OR a.publish_up <= ' . $nowDate . ')');
            $query->where('(a.publish_down IS NULL OR a.publish_down >= ' . $nowDate . ')');
		} else {
			$this->setState('filter.published', array(0, 1));
		}

		$this->setState('filter.access', true);
        $this->setState('filter.user_access', true);
    
		// Optional filter text
		$this->setState('list.filter', $jinput->get('filter-search', '', 'STRING'));

		// Filter.order
		$itemid = $jinput->get('catid', '0', 'INT') . ':' . $jinput->get('Itemid', '0', 'INT');
		$orderCol = $app->getUserStateFromRequest('com_jdownloads.category.' . $itemid . '.filter_order', 'filter_order', '', 'string');
        
        $listOrderNew = '';
        
        if (!in_array($orderCol, $this->filter_fields) || $orderCol == '') {
			// Use default sort order or menu order settings
            if ($menu_params->get('orderby_sec') == ''){
                // Use config settings
                switch ($params->get('files_order')){
                    case '0':
                         // Files ordering field
                         $orderCol = 'a.ordering';
                         $listOrderNew = 'ASC';
                         break;
                    case '1':
                         // Files date_added desc 
                         $orderCol = 'a.created'; // desc
                         $listOrderNew = 'DESC';
                         break;
                    case '2':
                         // Files date_added asc 
                         $orderCol = 'a.created'; // asc
                         $listOrderNew = 'ASC';
                         break;
                    case '3':
                         // Files title field asc 
                         $orderCol = 'a.title';
                         $listOrderNew = 'ASC';
                         break;
                    case '4':
                         // Files title field desc 
                         $orderCol = 'a.title';
                         $listOrderNew = 'DESC';
                         break;
                    case '5':
                         // Files hits/downloads field desc
                         $orderCol = 'a.downloads';
                         $listOrderNew = 'DESC';
                         break;
                    case '6':
                         // Files hits/downloads field asc
                         $orderCol = 'a.downloads';
                         $listOrderNew = 'ASC';
                         break;                         
                    case '7':
                         // Author title field asc 
                         $orderCol = 'a.author';
                         $listOrderNew = 'ASC';
                         break;
                    case '8':
                         // Author title field desc 
                         $orderCol = 'a.author';
                         $listOrderNew = 'DESC';
                         break;                         
                    case '9':
                         // Author title field desc 
                         $orderCol = 'a.featured';
                         $listOrderNew = 'DESC';
                         break;                         
                }
		    }  else {
                // Use order from menu settings 
                $filesOrderby = $params->get('orderby_sec', 'order');
                $orderCol    = QueryHelper::orderbySecondary($filesOrderby) . ' ';
                $order_array  = explode(' ', $orderCol);
                if (count($order_array) > 2){
                    $orderCol       = $order_array[0];
                    $listOrderNew   = $order_array[1];
                }
            }    
        }
		$this->setState('list.ordering', $orderCol);

		$listOrder = $app->getUserStateFromRequest('com_jdownloads.category.' . $itemid . '.filter_order_Dir', 'filter_order_Dir', '', 'cmd');
		if (!in_array(strtoupper($listOrder), array('ASC', 'DESC', ''))) {
			$listOrder = 'ASC';
		}
		if (!$listOrderNew){
            $this->setState('list.direction', $listOrder);
        } else {
            $this->setState('list.direction', $listOrderNew);
        }    

		$this->setState('list.start', $jinput->get('limitstart', '0', 'UINT'));

        $limit = $app->getUserStateFromRequest('com_jdownloads.category.' . $itemid . '.limit', 'limit',  '', 'uint');
		if (!$limit){
            if ((int)$menu_params->get('display_num') > 0) {
                $limit = (int)$menu_params->get('display_num');
            } else {
                $limit = (int)$params->get('files_per_side');
            }
        }
        
        $this->setState('list.limit', $limit);

		$this->setState('filter.language', $app->getLanguageFilter());

		$this->setState('layout', $jinput->get('layout'));
        
        // Set the featured Downloads state
        $this->setState('filter.featured', $params->get('show_featured'));        

	}

	/**
	 * Get the downloads in the category
	 *
	 * @return	mixed	An array of downloads or false if an error occurs.
	 */
	function getItems()
	{
		$limit = $this->getState('list.limit');

		if ($this->_downloads === null && $category = $this->getCategory())
		{	
            $model = $this->bootComponent('com_jdownloads')->getMVCFactory()->createModel('Downloads', 'Site', ['ignore_request' => true]);
            
            $model->setState('params', Factory::getApplication()->getParams());
			$model->setState('filter.category_id', $category->id);
			$model->setState('filter.published', $this->getState('filter.published'));
			$model->setState('filter.access', $this->getState('filter.access'));
            $model->setState('filter.user_access', $this->getState('filter.user_access'));			
            $model->setState('filter.language', $this->getState('filter.language'));
            $model->setState('list.ordering', $this->getState('list.ordering'));    
			$model->setState('list.start', $this->getState('list.start'));
			$model->setState('list.limit', $limit);
			$model->setState('list.direction', $this->getState('list.direction'));
			$model->setState('list.filter', $this->getState('list.filter'));
			$model->setState('list.links', $this->getState('list.links'));

            if ($limit >= 0) {
				$this->_downloads = $model->getItems();

				if ($this->_downloads === false) {
					$this->setError($model->getError());
				}
			} else {
				$this->_downloads=array();
			}
            
            $this->_pagination = $model->getPagination();
		}

		return $this->_downloads;
	}

	/**
	 * Build the orderby for the query
	 *
	 * @return	string	$orderby portion of query
	 * @since	1.5
	 */
	protected function _buildContentOrderBy()
	{
        $app		= Factory::getApplication('site');
        $jinput     = Factory::getApplication()->input;
		$db			= $this->getDbo();
		$params		= $this->state->params;
        
        $itemid = $jinput->get('catid', '0', 'INT') . ':' . $jinput->get('Itemid', '0', 'INT');

		$orderCol	= $app->getUserStateFromRequest('com_jdownloads.category.' . $itemid . '.filter_order', 'filter_order', '', 'string');
        if ($orderCol == ''){
            $orderCol = $this->state->get('list.ordering');
        }
		$orderDirn	= $app->getUserStateFromRequest('com_jdownloads.category.' . $itemid . '.filter_order_Dir', 'filter_order_Dir', '', 'cmd');
        if ($orderDirn == ''){
            $orderDirn = $this->state->get('list.direction');
        }
        
        $orderby	= ' ';

		if (!in_array($orderCol, $this->filter_fields)) {
			$orderCol = null;
		}

		if (!in_array(strtoupper($orderDirn), array('ASC', 'DESC', ''))) {
			$orderDirn = '';
		}

		/* if ($orderCol && $orderDirn) {
			$orderby .= $db->escape($orderCol) . ' ' . $db->escape($orderDirn) . ' ';
		} else {
            $orderby .= $db->escape($orderCol) .' ';
            
        } */
       
		$filesOrderby		= $params->get('orderby_sec', '');
        
        // We have uses in the jD configuration the old numerical values (to be compatible) so we must correct it here at first
        $config_cats_order  = JDHelper::getCorrectedOrderbyValues('primary', $params->get('cats_order'));
		$categoryOrderby	= $params->def('orderby_pri', $config_cats_order);

        if ($filesOrderby){
		    $secondary			= QueryHelper::orderbySecondary($filesOrderby) . ' ';
        } else {
            $secondary          = QueryHelper::orderbySecondary($orderCol) . ' ';
        }    
	
        $primary			= QueryHelper::orderbyPrimary($categoryOrderby);

		$orderby .= $db->escape($primary) . ' ' . $db->escape($secondary) . ' '; // a.created ';
        
		return $orderby;
	}

	public function getPagination()
	{
		if (empty($this->_pagination)) {
			return null;
		}
		return $this->_pagination;
	}

	/**
	 * Method to get category data for the current category
	 *
	 * @param	int		An optional ID
	 *
	 * @return	object
	 * @since	1.5
	 */
	public function getCategory()
	{
        $app      = Factory::getApplication();
        $params   = $app->getParams();

        $options = '';
        
        if (!is_object($this->_item)) {

			$categories = CategoriesHelper::getInstance('jdownloads', $options);
			$this->_item = $categories->get($this->getState('category.id', 'root'));

			// Compute selected asset permissions.
			if (is_object($this->_item)) {
				$user	= Factory::getUser();
				$userId	= $user->get('id');
				$asset	= 'com_jdownloads.category.'.$this->_item->id;

				// Check general create permission.
				if ($user->authorise('core.create', $asset)) {
					$this->_item->getParams()->set('access-create', true);
				}

				// TODO: Why aren't we lazy loading the children and siblings?
				$this->_children = $this->_item->getChildren();
				$this->_parent = false;

				if ($this->_item->getParent()) {
					$this->_parent = $this->_item->getParent();
				}

				$this->_rightsibling = $this->_item->getSibling();
				$this->_leftsibling = $this->_item->getSibling(false);
			}
			else {
				$this->_children = array();
				$this->_parent = false;
			}
            
            if (count($this->_children)){
                for ($i = 0; $i < count($this->_children); $i++) { 
                    if(isset($this->_children[$i])){
                        // Get the tags
                        $this->_children[$i]->tags = new TagsHelper;
                        $this->_children[$i]->tags->getItemTags('com_jdownloads.category',  $this->_children[$i]->id);
                    }
                }
            }
		}

		return $this->_item;
	}

	/**
	 * Get the parent category.
	 *
	 * @param	int		An optional category id. If not supplied, the model state 'category.id' will be used.
	 *
	 * @return	mixed	An array of categories or false if an error occurs.
	 * @since	1.6
	 */
	public function getParent()
	{
		if (!is_object($this->_item)) {
			$this->getCategory();
		}

		return $this->_parent;
	}

	/**
	 * Get the left sibling (adjacent) categories.
	 *
	 * @return	mixed	An array of categories or false if an error occurs.
	 * @since	1.6
	 */
	function &getLeftSibling()
	{
		if (!is_object($this->_item)) {
			$this->getCategory();
		}

		return $this->_leftsibling;
	}

	/**
	 * Get the right sibling (adjacent) categories.
	 *
	 * @return	mixed	An array of categories or false if an error occurs.
	 * @since	1.6
	 */
	function &getRightSibling()
	{
		if (!is_object($this->_item)) {
			$this->getCategory();
		}

		return $this->_rightsibling;
	}

	/**
	 * Get the child categories.
	 *
	 * @param	int		An optional category id. If not supplied, the model state 'category.id' will be used.
	 *
	 * @return	mixed	An array of categories or false if an error occurs.
	 * @since	1.6
	 */
	function &getChildren()
	{
		if (!is_object($this->_item)) {
			$this->getCategory();
		}

		// Order subcategories
		if (sizeof($this->_children)) {
			$params = $this->getState()->get('params');
            
            // Sort order defined in menu?
            $orderby_pri = $params->get('orderby_pri');
            
            if (!$orderby_pri){
                // When not we use jD settings
                $cats_order = $params->get('cats_order');
                if ($cats_order == 1){
                    $params->set('orderby_pri', 'alpha');
                } elseif ($cats_order == 2){
                    $params->set('orderby_pri', 'ralpha');
                } 
            }
			
            if ($params->get('orderby_pri') == 'alpha' || $params->get('orderby_pri') == 'ralpha') {
                $this->_children = ArrayHelper::sortObjects($this->_children, 'title', ($params->get('orderby_pri') == 'alpha') ? 1 : (-1));
			}
		}

		return $this->_children;
	}
}
