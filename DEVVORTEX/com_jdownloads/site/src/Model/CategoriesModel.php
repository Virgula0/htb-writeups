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

namespace JDownloads\Component\JDownloads\Site\Model;

\defined('_JEXEC') or die;
 
use Joomla\CMS\Factory;
use Joomla\CMS\Categories\CategoryNode;
use Joomla\CMS\MVC\Model\ListModel;
use Joomla\Registry\Registry;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Language\Multilanguage;
use Joomla\CMS\Helper\TagsHelper;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\Database\ParameterType;
use Joomla\String\StringHelper;
use Joomla\Utilities\ArrayHelper;

use JDownloads\Component\JDownloads\Administrator\Extension\JDownloadsComponent;
use JDownloads\Component\JDownloads\Site\Helper\CategoriesHelper;
use JDownloads\Component\JDownloads\Site\Helper\QueryHelper;
use JDownloads\Component\JDownloads\Site\Helper\AssociationHelper;
use JDownloads\Component\JDownloads\Administrator\Helper\JDownloadsAssociationsHelper;

/**
 * This models supports retrieving lists of download categories.
 *
 */
class CategoriesModel extends ListModel
{
	/**
	 * Model context string.
	 *
	 * @var		string
	 */
	public $_context = 'com_jdownloads.categories';

	/**
     * Parent category of the current one
     *
     * @var    CategoryNode|null
     */
    private $_parent = null;

	private $_items = null;

	/**
	 * Method to auto-populate the model state.
	 *
	 * Note. Calling getState in this method will result in recursion.
	 *
	 */
	protected function populateState($ordering = null, $direction = null)
	{
        $app    = Factory::getApplication();
        $jinput = Factory::getApplication()->input;

        // Get the parent id if defined.
        $parentId = $app->input->getInt('id');
        $this->setState('filter.parentId', $parentId);

        // Load the parameters. Merge Global and Menu Item params into new object
        $params = $app->getParams();

        if ($menu = $app->getMenu()->getActive()){
            $menuParams = $menu->getParams();
        } else {
            $menuParams = new Registry;
        }

        $mergedParams = clone $menuParams;
        $mergedParams->merge($params);

        $this->setState('params', $mergedParams);

        $user = Factory::getUser();
                
        // Create a new query object.
        $db           = $this->getDbo();
        $query        = $db->getQuery(true);
        $groups       = implode(',', $user->getAuthorisedViewLevels());
        $menu_params  = $this->state->params;
        $listOrderNew = '';

        $this->setState('filter.published', 1);
        $this->setState('filter.access', true);
        $this->setState('filter.user_access', true);

        // filter.order
        $orderCol = $app->getUserStateFromRequest('com_jdownloads.categories.filter_order', 'filter_order', '', 'string');
        
        if ($orderCol == ''){
            // Use default sort order or menu order settings
            if ($menu_params->get('orderby_pri') == ''){
                // Use config settings
                switch ($params->get('cats_order')){
                    case '1':
                         // Files title field asc 
                         $orderCol = 'c.title';
                         $listOrderNew = 'ASC';
                         break;
                    case '2':
                         // Files title field desc 
                         $orderCol = 'c.title';
                         $listOrderNew = 'DESC';
                         break;
                    default:
                         // Files ordering field
                         $orderCol = 'c.ordering';
                         $listOrderNew = 'ASC';
                         break;                }
            } else {
                // Use order from menu settings 
                $categoryOrderby    = $params->def('orderby_pri', $params->get('cats_order'));
                $orderCol           = str_replace(', ', '', QueryHelper::orderbyPrimary($categoryOrderby));
            }    
        }
        $this->setState('list.ordering', $orderCol);

        $listOrder = $app->getUserStateFromRequest('com_jdownloads.categories.filter_order_Dir', 'filter_order_Dir', '', 'cmd');
        if (!in_array(strtoupper($listOrder), array('ASC', 'DESC', ''))) {
            $listOrder = 'ASC';
        }
        if (!$listOrderNew){
            $this->setState('list.direction', $listOrder);
        } else {
            $this->setState('list.direction', $listOrderNew);
        }    

        $this->setState('list.start', $jinput->getUInt('limitstart', '0'));

        $limit = $app->getUserStateFromRequest('com_jdownloads.categories.limit', 'limit',  '', 'uint');
        if (!$limit){
            if ((int)$menu_params->get('display_num') > 0) {
                $limit = (int)$menu_params->get('display_num');
            } else {
                $limit = (int)$params->get('categories_per_side');
            }
        }
        
        $this->setState('list.limit', $limit);
        $this->setState('filter.language', $app->getLanguageFilter());
        $this->setState('layout', $jinput->get('layout'));        
	}

	/**
	 * Method to get a store id based on model configuration state.
	 *
	 * This is necessary because the model is used by the component and
	 * different modules that might need different sets of data or different
	 * ordering requirements.
	 *
	 * @param	string		$id	A prefix for the store id.
	 *
	 * @return	string		A store id.
	 */
	protected function getStoreId($id = '')
	{
		// Compile the store id.
		$id	.= ':'.$this->getState('filter.published');
		$id	.= ':'.$this->getState('filter.access');
		$id .= ':'.$this->getState('filter.user_access');
		$id	.= ':'.$this->getState('filter.parentId');
        $id .= ':'.$this->getState('filter.category_id');
        $id .= ':'.$this->getState('filter.level');
        
		return parent::getStoreId($id);
	}

	/**
	 * Redefine the function an add some properties to make the styling more easy
	 *
	 * @param	bool	$recursive	True if you want to return children recursively.
	 *
	 * @return	mixed	An array of data items on success, false on failure.
	 */
	public function getItems($recursive = false, $no_parent_id = false)
	{
	    if (!$this->_items){
			$app = Factory::getApplication();
			$menu = $app->getMenu();
			$active = $menu->getActive();
			
			if ($active){
                $params = $active->getParams();
            } else {
                $params = new Registry;
            }

			$options = array();
            $options['countItems'] = true; 
            $option =  $this->getState('list.ordering');
            
            if ($option == 'c.ordering'){
                $options['ordering'] = 'c.lft';
            } else {
                $options['ordering'] = $this->getState('list.ordering');
            }
                
			$options['direction']   = $this->getState('list.direction');
            $options['category_id'] = $this->getState('filter.category_id');
            $options['level']       = $this->getState('filter.level', 0);
            
			$categories = CategoriesHelper::getInstance('jdownloads', $options);
            
            if ($no_parent_id){
                // Special situation when we get the data for the 'tree' module
                $this->_parent = $categories->get('0');
            } else {
                $this->_parent = $categories->get($this->getState('filter.parentId', 'root'));
            }
            
            if (is_object($this->_parent)){
				$this->_items = $this->_parent->getChildren($recursive);
			} else {
				$this->_items = false;
			}
		}
        
		return $this->_items;
	}

	/**
     * Get the parent.
     *
     * @return  object  An array of data items on success, false on failure.
     *
     * @since   1.6
     */
    public function getParent()
	{
		if (!is_object($this->_parent)){
			$this->getItems();
		}

		return $this->_parent;
	}
    
    /**
    * Method to get the total number of categories
    *
    * @return  int     The total number of categories
    */
    public function getTotal()
    {
        if (empty($this->_total)){
            if ($this->_items == false) $this->_items = array();
            $this->_total = count($this->_items);
        }

        return $this->_total;
    }    
}
