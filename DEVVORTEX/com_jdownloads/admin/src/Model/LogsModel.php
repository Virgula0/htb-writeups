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

\defined('_JEXEC') or die();

use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Language\Associations;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\CMS\Table\Table;
use Joomla\Utilities\ArrayHelper;
use Joomla\CMS\Factory;
use Joomla\Database\ParameterType;
use Joomla\CMS\MVC\Model\ListModel;
use Joomla\Registry\Registry;

use JDownloads\Component\JDownloads\Administrator\Helper\JDownloadsHelper;

class LogsModel extends ListModel
{
	
     /**
     * Constructor.
     *
     * @param    array    An optional associative array of configuration settings.
     * @see      JController
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
     */
    protected function populateState($ordering = 'a.log_datetime', $direction = 'desc')
    {
        // Initialise variables.
        $app = Factory::getApplication();
        
        // Adjust the context to support modal layouts.
        if ($layout = $app->input->get('layout')){
            $this->context .= '.' . $layout;
        } 
        
        // Load the filter state.
        $search = $this->getUserStateFromRequest($this->context.'.filter.search', 'filter_search');
        $this->setState('filter.search', $search);

        // Load the type state.
        $type = $this->getUserStateFromRequest($this->context.'.filter.type', 'type');
        $this->setState('filter.type', $type);
        
        // Load the parameters.
        $params = ComponentHelper::getParams('com_jdownloads');
        $this->setState('params', $params);
        
        parent::populateState($ordering, $direction);
    }

    /**
     * Method to get a store id based on model configuration state.
     *
     * This is necessary because the model is used by the component and
     * different modules that might need different sets of data or different
     * ordering requirements.
     *
     * @param    string        $id    A prefix for the store id.
     * @return    string        A store id.
     */
    protected function getStoreId($id = '')
    {
        // Compile the store id.
        $id.= ':' . $this->getState('filter.search');
        $id.= ':' . $this->getState('filter.type');

        return parent::getStoreId($id);
    }

    /**
     * Build an SQL query to load the list data.
     *
     * @return    JDatabaseQuery
     */
    protected function getListQuery()
    {
        $app    = Factory::getApplication();
        $user   = $app->getIdentity();
        
        // Create a new query object.
        $db        = $this->getDbo();
        $query     = $db->getQuery(true);
        
        // Select the required fields from the table.
        $query->select(
            $this->getState(
                'list.select',
                'a.id, a.type, a.log_file_id, a.log_file_size, a.log_file_name, a.log_title, a.log_ip, a.log_datetime, a.log_user, a.log_browser, '  .
                'a.language, a.ordering'
            )
        );
                
        $query->from('`#__jdownloads_logs` AS a');
        
        // Join over the users to get the user name.
        $query->select('uc.name AS username');
        $query->join('LEFT', '#__users AS uc ON uc.id = a.log_user');

        // Filter by search in title
        $search = $this->getState('filter.search');

        if (!empty($search)) {
            if (stripos($search, 'id:') === 0) {
                $query->where('a.id = '.(int) substr($search, 3));
            } else {
                $search = $db->Quote('%'.$db->escape($search, true).'%');
                $query->where('(a.log_title LIKE '.$search.' OR a.log_file_name LIKE '.$search.' OR uc.name LIKE '.$search.')');
           }                      
        }                   
        
        // Filter by log type
        $type = $this->getState('filter.type');
        if (is_numeric($type)) {
            if ($type > 0) {
                $query->where('a.type = '.(int) $type);
            } else {
                 $query->where('(a.type IN (1, 2))');
            }   
        } 
            
        // Add the list ordering clause.
        $listOrdering = $this->getState('list.ordering', 'a.log_datetime');
        $listDirn     = $db->escape($this->getState('list.direction', 'desc'));
        $query->order($db->escape($listOrdering.' '.$listDirn));
        
        return $query;
    }
    
    /**
     * delete the selected log items.
     *
     * @return    boolean
     */
    function delete($cid)
    {
        $db        = $this->getDbo(); 
        $query     = $db->getQuery(true);
        
        $total = count( $cid );
        $logs = join(",", $cid);

        $query->from('#__jdownloads_logs');
        $query->delete();
        $query->where("id IN ($logs)");
        $db->setQuery((string)$query);
        if ($db->execute()){
            return true;
        } else {
            return false;
        }    
    } 
    
    /**
     * Add listed log IDs to the blick IP list
     *
     * @return    boolean
     */    
    public function blockip($cid){
        
        $params = ComponentHelper::getParams('com_jdownloads');
        
        $db        = $this->getDbo(); 
        $query     = $db->getQuery(true);
        $total = 0;
        $id = join(",", $cid);
        
        $db->setQuery("SELECT * FROM #__jdownloads_logs WHERE id IN ($id)");
        $logs = $db->loadObjectList();
        if ($logs){
            $blacklist = $params->get('blocking_list');
            for ($i=0; $i < count($logs); $i++) {
                if (!stristr($blacklist, $logs[$i]->log_ip)){
                    if ($blacklist){
                        $blacklist = $blacklist."\n".$logs[$i]->log_ip; 
                    } else {
                        $blacklist = $logs[$i]->log_ip;
                    }
                    $total++;
                }    
            }
            if ($total){
                // update data
                JDownloadsHelper::changeParamSetting('blocking_list', $blacklist);
                return true;
            } else {
                return false;
            }   
        }
        return false;
    }   
}
?>