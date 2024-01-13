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

class LicensesModel extends ListModel
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
                'title', 'a.title',
                'alias', 'a.alias',
                'description', 'a.description',
                'url', 'a.url',
                'language', 'a.language',
                'checked_out', 'a.checked_out',
                'checked_out_time', 'a.checked_out_time',
                'published', 'a.published',
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
    protected function populateState($ordering = 'a.ordering', $direction = 'asc')
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
        
        // Load the published filter state.
        $published = $this->getUserStateFromRequest($this->context . '.filter.published', 'filter_published', '');
        $this->setState('filter.published', $published);

        // Load the language state.
        $language = $this->getUserStateFromRequest($this->context.'.filter.language', 'filter_language', '');
        $this->setState('filter.language', $language);

        // Load the parameters.
        $params = ComponentHelper::getParams('com_jdownloads');
        $this->setState('params', $params);

        // List state information.
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
        $id.= ':' . $this->getState('filter.published');
        $id.= ':' . $this->getState('filter.language');

        return parent::getStoreId($id);
    }

    /**
     * Build an SQL query to load the list data.
     *
     * @return    JDatabaseQuery
     */
    protected function getListQuery()
    {
        $app = Factory::getApplication();
        $user = $app->getIdentity();
        
        // Create a new query object.
        $db        = $this->getDbo();
        $query     = $db->getQuery(true);
        
        // Select the required fields from the table.
        $query->select(
            $this->getState(
                'list.select',
                'a.id, a.title, a.alias, a.description, a.url, a.language, '  .
                'a.checked_out, a.checked_out_time, a.published, a.ordering'
            )
        );
        $query->from('`#__jdownloads_licenses` AS a');
        
        // Join over the users for the checked out user.
        $query->select('uc.name AS editor');
        $query->join('LEFT', '#__users AS uc ON uc.id=a.checked_out');

        // Join over the language
        $query->select('l.title AS language_title, l.image AS language_image');
        $query->join('LEFT', $db->quoteName('#__languages').' AS l ON l.lang_code = a.language');
        
        // Filter by search in title
        $search = $this->getState('filter.search');
        if (!empty($search)) {
            if (stripos($search, 'id:') === 0) {
                $query->where('a.id = '.(int) substr($search, 3));
            } else {
                $search = $db->Quote('%'.$db->escape($search, true).'%');
                $query->where('(a.title LIKE '.$search.' OR a.description LIKE '.$search.' OR a.alias LIKE '.$search.')');
            }
        }

        // Filter on the language.
        if ($language = $this->getState('filter.language')) {
            $query->where('a.language = ' . $db->quote($language));
        }

        // Filter by published state
        $published = $this->getState('filter.published');
        if (is_numeric($published)) {
            $query->where('a.published = '.(int) $published);
        } else if ($published === '') {
            $query->where('(a.published IN (0, 1))');
        }
        
        // Add the list ordering clause.
        $listOrdering = $this->getState('list.ordering', 'a.ordering');
        $listDirn     = $db->escape($this->getState('list.direction', 'ASC'));
        $query->order($db->escape($listOrdering.' '.$listDirn));
        
        return $query;
    }

}
?>