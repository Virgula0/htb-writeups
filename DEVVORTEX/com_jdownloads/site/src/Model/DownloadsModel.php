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

use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Helper\TagsHelper;
use Joomla\CMS\Language\Associations;
use Joomla\CMS\Language\Multilanguage;
use Joomla\CMS\MVC\Model\ListModel;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\Database\ParameterType;
use Joomla\Registry\Registry;
use Joomla\String\StringHelper;
use Joomla\Utilities\ArrayHelper;

use JDownloads\Component\JDownloads\Administrator\Extension\JDownloadsComponent;
use JDownloads\Component\JDownloads\Site\Helper\QueryHelper;
use JDownloads\Component\JDownloads\Site\Helper\AssociationHelper;
use JDownloads\Component\JDownloads\Administrator\Helper\JDownloadsAssociationsHelper;


/**
 * This models supports retrieving lists of downloads.
 *
 * @package		Joomla.Site
 * @subpackage	com_content
 */
class DownloadsModel extends ListModel
{

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
				'checked_out', 'a.checked_out',
				'checked_out_time', 'a.checked_out_time',
				'catid', 'a.catid', 'category_title',
                'author', 'a.author',
				'featured', 'a.featured',
                'published', 'a.published',
				'access', 'a.access', 'access_level',
                'user_access', 'a.user_access',
				'created', 'a.created',
				'created_by', 'a.created_by',
				'ordering', 'a.ordering',
				'language', 'a.language',
				'downloads', 'a.downloads',
				'publish_up', 'a.publish_up',
				'publish_down', 'a.publish_down',
				'images', 'a.images',
			);
		}

		parent::__construct($config);
	}

	/**
	 * Method to auto-populate the model state.
	 *
	 * Note. Calling getState in this method will result in recursion.
	 *
	 * @return	void
	 */
	protected function populateState($ordering = 'ordering', $direction = 'ASC')
	{
        $app    = Factory::getApplication();
        $jinput = Factory::getApplication()->input;

        // Special handling when latest (type: new) or hottest (type: top) are selected in category box 
        $order_type  = $jinput->getString('type', '');
        switch ($order_type){
            case 'new': 
               $this->setState('selected_item', 'new');
               break;
            case 'top': 
               $this->setState('selected_item', 'top');
               break;
            case 'all': 
               $order_type = '';
               break;
        }
        
        if ($order_type == 'new') $this->setState('only_uncategorised', true);

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

        $user         = Factory::getUser();
        
        $listOrderNew = false;
                
        // Create a new query object.
        $db        = $this->getDbo();
        $query     = $db->getQuery(true);
        $groups    = implode(',', $user->getAuthorisedViewLevels());
        $menu_params = $this->state->params;

        // Filter by start and end dates.
        if ((!$user->authorise('core.edit.state', 'com_jdownloads')) &&  (!$user->authorise('core.edit', 'com_jdownloads')))
        {
            // Limit to published for people who can't edit or edit.state.
            $this->setState('filter.published', 1);
            
            $nowDate = $db->Quote(Factory::getDate()->toSql()); // True to return the date string in the local time zone, false to return it in GMT.

            $query->where('(files.publish_up IS NULL OR files.publish_up <= ' . $nowDate . ')');
            $query->where('(files.publish_down IS NULL OR files.publish_down >= ' . $nowDate . ')');
        } else {
            $this->setState('filter.published', array(0, 1, 2));
        }

        $this->setState('filter.access', true);
        
        $this->setState('filter.user_access', true);

        // Optional filter text
        $this->setState('list.filter', $jinput->get('filter-search', '', 'STRING'));

        // filter.order
        $orderCol = $app->getUserStateFromRequest('com_jdownloads.downloads.filter_order', 'filter_order', '', 'string');
        
        if (!$order_type){
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
                             // Files created desc 
                             $orderCol = 'a.created'; // desc
                             $listOrderNew = 'DESC';
                             break;
                        case '2':
                             // Files created asc 
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
        } else {
            // Special order is selected in category select box
            if ($order_type == 'new'){
                $orderCol = 'a.created';
                $listOrderNew = 'DESC';
            } else {
                $orderCol = 'a.downloads';
                $listOrderNew = 'DESC';
            }
        }                
            
        $this->setState('list.ordering', $orderCol);

        $listOrder = $app->getUserStateFromRequest('com_jdownloads.downloads.filter_order_Dir', 'filter_order_Dir', '', 'cmd');
        if (!in_array(strtoupper($listOrder), array('ASC', 'DESC', ''))) {
            $listOrder = 'ASC';
        }
        if (!$listOrderNew){
            $this->setState('list.direction', $listOrder);
        } else {
            $this->setState('list.direction', $listOrderNew);
        }    

        $this->setState('list.start', $jinput->get('limitstart', '0', 'UINT'));

        $limit= $app->input->get('limit', false, 'uint');
        
        if ($limit === false){
            if ((int)$menu_params->get('display_num') > 0) {
                $limit = (int)$menu_params->get('display_num');
            } else {
                $limit = (int)$params->get('files_per_side');
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
		$id .= ':' . serialize($this->getState('filter.published'));
		$id .= ':' . $this->getState('filter.access');
        $id .= ':' . $this->getState('filter.user_access');
        $id .= ':' . $this->getState('filter.featured');
		$id	.= ':' . serialize($this->getState('filter.category_id'));
		$id .= ':' . $this->getState('filter.category_id.include');
		$id	.= ':' . serialize($this->getState('filter.author_id'));
		$id .= ':' . $this->getState('filter.author_id.include');
		$id	.= ':' . serialize($this->getState('filter.author_alias'));
		$id .= ':' . $this->getState('filter.author_alias.include');
        $id .= ':' . serialize($this->getState('filter.log_id'));
        $id .= ':' . $this->getState('filter.log_id.include');
		$id .= ':' . $this->getState('filter.date_filtering');
		$id .= ':' . $this->getState('filter.date_field');
		$id .= ':' . $this->getState('filter.start_date_range');
		$id .= ':' . $this->getState('filter.end_date_range');
		$id .= ':' . $this->getState('filter.relative_date');

		return parent::getStoreId($id);
	}

	/**
	 * Get the master query for retrieving a list of downloads subject to the model state.
	 *
	 * @return	JDatabaseQuery
	 */
	function getListQuery()
	{
        
        $app = Factory::getApplication();
        
		$db = $this->getDbo();
		$query = $db->getQuery(true);
        
        $nowDate = Factory::getDate()->toSql();
        
        // Get the current user for authorisation checks
        $user    = Factory::getUser();
        $user->authorise('core.admin') ? $is_admin = true : $is_admin = false;
        $groups  = implode (',', $user->getAuthorisedViewLevels());
        
        $params      = $this->getState('params');
        
		// Select the required fields from the table.
		$query->select(
			$this->getState(
				'list.select',
				'a.id, a.title, a.alias, a.description, a.description_long, a.file_pic, a.images, a.price, a.release, a.file_language, a.system, '.
                'a.license, a.url_license, a.license_agree, a.size, a.created, a.file_date, a.publish_up, a.publish_down, a.use_timeframe, a.url_download, a.preview_filename, '.
                'a.other_file_id, a.md5_value, a.sha1_value, a.extern_file, a.extern_site, a.mirror_1, a.mirror_2, a.extern_site_mirror_1, a.extern_site_mirror_2, '.
                'a.url_home, a.author, a.url_author, a.created_by, a.created_mail, a.modified_by, a.modified, a.submitted_by, a.set_aup_points, a.downloads, '.
                'a.catid, a.changelog, a.password, a.password_md5, a.views, a.metakey, a.metadesc, a.robots, a.update_active, a.access, a.user_access, a.language, a.ordering, a.featured, '.                
                'a.published, a.checked_out, a.checked_out_time, ' .
				'a.modified, ' .
   				'a.modified_by,' .
				// use created if publish_up is 0
				'CASE WHEN a.publish_up = 0 THEN a.created ELSE a.publish_up END as publish_up,' .
					'a.publish_down, a.images, a.metakey, a.metadesc, a.access, ' .
					'a.downloads,'.' '.$query->length('a.description_long').' AS readmore'
			)
		);

		// Process an Archived Download layout - IMPORTANT: Please note that the archive functionality is still not really implemented !  
		if ($this->getState('filter.published') == 2) {
			// If badcats is not null, this means that the download is inside an archived category
			// In this case, the state is set to 2 to indicate Archived (even if the download state is Published)
			$query->select($this->getState('list.select', 'CASE WHEN badcats.id is null THEN a.published ELSE 2 END AS state'));
		}
		else {
			// Process non-archived layout
			// If badcats is not null, this means that the download is inside an unpublished category
			// In this case, the state is set to 0 to indicate Unpublished (even if the download state is Published)
			$query->select($this->getState('list.select', 'CASE WHEN badcats.id is not null THEN 0 ELSE a.published END AS state'));
		}

		$query->from('#__jdownloads_files AS a');
        
        // Join on files table.
        $query->select('aa.url_download AS filename_from_other_download');
        $query->join('LEFT', '#__jdownloads_files AS aa on aa.id = a.other_file_id');        

		// Join over the categories.
		$query->select('c.title AS category_title, c.access AS category_access, c.alias AS category_alias, c.cat_dir AS category_cat_dir, c.cat_dir_parent AS category_cat_dir_parent');
		$query->join('LEFT', '#__jdownloads_categories AS c ON c.id = a.catid');
        
		$query->join('LEFT', '#__users AS ua ON ua.id = a.created_by');
		$query->join('LEFT', '#__users AS uam ON uam.id = a.modified_by');

        // Join on user table.
        if ($params->get('use_real_user_name_in_frontend')){
            $query->select('u.name AS creator');
        } else {
            $query->select('u.username AS creator');
        }    
        $query->join('LEFT', '#__users AS u on u.id = a.created_by');
        
        if ($params->get('use_real_user_name_in_frontend')){
            $query->select('u2.name AS modifier');
        } else {
            $query->select('u2.username AS modifier');
        } 
        $query->join('LEFT', '#__users AS u2 on u2.id = a.modified_by');        
        
        if ($params->get('use_real_user_name_in_frontend')){
            $query->select('u3.name AS user_access_name');
        } else {
            $query->select('u3.username AS user_access_name');
        } 
        $query->join('LEFT', '#__users AS u3 on u3.id = a.user_access');        
        
        // Join on license table.
        $query->select('l.title AS license_title, l.url AS license_url, l.description AS license_text, l.id as lid');
        $query->join('LEFT', '#__jdownloads_licenses AS l on l.id = a.license');
        
        // Join on ratings table.
        $query->select('ROUND(r.rating_sum / r.rating_count, 0) AS rating, r.rating_count as rating_count, r.rating_sum as rating_sum');
        $query->join('LEFT', '#__jdownloads_ratings AS r on r.file_id = a.id'); 
        
		// Join over the categories to get parent category titles
		$query->select('parent.title as parent_title, parent.id as parent_id, parent.alias as parent_alias');
		$query->join('LEFT', '#__jdownloads_categories as parent ON parent.id = c.parent_id');
        
        // Join on menu table. We need the single download menu itemid when exist                                                                                                  
        $query->select('menuf.id AS menuf_itemid');
        $query->join('LEFT', '(SELECT id, link, access, published from #__menu GROUP BY link) AS menuf on menuf.link LIKE CONCAT(\'index.php?option=com_jdownloads&view=download&id=\',a.id) AND menuf.published = 1 AND menuf.access IN ('.$groups.')') ;

        // Join on menu table. We need the single category menu itemid when exist                                                                                                  
        $query->select('menuc.id AS menuc_cat_itemid');
        $query->join('LEFT', '(SELECT id, link, access, published from #__menu GROUP BY link) AS menuc on menuc.link LIKE CONCAT(\'index.php?option=com_jdownloads&view=category&catid=\',a.catid) AND menuc.published = 1 AND menuc.access IN ('.$groups.')') ;
        
		// Join to check for category published state in parent categories up the tree
		$query->select('c.published, CASE WHEN badcats.id is null THEN c.published ELSE 0 END AS parents_published');
		$subquery = 'SELECT cat.id as id FROM #__jdownloads_categories AS cat JOIN #__jdownloads_categories AS parent ';
		$subquery .= 'ON cat.lft BETWEEN parent.lft AND parent.rgt ';
		// Find any up-path categories that are not published
		// If all categories are published, badcats.id will be null, and we just use the download state
		$subquery .= ' AND parent.published != 1 GROUP BY cat.id ';
		// Select state to unpublished if up-path category is unpublished
		$publishedWhere = 'CASE WHEN badcats.id is null THEN a.published ELSE 0 END';
        
		$query->join('LEFT OUTER', '(' . $subquery . ') AS badcats ON badcats.id = c.id');

		// Filter by access level and by user_access field (when used). 
        if ($this->getState('filter.access')){
			if ($this->getState('filter.user_access')){
                if ($user->id > 0){
                    // User is not a guest so we can generally use the user-id to find also the Downloads with single user access
                    if ($is_admin){
                        // User is admin so we should display all possible Downloads - included the Downloads with single user access 
                        $query->where('((a.access IN ('.$groups.') AND a.user_access = 0) OR (a.access != 0 AND a.user_access != 0))');
                        $query->where('c.access IN ('.$groups.')');
                    } else {
                        $query->where('((a.access IN ('.$groups.') AND a.user_access = 0) OR (a.access != 0 AND a.user_access = '.$db->quote($user->id). '))');
                        $query->where('c.access IN ('.$groups.')');
                    }
	            } else {    
	                $query->where('a.access IN ('.$groups.') AND a.user_access = 0');
				    $query->where('c.access IN ('.$groups.')');
	            }
            } else {
                $query->where('a.access IN ('.$groups.') AND a.user_access = 0');
			    $query->where('c.access IN ('.$groups.')');
            } 
		}
        

		// Filter by published state
		$published = $this->getState('filter.published');

		if (is_numeric($published)) {
			// Use download state if badcats.id is null, otherwise, force 0 for unpublished
			$query->where($publishedWhere . ' = ' . (int) $published);
		}
		elseif (is_array($published)) {
			ArrayHelper::toInteger($published);
			$published = implode(',', $published);
			// Use download state if badcats.id is null, otherwise, force 0 for unpublished
			$query->where($publishedWhere . ' IN ('.$published.')');
		}
        
        // Filter by a single category
        $categoryId = $this->getState('filter.category_id');

        if (is_numeric($categoryId)) {
            $type = $this->getState('filter.category_id.include', true) ? '= ' : '<> ';

            $categoryEquals = 'a.catid '.$type.(int) $categoryId;
            $query->where($categoryEquals);
        } else { 
            if (is_array($categoryId) && (count($categoryId) > 0)) {
                ArrayHelper::toInteger($categoryId);
                $categoryId = implode(',', $categoryId);
                if (!empty($categoryId)) {
                    $type = $this->getState('filter.category_id.include', true) ? 'IN' : 'NOT IN';
                    $query->where('a.catid '.$type.' ('.$categoryId.')');
                }
            }    
        }        

		// Filter by author
		$authorId = $this->getState('filter.author_id');
		$authorWhere = '';

		if (is_numeric($authorId)) {
			$type = $this->getState('filter.author_id.include', true) ? '= ' : '<> ';
			$authorWhere = 'a.created_by '.$type.(int) $authorId;
		}
		elseif (is_array($authorId)) {
			ArrayHelper::toInteger($authorId);
			$authorId = implode(',', $authorId);

			if ($authorId) {
				$type = $this->getState('filter.author_id.include', true) ? 'IN' : 'NOT IN';
				$authorWhere = 'a.created_by '.$type.' ('.$authorId.')';
			}
		}
       
		if (!empty($authorWhere)) {
			$query->where('('.$authorWhere.')');
		}

        // Filter by log ID - only used in modules
        $logId = $this->getState('filter.log_id');
        $logWhere = '';
       
        if ($logId){
               $type = $this->getState('filter.log_id.include', true) ? 'IN' : 'NOT IN';
               $logWhere = 'a.id '.$type.' ('.$logId.')';
        }
       
        if (!empty($logWhere)) {
            $query->where('('.$logWhere.')');
        }       
		
        // Filter by the downloads 'update_active' field   (required for last-updated- module)
        $updated = $this->getState('filter.updated', '0');
        if ($updated == 1) $query->where( 'a.update_active = 1' );

        // Additional filter - variable usable - as example in modules
        $additional = $this->getState('filter.additional', '');
        if ($additional) $query->where( $additional );
        
		// Filter by start and end dates.
		if ((!$user->authorise('core.edit.state', 'com_jdownloads')) && (!$user->authorise('core.edit', 'com_jdownloads')))
        {
            $nowDate = $db->Quote(Factory::getDate()->toSql()); // True to return the date string in the local time zone, false to return it in GMT.

            $query->where('(a.publish_up IS NULL OR a.publish_up <= ' . $nowDate . ')');
            $query->where('(a.publish_down IS NULL OR a.publish_down >= ' . $nowDate . ')');
        } 
        
        // Filter by Date Range or Relative Date
        $dateFiltering = $this->getState('filter.date_filtering', 'off');
        $dateField     = $db->escape($this->getState('filter.date_field', 'a.created'));

        switch ($dateFiltering)
        {
            case 'range':
                $startDateRange = $this->getState('filter.start_date_range', '');
                $endDateRange   = $this->getState('filter.end_date_range', '');

                if ($startDateRange || $endDateRange)
                {
                    $query->where($db->quoteName($dateField) . ' IS NOT NULL');

                    if ($startDateRange)
                    {
                        $query->where($db->quoteName($dateField) . ' >= :startDateRange')
                            ->bind(':startDateRange', $startDateRange);
                    }

                    if ($endDateRange)
                    {
                        $query->where($db->quoteName($dateField) . ' <= :endDateRange')
                            ->bind(':endDateRange', $endDateRange);
                    }
                }

                break;

            case 'relative':
                $relativeDate = (int) $this->getState('filter.relative_date', 0);
                $query->where(
                    $db->quoteName($dateField) . ' IS NOT NULL AND '
                    . $db->quoteName($dateField) . ' >= ' . $query->dateAdd($db->quote($nowDate), -1 * $relativeDate, 'DAY')
                );
                break;

            case 'off':
            default:
                break;
        }
        
		// Process the filter for list views with user-entered filters

		if ((is_object($params)) && ($params->get('filter_field') != 'hide') && ($filter = $this->getState('list.filter'))) {
			// Clean filter variable
			$filter = StringHelper::strtolower($filter);
			$hitsFilter = intval($filter);
			$filter = $db->Quote('%'.$db->escape($filter, true).'%', false);

			switch ($params->get('filter_field'))
			{
				case 'author':
					$query->where(
						'LOWER(ua.name) LIKE '.$filter.' '
					);
					break;

				case 'hits':
					$query->where('a.downloads >= '.$hitsFilter.' ');
					break;

				case 'title':
				default: // Default to 'title' if parameter is not valid
					$query->where('LOWER( a.title ) LIKE '.$filter);
					break;
			}
		}

		// Filter by language
		if ($this->getState('filter.language')) {
			$query->where('a.language in ('.$db->quote(Factory::getLanguage()->getTag()).','.$db->quote('*').')');
		}
        
        // Filter by featured state
        $featured = $this->getState('filter.featured');

        switch ($featured)
        {
            case 'hide':
                $query->where('a.featured = 0');
                break;

            case 'only':
                $query->where('a.featured = 1');
                break;

            case 'show':
            default:
                break;
        }        

		// Add the list ordering clause.
        $order = $this->getState('list.ordering', 'a.ordering').' '.$this->getState('list.direction', 'ASC');
        $order = str_replace('DESC   DESC','DESC', $order);
        $query->order($order);
		
		return $query;
	}

	/**
	 * Method to get a list of downloads.
	 *
	 * Overriden to inject convert the attribs field into a JParameter object.
	 *
	 * @return	mixed	An array of objects on success, false on failure.
	 */
	public function getItems()
	{
		$items	= parent::getItems();
        
		$user	= Factory::getUser();
		$userId	= $user->get('id');
		$guest	= $user->get('guest');
		$groups	= $user->getAuthorisedViewLevels();

		// Get the global params
		$globalParams = ComponentHelper::getParams('com_jdownloads', true);

		// Convert the parameter fields into objects.
		foreach ($items as &$item)
		{
			$downloadParams = new Registry;
			if (isset($item->attribs)){
                $downloadParams->loadString($item->attribs);
            }    

			$item->layout = $downloadParams->get('layout');

			$item->params = clone $this->getState('params');

            // Check the permission for the view
            $access = $this->getState('filter.access');
            
            if ($access){
                $item->params->set('access-view', true);
            } else {
                // If no access filter is set, the layout takes some responsibility for display of limited information.
                if ($item->catid == 0 || $item->category_access === null) {
                    $item->params->set('access-view', in_array($item->access, $groups));
                } else {
                    $item->params->set('access-view', in_array($item->access, $groups) && in_array($item->category_access, $groups));
                }
            }

			// Compute the asset access permissions.
            $asset = 'com_jdownloads.download.'.$item->id;

            // Check at first the 'download' permission.
            if ($user->authorise('download', $asset)) {
                $item->params->set('access-download', true);
            }                

            // Technically guest could edit a download, but lets not check that to improve performance a little.
            if (!$guest) {
                
				// Check general edit permission first.
				if ($user->authorise('core.edit', $asset)) {
					$item->params->set('access-edit', true);
				}
				// Now check if edit.own is available.
				elseif (!empty($userId) && $user->authorise('core.edit.own', $asset)) {
					// Check for a valid user and that they are the owner.
					if ($userId == $item->created_by) {
						$item->params->set('access-edit', true);
					}
				}
			}
            
            // Get the tags
            $item->tags = new TagsHelper;
            $item->tags->getItemTags('com_jdownloads.download', $item->id);
            
            // Build the multilingual association hints
            if ($item->params->get('show_associations')){
                $item->associations = AssociationHelper::displayAssociations($item->id);
            }            
		}

		return $items;
	}

	public function getStart()
	{
		return $this->getState('list.start');
	}
}
