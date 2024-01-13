<?php
/**
* @version $Id: mod_jdownloads_admin_stats.php v3.8
* @package mod_jdownloads_admin_stats
* @copyright (C) 2018 Arno Betz
* @license http://www.gnu.org/copyleft/gpl.html GNU/GPL
* @author Arno Betz http://www.jDownloads.com
*/

namespace JDownloads\Module\JDownloadsAdminStats\Administrator\Helper;

defined( '_JEXEC' ) or die( 'Restricted access' );

use Joomla\CMS\Factory;
use Joomla\CMS\Helper\ModuleHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
use Joomla\Registry\Registry;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Access\Access;
use Joomla\CMS\Filesystem\File;

use JDownloads\Component\JDownloads\Administrator\Model\DownloadsModel;
use JDownloads\Component\JDownloads\Administrator\Helper\JDownloadsHelper;

BaseDatabaseModel::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_jdownloads/src/Model');
    
class JDownloadsAdminStatsHelper
{
    public static function getLatestItems($params)
    {
        $user = Factory::getApplication()->getIdentity();

        // Get an instance of the Downloads model
        $model = BaseDatabaseModel::getInstance('Downloads', 'jdownloads', array('ignore_request' => true));
        
        // Set List SELECT
        $model->setState('list.select', 'a.id, a.title, a.catid, a.checked_out, a.checked_out_time, ' .
            ' a.access, a.user_access, a.created, a.created_by, a.modified_by, a.featured, a.published, a.publish_up, a.publish_down');

        $model->setState('list.ordering', 'a.created');
        $model->setState('list.direction', 'DESC');
        
        // Set the Start and Limit
        $model->setState('list.start', 0);
        $model->setState('list.limit', $params->get('amount_items', 5));

        $items = $model->getItems();
        
        if (!$items) $items = array();
        
        // Set the links
        foreach ($items as &$item){
            if ($user->authorise('core.edit', 'com_jdownloads.download.' . $item->id)){
                $item->link = Route::_('index.php?option=com_jdownloads&task=download.edit&id=' . $item->id);
            } else {
                $item->link = '';
            }
            if ($user->authorise('core.edit', 'com_jdownloads.category.' . $item->catid)){
                $item->catlink = Route::_('index.php?option=com_jdownloads&task=category.edit&id=' . $item->catid);
            } else {
                $item->catlink = '';
            }
        }        
        
        return $items;
    }

    public static function getPopularItems($params)
    {
        $user = Factory::getApplication()->getIdentity();

        // Get an instance of the Downloads model
        $model = BaseDatabaseModel::getInstance('Downloads', 'jdownloads', array('ignore_request' => true));

        // Set List SELECT
        $model->setState('list.select', 'a.id, a.title, a.catid, a.downloads AS hits, a.views, a.checked_out, a.checked_out_time, ' .
            ' a.access, a.user_access, a.created, a.created_by, a.modified_by, a.featured, a.published, a.publish_up, a.publish_down');

        $model->setState('list.ordering', 'a.downloads');
        $model->setState('list.direction', 'DESC');
        
        // Set the Start and Limit
        $model->setState('list.start', 0);
        $model->setState('list.limit', $params->get('amount_items', 5));

        $items = $model->getItems();
        
        if (!$items) $items = array();

        // Set the links
        foreach ($items as &$item){
            if ($user->authorise('core.edit', 'com_jdownloads.download.' . $item->id)){
                $item->link = Route::_('index.php?option=com_jdownloads&task=download.edit&id=' . $item->id);
            } else {
                $item->link = '';
            }
            if ($user->authorise('core.edit', 'com_jdownloads.category.' . $item->catid)){
                $item->catlink = Route::_('index.php?option=com_jdownloads&task=category.edit&id=' . $item->catid);
            } else {
                $item->catlink = '';
            }
        }        
        
        return $items;
    }

