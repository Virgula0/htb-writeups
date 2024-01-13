<?php
/**
 * @package jDownloads
 * @version 4.0
 * @copyright (C) 2007 - 2023 - Arno Betz - www.jdownloads.com
 * @license http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.txt
 *
 * jDownloads is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 */

namespace JDownloads\Component\JDownloads\Site\Helper; 
 
\defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Component\Router\Rules\RulesInterface;

use JDownloads\Component\JDownloads\Site\Helper\JDHelper;
use JDownloads\Component\JDownloads\Site\Helper\RouteHelper;

use \AllowDynamicProperties;

/**
 * Legacy routing rules class from com_jdownloads
 *
 */
#[AllowDynamicProperties] 
class LegacyRouter implements RulesInterface
{
    /**
     * Constructor for this legacy router
     *
     * @param   JComponentRouterView  $router  The router this rule belongs to
     *
     * @since       3.8
     */
    public function __construct($router)
    {
        $this->router = $router;
    }

    /**
     * Preprocess the route for the com_jdownloads component
     *
     * @param   array  &$query  An array of URL arguments
     *
     * @return  void
     *
     */
    public function preprocess(&$query){

    }

    /**
     * Build the route for the com_jdownloads component
     *
     * @param   array  &$query     An array of URL arguments
     * @param   array  &$segments  The URL arguments to use to assemble the subsequent URL.
     *
     * @return  void
     *
     */
    public function build(&$query, &$segments){

        // Get all alias from categories and downloads
        if (!isset($this->all_cat_aliases)){
            $db = Factory::getDbo();
            $aquery = $db->setQuery($db->getQuery(true)
                ->select('alias, id')
                ->from('#__jdownloads_categories')
                ->where('id > 1')
                ->order($db->quoteName('id')));
            $this->all_cat_aliases = $row = $db->loadAssocList('id');
            
        }
            
        if (!isset($this->all_files_aliases)){
            $db = Factory::getDbo();
            $aquery = $db->setQuery($db->getQuery(true)
                ->select('alias, id')
                ->from('#__jdownloads_files')
                ->order($db->quoteName('id')));
            $this->all_files_aliases = $row = $db->loadAssocList('id');
            
        }
        
        // Get a menu item based on Itemid or currently active
        $params = ComponentHelper::getParams('com_jdownloads');
        
        // $query['Itemid'] must always be an integer value
        if (isset($query['Itemid'])){
            if (is_array($query['Itemid'])){
                $query['Itemid'] = (int) $query['Itemid'][0]; 
            }
        }

        // We need a menu item.  Either the one specified in the query, or the current active one if none specified
        if (empty($query['Itemid'])){
            $menuItem = $this->router->menu->getActive();
            $query['Itemid'] = $menuItem->id;
            $menuItemGiven = false;
        } else {
            $menuItem = $this->router->menu->getItem($query['Itemid']);
            $menuItemGiven = true;
        }

        // Check again, as we need a link to the download overview. A link from another component would be invalid.
        if ($menuItemGiven && isset($menuItem) && $menuItem->component != 'com_jdownloads'){
            // Invalid menu item
            $menuitems = $this->router->menu->getItems(array(), array());
            foreach ($menuitems as $menuitem){
                if ($menuitem->link == 'index.php?option=com_jdownloads&view=categories'){
                    $menuItem = $this->router->menu->getItem($menuitem->id);
                    $query['Itemid'] = $menuItem->id;
                    break;
                } 
            }
        }

        if (isset($query['view'])){
            $view = $query['view'];
        } else {
            // We need to have a view in the query or it is an invalid URL
            return;
        }
     
        // Categories page
        if ($view == 'categories'){
            if (!$menuItemGiven) {
                $segments[] = $view;
            }
            unset($query['view']);
        }

        // Downloads list (all or only uncategorised)
        if ($view == 'downloads'){
            if (!$menuItemGiven) {
                $segments[] = $view;
            }

            if (isset($query['type']) && $query['type'] == 'uncategorised'){
                $segments[] = $query['type'];
                unset($query['type']);
            } else {
                $segments[] = 'all';
            }
            unset($query['view']);
        }

        // Category page
        if ($view == 'category'){
            $segments[] = $view;
            unset($query['view']);

            if (isset($query['catid'])) {
                if (strpos($query['catid'], ':') === false) {
                    $cat_id = (int) $query['catid'];
                    if (array_key_exists($cat_id, $this->all_cat_aliases)){
                    	$query['catid'] = $query['catid'].':'.$this->all_cat_aliases[$cat_id]['alias'];
                	}
                }

                $segments[] = $query['catid'];
                unset($query['catid']);
            } else {
			    // We should have id set for this view.  If we don't, it is an error
			    return $segments;
		    }
        }

        // Download page (single item)
        if ($view == 'download'){
            $segments[] = $view;
            unset($query['view']);

            if (isset($query['catid'])) {
                if (strpos($query['catid'], ':') === false) {
                    $cat_id = (int) $query['catid'];
                    $query['catid'] = $query['catid'].':'.$this->all_cat_aliases[$cat_id]['alias'];
                }

                $segments[] = $query['catid'];
                unset($query['catid']);
            }

            if (isset($query['id'])) {
                // Make sure we have the id and the alias
                if (strpos($query['id'], ':') === false) {
                    $file_id = (int) $query['id'];
                    $query['id'] = $query['id'].':'.$this->all_files_aliases[$file_id]['alias'];
                }
                $segments[] = $query['id'];
                unset($query['id']);
            } else {
                // We should have id set for this view.  If we don't, it is an error
                return $segments;
            }
        }

		// MyDownloads list
		if ($view == 'mydownloads'){
			if (!$menuItemGiven) {
				$segments[] = $view;
			}
			unset($query['view']);        
		}    
		
		// My download history
		if ($view == 'myhistory'){
			if (!$menuItemGiven) {
				$segments[] = $view;
			}
			unset($query['view']);        
		}   		
		
        // Search page
        if ($view == 'search'){
            $segments[] = $view;
            unset($query['view']);
        }

        // Summary page
        if ($view == 'summary'){
            $segments[] = $view;
            unset($query['view']);

            if (isset($query['catid'])) {
                if (strpos($query['catid'], ':') === false) {
                    $cat_id = (int) $query['catid'];
                    $query['catid'] = $query['catid'].':'.$this->all_cat_aliases[$cat_id]['alias'];
                }

                $segments[] = $query['catid'];
                unset($query['catid']);
            }

            if (isset($query['id'])) {
                // Make sure we have the id and the alias
                if (strpos($query['id'], ':') === false) {
                    $file_id = (int) $query['id'];
                    $query['id'] = $query['id'].':'.$this->all_files_aliases[$file_id]['alias'];
                }
                $segments[] = $query['id'];
                unset($query['id']);
            } else {
                // We should have id set for this view.  If we don't, it is an error
                return $segments;
            }

            // Mirror link
            if (isset($query['m']) && $query['m'] > 0){
                $segments[] = (int)$query['m'];
                unset($query['m']);
            }

        }

        // Report page
        if ($view == 'report'){
            $segments[] = $view;
            unset($query['view']);

            if (isset($query['catid'])) {
                if (strpos($query['catid'], ':') === false) {
                    $cat_id = (int) $query['catid'];
                    $query['catid'] = $query['catid'].':'.$this->all_cat_aliases[$cat_id]['alias'];
                }

                $segments[] = $query['catid'];
                unset($query['catid']);
            }

            if (isset($query['id'])) {
                // Make sure we have the id and the alias
                if (strpos($query['id'], ':') === false) {
                    $file_id = (int) $query['id'];
                    $query['id'] = $query['id'].':'.$this->all_files_aliases[$file_id]['alias'];
                }
                $segments[] = $query['id'];
                unset($query['id']);
            } else {
                // We should have id set for this view.  If we don't, it is an error
                return $segments;
            }
        }

        // Survey page
        if ($view == 'survey'){
            $segments[] = $view;
            unset($query['view']);

            if (isset($query['catid'])) {
                if (strpos($query['catid'], ':') === false) {
                    $cat_id = (int) $query['catid'];
                    $query['catid'] = $query['catid'].':'.$this->all_cat_aliases[$cat_id]['alias'];
                }

                $segments[] = $query['catid'];
                unset($query['catid']);
            }

            if (isset($query['id'])) {
                // Make sure we have the id and the alias
                if (strpos($query['id'], ':') === false) {
                    $file_id = (int) $query['id'];
                    $query['id'] = $query['id'].':'.$this->all_files_aliases[$file_id]['alias'];
                }
                $segments[] = $query['id'];
                unset($query['id']);
            } else {
                // We should have id set for this view.  If we don't, it is an error
                return $segments;
            }
        }

	    // If the layout is specified and it is the same as the layout in the menu item, we
	    // unset it so it doesn't go into the query string.
	    if (isset($query['layout'])) {
		    if ($menuItemGiven && isset($menuItem->query['layout'])) {
			    if ($query['layout'] == $menuItem->query['layout']) {
				    unset($query['layout']);
                    unset($query['view']);
			    }
		    } else {
			    if ($query['layout'] == 'edit') {
				    //unset($query['layout']);
			    }
		    }
	    }

        // Send download task
        if (isset($query['task']) && $query['task'] == 'download.send'){
             $segments[] = 'send';
             unset($query['task']);

            if (isset($query['catid'])) {
                if (strpos($query['catid'], ':') === false) {
                    $cat_id = (int) $query['catid'];
                    $query['catid'] = $query['catid'].':'.$this->all_cat_aliases[$cat_id]['alias'];
                }

                $segments[] = $query['catid'];
                unset($query['catid']);
            }

            if (isset($query['id'])) {
                // Make sure we have the id and the alias
                if (strpos($query['id'], ':') === false) {
                    $file_id = (int) $query['id'];
                    $query['id'] = $query['id'].':'.$this->all_files_aliases[$file_id]['alias'];
                }
                $segments[] = $query['id'];
                unset($query['id']);
            }

            if (isset($query['m']) && $query['m'] > 0){
                $segments[] = (int)$query['m'];
                unset($query['m']);
            } else {
                unset($query['m']);
            }

            if (isset($query['list'])){
                $value = preg_match("/[0-9,]+/", $query['list']);
                if ($value){
                    $segments[] = $query['list'];
                    unset($query['list']);
                }
            }

            if (isset($query['user'])){
                $segments[] = (int)$query['user'];
                unset($query['user']);
            }
        }

        $total = count($segments);

        for ($i = 0; $i < $total; $i++){
            $segments[$i] = str_replace(':', '-', $segments[$i]);
        }

	    return $segments;
    }

