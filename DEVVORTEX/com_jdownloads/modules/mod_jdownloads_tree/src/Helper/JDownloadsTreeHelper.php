<?php
/**
* @version $Id: mod_jdownloads_tree.php v3.8
* @package mod_jdownloads_tree
* this has functions: getList - writeJavascript - Get_Cookie - Set_Cookie - expandNode - initTree - addToArray
*       - drawSubNode - drawTree - 
*/
namespace JDownloads\Module\JDownloadsTree\Site\Helper;

\defined( '_JEXEC' ) or die;

use Joomla\CMS\Access\Access;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Router\Route;
use Joomla\Registry\Registry;
use Joomla\Utilities\ArrayHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Model\DatebaseModel;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;

use JDownloads\Component\JDownloads\Site\Helper\RouteHelper;
use JDownloads\Component\JDownloads\Site\Model\DownloadsModel;
use JDownloads\Component\JDownloads\Site\Model\CategoriesModel;

abstract class JDownloadsTreeHelper
{
    public static function getList($params)
    {
        $db = Factory::getDbo();

        // Get an instance of the generic downloads model
		BaseDatabaseModel::addIncludePath(JPATH_SITE .'/components/com_jdownloads/src/Model');
		$model = BaseDatabaseModel::getInstance('Categories', 'jdownloads');

        // Set application parameters in model
        $app = Factory::getApplication();
        $appParams = $app->getParams('com_jdownloads');
        $model->setState('params', $appParams);
       
        // Set the filters based on the module params
        //$model->setState('list.start', 0);
        //$model->setState('list.limit', (int) $params->get('sum_view', 5));
        $model->setState('filter.published', 1);

        // Access filter
        $model->setState('filter.access', true);
        $model->setState('filter.user_access', true);
        
        $authorised = Access::getAuthorisedViewLevels(Factory::getUser()->get('id'));

        // Category filter
        
        // Category display decisions
        $catid        = $params->get('catid');
        $catoption    = intval( $params->get('catoption', 1 ) );
        if ($catid){
            $catid = implode(',', $catid);
            if ($catoption == 1){
                $catid = '1,'.$catid;
            }
            $cat_condition = 'c.id '.($catoption ? ' IN ':' NOT IN ') .'(' . $catid . ') ';
            $model->setState('filter.category_id', $cat_condition);
        } else {
            $model->setState('filter.category_id', '');
        }       
        
        $level = intval( $params->get('maxlevel', 0 ) );
        $model->setState('filter.level', $level);

        // Filter by language
        $model->setState('filter.language', $app->getLanguageFilter());

        // Set sort ordering
        $ordering = 'c.lft';
        $dir = 'ASC';

        $model->setState('list.ordering', $ordering);
        $model->setState('list.direction', $dir);

        $items = $model->getItems(true, true);  // with childrens // $no_parent_id = true

        if ($items){
            foreach ($items as &$item){
                $item->catslug = $item->id . ':' . $item->alias;

                if (in_array($item->access, $authorised)){
                    // We know that user has the privilege to view the download
                    $item->link = '-';
                } else {
                    $item->link = Route::_('index.php?option=com_users&view=login');
                }
            }
        }
        
        return $items;        
    }


 }
  
?>    	