    public static function getFeaturedItems($params)
    {
        $user = Factory::getApplication()->getIdentity();

        // Get an instance of the Downloads model
        $model = BaseDatabaseModel::getInstance('Downloads', 'jdownloads', array('ignore_request' => true));

        // Set List SELECT
        $model->setState('list.select', 'a.id, a.title, a.catid, a.checked_out, a.checked_out_time, ' .
            ' a.access, a.user_access, a.created, a.created_by, a.modified_by, a.featured, a.published, a.publish_up, a.publish_down');

        // Select only where featured field is set to '1'
        $model->setState('filter.featured', '1');           
        
        $model->setState('list.ordering', 'a.created');
        $model->setState('list.direction', 'DESC');
        
        // Set the Start and Limit
        $model->setState('list.start', 0);
        $model->setState('list.limit', $params->get('amount_items', 5));

        $items = $model->getItems();
        
        if (!$items) $items = array();

        // Set the links
        foreach ($items as &$item){
            if ($user->authorise('core.edit', 'com_jdownloads.download.' . $item->id)){
                $item->link = Route::_('index.php?option=com_jdownloads&task=download.edit&id=' . $item->id);
            } else {
                $item->link = '';
            }
            if ($user->authorise('core.edit', 'com_jdownloads.category.' . $item->catid)){
                $item->catlink = Route::_('index.php?option=com_jdownloads&task=category.edit&id=' . $item->catid);
            } else {
                $item->catlink = '';
            }
        }        
        
        return $items;
    }    
    
    public static function getMostRatedItems($params)
    {

        // Access filter
        $user = Factory::getApplication()->getIdentity();
        $groups = implode(',', array_unique($user->getAuthorisedViewLevels()));
        
        $db = Factory::getDBO();
        $query = "SELECT i.*, c.title AS category_title, v.name AS author, r.file_id, r.rating_count, round(( r.rating_sum / r.rating_count ) * 20) AS ratenum FROM #__jdownloads_files as i
        LEFT JOIN #__jdownloads_categories AS c ON c.id = i.catid
        INNER JOIN #__jdownloads_ratings AS r ON i.id = r.file_id 
        LEFT JOIN #__users AS v ON v.id = i.created_by
        WHERE i.access IN ('$groups') AND c.access IN ('$groups')
        ORDER BY rating_count DESC, ratenum DESC";
        $db->setQuery($query, 0, $params->get('amount_items', 5));
        
        $items = $db->loadObjectList();
        
        if (!$items) $items = array();
        
        // Set the links
        foreach ($items as &$item){
            if ($user->authorise('core.edit', 'com_jdownloads.download.' . $item->id)){
                $item->link = Route::_('index.php?option=com_jdownloads&task=download.edit&id=' . $item->id);
            } else {
                $item->link = '';
            }
            if ($user->authorise('core.edit', 'com_jdownloads.category.' . $item->catid)){
                $item->catlink = Route::_('index.php?option=com_jdownloads&task=category.edit&id=' . $item->catid);
            } else {
                $item->catlink = '';
            }
        }  
        return $items;
    }

    public static function getTopRatedItems($params)
    {

        // Access filter
        $user = Factory::getApplication()->getIdentity();
        $groups = implode(',', array_unique($user->getAuthorisedViewLevels()));
        
        $db = Factory::getDBO();
        $query = "SELECT i.*, c.title AS category_title, v.name AS author, r.file_id, r.rating_count, round(( r.rating_sum / r.rating_count ) * 20) AS ratenum FROM #__jdownloads_files as i
        LEFT JOIN #__jdownloads_categories AS c ON c.id = i.catid
        INNER JOIN #__jdownloads_ratings AS r ON i.id = r.file_id 
        LEFT JOIN #__users AS v ON v.id = i.created_by
        WHERE i.access IN ('$groups') AND c.access IN ('$groups') 
        ORDER BY ratenum DESC , rating_count DESC";
        $db->setQuery($query, 0, $params->get('amount_items', 5));
        
        $items = $db->loadObjectList();
        
        if (!$items) $items = array();
        
        // Set the links
        foreach ($items as &$item){
            if ($user->authorise('core.edit', 'com_jdownloads.download.' . $item->id)){
                $item->link = Route::_('index.php?option=com_jdownloads&task=download.edit&id=' . $item->id);
            } else {
                $item->link = '';
            }
            if ($user->authorise('core.edit', 'com_jdownloads.category.' . $item->catid)){
                $item->catlink = Route::_('index.php?option=com_jdownloads&task=category.edit&id=' . $item->catid);
            } else {
                $item->catlink = '';
            }
        }  
        return $items;
    }

