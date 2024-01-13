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
 
use Joomla\CMS\MVC\Model\ListModel;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\Utilities\ArrayHelper;
use Joomla\String\StringHelper;
use Joomla\CMS\Factory;
use Joomla\Registry\Registry;

use JDownloads\Component\JDownloads\Administrator\Extension\JDownloadsComponent;
use JDownloads\Component\JDownloads\Site\Helper\QueryHelper;


/**
 * This models supports retrieving lists of downloads.
 *
 * @package		Joomla.Site
 * @subpackage	com_content
 */
class MyHistoryModel extends ListModel
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
                'type', 'a.type',
                'log_file_id', 'a.log_file_id',
                'log_file_size', 'a.log_file_size',
                'log_file_name', 'a.log_file_name',
                'log_title', 'a.log_title',
                'log_ip', 'a.log_ip',
                'log_datetime', 'a.log_datetime',
                'log_user', 'a.log_user',
                'log_browser', 'a.log_browser',
                'language', 'a.language',
                'ordering', 'a.ordering',
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
        
        $app = Factory::getApplication();
        $jinput = Factory::getApplication()->input;
        $user   = Factory::getUser();

        $classname = 'JDownloads\Component\JDownloads\Site\Helper\QueryHelper';

        if (!class_exists($classname))
        {
            $path = JPATH_SITE . '/components/com_jdownloads/src/Helper/QueryHelper.php';
            
            if (is_file($path))
            {
                include_once $path;
                            \JLoader::register($classname, $path);
            }
                else
            {
                return false;
            }
        }
        
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

        $listOrderNew = false;
                
        // Create a new query object.
        $db        = $this->getDbo();
        $query     = $db->getQuery(true);
        $menu_params = $this->state->params;

        // filter.order
        $orderCol = $app->getUserStateFromRequest('com_jdownloads.downloads.filter_order', 'filter_order', '', 'string');
        
        if (!in_array($orderCol, $this->filter_fields) || $orderCol == '') {
            // use order from menu settings 
            $filesOrderby = $params->get('orderby_sec', 'order');
            $orderCol    = QueryHelper::orderHistoryBy($filesOrderby) . ' ';
            $order_array  = explode(' ', $orderCol);
            if (count($order_array) > 2){
                $orderCol       = $order_array[0];
                $listOrderNew   = $order_array[1];
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

        $this->setState('list.start', $app->input->getUInt('limitstart', 0));

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
		$id .= ':' . $this->getState('filter.date_field');

		return parent::getStoreId($id);
	}

	/**
	 * Get the master query for retrieving a list of downloads subject to the model state.
	 *
	 * @return	JDatabaseQuery
	 */
	function getListQuery()
	{
        
        // Create a new query object.
		$db = $this->getDbo();
		$query = $db->getQuery(true);
        $user    = Factory::getUser();
        $groups  = implode (',', $user->getAuthorisedViewLevels());
        
		// Select the required fields from the table.
		$query->select(
            $this->getState(
                'list.select',
                'a.id, a.type, a.log_file_id, a.log_file_size, a.log_file_name, a.log_title, a.log_ip, a.log_datetime, a.log_user, a.log_browser, '  .
                'a.language, a.ordering'
            )
        );
                
        $query->from('`#__jdownloads_logs` AS a');
        
        // Join over the downloads table
        $query->select('b.*')
        ->join('LEFT', '#__jdownloads_files AS b ON b.id = a.log_file_id');
        
        // Join on category table.
        $query->select('c.title AS category_title, c.access AS category_access, c.published AS category_published')
            ->innerJoin('#__jdownloads_categories AS c on c.id = b.catid')
            ->where('c.published IN (0,1)');
        
		// Filter by user id
        $query->where('a.log_user = '.$db->Quote($user->id)); 
        
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

		// Get the global params
		$globalParams = ComponentHelper::getParams('com_jdownloads', true);

		// Convert the parameter fields into objects.
		foreach ($items as &$item)
		{
			$downloadParams = new Registry;
			$item->layout = $downloadParams->get('layout');
			$item->params = clone $this->getState('params');
		}

		return $items;
	}

	public function getStart()
	{
		return $this->getState('list.start');
	}
}