    /**
     * Parse the segments of a URL.
     *
     * @param   array  &$segments  The segments of the URL to parse.
     * @param   array  &$vars      The URL attributes to be used by the application.
     *
     * @return  void
     *
     * @since       3.8
     * @deprecated  4.0
     */
    public function parse(&$segments, &$vars){

        require_once JPATH_SITE . '/components/com_jdownloads/src/Helper/RouteHelper.php';

        $menuid = JDHelper::getMenuItemids();
        $item = $this->router->menu->getItem($menuid['root']);
        
        $total = count($segments);

        for ($i = 0; $i < $total; $i++)
        {
            $segments[$i] = preg_replace('/-/', ':', $segments[$i], 1);
        }

        // Get the active menu item.
        //$item = $this->router->menu->getActive();
        $params = ComponentHelper::getParams('com_jdownloads');
        $advanced = $params->get('sef_advanced_link', 0);
        $db = Factory::getDbo();

        // Count route segments
        $count = count($segments);

        /*
         * Standard routing for downloads.  If we don't pick up an Itemid then we get the view from the segments
         * the first segment is the view and the last segment is the id of the download or category.
         */
        if (!isset($item))
        {
            $vars['view'] = $segments[0];
            $vars['id'] = $segments[$count - 1];

            return;
        }

        // We use the old part from 3.2
        
        switch($segments[0])
        {
            case 'categories' :
                    $vars['view']   = 'categories';
            break;

            case 'uncategorised' :
                   $vars['view']    = 'downloads';
                   $vars['type']    = 'uncategorised';
            break;

            case 'all' :
                   $vars['view']    = 'downloads';
                   $vars['type']    = 'all';
            break;

            case 'mydownloads' :
                    $vars['view']   = 'mydownloads';
            break;

            case 'myhistory' :
                    $vars['view']   = 'myhistory';
            break;			
			
            case 'category'   :
                    $vars['view']   = $segments[$count - 2];
                    $vars['catid']  = (int)$segments[$count - 1];
            break;

            case 'download'   :
                    $vars['view']   = 'download';
                    $vars['catid']  = (int)$segments[$count - 2];
                    $vars['id']     = (int)$segments[$count - 1];
                    unset($segments[0]);
                    unset($segments[$count - 1]);
                    unset($segments[$count - 2]);

            break;

            case 'summary'   :
                    $vars['view']   = 'summary';
                    if ($count > 1){
                        $vars['catid']  = (int)$segments[$count - 2];
                        $vars['id']     = (int)$segments[$count - 1];
                    }
            break;

            case 'report'   :
                    $vars['view']   = 'report';
                    $vars['catid']  = (int)$segments[$count - 2];
                    $vars['id']     = (int)$segments[$count - 1];
            break;

            case 'survey'   :
                    $vars['view']   = 'survey';
                    $vars['catid']  = (int)$segments[$count - 2];
                    $vars['id']     = (int)$segments[$count - 1];
            break;


            case 'search'   :
                if($count == 1) {
                    $vars['view']   = 'search';
                }
            break;

            case 'send'   :
                    $vars['task']   = 'download.send';
                    $single_file = true;
                    foreach ($segments as $segment){
                        if (strpos($segment, ',')){
                            $single_file = false;
                        }
                    }
                    if (!$single_file){
                        // Mass download
                        $vars['catid']  = (int)$segments[1];
                        $vars['list']   = $segments[2];
                        $vars['user']   = (int)$segments[3];
                    } else {
                        // Single download
                        $vars['catid']  = (int)$segments[1];
                        $vars['id']     = (int)$segments[2];
                        if (isset($segments[3]) && $segments[3] > 0){
                            $vars['m']  = (int)$segments[3];
                        }
                    }

            break;
        }

        if (isset($segments[0])) unset($segments[0]);
        if (isset($segments[1])) unset($segments[1]);
        if (isset($segments[2])) unset($segments[2]);
        if (isset($segments[3])) unset($segments[3]);
        
	    return $vars;
    }
}
?>