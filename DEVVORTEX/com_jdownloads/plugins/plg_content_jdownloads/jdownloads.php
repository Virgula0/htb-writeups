<?php
/**
* jDownloads Content Plugin
* Version 4.0 
* For Joomla 4.0 and higher
* Original created by Marco Pelozzi - marco.u3@bluewin.ch - www.redorion.com
* Modified and reworked by Arno Betz - jDownloads.com - 2018 - 2022
*  
* Usage:
*  {jd_file file==*ID}                  where *ID stands for the ID-number of the download in jDownloads.
*  {jd_file category==1 count==5}       category==1 is the chosen category id. count==5 is die number of viewed downloads from this category, when it is 0 all files are listed.
*  {jd_file cat_hottest==1 count==5}    Views the 5 most downloaded files from a given category ID.
*  {jd_file cat_latest==1 count==5}     Views the 5 last added files from a given category ID.
*  {jd_file latest==5}                  Views the 5 newest downloads.
*  {jd_file hottest==5}                 Views the 5 top downloads.
*  {jd_file updated==5}                 Views the 5 last updated downloads.
*  {jd_file onlinelayout==layout name}  Additional to the placeholders above, you can use this to select for the view a different file layout. 
*                                       So it is possible to use in the same content various layouts. 
*                                       Example:  {jd_file onlinelayout==Simple File List}{jd_file latest==5}
*
* 
* 
*/

defined('_JEXEC') or die;

setlocale(LC_ALL, 'C.UTF-8', 'C');

use Joomla\String\StringHelper;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Multilanguage;
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\CMS\Router\Route;
use Joomla\Database\ParameterType;
use Joomla\Registry\Registry;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;
use Joomla\Event\Event;
use Joomla\Component\Fields\Administrator\Helper\FieldsHelper;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\Filesystem\File;
use Joomla\CMS\Filesystem\Folder;
use Joomla\CMS\Helper\TagsHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Layout\FileLayout;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Helper\UserGroupsHelper;

use JDownloads\Component\JDownloads\Site\Helper\JDHelper;
use JDownloads\Component\JDownloads\Site\Helper\RouteHelper;

$classname = 'JDownloads\Component\JDownloads\Site\Helper\JDHelper'; 

if (!class_exists($classname)) {
    $path = JPATH_SITE . '/components/com_jdownloads/src/Helper/JDHelper.php'; 
    if (is_file($path)) {
        include_once $path;
        \JLoader::register($classname, $path); 
    } else {
        return false;
    }
}

global $cat_link_itemidsPlg;

$db = Factory::getDBO();
$app = Factory::getApplication();
 
$jdContentType='';

if ($app->isClient('site')){

    // Get all published single category menu links
    $db->setQuery("SELECT id, link from #__menu WHERE link LIKE 'index.php?option=com_jdownloads&view=category%' AND published = 1");
    $cat_link_itemidsPlg = $db->loadAssocList();
    if ($cat_link_itemidsPlg){
        for ($i=0; $i < count($cat_link_itemidsPlg); $i++){
             $cat_link_itemidsPlg[$i]['catid'] = substr( strrchr ( $cat_link_itemidsPlg[$i]['link'], '=' ), 1);
        }    
    }
    
    // get current category menu ID when exist and all needed menu IDs for the header links
    global $menuItemids;
    $menuItemids = JDHelper::getMenuItemids();
    
    // get all other menu category IDs so we can use it when we needs it
    global $cat_link_itemids;
    $cat_link_itemids = JDHelper::getAllJDCategoryMenuIDs();
    
    // "Home" menu link itemid
    global $root_itemid;
    $root_itemid =  $menuItemids['root'];
    
    global $date_format;
    $date_format = JDHelper::getDateFormat();    

    //Globals definition
    $GLOBALS['jDFPOnlineLayout'] = '';
    $GLOBALS['params'] = ComponentHelper::getParams('com_jdownloads');
    $GLOBALS['jDownloadsMessage'] = 0;
    $GLOBALS['jDownloadsTested'] = 0;
    $GLOBALS['jDFPsfolders'] = jd_SymbolFolders();
    $GLOBALS['jDFPrank'] = 1;
    $GLOBALS['jDFPison'] = 1;
    $GLOBALS['jDFPcatids'] = '';
    $GLOBALS['jDFPloaded'] = 0;
    $GLOBALS['jDLayoutTitleExists'] = false;
}

class PlgContentJDownloads extends CMSPlugin
{

    function __construct(&$subject, $article_params)
    {
        parent::__construct($subject, $article_params);
    }

