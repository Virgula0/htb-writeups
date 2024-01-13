<?php
defined('_JEXEC') or die;

use Joomla\CMS\Router\Route;
use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Date\Date;
use Joomla\CMS\Uri\Uri;

use JDownloads\Component\JDownloads\Site\Helper\JDHelper;
use JDownloads\Module\JDownloadsTree\Site\Helper\JDownloadsTreeHelper;

    // Creating new tree object
    $tree = new jdownloadstree();    

    // Let's append categories & sub categories to the tree        
    foreach ($rows as $row) {
        
        if(strlen($row->title) > $lengthc){
            $row->title  = substr($row->title,0,($lengthc - 3));
            $row->title .= "...";
        }                                      
    
        if ($row->menu_itemid){  
            $Itemid = $row->menu_itemid;
        } else {
            $Itemid = $root_itemid;
        }           
       
        if ($row->link == '-'){
            // User has the permissions to view the category
            $catlink = Route::_('index.php?option=com_jdownloads&amp;view=category&amp;catid='. $row->id .'&amp;Itemid='. $Itemid);
        } else {
            // Link to the login page
            $catlink = $row->link;
        }
        $tree->addToArray($row->id, $row->title, $row->parent_id, $catlink, '', $row->numitems, $row->subcatitems);

        if ($row->id > $nodeId){
            $nodeId = $row->id;
        }   // Get max id
    }
   
    $nodeId++;
     
    // Draw the tree
    $livesite = Uri::root();
    $tree->writeJavascript($livesite);
    $tree->drawTree($home_link, $moduleclass_sfx, $params);
    // $tree->applyStyle(); No longer required as CSS is in jdownloads_module.css
?>