    public static function getMonitoringLog()
    {
        if (!Factory::getUser()->authorise('core.admin', 'com_jdownloads')) return '';
        
        // get log file
        if (File::exists(JPATH_COMPONENT_ADMINISTRATOR.'/monitoring_logs.txt')){
            $log_file = file_get_contents(JPATH_COMPONENT_ADMINISTRATOR.'/monitoring_logs.txt');
        } else {
            $log_file = '';
        }
        return $log_file;
    }

    public static function getRestoreLog()
    {
        if (!Factory::getUser()->authorise('core.admin', 'com_jdownloads')) return '';
        
        // get restore log file
        if (File::exists(JPATH_COMPONENT_ADMINISTRATOR.'/restore_logs.txt')){
            $restore_log_file = file_get_contents(JPATH_COMPONENT_ADMINISTRATOR.'/restore_logs.txt');
        } else {
            $restore_log_file = '';
        } 
        return $restore_log_file;
    }

    public static function getInstallLog()
    {
        if (!Factory::getUser()->authorise('core.admin', 'com_jdownloads')) return '';   
        
        // get installation log file
        $log_file = Factory::getConfig()->get('log_path').'/com_jdownloads_install_logs.php';
        if (File::exists($log_file)){
            $install_log_file = file_get_contents($log_file);
            $install_log_file = nl2br($install_log_file);
            $install_log_file = str_replace('#<br />', '', $install_log_file);
            $install_log_file = str_replace("#<?php die('Forbidden.'); ?><br />", '', $install_log_file);    
        } else {
            $install_log_file = '';
        }
        return $install_log_file; 
    }
    
    public static function getStatistics()
    {
        $statistics = new \stdClass;
        
        $downloads = self::countDownloads();
        $statistics->num_total_downloads        = (int)$downloads->total;
        $statistics->num_published_downloads    = (int)$downloads->published;
        $statistics->num_unpublished_downloads  = (int)$downloads->unpublished;
        $statistics->num_featured               = (int)$downloads->featured;
        $statistics->sum_downloaded             = (int)$downloads->downloaded;
        
        $categories = self::countCategories();
        $statistics->num_total_categories       = (int)$categories->total;
        $statistics->num_published_categories   = (int)$categories->published;
        $statistics->num_unpublished_categories = (int)$categories->unpublished;
        
        $statistics->category_tags              = self::getCategoryTags();
        $statistics->download_tags              = self::getDownloadTags();
        
        return $statistics;
    }

    public static function countDownloads()
    {
        $db = Factory::getDBO();
        $query = "SELECT COUNT(*) AS total,
                         COUNT(NULLIF(published, '1')) AS unpublished,
                         COUNT(NULLIF(published, '0')) AS published,
                         COUNT(NULLIF(featured, '0'))  AS featured,
                         SUM(downloads)                AS downloaded 
                         FROM #__jdownloads_files";
        $db->setQuery($query);
        $result = $db->loadObject();
        return $result;
    }


    public static function countCategories()
    {
        $db = Factory::getDBO();
        $query = "SELECT COUNT(*) AS total,
                         COUNT(NULLIF(published, '1')) AS unpublished,
                         COUNT(NULLIF(published, '0')) AS published
                         FROM #__jdownloads_categories 
                         WHERE level > 0";
        $db->setQuery($query);
        $result = $db->loadObject();
        return $result;
    }    
    
