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
use Joomla\Database\DatabaseQuery;
use Joomla\CMS\MVC\Factory\MVCFactoryInterface;
use Joomla\Utilities\ArrayHelper;
use Joomla\CMS\Factory;
use Joomla\Database\ParameterType;
use Joomla\CMS\MVC\Model\ListModel;
use Joomla\Registry\Registry;

use JDownloads\Component\JDownloads\Administrator\Helper\AssociationsHelper;
use JDownloads\Component\JDownloads\Administrator\Helper\JDownloadsAssociationsHelper;

class CategoriesModel extends ListModel
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
                'cat_dir', 'a.cat_dir',
                'parent_id', 'a.parent_id',
                'title', 'a.title',
                'alias', 'a.alias',
                'lft', 'a.lft',
                'level', 'a.level',
                'description', 'a.description',
                'pic', 'a.pic',
                'access', 'a.access', 'access_level',
                'created_user_id', 'a.created_user_id',
                'created_time', 'a.created_time',
                'modified_user_id', 'a.modified_user_id',
                'modified_time', 'a.modified_time',
                'language', 'a.language', 'language_title',
                'views', 'a.views',
                'ordering', 'a.ordering',
                'published', 'a.published',
                'checked_out', 'a.checked_out',
                'checked_out_time', 'a.checked_out_time',
                'tag',
            );
        }
        
        if (Associations::isEnabled())
        {
            $config['filter_fields'][] = 'association';
        }

        parent::__construct($config);
    }


    /**
     * Method to auto-populate the model state.
     *
     * Note. Calling getState in this method will result in recursion.
     * 
     * @param   string  $ordering   An optional ordering field.
     * @param   string  $direction  An optional direction (asc|desc).
     *
     */
    protected function populateState($ordering = 'a.lft', $direction = 'asc')
    {
        $app = Factory::getApplication();
        
        $forcedLanguage = $app->input->get('forcedLanguage', '', 'cmd');
        
        // Adjust the context to support modal layouts.
        if ($layout = $app->input->get('layout')){
            $this->context .= '.' . $layout;
        }
        
        // Adjust the context to support forced languages.
        if ($forcedLanguage){
            $this->context .= '.' . $forcedLanguage;
        }
        
        $this->setState('filter.search', $this->getUserStateFromRequest($this->context . '.search', 'filter_search', '', 'string'));
        $this->setState('filter.published', $this->getUserStateFromRequest($this->context . '.filter.published', 'filter_published', '', 'string'));
        $this->setState('filter.language', $this->getUserStateFromRequest($this->context . '.filter.language', 'filter_language', '', 'string'));
        $this->setState('filter.level', $this->getUserStateFromRequest($this->context . '.filter.level', 'filter_level', '', 'string'));
        
        // New handling for multiple filters
        $formSubmited = $app->input->post->get('form_submited');

        $access     = $this->getUserStateFromRequest($this->context . '.filter.access', 'filter_access');
        $tag        = $this->getUserStateFromRequest($this->context . '.filter.tag', 'filter_tag', '');        
        
        if ($formSubmited){
            $access = $app->input->post->get('access');
            $this->setState('filter.access', $access);

            $tag = $app->input->post->get('tag');
            $this->setState('filter.tag', $tag);
        }        

        // Load the parameters.
        $params = ComponentHelper::getParams('com_jdownloads');
        $this->setState('params', $params);
        
        // List state information.
        parent::populateState($ordering, $direction);
        
        // Force a language.
        if (!empty($forcedLanguage)){
            $this->setState('filter.language', $forcedLanguage);
        }
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
     * @since    1.6
     */
    protected function getStoreId($id = '')
    {
        // Compile the store id.
        $id .= ':' . $this->getState('filter.search');
        $id .= ':' . serialize($this->getState('filter.access'));
        $id .= ':' . $this->getState('filter.published');
        $id .= ':' . $this->getState('filter.language');
        $id .= ':' . $this->getState('filter.level');
        $id .= ':' . serialize($this->getState('filter.tag'));        

        return parent::getStoreId($id);
    }

    /**
     * Build an SQL query to load the list data.
     *
     * @return    JDatabaseQuery
     * @since    1.6
     */
    protected function getListQuery()
    {
        $app = Factory::getApplication();
        $user = $app->getIdentity();
        
        // Create a new query object.
        $db = $this->getDbo();
        $query = $db->getQuery(true);
        
        // Select the required fields from the table.
        $query->select(
            $this->getState(
                'list.select',
                'a.id, a.cat_dir, a.cat_dir_parent, a.parent_id, a.level, a.lft, a.rgt, a.title, a.alias, a.description, a.pic, a.access, '  .
                'a.language, a.notes, a.ordering, a.published, a.created_user_id, a.checked_out, a.checked_out_time, a.asset_id'
            )
        );
        $query->from('`#__jdownloads_categories` AS a');

        // Join over the language
        $query->select('l.title AS language_title, l.image AS language_image')
            ->join('LEFT', $db->quoteName('#__languages') . ' AS l ON l.lang_code = a.language');

        // Join over the users for the checked out user.
        $query->select('uc.name AS editor')
            ->join('LEFT', '#__users AS uc ON uc.id=a.checked_out');

        // Join over the asset groups.
        $query->select('ag.title AS access_level')
            ->join('LEFT', '#__viewlevels AS ag ON ag.id = a.access');

        // Join over the users for the author.
        $query->select('ua.name AS author_name')
            ->join('LEFT', '#__users AS ua ON ua.id = a.created_user_id');
            
        // Join over the associations.
        $assoc = $this->getAssoc();

        if ($assoc)
        {
            $query->select('COUNT(asso2.id)>1 as association')
                ->join('LEFT', '#__associations AS asso ON asso.id = a.id AND asso.context=' . $db->quote('com_jdownloads.category.item'))
                ->join('LEFT', '#__associations AS asso2 ON asso2.key = asso.key')
                ->group('a.id, l.title, uc.name, ag.title, ua.name');
        }    

        // remove 'root' cat
        $query->where('a.id > 1');             
        
        // Filter on the level.
        if ($level = $this->getState('filter.level'))
        {
            $query->where('a.level <= ' . (int) $level);
        }

        // Filter by access level.
        $access = $this->getState('filter.access');

        if (is_numeric($access))
        {
            $query->where('a.access = ' . (int) $access);
        }
        elseif (is_array($access))
        {
            $access = ArrayHelper::toInteger($access);
            $access = implode(',', $access);
            $query->where('a.access IN (' . $access . ')');
        }
        
        // Implement View Level Access
        if (!$user->authorise('core.admin'))
        {
            $groups = implode(',', $user->getAuthorisedViewLevels());
            $query->where('a.access IN (' . $groups . ')');
        }

        // Filter by published state
        $published = $this->getState('filter.published');

        if (is_numeric($published))
        {
            $query->where('a.published = ' . (int) $published);
        }
        elseif ($published === '')
        {
            $query->where('(a.published IN (0, 1))');
        }

        // Filter by search in title
        $search = $this->getState('filter.search');

        if (!empty($search))
        {
            if (stripos($search, 'id:') === 0)
            {
                $query->where('a.id = ' . (int) substr($search, 3));
            }
            else
            {
                $search = $db->quote('%' . str_replace(' ', '%', $db->escape(trim($search), true) . '%'));
                $query->where('(a.title LIKE ' . $search . ' OR a.alias LIKE ' . $search . ' OR a.notes LIKE ' . $search . ')');
            }
        }

        // Filter on the language.
        if ($language = $this->getState('filter.language'))
        {
            $query->where('a.language = ' . $db->quote($language));
        }
        
                // Filter by a single or group of tags.
        $tag       = $this->getState('filter.tag');
        $typeAlias = 'com_jdownloads.category';

        // Run simplified query when filtering by one tag.
        if (\is_array($tag) && \count($tag) === 1)
        {
            $tag = $tag[0];
        }

        if ($tag && \is_array($tag))
        {
            $tag = ArrayHelper::toInteger($tag);

            $subQuery = $db->getQuery(true)
                ->select('DISTINCT ' . $db->quoteName('content_item_id'))
                ->from($db->quoteName('#__contentitem_tag_map'))
                ->where(
                    [
                        $db->quoteName('tag_id') . ' IN (' . implode(',', $query->bindArray($tag)) . ')',
                        $db->quoteName('type_alias') . ' = :typeAlias',
                    ]
                );

            $query->join(
                'INNER',
                '(' . $subQuery . ') AS ' . $db->quoteName('tagmap'),
                $db->quoteName('tagmap.content_item_id') . ' = ' . $db->quoteName('a.id')
            )
                ->bind(':typeAlias', $typeAlias);
        }
        elseif ($tag = (int) $tag)
        {
            $query->join(
                'INNER',
                $db->quoteName('#__contentitem_tag_map', 'tagmap'),
                $db->quoteName('tagmap.content_item_id') . ' = ' . $db->quoteName('a.id')
            )
                ->where(
                    [
                        $db->quoteName('tagmap.tag_id') . ' = :tag',
                        $db->quoteName('tagmap.type_alias') . ' = :typeAlias',
                    ]
                )
                ->bind(':tag', $tag, ParameterType::INTEGER)
                ->bind(':typeAlias', $typeAlias);
        }       
        
        // Add the list ordering clause
        $listOrdering = $this->getState('list.ordering', 'a.lft');
        $listDirn = $db->escape($this->getState('list.direction', 'ASC'));

        if ($listOrdering == 'a.access')
        {
            $query->order('a.access ' . $listDirn . ', a.lft ' . $listDirn);
        }
        else
        {
            $query->order($db->escape($listOrdering) . ' ' . $listDirn);
        }

        // Group by on Categories for JOIN with component tables to count items
        $query->group('a.id,
                a.title,
                a.alias,
                a.notes,
                a.published,
                a.access,
                a.checked_out,
                a.checked_out_time,
                a.created_user_id,
                a.cat_dir,
                a.parent_id,
                a.level,
                a.lft,
                a.rgt,
                a.pic,
                a.language,
                l.title,
                l.image,
                uc.name,
                ag.title,
                ua.name'
        );

        return $query;
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

        if (!\is_null($assoc))
        {
            return $assoc;
        }

        $assoc = Associations::isEnabled();

        if (!$assoc)
        {
            $assoc = false;
        }
        else
        {
        /*    \JLoader::register('JdownloadsAssociationsHelper', JPATH_ADMINISTRATOR . '/components/com_jdownloads/src/Helper/associations.php');

            $assoc = class_exists('JdownloadsAssociationsHelper');  
        */
        } 

        return $assoc;
    }
    
    /**
     * Method to get an array of data items.
     *
     * @return  mixed  An array of data items on success, false on failure.
     *
     */
    public function getItems()
    {
        $items = parent::getItems();

        if ($items != false)
        {   // count Download items
            $this->countItems($items);
        }
        return $items;
    }     
    
    /**
     * Adds Count Items for Categories
     *
     * @param   stdClass[]  &$items  
     * @return  stdClass[]
     */
    public static function countItems(&$items)
    {
        $db = Factory::getDbo();

        foreach ($items as $item)
        {
            $item->count_unpublished = 0;
            $item->count_published = 0;
            $query = $db->getQuery(true);
            $query->select('published, count(*) AS count')
                ->from($db->qn('#__jdownloads_files'))
                ->where('catid = ' . (int) $item->id)
                ->group('published');
            $db->setQuery($query);
            $downloads = $db->loadObjectList();

            foreach ($downloads as $download)
            {
                if ($download->published == 1)
                {
                    $item->count_published = $download->count;
                }

                if ($download->published == 0)
                {
                    $item->count_unpublished = $download->count;
                }
            }
        }
        return $items;
    }
    
}
?>