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

use JDownloads\Component\JDownloads\Administrator\Table\JDCategoryTable;
use JDownloads\Component\JDownloads\Administrator\Table\DownloadTable;

class DownloadsModel extends ListModel
{
     /**
     * Constructor.
     *
     * @param    array    An optional associative array of configuration settings.
     */
    public function __construct($config = array())
    {
        if (empty($config['filter_fields'])){
            $config['filter_fields'] = array(
                'id', 'a.id',
                'title', 'a.title',
                'alias', 'a.alias',
                'description', 'a.description',
                'description_long', 'a.description_long',
                'file_pic', 'a.file_pic',
                'price', 'a.price',
                'release', 'a.release',
                'file_language', 'a.file_language',
                'system', 'a.system',
                'license', 'a.license',
                'url_license', 'a.url_license',
                'license_agree', 'a.license_agree',
                'size', 'a.size',
                'created', 'a.created',
                'file_date', 'a.file_date',
                'publish_up', 'a.publish_up',
                'publish_down', 'a.publish_down',
                'url_download', 'a.url_download',
                'preview_filename', 'a.preview_filename',
                'other_file_id', 'a.other_file_id',
                'extern_file', 'a.extern_file',                
                'extern_site', 'a.extern_site',                                                                
                'mirror_1', 'a.mirror_1',
                'mirror_2', 'a.mirror_2',
                'extern_site_mirror_1', 'a.extern_site_mirror_1',
                'extern_site_mirror_2', 'a.extern_site_mirror_2',
                'url_home', 'a.url_home',
                'author', 'a.author',
                'url_author', 'a.url_author',
                'created_by', 'a.created_by',
                'modified_by', 'a.modified_by',
                'modified', 'a.modified',
                'downloads', 'a.downloads',
                'catid', 'a.catid', 'category_title',
                'notes', 'a.notes',
                'changelog', 'a.changelog',
                'password', 'a.password',
                'views', 'a.views',
                'update_active', 'a.update_active',
                'access', 'a.access', 'access_level',
                'user_access', 'a.user_access',
                'language', 'a.language',
                'ordering', 'a.ordering',
                'featured', 'a.featured',                                                                
                'published', 'a.published',                                                
                'tag',
                'author_id',
                'category_id',
            );
            
            // Added to support the Joomla Language Associations
            if (Associations::isEnabled()){
                $config['filter_fields'][] = 'association';
            }
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
     * @return  void
     *
     * @since   1.6
     */
    protected function populateState($ordering ='a.id', $direction = 'desc')
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
        
        $search = $this->getUserStateFromRequest($this->context . '.filter.search', 'filter_search');
        $this->setState('filter.search', $search);        

        $published = $this->getUserStateFromRequest($this->context . '.filter.published', 'filter_published', '');
        $this->setState('filter.published', $published);

        $level = $this->getUserStateFromRequest($this->context . '.filter.level', 'filter_level');
        $this->setState('filter.level', $level);

        $language = $this->getUserStateFromRequest($this->context . '.filter.language', 'filter_language', '');
        $this->setState('filter.language', $language);

        $featured = $this->getUserStateFromRequest($this->context . '.filter.featured', 'filter_featured', '');
        $this->setState('filter.featured', $featured);
        
        $formSubmitted = $app->input->post->get('form_submitted');

        // Gets the value of a user state variable and sets it in the session
        $this->getUserStateFromRequest($this->context . '.filter.access', 'filter_access');
        $this->getUserStateFromRequest($this->context . '.filter.author_id', 'filter_author_id');
        $this->getUserStateFromRequest($this->context . '.filter.category_id', 'filter_category_id');
        $this->getUserStateFromRequest($this->context . '.filter.tag', 'filter_tag', '');

        if ($formSubmitted){
            $access = $app->input->post->get('access');
            $this->setState('filter.access', $access);

            $authorId = $app->input->post->get('author_id');
            $this->setState('filter.author_id', $authorId);

            $categoryId = $app->input->post->get('category_id');
            $this->setState('filter.category_id', $categoryId);

            $tag = $app->input->post->get('tag');
            $this->setState('filter.tag', $tag);
        }
        
        // Load the parameters.
        $params = ComponentHelper::getParams('com_jdownloads');
        $this->setState('params', $params);
        
        // List state information.
        parent::populateState($ordering, $direction);
        
        // Force a language
        if (!empty($forcedLanguage)){
            $this->setState('filter.language', $forcedLanguage);
            $this->setState('filter.forcedLanguage', $forcedLanguage);
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
     */
    protected function getStoreId($id = '')
    {
        // Compile the store id.
        $id .= ':'.$this->getState('filter.search');
        $id .= ':' . serialize($this->getState('filter.access'));
        $id .= ':' . $this->getState('filter.published');
        $id .= ':' . $this->getState('filter.featured');
        $id .= ':' . serialize($this->getState('filter.category_id'));
        $id .= ':' . serialize($this->getState('filter.author_id'));
        $id .= ':' . $this->getState('filter.language');
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
        $app  = Factory::getApplication();
        $user = $app->getIdentity();
        
        // Create a new query object.
        $db        = $this->getDbo();
        $query     = $db->getQuery(true);
        
        $app        = Factory::getApplication();
        $modal      = $app->getUserState( 'jd_modal' );
        $modaltype  = $this->get('context');
        
        // We have three different uses - so we need a check
        $editor_field = $app->input->get('editor');
        $function = $app->input->get('function');
        
        $modal_edit_file_id = (int)$app->getUserState( 'jd_edit_file_id' );
        
        // Select the required fields from the table.
        $query->select(
            $this->getState(
                'list.select',
                'DISTINCT a.id, a.title, a.alias, a.description, a.file_pic, a.price, a.release, a.catid, a.images, '.
                'a.size, a.created, a.created_by, a.modified, a.modified_by, a.publish_up, a.publish_down, a.url_download, a.preview_filename, a.other_file_id, a.extern_file, a.downloads, '.
                'a.extern_site, a.notes, a.password, a.access, a.user_access, a.language, a.checked_out, a.checked_out_time, a.ordering, a.featured, a.published, a.asset_id'
            )
        );
        $query->from('`#__jdownloads_files` AS a');
        
        // Join over the users for the checked out user. 
        $query->select('uc.name AS editor');
        $query->join('LEFT', '#__users AS uc ON uc.id=a.checked_out');
        
        // Join over the files for other selected file
        $query->select('f.url_download AS other_file_name, f.title AS other_download_title');
        $query->join('LEFT', $db->quoteName('#__jdownloads_files').' AS f ON f.id = a.other_file_id');
        
        // Join over the language
        $query->select('l.title AS language_title, l.image AS language_image');
        $query->join('LEFT', $db->quoteName('#__languages').' AS l ON l.lang_code = a.language');
       
        // Join over the asset groups.
        $query->select('ag.title AS access_level');
        $query->join('LEFT', '#__viewlevels AS ag ON ag.id = a.access');        
        
        // Join over the categories.
        $query->select('c.title AS category_title, c.created_user_id AS category_uid, c.level AS category_level');
        $query->join('LEFT', '#__jdownloads_categories AS c ON c.id = a.catid');
        
        // Join over the parent categories.
        $query->select('parent.title AS category_title_parent, parent.id AS parent_category_id, parent.created_user_id AS parent_category_uid, parent.level AS parent_category_level');
        $query->join('LEFT', '#__jdownloads_categories AS parent ON parent.id = c.parent_id');
        
        // Join over the users for the author.
        $query->select('ua.name AS author_name');
        $query->join('LEFT', '#__users AS ua ON ua.id = a.created_by');       
        
        // Get the username when the access is assigned only to a single user
        $query->select('u3.username AS single_user_access, u3.name AS single_user_access_name');
        $query->join('LEFT', '#__users AS u3 on u3.id = a.user_access');        
        
        // Filter by published state
        $published = $this->getState('filter.published');
        if (is_numeric($published)){
            $query->where('a.published = ' . (int) $published);
        } elseif ($published === ''){
            $query->where('(a.published = 0 OR a.published = 1)');
        }
        
        // Filter by featured state
        $featured = $this->getState('filter.featured');
        if (is_numeric($featured)){
            $query->where('a.featured = ' . (int) $featured);
        } elseif ($featured === '' || $featured === 'all'){
            $query->where('(a.featured = 0 OR a.featured = 1)');
        }        
        
        // Filter by categories and by level
        $categoryId = $this->getState('filter.category_id', array());
        $level = (int) $this->getState('filter.level');

        if (!is_array($categoryId)){
            $categoryId = $categoryId ? array($categoryId) : array();
        }

        // Case: Using both categories filter and by level filter
        if (count($categoryId) && (int)$categoryId[0] != 0){
            $categoryId = ArrayHelper::toInteger($categoryId);
            $categoryTable = Table::getInstance('JDCategoryTable', 'JDownloads\\Component\\JDownloads\\Administrator\\Table\\');
            $subCatItemsWhere = array();

            foreach ($categoryId as $key => $filter_catid){
                $categoryTable->load($filter_catid);

                // Because values to $query->bind() are passed by reference, using $query->bindArray() here instead to prevent overwriting.
                $valuesToBind = [$categoryTable->lft, $categoryTable->rgt];

                if ($level){
                    $valuesToBind[] = $level + $categoryTable->level - 1;
                }

                // Bind values and get parameter names.
                $bounded = $query->bindArray($valuesToBind);

                $categoryWhere = $db->quoteName('c.lft') . ' >= ' . $bounded[0] . ' AND ' . $db->quoteName('c.rgt') . ' <= ' . $bounded[1];

                if ($level){
                    $categoryWhere .= ' AND ' . $db->quoteName('c.level') . ' <= ' . $bounded[2];
                }

                $subCatItemsWhere[] = '(' . $categoryWhere . ')';
            }

            $query->where('(' . implode(' OR ', $subCatItemsWhere) . ')');
        
        } elseif ($level = (int) $level){
        
            // Case: Using only the by level filter
            $query->where($db->quoteName('c.level') . ' <= :level')
                ->bind(':level', $level, ParameterType::INTEGER);
        }
        
        // Added to support the Joomla Language Associations
        $assogroup = 'a.id, l.title, l.image, uc.name, ag.title, c.title, ua.name, c.created_user_id, c.level';
        
        // Join over the associations.
        if (Associations::isEnabled())
        {
            $query->select('CASE WHEN COUNT(asso2.id)>1 THEN 1 ELSE 0 END as association')
                ->join('LEFT', '#__associations AS asso ON asso.id = a.id AND asso.context=' . $db->quote('com_jdownloads.item'))
                ->join('LEFT', '#__associations AS asso2 ON ' . $db->quoteName('asso2.key') . ' = ' . $db->quoteName('asso.key'))
                ->group($assogroup);
        }
        
        // Filter by access level.
        $access = $this->getState('filter.access');

        if (is_numeric($access)){
            $query->where('a.access = ' . (int) $access);
        } elseif (is_array($access)){
            $access = ArrayHelper::toInteger($access);
            $access = implode(',', $access);
            $query->where('a.access IN (' . $access . ')');
        }
        
        // Filter by access level on categories.
        if (!$user->authorise('core.admin')) {
            $groups = $user->getAuthorisedViewLevels();
            $query->whereIn($db->quoteName('a.access'), $groups);
            $query->whereIn($db->quoteName('c.access'), $groups);
        }

        // Filter by author
        $authorId = $this->getState('filter.author_id');

        if (is_numeric($authorId)) {
            $type = $this->getState('filter.author_id.include', true) ? '= ' : '<>';
            $query->where('a.created_by ' . $type . (int) $authorId);
        } elseif (\is_array($authorId)) {
            $authorId = ArrayHelper::toInteger($authorId);
            $query->whereIn($db->quoteName('a.created_by'), $authorId);
        }
        
        // Filter by search in title
        $search = $this->getState('filter.search');
        if (!empty($search)){
            if (stripos($search, 'id:') === 0) {
                $query->where('a.id = '.(int) substr($search, 3));
            }   elseif (stripos($search, 'author:') === 0){
                $search = $db->quote('%' . $db->escape(substr($search, 7), true) . '%');
                $query->where('(ua.name LIKE ' . $search . ' OR ua.username LIKE ' . $search . ')');
            }   elseif (stripos($search, 'version:') === 0){
                $search = $db->quote('%' . $db->escape(substr($search, 8), true) . '%');
                $query->where('(a.release LIKE ' . $search . ')');
            } else {
                $search = $db->Quote('%'.$db->escape(trim($search, true).'%'));
                $query->where('(a.title LIKE '.$search.' OR a.description LIKE '.$search.' OR a.description_long LIKE '.$search.' OR a.notes LIKE '.$search.')');
            }
        }                                                   

        // Filter on the language.
        if ($language = $this->getState('filter.language')) {
            $query->where('a.language = ' . $db->quote($language));
        }
        
        // Filter by a single or group of tags.
        $tag = $this->getState('filter.tag');

        // Run simplified query when filtering by one tag.
        if (\is_array($tag) && \count($tag) === 1){
            $tag = $tag[0];
        }

        if ($tag && \is_array($tag)){
            $tag = ArrayHelper::toInteger($tag);

            $subQuery = $db->getQuery(true)
                ->select('DISTINCT ' . $db->quoteName('content_item_id'))
                ->from($db->quoteName('#__contentitem_tag_map'))
                ->where(
                    [
                        $db->quoteName('tag_id') . ' IN (' . implode(',', $query->bindArray($tag)) . ')',
                        $db->quoteName('type_alias') . ' = ' . $db->quote('com_jdownloads.download'),
                    ]
                );

            $query->join(
                'INNER',
                '(' . $subQuery . ') AS ' . $db->quoteName('tagmap'),
                $db->quoteName('tagmap.content_item_id') . ' = ' . $db->quoteName('a.id')
            );
        
        } else if ($tag = (int) $tag){
        
            $query->join(
                'INNER',
                $db->quoteName('#__contentitem_tag_map', 'tagmap'),
                $db->quoteName('tagmap.content_item_id') . ' = ' . $db->quoteName('a.id')
            )
                ->where(
                    [
                        $db->quoteName('tagmap.tag_id') . ' = :tag',
                        $db->quoteName('tagmap.type_alias') . ' = ' . $db->quote('com_jdownloads.download'),
                    ]
                )
                ->bind(':tag', $tag, ParameterType::INTEGER);
        }        
        
        // Used only for 'modal' selection lists when it is not run in jDownloads editor plugin or in the menu selection.
        if ($editor_field !== 'jform_description' && $function !== 'jSelectDownload_jform_request_id' 
            && $function !== 'jSelectArticle_jform_request_id' && $editor_field !== 'jform_articletext'){
                                                                                                                       
            // View only downloads with an assigned file or extern link 
            if ($modal && $modaltype == 'com_jdownloads.downloads.modal'){
                if ($modal_edit_file_id > 0){
                    $query->where("a.id != '$modal_edit_file_id'");
                }
                $query->where("a.url_download != '' || a.extern_file != ''");
            }
        }

        // Add the list ordering clause.
        $orderCol    = $this->state->get('list.ordering', 'a.id');
        $orderDirn   = $this->state->get('list.direction', 'desc');
        
        $query->order($db->escape($orderCol) . ' ' . $db->escape($orderDirn));
        return $query;
    }

    /**
     * Build a list of authors
     *
     * @return  stdClass
     */
    public function getAuthors()
    {
        // Create a new query object.
        $db = $this->getDbo();
        $query = $db->getQuery(true);

        // Construct the query
        $query->select('u.id AS value, u.name AS text')
            ->from('#__users AS u')
            ->join('INNER', '#__jdownloads_files AS c ON c.created_by = u.id')
            ->group('u.id, u.name')
            ->order('u.name');

        // Setup the query
        $db->setQuery($query);

        // Return the result
        return $db->loadObjectList();
    }
    
    /**
     * Method to get a list of Downloads
     * Overridden to add a check for access levels.
     *
     * @return  mixed  An array of data items on success, false on failure.
     *
     */
    public function getItems()
    {
        $app = Factory::getApplication();
        $user = $app->getIdentity();
        
        $items = parent::getItems();
        
        $amount_previews = (int)self::getAmountPreviews();
        $this->state->set('amount_previews', $amount_previews);
        
        if ($app->isClient('site')){
            $groups = $app->getIdentity()->getAuthorisedViewLevels();

            for ($x = 0, $count = count($items); $x < $count; $x++){
                // Check the access level. Remove Downloads the user shouldn't see
                if (!in_array($items[$x]->access, $groups)){
                    unset($items[$x]);
                }
            }
        }

        return $items;
    }
    
    /**
    * Get the amount of preview files in jDownloads files table
    * 
    * @return  int
    */
    public function getAmountPreviews(){
        
        // Create a new query object.
        $db = $this->getDbo();
        $query = $db->getQuery(true);
        
        $query->select('COUNT(*) AS amount_previews')
        ->from('#__jdownloads_files')
        ->where('preview_filename != '.$db->quote(''));
        
        // Setup the query
        $db->setQuery($query);

        // Return the result
        return $db->loadResult();
    }
    
}
?>