    public static function getCategoryTags()
    {
        BaseDatabaseModel::addIncludePath(JPATH_SITE . '/components/com_tags/src/Model', 'TagsModel');
        
        $app = Factory::getApplication();
        $db = Factory::getDbo();

        //$model = BaseDatabaseModel::getInstance ('Tags', 'TagsModel', array('ignore_request' => true));
        $model = BaseDatabaseModel::getInstance('Tags', 'TagsModel', array('ignore_request' => true));
        
        // Set application parameters in model
        $appParams = ComponentHelper::getParams('com_tags');
        $model->setState('params', $appParams);
       
        // Set the filters based on the module params
        $model->setState('list.start', 0);
        //$model->setState('list.limit', (int) $params->get('sum_view', 5));
        $model->setState('filter.published', 1);

        // Access filter
        $access = !ComponentHelper::getParams('com_jdownloads')->get('show_noauth');
        $authorised = Access::getAuthorisedViewLevels(Factory::getUser()->get('id'));
        $model->setState('filter.access', $access);

        // User filter
        $userId = Factory::getUser()->get('id');

        // Filter by language
        $model->setState('filter.language', '*');

        // Set sort ordering
        $ordering = 'a.title';
        $dir = 'ASC';

        $model->setState('list.ordering', $ordering);
        $model->setState('list.direction', $dir);

        $items = $model->getItems();
        
        if (!$items) $items = array();

        if (count($items)){
            $items = JDownloadsHelper::countTagItems($items, 'com_jdownloads.category');
            return $items;
        } else {
            return '';
        }
    }
    
    public static function getDownloadTags()
    {
        BaseDatabaseModel::addIncludePath(JPATH_SITE . '/components/com_tags/src/Model', 'TagsModel');
        
        $app = Factory::getApplication();
        $db = Factory::getDbo();

        // Get an instance of the generic downloads model
        //$model = BaseDatabaseModel::getInstance ('Tags', 'TagsModel', array('ignore_request' => true));
        $model = BaseDatabaseModel::getInstance('Tags', 'TagsModel', array('ignore_request' => true));
        
        // Set application parameters in model
        $appParams = ComponentHelper::getParams('com_tags');
        $model->setState('params', $appParams);
       
        // Set the filters based on the module params
        $model->setState('list.start', 0);
        //$model->setState('list.limit', (int) $params->get('sum_view', 5));
        $model->setState('filter.published', 1);

        // Access filter
        $access = !ComponentHelper::getParams('com_jdownloads')->get('show_noauth');
        $authorised = Access::getAuthorisedViewLevels(Factory::getUser()->get('id'));
        $model->setState('filter.access', $access);

        // User filter
        $userId = Factory::getUser()->get('id');

        // Filter by language
        $model->setState('filter.language', '*');

        // Set sort ordering
        $ordering = 'a.title';
        $dir = 'ASC';

        $model->setState('list.ordering', $ordering);
        $model->setState('list.direction', $dir);

        $items = $model->getItems();
        
        if (!$items) $items = array();

        if (count($items)){
            $items = JDownloadsHelper::countTagItems($items, 'com_jdownloads.download');
            return $items;
        } else {
            return '';
        }
        
    }
    
    public static function getTemplates()
    {
        $db = Factory::getDBO();
        $query = "SELECT name FROM #__extensions WHERE type = 'template' AND client_id = 0 AND enabled = 1";
        $db->setQuery($query);
        $result = $db->loadColumn();
        if ($result){
            return $result;  
        } else {
            return array();
        }
    }
 
    public static function findTextInArray(array &$array, $text) {
        $keys = [];
        foreach ($array as $key => &$value) {
            if (strpos($value, $text) !== false) {
                $keys[] = $key;
            }
        }
        return $keys;
    } 
    
    public static function getMainMenuItem() {
        $db = Factory::getDBO();
        $query = "SELECT title FROM #__menu WHERE published = 1 AND link = 'index.php?option=com_jdownloads&view=categories' AND client_id = 0";
        $db->setQuery($query);
        $result = $db->loadColumn();
        if ($result){
            return $result;  
        } else {
            return '';
        }
    }
 
    public static function checkSystemPlugin() {
        $db = Factory::getDBO();
        $query = "SELECT enabled FROM #__extensions WHERE type = 'plugin' AND name = 'plg_system_jdownloads'";
        $db->setQuery($query);
        $result = $db->loadResult();
        
        if (!$result){
            // Activate it again
            $db = Factory::getDBO();
            $query = "UPDATE #__extensions SET `enabled` = 1 WHERE type = 'plugin' AND name = 'plg_system_jdownloads'";
            $db->setQuery($query);
            $update = $db->execute();
            return $result;  
        } else {
            return $result;
        }        
    } 
    
}