    function onContentPrepare($context, &$article, &$article_params)
    {
        global $app, $jDFPplugin_live_site, $jDFPloaded, $params;         
        
        if ($app->isClient('site')){        
       
            // Simple performance check to determine whether bot should process further
            if (isset($article->text)){
                if (strpos($article->text, '{jd_file') === false) {
                    return;
                }
            } else {
                return;
            }
            
            // Load language
            $lang = Factory::getLanguage();
            $lang->load('com_jdownloads', JPATH_SITE);
            
            // Live site
            $GLOBALS['jDFPlive_site'] = Uri::base();
            // Live site of plugin
            $GLOBALS['jDFPplugin_live_site'] = $GLOBALS['jDFPlive_site'].'plugins/';

            // Absolute path
            $GLOBALS['jDFPabsolute_path'] = JPATH_SITE.'/';
         
            $ipad_user = false;
            // check whether we have an ipad/iphone user for flowplayer aso...
            if ((bool) strpos($_SERVER['HTTP_USER_AGENT'], 'iPad') || (bool) strpos($_SERVER['HTTP_USER_AGENT'], 'iPhone') || (bool) strpos($_SERVER['HTTP_USER_AGENT'], 'Android')){        
                $ipad_user = true;
            }
            
            $document = Factory::getDocument(); 
            
            if ($jDFPloaded == 0){
                
                // Load all required assets files.
                // In special cases, the files might have already been loaded. This is the case if another download was embedded within a download via jDownloads content plugin.
                // @TODO: This should be replaced by the use of the webAssetManager in a later version.
                if ($params->get('load_frontend_css')){
                    // Check if asset file has already been loaded
                    $already_loaded = checkAssetAlreadyLoaded('components/com_jdownloads/assets/css/jdownloads_fe.css', 'css', $document);
                    if (!$already_loaded){
                    $document->addStyleSheet( Uri::base().'components/com_jdownloads/assets/css/jdownloads_fe.css', 'text/css', null, array() );
                    }
                } else {
                    // Handling with optionally own css file
                    if ($params->get('own_css_file')){
                        $own_css_path = JPATH_ROOT.'/components/com_jdownloads/assets/css/'.$params->get('own_css_file');
                        if (File::exists($own_css_path)){
                            $already_loaded = checkAssetAlreadyLoaded($params->get('own_css_file'), 'css', $document);
                            if (!$already_loaded){
                            $document->addStyleSheet( Uri::base().'components/com_jdownloads/assets/css/'.$params->get('own_css_file'), "text/css", null, array() );
                        }
                    }
                } 
                } 
                
                // Check if asset file has already been loaded
                $already_loaded = checkAssetAlreadyLoaded('components/com_jdownloads/assets/css/jdownloads_buttons.css', 'css', $document);
                if (!$already_loaded){
                
                $document->addStyleSheet( Uri::base().'components/com_jdownloads/assets/css/jdownloads_buttons.css', 'text/css', null, array() );
                }
                
                if ($params->get('view_ratings')){
                    // Check if asset file has already been loaded
                    $already_loaded = checkAssetAlreadyLoaded('components/com_jdownloads/assets/rating/css/ajaxvote.css', 'css', $document);
                    if (!$already_loaded){
                    $document->addStyleSheet( Uri::base().'components/com_jdownloads/assets/rating/css/ajaxvote.css', "text/css", null, array() );         
                }
                }
                
                if ($params->get('use_lightbox_function')){
                    // Only when lightbox is activated in jD
                    HTMLHelper::_('jquery.framework');
                    
                    // Check if asset file has already been loaded
                    $already_loaded = checkAssetAlreadyLoaded('components/com_jdownloads/assets/lightbox/src/js/lightbox.js', 'js', $document);
                    if (!$already_loaded){
                    $document->addScript(Uri::base().'/components/com_jdownloads/assets/lightbox/src/js/lightbox.js');
                    $document->addStyleSheet(Uri::base().'components/com_jdownloads/assets/lightbox/src/css/lightbox.css', 'text/css', null, array() );
                }
                }
                
                $custom_css_path = JPATH_ROOT.'/components/com_jdownloads/assets/css/jdownloads_custom.css';
                if (File::exists($custom_css_path)){
                    // Check if asset file has already been loaded
                    $already_loaded = checkAssetAlreadyLoaded('components/com_jdownloads/assets/css/jdownloads_custom.css', 'css', $document);
                    if (!$already_loaded){
                    $document->addStyleSheet( Uri::base().'components/com_jdownloads/assets/css/jdownloads_custom.css', 'text/css', null, array() );                
                }                
                
                }                
                
                // Loadscript for flowplayer
                if ($params->get('flowplayer_use')){
                    // Check if asset file has already been loaded
                    $already_loaded = checkAssetAlreadyLoaded('components/com_jdownloads/assets/flowplayer/flowplayer-3.2.12.min.js', 'js', $document);
                    if (!$already_loaded){
                    $document->addScript(Uri::base().'components/com_jdownloads/assets/flowplayer/flowplayer-3.2.12.min.js');
                    }
                }
                
                // Add rating script
                if ($params->get('view_ratings')){
                    // Check if asset file has already been loaded
                    $already_loaded = checkAssetAlreadyLoaded('components/com_jdownloads/assets/rating/js/ajaxvote.js', 'js', $document);
                    if (!$already_loaded){
                    $document->addScript(Uri::base().'components/com_jdownloads/assets/rating/js/ajaxvote.js');
                }  
                                
            }     
                                
            }     
            
            $jDFPloaded = 1;

            $regex = "#{jd_file (.*?)==(.*?)}#s";
            $article->text = preg_replace_callback($regex, 'jd_file_callback', $article->text);

            return true;
        }
    }
}

    // Calculate Symbolfolders depending on jDownloads
    function jd_SymbolFolders(){
        global $params;
       
        // Get the path to the activated mime type image folder (for file symbols) 
        switch ($params->get('selected_file_type_icon_set'))
        {
            case 2:
                $file_pic_folder = 'images/jdownloads/fileimages/flat_1/';
                break;
            case 3:
                $file_pic_folder = 'images/jdownloads/fileimages/flat_2/';
                break;
            default:
                $file_pic_folder = 'images/jdownloads/fileimages/';
                break;
        }
       
        $jd_l_folders                    = array();
        $jd_l_folders['thumb']           = 'images/jdownloads/screenshots/thumbnails/';
        $jd_l_folders['screenshot']      = 'images/jdownloads/screenshots/';
        $jd_l_folders['symbolfolder']    = 'images/jdownloads/';
        $jd_l_folders['cat']             = 'images/jdownloads/catimages/';
        $jd_l_folders['file']            = $file_pic_folder;
        $jd_l_folders['mini']            = 'images/jdownloads/miniimages/';
        $jd_l_folders['featured']        = 'images/jdownloads/featuredimages/';
        return $jd_l_folders;
    }

    function jd_file_callback($matches){
        global $jDownloadsTested, $jDownloadsMessage, $params, $jDFPOnlineLayout, $jDFPrank, $jDFPison, $jdContentType;
        $db =  Factory::getDBO();
        $jdf_whatcontent = $matches[1];
	    $jdContentType='';

        if ($jdf_whatcontent == 'plugin'){
            switch ($matches[2]){
	            case 'on':
                    $jDFPison = 1;
                    break;
	            case 'off':
                    $jDFPison = 0;
                    break;
	            case 'silent':
                    $jDFPison = 2;
                    break;
            }
            return '';
        }
        if ($jDFPison == 0){
	        return $matches[0];
        }
        if ($jDFPison == 2){
	        return '';
        }

        // Load layout
        if ($jDFPOnlineLayout == '') {
            $jDFPOnlineLayout = $params->get('fileplugin_defaultlayout');
        }
        
        switch ($jdf_whatcontent) {
            case 'file':
                $jDFPrank = '';
		        $jdContentType='';
                $id_result = jd_file_createdownload($matches);
                break;
            case 'durl':
                $jDFPrank = '';
                $id_result = jd_file_createdownload($matches);
                break;
            case 'onlinelayout':
                jd_set_newlayout($matches);
                $id_result = '';
                break;
            case 'latest':
		        $jdContentType='jd_content_latest';
                $id_result =jd_file_latest_hottest($matches);
                break;
            case 'hottest':
		        $jdContentType='jd_content_hottest';
                $id_result = jd_file_latest_hottest($matches);
                break;
            case 'updated':
		        $jdContentType='jd_content_updated';
                $id_result = jd_file_latest_updated($matches);
                break;       
            case 'category':
		        $jdContentType='jd_content_category';
                $id_result = jd_file_createcategory($matches, '');
                break;
            case 'cat_hottest':
		        $jdContentType='jd_content_category_hottest';
                $id_result = jd_file_createcategory($matches, 'hottest');
                break;
            case 'cat_latest':
		        $id_result = jd_file_createcategory($matches, 'latest');
		        $jdContentType='jd_content_category_latest';
                break;        
        }
        return $id_result;
    }

    function jd_set_newlayout($matches){
        global $jDFPOnlineLayout;
        
        $jDFPOnlineLayout = strip_tags($matches[2]);
        return '';
    }

    // Create a list with all valid category IDs which has the correct access settings 
    function getCategoryIDs($p_subcat){
        global $jDFPcatids;
      
        $db   =  Factory::getDBO();
        $user = Factory::getUser();
      
        $query  = $db->getQuery(true);
        $groups = implode(',', $user->getAuthorisedViewLevels());
              
        $db->setQuery("SELECT id FROM #__jdownloads_categories WHERE published = 1 AND access IN ($groups) ORDER BY ordering");
        $rows = $db->loadObjectList('id'); 
        if ($rows){
            foreach ($rows as $row){
                $jDFPcatids .= $row->id.',';
            }
            $jDFPcatids = substr($jDFPcatids, 0, -1); 
        }
    }

    // Create the output list for the last updated Downloads
    function jd_file_latest_updated($matches){
       global $jDFPrank, $jDFPcatids, $params;
       
       $db =  Factory::getDBO();
       $user = Factory::getUser();
      
       $query  = $db->getQuery(true);
       $groups = implode(',', $user->getAuthorisedViewLevels());
       
       $jDFPcatids = '';
       $bidon = getCategoryIDs(0);

       $days = $params->get('days_is_file_updated');
       if (!$days) $days = 15;

       $until_day = mktime(0,0,0,date("m"), date("d")-$days, date("Y"));
       $until = date('Y-m-d H:m:s', $until_day);

       $filesql ="SELECT id FROM #__jdownloads_files WHERE catid IN (".$jDFPcatids.") AND (update_active = 1) AND (modified >= '.$until.') AND access IN ($groups) AND published = 1 ORDER BY {dado} DESC LIMIT ".$db->escape($matches[2]).";";
       
       if ($matches[1] == 'updated'){
           $filesql = str_replace("{dado}",'modified',$filesql);
       } else {
           $filesql = str_replace("{dado}",'downloads',$filesql);
       }

       $db->setQuery($filesql);
       $files = $db->loadObjectList();
       
       $filetable = '';
       $jDFPrank = 1;
       
       if ($files){
           foreach ($files as $thefile){
               $sim_matches = array("", "file", $thefile->id);
               $filetable .= jd_file_createdownload($sim_matches);
               $jDFPrank++;
           }
       }
       return $filetable;
    }

    // Create the output list for the hottest or latest Downloads
    function jd_file_latest_hottest($matches){
        global $jDFPrank, $jDFPcatids;

        $db =  Factory::getDBO();
        $user = Factory::getUser();
      
        $query  = $db->getQuery(true);
        $groups = implode(',', $user->getAuthorisedViewLevels());
       
        $jDFPcatids = '';
        $bidon = getCategoryIDs(0);

        $filesql = "SELECT id FROM #__jdownloads_files WHERE catid IN (".$jDFPcatids.") AND access IN ($groups) AND published = 1 ORDER BY {dado} DESC LIMIT ".$db->escape($matches[2]).";";
      
        if ($matches[1] == 'latest'){
            $filesql = str_replace("{dado}",'created',$filesql);
        } else {
            $filesql = str_replace("{dado}",'downloads',$filesql);
        }

        $db->setQuery($filesql);
        $files = $db->loadObjectList();
       
        $filetable = '';
        $jDFPrank = 1;
       
        if ($files){
   	        foreach ($files as $file){
   		        $sim_matches = array("", "file", $file->id);
                // create the output
                $filetable .= jd_file_createdownload($sim_matches);
                $jDFPrank++;
   	        }
        }
        return $filetable;
    }

    // Build the data output for a file placeholder 
    function jd_file_createdownload($matches){
        global $params, $jDFPOnlineLayout, $jDFPsfolders, $jdContentType;
          
        $app = Factory::getApplication();
        $db = Factory::getDBO();
        $user = Factory::getUser();

        // Import jDownloads site model
        BaseDatabaseModel::addIncludePath(JPATH_SITE . '/components/com_jdownloads/src/Model');
        $model_download = BaseDatabaseModel::getInstance('Download', 'jdownloads');
        
        // Get the data from the model
        $fileid = (int) $matches[2];
        $item = $model_download->getItem($fileid, true); // we use the second param as switch for plugin == true
          
        if (!$item) {
            // we will give not any informations in the frontend
            return '';
              
            /*
            // Not possible to get this download 
            $jd_filepic = Uri::base().'/plugins/content/jdownloads/jdownloads/images/offline.gif';
            $jd_filetitle = str_replace("{fileid}",$matches[2],Text::_('COM_JDOWNLOADS_FRONTEND_SETTINGS_FILEPLUGIN_FILEUNKNOWN'));
            $jd_template = jd_file_fill_nodownload($jd_template, $jd_filetitle, '', $jd_filepic);
            
            if ($params->get('fileplugin_enable_plugin') == 0) {
    	        if ($params->get('fileplugin_show_jdfiledisabled') == 0){
                    $jd_template = '';
    	        }
            }
            return $jd_template;
            */
        }
          
        if (!isset($item->params)){
            // something went wrong as we need the params
            return '';
        }
        
        // Check the 'view access' rights
        $access = $item->params->get('access-view');
        if (!$access){
            return '';
        }

        // Check the 'download' permissions
        $download = $item->params->get('access-download');
        if (!$download){
            // Not allowed 
            $download_allowed = 0;
        } else {
            // Allowed
            $download_allowed = 1;
        }

        // Check the 'edit' permissions
        $edit = $item->params->get('access-edit');
        if (!$edit){
            // Not allowed 
            $edit_allowed = 0;
        } else {
            // Allowed
            $edit_allowed = 1;
        }
          
        // Get tags
        $item->tags = new TagsHelper;
        $item->tags->getItemTags('com_jdownloads.download', $item->id);
          
      
        // Load the layout
        $jdLayout = $jDFPOnlineLayout;
      
        $query = "SELECT * FROM #__jdownloads_templates WHERE (template_name = ".$db->quote($db->escape($jdLayout)).") AND (template_typ = 2)";
        $db->setQuery($query);
        $layout = $db->loadObject();

        // It was not possible to load the layout - abort
        if (!$layout) {
            $ReturnValue = str_replace("{thelayout}", $jdLayout, Text::_('COM_JDOWNLOADS_FRONTEND_SETTINGS_FILEPLUGIN_LAYOUTUNKNOWN')).'<br />';
            return $ReturnValue;
        }

        $jd_template              = $layout->template_text;
        $jd_template_header       = $layout->template_header_text;
        $jd_template_subheader    = $layout->template_subheader_text;
        $jd_template_footer       = $layout->template_footer_text;
        $jd_template_symbol_off   = $layout->symbol_off;
      
        $jd_template = $layout->template_before_text.$jd_template.$layout->template_after_text;

        // required for other content plugins
        $item->text = '';
        
        // Process the content plugins - and get the Joomla Fields when in use
        PluginHelper::importPlugin('content');
        $app->triggerEvent('onContentPrepare', ['com_jdownloads.download', &$item, &$params, 0]);

        $item->event = new \stdClass;
        $results = $app->triggerEvent('onContentAfterTitle', array('com_jdownloads.download', &$item, &$params, 0));
        $item->event->afterDisplayTitle = trim(implode("\n", $results));
        
        $results = $app->triggerEvent('onContentBeforeDisplay', array('com_jdownloads.download', &$item, &$params, 0));
        $item->event->beforeDisplayContent = trim(implode("\n", $results));

        $results = $app->triggerEvent('onContentAfterDisplay', array('com_jdownloads.download', &$item, &$params, 0));
        $item->event->afterDisplayContent = trim(implode("\n", $results));
        
        // Plugin enabled or disabled
        if ($params->get('fileplugin_enable_plugin') == 0){
            if ($params->get('fileplugin_show_jdfiledisabled') == 0){
                $jd_template = '';
            } else {
                $jd_filetitle = JDHelper::getOnlyLanguageSubstring($params->get('fileplugin_offline_title'));
                $jd_filepic = 'plugins/content/jdownloads/jdownloads/images/offline.gif';
                $jd_filedescription = JDHelper::getOnlyLanguageSubstring($params->get('fileplugin_offline_descr'));
                if ($params->get('fileplugin_show_downloadtitle') == 1){
                    $jd_filetitle = $item->title;
                    $jd_filepic = $jDFPsfolders['file'].$item->file_pic;
                    $jd_filedescription = $params->get('fileplugin_offline_title').'&nbsp;'.$params->get('fileplugin_offline_descr');
                }
                $jd_template = jd_file_fill_nodownload($jd_template,$jd_filetitle,$jd_filedescription,$jd_filepic);
            }
            return $jd_template;
        }

        $jd_template = jd_file_fill_downloadok($jd_template, $item, $jd_template_symbol_off, $matches[1], $download_allowed, $edit_allowed);
        return '<div class="jd_content jd_content_plugin '.$jdContentType.'">'.$jd_template.'</div>';
    }
    

    function jd_file_fill_nodownload($p_Template, $p_Title, $p_Description, $p_Filepic){
      global $params, $jDFPplugin_live_site, $jDFPlive_site;

      $l_Template = str_replace("{{{","[[[",$p_Template);
      $jd_file_pic = '<img src="'.$jDFPlive_site.$p_Filepic.'" align="absmiddle" border="0" height="'.$params->get('file_pic_size').'" width="'.$params->get('file_pic_size').'" alt="'.substr(strrchr($p_Filepic,"/"),1,-4).'"/>';
      $l_Template = str_replace("{file_pic}",$jd_file_pic,$l_Template);
      $jd_file_pic = '<img src="'.$jDFPplugin_live_site.'content/jdownloads/jdownloads/images/nodownload.gif">';
      $l_Template = str_replace("{checkbox_list}",$jd_file_pic,$l_Template);
      $l_Template = str_replace("{file_title}",$p_Title,$l_Template);
      $l_Template = str_replace("{file_title_only}",$p_Title,$l_Template);
      $l_Template = str_replace("{description}",$p_Description,$l_Template);
      $l_Template = str_replace("{release}",'',$l_Template);
      $l_Template = str_replace("{release_title}",'',$l_Template);
      $l_Template = str_replace("{size}",'',$l_Template);
      $l_Template = str_replace("{downloads}",'',$l_Template);
      $l_Template = str_replace("{pic_is_new}",'',$l_Template);
      $l_Template = str_replace("{pic_is_hot}",'',$l_Template);
      $l_Template = str_replace("{license}",'',$l_Template);
      $l_Template = str_replace("{date_added}",'',$l_Template);
      $l_Template = str_replace("{language}",'',$l_Template);
      $l_Template = str_replace("{system}",'',$l_Template);
      $l_Template = str_replace("{url_download}",'',$l_Template);
      $l_Template = str_replace("{file_id}",'',$l_Template);
      $l_Template = str_replace("{ordering}",'',$l_Template);
      $l_Template = str_replace("{published}",'',$l_Template);
      $l_Template = str_replace("{cat_id}",'',$l_Template);
      $l_Template = str_replace("{mirror_1}",'',$l_Template);
      $l_Template = str_replace("{mirror_2}",'',$l_Template);
      $l_Template = str_replace("{link_to_details}",'',$l_Template);
      $l_Template = str_replace("{thumbnail}",'',$l_Template);
      $l_Template = str_replace("{screenshot}",'',$l_Template);
      $l_Template = str_replace("{pic_is_updated}",'',$l_Template);
      $l_Template = str_replace("{rank}",'',$l_Template);
      $l_Template = str_replace("{show_association}",'',$l_Template);
      $l_Template = str_replace('{information_header}', '', $l_Template);
      $l_Template = str_replace('{access_title}', '', $l_Template);
      $l_Template = str_replace('{access}', '', $l_Template);

      $l_Template = str_replace("{hits_title}",'',$l_Template);
      $l_Template = str_replace("{hits_value}",'',$l_Template);
      
      $l_Template = str_replace('{category_title}', '', $l_Template);
      $l_Template = str_replace('{category_name}', '', $l_Template);
      
      // remove images placeholders
      if (strpos($l_Template, "{screenshot_end}") > 0) {
        $pos_end = strpos($l_Template, '{screenshot_end}');
        $pos_beg = strpos($l_Template, '{screenshot_begin}');
        $l_Template = substr_replace($l_Template, '', $pos_beg, ($pos_end - $pos_beg) + 16);
      }
      for ($i=2; $i < 20; $i++){
          if (strpos($l_Template, "{screenshot_end$i}") > 0) {
            $pos_end = strpos($l_Template, "{screenshot_end$i}");
            $pos_beg = strpos($l_Template, "{screenshot_begin$i}");
            if ($i < 10){
                $l_Template = substr_replace($l_Template, '', $pos_beg, ($pos_end - $pos_beg) + 17);
            } else {
                $l_Template = substr_replace($l_Template, '', $pos_beg, ($pos_end - $pos_beg) + 18);
            }    
          }
      }

      $l_Template = str_replace("{thumbnail_lightbox}",'',$l_Template);
      $l_Template = str_replace("{thumbnail_gallery}",'',$l_Template);
      $l_Template = str_replace("{created_by_title}",'',$l_Template);
      $l_Template = str_replace("{created_by_value}",'',$l_Template);
      $l_Template = str_replace("{created_date_title}",'',$l_Template);
      $l_Template = str_replace("{created_date_value}",'',$l_Template);
      $l_Template = str_replace("{modified_by_title}",'',$l_Template);
      $l_Template = str_replace("{modified_by_value}",'',$l_Template);
      $l_Template = str_replace("{modified_date_title}",'',$l_Template);
      $l_Template = str_replace("{modified_date_value}",'',$l_Template);
      $l_Template = str_replace("{price_title}",'',$l_Template);
      $l_Template = str_replace("{price_value}",'',$l_Template);
      $l_Template = str_replace("{system_title}",'',$l_Template);
      $l_Template = str_replace("{system_text}",'',$l_Template);
      $l_Template = str_replace("{license_title}",'',$l_Template);
      $l_Template = str_replace("{license_text}",'',$l_Template);
      $l_Template = str_replace("{language_title}",'',$l_Template);
      $l_Template = str_replace("{language_text}",'',$l_Template);
      $l_Template = str_replace("{filesize_title}",'',$l_Template);
      $l_Template = str_replace("{filesize_value}",'',$l_Template);
      $l_Template = str_replace("{author}",'',$l_Template);
      $l_Template = str_replace("{url_author}",'',$l_Template);
      $l_Template = str_replace("{author_title}",'',$l_Template);
      $l_Template = str_replace("{author_text}",'',$l_Template);
      $l_Template = str_replace("{url_home}",'',$l_Template);
      $l_Template = str_replace("{author_url_title}",'',$l_Template);
      $l_Template = str_replace("{author_url_text}",'',$l_Template);
      $l_Template = str_replace("{files_title_begin}",'',$l_Template);
      $l_Template = str_replace("{files_title_text}",'',$l_Template);
      $l_Template = str_replace("{files_title_end}",'',$l_Template);
      $l_Template = str_replace("{preview_player}",'',$l_Template);
      $l_Template = str_replace("{mp3_player}",'',$l_Template);
      $l_Template = str_replace("{mp3_id3_tag}",'',$l_Template);
      $l_Template = str_replace("{google_adsense}",'',$l_Template);
      $l_Template = str_replace("{google_adsense_2}",'',$l_Template);
      $l_Template = str_replace("{report_link}",'',$l_Template);
      $l_Template = str_replace("{sum_jcomments}",'',$l_Template);
      $l_Template = str_replace("{rating}",'',$l_Template);
      $l_Template = str_replace("{rating_title}",'',$l_Template);
      $l_Template = str_replace("{file_date}", '', $l_Template); 
      $l_Template = str_replace("{file_date_title}", '', $l_Template);
      $l_Template = str_replace("{tags_title}", '', $l_Template);
      $l_Template = str_replace("{tags}", '', $l_Template);
      $l_Template = str_replace("{featured_class}", '', $l_Template);
      $l_Template = str_replace("{featured_detail_class}", '', $l_Template);
      $l_Template = str_replace("{featured_pic}", '', $l_Template);
	  $l_Template = str_replace("{preview_player}", '', $l_Template);
      
      // In the layout could still exist not required field placeholders
      $results = JDHelper::searchFieldPlaceholder($l_Template);
      if ($results){
          foreach ($results as $result){
              $l_Template = str_replace($result[0], '', $l_Template);   // Remove label and value placeholder
          }
      }
      
       // delete the tabs placeholder 
       $l_Template = str_replace('{tabs begin}', '', $l_Template);
       $l_Template = str_replace('{tab description}', '', $l_Template);
       $l_Template = str_replace('{tab description end}', '', $l_Template);
       $l_Template = str_replace('{tab pics}', '', $l_Template);
       $l_Template = str_replace('{tab pics end}', '', $l_Template);
       $l_Template = str_replace('{tab mp3}', '', $l_Template);
       $l_Template = str_replace('{tab mp3 end}', '', $l_Template);
       $l_Template = str_replace('{tab data}', '', $l_Template);
       $l_Template = str_replace('{tab data end}', '', $l_Template);
       $l_Template = str_replace('{tab download}', '', $l_Template);
       $l_Template = str_replace('{tab download end}', '', $l_Template);
       $l_Template = str_replace('{tab custom1}', '', $l_Template);
       $l_Template = str_replace('{tab custom1 end}', '', $l_Template);      
       $l_Template = str_replace('{tab custom2}', '', $l_Template);
       $l_Template = str_replace('{tab custom2 end}', '', $l_Template);
       $l_Template = str_replace('{tab custom3}', '', $l_Template);
       $l_Template = str_replace('{tab custom3 end}', '', $l_Template);
       $l_Template = str_replace('{tabs end}', '', $l_Template);
           
       $l_Template = JDHelper::removeEmptyTags($l_Template);
       return str_replace("[[[","{",$l_Template);
    }

    function jd_file_fill_downloadok($p_Template, $files, $p_Symbol_Off, $p_DownloadType, $download_allowed, $edit_allowed){
        global $params, $jDFPsfolders, $jDFPrank, $jDFPlive_site, $jDFPplugin_live_site, $jDFPabsolute_path, $cat_link_itemidsPlg, $jDLayoutTitleExists, $root_itemid, $date_format;

        $db     = Factory::getDBO();
        $user   = Factory::getUser();
        
        $jd_user_settings = JDHelper::getUserRules();
        
        $jdlink_author_text   = '';
        $createdbyname        = '';
        $modifiedbyname       = '';
        
        $truncated_file_desc_len = $params->get('plugin_auto_file_short_description_value');
      
        $l_Template = str_replace("{{{","[[[", $p_Template);

        $jdpic_license = '';
        $jdpic_date = '';
        $jdpic_author = '';
        $jdpic_website = '';
        $jdpic_system = '';
        $jdpic_language = '';
        $jdpic_download = '';
        $jdpic_hits = ''; 
        $jdpic_size = '';
        $jdpic_price = '';
        $cat_itemid = 0;
      
        if ($p_Symbol_Off == 0){
            $msize = $params->get('info_icons_size');
            $jdpic_license  = '<img src="'.Uri::base().$jDFPsfolders['mini'].'license.png" style="vertical-align:middle;" width="'.$msize.'" height="'.$msize.'" border="0"  alt="'.Text::_('COM_JDOWNLOADS_FRONTEND_MINI_ICON_ALT_LICENCE').'" title="'.Text::_('COM_JDOWNLOADS_FRONTEND_MINI_ICON_ALT_LICENCE').'" />&nbsp;';
            $jdpic_date     = '<img src="'.Uri::base().$jDFPsfolders['mini'].'date.png" style="vertical-align:middle;" width="'.$msize.'" height="'.$msize.'" border="0" alt="'.Text::_('COM_JDOWNLOADS_FRONTEND_MINI_ICON_ALT_DATE').'" title="'.Text::_('COM_JDOWNLOADS_FRONTEND_MINI_ICON_ALT_DATE').'" />';
            $jdpic_author   = '<img src="'.Uri::base().$jDFPsfolders['mini'].'contact.png" style="vertical-align:middle;" width="'.$msize.'" height="'.$msize.'" border="0" alt="'.Text::_('COM_JDOWNLOADS_FRONTEND_MINI_ICON_ALT_AUTHOR').'" title="'.Text::_('COM_JDOWNLOADS_FRONTEND_MINI_ICON_ALT_AUTHOR').'" />;';
            $jdpic_website  = '<img src="'.Uri::base().$jDFPsfolders['mini'].'weblink.png" style="vertical-align:middle;" width="'.$msize.'" height="'.$msize.'" border="0" alt="'.Text::_('COM_JDOWNLOADS_FRONTEND_MINI_ICON_ALT_WEBSITE').'" title="'.Text::_('COM_JDOWNLOADS_FRONTEND_MINI_ICON_ALT_WEBSITE').'" />';
            $jdpic_system   = '<img src="'.Uri::base().$jDFPsfolders['mini'].'system.png" style="vertical-align:middle;" width="'.$msize.'" height="'.$msize.'" border="0" alt="'.Text::_('COM_JDOWNLOADS_FRONTEND_MINI_ICON_ALT_SYSTEM').'" title="'.Text::_('COM_JDOWNLOADS_FRONTEND_MINI_ICON_ALT_SYSTEM').'" />&nbsp;';
            $jdpic_language = '<img src="'.Uri::base().$jDFPsfolders['mini'].'language.png" style="vertical-align:middle;" width="'.$msize.'" height="'.$msize.'" border="0" alt="'.Text::_('COM_JDOWNLOADS_FRONTEND_MINI_ICON_ALT_LANGUAGE').'" title="'.Text::_('COM_JDOWNLOADS_FRONTEND_MINI_ICON_ALT_LANGUAGE').'" />&nbsp;';
            $jdpic_download = '<img src="'.Uri::base().$jDFPsfolders['mini'].'download.png" style="vertical-align:middle;" width="'.$msize.'" height="'.$msize.'" border="0" alt="'.Text::_('COM_JDOWNLOADS_FRONTEND_MINI_ICON_ALT_DOWNLOAD').'" title="'.Text::_('COM_JDOWNLOADS_FRONTEND_MINI_ICON_ALT_DOWNLOAD').'" />&nbsp;';
            $jdpic_hits     = '<img src="'.Uri::base().$jDFPsfolders['mini'].'download.png" style="vertical-align:middle;" width="'.$msize.'" height="'.$msize.'" border="0" alt="'.Text::_('COM_JDOWNLOADS_FRONTEND_MINI_ICON_ALT_DOWNLOAD').'" title="'.Text::_('COM_JDOWNLOADS_FRONTEND_MINI_ICON_ALT_DOWNLOAD_HITS').'" />&nbsp;';
            $jdpic_size     = '<img src="'.Uri::base().$jDFPsfolders['mini'].'stuff.png" style="vertical-align:middle;" width="'.$msize.'" height="'.$msize.'" border="0" alt="'.Text::_('COM_JDOWNLOADS_FRONTEND_MINI_ICON_ALT_FILESIZE').'" title="'.Text::_('COM_JDOWNLOADS_FRONTEND_MINI_ICON_ALT_FILESIZE').'" />&nbsp;';
            $jdpic_price    = '<img src="'.Uri::base().$jDFPsfolders['mini'].'currency.png" style="vertical-align:middle;" width="'.$msize.'" height="'.$msize.'" border="0" alt="'.Text::_('COM_JDOWNLOADS_FRONTEND_MINI_ICON_ALT_PRICE').'" title="'.Text::_('COM_JDOWNLOADS_FRONTEND_MINI_ICON_ALT_PRICE').'" />&nbsp;';
        }

        // Build a little pic for extern links
        $jdextern_url_pic = '<img src="'.$jDFPplugin_live_site.'content/jdownloads/jdownloads/images/link_extern.gif" style="vertical-align:middle;" alt="link_extern" title="" />';
      
        $jd_file_pic      = '<img src="'.Uri::base().$jDFPsfolders['file'].$files->file_pic.'" style="vertical-align:middle;" border="0" width="'.$params->get('file_pic_size').'" height="'.$params->get('file_pic_size_height').'" alt="'.substr($files->file_pic,0,-4).'" title="" />';
        
        // Alternate CSS buttons when selected in configuration
        $status_color_hot        = $params->get('css_button_color_hot');
        $status_color_new        = $params->get('css_button_color_new');
        $status_color_updated    = $params->get('css_button_color_updated');
        $download_color          = $params->get('css_button_color_download');
        $download_size           = $params->get('css_button_size_download');
        $download_size_mirror    = $params->get('css_button_size_download_mirror');        
        $download_color_mirror1  = $params->get('css_button_color_mirror1');        
        $download_color_mirror2  = $params->get('css_button_color_mirror2'); 
        $download_size_listings  = $params->get('css_button_size_download_small');        
        
        $jd_catid       = $files->catid;
        $jd_filename    = $files->url_download;                                                                              
        $jd_file_language  = $files->file_language;
        $jd_system      = $files->system;
        
        if ($files->category_cat_dir_parent){
            $category_dir = $files->category_cat_dir_parent.'/'.$files->category_cat_dir;
        } else {
            $category_dir = $files->category_cat_dir;
        }        
        
        // Has this Download really a file?
        if (!$files->url_download && !$files->other_file_id && !$files->extern_file){
            // only a document without file
            $no_file_info = Text::_('COM_JDOWNLOADS_FRONTEND_ONLY_DOCUMENT_USER_INFO');
            $download_has_a_file = false;
        } else {
            $download_has_a_file = true;
            $no_file_info = '';
        }         
        
		// Edit link
		if ($edit_allowed) {
			// Changed in 3.9.0.6 - We do not display in the future an edit icon here to prevent problems. Users can always use the edit symbol in the details view.
            $edit_icon = ''; // JDHelper::getEditIcon($files);
		} else {
			$edit_icon = '';
		}
        
        // Compute the download slugs
        $files->slug = $files->alias ? ($files->id . ':' . $files->alias) : $files->id;
        
        // create all file titles
        $l_Template = JDHelper::buildFieldTitles($l_Template, $files);
        
        // When we have a simple document, view only the info not any buttons.
        if (!$download_has_a_file){
            if (strpos($l_Template, '{url_download}')){
                $l_Template = str_replace('{url_download}', $no_file_info, $l_Template);    
            } else {
                $l_Template = str_replace('{checkbox_list}', $no_file_info, $l_Template);    
            }

            // Place the images
            $l_Template = JDHelper::placeThumbs($l_Template, $files->images);      
            
            // we change the old lightbox tag type to the new
            $l_Template = str_replace('rel="lightbox"', 'data-lightbox="lightbox'.$files->id.'"', $l_Template);         

            if ($params->get('view_detailsite')){
                $title_link = Route::_(RouteHelper::getDownloadRoute($files->slug, $files->catid, $files->language));
                $title_link_text = '<a href="'.$title_link.'">'.$files->title.'</a>';
                $detail_link_text = '<a href="'.$title_link.'">'.Text::_('COM_JDOWNLOADS_FE_DETAILS_LINK_TEXT_TO_DETAILS').'</a>';
                // Build the file symbol (with link)
                if ($files->file_pic != '' ) {
                    $filepic = '<a href="'.$title_link.'">'.'<img src="'.Uri::base().$jDFPsfolders['file'].$files->file_pic.'" align="top" width="'.$params->get('file_pic_size').'" height="'.$params->get('file_pic_size_height').'" border="0" alt="'.substr($files->file_pic,0,-4).'" /></a>';
                } else {
                    $filepic = '';
                }
                $l_Template = str_replace('{file_pic}', $filepic, $l_Template);
                // link to details view at the end
                $l_Template = str_replace('{link_to_details}', $detail_link_text, $l_Template);
                $l_Template = str_replace('{file_title}', $title_link_text.' '.$edit_icon, $l_Template);
            
            } else {
                // no links
                if ($files->file_pic != '' ) {
                    $filepic = '<img src="'.Uri::base().$jDFPsfolders['file'].$files->file_pic.'" align="top" width="'.$params->get('file_pic_size').'" height="'.$params->get('file_pic_size_height').'" border="0" alt="'.substr($files->file_pic,0,-4).'" />';
                } else {
                    $filepic = '';
                }
                $l_Template = str_replace('{file_pic}', $filepic, $l_Template);
                // remove link to details view at the end
                $l_Template = str_replace('{link_to_details}', '', $l_Template);
                $l_Template = str_replace('{file_title}', $files->title.' '.$edit_icon, $l_Template);
            }
            $l_Template = str_replace('{checkbox_list}', '', $l_Template);
            $l_Template = str_replace('{mirror_1}', '', $l_Template);
            $l_Template = str_replace('{mirror_2}', '', $l_Template);
            $l_Template = str_replace('{hits_value}', '', $l_Template);
            $l_Template = str_replace('{filesize_value}', '', $l_Template); 
        } 

        // category title        
        $l_Template = str_replace('{category_title}', Text::_('COM_JDOWNLOADS_CATEGORY_LABEL'), $l_Template);
        $l_Template = str_replace('{category_name}', $files->category_title, $l_Template);
        
        
        // replace 'featured' placeholders
        if ($files->featured){
            // add the css class
            if ($params->get('use_featured_classes')){
                $l_Template = str_replace('{featured_class}', 'jd_featured', $l_Template);
                $l_Template = str_replace('{featured_detail_class}', 'jd_featured_detail', $l_Template);
            } else {
                $l_Template = str_replace('{featured_class}', '', $l_Template);
                $l_Template = str_replace('{featured_detail_class}', '', $l_Template);   
            }            
            // add the pic
            if ($params->get('featured_pic_filename')){
                $featured_pic = '<img class="jd_featured_star" src="'.Uri::base().'images/jdownloads/featuredimages/'.$params->get('featured_pic_filename').'" width="'.$params->get('featured_pic_size').'" height="'.$params->get('featured_pic_size_height').'" alt="'.substr($params->get('featured_pic_filename'),0,-4).'"/>';
                $l_Template = str_replace('{featured_pic}', $featured_pic, $l_Template);
            } else {
                $l_Template = str_replace('{featured_pic}', '', $l_Template);
            }
        } else {
            $l_Template = str_replace('{featured_class}', '', $l_Template);
            $l_Template = str_replace('{featured_detail_class}', '', $l_Template);
            $l_Template = str_replace('{featured_pic}', '', $l_Template);
        }
        
        // Build the license info data and build link
        if ($files->license == '') $files->license = 0;
        $lic_data = '';

        if ($files->license_url != '') {
             $lic_data = $jdpic_license.'<a href="'.$files->license_url.'" target="_blank" rel="nofollow" title="'.Text::_('COM_JDOWNLOADS_FRONTEND_MINI_ICON_ALT_LICENCE').'">'.$files->license_title.'</a> '.$jdextern_url_pic;
        } else {
            if ($files->license_title != '') {
                 if ($files->license_text != '') {
                      $lic_data = $jdpic_license.$files->license_title;
                      $lic_data .= HTMLHelper::_('tooltip', $files->license_text, $files->license_title);
                 } else {
                      $lic_data = $files->license_title;
                 }
            } else {
                $lic_data = '';
            }
        }
        $l_Template = str_replace('{license_text}', $lic_data, $l_Template);
        $l_Template = str_replace('{license}', $lic_data, $l_Template); // old placeholder

        // Build the 'files language' data
        $file_lang_values = explode(',' , JDHelper::getOnlyLanguageSubstring($params->get('language_list')));

        if ($jd_file_language == 0 ) {
            $jd_showlanguage = '';
        } else {
            $jd_showlanguage = $jdpic_language.$file_lang_values[$jd_file_language];
        }
        $l_Template = str_replace("{language}",$jd_showlanguage,$l_Template); // old placeholder
        $l_Template = str_replace("{language_text}",$jd_showlanguage,$l_Template);
      
        // Build the 'System' data
        $file_sys_values = explode(',' , $params->get('system_list'));
        if ($jd_system == 0 ) {
            $jd_showsystem = '';
        } else {
            $jd_showsystem = $jdpic_system.$file_sys_values[$jd_system];
        }
        $l_Template = str_replace("{system}",$jd_showsystem,$l_Template); // old placeholder
        $l_Template = str_replace("{system_text}",$jd_showsystem,$l_Template);
      
        // Build hits values
        $numbers_downloads = JDHelper::strToNumber((int)$files->downloads);
        $jd_showhits = $jdpic_hits.$numbers_downloads;
        $l_Template = str_replace("{hits_value}",$jd_showhits,$l_Template);

        // Build website url
        if (!$files->url_home == '') {
             if (strpos($files->url_home, 'http://') !== false or strpos($files->url_home, 'https://') !== false) {    
                 $l_Template = str_replace('{url_home}',$jdpic_website.'<a href="'.$files->url_home.'" target="_blank" title="'.Text::_('COM_JDOWNLOADS_FRONTEND_HOMEPAGE').'">'.Text::_('COM_JDOWNLOADS_FRONTEND_HOMEPAGE').'</a> '.$jdextern_url_pic, $l_Template);
                 $l_Template = str_replace('{author_url_text} ',$jdpic_website.'<a href="'.$files->url_home.'" target="_blank" title="'.Text::_('COM_JDOWNLOADS_FRONTEND_HOMEPAGE').'">'.Text::_('COM_JDOWNLOADS_FRONTEND_HOMEPAGE').'</a> '.$jdextern_url_pic, $l_Template);
             } else {
                 $l_Template = str_replace('{url_home}',$jdpic_website.'<a href="http://'.$files->url_home.'" target="_blank" title="'.Text::_('COM_JDOWNLOADS_FRONTEND_HOMEPAGE').'">'.Text::_('COM_JDOWNLOADS_FRONTEND_HOMEPAGE').'</a> '.$jdextern_url_pic, $l_Template);
                 $l_Template = str_replace('{author_url_text}',$jdpic_website.'<a href="http://'.$files->url_home.'" target="_blank" title="'.Text::_('COM_JDOWNLOADS_FRONTEND_HOMEPAGE').'">'.Text::_('COM_JDOWNLOADS_FRONTEND_HOMEPAGE').'</a> '.$jdextern_url_pic, $l_Template);
             }    
        } else {
            $l_Template = str_replace('{url_home}', '', $l_Template);
            $l_Template = str_replace('{author_url_text}', '', $l_Template);
        }

        // Encode is link a mail
        if (strpos($files->url_author, '@') && $params->get('mail_cloaking')){
            if (!$files->author) { 
                $mail_encode = HTMLHelper::_('email.cloak', $files->url_author);
            } else {
                $mail_encode = HTMLHelper::_('email.cloak', $files->url_author, true, $files->author, false);
            }        
        } else {
            $mail_encode = '';
        }
                
        // Build author link
        if ($files->author <> ''){
            if ($files->url_author <> '') {           
                if ($mail_encode) {
                    $link_author = $jdpic_author.$mail_encode;
                } else {
                    if (strpos($files->url_author, 'http://') !== false or strpos($files->url_author, 'https://') !== false) {    
                        $link_author = $jdpic_author.'<a href="'.$files->url_author.'" target="_blank">'.$files->author.'</a> '.$jdextern_url_pic;
                    } else {
                        $link_author = $jdpic_author.'<a href="http://'.$files->url_author.'" target="_blank">'.$files->author.'</a> '.$jdextern_url_pic;
                    }        
                }
                $l_Template = str_replace('{author}',$link_author, $l_Template);
                $l_Template = str_replace('{author_text}',$link_author, $l_Template);
                $l_Template = str_replace('{url_author}', '', $l_Template);
            } else {
                $link_author = $jdpic_author.$files->author;
                $l_Template = str_replace('{author}',$link_author, $l_Template);
                $l_Template = str_replace('{author_text}',$link_author, $l_Template);
                $l_Template = str_replace('{url_author}', '', $l_Template);
            }
        } else {
                $l_Template = str_replace('{url_author}', $jdpic_author.$files->url_author, $l_Template);
                $l_Template = str_replace('{author}','', $l_Template);
                $l_Template = str_replace('{author_text}','', $l_Template); 
        }
                     
        // Place the images
        $l_Template = JDHelper::placeThumbs($l_Template, $files->images, 'list');      
        
        // we change the old lightbox tag type to the new
        $l_Template = str_replace('rel="lightbox"', 'data-lightbox="lightbox'.$files->id.'"', $l_Template);     
        
        // Compute for HOT symbol
        if ($params->get('loads_is_file_hot') > 0 && $files->downloads >= $params->get('loads_is_file_hot') ){
            $l_Template = str_replace('{pic_is_hot}', '<span class="jdbutton '.$status_color_hot.' jstatus">'.Text::_('COM_JDOWNLOADS_HOT').'</span>', $l_Template);
        } else {    
            $l_Template = str_replace('{pic_is_hot}', '', $l_Template);
        }
        
        // Compute for NEW symbol
        $days_diff = JDHelper::computeDateDifference(date('Y-m-d H:i:s'), $files->created);
        if ($params->get('days_is_file_new') > 0 && $days_diff <= $params->get('days_is_file_new')){
            $l_Template = str_replace('{pic_is_new}', '<span class="jdbutton '.$status_color_new.' jstatus">'.Text::_('COM_JDOWNLOADS_NEW').'</span>', $l_Template);
        } else {    
            $l_Template = str_replace('{pic_is_new}', '', $l_Template);
        }
        
        // Compute for UPDATED symbol
        // View it only when in the download is activated the 'updated' option
        if ($files->update_active) {
            $days_diff = JDHelper::computeDateDifference(date('Y-m-d H:i:s'), $files->modified);
            if ($params->get('days_is_file_updated') > 0 && $days_diff >= 0 && $days_diff <= $params->get('days_is_file_updated')){
                $l_Template = str_replace('{pic_is_updated}', '<span class="jdbutton '.$status_color_updated.' jstatus">'.Text::_('COM_JDOWNLOADS_UPDATED').'</span>', $l_Template);
            } else {    
                $l_Template = str_replace('{pic_is_updated}', '', $l_Template);
            }
        } else {
           $l_Template = str_replace('{pic_is_updated}', '', $l_Template);
        }    
      
        // file size
        if ($files->size == '' || $files->size == '0 B') {
            $l_Template = str_replace('{size}', '', $l_Template);
            $l_Template = str_replace('{filesize_value}', '', $l_Template);
        } else {
            $l_Template = str_replace('{size}', $jdpic_size.$files->size, $l_Template);
            $l_Template = str_replace('{filesize_value}', $jdpic_size.$files->size, $l_Template);
        } 
        
        // price
        if ($files->price != '') {
            $l_Template = str_replace('{price_value}', $jdpic_price.$files->price, $l_Template);
        } else {
            $l_Template = str_replace('{price_value}', '', $l_Template);
        }

        // Add access information
        $l_Template = str_replace('{access_title}', Text::_('COM_JDOWNLOADS_ACCESS'), $l_Template);
        $userhelper = new UserGroupsHelper();
        $user_group  = $userhelper->load($files->access);              
        $l_Template = str_replace('{access}', $user_group->title, $l_Template);

        // file_date
        if ($files->file_date != '0000-00-00 00:00:00' && $files->file_date != null) {
             if ($files->params->get('show_date') == 0){ 
                 $filedate_data = $jdpic_date.HTMLHelper::_('date',$files->file_date, $date_format['long']);
                 $filedate_data_title = Text::_('COM_JDOWNLOADS_EDIT_FILE_FILE_DATE_TITLE'); 
             } else {
                 $filedate_data = $jdpic_date.HTMLHelper::_('date',$files->file_date, $date_format['short']);
                 $filedate_data_title = '';
             }    
        } else {
             $filedate_data = '';
             $filedate_data_title = '';
        }
        $l_Template = str_replace('{file_date}',$filedate_data, $l_Template);
        
        
        // date_added
        if ($files->created != '0000-00-00 00:00:00' && $files->created != null) {
            if ($files->params->get('show_date') == 0){ 
                // use 'normal' date-time format field
                $date_data = $jdpic_date.HTMLHelper::_('date',$files->created, $date_format['long']);
            } else {
                // use 'short' date-time format field
                $date_data = $jdpic_date.HTMLHelper::_('date',$files->created, $date_format['short']);
            }    
        } else {
             $date_data = '';
        }
        $l_Template = str_replace('{date_added}',$date_data, $l_Template);
        $l_Template = str_replace('{created_date_value}',$date_data, $l_Template);
        
        if ($files->creator){
            $l_Template = str_replace('{created_by_value}', $files->creator, $l_Template);
        } else {
            $l_Template = str_replace('{created_by_value}', '', $l_Template);
        }                
        if ($files->modifier){
            $l_Template = str_replace('{modified_by_value}', $files->modifier, $l_Template);
        } else {                              
            $l_Template = str_replace('{modified_by_value}', '', $l_Template);
        }
        
        // modified_date
        if ($files->modified != '0000-00-00 00:00:00' && $files->modified != null) {
            if ($files->params->get('show_date') == 0){ 
                $modified_data = $jdpic_date.HTMLHelper::_('date',$files->modified, $date_format['long']);
            } else {
                $modified_data = $jdpic_date.HTMLHelper::_('date',$files->modified, $date_format['short']);
            }    
        } else {
            $modified_data = '';
        }
        $l_Template = str_replace('{modified_date_value}',$modified_data, $l_Template);
   
        if ($files->release == '') {
            $l_Template = str_replace('{release}', '', $l_Template);
        } else {
            $l_Template = str_replace('{release}', $files->release.' ', $l_Template); 
            // with versions text from language file
            // $l_Template = str_replace('{release}', Text::_('COM_JDOWNLOADS_FRONTEND_VERSION_TITLE').$files->release, $l_Template);
        }
        
        // Create an additional hint for the description footer when this download has a file but user has not the download permissions 
        if ($download_has_a_file){
            if ($user->guest){
                $first_reg_msg = '<div class="'.$params->get('css_button_color_download').' '.$params->get('css_button_size_download').'">'.Text::_('COM_JDOWNLOADS_FRONTEND_FILE_ACCESS_REGGED').'</div>';
            } else {
                $first_reg_msg = '<div class="'.$params->get('css_button_color_download').' '.$params->get('css_button_size_download').'">'.Text::_('COM_JDOWNLOADS_FRONTEND_FILE_ACCESS_REGGED2').'</div>';
            }         
        } else {
            $first_reg_msg = '';            
        }
        
        // Check and build the description text
        if ($truncated_file_desc_len){
            if (StringHelper::strlen($files->description) > $truncated_file_desc_len){ 
                // Cut description text
                $shorted_text = HTMLHelper::_('string.truncate', $files->description, $truncated_file_desc_len, true, true); // Do not cut off words; HTML allowed;
                if (!$download_allowed){
                    //$l_Template = str_replace('{description}', $shorted_text.$first_reg_msg, $l_Template);
                    $l_Template = str_replace('{description}', $shorted_text, $l_Template);
                } else {
                    $l_Template = str_replace('{description}', $shorted_text, $l_Template);
                }    
            } else {
                if (!$download_allowed){
                     // $l_Template = str_replace('{description}', $files->description.$first_reg_msg, $l_Template);
                     $l_Template = str_replace('{description}', $files->description, $l_Template);
                } else {     
                     $l_Template = str_replace('{description}', $files->description, $l_Template);
                }     
            }    
        } else {
            if (!$download_allowed){
                 //$l_Template = str_replace("{description}",$files->description.$first_reg_msg, $l_Template);
                 $l_Template = str_replace('{description}', $files->description, $l_Template);
            } else {
                 $l_Template = str_replace('{description}', $files->description, $l_Template);     
            }     
        } 
      
        // create filename
        if ($files->url_download){
            $l_Template = str_replace('{file_name}', JDHelper::getShorterFilename($db->escape(strip_tags($files->url_download))), $l_Template);
        } elseif (isset($files->filename_from_other_download) && $files->filename_from_other_download != ''){    
            $l_Template = str_replace('{file_name}', JDHelper::getShorterFilename($db->escape(strip_tags($files->filename_from_other_download))), $l_Template);
        } else {
            $l_Template = str_replace('{file_name}', '', $l_Template);
        }
        
        $l_Template = str_replace("{show_association}", '', $l_Template);
        
        // replace both Google adsense placeholder with script
        $l_Template = JDHelper::insertGoogleAdsenseCode($l_Template);

        // report download link
        if ($jd_user_settings->view_report_form){
            $report_link = '<a href="'.Route::_("index.php?option=com_jdownloads&amp;view=report&amp;id=".$files->slug."&amp;catid=".$files->catid."&amp;Itemid=".$root_itemid).'" rel="nofollow">'.Text::_('COM_JDOWNLOADS_FRONTEND_REPORT_FILE_LINK_TEXT').'</a>';
            $l_Template = str_replace('{report_link}', $report_link, $l_Template);
        } else {
            $l_Template = str_replace('{report_link}', '', $l_Template);
        }      
      
        // media player
        if ($files->preview_filename){
            // we use the preview file when exist  
            $is_preview = true;
            $files->itemtype = JDHelper::getFileExtension($files->preview_filename);
            $is_playable    = JDHelper::isPlayable($files->preview_filename);
            $extern_media = false;
        } else {                  
            $is_preview = false;
            if ($files->extern_file){
                $extern_media = true;
                $files->itemtype = JDHelper::getFileExtension($files->extern_file);
                $is_playable    = JDHelper::isPlayable($files->extern_file);
            } else {    
                $files->itemtype = JDHelper::getFileExtension($files->url_download);
                $is_playable    = JDHelper::isPlayable($files->url_download);
                $extern_media = false;
            }  
        }            
        
        if ( $is_playable ){
            
            if ($params->get('html5player_use')){
                // we will use the new HTML5 player option
                if ($extern_media){
                    $media_path = $files->extern_file;
                } else {        
                    if ($is_preview){
                        // we need the relative path to the "previews" folder
                        $media_path = Uri::base().basename($params->get('files_uploaddir')).'/'.$params->get('preview_files_folder_name').'/'.$files->preview_filename;
                    } else {
                        // we use the normal download file for the player
                        $media_path = Uri::base().basename($params->get('files_uploaddir')).'/'.$category_dir.'/'.$files->url_download;
                    }   
                }    
                        
                // create the HTML5 player
                $player = JDHelper::getHTML5Player($files, $media_path);
                
                // we use the player for video files only in listings, when the option allowed this
                if ($params->get('html5player_view_video_only_in_details') && $files->itemtype != 'mp3' && $files->itemtype != 'wav' && $files->itemtype != 'oga'){
                    $l_Template = str_replace('{mp3_player}', '', $l_Template);
                    $l_Template = str_replace('{preview_player}', '', $l_Template);
                } else {                            
                    if ($files->itemtype == 'mp4' || $files->itemtype == 'webm' || $files->itemtype == 'ogg' || $files->itemtype == 'ogv' || $files->itemtype == 'mp3' || $files->itemtype == 'wav' || $files->itemtype == 'oga'){
                        // We will replace at first the old placeholder when exist
                        if (strpos($l_Template, '{mp3_player}')){
                            $l_Template = str_replace('{mp3_player}', $player, $l_Template);
                            $l_Template = str_replace('{preview_player}', '', $l_Template);
                        } else {                
                            $l_Template = str_replace('{preview_player}', $player, $l_Template);
                        }    
                    } else {
                        $l_Template = str_replace('{mp3_player}', '', $l_Template);
                        $l_Template = str_replace('{preview_player}', '', $l_Template);
                    }    
                }                
            
            } else {        
    
                if ( $params->get('flowplayer_use') && $is_playable ){
                    // we will use the new flowplayer option
                    if ($extern_media){
                        $media_path = $files->extern_file;
                    } else {        
                        if ($is_preview){
                            // we need the relative path to the "previews" folder
                            $media_path = basename($params->get('files_uploaddir')).'/'.$params->get('preview_files_folder_name').'/'.$files->preview_filename;
                        } else {
                            // we use the normal download file for the player
                            $media_path = basename($params->get('files_uploaddir')).'/'.$category_dir.'/'.$files->url_download;
                        }   
                    }    

                    $ipadcode = '';

                    if ($files->itemtype == 'mp3'){
                        $fullscreen = 'false';
                        $autohide = 'false';
                        $playerheight = (int)$params->get('flowplayer_playerheight_audio');
                        // we must use also the ipad plugin identifier when required
                        // see http://flowplayer.blacktrash.org/test/ipad-audio.html and http://flash.flowplayer.org/plugins/javascript/ipad.html
                        if ((bool) strpos($_SERVER['HTTP_USER_AGENT'], 'iPad') || (bool) strpos($_SERVER['HTTP_USER_AGENT'], 'iPhone')){        
                            $ipadcode = '.ipad();'; 
                        }
                    } else {
                        $fullscreen = 'true';
                        $autohide = 'true';
                        $playerheight = (int)$params->get('flowplayer_playerheight');
                    }
                    
                    $player = '<a href="'.$media_path.'" style="display:block;width:'.$params->get('flowplayer_playerwidth').'px; height:'.$playerheight.'px;" class="player" id="player'.$files->id.'"></a>';
                    $player .= '<script language="JavaScript">
                    // install flowplayer into container
                                flowplayer("player'.$files->id.'", "'.Uri::base().'components/com_jdownloads/assets/flowplayer/flowplayer-3.2.16.swf",  
                                 {  
                        plugins: {
                            controls: {
                                // insert at first the config settings
                                // and now the basics
                                fullscreen: '.$fullscreen.',
                                height: '.(int)$params->get('flowplayer_playerheight_audio').',
                                autoHide: '.$autohide.',
                            }
                            
                        },
                        clip: {
                            autoPlay: false,
                            // optional: when playback starts close the first audio playback
                             onBeforeBegin: function() {
                                $f("player'.$files->id.'").close();
                            }
                        }
                    })'.$ipadcode.'; </script>';
                    // the 'ipad code' above is only required for ipad/iphone users                
                    
                    // we use the player for video files only in listings, when the option allowed this
                    if ($params->get('flowplayer_view_video_only_in_details') && $files->itemtype != 'mp3'){ 
                        $l_Template = str_replace('{mp3_player}', '', $l_Template);
                        $l_Template = str_replace('{preview_player}', '', $l_Template);            
                    } else {    
                        if ($files->itemtype == 'mp4' || $files->itemtype == 'flv' || $files->itemtype == 'mp3'){    
                            // We will replace at first the old placeholder when exist
                            if (strpos($l_Template, '{mp3_player}')){
                                $l_Template = str_replace('{mp3_player}', $player, $l_Template);
                                $l_Template = str_replace('{preview_player}', '', $l_Template);
                            } else {
                                $l_Template = str_replace('{preview_player}', $player, $l_Template);
                            }                                
                        } else {
                            $l_Template = str_replace('{mp3_player}', '', $l_Template);
                            $l_Template = str_replace('{preview_player}', '', $l_Template);
                        }
                    }
                }
            }
        } 
        
        if ($params->get('mp3_view_id3_info') && $files->itemtype == 'mp3' && !$extern_media){
            // read mp3 infos
            if ($is_preview){
                // get the path to the preview file
                $mp3_path_abs = $params->get('files_uploaddir').'/'.$params->get('preview_files_folder_name').'/'.$files->preview_filename;
            } else {
                // get the path to the downloads file
                $mp3_path_abs = $params->get('files_uploaddir').'/'.$category_dir.'/'.$files->url_download;
            }
            
            $info = JDHelper::getID3v2Tags($mp3_path_abs);         
            if ($info){
                // add it
                $mp3_info = '<div class="jd_mp3_id3tag_wrapper" style="max-width:'.(int)$params->get('html5player_audio_width').'px; ">'.stripslashes($params->get('mp3_info_layout')).'</div>';
                $mp3_info = str_replace('{name_title}', Text::_('COM_JDOWNLOADS_FE_VIEW_ID3_TITLE'), $mp3_info);
                if ($is_preview){
                    $mp3_info = str_replace('{name}', $files->preview_filename, $mp3_info);
                } else {
                    $mp3_info = str_replace('{name}', $files->url_download, $mp3_info);
                } 
                $mp3_info = str_replace('{album_title}', Text::_('COM_JDOWNLOADS_FE_VIEW_ID3_ALBUM'), $mp3_info);
                $mp3_info = str_replace('{album}', $info['TALB'], $mp3_info);
                $mp3_info = str_replace('{artist_title}', Text::_('COM_JDOWNLOADS_FE_VIEW_ID3_ARTIST'), $mp3_info);
                $mp3_info = str_replace('{artist}', $info['TPE1'], $mp3_info);
                $mp3_info = str_replace('{genre_title}', Text::_('COM_JDOWNLOADS_FE_VIEW_ID3_GENRE'), $mp3_info);
                $mp3_info = str_replace('{genre}', $info['TCON'], $mp3_info);
                $mp3_info = str_replace('{year_title}', Text::_('COM_JDOWNLOADS_FE_VIEW_ID3_YEAR'), $mp3_info);
                $mp3_info = str_replace('{year}', $info['TYER'], $mp3_info);
                $mp3_info = str_replace('{length_title}', Text::_('COM_JDOWNLOADS_FE_VIEW_ID3_LENGTH'), $mp3_info);
                $mp3_info = str_replace('{length}', $info['TLEN'].' '.Text::_('COM_JDOWNLOADS_FE_VIEW_ID3_MINS'), $mp3_info);
                $l_Template = str_replace('{mp3_id3_tag}', $mp3_info, $l_Template); 
            }     
        }
        
        // replace the {preview_url}
        if ($files->preview_filename){
            // we need the relative path to the "previews" folder
            $media_path = basename($params->get('files_uploaddir')).'/'.$params->get('preview_files_folder_name').'/'.$files->preview_filename;
            $l_Template = str_replace('{preview_url}', $media_path, $l_Template);
        } else {
            $l_Template = str_replace('{preview_url}', '', $l_Template);
        }
        
        // replace the placeholder {information_header}
        $l_Template = str_replace('{information_header}', Text::_('COM_JDOWNLOADS_INFORMATION'), $l_Template);
        
        // render the tags
        if (!empty($files->tags->itemTags)){ 
            $files->tagLayout = new FileLayout('joomla.content.tags');
            $l_Template = str_replace('{tags}', $files->tagLayout->render($files->tags->itemTags), $l_Template);
            $l_Template = str_replace('{tags_title}', Text::_('COM_JDOWNLOADS_TAGS_LABEL'), $l_Template);
        } else {
            $l_Template = str_replace('{tags}', '', $l_Template);
            $l_Template = str_replace('{tags_title}', '', $l_Template);
        }        
                         
        // Insert the Joomla Fields data when used 
        if (isset($files->jcfields) && count((array)$files->jcfields)){
            foreach ($files->jcfields as $field){
                if ($params->get('remove_field_title_when_empty') && !$field->value){
                    $l_Template = str_replace('{jdfield_title '.$field->id.'}', '', $l_Template);  // Remove label placeholder
                    $l_Template = str_replace('{jdfield '.$field->id.'}', '', $l_Template);        // Remove value placeholder
                } else {
                    $l_Template = str_replace('{jdfield_title '.$field->id.'}', $field->label, $l_Template);  // Insert label
                    $l_Template = str_replace('{jdfield '.$field->id.'}', $field->value, $l_Template);        // Insert value
                }
            }
            
            // In the layout could still exist not required field placeholders
            $results = JDHelper::searchFieldPlaceholder($l_Template);
            if ($results){
                foreach ($results as $result){
                    $l_Template = str_replace($result[0], '', $l_Template);   // Remove label and value placeholder
                }
            } 
        } else {
            // In the layout could still exist not required field placeholders
            $results = JDHelper::searchFieldPlaceholder($l_Template);
            if ($results){
                foreach ($results as $result){
                    $l_Template = str_replace($result[0], '', $l_Template);   // Remove label and value placeholder
                }
            }
        }          
                         
        $user_can_see_download_url = false;
       
        // only view download link when user has correct access level
        if ($files->params->get('access-download') == true){     
            $user_can_see_download_url = true;
            $blank_window = '';
            $blank_window1 = '';
            $blank_window2 = '';
            // get file extension
            $view_types = array();
            $view_types = explode(',', $params->get('file_types_view'));
            $only_file_name = basename($files->url_download);
            $filesextension = JDHelper::getFileExtension($only_file_name);
            if (in_array($filesextension, $view_types)){
                $blank_window = 'target="_blank"';
            }    
            // check is set link to a new window?
            if ($files->extern_file && $files->extern_site   ){
                $blank_window = 'target="_blank"';
            }
            // is 'direct download' activated?
            if ($params->get('direct_download') == '0'){ 
                // when not, we must link to the summary page
                $url_task = 'summary';
                $blank_window = '';
                $download_link = Route::_(RouteHelper::getOtherRoute($files->slug, $files->catid, $files->language, $url_task));
            } else {
                if ($files->license_agree || $files->password || $jd_user_settings->view_captcha) {
                     // user must agree the license - fill out a password field - or fill out the captcha human check - so we must view the summary page!
                    $url_task = 'summary';
                    $download_link = Route::_(RouteHelper::getOtherRoute($files->slug, $files->catid, $files->language, $url_task));
                } else {     
                    // start the download promptly
                    $url_task = 'download.send';
                    $download_link = Route::_(RouteHelper::getOtherRoute($files->slug, $files->catid, $files->language, $url_task));
                }
            }
            
            if ($url_task == 'download.send'){
                $download_link_text = '<a '.$blank_window.' href="'.$download_link.'" aria-label="'.Text::_('COM_JDOWNLOADS_LINKTEXT_DOWNLOAD_URL_ARIA_DIRECT').'" alt="'.substr(Text::_('COM_JDOWNLOADS_LINKTEXT_DOWNLOAD_URL'),0,-4).'" class="jdbutton '.$download_color.' '.$download_size_listings.'">'.Text::_('COM_JDOWNLOADS_LINKTEXT_DOWNLOAD_URL').'</a>';
             } else {
                $download_link_text = '<a '.$blank_window.' href="'.$download_link.'" aria-label="'.Text::_('COM_JDOWNLOADS_LINKTEXT_DOWNLOAD_URL_ARIA_SUMMARY').'" alt="'.substr(Text::_('COM_JDOWNLOADS_LINKTEXT_DOWNLOAD_URL'),0,-4).'" class="jdbutton '.$download_color.' '.$download_size_listings.'">'.Text::_('COM_JDOWNLOADS_LINKTEXT_DOWNLOAD_URL').'</a>';
             }
            if (strpos($l_Template, '{url_download}')){
                $l_Template = str_replace('{url_download}', $download_link_text, $l_Template);
            } else {
                $l_Template = str_replace('{checkbox_list}', $download_link_text, $l_Template);
            }    
            
            // mirrors
            if ($files->mirror_1) {
                if ($files->extern_site_mirror_1 && $url_task == 'download.send'){
                    $blank_window1 = 'target="_blank"';
                }
                $mirror1_link_dum = Route::_(RouteHelper::getOtherRoute($files->slug, $files->catid, $files->language, 'download.send', 1));
                $mirror1_link = '<a '.$blank_window1.' href="'.$mirror1_link_dum.'" alt="'.Text::_('COM_JDOWNLOADS_LINKTEXT_DOWNLOAD_URL').'" class="jdbutton '.$download_color_mirror1.' '.$download_size_mirror.'">'.Text::_('COM_JDOWNLOADS_FRONTEND_MIRROR_URL_TITLE_1').'</a>'; 
                $l_Template = str_replace('{mirror_1}', $mirror1_link, $l_Template);
            } else {
                $l_Template = str_replace('{mirror_1}', '', $l_Template);
            }
            if ($files->mirror_2) {
                if ($files->extern_site_mirror_2 && $url_task == 'download.send'){
                    $blank_window2 = 'target="_blank"';
                }            
                $mirror2_link_dum = Route::_(RouteHelper::getOtherRoute($files->slug, $files->catid, $files->language, 'download.send', 2));
                $mirror2_link = '<a '.$blank_window2.' href="'.$mirror2_link_dum.'" alt="'.Text::_('COM_JDOWNLOADS_LINKTEXT_DOWNLOAD_URL').'" class="jdbutton '.$download_color_mirror2.' '.$download_size_mirror.'">'.Text::_('COM_JDOWNLOADS_FRONTEND_MIRROR_URL_TITLE_2').'</a>'; 
                $l_Template = str_replace('{mirror_2}', $mirror2_link, $l_Template);
            } else {
                $l_Template = str_replace('{mirror_2}', '', $l_Template);
            }            

        } else {

            // visitor has not access to download this item - so we will inform him

            if (strpos($l_Template, '{url_download}')){
                $l_Template = str_replace('{url_download}', $first_reg_msg, $l_Template);    
            } else {
                $l_Template = str_replace('{checkbox_list}', $first_reg_msg, $l_Template);    
            }
            
            $l_Template = str_replace('{mirror_1}', '', $l_Template); 
            $l_Template = str_replace('{mirror_2}', '', $l_Template); 
        }
        
        if ($params->get('view_detailsite')){
            $title_link = Route::_(RouteHelper::getDownloadRoute($files->slug, $files->catid, $files->language));
            $title_link_text = '<a href="'.$title_link.'">'.$files->title.'</a> ';
            $detail_link_text = '<a href="'.$title_link.'">'.Text::_('COM_JDOWNLOADS_FE_DETAILS_LINK_TEXT_TO_DETAILS').'</a>';
            // Build the file symbol (with link)
            if ($files->file_pic != '' ) {
                $filepic = '<a href="'.$title_link.'">'.'<img src="'.Uri::base().$jDFPsfolders['file'].$files->file_pic.'" align="top" width="'.$params->get('file_pic_size').'" height="'.$params->get('file_pic_size_height').'" border="0" alt="'.substr($files->file_pic,0,-4).'"/></a>';
            } else {
                $filepic = '';
            }
            $l_Template = str_replace('{file_pic}', $filepic, $l_Template);
            // link to details view at the end
            $l_Template = str_replace('{link_to_details}', $detail_link_text, $l_Template);
            $l_Template = str_replace('{file_title}', $title_link_text.' '.$edit_icon, $l_Template);
            
        } elseif ($params->get('use_download_title_as_download_link')){
            
            if ($user_can_see_download_url){
                // build title link as download link
               if ($url_task == 'download.send'){ 
                  $download_link_text = '<a '.$blank_window.' href="'.$download_link.'" title="'.Text::_('COM_JDOWNLOADS_LINKTEXT_DOWNLOAD_URL').'" class="jd_download_url">'.$files->title.'</a> ';
               } else {
                  $download_link_text = '<a href="'.$download_link.'" title="'.Text::_('COM_JDOWNLOADS_LINKTEXT_DOWNLOAD_URL').'">'.$files->title.'</a> ';                  
               }
               // View file icon also with link
               if ($files->file_pic != '' ) {
                    $filepic = '<a href="'.$download_link.'"><img src="'.Uri::base().$jDFPsfolders['file'].$files->file_pic.'" align="top" width="'.$params->get('file_pic_size').'" height="'.$params->get('file_pic_size_height').'" border="0" alt="'.substr($files->file_pic,0,-4).'"/></a>';
               } else {
                    $filepic = '';
               }
               $l_Template = str_replace('{file_pic}', $filepic, $l_Template);
               $l_Template = str_replace('{link_to_details}', '', $l_Template);
               $l_Template = str_replace('{file_title}', $download_link_text.' '.$edit_icon, $l_Template);
            } else {
                // user may not use download link
                $l_Template = str_replace('{file_title}', $files->title, $l_Template);
                if ($files->file_pic != '' ) {
                    $filepic = '<img src="'.Uri::base().$jDFPsfolders['file'].$files->file_pic.'" align="top" width="'.$params->get('file_pic_size').'" height="'.$params->get('file_pic_size_height').'" border="0" alt="'.substr($files->file_pic,0,-4).'"/>';
                } else {
                    $filepic = '';
                }
                $l_Template = str_replace('{file_pic}', $filepic, $l_Template);
            }    
        } else {
            // no links
            if ($files->file_pic != '' ) {
                $filepic = '<img src="'.Uri::base().$jDFPsfolders['file'].$files->file_pic.'" align="top" width="'.$params->get('file_pic_size').'" height="'.$params->get('file_pic_size_height').'" border="0" alt="'.substr($files->file_pic,0,-4).'"/>';
            } else {
                $filepic = '';
            }
            $l_Template = str_replace('{file_pic}', $filepic, $l_Template);
            // remove link to details view at the end
            $l_Template = str_replace('{link_to_details}', '', $l_Template);
            $l_Template = str_replace('{file_title}', $files->title.' ', $l_Template);
        }             

        // insert rating system
        if ($params->get('view_ratings')){
            $rating_system = JDHelper::getRatings($files->id, $user_can_see_download_url, $files->rating_count, $files->rating_sum);
            $l_Template = str_replace('{rating}', $rating_system, $l_Template);
            $l_Template = str_replace('{rating_title}', Text::_('COM_JDOWNLOADS_RATING_LABEL'), $l_Template);
        } else {
            $l_Template = str_replace('{rating}', '', $l_Template);
            $l_Template = str_replace('{rating_title}', '', $l_Template);
        }

    
      $l_Template = str_replace('{file_title_only}', $files->title, $l_Template);
      
      $l_Template = str_replace('{checkbox_list}', '',$l_Template);
      
      $l_Template = str_replace('{file_id}', $files->id,$l_Template);
      $l_Template = str_replace('{ordering}', $files->ordering,$l_Template);
      $l_Template = str_replace('{published}', $files->published,$l_Template);
      $l_Template = str_replace('{cat_id}', $files->catid,$l_Template);
      
      $l_Template = str_replace('{rank}',$jDFPrank, $l_Template);
      
      $l_Template = str_replace('{mp3_player}', '', $l_Template);
      $l_Template = str_replace('{mp3_id3_tag}', '', $l_Template);      
      $l_Template = str_replace('{preview_player}', '', $l_Template);
      $l_Template = str_replace('{report_link}','', $l_Template);
      $l_Template = str_replace('{sum_jcomments}','', $l_Template);
      $l_Template = str_replace('{rating}','', $l_Template);
      $l_Template = str_replace('{rating_title}','', $l_Template);
      
      // insert files title area
      if (!$jDLayoutTitleExists){
            $l_Template = str_replace('{files_title_begin}', '', $l_Template);
            $l_Template = str_replace('{files_title_end}', '', $l_Template);  
            $l_Template = str_replace('{files_title_text}', Text::_('COM_JDOWNLOADS_FE_FILELIST_TITLE_OVER_FILES_LIST'), $l_Template);
            $jDLayoutTitleExists = true;
      } else {
            if (strpos($l_Template, "{files_title_end}") > 0){
                $pos_end = strpos($l_Template, '{files_title_end}');
                $pos_beg = strpos($l_Template, '{files_title_begin}');
                $l_Template = substr_replace($l_Template, '', $pos_beg, ($pos_end - $pos_beg) + 17);
            }
      }      
      
      // add the content plugin event 'before display content'
      if (strpos($l_Template, '{before_display_content}') > 0){
          $l_Template = str_replace('{before_display_content}', $files->event->beforeDisplayContent, $l_Template);
      } else {
          $l_Template = $files->event->beforeDisplayContent.$l_Template;    
      }

      // for the 'after display title' event can we only use a placeholder - a fix position is not really given
      $l_Template = str_replace('{after_display_title}', $files->event->afterDisplayTitle, $l_Template);
        
      // add the content plugin event 'after display content'
      if (strpos($l_Template, '{after_display_content}') > 0){
          $l_Template = str_replace('{after_display_content}', $files->event->afterDisplayContent, $l_Template);
          $event = '';
      } else {
          $event = $files->event->afterDisplayContent;    
      }
      
      // support content plugins in layout output 
      $l_Template = HTMLHelper::_('content.prepare', $l_Template, '', 'com_jdownloads.download');

      // remove empty html tags
      if ($params->get('remove_empty_tags')){
          $l_Template = JDHelper::removeEmptyTags($l_Template);
      }
      
      // finaly add the 'after display content' event output when required
      $l_Template .= $event;
      
      return str_replace("[[[","{",$l_Template);
    }


    // Build the data output for a category placeholder  
    function jd_file_createcategory($matches, $type)
    {
        global $params;
        
        $db = Factory::getDBO();
        $user = Factory::getUser();

        $count = '';
        $cat_result = array();
        $sum = strrchr($matches[2] , ' count==');
        $matches[2] = str_replace($sum, '', $matches[2]);
        $sum = (int)str_replace(' count==', '', $sum);
        if ($sum > 0) $count = 'LIMIT '.$sum; 
      
        // Get the data from the model
        // and convert to array of integer
        $catid = implode(',', array_map( 'intval', array_filter( explode(',', $matches[2]), 'is_numeric' ) ));
      
        $query    = $db->getQuery(true);
        $groups    = implode(',', $user->getAuthorisedViewLevels());
        $asset    = 'com_jdownloads.category.'.$catid;
      
        // use for sort order the config settings
        switch ($params->get('files_order')){
            case '0':
                // files ordering field
                $orderCol = 'ordering';
                $listOrderNew = 'ASC';
                break;
            case '1':
                // files created desc 
                $orderCol = 'created'; // desc
                $listOrderNew = 'DESC';
                break;
            case '2':
                // files created asc 
                $orderCol = 'created'; // asc
                $listOrderNew = 'ASC';
                break;
            case '3':
                // files title field asc 
                $orderCol = 'title';
                $listOrderNew = 'ASC';
                break;
            case '4':
                // files title field desc 
                $orderCol = 'title';
                $listOrderNew = 'DESC';
                break;
            case '5':
                // files hits/downloads field desc
                $orderCol = 'downloads';
                $listOrderNew = 'DESC';
                break;
            case '6':
                // files hits/downloads field asc
                $orderCol = 'downloads';
                $listOrderNew = 'ASC';
                break;                         
            case '7':
                // author title field asc 
                $orderCol = 'author';
                $listOrderNew = 'ASC';
                break;
            case '8':
                // author title field desc 
                $orderCol = 'author';
                $listOrderNew = 'DESC';
                break;                         
        }      
      
        // Check at first whether this user may view the items from this category.
        $db->setQuery("SELECT count(*) FROM #__jdownloads_categories WHERE published = 1 AND id IN ($catid) AND access IN ($groups)");
        $cat = $db->loadResult();
       
        if ($cat){
            if ($type == 'hottest'){
                // we will view only the most downloaded files from a single category 
                $db->setQuery("SELECT * FROM #__jdownloads_files WHERE published = 1 AND catid IN ($catid) AND access IN ($groups) ORDER BY downloads desc ".$count);
            } elseif ($type == 'latest'){
                // we will view only the newest files from a single category
                $db->setQuery("SELECT * FROM #__jdownloads_files WHERE published = 1 AND catid IN ($catid) AND access IN ($groups) ORDER BY created desc ".$count);
            } else {
                // only category placeholder is used - so we use the configuration sort order 
                $db->setQuery("SELECT * FROM #__jdownloads_files WHERE published = 1 AND catid IN ($catid) AND access IN ($groups) ORDER BY $orderCol $listOrderNew ".$count);
            }       
            $cat_result = '';
            $files = $db->loadObjectList();
           
            if ($files){
                foreach ($files as $file){
                    $matches[1]  = 'file';
                    $matches[2]  = $file->id;
                    $file_result = jd_file_createdownload($matches); 
                    $cat_result .= $file_result;   
                }    
            }    

            if ($cat_result) {
                return $cat_result; 
            } else {
                return NULL;
            }
        } else {
            // seems that user have not the permissions to view items from this category/categories
            // or wrong category IDs used
            return '';
        }     
    } 
    
    /**
    * Check if a asset file has already been loaded.
    * 
    * @var      string      $asset      the searched asset file without path
    *           string      $type       type (css or js)
    *           array       $document
    * 
    * @return   boolean     true when the asset was found
    * 
    * @since 4.0.35
    */
    function checkAssetAlreadyLoaded($asset, $type, $document)
    {
        // Check if asset file exist always in document
        if ($type == 'css'){ 
            
            if (isset($document->_styleSheets)){
                if (array_key_exists(Uri::base().$asset, $document->_styleSheets)){
                    return true;    
                }
            }
            return false;
        
        } elseif ($type == 'js'){
            
            if (isset($document->_scripts)){
                if (array_key_exists(Uri::base().$asset, $document->_scripts)){
                    return true;    
                }
            }
            return false;    
        }    
    }
     
?>