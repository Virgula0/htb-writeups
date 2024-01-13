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
 
defined('_JEXEC') or die('Restricted access');

setlocale(LC_ALL, 'C.UTF-8', 'C');

use Joomla\CMS\Application\ApplicationHelper;
use Joomla\Utilities\ArrayHelper; 
use Joomla\String\StringHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Session\Session;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Filesystem\File;
use Joomla\CMS\Filesystem\Folder;
use Joomla\CMS\Log\Log;
use Joomla\CMS\Installer\Installer;
use Joomla\CMS\Installer\InstallerScript;
use Joomla\CMS\Table\Table;
use Joomla\CMS\Installer\InstallerAdapter;
use Joomla\CMS\Object\CMSObject;
use Joomla\CMS\Version;

HtmlHelper::_('behavior.formvalidator');
HtmlHelper::_('behavior.keepalive');

/**
 * Install Script file of jDownloads component
 */
class com_jdownloadsInstallerScript extends InstallerScript
{
	private $new_version;
    private $new_version_short;
    private $target_joomla_version;
    private $old_version_short;
    private $install_msg;
    private $run_upgrade_from_39;
    private $categories_table;
    private $files_table;
    private $templates_table;
    private $licenses_table;
    private $logs_table;
    private $usergroups_limits;
    private $wrong_table = array();
    private $wrong_fields = array();
    
    /**
	 * Method to install the component
	 *
	 * @return void
	 */
	public function install($parent) 
	{
        // Try to set time limit
        @set_time_limit(0);

        // Try to increase memory limit
        if ((int) ini_get( 'memory_limit' ) < 32){
            @ini_set( 'memory_limit', '32M' );
        }
        
        $db = Factory::getDBO();
        $user = Factory::getApplication()->getIdentity();

        // Add a log entry
        self::addLog(Text::sprintf('COM_JDOWNLOADS_INSTALL_LOG_START', $user->id, $user->name, $this->new_version), 'Log::INFO', false);                
        
        $params = ComponentHelper::getParams('com_jdownloads');
        $files_upload_dir = $params->get( 'files_uploaddir' );
        
		// Insert the default layouts.
		require_once (JPATH_ADMINISTRATOR.'/components/com_jdownloads/src/Helper/StandardLayouts.php');
		
        /*
        / Copy frontend images to the joomla images folder
        */
        $target = JPATH_ROOT.'/images/jdownloads';
        $source = dirname(__FILE__).'/site/assets/images/jdownloads';
        
        $images_copy_result   = false;
        $images_folder_exists = false;
        
        if (!Folder::exists($target)){
            $images_copy_result = Folder::copy($source,$target);
        } else {
            $images_folder_exists = true;
        }       

        // Check whether custom css file already exist
        $custom_css_path = JPATH_ROOT.'/components/com_jdownloads/assets/css/jdownloads_custom.css';
        if (!File::exists($custom_css_path)){
            // create a new css file
            $text  = "/* Custom CSS File for jDownloads\n";
            $text .= "   If this file already exist then jDownloads does not overwrite it when installing or upgrading jDownloads.\n";
            $text .= "   This file is loaded after the standard jdownloads_fe.css.\n";   
            $text .= "   So you can use it to overwrite the standard css classes for your own customising.\n*/";               
            $x = file_put_contents($custom_css_path, $text, FILE_APPEND);
        }
        
        /*
        / Install modules and plugins
        */
        $status = new CMSObject();
        $status->modules = array();
        $status->plugins = array();
        $src_modules = dirname(__FILE__).'/modules';
        $src_plugins = dirname(__FILE__).'/plugins';

        // Install Plugins
        $installer = new Installer;
        $result = $installer->install($src_plugins.'/plg_system_jdownloads');
        $status->plugins[] = array('name'=>'jDownloads System Plugin','group'=>'system', 'result'=>$result);
        
        // System plugin must be enabled for user group limits
        $db->setQuery("UPDATE #__extensions SET enabled = '1' WHERE `name` = 'plg_system_jdownloads' AND `type` = 'plugin'");
        $db->execute();

        $installer = new Installer;
        $result = $installer->install($src_plugins.'/editor_button_plugin_jdownloads_downloads');
        $status->plugins[] = array('name'=>'jDownloads Download Content Button Plugin','group'=>'editors-xtd', 'result'=>$result);        
        
        $installer = new Installer;
        $result = $installer->install($src_plugins.'/plg_content_jdownloads');
        $status->plugins[] = array('name'=>'jDownloads Content Plugin','group'=>'content', 'result'=>$result);        

        $installer = new Installer;
        $result = $installer->install($src_plugins.'/plg_finder_jdownloads');
        $status->plugins[] = array('name'=>'jDownloads Finder Plugin','group'=>'finder', 'result'=>$result);

        $installer = new Installer;
        $result = $installer->install($src_plugins.'/plg_finder_folder');
        $status->plugins[] = array('name'=>'jDownloads Finder Categories Plugin','group'=>'finder', 'result'=>$result);
        
        $installer = new Installer;
        $result = $installer->install($src_plugins.'/plg_content_jdownloads_tags_fix');
        $status->plugins[] = array('name'=>'jDownloads Tags Fix Content Plugin','group'=>'content', 'result'=>$result);        

		// 'Tags' fix plugin must be enabled 
        $db->setQuery("UPDATE #__extensions SET enabled = '1' WHERE `name` = 'plg_content_jdownloads_tags_fix' AND `type` = 'plugin'");
        $db->execute();		

        // Install Modules
        $installer = new Installer;
        $result = $installer->install($src_modules.'/mod_jdownloads_latest');
        $status->modules[] = array('name'=>'jDownloads Latest Module','client'=>'site', 'result'=>$result);

        $installer = new Installer;
        $result = $installer->install($src_modules.'/mod_jdownloads_top');
        $status->modules[] = array('name'=>'jDownloads Top Module','client'=>'site', 'result'=>$result);

        $installer = new Installer;
        $result = $installer->install($src_modules.'/mod_jdownloads_last_updated');
        $status->modules[] = array('name'=>'jDownloads Last Updated Module','client'=>'site', 'result'=>$result);

        $installer = new Installer;
        $result = $installer->install($src_modules.'/mod_jdownloads_most_recently_downloaded');
        $status->modules[] = array('name'=>'jDownloads Most Recently Downloaded Module','client'=>'site', 'result'=>$result);
        
        $installer = new Installer;
        $result = $installer->install($src_modules.'/mod_jdownloads_stats');
        $status->modules[] = array('name'=>'jDownloads Stats Module','client'=>'site', 'result'=>$result);        

        $installer = new Installer;
        $result = $installer->install($src_modules.'/mod_jdownloads_tree');
        $status->modules[] = array('name'=>'jDownloads Tree Module','client'=>'site', 'result'=>$result);
        
        $installer = new Installer;
        $result = $installer->install($src_modules.'/mod_jdownloads_related');
        $status->modules[] = array('name'=>'jDownloads Related Module','client'=>'site', 'result'=>$result);        

        $installer = new Installer;
        $result = $installer->install($src_modules.'/mod_jdownloads_rated');
        $status->modules[] = array('name'=>'jDownloads Rated Module','client'=>'site', 'result'=>$result);

        $installer = new Installer;
        $result = $installer->install($src_modules.'/mod_jdownloads_featured');
        $status->modules[] = array('name'=>'jDownloads Featured Module','client'=>'site', 'result'=>$result);
        
        $installer = new Installer;
        $result = $installer->install($src_modules.'/mod_jdownloads_view_limits');
        $status->modules[] = array('name'=>'jDownloads View Limits Module','client'=>'site', 'result'=>$result);
        
        $installer = new Installer;
        $result = $installer->install($src_modules.'/mod_jdownloads_admin_stats');
        $status->modules[] = array('name'=>'jDownloads Admin Stats Module','client'=>'admin', 'result'=>$result);
        
        // Admin stats module (should be published on position 'jdcpanel').
        $mod_array = array ("view_latest" => "1", "view_popular" => "1", "view_featured" => "1", "view_most_rated" => "1", "view_top_rated" => "1", "amount_items" => "5", "view_statistics" => "1", "view_monitoring_log" => "1", "view_restore_log" => "1", "view_server_info" => "1", "layout" => "_:default", "moduleclass_sfx" => "", "cache" => "0", "cache_time" => "900", "module_tag" => "div", "bootstrap_size" => "0", "header_tag" => "h3", "header_class" => "", "style" => "0");
        $mod_params = json_encode($mod_array, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        $db->setQuery("UPDATE #__modules SET `published` = '1', `title` = '".Text::_('COM_JDOWNLOADS_STATS_MODULE_TITLE')."', `position` = 'jdcpanel', `ordering` = '1', `showtitle` = '1', `params` = '".$mod_params."' WHERE `module` = 'mod_jdownloads_admin_stats'");
        $db->execute();
        
        // It must also exist a dataset in the _modules_menu table to get the module visible!
        // Get the right ID
        $db->setQuery("SELECT id FROM #__modules WHERE `module` = 'mod_jdownloads_admin_stats'");
        $module_id = (int)$db->loadResult();
        
        if ($module_id){
            $db->setQuery("SELECT COUNT(*) FROM #__modules_menu WHERE `moduleid` = '$module_id'");
            $result = (int)$db->loadResult();
            // Insert it only when not already exist            
            if (!$result){
            	$db->setQuery("INSERT INTO #__modules_menu (moduleid, menuid) VALUES ('$module_id' , '0')");
            	$db->execute();
        	}
        }
        
        // Monitoring admin module
        $installer = new Installer;
        $result = $installer->install($src_modules.'/mod_jdownloads_admin_monitoring');
        $status->modules[] = array('name'=>'jDownloads Admin Monitoring Module','client'=>'admin', 'result'=>$result);
        
        // Admin monitoring module should be published on position 'jdcpanel'.
        $mod_array = array ("layout" => "_:default", "moduleclass_sfx" => "", "cache" => "0", "cache_time" => "900", "module_tag" => "div", "bootstrap_size" => "0", "header_tag" => "h3", "header_class" => "", "style" => "0");
        $mod_params = json_encode($mod_array, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        $db->setQuery("UPDATE #__modules SET `published` = '1', `title` = '".Text::_('COM_JDOWNLOADS_MONITORING_MODULE_TITLE')."', `position` = 'jdcpanel', `ordering` = '2', `showtitle` = '1', `params` = '".$mod_params."' WHERE `module` = 'mod_jdownloads_admin_monitoring'");
        $db->execute();
        
        // It must also exist a dataset in the _modules_menu table to get the module visible!
        // Get the right ID
        $db->setQuery("SELECT id FROM #__modules WHERE `module` = 'mod_jdownloads_admin_monitoring'");
        $module_id = (int)$db->loadResult();
        
        if ($module_id){
            $db->setQuery("SELECT COUNT(*) FROM #__modules_menu WHERE `moduleid` = '$module_id'");
            $result = (int)$db->loadResult();
            // Insert it only when not already exist            
            if (!$result){
	            $db->setQuery("INSERT INTO #__modules_menu (moduleid, menuid) VALUES ('$module_id' , '0')");
	            $db->execute();
        	}
        }
        
        ?>
        <hr>
        <div class="adminlist" style="">
            <h4 style="color:#555;"><?php echo Text::_('COM_JDOWNLOADS_INSTALL_0'); ?></h4>
        <ul>

        <?php
        
        // Exist the jDownloads tables?
        // Get DB prefix string
        $prefix = self::getCorrectDBPrefix();
        $tablelist = $db->getTableList();
       
        if ( !in_array ( $prefix.'jdownloads_files', $tablelist ) ){
           Factory::getApplication()->enqueueMessage( Text::_('COM_JDOWNLOADS_INSTALL_ERROR_NO_TABLES'), 'warning');
           return false;  
       
        } else {
       
            $jd_version = $this->new_version_short;
              
            switch ($this->old_version_found){
               
                case '3.9':
                
                case '4.0':

                default:
                    // Fresh installation - Build upload root path
                    $jd_upload_root = JPATH_ROOT.'/jdownloads';
            } 
          
            if ($this->old_version_found == 0){
                /*
                / Install default configuration data - but only when we have really a 'fresh' installation and we have not found any old DB tables
                */
                $query = array();
                 
                // Write default layouts in database      
                $sum_layouts = 0;
				$name_extension = ' v3.9';

                // Categories Standard Layout  (activated by installation as default)
                $cats_layout       = stripslashes($JLIST_BACKEND_SETTINGS_TEMPLATES_CATS_DEFAULT);
                $cats_header       = stripslashes($cats_header);
                $cats_subheader    = stripslashes($cats_subheader);
                $cats_footer       = stripslashes($cats_footer);
                $db->setQuery("INSERT INTO #__jdownloads_templates (template_name, template_typ, template_text, template_header_text, template_subheader_text, template_footer_text, template_before_text, template_after_text, note, template_active, locked, language, preview_id)  VALUES ('".$db->escape(Text::_('COM_JDOWNLOADS_BACKEND_SETTINGS_TEMPLATES_CATS_DEFAULT_NAME')).$name_extension."', 1, '".$cats_layout."', '".$cats_header."', '".$cats_subheader."', '".$cats_footer."', '', '', '', 1, 1, '*', 1)");
                $db->execute();
                $sum_layouts++;

                // Categories Layout with 4 columns
                $cats_layout = stripslashes($JLIST_BACKEND_SETTINGS_TEMPLATES_CATS_COL_DEFAULT); 
                $db->setQuery("INSERT INTO #__jdownloads_templates (template_name, template_typ, template_text, template_header_text, template_subheader_text, template_footer_text, template_before_text, template_after_text, template_active, locked, note, cols, language, preview_id)  VALUES ('".$db->escape(Text::_('COM_JDOWNLOADS_BACKEND_SETTINGS_TEMPLATES_CATS_COL_TITLE')).$name_extension."', 1, '".$cats_layout."', '".$cats_header."', '".$cats_subheader."', '".$cats_footer."', '', '', 0, 1, '".$db->escape(Text::_('COM_JDOWNLOADS_BACKEND_SETTINGS_TEMPLATES_CATS_COL_NOTE'))."', 4, '*', 2)");
                $db->execute();
                $sum_layouts++;

                // Categories Layout with 2 columns
                $cats_layout = stripslashes($JLIST_BACKEND_SETTINGS_TEMPLATES_CATS_COL2_DEFAULT); 
                $db->setQuery("INSERT INTO #__jdownloads_templates (template_name, template_typ, template_text, template_header_text, template_subheader_text, template_footer_text, template_before_text, template_after_text, template_active, locked, note, cols, language, preview_id)  VALUES ('".$db->escape(Text::_('COM_JDOWNLOADS_BACKEND_SETTINGS_TEMPLATES_CATS_COL2_TITLE')).$name_extension."', 1, '".$cats_layout."', '".$cats_header."', '".$cats_subheader."', '".$cats_footer."', '', '', 0, 1, '', 2, '*', 3)");
                $db->execute();
                $sum_layouts++;
                                      
                // This layout is used to view the subcategories from a category. 
                $cats_layout        = stripslashes($JLIST_BACKEND_SETTINGS_TEMPLATES_SUBCATS_PAGINATION_DEFAULT);
                $cats_layout_before = stripslashes($JLIST_BACKEND_SETTINGS_TEMPLATES_SUBCATS_PAGINATION_BEFORE);
                $cats_layout_after  = stripslashes($JLIST_BACKEND_SETTINGS_TEMPLATES_SUBCATS_PAGINATION_AFTER);
                $cats_header       = '';
                $cats_subheader    = '';
                $cats_footer       = '';
                $note              = stripslashes(Text::_('COM_JDOWNLOADS_BACKEND_TEMPEDIT_USE_SUBCATS_NOTE'));
                $db->setQuery("INSERT INTO #__jdownloads_templates (template_name, template_typ, template_text, template_header_text, template_subheader_text, template_footer_text, template_before_text, template_after_text, note, template_active, locked, cols, language, preview_id )  VALUES ('".$db->escape(Text::_('COM_JDOWNLOADS_BACKEND_SETTINGS_TEMPLATES_CATS_DEFAULT_PAGINATION_NAME')).$name_extension."', 8, '".$cats_layout."', '".$cats_header."', '".$cats_subheader."', '".$cats_footer."', '".$cats_layout_before."', '".$cats_layout_after."', '".$db->escape($note)."', 1, 1, 1, '*', 4)");
                $db->execute();
                $sum_layouts++;

                // This layout is used to view the subcategories from a category in a multi column example. 
                $cats_layout        = stripslashes($JLIST_BACKEND_SETTINGS_TEMPLATES_SUBCATS_MULTICOLUMN_DEFAULT);
                $cats_layout_before = stripslashes($JLIST_BACKEND_SETTINGS_TEMPLATES_SUBCATS_PAGINATION_BEFORE);
                $cats_layout_after  = stripslashes($JLIST_BACKEND_SETTINGS_TEMPLATES_SUBCATS_PAGINATION_AFTER);
                $cats_header       = '';
                $cats_subheader    = '';
                $cats_footer       = '';
                $note              = stripslashes(Text::_('COM_JDOWNLOADS_BACKEND_TEMPEDIT_USE_SUBCATS_NOTE'));
                $db->setQuery("INSERT INTO #__jdownloads_templates (template_name, template_typ, template_text, template_header_text, template_subheader_text, template_footer_text, template_before_text, template_after_text, note, template_active, locked, cols, language, preview_id )  VALUES ('".$db->escape(Text::_('COM_JDOWNLOADS_BACKEND_SETTINGS_TEMPLATES_SUBCAT_DEFAULT_NAME')).$name_extension."', 8, '".$cats_layout."', '".$cats_header."', '".$cats_subheader."', '".$cats_footer."', '".$cats_layout_before."', '".$cats_layout_after."', '".$db->escape($note)."', 0, 1, 4, '*', 5)");
                $db->execute();
                $sum_layouts++;
                                      
                // Category Standard Layout (activated by installation as default)
                $cat_layout       = stripslashes($JLIST_BACKEND_SETTINGS_TEMPLATES_CAT_DEFAULT);
                $cat_header       = stripslashes($cat_header);
                $cat_subheader    = stripslashes($cat_subheader);
                $cat_footer       = stripslashes($cat_footer);
                $db->setQuery("INSERT INTO #__jdownloads_templates (template_name, template_typ, template_text, template_header_text, template_subheader_text, template_footer_text, template_before_text, template_after_text, note, template_active, locked, language, preview_id)  VALUES ('".$db->escape(Text::_('COM_JDOWNLOADS_BACKEND_SETTINGS_TEMPLATES_CAT_DEFAULT_NAME')).$name_extension."', 4, '".$cat_layout."', '".$cat_header."', '".$cat_subheader."', '".$cat_footer."', '', '', '', 1, 1, '*', 6)");
                $db->execute();              
                $sum_layouts++;

                // Files Standard Layout (with mini icons)
                $files_layout       = stripslashes($JLIST_BACKEND_SETTINGS_TEMPLATES_FILES_DEFAULT);
                $files_header       = stripslashes($files_header);
                $files_subheader    = stripslashes($files_subheader);
                $files_footer       = stripslashes($files_footer);
                $db->setQuery("INSERT INTO #__jdownloads_templates (template_name, template_typ, template_text, template_header_text, template_subheader_text, template_footer_text, template_before_text, template_after_text, template_active, locked, note, checkbox_off, symbol_off, language, preview_id)  VALUES ('".$db->escape(Text::_('COM_JDOWNLOADS_BACKEND_SETTINGS_TEMPLATES_FILES_DEFAULT_NAME')).$name_extension."', 2, '".$files_layout."', '".$files_header."', '".$files_subheader."', '".$files_footer."', '', '', 0, 1, '', 1, 0, '*', 7)");
                $db->execute();
                $sum_layouts++;

                // Files Simple Layout with Checkboxes
                $files_layout = stripslashes($JLIST_BACKEND_SETTINGS_TEMPLATES_FILES_DEFAULT_NEW_SIMPLE_1); 
                $db->setQuery("INSERT INTO #__jdownloads_templates (template_name, template_typ, template_text, template_header_text, template_subheader_text, template_footer_text, template_before_text, template_after_text, template_active, locked, note, checkbox_off, symbol_off, language, preview_id)  VALUES ('".$db->escape(Text::_('COM_JDOWNLOADS_BACKEND_SETTINGS_TEMPLATES_FILES_DEFAULT_NEW_SIMPLE_1_NAME')).$name_extension."', 2, '".$files_layout."', '".$files_header."', '".$files_subheader."', '".$files_footer."', '', '', 0, 1, '', 0, 1, '*', 8)");
                $db->execute();
                $sum_layouts++;
                    
                // Files Simple Layout without Checkboxes (activated by installation as default)
                $files_layout = stripslashes($JLIST_BACKEND_SETTINGS_TEMPLATES_FILES_DEFAULT_NEW_SIMPLE_2); 
                $db->setQuery("INSERT INTO #__jdownloads_templates (template_name, template_typ, template_text, template_header_text, template_subheader_text, template_footer_text, template_before_text, template_after_text, template_active, locked, note, checkbox_off, symbol_off, language, preview_id)  VALUES ('".$db->escape(Text::_('COM_JDOWNLOADS_BACKEND_SETTINGS_TEMPLATES_FILES_DEFAULT_NEW_SIMPLE_2_NAME')).$name_extension."', 2, '".$files_layout."', '".$files_header."', '".$files_subheader."', '".$files_footer."', '', '', 1, 1, '', 1, 1, '*', 9)");
                $db->execute();
                $sum_layouts++;

                // Files Layout - Alternate
                $files_layout        = stripslashes($JLIST_BACKEND_SETTINGS_TEMPLATES_FILES_NEW_ALTERNATE_1);
                $files_layout_before = stripslashes($JLIST_BACKEND_SETTINGS_TEMPLATES_FILES_NEW_ALTERNATE_1_BEFORE);
                $files_layout_after  = stripslashes($JLIST_BACKEND_SETTINGS_TEMPLATES_FILES_NEW_ALTERNATE_1_AFTER);
                $db->setQuery("INSERT INTO #__jdownloads_templates (template_name, template_typ, template_text, template_header_text, template_subheader_text, template_footer_text, template_before_text, template_after_text, template_active, locked, note, checkbox_off, symbol_off, language, preview_id)  VALUES ('".$db->escape(Text::_('COM_JDOWNLOADS_BACKEND_SETTINGS_TEMPLATES_FILES_DEFAULT_NEW_ALTERNATE_1_NAME')).$name_extension."', 2, '".$files_layout."', '".$files_header."', '".$files_subheader."', '".$files_footer."', '".$files_layout_before."', '".$files_layout_after."', 0, 1, '', 1, 1, '*', 10)");
                $db->execute();
                $sum_layouts++;                            

                // Files Layout with Full Info
                $files_layout = stripslashes($JLIST_BACKEND_SETTINGS_TEMPLATES_FILES_FULL_INFO); 
                $db->setQuery("INSERT INTO #__jdownloads_templates (template_name, template_typ, template_text, template_header_text, template_subheader_text, template_footer_text, template_before_text, template_after_text, template_active, locked, note, checkbox_off, symbol_off, language, preview_id)  VALUES ('".$db->escape(Text::_('COM_JDOWNLOADS_BACKEND_SETTINGS_TEMPLATES_FILES_FULL_INFO_NAME')).$name_extension."', 2, '".$files_layout."', '".$files_header."', '".$files_subheader."', '".$files_footer."', '', '', 0, 1, '', 1, 1, '*', 11)");
                $db->execute();
                $sum_layouts++;

                // Files Layout - Just a Link
                $files_layout = stripslashes($JLIST_BACKEND_SETTINGS_TEMPLATES_FILES_JUST_LINK); 
                $db->setQuery("INSERT INTO #__jdownloads_templates (template_name, template_typ, template_text, template_header_text, template_subheader_text, template_footer_text, template_before_text, template_after_text, template_active, locked, note, checkbox_off, symbol_off, language, preview_id)  VALUES ('".$db->escape(Text::_('COM_JDOWNLOADS_BACKEND_SETTINGS_TEMPLATES_FILES_JUST_LINK_NAME')).$name_extension."', 2, '".$files_layout."', '".$files_header."', '".$files_subheader."', '".$files_footer."', '', '', 0, 1, '', 1, 1, '*', 12)");
                $db->execute();
                $sum_layouts++;

                // Files Layout - Single Line
                $files_layout = stripslashes($JLIST_BACKEND_SETTINGS_TEMPLATES_FILES_SINGLE_LINE); 
                $db->setQuery("INSERT INTO #__jdownloads_templates (template_name, template_typ, template_text, template_header_text, template_subheader_text, template_footer_text, template_before_text, template_after_text, template_active, locked, note, checkbox_off, symbol_off, language, preview_id)  VALUES ('".$db->escape(Text::_('COM_JDOWNLOADS_BACKEND_SETTINGS_TEMPLATES_FILES_SINGLE_LINE_NAME')).$name_extension."', 2, '".$files_layout."', '".$files_header."', '".$files_subheader."', '".$files_footer."', '', '', 0, 1, '', 1, 1, '*', 13)");
                $db->execute();
                $sum_layouts++;

                // Files Layout - Compact with checkboxes v.3.9 (by Colin)
                $files_layout = stripslashes($JLIST_BACKEND_SETTINGS_TEMPLATES_FILES_COMPACT_CHECKBOXES); 
                $db->setQuery("INSERT INTO #__jdownloads_templates (template_name, template_typ, template_text, template_header_text, template_subheader_text, template_footer_text, template_before_text, template_after_text, template_active, locked, note, checkbox_off, symbol_off, language, preview_id)  VALUES ('".$db->escape(Text::_('COM_JDOWNLOADS_BACKEND_SETTINGS_TEMPLATES_FILES_DEFAULT_COMPACT_NAME_2')).$name_extension."', 2, '".$files_layout."', '".$files_header."', '".$files_subheader."', '".$files_footer."', '', '', 0, 1, '', 0, 1, '*', 14)");
                $db->execute();
                $sum_layouts++;

                // Files Layout - Compact with download buttons v.3.9 (by Colin)
                $files_layout = stripslashes($JLIST_BACKEND_SETTINGS_TEMPLATES_FILES_COMPACT_WITHOUT_CHECKBOXES); 
                $db->setQuery("INSERT INTO #__jdownloads_templates (template_name, template_typ, template_text, template_header_text, template_subheader_text, template_footer_text, template_before_text, template_after_text, template_active, locked, note, checkbox_off, symbol_off, language, preview_id)  VALUES ('".$db->escape(Text::_('COM_JDOWNLOADS_BACKEND_SETTINGS_TEMPLATES_FILES_DEFAULT_COMPACT_NAME_1')).$name_extension."', 2, '".$files_layout."', '".$files_header."', '".$files_subheader."', '".$files_footer."', '', '', 0, 1, '', 1, 1, '*', 15)");
                $db->execute();
                $sum_layouts++;

                // Details Standard Layout
                $detail_layout        = stripslashes($JLIST_BACKEND_SETTINGS_TEMPLATES_DETAILS_DEFAULT);
                $details_header       = stripslashes($details_header);
                $details_subheader    = stripslashes($details_subheader);
                $details_footer       = stripslashes($details_footer);               
                $db->setQuery("INSERT INTO #__jdownloads_templates (template_name, template_typ, template_text, template_header_text, template_subheader_text, template_footer_text, template_before_text, template_after_text, note, template_active, locked, symbol_off, language, preview_id)  VALUES ('".$db->escape(Text::_('COM_JDOWNLOADS_BACKEND_SETTINGS_TEMPLATES_DETAILS_DEFAULT_NAME')).$name_extension."', 5, '$detail_layout', '".$details_header."', '".$details_subheader."', '".$details_footer."', '', '', '', 1, 1, 1, '*', 16)");
                $db->execute();
                $sum_layouts++;

                // Details Layout with Tabs
                $detail_layout = stripslashes($JLIST_BACKEND_SETTINGS_TEMPLATES_DETAILS_DEFAULT_WITH_TABS);
                $db->setQuery("INSERT INTO #__jdownloads_templates (template_name, template_typ, template_text, template_header_text, template_subheader_text, template_footer_text, template_before_text, template_after_text, note, template_active, locked, symbol_off, language, preview_id)  VALUES ('".$db->escape(Text::_('COM_JDOWNLOADS_BACKEND_SETTINGS_TEMPLATES_DETAILS_WITH_TABS_TITLE')).$name_extension."', 5, '$detail_layout', '".$details_header."', '".$details_subheader."', '".$details_footer."', '', '', '', '0', 1, 1, '*', 17)");
                $db->execute();
                $sum_layouts++;

                // Details Layout with all new Data Fields v2.5
                $detail_layout = stripslashes($JLIST_BACKEND_SETTINGS_TEMPLATES_DETAILS_DEFAULT_NEW_25);
                $db->setQuery("INSERT INTO #__jdownloads_templates (template_name, template_typ, template_text, template_header_text, template_subheader_text, template_footer_text, template_before_text, template_after_text, note, template_active, locked, symbol_off, language, preview_id)  VALUES ('".$db->escape(Text::_('COM_JDOWNLOADS_BACKEND_SETTINGS_TEMPLATES_DETAILS_25_TITLE')).$name_extension."', 5, '$detail_layout', '".$details_header."', '".$details_subheader."', '".$details_footer."', '', '', '', '0', 1, 1, '*', 18)");
                $db->execute();
                $sum_layouts++;

                // Details Layout with all new Data Fields (FULL Info with Related) v3.9
                $detail_layout = stripslashes($JLIST_BACKEND_SETTINGS_TEMPLATES_DETAILS_DEFAULT_WITH_RELATED);
                $db->setQuery("INSERT INTO #__jdownloads_templates (template_name, template_typ, template_text, template_header_text, template_subheader_text, template_footer_text, template_before_text, template_after_text, note, template_active, locked, symbol_off, language, preview_id)  VALUES ('".$db->escape(Text::_('COM_JDOWNLOADS_BACKEND_SETTINGS_TEMPLATES_DETAILS_WITH_RELATED_TITLE')).$name_extension."', 5, '$detail_layout', '".$details_header."', '".$details_subheader."', '".$details_footer."', '', '', '', '0', 1, 1, '*', 19)");
                $db->execute();
                $sum_layouts++;

                // New details Layout whsh use W3.CSS option v3.9
                $detail_layout = stripslashes($JLIST_BACKEND_SETTINGS_TEMPLATES_DETAILS_DEFAULT_WITH_W3CSS);
                $db->setQuery("INSERT INTO #__jdownloads_templates (template_name, template_typ, template_text, template_header_text, template_subheader_text, template_footer_text, template_before_text, template_after_text, note, template_active, locked, symbol_off, uses_w3css, language, preview_id)  VALUES ('".$db->escape(Text::_('COM_JDOWNLOADS_BACKEND_SETTINGS_TEMPLATES_DETAILS_DEFAULT_WITH_W3CSS_NAME').' v3.9')."', 5, '$detail_layout', '".$details_header."', '".$details_subheader."', '".$details_footer."', '', '', '', '0', 1, 1, 1, '*', 23)");
                $db->execute();
                $sum_layouts++;              

                // Summary Standard Layout
                $summary_layout       = stripslashes($JLIST_BACKEND_SETTINGS_TEMPLATES_SUMMARY_DEFAULT);
                $summary_header      = stripslashes($summary_header);
                $summary_subheader    = stripslashes($summary_subheader);
                $summary_footer       = stripslashes($summary_footer);              
                $db->setQuery("INSERT INTO #__jdownloads_templates (template_name, template_typ, template_text, template_header_text, template_subheader_text, template_footer_text, template_before_text, template_after_text, note, template_active, locked, language, preview_id)  VALUES ('".$db->escape(Text::_('COM_JDOWNLOADS_BACKEND_SETTINGS_TEMPLATES_SUMMARY_DEFAULT_NAME')).$name_extension."', 3, '".$summary_layout."', '".$summary_header."', '".$summary_subheader."', '".$summary_footer."', '', '', '', 1, 1, '*', 20)");
                $db->execute();
                $sum_layouts++;

                // Default search results layout vertical (for internal search function) - take it from $search2_header, $search2_subheader and $search2_footer
                $search_result_layout = stripslashes($JLIST_BACKEND_SETTINGS_TEMPLATES_SEARCH_DEFAULT);
                $search_header       = stripslashes($search_header);
                $search_subheader    = stripslashes($search_subheader);
                $search_footer       = stripslashes($search_footer);  
                $db->setQuery("INSERT INTO #__jdownloads_templates (template_name, template_typ, template_text, template_header_text, template_subheader_text, template_footer_text, template_before_text, template_after_text, template_active, locked, note, cols, language, preview_id)  VALUES ('".$db->escape(Text::_('COM_JDOWNLOADS_BACKEND_SETTINGS_TEMPLATES_SEARCH_DEFAULT_NAME')).$name_extension."', 7, '".$search_result_layout."', '".$search_header."', '".$search_subheader."', '".$search_footer."', '', '', 1, 1, '', 4, '*', 21)");
                $db->execute();
                $sum_layouts++;

                // Horizontal search results layout - (for internal search function) - take it from $search2_header, $search2_subheader and $search2_footer
                $search_result_layout = stripslashes($JLIST_BACKEND_SETTINGS_TEMPLATES_SEARCH_DEFAULT_HORIZONTAL);
                $search_header       = stripslashes($search2_header);
                $search_subheader    = stripslashes($search2_subheader);
                $search_footer       = stripslashes($search2_footer);  
                $db->setQuery("INSERT INTO #__jdownloads_templates (template_name, template_typ, template_text, template_header_text, template_subheader_text, template_footer_text, template_before_text, template_after_text, template_active, locked, note, cols, language, preview_id)  VALUES ('".$db->escape(Text::_('COM_JDOWNLOADS_BACKEND_SETTINGS_TEMPLATES_SEARCH_DEFAULT2_NAME')).$name_extension."', 7, '".$search_result_layout."', '".$search_header."', '".$search_subheader."', '".$search_footer."', '', '', 0, 1, '', 4, '*', 22)");
                $db->execute();
                $sum_layouts++;                  

                echo '<li><font color="green">'.Text::sprintf('COM_JDOWNLOADS_INSTALL_4', $sum_layouts).'</font></li>';

                // Write default licenses in database      

                $sum_licenses = 7;

                $db->setQuery("INSERT INTO #__jdownloads_licenses (title, alias, description, url, language, published, ordering)  VALUES ('".$db->escape(Text::_('COM_JDOWNLOADS_SETTINGS_LICENSE1_TITLE'))."', '".ApplicationHelper::stringURLSafe(Text::_('COM_JDOWNLOADS_SETTINGS_LICENSE1_TITLE'))."', '', '".Text::_('COM_JDOWNLOADS_SETTINGS_LICENSE1_URL')."', '*', 1, 1)");
                $db->execute();

                $db->setQuery("INSERT INTO #__jdownloads_licenses (title, alias, description, url, language, published, ordering)  VALUES ('".$db->escape(Text::_('COM_JDOWNLOADS_SETTINGS_LICENSE2_TITLE'))."', '".ApplicationHelper::stringURLSafe(Text::_('COM_JDOWNLOADS_SETTINGS_LICENSE2_TITLE'))."', '', '".Text::_('COM_JDOWNLOADS_SETTINGS_LICENSE2_URL')."', '*', 1, 2)");
                $db->execute();

                $db->setQuery("INSERT INTO #__jdownloads_licenses (title, alias, description, url, language, published, ordering)  VALUES ('".$db->escape(Text::_('COM_JDOWNLOADS_SETTINGS_LICENSE3_TITLE'))."', '".ApplicationHelper::stringURLSafe(Text::_('COM_JDOWNLOADS_SETTINGS_LICENSE3_TITLE'))."', '".Text::_('COM_JDOWNLOADS_SETTINGS_LICENSE3_TEXT')."', '', '*', 1, 3)");
                $db->execute();

                $db->setQuery("INSERT INTO #__jdownloads_licenses (title, alias, description, url, language, published, ordering)  VALUES ('".$db->escape(Text::_('COM_JDOWNLOADS_SETTINGS_LICENSE4_TITLE'))."', '".ApplicationHelper::stringURLSafe(Text::_('COM_JDOWNLOADS_SETTINGS_LICENSE4_TITLE'))."', '".Text::_('COM_JDOWNLOADS_SETTINGS_LICENSE4_TEXT')."', '', '*', 1, 4)");
                $db->execute();

                $db->setQuery("INSERT INTO #__jdownloads_licenses (title, alias, description, url, language, published, ordering)  VALUES ('".$db->escape(Text::_('COM_JDOWNLOADS_SETTINGS_LICENSE5_TITLE'))."', '".ApplicationHelper::stringURLSafe(Text::_('COM_JDOWNLOADS_SETTINGS_LICENSE5_TITLE'))."', '".Text::_('COM_JDOWNLOADS_SETTINGS_LICENSE5_TEXT')."', '', '*', 1, 5)");
                $db->execute();

                $db->setQuery("INSERT INTO #__jdownloads_licenses (title, alias, description, url, language, published, ordering)  VALUES ('".$db->escape(Text::_('COM_JDOWNLOADS_SETTINGS_LICENSE6_TITLE'))."', '".ApplicationHelper::stringURLSafe(Text::_('COM_JDOWNLOADS_SETTINGS_LICENSE6_TITLE'))."', '', '', '*', 1, 1)");
                $db->execute();

                $db->setQuery("INSERT INTO #__jdownloads_licenses (title, alias, description, url, language, published, ordering)  VALUES ('".$db->escape(Text::_('COM_JDOWNLOADS_SETTINGS_LICENSE7_TITLE'))."', '".ApplicationHelper::stringURLSafe(Text::_('COM_JDOWNLOADS_SETTINGS_LICENSE7_TITLE'))."', '', '".Text::_('COM_JDOWNLOADS_SETTINGS_LICENSE7_URL')."', '*', 1, 6)");
                $db->execute();

                self::addLog(Text::sprintf('COM_JDOWNLOADS_INSTALL_6', $sum_licenses), 'Log::INFO');

                echo '<li><font color="green">'.Text::sprintf('COM_JDOWNLOADS_INSTALL_6', $sum_licenses).'</font></li>';
            }              
          
            // Final checks
          
            // Checked if exist Falang - if yes, move the files

            if (Folder::exists(JPATH_SITE.'/administrator/components/com_falang/contentelements') && !File::exists(JPATH_SITE.'/administrator/components/com_falang/contentelements/jdownloads_files.xml')){
                $fishresult = 1;
                File::copy( JPATH_SITE."/administrator/components/com_jdownloads/assets/falang/jdownloads_categories.xml", JPATH_SITE."/administrator/components/com_falang/contentelements/jdownloads_categories.xml");
                File::copy( JPATH_SITE."/administrator/components/com_jdownloads/assets/falang/jdownloads_files.xml", JPATH_SITE."/administrator/components/com_falang/contentelements/jdownloads_files.xml");
                File::copy( JPATH_SITE."/administrator/components/com_jdownloads/assets/falang/jdownloads_templates.xml", JPATH_SITE."/administrator/components/com_falang/contentelements/jdownloads_templates.xml");
                File::copy( JPATH_SITE."/administrator/components/com_jdownloads/assets/falang/jdownloads_licenses.xml", JPATH_SITE."/administrator/components/com_falang/contentelements/jdownloads_licenses.xml");
                File::copy( JPATH_SITE."/administrator/components/com_jdownloads/assets/falang/jdownloads_usergroups_limits.xml", JPATH_SITE."/administrator/components/com_falang/contentelements/jdownloads_usergroups_limits.xml");
                Folder::delete( JPATH_SITE."/administrator/components/com_jdownloads/assets/falang"); 
            } else { 
                $fishresult = 0;
            }               
          
            if ($fishresult) {
                self::addLog(Text::_('COM_JDOWNLOADS_INSTALL_17')." ".JPATH_SITE.'/administrator/components/com_falang/contentelements', 'Log::INFO');
                echo '<li><font color="green">'.Text::_('COM_JDOWNLOADS_INSTALL_17')." ".JPATH_SITE.'/administrator/components/com_falang/contentelements'.'</font></li>';
            } else {
                self::addLog(Text::_('COM_JDOWNLOADS_INSTALL_18')." ".JPATH_SITE.'/administrator/components/com_jdownloads/assets/falang'.'<br />'.Text::_('COM_JDOWNLOADS_INSTALL_19'), 'Log::INFO');
                echo '<li><font color="green">'.Text::_('COM_JDOWNLOADS_INSTALL_18')." ".JPATH_SITE.'/administrator/components/com_jdownloads/assets/falang'.'<br />'.Text::_('COM_JDOWNLOADS_INSTALL_19').'</font></li>';
            }        
    
            // Check if the default upload directory exists 
            $dir_exist = Folder::exists($jd_upload_root);
            
            if ($dir_exist) {
                if (is_writable($jd_upload_root)) {
                    // Exist and is writable!
                    self::addLog(Text::_('COM_JDOWNLOADS_INSTALL_7'), 'Log::INFO');
                    echo '<li><font color="green">'.Text::_('COM_JDOWNLOADS_INSTALL_7').'</font></li>';
                } else {
                    // Exist but is NOT writable!
                    self::addLog(Text::_('COM_JDOWNLOADS_INSTALL_8'), 'Log::INFO');
                    echo '<li><font color="red"><strong>'.Text::_('COM_JDOWNLOADS_INSTALL_8').'</strong></font></li>';
                }
            } else {
                // Try to create it
                if ($makedir =  Folder::create($jd_upload_root, 0755)) {
                    // Succesful created
                    self::addLog(Text::_('COM_JDOWNLOADS_INSTALL_9'), 'Log::INFO');
                    echo '<li><font color="green">'.Text::_('COM_JDOWNLOADS_INSTALL_9').'</font></li>';
                } else {
                    // Could not create the folder!
                    self::addLog(Text::_('COM_JDOWNLOADS_INSTALL_10'), 'Log::INFO');
                    echo '<li><font color="red"><strong>'.Text::_('COM_JDOWNLOADS_INSTALL_10').'</strong></font></li>'; 
                }
            }
            
            // Check if the default 'preview files' directory exists
            $dir_exist_preview = Folder::exists($jd_upload_root.'/_preview_files');

            if ($dir_exist_preview) {
                if (is_writable($jd_upload_root.'/_preview_files')) {
                    // Exist and is writable!
                    self::addLog(Text::_('COM_JDOWNLOADS_INSTALL_30'), 'Log::INFO');
                    echo '<li><font color="green">'.Text::_('COM_JDOWNLOADS_INSTALL_30').'</font></li>';
                } else {
                    // Exist but is NOT writable!
                    self::addLog(Text::_('COM_JDOWNLOADS_INSTALL_31'), 'Log::INFO');
                    echo '<li><font color="red"><strong>'.Text::_('COM_JDOWNLOADS_INSTALL_31').'</strong></font></li>';
                }
            } else {
                if ($makedir =  Folder::create($jd_upload_root.'/_preview_files', 0755)) {
                    // Succesful created
                    self::addLog(Text::_('COM_JDOWNLOADS_INSTALL_28'), 'Log::INFO');
                    echo '<li><font color="green">'.Text::_('COM_JDOWNLOADS_INSTALL_28').'</font></li>';
                } else {
                    // Could not create the folder!
                    self::addLog(Text::_('COM_JDOWNLOADS_INSTALL_29'), 'Log::INFO');
                    echo '<li><font color="red"><strong>'.Text::_('COM_JDOWNLOADS_INSTALL_29').'</strong></font></li>';
                }
            }            
            
            // Check if the default directory for the 'temporary files' (tempzipfiles) exist
            $dir_existzip = Folder::exists($jd_upload_root.'/_tempzipfiles');

            if ($dir_existzip) {
                if (is_writable($jd_upload_root.'/_tempzipfiles')) {
                    // Exist and is writable!
                    self::addLog(Text::_('COM_JDOWNLOADS_INSTALL_11'), 'Log::INFO');
                    echo '<li><font color="green">'.Text::_('COM_JDOWNLOADS_INSTALL_11').'</font></li>';
                } else {
                    // Exist but is NOT writable!
                    self::addLog(Text::_('COM_JDOWNLOADS_INSTALL_12'), 'Log::INFO');
                    echo '<li><font color="red"><strong>'.Text::_('COM_JDOWNLOADS_INSTALL_12').'</strong></font></li>';
                }
            } else {
                if ($makedir = Folder::create($jd_upload_root.'/_tempzipfiles/', 0755)) {
                    // Succesful created
                    self::addLog(Text::_('COM_JDOWNLOADS_INSTALL_13'), 'Log::INFO');
                    echo '<li><font color="green">'.Text::_('COM_JDOWNLOADS_INSTALL_13').'</font></li>';
                } else {
                    // Could not create the folder!
                    self::addLog(Text::_('COM_JDOWNLOADS_INSTALL_14'), 'Log::INFO');
                    echo '<li><font color="red"><strong>'.Text::_('COM_JDOWNLOADS_INSTALL_14').'</strong></font></li>';
                }
            }
       
        echo '</ul>';
        
        // Create the dashboard module
        // $this->addDashboardMenu('jDownloads', 'jdownloads');
        
        // Display finaly the results from the extension installation
        
        $rows = 0;
        ?>                           
        
        </div>
        <hr>

        <table class="adminlist" style="width: 100%; margin:10px 10px 10px 10px;">
            <thead>
                <tr>
                    <th class="title" style="text-align:left;"><?php echo Text::_('COM_JDOWNLOADS_INSTALL_EXTENSION'); ?></th>
                    <th style="width: 50%; text-align:center;"><?php echo Text::_('COM_JDOWNLOADS_INSTALL_STATUS'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php if (count($status->modules)) : ?>
                <tr>
                    <th style="text-align:left;"><?php echo Text::_('COM_JDOWNLOADS_INSTALL_MODULE'); ?></th>
                </tr>
                <?php foreach ($status->modules as $module) : ?>
                <tr class="row<?php echo (++ $rows % 2); ?>">
                    <td class="key"><?php echo $module['name']; ?></td>
                    <td style="text-align:center;"><?php echo ($module['result'])?Text::_('COM_JDOWNLOADS_INSTALL_INSTALLED'):Text::_('COM_JDOWNLOADS_INSTALL_NOT_INSTALLED'); ?></td>
                </tr>
                <?php endforeach;?>
                <?php endif;?>
                <?php if (count($status->plugins)) : ?>
                <tr>
                    <th style="text-align:left;"><?php echo Text::_('COM_JDOWNLOADS_INSTALL_PLUGIN'); ?></th>
                </tr>
                <?php foreach ($status->plugins as $plugin) : ?>
                <tr class="row<?php echo (++ $rows % 2); ?>">
                    <td class="key"><?php echo ucfirst($plugin['name']); ?></td>
                    <td style="text-align:center;"><?php echo ($plugin['result'])?Text::_('COM_JDOWNLOADS_INSTALL_INSTALLED'):Text::_('COM_JDOWNLOADS_INSTALL_NOT_INSTALLED'); ?></td>
                </tr>
                <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
        <?php
        }
	}
 
	/**
	 * Method to uninstall the component
	 *
	 * @return void
	 */
	public function uninstall($parent) 
	{
        $db       = Factory::getDBO();
        $app      = Factory::getApplication();
        $session  = Factory::getSession();
        
        $params = ComponentHelper::getParams('com_jdownloads');
        
        $uninstall_options_results = array();
        
        $del_images = $session->get('del_jd_images', -1);
        $del_files  = $session->get('del_jd_files', -1);
        $del_tables = $session->get('del_jd_tables', -1);
        
        if ($del_images == -1 && $del_files == -1 && $del_tables == -1){
            // Move the user to the uninstall options page 
            $app->redirect(Route::_('index.php?option=com_jdownloads&view=uninstall', false));
            exit;
        }        
        
        $status = new CMSObject();
        $status->modules = array();
        $status->plugins = array();
        $src = $src = dirname(__FILE__);

        // Top Module
        $db->setQuery('SELECT `extension_id` FROM #__extensions WHERE `element` = "mod_jdownloads_top" AND `type` = "module"');
        $id = $db->loadResult();
        if ($id)
        {
            $installer = new Installer;
            $result = $installer->uninstall('module',$id,1);
            $status->modules[] = array('name'=>'jDownloads Top Module','client'=>'site', 'result'=>$result);
        }

        // Uninstall the Latest Module
        $db->setQuery('SELECT `extension_id` FROM #__extensions WHERE `element` = "mod_jdownloads_latest" AND `type` = "module"');
        $id = $db->loadResult();
        if ($id)
        {
            $installer = new Installer;
            $result = $installer->uninstall('module',$id,1);
            $status->modules[] = array('name'=>'jDownloads Latest Module','client'=>'site', 'result'=>$result);
        }

        // Uninstall the Last Upadated Downloads Module
        $db->setQuery('SELECT `extension_id` FROM #__extensions WHERE `element` = "mod_jdownloads_last_updated" AND `type` = "module"');
        $id = $db->loadResult();
        if ($id)
        {
            $installer = new Installer;
            $result = $installer->uninstall('module',$id,1);
            $status->modules[] = array('name'=>'jDownloads Last Updated Module','client'=>'site', 'result'=>$result);
        }        

        // Uninstall the Most Recently Downloaded Module
        $db->setQuery('SELECT `extension_id` FROM #__extensions WHERE `element` = "mod_jdownloads_most_recently_downloaded" AND `type` = "module"');
        $id = $db->loadResult();
        if ($id)
        {
            $installer = new Installer;
            $result = $installer->uninstall('module',$id,1);
            $status->modules[] = array('name'=>'jDownloads Most Recently Downloaded Module','client'=>'site', 'result'=>$result);
        }  
        
        // Uninstall the Frontend Stats Module
        $db->setQuery('SELECT `extension_id` FROM #__extensions WHERE `element` = "mod_jdownloads_stats" AND `type` = "module"');
        $id = $db->loadResult();
        if ($id)
        {
            $installer = new Installer;
            $result = $installer->uninstall('module',$id,1);
            $status->modules[] = array('name'=>'jDownloads Stats Module','client'=>'site', 'result'=>$result);
        }        
        
        // Uninstall the Tree Module
        $db->setQuery('SELECT `extension_id` FROM #__extensions WHERE `element` = "mod_jdownloads_tree" AND `type` = "module"');
        $id = $db->loadResult();
        if ($id)
        {
            $installer = new Installer;
            $result = $installer->uninstall('module',$id,1);
            $status->modules[] = array('name'=>'jDownloads Tree Module','client'=>'site', 'result'=>$result);
        }        

        // Uninstall the Related Module
        $db->setQuery('SELECT `extension_id` FROM #__extensions WHERE `element` = "mod_jdownloads_related" AND `type` = "module"');
        $id = $db->loadResult();
        if ($id)
        {
            $installer = new Installer;
            $result = $installer->uninstall('module',$id,1);
            $status->modules[] = array('name'=>'jDownloads Related Module','client'=>'site', 'result'=>$result);
        } 

        // Uninstall the Rated Module
        $db->setQuery('SELECT `extension_id` FROM #__extensions WHERE `element` = "mod_jdownloads_rated" AND `type` = "module"');
        $id = $db->loadResult();
        if ($id)
        {
            $installer = new Installer;
            $result = $installer->uninstall('module',$id,1);
            $status->modules[] = array('name'=>'jDownloads Rated Module','client'=>'site', 'result'=>$result);
        }

        // Uninstall the View Limits Module
        $db->setQuery('SELECT `extension_id` FROM #__extensions WHERE `element` = "mod_jdownloads_view_limits" AND `type` = "module"');
        $id = $db->loadResult();
        if($id)
        {
            $installer = new Installer;
            $result = $installer->uninstall('module',$id,1);
            $status->modules[] = array('name'=>'jDownloads View Limits Module','client'=>'site', 'result'=>$result);
        }         
        
        // Uninstall the Featured Module
        $db->setQuery('SELECT `extension_id` FROM #__extensions WHERE `element` = "mod_jdownloads_featured" AND `type` = "module"');
        $id = $db->loadResult();
        if ($id)
        {
            $installer = new Installer;
            $result = $installer->uninstall('module',$id,1);
            $status->modules[] = array('name'=>'jDownloads Featured Module','client'=>'site', 'result'=>$result);
        }
        
        // Uninstall the Admin Stats Module
        $db->setQuery('SELECT `extension_id` FROM #__extensions WHERE `element` = "mod_jdownloads_admin_stats" AND `type` = "module"');
        $id = $db->loadResult();
        if ($id)
        {
            $installer = new Installer;
            $result = $installer->uninstall('module',$id,1);
            $status->modules[] = array('name'=>'jDownloads Admin Stats Module','client'=>'admin', 'result'=>$result);
        }

        // Uninstall the Admin Monitoring Module
        $db->setQuery('SELECT `extension_id` FROM #__extensions WHERE `element` = "mod_jdownloads_admin_monitoring" AND `type` = "module"');
        $id = $db->loadResult();
        if ($id)
        {
            $installer = new Installer;
            $result = $installer->uninstall('module',$id,1);
            $status->modules[] = array('name'=>'jDownloads Admin Monitoring Module','client'=>'admin', 'result'=>$result);
        }
        
        // Uninstall the System Plugin
        $db->setQuery('SELECT `extension_id` FROM #__extensions WHERE `type` = "plugin" AND `name` = "plg_system_jdownloads" AND `folder` = "system"');
        $id = $db->loadResult();
        if ($id)
        {
            $installer = new Installer;
            $result = $installer->uninstall('plugin',$id,1);
            $status->plugins[] = array('name'=>'jDownloads System Plugin','group'=>'system', 'result'=>$result);
        }

        // Uninstall the old Search Plugin - not more supported in Joomla 4
        $db->setQuery('SELECT `extension_id` FROM #__extensions WHERE `type` = "plugin" AND `name` = "plg_search_jdownloads" AND `folder` = "search"');
        $id = $db->loadResult();
        if ($id)
        {
            $installer = new Installer;
            $result = $installer->uninstall('plugin',$id,1);
            $status->plugins[] = array('name'=>'jDownloads Search Plugin','group'=>'search', 'result'=>$result);
        }        
        
        // Uninstall the Example Plugin
        $db->setQuery('SELECT `extension_id` FROM #__extensions WHERE `type` = "plugin" AND `element` = "example" AND `folder` = "jdownloads"');
        $id = $db->loadResult();
        if ($id)
        {
            $installer = new Installer;
            $result = $installer->uninstall('plugin',$id,1);
            $status->plugins[] = array('name'=>'jDownloads Example Plugin','group'=>'jdownloads', 'result'=>$result);
        }
        
        // Uninstall the Button Plugin Download Link
        $db->setQuery('SELECT `extension_id` FROM #__extensions WHERE `type` = "plugin" AND `element` = "downloadlink" AND `folder` = "editors-xtd"');
        $id = $db->loadResult();
        if ($id)
        {
            $installer = new Installer;
            $result = $installer->uninstall('plugin',$id,1);
            $status->plugins[] = array('name'=>'jDownloads Download Link Button Plugin','group'=>'editors-xtd', 'result'=>$result);
        }        		

        // Uninstall the Button Plugin Download Content
        $db->setQuery('SELECT `extension_id` FROM #__extensions WHERE `type` = "plugin" AND `element` = "jdownloads" AND `folder` = "editors-xtd"');
        $id = $db->loadResult();
        if ($id)
        {
            $installer = new Installer;
            $result = $installer->uninstall('plugin',$id,1);
            $status->plugins[] = array('name'=>'jDownloads Download Content Button Plugin','group'=>'editors-xtd', 'result'=>$result);
        } 
		
        // Uninstall the Content Plugin
        $db->setQuery('SELECT `extension_id` FROM #__extensions WHERE `type` = "plugin" AND `name` = "plg_content_jdownloads" AND `folder` = "content"');
        $id = $db->loadResult();
        if ($id)
        {
            $installer = new Installer;
            $result = $installer->uninstall('plugin',$id,1);
            $status->plugins[] = array('name'=>'jDownloads Content Plugin','group'=>'content', 'result'=>$result);
        }

        // Uninstall the Finder Plugin for Downloads
        $db->setQuery('SELECT `extension_id` FROM #__extensions WHERE `type` = "plugin" AND `name` = "plg_finder_jdownloads" AND `folder` = "finder"');
        $id = $db->loadResult();
        if ($id)
        {
            $installer = new Installer;
            $result = $installer->uninstall('plugin',$id,1);
            $status->plugins[] = array('name'=>'jDownloads Finder Plugin','group'=>'finder', 'result'=>$result);
        }

        // Uninstall the Finder Plugin for Categories
        $db->setQuery('SELECT `extension_id` FROM #__extensions WHERE `type` = "plugin" AND `name` = "plg_finder_folder" AND `folder` = "finder"');
        $id = $db->loadResult();
        if ($id)
        {
            $installer = new Installer;
            $result = $installer->uninstall('plugin',$id,1);
            $status->plugins[] = array('name'=>'jDownloads Finder Category Plugin','group'=>'finder', 'result'=>$result);
        }
        
        // Actionlog Plugin
        /* $db->setQuery('SELECT `extension_id` FROM #__extensions WHERE `type` = "plugin" AND `name` = "plg_actionlog_jdownloads" AND `folder` = "actionlog"');
        $id = $db->loadResult();
        if($id)
        {
            $installer = new Installer;
            $result = $installer->uninstall('plugin',$id,1);
            $status->plugins[] = array('name'=>'jDownloads Actionlog Plugin','group'=>'actionlog', 'result'=>$result);
        }
        */
		
		// Uninstall the Content Plugin 'Tags Fix'
        $db->setQuery('SELECT `extension_id` FROM #__extensions WHERE `type` = "plugin" AND `name` = "plg_content_jdownloads_tags_fix" AND `folder` = "content"');
        $id = $db->loadResult();
        if ($id)
        {
            $installer = new Installer;
            $result = $installer->uninstall('plugin',$id,1);
            $status->plugins[] = array('name'=>'jDownloads Content Tags Fix Plugin','group'=>'content', 'result'=>$result);
        }

        // Check Uninstall type from Session 
        
        // Use special font colors for some situations/options in results messages 
         
        if ($del_images == '0'){
            
            // We shall remove jDownloads completely
            
            // Delete the image folders
            $path = JPATH_ROOT.'/images/jdownloads';
            if (Folder::exists($path)){
                if (Folder::delete($path)){
                    // Add message for succesful action
                    $uninstall_options_results[] = '<p align="center"><b><span style="color:#00CC00">'.Text::_('COM_JDOWNLOADS_UNINSTALL_IMAGES_DELETED').'</b></p>';
                } else {
                    // Add message for not succesful action
                    $uninstall_options_results[] = '<p align="center"><b><span style="color:#FF0000">'.Text::_('COM_JDOWNLOADS_UNINSTALL_IMAGES_NOT_DELETED').'</b></p>';
                }
            } else {
                // Folder not found
                $uninstall_options_results[] = '<p align="center"><b><span style="color:#FF0000">Image folder not found!</b></p>';
            }
        } else {
            $uninstall_options_results[] = '<p align="center"><b><span style="color:#FF8040">'.Text::_('COM_JDOWNLOADS_UNINSTALL_IMAGES_NOT_SELECTED').'</b></p>';
        }
            
        if ($del_files == '0'){            
            
            // Delete upload the upload folder with all files (Downloads)
            $path = $params->get('files_uploaddir');

            if ($path && Folder::exists($path)){
                if (Folder::delete($path)){
                    // Add message for succesful action
                    $uninstall_options_results[] = '<p align="center"><b><span style="color:#00CC00">'.Text::_('COM_JDOWNLOADS_UNINSTALL_FILES_DELETED').'</b></p>';
                } else {
                    // Add message for not succesful action
                    $uninstall_options_results[] = '<p align="center"><b><span style="color:#FF0000">'.Text::_('COM_JDOWNLOADS_UNINSTALL_FILES_NOT_DELETED').'</b></p>';
                }
            } else {
                // Folder not found
                $uninstall_options_results[] = '<p align="center"><b><span style="color:#FF0000">Upload folder not found!</b></p>';
            }
        } else {
            $uninstall_options_results[] = '<p align="center"><b><span style="color:#FF8040">'.Text::_('COM_JDOWNLOADS_UNINSTALL_FILES_NOT_SELECTED').'</b></p>';
        }
        
        if ($del_tables == '0'){            
            
            // Delete all database tables 
            $db->setQuery('DROP TABLE IF EXISTS #__jdownloads_categories, #__jdownloads_files, #__jdownloads_licenses, #__jdownloads_logs, #__jdownloads_ratings, #__jdownloads_templates, #__jdownloads_usergroups_limits');
            $result = $db->execute();
            if ($result === true){
                // Add message for succesful action
                $uninstall_options_results[] = '<p align="center"><b><span style="color:#00CC00">'.Text::_('COM_JDOWNLOADS_UNINSTALL_TABLES_DELETED').'</b></p>';
            } else {
                // Add message for not succesful action
                $uninstall_options_results[] = '<p align="center"><b><span style="color:#FF0000">'.Text::_('COM_JDOWNLOADS_UNINSTALL_TABLES_NOT_DELETED').'</b></p>';
            }
            
            // We now also remove all permission data in the asset table, if necessary - but is a complete restore still possible with
            // the help of a jDownloads data backup? Should be tested and possibly corrected if necessary.
            $db->setQuery('DELETE FROM #__assets WHERE name LIKE '.$db->quote('%com_jdownloads%'));
            $result = $db->execute();
            
        } else {
            $uninstall_options_results[] = '<p align="center"><b><span style="color:#FF8040">'.Text::_('COM_JDOWNLOADS_UNINSTALL_TABLES_NOT_SELECTED').'</b></p>';
        }
        
        $session->set('del_jd_images', -1);
        $session->set('del_jd_files',  -1);
        $session->set('del_jd_tables', -1);
        
        $msg = '<h4>'.Text::_('COM_JDOWNLOADS_DEINSTALL_0').'</h4><hr>';
        foreach ($uninstall_options_results as $result){
            $msg .= $result; 
        }
        $msg .= '<hr>';
                
        $msg .= '       
        <table class="adminlist" width="100%">
            <thead>
                <tr>
                    <th class="title" style="text-align:left;">'.Text::_('COM_JDOWNLOADS_INSTALL_EXTENSION').'</th>
                    <th style="width:50%; text-align:center;">'.Text::_('COM_JDOWNLOADS_INSTALL_STATUS').'</th>
                </tr>
            </thead>
            <tbody>
                <tr class="row0">
                    <td class="key">'.Text::_('COM_JDOWNLOADS_INSTALL_COMPONENT').' '.Text::_('COM_JDOWNLOADS_INSTALL_JDOWNLOADS').'</td>
                    <td style="text-align:center;">'.Text::_('COM_JDOWNLOADS_DEINSTALL_REMOVED').'</td>
                </tr>';

        if (count($status->modules)){
            $msg .=
            '<tr>
                <th style="text-align:left;">'.Text::_('COM_JDOWNLOADS_INSTALL_MODULE').'</th>
            </tr>';
        
            foreach ($status->modules as $module) {
                $msg .= 
                '<tr class="">
                <td class="key">'.$module['name'].'</td>
                <td style="text-align:center">';
                if ($module['result']){
                    $msg .= Text::_('COM_JDOWNLOADS_DEINSTALL_REMOVED').'</td></tr>';
                }else {
                    $msg .= Text::_('COM_JDOWNLOADS_DEINSTALL_NOT_REMOVED').'</td></tr>';
                }
            }
        }

        if (count($status->plugins)){
            $msg .=
            '<tr>
                <th style="text-align:left;">'.Text::_('COM_JDOWNLOADS_INSTALL_PLUGIN').'</th>
            </tr>';
            foreach ($status->plugins as $plugin){
                $msg .=
                '<tr class="">
                    <td class="key">'.ucfirst($plugin['name']).'</td>
                    <td style="text-align:center;">';
                if ($plugin['result']){
                    $msg .= Text::_('COM_JDOWNLOADS_DEINSTALL_REMOVED').'</td></tr>';
                }else {
                    $msg .= Text::_('COM_JDOWNLOADS_DEINSTALL_NOT_REMOVED').'</td></tr>';
                }
            }
        }
        $msg .=
            '</tbody>
        </table>
        <hr>';
        echo $msg;
        $session->set('jd_uninstall_msg', $msg);
	}
 
	/**
	 * Method to update the component
     * 
     * We can update only from a 4.0 series
	 * ====================================
     * 
	 * @return void
	 */
	public function update($parent) 
	{
        // Try to set time limit
        @set_time_limit(0);

        // Try to increase memory limit
        if ((int) ini_get( 'memory_limit' ) < 32){
            @ini_set( 'memory_limit', '32M' );
        }
        
        $user = Factory::getApplication()->getIdentity();
        $db   = Factory::getDBO();
        $params = ComponentHelper::getParams('com_jdownloads');
        
        // Currently we do not support the option to hide empty categories. This must first be overworked in the future.
        // So it must always be activated
        $params->set('view_empty_categories', 1);

        $prefix = self::getCorrectDBPrefix();
        $tablelist = $db->getTableList();

        // Add a log entry
        self::addLog(Text::sprintf('COM_JDOWNLOADS_UPDATE_LOG_START', $user->id, $user->name, $this->old_version_short, $this->new_version), 'Log::INFO');
        
        $rows = 0;
        $amount_mod = 0;
        $amount_plg = 0;

        $status = new CMSObject();
        $status->modules = array();
        $status->plugins = array();
        
        $src_modules = dirname(__FILE__).'/modules';
        $src_plugins = dirname(__FILE__).'/plugins';
        
        // We must install again all modules and plugins since it can be that we must also install here an update
        
        // Update Modules
        
        $installer = new Installer;
        $result = $installer->install($src_modules.'/mod_jdownloads_latest');
        $status->modules[] = array('name'=>'jDownloads Latest Module','client'=>'site', 'result'=>$result);
        if ($result) $amount_mod ++;

        $installer = new Installer;
        $result = $installer->install($src_modules.'/mod_jdownloads_top');
        $status->modules[] = array('name'=>'jDownloads Top Module','client'=>'site', 'result'=>$result);
        if ($result) $amount_mod ++;

        $installer = new Installer;
        $result = $installer->install($src_modules.'/mod_jdownloads_last_updated');
        $status->modules[] = array('name'=>'jDownloads Last Updated Module','client'=>'site', 'result'=>$result);
        if ($result) $amount_mod ++;

        $installer = new Installer;
        $result = $installer->install($src_modules.'/mod_jdownloads_most_recently_downloaded');
        $status->modules[] = array('name'=>'jDownloads Most Recently Downloaded Module','client'=>'site', 'result'=>$result);
        if ($result) $amount_mod ++;
        
        $installer = new Installer;                                                                                     
        $result = $installer->install($src_modules.'/mod_jdownloads_stats');
        $status->modules[] = array('name'=>'jDownloads Stats Module','client'=>'site', 'result'=>$result);        
        if ($result) $amount_mod ++;

        $installer = new Installer;                                                                                     
        $result = $installer->install($src_modules.'/mod_jdownloads_tree');
        $status->modules[] = array('name'=>'jDownloads Tree Module','client'=>'site', 'result'=>$result);         
        if ($result) $amount_mod ++;

        $installer = new Installer;                                                                                     
        $result = $installer->install($src_modules.'/mod_jdownloads_related');
        $status->modules[] = array('name'=>'jDownloads Related Module','client'=>'site', 'result'=>$result);        
        if ($result) $amount_mod ++;

        $installer = new Installer;                                                                                     
        $result = $installer->install($src_modules.'/mod_jdownloads_rated');
        $status->modules[] = array('name'=>'jDownloads Rated Module','client'=>'site', 'result'=>$result);
        if ($result) $amount_mod ++;
        
        $installer = new Installer;
        $result = $installer->install($src_modules.'/mod_jdownloads_featured');
        $status->modules[] = array('name'=>'jDownloads Featured Module','client'=>'site', 'result'=>$result);
        if ($result) $amount_mod ++;

        $installer = new Installer;
        $result = $installer->install($src_modules.'/mod_jdownloads_view_limits');
        $status->modules[] = array('name'=>'jDownloads View Limits Module','client'=>'site', 'result'=>$result);
        if ($result) $amount_mod ++;
        
        $installer = new Installer;
        $result = $installer->install($src_modules.'/mod_jdownloads_admin_stats');
        $status->modules[] = array('name'=>'jDownloads Admin Stats Module','client'=>'admin', 'result'=>$result);
        if ($result) $amount_mod ++;

        $installer = new Installer;
        $result = $installer->install($src_modules.'/mod_jdownloads_admin_monitoring');
        $status->modules[] = array('name'=>'jDownloads Admin Monitoring Module','client'=>'admin', 'result'=>$result);
        if ($result) $amount_mod ++;
        
        self::addLog(Text::sprintf('COM_JDOWNLOADS_UPDATE_LOG_MODS_INSTALLED_UPDATED', $amount_mod), 'Log::INFO');
        
        // Update Plugins
        
        $installer = new Installer;
        $result = $installer->install($src_plugins.'/plg_system_jdownloads');
        $status->plugins[] = array('name'=>'jDownloads System Plugin','group'=>'system', 'result'=>$result);
        if ($result) $amount_plg ++;
                
        $installer = new Installer;
        $result = $installer->install($src_plugins.'/editor_button_plugin_jdownloads_downloads');
        $status->plugins[] = array('name'=>'jDownloads Download Content Button Plugin','group'=>'editors-xtd', 'result'=>$result);               
        if ($result) $amount_plg ++;

        $installer = new Installer;
        $result = $installer->install($src_plugins.'/plg_content_jdownloads');
        $status->plugins[] = array('name'=>'jDownloads Content Plugin','group'=>'content', 'result'=>$result);               
        if ($result) $amount_plg ++;

        /* $installer = new Installer;
        $result = $installer->install($src_plugins.'/plg_jdownloads_example');
        $status->plugins[] = array('name'=>'jDownloads Example Plugin','group'=>'jdownloads', 'result'=>$result);
        if ($result) $amount_plg ++;
        */
        
        $installer = new Installer;
        $result = $installer->install($src_plugins.'/plg_finder_jdownloads');
        $status->plugins[] = array('name'=>'jDownloads Finder Plugin','group'=>'finder', 'result'=>$result);
        if ($result) $amount_plg ++;

        $installer = new Installer;
        $result = $installer->install($src_plugins.'/plg_finder_folder');
        $status->plugins[] = array('name'=>'jDownloads Finder Category Plugin','group'=>'finder', 'result'=>$result);
        if ($result) $amount_plg ++;
        
        $installer = new Installer;
        $result = $installer->install($src_plugins.'/plg_content_jdownloads_tags_fix');
        $status->plugins[] = array('name'=>'jDownloads Content Tags Fix Plugin','group'=>'content', 'result'=>$result);
        if ($result) $amount_plg ++;
        
		// Tags fix plugin must be enabled 
        $db->setQuery("UPDATE #__extensions SET enabled = '1' WHERE `name` = 'plg_content_jdownloads_tags_fix' AND `type` = 'plugin'");
        $db->execute();		
        
        // Delete old tags fix plugin from 3.2 when exist - 
        // Removed in 4.0 as we deinstall here the new installed 4.0 plugin instead to delete correct the old one. The element identifier is here wrong.
        /* $db->setQuery('SELECT `extension_id` FROM #__extensions WHERE `type` = "plugin" AND `element` = "jdownloads_tags_fix" AND `folder` = "content"');
        $id = $db->loadResult();
        if ($id) {
            $installer = new Installer;
            $result = $installer->uninstall('plugin',$id,1);
        } */

        self::addLog(Text::sprintf('COM_JDOWNLOADS_UPDATE_LOG_PLGS_INSTALLED_UPDATED', $amount_plg), 'Log::INFO');
        
        echo '<h4 style="color:#555;">' . Text::_('COM_JDOWNLOADS_UPDATE_TEXT') . '</h4>';
        
        if (count($status->modules) || count($status->plugins)){
            ?>    
            <hr>
            <table class="adminlist" style="width:100%; margin:10px 10px 10px 10px;">
                <thead>
                    <tr>
                        <th class="title" style="text-align:left;"><?php echo Text::_('COM_JDOWNLOADS_INSTALL_EXTENSION'); ?></th>
                        <th style="width:50%; text-align:center;"><?php echo Text::_('COM_JDOWNLOADS_INSTALL_STATUS'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($status->modules)) : ?>
                    <tr>
                        <th style="text-align:left;"><?php echo Text::_('COM_JDOWNLOADS_INSTALL_MODULE'); ?></th>
                    </tr>
                    <?php foreach ($status->modules as $module) : ?>
                    <tr class="row<?php echo (++ $rows % 2); ?>">
                        <td class="key"><?php echo $module['name']; ?></td>
                        <td style="text-align:center;"><?php echo ($module['result'])?Text::_('COM_JDOWNLOADS_INSTALL_INSTALLED'):Text::_('COM_JDOWNLOADS_INSTALL_NOT_INSTALLED'); ?></td>
                    </tr>
                    <?php endforeach;?>
                    <?php endif;?>
                    <?php if (count($status->plugins)) : ?>
                    <tr>
                        <th style="text-align:left;"><?php echo Text::_('COM_JDOWNLOADS_INSTALL_PLUGIN'); ?></th>
                    </tr>
                    <?php foreach ($status->plugins as $plugin) : ?>
                    <tr class="row<?php echo (++ $rows % 2); ?>">
                        <td class="key"><?php echo ucfirst($plugin['name']); ?></td>
                        <td style="text-align:center;"><?php echo ($plugin['result'])?Text::_('COM_JDOWNLOADS_INSTALL_INSTALLED'):Text::_('COM_JDOWNLOADS_INSTALL_NOT_INSTALLED'); ?></td>
                    </tr>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>            
            <?php            
        }
        
        // Must we update any other thing?
        if ($this->old_version_found){
       
            // When it should exist we must uninstall the old Search Plugin - not more supported in Joomla 4
            $db->setQuery('SELECT `extension_id` FROM #__extensions WHERE `type` = "plugin" AND `name` = "plg_search_jdownloads" AND `folder` = "search"');
            $id = $db->loadResult();
            if ($id)
            {
                $installer = new Installer;
                $result = $installer->uninstall('plugin',$id,1);
            }
            
            // Update the titles from the admin modules when upgrade from 3.x
            if ($this->run_upgrade_from_39){
                $db->setQuery("UPDATE #__modules SET `published` = '1', `title` = '".Text::_('COM_JDOWNLOADS_STATS_MODULE_TITLE')."', `position` = 'jdcpanel', `ordering` = '1', `showtitle` = '1' WHERE `module` = 'mod_jdownloads_admin_stats'");
                $db->execute();
                $db->setQuery("UPDATE #__modules SET `published` = '1', `title` = '".Text::_('COM_JDOWNLOADS_MONITORING_MODULE_TITLE')."', `position` = 'jdcpanel', `ordering` = '2', `showtitle` = '1' WHERE `module` = 'mod_jdownloads_admin_monitoring'");
                $db->execute();
            } 
        }
    }
 
	/**
	 * Method to run before an install/update/
     * 
	 * @return void
	 */
	public function preflight($type, $parent) 
	{
        $parent   = $parent->getParent();
        $source   = $parent->getPath("source");
        $manifest = $parent->get("manifest");
        $db       = Factory::getDBO();
        $session  = Factory::getSession();        

        $this->old_version_found = false;
        
        $pos = strpos($manifest->version, ' ');
        if ($pos){
            $this->new_version_short     = substr($manifest->version, 0, $pos);
        } else {
            $this->new_version_short     = $manifest->version;
        }    
        $this->new_version              = (string)$manifest->version;
        $this->target_joomla_version    = (string)$manifest->targetjoomla;
        $this->minimum_databases        = (string)$manifest->minimum_databases;
        
        // Add a log entry - with basic system information at first
        self::addLog('', 'Log::INFO', true);                
        
        if ( $type == 'install'){     
            self::addLog('------------------------------------------------------ Installation Started', '');                
        }
        if ( $type == 'update'){     
            self::addLog('------------------------------------------------------ Update Started', '');                
        }
        
        $prefix = self::getCorrectDBPrefix();
        $tablelist = $db->getTableList();
        
        if ( $type == 'install' || $type == 'update' ) {
            // This component does only work with Joomla release 4.0 or higher - otherwise abort
            $jversion     = new Version();
            $jversion_value = $jversion->getShortVersion();
        
            if (!$jversion->isCompatible($this->target_joomla_version)) {
                // Is not the required joomla target version - Abort!
                Factory::getApplication()->enqueueMessage( Text::_('COM_JDOWNLOADS_INSTALL_WRONG_JOOMLA_RELEASE'), 'warning');
                return false;
            }
         
            if ( $type == 'update' ) {
                $component_header = Text::_('COM_JDOWNLOADS_DESCRIPTION');
                $typetext = Text::_('COM_JDOWNLOADS_INSTALL_TYPE_UPDATE');
                $db->setQuery('SELECT * FROM #__extensions WHERE `element` = "com_jdownloads" AND `type` = "component"');
                $item = $db->loadObject();
                $old_manifest = json_decode($item->manifest_cache); 
                $pos = strpos($old_manifest->version, ' ');
                if ($pos){
                    $this->old_version_short = substr($old_manifest->version, 0, $pos);    
                } else {
                    $this->old_version_short = $old_manifest->version;    
                } 
                
                // Build the versions text information for the finaly update message.
                $rel = $this->old_version_short . ' to ' . $this->new_version;

                if ( !version_compare($this->new_version_short, $this->old_version_short, '>=' ) ) {
                    self::addLog(Text::_('COM_JDOWNLOADS_UPDATE_ERROR_INCORRECT_VERSION').' '.$rel, 'Log::WARNING');                
                    // Abort if the release being installed is not newer (or equal) than the currently installed jDownloads version
                    Factory::getApplication()->enqueueMessage(Text::_('COM_JDOWNLOADS_UPDATE_ERROR_INCORRECT_VERSION').' '.$rel, 'warning');
                    return false;
                }
                
                // Does the currently installed jDownloads version meet the requirement to update to version 4?
                if (substr($this->old_version_short, 0, 7) >= '3.9.8.6' && substr($this->old_version_short, 0, 1) < '4'){
                    $this->old_version_found = true;
                    $this->run_upgrade_from_39 = true;
                    
                    self::buildTableDefinitions();
                    
                    // Check at first the jDownloads tables in database 
                    $database_ok = self::checkDatabaseTableStructures();
                    if (!$database_ok){
                        // Abort!                                                                                                                                                                                                                                                                                                
                        $msg_a = '<b>A check of the jDownloads database tables revealed differences from the expected data structure. Therefore, the update was cancelled.</b><br>Nevertheless, an attempt was made to correct the affected tables. Therefore, you can now try to perform the update again. However, if you receive this message again, check the listed table. Compare the structure with the structure in this file of your old jDownloads 3.9 version: <u>/administrator/components/com_jdownloads/sql/install.mysql.utf8.sql</u>. Use the documentation and/or the jDownloads support forum to solve the problem.';
                        $msg_b = '<b>The data fields listed below are not in the correct position within the table. Therefore, the update was cancelled.</b><br>Nevertheless, an attempt was made to correct the affected tables. Therefore, you can now try to perform the update again. However, if you receive this message again, check the listed table. However, if you receive this message again, check the listed table. To do this, compare the order of the data fields with the structure in this file of your old jDownloads 3 version: <u>/administrator/components/com_jdownloads/sql/install.mysql.utf8.sql</u>. Use the documentation and/or the jDownloads support forum to solve the problem.';
                        
                        if ($this->wrong_table){
                            $msg_a = $msg_a.'<br><br>'.implode('<br>', $this->wrong_table);
                            self::addLog($msg_a, 'Log::WARNING');
                            Factory::getApplication()->enqueueMessage($msg_a, 'error');
                        }
                        
                        if ($this->wrong_fields){
                            $msg_b = $msg_b.'<br><br>'.implode('<br>', $this->wrong_fields);
                            self::addLog($msg_b, 'Log::WARNING');
                            Factory::getApplication()->enqueueMessage($msg_b, 'error');
                        }    
                        
                        return false;
                    } 
                    
                    $success = self::runUpgrade();
                    if (!$success) return false;
                }

            } else {
                
                // Only if $type == 'install'
                // Build the text information for the finaly installation message (no update!).
                $component_header = Text::_('COM_JDOWNLOADS_DESCRIPTION');
                $typetext =  Text::_('COM_JDOWNLOADS_INSTALL_TYPE_INSTALL');
                $rel = $this->new_version; 
            }

            $install_msg = '<p><b>'.$component_header.'</b></p>
                            <p>'.$typetext.' '.Text::_('COM_JDOWNLOADS_INSTALL_VERSION').' '.$rel.'</p>';

            $this->install_msg = $install_msg."\n";
            
            ?>
            <table class="adminlist" style="width:100%;">
                <thead>
                    <tr>
                        <th class="title" style="text-align:center;"><img src="<?php echo URI::base(); ?>components/com_jdownloads/assets/images/jdownloads.jpg" style="border:0;" alt="jDownloads Logo" /><br />
                        <?php echo $this->install_msg; ?>
                        </th>
                    </tr>
                </thead>
           </table>
        <form>
            <div style="text-align:center; margin:25px,0px,25px,0px;"><input class="btn btn-primary" style="align:center;" type="button" value="<?php echo Text::_('COM_JDOWNLOADS_INSTALL_16').'&nbsp; '; ?>" onclick="window.location.href='index.php?option=com_jdownloads'" /></div>
        </form>
        
        <?php  // end install/update
        
        } else {
            
            if ($type == 'uninstall'){
                       
            }
           
        }
        
        // ***********************************************************************************************************
        // Only here the component files are copied from the Joomla installation function to the target directories... 
        // ***********************************************************************************************************
	}
 
	/**
	 * Method to run after an install/update/discover_install method
	 *
	 * @return void
	 */
	public function postflight($type, $parent) 
	{
		// $parent is the class calling this method
		// $type is the type of change (install, update or discover_install)
        
        $app    = Factory::getApplication(); 
        $db     = Factory::getDBO();        
        
        if ( $type == 'install' ){
            
            require_once (JPATH_ADMINISTRATOR.'/components/com_jdownloads/src/Helper/JDownloadsHelper.php');
            require_once (JPATH_ADMINISTRATOR.'/components/com_jdownloads/src/Helper/StandardLayouts.php');
            
            /* Write default permission settings in the assets table when not exist already
            /  
            / Temporarily removed as we did not want to use automatic download permission for all levels as default in the future. 
            / This would make it easier to create a category-level authorisation structure without having to make major adjustments beforehand.
            / But activated again in first beta to be full compatible with old 3.9 series.
            */
            $query = $db->getQuery(true);
            $query->select('rules');
            $query->from('#__assets');
            $query->where('name = '.$db->Quote('com_jdownloads'));
            $db->setQuery($query);
            $jd_component_rule = $db->loadResult();              
              
            if ($jd_component_rule = '' || $jd_component_rule == '{}'){              
                $query = $db->getQuery(true);
                $query->update($db->quoteName('#__assets'));
                $query->set('rules = '.$db->Quote('{"download":{"1":1}}'));
                $query->where('name = '.$db->Quote('com_jdownloads'));
                $db->setQuery($query);
                if (!$db->execute()){
                    $this->setError($db->getErrorMsg());
                }    
            }
            
            // Replace titles from the jD backend admin modules with the right content from language file
            $query = $db->getQuery(true);
            $query->select('title');
            $query->from('#__assets');
            $query->where('title = '.$db->Quote('jDownloads Stats for Administrators'));
            $db->setQuery($query);
            $result = $db->LoadResult();
            if ($result){
                $new_name = Text::_('COM_JDOWNLOADS_STATS_MODULE_TITLE');
                $old_name = Text::_('jDownloads Stats for Administrators');
                $query = $db->getQuery(true);
                $query->update($db->quoteName('#__assets'));
                $query->set('title = '.$db->Quote($new_name));
                $query->where('title = '.$db->Quote($old_name));
                $db->setQuery($query);
                if (!$db->execute()){
                    $this->setError($db->getErrorMsg());
                }
                $new_name = Text::_('COM_JDOWNLOADS_MONITORING_MODULE_TITLE');
                $old_name = Text::_('jDownloads Administrator Monitoring');
                $query = $db->getQuery(true);
                $query->update($db->quoteName('#__assets'));
                $query->set('title = '.$db->Quote($new_name));
                $query->where('title = '.$db->Quote($old_name));
                $db->setQuery($query);
                if (!$db->execute()){
                    $this->setError($db->getErrorMsg());
                }
            }
            
            // Get the normal backend language ini file
            $language = Factory::getLanguage();
            $language->load('com_jdownloads', JPATH_ADMINISTRATOR);
            
            // Get the currently stored jDownloads params
            $query = $db->getQuery(true);
            $db->setQuery('SELECT `params` FROM #__extensions WHERE `type` = "component" AND `element` = "com_jdownloads"');
            $old_params = $db->loadResult();
            
            if ($old_params == '{}' || !$old_params){
            
                // Create the default param values
                $json = file_get_contents(JPATH_ADMINISTRATOR.'/components/com_jdownloads/default_params.txt');
                $def_params = json_decode($json);
                

                // Add the root path
                $files_uploaddir = $db->escape(JPATH_ROOT.'/jdownloads'); 
                $files_uploaddir = rtrim($files_uploaddir, "/");
                $files_uploaddir = rtrim($files_uploaddir, "\\");
                $files_uploaddir = str_replace('\\', '/', $files_uploaddir);
                
                $def_params->files_uploaddir = $files_uploaddir;
                $def_params->root_dir        = $files_uploaddir;
                
                // Some fields must have values from the current active jD language files
                $def_params->global_datetime                = $db->escape(Text::_('COM_JDOWNLOADS_INSTALL_DEFAULT_DATE_FORMAT'));
                $def_params->global_datetime_short          = $db->escape(Text::_('COM_JDOWNLOADS_INSTALL_DEFAULT_DATE_FORMAT'));
                $def_params->offline_text                   = $db->escape(Text::_('COM_JDOWNLOADS_BACKEND_OFFLINE_MESSAGE_DEFAULT'));
                $def_params->system_list                    = $db->escape(Text::_('COM_JDOWNLOADS_BACKEND_FILESEDIT_SYSTEM_DEFAULT_LIST'));
                $def_params->language_list                  = $db->escape(Text::_('COM_JDOWNLOADS_BACKEND_FILESEDIT_LANGUAGE_DEFAULT_LIST'));
                $def_params->send_mailto_betreff            = $db->escape(Text::_('COM_JDOWNLOADS_SETTINGS_INSTALL_3'));
                $def_params->send_mailto_template_download  = $db->escape(Text::_('COM_JDOWNLOADS_BACKEND_SETTINGS_TEMPLATES_MAIL_DEFAULT'));
                $def_params->send_mailto_betreff_upload     = $db->escape(Text::_('COM_JDOWNLOADS_SETTINGS_INSTALL_6'));
                $def_params->send_mailto_template_upload    = $db->escape(Text::_('COM_JDOWNLOADS_BACKEND_SETTINGS_GLOBAL_MAIL_UPLOAD_TEMPLATE'));
                $def_params->report_mail_subject            = $db->escape(Text::_('COM_JDOWNLOADS_CONFIG_REPORT_FILE_MAIL_SUBJECT_DEFAULT'));
                $def_params->report_mail_layout             = $db->escape(Text::_('COM_JDOWNLOADS_CONFIG_REPORT_FILE_MAIL_LAYOUT_DEFAULT'));
                $def_params->customers_mail_subject         = $db->escape(Text::_('COM_JDOWNLOADS_CONFIG_CUSTOMERS_MAIL_SUBJECT_DEFAULT'));
                $def_params->customers_mail_layout          = $db->escape(Text::_('COM_JDOWNLOADS_CONFIG_CUSTOMERS_MAIL_LAYOUT_DEFAULT'));
                $def_params->user_message_when_zero_points  = $db->escape(Text::_('COM_JDOWNLOADS_BACKEND_SET_AUP_FE_MESSAGE_NO_DOWNLOAD'));
                $def_params->fileplugin_defaultlayout       = $db->escape(Text::_('COM_JDOWNLOADS_BACKEND_SETTINGS_TEMPLATES_FILES_DEFAULT_NAME'));
                $def_params->fileplugin_offline_title       = $db->escape(Text::_('COM_JDOWNLOADS_FRONTEND_SETTINGS_FILEPLUGIN_OFFLINE_FILETITLE'));
                $def_params->fileplugin_offline_descr       = $db->escape(Text::_('COM_JDOWNLOADS_FRONTEND_SETTINGS_FILEPLUGIN_DESCRIPTION'));
                $def_params->checkbox_top_text              = $db->escape(Text::_('COM_JDOWNLOADS_SETTINGS_INSTALL_1'));
                
                //$def_params->sortorder_fields               = '["0","1","2","3","4"]';
            
                $json = json_encode($def_params, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
	            $query = $db->getQuery(true);
	            $db->setQuery("UPDATE #__extensions SET params = '".$json."' WHERE `type` = 'component' AND `element` = 'com_jdownloads'");
                if ($db->execute()){
                    self::addLog(Text::_('COM_JDOWNLOADS_INSTALL_HINT_1'), 'Log::INFO');                
                }
        	}
        }
        
        if ( $type == 'update' ){
            // Update the length of logs_ip field to 50
            $query = $db->getQuery(true);
            $db->setQuery("ALTER TABLE #__jdownloads_logs CHANGE log_ip log_ip varchar(50) NOT NULL DEFAULT ''");
            try {
                $db->execute();
            } catch(RuntimeException $e) {
                self::addLog(Text::_($e->getMessage()), 'Log::ERROR');
                Factory::getApplication()->enqueueMessage($e->getMessage(), 'error');
            }
            
            // If the jDownloads component or other parts have been deactivated (due to earlier incompatibility),
            // they must now be reactivated to avoid an error message the first time they are called.
            $query = $db->getQuery(true);
            $db->setQuery("UPDATE #__extensions SET enabled = '1' WHERE `name` = 'com_jDownloads'");
            $db->execute();
            $db->setQuery("UPDATE #__extensions SET enabled = '1' WHERE `name` = 'plg_system_jdownloads'");
            $db->execute();
            $db->setQuery("UPDATE #__extensions SET enabled = '1' WHERE `name` = 'plg_content_jdownloads_tags_fix'");
            $db->execute();
            $db->setQuery("UPDATE #__extensions SET enabled = '1' WHERE `element` = 'mod_jdownloads_admin_stats'");
            $db->execute();
            $db->setQuery("UPDATE #__extensions SET enabled = '1' WHERE `element` = 'mod_jdownloads_admin_monitoring'");
            $db->execute();
            
            // If an upgrade from 3.9 was performed at an earlier time, the 'publish_down' field in #__jdownloads_files might still contain wrong values (0000-00-00 00:00:00).
            // Here we try to check this and correct it to 'NULL'.
            $query = $db->getQuery(true);
            $db->setQuery("SELECT COUNT(*) FROM #__jdownloads_files WHERE `publish_down` = '0000-00-00 00:00:00'");
            $result = $db->loadResult();
            if ($result > 0){
                $db->setQuery("SET SESSION sql_mode = 'NO_ENGINE_SUBSTITUTION'");
                $db->execute();
                $db->setQuery("UPDATE #__jdownloads_files SET `publish_down` = null WHERE `publish_down` = '0000-00-00 00:00:00'");
                if ($db->execute()){
                    self::addLog(Text::_('Wrong values successfully corrected in jdownloads_files `publish_down` field.'), 'Log::INFO');                
                } else {
                    self::addLog(Text::_('Attention! Wrong values could not be corrected in jdownloads_files `publish_down` field.'), 'Log::ALERT');                
                }
            }              
        }    
        
        // $type is install, update or discover_install
        
        // Write for the Tags feature the jDownloads data in the #__content_types table
        $query = $db->getQuery(true);
        $query->select('*');
        $query->from('#__content_types');
        $query->where('type_alias = '.$db->Quote('com_jdownloads.download'));
        $db->setQuery($query);
        $type_download = $db->loadResult();              
        
        if (!$type_download){              
            $query = $db->getQuery(true);
            $query->insert($db->quoteName('#__content_types'))
                    ->columns(array($db->quoteName('type_title'), $db->quoteName('type_alias'), $db->quoteName('table'), $db->quoteName('rules'), $db->quoteName('field_mappings'), $db->quoteName('router')))
                    ->values($db->quote('jDownloads Download'). ', ' .$db->quote('com_jdownloads.download'). ',' .$db->quote('{"special":{"dbtable":"#__jdownloads_files","key":"id","type":"DownloadTable","prefix":"JDownloads\\Component\\JDownloads\\Administrator\\Table\\","config":"array()"},"common":{"dbtable":"#__ucm_content","key":"ucm_id","type":"Download","prefix":"Joomla\\CMS\\Table\\","config":"array()"}}', false).', '.$db->quote('').', '.$db->quote('{"common":{"core_content_item_id":"id","core_title":"title","core_state":"published","core_alias":"alias","core_created_time":"created","core_modified_time":"modified","core_body":"description", "core_hits":"views","core_publish_up":"publish_up","core_publish_down":"publish_down","core_access":"access", "core_params":"params", "core_featured":"featured", "core_metadata":"null", "core_language":"language", "core_images":"images", "core_urls":"null", "core_version":"null", "core_ordering":"ordering", "core_metakey":"metakey", "core_metadesc":"metadesc", "core_catid":"catid", "core_xreference":"null", "asset_id":"asset_id"}, "special":{"description_long":"description_long"}}', false).', ' .$db->quote('RouteHelper::getDownloadRoute'));
            $db->setQuery($query);                                                                                                                                                                                                                                                                                                                                                    
            if (!$db->execute()){
                $this->setError($db->getErrorMsg());
            }    
            
            $query = $db->getQuery(true);
            $query->insert($db->quoteName('#__content_types'))
                    ->columns(array($db->quoteName('type_title'), $db->quoteName('type_alias'), $db->quoteName('table'), $db->quoteName('rules'), $db->quoteName('field_mappings'), $db->quoteName('router')))
                    ->values($db->quote('jDownloads Category'). ', ' .$db->quote('com_jdownloads.category'). ',' .$db->quote('{"special":{"dbtable":"#__jdownloads_categories","key":"id","type":"JDCategoryTable","prefix":"JDownloads\\Component\\JDownloads\\Administrator\\Table\\","config":"array()"},"common":{"dbtable":"#__ucm_content","key":"ucm_id","type":"Category","prefix":"Joomla\\CMS\\Table\\","config":"array()"}}', false).', '.$db->quote('').', '.$db->quote('{"common":{"core_content_item_id":"id","core_title":"title","core_state":"published","core_alias":"alias","core_created_time":"created_time","core_modified_time":"modified_time","core_body":"description", "core_hits":"views","core_publish_up":"null","core_publish_down":"null","core_access":"access", "core_params":"params", "core_featured":"null", "core_metadata":"null", "core_language":"language", "core_images":"null", "core_urls":"null", "core_version":"null", "core_ordering":"ordering", "core_metakey":"metakey", "core_metadesc":"metadesc", "core_catid":"parent_id", "core_xreference":"null", "asset_id":"asset_id"}, "special":{"parent_id":"parent_id","lft":"lft","rgt":"rgt","level":"level","path":"null","extension":"null","note":"null"}}', false).', ' .$db->quote('RouteHelper::getCategoryRoute'));
            $db->setQuery($query);                                                                                                                                                                                                                                                                                                                                                               
            if (!$db->execute()){
                $this->setError($db->getErrorMsg());
            }    
        } 
        
        // Write for the Joomla user 'action log' feature the required jDownloads data in the #__action_log_config
        $query = $db->getQuery(true);
        $query->select('*');
        $query->from('#__action_log_config');
        $query->where('type_alias = '.$db->Quote('com_jdownloads.download'));
        $db->setQuery($query);
        $type_download = $db->loadResult();              
        
        if (!$type_download){              
            $query = $db->getQuery(true);
            $query->insert($db->quoteName('#__action_log_config'))
                    ->columns(array($db->quoteName('type_title'), $db->quoteName('type_alias'), $db->quoteName('id_holder'), $db->quoteName('title_holder'), $db->quoteName('table_name'), $db->quoteName('text_prefix')))
                    ->values($db->quote('download'). ', ' .$db->quote('com_jdownloads.download'). ', ' .$db->quote('id'). ',' .$db->quote('title'). ', ' .$db->quote('#__jdownloads_files'). ', ' .$db->quote('COM_JDOWNLOADS_ACTIONLOG'));
            $db->setQuery($query);                                                                                                                                                                                                                                                                                                                                                    
            if (!$db->execute()){
                $this->setError($db->getErrorMsg());
            } 
            $query = $db->getQuery(true);
            $query->insert($db->quoteName('#__action_log_config'))
                    ->columns(array($db->quoteName('type_title'), $db->quoteName('type_alias'), $db->quoteName('id_holder'), $db->quoteName('title_holder'), $db->quoteName('table_name'), $db->quoteName('text_prefix')))
                    ->values($db->quote('category'). ', ' .$db->quote('com_jdownloads.category'). ', ' .$db->quote('id'). ', ' .$db->quote('title'). ', ' .$db->quote('#__jdownloads_categories'). ', ' .$db->quote('COM_JDOWNLOADS_ACTIONLOG'));
            $db->setQuery($query);                                                                                                                                                                                                                                                                                                                                                    
            if (!$db->execute()){
                $this->setError($db->getErrorMsg());
            } 
            $query = $db->getQuery(true);
            $query->insert($db->quoteName('#__action_log_config'))
                    ->columns(array($db->quoteName('type_title'), $db->quoteName('type_alias'), $db->quoteName('id_holder'), $db->quoteName('title_holder'), $db->quoteName('table_name'), $db->quoteName('text_prefix')))
                    ->values($db->quote('license'). ', ' .$db->quote('com_jdownloads.license'). ', ' .$db->quote('id'). ', ' .$db->quote('title'). ', ' .$db->quote('#__jdownloads_licenses'). ', ' .$db->quote('COM_JDOWNLOADS_ACTIONLOG'));
            $db->setQuery($query);                                                                                                                                                                                                                                                                                                                                                    
            if (!$db->execute()){
                $this->setError($db->getErrorMsg());
            } 
            $query = $db->getQuery(true);
            $query->insert($db->quoteName('#__action_log_config'))
                    ->columns(array($db->quoteName('type_title'), $db->quoteName('type_alias'), $db->quoteName('id_holder'), $db->quoteName('title_holder'), $db->quoteName('table_name'), $db->quoteName('text_prefix')))
                    ->values($db->quote('layout'). ', ' .$db->quote('com_jdownloads.template'). ', ' .$db->quote('id'). ', ' .$db->quote('template_name'). ', ' .$db->quote('#__jdownloads_templates'). ', ' .$db->quote('COM_JDOWNLOADS_ACTIONLOG'));
            $db->setQuery($query);                                                                                                                                                                                                                                                                                                                                                    
            if (!$db->execute()){
                $this->setError($db->getErrorMsg());
            } 
            $query = $db->getQuery(true);
            $query->insert($db->quoteName('#__action_log_config'))
                    ->columns(array($db->quoteName('type_title'), $db->quoteName('type_alias'), $db->quoteName('id_holder'), $db->quoteName('title_holder'), $db->quoteName('table_name'), $db->quoteName('text_prefix')))
                    ->values($db->quote('group'). ', ' .$db->quote('com_jdownloads.group'). ', ' .$db->quote('id'). ', ' .$db->quote('group_id'). ', ' .$db->quote('#__jdownloads_usergroups_limits'). ', ' .$db->quote('COM_JDOWNLOADS_ACTIONLOG'));
            $db->setQuery($query);                                                                                                                                                                                                                                                                                                                                                    
            if (!$db->execute()){
                $this->setError($db->getErrorMsg());
            } 
            
            // We need also a new dataset in the #__action_logs_extensions table
            $query = $db->getQuery(true);
            $query->select('*');
            $query->from('#__action_logs_extensions');
            $query->where('extension = '.$db->Quote('com_jdownloads'));
            $db->setQuery($query);
            $result = $db->loadResult();              
            
            if (!$result){              
                $query = $db->getQuery(true);
                $query->insert($db->quoteName('#__action_logs_extensions'))
                        ->columns(array($db->quoteName('extension')))
                        ->values($db->quote('com_jdownloads'));
                $db->setQuery($query);                                                                                                                                                                                                                                                                                                                                                    
                if (!$db->execute()){
                    $this->setError($db->getErrorMsg());
                }
            }
        }                   

        
        if ( $type == 'install'){     
            self::addLog('------------------------------------------------------ Installation Finished', 'Log::INFO');                
        }
        if ( $type == 'update'){     
            self::addLog('------------------------------------------------------ Update Finished', 'Log::INFO');                
        }
                            
        ?>
        <form>
            <div style="text-align:center; margin:25px,0px,25px,0px;"><input class="btn btn-primary" style="align:center;" type="button" value="<?php echo Text::_('COM_JDOWNLOADS_INSTALL_16').'&nbsp; '; ?>" onclick="window.location.href='index.php?option=com_jdownloads'" /></div>
        </form>
        <?php
	}

    /**
     * Method to get the correct db prefix (problem with getTablelist() which always/sometimes has lowercase prefix names in array)
     *
     * @return string
     */
    private function getCorrectDBPrefix() 
    {
        $db = Factory::getDBO();

        // Get DB prefix string and table list
        $prefix     = $db->getPrefix();
        $prefix_low = strtolower($prefix);
        $tablelist  = $db->getTableList();

        if (!in_array ( $prefix.'assets', $tablelist)) {
            if (in_array ( $prefix_low.'assets', $tablelist)) {
                return $prefix_low;
            } else {
                // Assets table not found? 
                return '';
            } 
        } else {
            return $prefix;
        }        
    }
    
    
    /**
     * Method to get only a part from the string in a selected language
     *
     * @return string
     */
    private function getOnlyLanguageSubstring($msg, $lang_key = '')
    {
        // Get the current locale language tag
        if (!$lang_key){
            $lang       = Factory::getLanguage();
            $lang_key   = $lang->getTag();        
        }
        
        // Remove the language tag from the text
        $startpos = StringHelper::strpos($msg, '{'.$lang_key.'}') +  strlen( $lang_key) + 2 ;
        $endpos   = StringHelper::strpos($msg, '{/'.$lang_key.'}') ;
        
        if ($startpos !== false && $endpos !== false){
            return StringHelper::substr($msg, $startpos, ($endpos - $startpos ));
        } else {    
            return $msg;
        }    
    }
    
    /**
     * Method to add a log item 
     *
     * @return
     */
    private function addLog($msg, $priority, $basic_informations = false)
    {
        if ($basic_informations){
            // Add a few important system information when param is set

            $db = Factory::getDbo();
            $messages_array = array();

            $logoptions['text_entry_format'] = '{DATE} {TIME}    {PRIORITY}     {MESSAGE}';
            $logoptions['text_file'] = 'com_jdownloads_install_logs.php';
            
            Log::addLogger($logoptions, Log::ALL, 'jD');

            $version = new Version();
            
            $messages_array[] = '------------------------------------------------------ System Information';                
            $messages_array[] = 'OS                 : '.substr(php_uname(), 0, 7);
            $messages_array[] = 'PHP                : '.phpversion();
            $messages_array[] = 'MySQL              : '.$db->getVersion();
            $messages_array[] = 'Joomla!            : '.$version->getShortVersion();
            
            $messages_array[] = 'Debug              : '.Factory::getApplication()->get('debug');
            $messages_array[] = 'Debug Language     : '.Factory::getApplication()->get('debug_lang');
            
            $messages_array[] = 'Error Reporting    : '.Factory::getApplication()->get('error_reporting');
            
            $messages_array[] = 'SEF                : '.Factory::getApplication()->get('sef');
            $messages_array[] = 'Unicode Aliases    : '.Factory::getApplication()->get('unicodeslugs');
            $messages_array[] = 'System Cache       : '.Factory::getApplication()->get('caching');
            $messages_array[] = 'Captcha            : '.Factory::getApplication()->get('captcha');
            
            foreach ($messages_array as $message){
                        
                try
                {
                    Log::add(Text::_($message), LOG::INFO, 'jD');
                }            
                catch (RuntimeException $exception)
                {
                    // Informational log only
                }
            }
            return;
        }
        
        // Add here the normal log item
        
        $logoptions['text_entry_format'] = '{DATE} {TIME}    {PRIORITY}     {MESSAGE}';
        $logoptions['text_file'] = 'com_jdownloads_install_logs.php';
        
        Log::addLogger($logoptions, Log::ALL, 'jD');

        try
        {
            Log::add(Text::_($msg), $priority, 'jD');
        }            
        catch (RuntimeException $exception)
        {
            // Informational log only
        }
        return;
    }
    
    /**
     * Method to update jDownloads to 4.x 
     *
     * @return  boolean
     */
    private function runUpgrade()
    {
        // ************************
        // Try to start the upgrade 
        // ************************
        
        // Add log message
        self::addLog(Text::_('COM_JDOWNLOADS_UPGRADE39_HINT_1'), 'Log::INFO');
        
        // Hint:
        // Since the existing settings and data could be lost during a normal uninstall,
        // we try to use this function to delete all old jDownloads directories and files, 
        // which are then replaced with the new file versions. 
           
        // Step 1
        // Delete all not more required BACKEND folders and files expect the assets folder 

        self::addLog(Text::_('COM_JDOWNLOADS_UPGRADE39_HINT_2'), 'Log::INFO');
        
        $status = [
            'files_exist'     => [],
            'folders_exist'   => [],
            'files_deleted'   => [],
            'folders_deleted' => [],
            'files_errors'    => [],
            'folders_errors'  => [],
            'folders_checked' => [],
            'files_checked'   => []
        ];
        
        $folders = array(
            '/administrator/components/com_jdownloads/helpers/html',
            '/administrator/components/com_jdownloads/helpers',
            '/administrator/components/com_jdownloads/models/fields/modal',
            '/administrator/components/com_jdownloads/models/fields',
            '/administrator/components/com_jdownloads/models/forms',
            //'/administrator/components/com_jdownloads/models',
            //'/administrator/components/com_jdownloads/controllers',
            //'/administrator/components/com_jdownloads/tables',
            '/administrator/components/com_jdownloads/views/association/tmpl',
            '/administrator/components/com_jdownloads/views/association',
            '/administrator/components/com_jdownloads/views/associations/tmpl',
            '/administrator/components/com_jdownloads/views/associations',
            '/administrator/components/com_jdownloads/views/backup/tmpl',
            '/administrator/components/com_jdownloads/views/backup',
            '/administrator/components/com_jdownloads/views/categories/tmpl',
            '/administrator/components/com_jdownloads/views/categories',
            '/administrator/components/com_jdownloads/views/category/tmpl',
            '/administrator/components/com_jdownloads/views/category',
            '/administrator/components/com_jdownloads/views/cssedit/tmpl',
            '/administrator/components/com_jdownloads/views/cssedit',
            '/administrator/components/com_jdownloads/views/cssexport/tmpl',
            '/administrator/components/com_jdownloads/views/cssexport',
            '/administrator/components/com_jdownloads/views/cssimport/tmpl',
            '/administrator/components/com_jdownloads/views/cssimport',
            '/administrator/components/com_jdownloads/views/download/tmpl',
            '/administrator/components/com_jdownloads/views/download',
            '/administrator/components/com_jdownloads/views/downloads/tmpl',
            '/administrator/components/com_jdownloads/views/downloads',
            '/administrator/components/com_jdownloads/views/files/tmpl',
            '/administrator/components/com_jdownloads/views/files',
            '/administrator/components/com_jdownloads/views/group/tmpl',
            '/administrator/components/com_jdownloads/views/group',
            '/administrator/components/com_jdownloads/views/groups/tmpl',
            '/administrator/components/com_jdownloads/views/groups',
            '/administrator/components/com_jdownloads/views/info/tmpl',
            '/administrator/components/com_jdownloads/views/info',
            '/administrator/components/com_jdownloads/views/jdownloads/tmpl',
            '/administrator/components/com_jdownloads/views/jdownloads',
            '/administrator/components/com_jdownloads/views/layoutinstall/tmpl',
            '/administrator/components/com_jdownloads/views/layoutinstall',
            '/administrator/components/com_jdownloads/views/layouts/tmpl',
            '/administrator/components/com_jdownloads/views/layouts',
            '/administrator/components/com_jdownloads/views/license/tmpl',
            '/administrator/components/com_jdownloads/views/license',
            '/administrator/components/com_jdownloads/views/licenses/tmpl',
            '/administrator/components/com_jdownloads/views/licenses',
            '/administrator/components/com_jdownloads/views/list/tmpl',
            '/administrator/components/com_jdownloads/views/list',
            '/administrator/components/com_jdownloads/views/logs/tmpl',
            '/administrator/components/com_jdownloads/views/logs',
            '/administrator/components/com_jdownloads/views/optionsdefault/tmpl',
            '/administrator/components/com_jdownloads/views/optionsdefault',
            '/administrator/components/com_jdownloads/views/optionsexport/tmpl',
            '/administrator/components/com_jdownloads/views/optionsexport',
            '/administrator/components/com_jdownloads/views/optionsimport/tmpl',
            '/administrator/components/com_jdownloads/views/optionsimport',
            '/administrator/components/com_jdownloads/views/restore/tmpl',
            '/administrator/components/com_jdownloads/views/restore',
            '/administrator/components/com_jdownloads/views/template/tmpl',
            '/administrator/components/com_jdownloads/views/template',
            '/administrator/components/com_jdownloads/views/templates/tmpl',
            '/administrator/components/com_jdownloads/views/templates',
            '/administrator/components/com_jdownloads/views/tools/tmpl',
            '/administrator/components/com_jdownloads/views/tools',
            '/administrator/components/com_jdownloads/views/uninstall/tmpl',
            '/administrator/components/com_jdownloads/views/uninstall',
            '/administrator/components/com_jdownloads/views/uploads/tmpl',
            '/administrator/components/com_jdownloads/views/uploads'
        );
        
        $files = array(
            '/administrator/components/com_jdownloads/access.xml',
            '/administrator/components/com_jdownloads/controller.php',
            '/administrator/components/com_jdownloads/default_params.txt',
            '/administrator/components/com_jdownloads/htaccess.txt',
            '/administrator/components/com_jdownloads/jdownloads.php',
            '/administrator/components/com_jdownloads/jdownloads.xml',
            '/administrator/components/com_jdownloads/script.php'
        );
        
        $status['files_checked'] = $files;
        $status['folders_checked'] = $folders;

        foreach ($files as $file)
        {
            if ($fileExists = File::exists(JPATH_ROOT . $file))
            {
                $status['files_exist'][] = $file;

                if (File::delete(JPATH_ROOT . $file))
                {
                    $status['files_deleted'][] = $file;
                } else {
                    $status['files_errors'][] = Text::sprintf('FILES_JOOMLA_ERROR_FILE_FOLDER', $file);
                }
            }
        }

        foreach ($folders as $folder)
        {
            if ($folderExists = Folder::exists(JPATH_ROOT . $folder))
            {
                $status['folders_exist'][] = $folder;

                if (Folder::delete(JPATH_ROOT . $folder))
                {
                    $status['folders_deleted'][] = $folder;
                } else {
                    $status['folders_errors'][] = Text::sprintf('FILES_JOOMLA_ERROR_FILE_FOLDER', $folder);
                }
            }
        }

        if (count($status['folders_errors']))
        {
            $foldersresult = implode('<br>', $status['folders_errors']);
            self::addLog(Text::_('COM_JDOWNLOADS_UPGRADE39_HINT_4'.'<br>'.$foldersresult), 'Log::INFO');
        }

        if (count($status['files_errors']))
        {
            $filesresult = implode('<br>', $status['files_errors']);
            self::addLog(Text::_('COM_JDOWNLOADS_UPGRADE39_HINT_3'.'<br>'.$filesresult), 'Log::INFO');
        }

        // Step 2
        // Delete all not more required FRONTEND folders and files expect the assets folder 

        $status = [
            'files_exist'     => [],
            'folders_exist'   => [],
            //'files_deleted'   => [],
            //'folders_deleted' => [],
            'files_errors'    => [],
            'folders_errors'  => [],
            'folders_checked' => [],
            'files_checked'   => []
        ];
        
        $folders = array(
            '/components/com_jdownloads/helpers',
            '/components/com_jdownloads/models/forms',
            //'/components/com_jdownloads/models',
            //'/components/com_jdownloads/controllers',
            '/components/com_jdownloads/views/categories/tmpl',
            '/components/com_jdownloads/views/categories',
            '/components/com_jdownloads/views/category/tmpl',
            '/components/com_jdownloads/views/category',
            '/components/com_jdownloads/views/download/tmpl',
            '/components/com_jdownloads/views/download',
            '/components/com_jdownloads/views/downloads/tmpl',
            '/components/com_jdownloads/views/downloads',
            '/components/com_jdownloads/views/form/tmpl',
            '/components/com_jdownloads/views/form',
            '/components/com_jdownloads/views/mydownloads/tmpl',
            '/components/com_jdownloads/views/mydownloads',
            '/components/com_jdownloads/views/myhistory/tmpl',
            '/components/com_jdownloads/views/myhistory',
            '/components/com_jdownloads/views/report/tmpl',
            '/components/com_jdownloads/views/report',
            '/components/com_jdownloads/views/search/tmpl',
            '/components/com_jdownloads/views/search',
            '/components/com_jdownloads/views/summary/tmpl',
            '/components/com_jdownloads/views/summary',
            '/components/com_jdownloads/views/survey/tmpl',
            '/components/com_jdownloads/views/survey'            
            //'/components/com_jdownloads/views'
        );
        
        $files = array(
            '/components/com_jdownloads/controller.php',
            '/components/com_jdownloads/jdownloads.php',
            '/components/com_jdownloads/metadata.xml',
            '/components/com_jdownloads/router.php'
        );
        
        $status['files_checked'] = $files;
        $status['folders_checked'] = $folders;

        foreach ($files as $file)
        {
            if ($fileExists = File::exists(JPATH_ROOT . $file))
            {
                $status['files_exist'][] = $file;

                if (File::delete(JPATH_ROOT . $file))
                {
                    $status['files_deleted'][] = $file;
                } else {
                    $status['files_errors'][] = Text::sprintf('FILES_JOOMLA_ERROR_FILE_FOLDER', $file);
                }
            }
        }

        foreach ($folders as $folder)
        {
            if ($folderExists = Folder::exists(JPATH_ROOT . $folder))
            {
                $status['folders_exist'][] = $folder;

                if (Folder::delete(JPATH_ROOT . $folder))
                {
                    $status['folders_deleted'][] = $folder;
                } else {
                    $status['folders_errors'][] = Text::sprintf('FILES_JOOMLA_ERROR_FILE_FOLDER', $folder);
                }
            }
        }

        if (count($status['folders_errors']))
        {
            $foldersresult = implode('<br>', $status['folders_errors']);
            self::addLog(Text::_('COM_JDOWNLOADS_UPGRADE39_HINT_4'.'<br>'.$foldersresult), 'Log::INFO');
        }

        if (count($status['files_errors']))
        {
            $filesresult = implode('<br>', $status['files_errors']);
            self::addLog(Text::_('COM_JDOWNLOADS_UPGRADE39_HINT_3'.'<br>'.$filesresult), 'Log::INFO');
        }

        // Delete all jD Modules and Plugins
        
        $status = [
            'files_exist'     => [],
            'folders_exist'   => [],
            //'files_deleted'   => [],
            //'folders_deleted' => [],
            'files_errors'    => [],
            'folders_errors'  => [],
            'folders_checked' => [],
            'files_checked'   => []
        ];
        
        $folders = array(
            '/administrator/modules/mod_jdownloads_admin_monitoring/language/de-DE',
            '/administrator/modules/mod_jdownloads_admin_monitoring/language/en-GB',
            '/administrator/modules/mod_jdownloads_admin_monitoring/tmpl',
            '/administrator/modules/mod_jdownloads_admin_monitoring',
            '/administrator/modules/mod_jdownloads_admin_stats/language/de-DE',
            '/administrator/modules/mod_jdownloads_admin_stats/language/en-GB',
            '/administrator/modules/mod_jdownloads_admin_stats/tmpl',
            '/administrator/modules/mod_jdownloads_admin_stats',
            '/modules/mod_jdownloads_featured/language/de-DE',
            '/modules/mod_jdownloads_featured/language/en-GB',
            '/modules/mod_jdownloads_featured/language',
            '/modules/mod_jdownloads_featured/tmpl',
            '/modules/mod_jdownloads_featured/fields',
            '/modules/mod_jdownloads_featured',            
            '/modules/mod_jdownloads_last_updated/language/de-DE',
            '/modules/mod_jdownloads_last_updated/language/en-GB',
            '/modules/mod_jdownloads_last_updated/language',
            '/modules/mod_jdownloads_last_updated/tmpl',
            '/modules/mod_jdownloads_last_updated/fields',
            '/modules/mod_jdownloads_last_updated',
            '/modules/mod_jdownloads_latest/language/de-DE',
            '/modules/mod_jdownloads_latest/language/en-GB',
            '/modules/mod_jdownloads_latest/language',
            '/modules/mod_jdownloads_latest/tmpl',
            '/modules/mod_jdownloads_latest/fields',
            '/modules/mod_jdownloads_latest',
            '/modules/mod_jdownloads_most_recently_downloaded/language/de-DE',
            '/modules/mod_jdownloads_most_recently_downloaded/language/en-GB',
            '/modules/mod_jdownloads_most_recently_downloaded/language',
            '/modules/mod_jdownloads_most_recently_downloaded/tmpl',
            '/modules/mod_jdownloads_most_recently_downloaded/fields',
            '/modules/mod_jdownloads_most_recently_downloaded',
            '/modules/mod_jdownloads_rated/language/de-DE',
            '/modules/mod_jdownloads_rated/language/en-GB',
            '/modules/mod_jdownloads_rated/language',
            '/modules/mod_jdownloads_rated/tmpl',
            '/modules/mod_jdownloads_rated/fields',
            '/modules/mod_jdownloads_rated/mod_jdownloads_images',
            '/modules/mod_jdownloads_rated',
            '/modules/mod_jdownloads_related/language/de-DE',
            '/modules/mod_jdownloads_related/language/en-GB',
            '/modules/mod_jdownloads_related/language',
            '/modules/mod_jdownloads_related/tmpl',
            '/modules/mod_jdownloads_related/fields',
            '/modules/mod_jdownloads_related',
            '/modules/mod_jdownloads_stats/language/de-DE',
            '/modules/mod_jdownloads_stats/language/en-GB',
            '/modules/mod_jdownloads_stats/language',
            '/modules/mod_jdownloads_stats/tmpl',
            '/modules/mod_jdownloads_stats',
            '/modules/mod_jdownloads_top/language/de-DE',
            '/modules/mod_jdownloads_top/language/en-GB',
            '/modules/mod_jdownloads_top/language',
            '/modules/mod_jdownloads_top/tmpl',
            '/modules/mod_jdownloads_top/fields',
            '/modules/mod_jdownloads_top',
            '/modules/mod_jdownloads_tree/language/de-DE',
            '/modules/mod_jdownloads_tree/language/en-GB',
            '/modules/mod_jdownloads_tree/language',
            '/modules/mod_jdownloads_tree/tmpl',
            '/modules/mod_jdownloads_tree/fields',
            '/modules/mod_jdownloads_tree/jdtree/images',
            '/modules/mod_jdownloads_tree/jdtree',
            '/modules/mod_jdownloads_tree',            
            '/modules/mod_jdownloads_view_limits/language/de-DE',
            '/modules/mod_jdownloads_view_limits/language/en-GB',
            '/modules/mod_jdownloads_view_limits/language',
            '/modules/mod_jdownloads_view_limits/tmpl',
            '/modules/mod_jdownloads_view_limits',
            '/plugins/content/jdownloads/jdownloads/images',
            '/plugins/content/jdownloads/jdownloads',
            '/plugins/content/jdownloads',
            '/plugins/content/jdownloads_tags_fix/language/de-DE',
            '/plugins/content/jdownloads_tags_fix/language/en-GB',
            '/plugins/content/jdownloads_tags_fix/language',
            '/plugins/content/jdownloads_tags_fix',
            '/plugins/editors-xtd/jdownloads/assets/css',
            '/plugins/editors-xtd/jdownloads/assets/images',
            '/plugins/editors-xtd/jdownloads/assets',
            '/plugins/editors-xtd/jdownloads',
            '/plugins/finder/folder',
            '/plugins/finder/jdownloads',
            '/plugins/jdownloads/example/language/de-DE',
            '/plugins/jdownloads/example/language/en-GB',
            '/plugins/jdownloads/example/language',
            '/plugins/jdownloads/example',
            '/plugins/jdownloads',
            '/plugins/system/jdownloads/language/de-DE',
            '/plugins/system/jdownloads/language/en-GB',
            '/plugins/system/jdownloads/language',
            '/plugins/system/jdownloads'
        );
        
        $files = array();
        
        $status['files_checked'] = $files;
        $status['folders_checked'] = $folders;

        foreach ($files as $file)
        {
            if ($fileExists = File::exists(JPATH_ROOT . $file))
            {
                $status['files_exist'][] = $file;

                if (File::delete(JPATH_ROOT . $file))
                {
                    $status['files_deleted'][] = $file;
                } else {
                    $status['files_errors'][] = Text::sprintf('FILES_JOOMLA_ERROR_FILE_FOLDER', $file);
                }
            }
        }

        foreach ($folders as $folder)
        {
            if ($folderExists = Folder::exists(JPATH_ROOT . $folder))
            {
                $status['folders_exist'][] = $folder;

                if (Folder::delete(JPATH_ROOT . $folder))
                {
                    $status['folders_deleted'][] = $folder;
                } else {
                    $status['folders_errors'][] = Text::sprintf('FILES_JOOMLA_ERROR_FILE_FOLDER', $folder);
                }
            }
        }

        if (count($status['folders_errors']))
        {
            $foldersresult = implode('<br>', $status['folders_errors']);
            self::addLog(Text::_('COM_JDOWNLOADS_UPGRADE39_HINT_4'.'<br>'.$foldersresult), 'Log::INFO');
        }

        if (count($status['files_errors']))
        {
            $filesresult = implode('<br>', $status['files_errors']);
            self::addLog(Text::_('COM_JDOWNLOADS_UPGRADE39_HINT_3'.'<br>'.$filesresult), 'Log::INFO');
        }
        
        // *******************************************************************************************
        // Step 3
        // For a safe transfer of the data, we first create new tables with the new field definitions. 
        // Then we transfer the original data into the new tables and finally rename the original tables to be able to assign
        // the correct name to the new tables. The old tables remain as a backup for the time being and can be deleted later by the user.
        // *******************************************************************************************
        
        // Add log message
        self::addLog(Text::_('COM_JDOWNLOADS_UPGRADE39_HINT_6'), 'Log::INFO');
        
        $db = Factory::getDBO();
        
        // Build table creation variables
        self::buildTableDefinitions();
        
        // Categories table
        $db->setQuery($this->categories_table);
        try {
            $result = $db->execute();
        } catch(RuntimeException $e) {
            self::addLog(Text::_($e->getMessage()), 'Log::ERROR');
            Factory::getApplication()->enqueueMessage($e->getMessage(), 'error');
        }
        
        if ($result){
            $db->setQuery('INSERT INTO #__jdownloads_categories_40x SELECT * FROM #__jdownloads_categories');
            try {
                $result = $db->execute();
            } catch(RuntimeException $e) {
                self::addLog(Text::_($e->getMessage()), 'Log::ERROR');
                Factory::getApplication()->enqueueMessage($e->getMessage(), 'error');
            }
            
            if ($result){
                $db->setQuery('RENAME TABLE #__jdownloads_categories TO #__jdownloads_categories_39old');
                try {
                    $result = $db->execute();
                } catch(RuntimeException $e) {
                    self::addLog(Text::_($e->getMessage()), 'Log::ERROR');
                    Factory::getApplication()->enqueueMessage($e->getMessage(), 'error');
                }
                
                if ($result){
                    $db->setQuery('RENAME TABLE #__jdownloads_categories_40x TO #__jdownloads_categories');
                    try {
                        $result = $db->execute();
                    } catch(RuntimeException $e) {
                        self::addLog(Text::_($e->getMessage()), 'Log::ERROR');
                        Factory::getApplication()->enqueueMessage($e->getMessage(), 'error');
                    }
                                        
                    if ($result){
                        $db->setQuery('DROP TABLE IF EXISTS #__jdownloads_categories_39old');
                        try {
                            $result = $db->execute();
                        } catch(RuntimeException $e) {
                            self::addLog(Text::_($e->getMessage()), 'Log::ERROR');
                            Factory::getApplication()->enqueueMessage($e->getMessage(), 'error');
                        }
                    }
                }    
            }
        }
        
        // Files table
        $db->setQuery($this->files_table);
        try {
            $result = $db->execute();
        } catch(RuntimeException $e) {
            self::addLog(Text::_($e->getMessage()), 'Log::ERROR');
            Factory::getApplication()->enqueueMessage($e->getMessage(), 'error');
        }
        
        if ($result){
            $db->setQuery('INSERT INTO #__jdownloads_files_40x SELECT * FROM #__jdownloads_files');
            try {
                $result = $db->execute();
            } catch(RuntimeException $e) {
                self::addLog(Text::_($e->getMessage()), 'Log::ERROR');
                Factory::getApplication()->enqueueMessage($e->getMessage(), 'error');
            }
            
            if ($result){
                $db->setQuery('RENAME TABLE #__jdownloads_files TO #__jdownloads_files_39old');
                try {
                    $result = $db->execute();
                } catch(RuntimeException $e) {
                    self::addLog(Text::_($e->getMessage()), 'Log::ERROR');
                    Factory::getApplication()->enqueueMessage($e->getMessage(), 'error');
                }
                
                if ($result){
                    $db->setQuery('RENAME TABLE #__jdownloads_files_40x TO #__jdownloads_files');
                    try {
                        $result = $db->execute();
                    } catch(RuntimeException $e) {
                        self::addLog(Text::_($e->getMessage()), 'Log::ERROR');
                        Factory::getApplication()->enqueueMessage($e->getMessage(), 'error');
                    }
                                        
                    if ($result){
                        $db->setQuery('DROP TABLE IF EXISTS #__jdownloads_files_39old');
                        try {
                            $result = $db->execute();
                        } catch(RuntimeException $e) {
                            self::addLog(Text::_($e->getMessage()), 'Log::ERROR');
                            Factory::getApplication()->enqueueMessage($e->getMessage(), 'error');
                        }
                    }
                }    
            }
        }
        
        // Licenses table
        $db->setQuery($this->licenses_table);
        try {
            $result = $db->execute();
        } catch(RuntimeException $e) {
            self::addLog(Text::_($e->getMessage()), 'Log::ERROR');
            Factory::getApplication()->enqueueMessage($e->getMessage(), 'error');
        } 
    
        if ($result){
            $db->setQuery('INSERT INTO #__jdownloads_licenses_40x SELECT * FROM #__jdownloads_licenses');
            try {
                $result = $db->execute();
            } catch(RuntimeException $e) {
                self::addLog(Text::_($e->getMessage()), 'Log::ERROR');
                Factory::getApplication()->enqueueMessage($e->getMessage(), 'error');
            }
            
            if ($result){
                $db->setQuery('RENAME TABLE #__jdownloads_licenses TO #__jdownloads_licenses_39old');
                try {
                    $result = $db->execute();
                } catch(RuntimeException $e) {
                    self::addLog(Text::_($e->getMessage()), 'Log::ERROR');
                    Factory::getApplication()->enqueueMessage($e->getMessage(), 'error');
                }
                
                if ($result){
                    $db->setQuery('RENAME TABLE #__jdownloads_licenses_40x TO #__jdownloads_licenses');
                    try {
                        $result = $db->execute();
                    } catch(RuntimeException $e) {
                        self::addLog(Text::_($e->getMessage()), 'Log::ERROR');
                        Factory::getApplication()->enqueueMessage($e->getMessage(), 'error');
                    }
                                        
                    if ($result){
                        $db->setQuery('DROP TABLE IF EXISTS #__jdownloads_licenses_39old');
                        try {
                            $result = $db->execute();
                        } catch(RuntimeException $e) {
                            self::addLog(Text::_($e->getMessage()), 'Log::ERROR');
                            Factory::getApplication()->enqueueMessage($e->getMessage(), 'error');
                        }
                    }
                }    
            }
        }
        
        // Templates table
        $db->setQuery($this->templates_table);
        try {
            $result = $db->execute();
        } catch(RuntimeException $e) {
            self::addLog(Text::_($e->getMessage()), 'Log::ERROR');
            Factory::getApplication()->enqueueMessage($e->getMessage(), 'error');
        }

        if ($result){
            $db->setQuery('INSERT INTO #__jdownloads_templates_40x SELECT * FROM #__jdownloads_templates');
            try {
                $result = $db->execute();
            } catch(RuntimeException $e) {
                //self::addLog(Text::_($e->getMessage()), 'Log::ERROR');
                //Factory::getApplication()->enqueueMessage($e->getMessage(), 'error');
                
                // Table seems to have the another data field order, so we try this other order (preview_id not at the end position)
                $db->setQuery('DROP TABLE IF EXISTS #__jdownloads_templates_40x');
                try {
                    $result = $db->execute();
                } catch(RuntimeException $e) {
                    self::addLog(Text::_($e->getMessage()), 'Log::ERROR');
                    Factory::getApplication()->enqueueMessage($e->getMessage(), 'error');
                }
                
                if ($result){
                    $db->setQuery($this->templates_table_2);
                    try {
                        $result = $db->execute();
                    } catch(RuntimeException $e) {
                        self::addLog(Text::_($e->getMessage()), 'Log::ERROR');
                        Factory::getApplication()->enqueueMessage($e->getMessage(), 'error');
                    }
                }    
                
                if ($result){
                    $db->setQuery('INSERT INTO #__jdownloads_templates_40x SELECT * FROM #__jdownloads_templates');
                    try {
                        $result = $db->execute();
                    } catch(RuntimeException $e) {
                        self::addLog(Text::_($e->getMessage()), 'Log::ERROR');
                        Factory::getApplication()->enqueueMessage($e->getMessage(), 'error');
                    }
                }
            }
            
            if ($result){
                $db->setQuery('RENAME TABLE #__jdownloads_templates TO #__jdownloads_templates_39old');
                try {
                    $result = $db->execute();
                } catch(RuntimeException $e) {
                    self::addLog(Text::_($e->getMessage()), 'Log::ERROR');
                    Factory::getApplication()->enqueueMessage($e->getMessage(), 'error');
                }
                
                if ($result){
                    $db->setQuery('RENAME TABLE #__jdownloads_templates_40x TO #__jdownloads_templates');
                    try {
                        $result = $db->execute();
                    } catch(RuntimeException $e) {
                        self::addLog(Text::_($e->getMessage()), 'Log::ERROR');
                        Factory::getApplication()->enqueueMessage($e->getMessage(), 'error');
                    }
                                        
                    if ($result){
                        $db->setQuery('DROP TABLE IF EXISTS #__jdownloads_templates_39old');
                        try {
                            $result = $db->execute();
                        } catch(RuntimeException $e) {
                            self::addLog(Text::_($e->getMessage()), 'Log::ERROR');
                            Factory::getApplication()->enqueueMessage($e->getMessage(), 'error');
                        }
                        
                        // Try now to move the preview_id field to the correct position at the end.
                        $db->setQuery('ALTER TABLE `#__jdownloads_templates` CHANGE `preview_id` `preview_id` TINYINT(3) NOT NULL DEFAULT 0 AFTER `checked_out_time`');
                        try {
                            $result = $db->execute();
                        } catch(RuntimeException $e) {
                            self::addLog(Text::_($e->getMessage()), 'Log::ERROR');
                            Factory::getApplication()->enqueueMessage($e->getMessage(), 'error');
                        }
                        if ($result){
                            self::addLog('Templates field `preview_id` successful moved at the end.', 'Log::INFO');
                        }
                    }
                }    
            }
        }        
        
        // Logs table
        $db->setQuery($this->logs_table);
        try {
            $result = $db->execute();
        } catch(RuntimeException $e) {
            self::addLog(Text::_($e->getMessage()), 'Log::ERROR');
            Factory::getApplication()->enqueueMessage($e->getMessage(), 'error');
        }
        
        if ($result){
            $db->setQuery('INSERT INTO #__jdownloads_logs_40x SELECT * FROM #__jdownloads_logs');
            try {
                $result = $db->execute();
            } catch(RuntimeException $e) {
                self::addLog(Text::_($e->getMessage()), 'Log::ERROR');
                Factory::getApplication()->enqueueMessage($e->getMessage(), 'error');
            }
            
            if ($result){
                $db->setQuery('RENAME TABLE #__jdownloads_logs TO #__jdownloads_logs_39old');
                try {
                    $result = $db->execute();
                } catch(RuntimeException $e) {
                    self::addLog(Text::_($e->getMessage()), 'Log::ERROR');
                    Factory::getApplication()->enqueueMessage($e->getMessage(), 'error');
                }
                
                if ($result){
                    $db->setQuery('RENAME TABLE #__jdownloads_logs_40x TO #__jdownloads_logs');
                    try {
                        $result = $db->execute();
                    } catch(RuntimeException $e) {
                        self::addLog(Text::_($e->getMessage()), 'Log::ERROR');
                        Factory::getApplication()->enqueueMessage($e->getMessage(), 'error');
                    }
                                        
                    if ($result){
                        $db->setQuery('DROP TABLE IF EXISTS #__jdownloads_logs_39old');
                        try {
                            $result = $db->execute();
                        } catch(RuntimeException $e) {
                            self::addLog(Text::_($e->getMessage()), 'Log::ERROR');
                            Factory::getApplication()->enqueueMessage($e->getMessage(), 'error');
                        }
                    }
                }    
            }
        } 
        
        // User Groups Limits
        $db->setQuery($this->usergroups_limits_table);
        try {
            $result = $db->execute();
        } catch(RuntimeException $e) {
            self::addLog(Text::_($e->getMessage()), 'Log::ERROR');
            Factory::getApplication()->enqueueMessage($e->getMessage(), 'error');
        }
        
        if ($result){
            $db->setQuery('INSERT INTO #__jdownloads_usergroups_limits_40x SELECT * FROM #__jdownloads_usergroups_limits');
            try {
                $result = $db->execute();
            } catch(RuntimeException $e) {
                self::addLog(Text::_($e->getMessage()), 'Log::ERROR');
                Factory::getApplication()->enqueueMessage($e->getMessage(), 'error');
            }
            
            if ($result){
                $db->setQuery('RENAME TABLE #__jdownloads_usergroups_limits TO #__jdownloads_usergroups_limits_39old');
                try {
                    $result = $db->execute();
                } catch(RuntimeException $e) {
                    self::addLog(Text::_($e->getMessage()), 'Log::ERROR');
                    Factory::getApplication()->enqueueMessage($e->getMessage(), 'error');
                }
                
                if ($result){
                    $db->setQuery('RENAME TABLE #__jdownloads_usergroups_limits_40x TO #__jdownloads_usergroups_limits');
                    try {
                        $result = $db->execute();
                    } catch(RuntimeException $e) {
                        self::addLog(Text::_($e->getMessage()), 'Log::ERROR');
                        Factory::getApplication()->enqueueMessage($e->getMessage(), 'error');
                    }
                    
                    if ($result){
                        $db->setQuery('DROP TABLE IF EXISTS #__jdownloads_usergroups_limits_39old');
                        try {
                            $result = $db->execute();
                        } catch(RuntimeException $e) {
                            self::addLog(Text::_($e->getMessage()), 'Log::ERROR');
                            Factory::getApplication()->enqueueMessage($e->getMessage(), 'error');
                        }
                    }
                }    
            }
        }         
        // Convert fields with old datetime values '0000-00-00 00:00:00' to NULL
        self::addLog(Text::_('COM_JDOWNLOADS_UPGRADE39_HINT_7'), 'Log::INFO');
        
        $db->setQuery('UPDATE '. $db->quoteName('#__jdownloads_categories') . ' SET `checked_out_time` = NULL WHERE `checked_out_time` = '. $db->quote('0000-00-00 00:00:00'));
        try {
            $db->execute();
        } catch(RuntimeException $e) {
            self::addLog(Text::_($e->getMessage()), 'Log::ERROR');
            Factory::getApplication()->enqueueMessage($e->getMessage(), 'error');
        }
        
        $db->setQuery('UPDATE '. $db->quoteName('#__jdownloads_categories') . ' SET `modified_time` = `created_time` WHERE `modified_time` = '. $db->quote('0000-00-00 00:00:00'));
        try {
            $db->execute();
        } catch(RuntimeException $e) {
            self::addLog(Text::_($e->getMessage()), 'Log::ERROR');
            Factory::getApplication()->enqueueMessage($e->getMessage(), 'error');
        }
        
        $db->setQuery('UPDATE '. $db->quoteName('#__jdownloads_files') . ' SET `publish_down` = NULL WHERE `publish_down` = '. $db->quote('0000-00-00 00:00:00'));
        try {
            $db->execute();
        } catch(RuntimeException $e) {
            self::addLog(Text::_($e->getMessage()), 'Log::ERROR');
            Factory::getApplication()->enqueueMessage($e->getMessage(), 'error');
        }
        
        $db->setQuery('UPDATE '. $db->quoteName('#__jdownloads_files') . ' SET `publish_up` = NULL WHERE `publish_up` = '. $db->quote('0000-00-00 00:00:00'));
        try {
            $db->execute();
        } catch(RuntimeException $e) {
            self::addLog(Text::_($e->getMessage()), 'Log::ERROR');
            Factory::getApplication()->enqueueMessage($e->getMessage(), 'error');
        }
        
        $db->setQuery('UPDATE '. $db->quoteName('#__jdownloads_files') . ' SET `file_date` = NULL WHERE `file_date` = '. $db->quote('0000-00-00 00:00:00'));
        try {
            $db->execute();
        } catch(RuntimeException $e) {
            self::addLog(Text::_($e->getMessage()), 'Log::ERROR');
            Factory::getApplication()->enqueueMessage($e->getMessage(), 'error');
        }
        
        $db->setQuery('UPDATE '. $db->quoteName('#__jdownloads_files') . ' SET `checked_out_time` = NULL WHERE `checked_out_time` = '. $db->quote('0000-00-00 00:00:00'));
        try {
            $db->execute();
        } catch(RuntimeException $e) {
            self::addLog(Text::_($e->getMessage()), 'Log::ERROR');
            Factory::getApplication()->enqueueMessage($e->getMessage(), 'error');
        }
        
        // Copy the created date to the field modify when empty
        $db->setQuery('UPDATE '. $db->quoteName('#__jdownloads_files') . ' SET `modified` = `created` WHERE `modified` = '. $db->quote('0000-00-00 00:00:00'));
        try {
            $db->execute();
        } catch(RuntimeException $e) {
            self::addLog(Text::_($e->getMessage()), 'Log::ERROR');
            Factory::getApplication()->enqueueMessage($e->getMessage(), 'error');
        }
        
        $db->setQuery('UPDATE '. $db->quoteName('#__jdownloads_licenses') . ' SET `checked_out_time` = NULL WHERE `checked_out_time` = '. $db->quote('0000-00-00 00:00:00'));
        try {
            $db->execute();
        } catch(RuntimeException $e) {
            self::addLog(Text::_($e->getMessage()), 'Log::ERROR');
            Factory::getApplication()->enqueueMessage($e->getMessage(), 'error');
        }
        
        $db->setQuery('UPDATE '. $db->quoteName('#__jdownloads_templates') . ' SET `checked_out_time` = NULL WHERE `checked_out_time` = '. $db->quote('0000-00-00 00:00:00'));
        try {
            $db->execute();
        } catch(RuntimeException $e) {
            self::addLog(Text::_($e->getMessage()), 'Log::ERROR');
            Factory::getApplication()->enqueueMessage($e->getMessage(), 'error');
        }
        
        $db->setQuery('UPDATE '. $db->quoteName('#__jdownloads_usergroups_limits') . ' SET `checked_out_time` = NULL WHERE `checked_out_time` = '. $db->quote('0000-00-00 00:00:00'));
        try {
            $db->execute();
        } catch(RuntimeException $e) {
            self::addLog(Text::_($e->getMessage()), 'Log::ERROR');
            Factory::getApplication()->enqueueMessage($e->getMessage(), 'error');
        }
        
        self::addLog(Text::_('COM_JDOWNLOADS_UPGRADE39_HINT_8'), 'Log::INFO');
        self::addLog(Text::_('COM_JDOWNLOADS_UPGRADE39_HINT_9'), 'Log::INFO');
                
        return true;
    }
    
    // Build table creation variables
    private function buildTableDefinitions()
    { 
        $this->categories_table = "CREATE TABLE IF NOT EXISTS `#__jdownloads_categories_40x` (
          `id` int(11) NOT NULL AUTO_INCREMENT,
          `cat_dir` text NOT NULL,
          `cat_dir_parent` text NOT NULL,
          `parent_id` int(11) unsigned NOT NULL DEFAULT '0',
          `lft` int(11) NOT NULL DEFAULT '0',
          `rgt` int(11) NOT NULL DEFAULT '0',
          `level` int(10) unsigned NOT NULL DEFAULT '0',
          `title` varchar(255) NOT NULL DEFAULT '',
          `alias` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL DEFAULT '',
          `description` text NOT NULL,
          `pic` varchar(255) NOT NULL DEFAULT '',
          `access` int(10) unsigned NOT NULL DEFAULT '0',
          `user_access` int(11) unsigned NOT NULL DEFAULT '0',
          `metakey` text NOT NULL,
          `metadesc` text NOT NULL,
          `robots` varchar(255) NOT NULL DEFAULT '',
          `created_user_id` int(10) unsigned NOT NULL DEFAULT '0',
          `created_time` datetime NOT NULL,
          `modified_user_id` int(10) NOT NULL DEFAULT '0',
          `modified_time` datetime NOT NULL,
          `language` char(7) NOT NULL DEFAULT '',
          `notes` text NOT NULL,
          `views` int(10) unsigned NOT NULL DEFAULT '0',
          `params` text NOT NULL,
          `password` varchar(100) NOT NULL DEFAULT '',
          `password_md5` varchar(100) NOT NULL DEFAULT '',
          `ordering` int(11) NOT NULL DEFAULT '0',
          `published` tinyint(1) NOT NULL DEFAULT '0',
          `checked_out` int(11) NOT NULL DEFAULT '0',
          `checked_out_time` datetime,
          `asset_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT 'FK to the #__assets table',
          PRIMARY KEY (`id`),
          KEY `idx_access` (`access`),
          KEY `idx_checked_out` (`checked_out`),
          KEY `idx_left_right` (`lft`,`rgt`),
          KEY `idx_alias` (`alias`(100)),
          KEY `idx_published` (`published`),
          KEY `idx_language` (`language`),
          KEY `idx_cat_dir` (`cat_dir`(100))
        ) ENGINE=InnoDB  DEFAULT CHARSET=utf8mb4 DEFAULT COLLATE=utf8mb4_unicode_ci;";
        
        $this->files_table = "CREATE TABLE IF NOT EXISTS `#__jdownloads_files_40x` (
          `id` int(11) NOT NULL AUTO_INCREMENT,
          `title` varchar(255) NOT NULL DEFAULT '',
          `alias` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL DEFAULT '',
          `description` longtext NOT NULL,
          `description_long` longtext NOT NULL,
          `file_pic` varchar(255) NOT NULL DEFAULT '',
          `images` text NOT NULL,
          `price` varchar(20) NOT NULL DEFAULT '',
          `release` varchar(255) NOT NULL DEFAULT '',
          `file_language` int(3) NOT NULL DEFAULT '0',
          `system` int(3) NOT NULL DEFAULT '0',
          `license` varchar(255) NOT NULL DEFAULT '',
          `url_license` varchar(255) NOT NULL DEFAULT '',
          `license_agree` tinyint(1) NOT NULL DEFAULT '0',
          `size` varchar(255) NOT NULL DEFAULT '',
          `created` datetime NOT NULL,
          `file_date` datetime NULL DEFAULT NULL,
          `publish_up` datetime NULL DEFAULT NULL,
          `publish_down` datetime NULL DEFAULT NULL,
          `use_timeframe` tinyint(1) NOT NULL DEFAULT '0',
          `url_download` varchar(400) NOT NULL DEFAULT '' COMMENT 'contains only the assigned filename',
          `preview_filename` varchar(400) NOT NULL DEFAULT '',
          `other_file_id` int(11) NOT NULL DEFAULT '0',
          `md5_value` varchar(100) NOT NULL DEFAULT '',
          `sha1_value` varchar(100) NOT NULL DEFAULT '',
          `extern_file` varchar(600) NOT NULL DEFAULT '',
          `extern_site` tinyint(1) NOT NULL DEFAULT '0',
          `mirror_1` varchar(600) NOT NULL DEFAULT '',
          `mirror_2` varchar(600) NOT NULL DEFAULT '',
          `extern_site_mirror_1` tinyint(1) NOT NULL DEFAULT '0',
          `extern_site_mirror_2` tinyint(1) NOT NULL DEFAULT '0',
          `url_home` varchar(255) NOT NULL DEFAULT '',
          `author` varchar(255) NOT NULL DEFAULT '',
          `url_author` varchar(255) NOT NULL DEFAULT '',
          `created_by` int(11) NOT NULL DEFAULT '0',
          `created_mail` varchar(255) NOT NULL DEFAULT '',
          `modified_by` int(11) NOT NULL DEFAULT '0',
          `modified` datetime NOT NULL,
          `submitted_by` int(11) NOT NULL DEFAULT '0',
          `set_aup_points` tinyint(1) NOT NULL DEFAULT '0',
          `downloads` int(11) NOT NULL DEFAULT '0',
          `catid` int(11) NOT NULL DEFAULT '0',
          `notes` text NOT NULL,
          `changelog` text NOT NULL,
          `password` varchar(100) NOT NULL DEFAULT '',
          `password_md5` varchar(100) NOT NULL DEFAULT '',
          `views` int(11) NOT NULL DEFAULT '0',
          `metakey` text NOT NULL,
          `metadesc` text NOT NULL,
          `robots` varchar(255) NOT NULL DEFAULT '',
          `update_active` tinyint(1) NOT NULL DEFAULT '0',
          `access` int(10) unsigned NOT NULL DEFAULT '0',
          `user_access` int(11) unsigned NOT NULL DEFAULT '0',
          `language` char(7) NOT NULL DEFAULT '',
          `ordering` int(11) NOT NULL DEFAULT '0',
          `featured` tinyint(1) NOT NULL DEFAULT '0',
          `published` tinyint(1) NOT NULL DEFAULT '0',
          `checked_out` int(11) NOT NULL DEFAULT '0',
          `checked_out_time` datetime NULL DEFAULT NULL,
          `asset_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT 'FK to the #__assets table',
          PRIMARY KEY (`id`),
          KEY `idx_catid` (`catid`),
          KEY `idx_access` (`access`),
          KEY `idx_user_access` (`user_access`),
          KEY `idx_published` (`published`),
          KEY `idx_checked_out` (`checked_out`),
          KEY `idx_alias` (`alias`(191)),
          KEY `idx_created_by` (`created_by`),
          KEY `idx_language` (`language`),
          KEY `idx_featured` (`featured`)      
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 DEFAULT COLLATE=utf8mb4_unicode_ci;";
        
        $this->templates_table = "CREATE TABLE IF NOT EXISTS `#__jdownloads_templates_40x` (
          `id` int(11) NOT NULL AUTO_INCREMENT,
          `template_name` varchar(255) NOT NULL DEFAULT '',
          `template_typ` tinyint(2) NOT NULL DEFAULT '0',
          `template_header_text` longtext NOT NULL,
          `template_subheader_text` longtext NOT NULL,
          `template_footer_text` longtext NOT NULL,
          `template_before_text` text NOT NULL,
          `template_text` longtext NOT NULL,
          `template_after_text` text NOT NULL,
          `template_active` tinyint(1) NOT NULL DEFAULT '0',
          `locked` tinyint(1) NOT NULL DEFAULT '0',
          `note` text NOT NULL,
          `cols` tinyint(1) NOT NULL DEFAULT '1',
          `uses_bootstrap` tinyint(1) NOT NULL DEFAULT '0',
          `uses_w3css` tinyint(1) NOT NULL DEFAULT '0',
          `checkbox_off` tinyint(1) NOT NULL DEFAULT '0',
          `use_to_view_subcats` tinyint(1) NOT NULL DEFAULT '0',
          `symbol_off` tinyint(1) NOT NULL DEFAULT '0',
          `language` char(7) NOT NULL DEFAULT '*',
          `checked_out` int(11) NOT NULL DEFAULT '0',
          `checked_out_time` datetime,
          `preview_id` int(3) NOT NULL DEFAULT '0',
          PRIMARY KEY (`id`),
          KEY `idx_checked_out` (`checked_out`),
          KEY `idx_template_typ` (`template_typ`),
          KEY `idx_language` (`language`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 DEFAULT COLLATE=utf8mb4_unicode_ci;";

        $this->templates_table_2 = "CREATE TABLE IF NOT EXISTS `#__jdownloads_templates_40x` (
          `id` int(11) NOT NULL AUTO_INCREMENT,
          `template_name` varchar(255) NOT NULL DEFAULT '',
          `template_typ` tinyint(2) NOT NULL DEFAULT '0',
          `template_header_text` longtext NOT NULL,
          `template_subheader_text` longtext NOT NULL,
          `template_footer_text` longtext NOT NULL,
          `template_before_text` text NOT NULL,
          `template_text` longtext NOT NULL,
          `template_after_text` text NOT NULL,
          `template_active` tinyint(1) NOT NULL DEFAULT '0',
          `locked` tinyint(1) NOT NULL DEFAULT '0',
          `note` text NOT NULL,
          `cols` tinyint(1) NOT NULL DEFAULT '1',
          `preview_id` int(3) NOT NULL DEFAULT '0',          
          `uses_bootstrap` tinyint(1) NOT NULL DEFAULT '0',
          `uses_w3css` tinyint(1) NOT NULL DEFAULT '0',
          `checkbox_off` tinyint(1) NOT NULL DEFAULT '0',
          `use_to_view_subcats` tinyint(1) NOT NULL DEFAULT '0',
          `symbol_off` tinyint(1) NOT NULL DEFAULT '0',
          `language` char(7) NOT NULL DEFAULT '*',
          `checked_out` int(11) NOT NULL DEFAULT '0',
          `checked_out_time` datetime,
          PRIMARY KEY (`id`),
          KEY `idx_checked_out` (`checked_out`),
          KEY `idx_template_typ` (`template_typ`),
          KEY `idx_language` (`language`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 DEFAULT COLLATE=utf8mb4_unicode_ci;";
        
        $this->licenses_table = "CREATE TABLE IF NOT EXISTS `#__jdownloads_licenses_40x` (
          `id` int(11) NOT NULL AUTO_INCREMENT,
          `title` varchar(255) NOT NULL DEFAULT '',
          `alias` varchar(255) NOT NULL DEFAULT '',
          `description` longtext NOT NULL,
          `url` varchar(255) NOT NULL DEFAULT '',
          `language` char(7) NOT NULL DEFAULT '',
          `checked_out` int(11) NOT NULL DEFAULT '0',
          `checked_out_time` datetime,
          `published` tinyint(1) NOT NULL DEFAULT '0',
          `ordering` int(11) NOT NULL DEFAULT '0',
          PRIMARY KEY (`id`),
          KEY `idx_checked_out` (`checked_out`),
          KEY `idx_language` (`language`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 DEFAULT COLLATE=utf8mb4_unicode_ci;";
        
        $this->ratings_table = "CREATE TABLE IF NOT EXISTS `#__jdownloads_ratings_40x` (
        `file_id` int(11) NOT NULL default '0',
        `rating_sum` int(11) unsigned NOT NULL default '0',
        `rating_count` int(11) unsigned NOT NULL default '0',
        `lastip` varchar(50) NOT NULL default '',
        PRIMARY KEY  (`file_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 DEFAULT COLLATE=utf8mb4_unicode_ci;";
        
        $this->logs_table = "CREATE TABLE IF NOT EXISTS `#__jdownloads_logs_40x` (
          `id` int(11) NOT NULL AUTO_INCREMENT,
          `type` tinyint(1) NOT NULL DEFAULT '1',
          `log_file_id` int(11) NOT NULL DEFAULT '0',
          `log_file_size` varchar(20) NOT NULL DEFAULT '',
          `log_file_name` varchar(255) NOT NULL DEFAULT '',
          `log_title` varchar(255) NOT NULL DEFAULT '',
          `log_ip` varchar(25) NOT NULL DEFAULT '',
          `log_datetime` datetime NOT NULL,
          `log_user` int(11) NOT NULL DEFAULT '0',
          `log_browser` varchar(255) NOT NULL DEFAULT '',
          `language` char(7) NOT NULL DEFAULT '',
          `ordering` int(11) NOT NULL DEFAULT '0',
          PRIMARY KEY (`id`),
          KEY `idx_type` (`type`),
          KEY `idx_log_user` (`log_user`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 DEFAULT COLLATE=utf8mb4_unicode_ci;";                                                

        $this->usergroups_limits_table = "CREATE TABLE IF NOT EXISTS `#__jdownloads_usergroups_limits_40x` (
        `id` INT(11) NOT NULL AUTO_INCREMENT,
        `importance` SMALLINT( 6 ) NOT NULL DEFAULT '0',
        `group_id` INT( 10 ) NOT NULL DEFAULT '0',
        `download_limit_daily` INT(10) NOT NULL DEFAULT '0',
        `download_limit_daily_msg` text NOT NULL,
        `download_limit_weekly` INT(10) NOT NULL DEFAULT '0',
        `download_limit_weekly_msg` text NOT NULL,
        `download_limit_monthly` INT(10) NOT NULL DEFAULT '0',
        `download_limit_monthly_msg` text NOT NULL,
        `download_volume_limit_daily` INT(10) NOT NULL DEFAULT '0',
        `download_volume_limit_daily_msg` text NOT NULL,
        `download_volume_limit_weekly` INT(10) NOT NULL DEFAULT '0',
        `download_volume_limit_weekly_msg` text NOT NULL,
        `download_volume_limit_monthly` INT(10) NOT NULL DEFAULT '0',
        `download_volume_limit_monthly_msg` text NOT NULL,
        `how_many_times` INT( 10 ) NOT NULL DEFAULT '0',
        `how_many_times_msg` text NOT NULL,
        `download_limit_after_this_time` INT( 4 ) NOT NULL DEFAULT '60',
        `transfer_speed_limit_kb` INT(10) NOT NULL DEFAULT '0',
        `upload_limit_daily` INT(10) NOT NULL DEFAULT '0',
        `upload_limit_daily_msg` text NOT NULL,
        `view_captcha` tinyint(1) NOT NULL DEFAULT '0',
        `view_inquiry_form` tinyint(1) NOT NULL DEFAULT '0',
        `view_report_form` tinyint(1) NOT NULL DEFAULT '0',
        `must_form_fill_out` tinyint(1) NOT NULL DEFAULT '0',
        `form_fieldset` CHAR( 100 ) NOT NULL DEFAULT '',
        `countdown_timer_duration` INT(10) NOT NULL DEFAULT '0',
        `countdown_timer_msg` text NOT NULL,
        `may_edit_own_downloads` tinyint(1) NOT NULL DEFAULT '0',
        `may_edit_all_downloads` tinyint(1) NOT NULL DEFAULT '0',
        `use_private_area` tinyint(1) NOT NULL DEFAULT '0',
        `view_user_his_limits` tinyint(1) NOT NULL DEFAULT '0',
        `view_user_his_limits_msg` text NOT NULL,
        `uploads_only_in_cat_id` INT( 11 ) NOT NULL DEFAULT '0',
        `uploads_auto_publish` TINYINT( 1 ) NOT NULL DEFAULT '0',
        `uploads_allowed_types` TEXT NOT NULL,
        `uploads_use_editor` TINYINT( 1 ) NOT NULL DEFAULT '1',
        `uploads_use_tabs` TINYINT( 1 ) NOT NULL DEFAULT '1',    
        `uploads_allowed_preview_types` TEXT NOT NULL,
        `uploads_maxfilesize_kb` CHAR( 15 ) NOT NULL DEFAULT '2048',
        `uploads_form_text` TEXT NOT NULL,
        `uploads_max_amount_images` INT( 3 ) NOT NULL DEFAULT '3',
        `uploads_can_change_category` tinyint(1) NOT NULL DEFAULT '1',    
        `uploads_default_access_level` INT( 10 ) NOT NULL DEFAULT '0',
        `uploads_view_upload_icon` TINYINT( 1 ) NOT NULL DEFAULT '1',
        `uploads_allow_custom_tags` TINYINT( 1 ) NOT NULL DEFAULT '1',    
        `form_title` TINYINT( 1 ) NOT NULL DEFAULT '1',
        `form_alias` TINYINT( 1 ) NOT NULL DEFAULT '1',
        `form_alias_x` TINYINT( 1 ) NOT NULL DEFAULT '0',
        `form_version` TINYINT( 1 ) NOT NULL DEFAULT '1',
        `form_version_x` TINYINT( 1 ) NOT NULL DEFAULT '0',
        `form_access` TINYINT( 1 ) NOT NULL DEFAULT '0',    
        `form_access_x` TINYINT( 1 ) NOT NULL DEFAULT '0',    
        `form_user_access` TINYINT( 1 ) NOT NULL DEFAULT '0',
        `form_update_active` TINYINT( 1 ) NOT NULL DEFAULT '0',
        `form_file_language` TINYINT( 1 ) NOT NULL DEFAULT '1',
        `form_file_language_x` TINYINT( 1 ) NOT NULL DEFAULT '0',
        `form_file_system` TINYINT( 1 ) NOT NULL DEFAULT '1',
        `form_file_system_x` TINYINT( 1 ) NOT NULL DEFAULT '0',
        `form_license` TINYINT( 1 ) NOT NULL DEFAULT '1',
        `form_license_x` TINYINT( 1 ) NOT NULL DEFAULT '0',
        `form_confirm_license` TINYINT( 1 ) NOT NULL DEFAULT '1',
        `form_short_desc` TINYINT( 1 ) NOT NULL DEFAULT '1',
        `form_short_desc_x` TINYINT( 1 ) NOT NULL DEFAULT '0',
        `form_long_desc` TINYINT( 1 ) NOT NULL DEFAULT '1',
        `form_long_desc_x` TINYINT( 1 ) NOT NULL DEFAULT '0',
        `form_changelog` TINYINT( 1 ) NOT NULL DEFAULT '1',        
        `form_changelog_x` TINYINT( 1 ) NOT NULL DEFAULT '0',        
        `form_category` TINYINT( 1 ) NOT NULL DEFAULT '1',
        `form_view_access` TINYINT( 1 ) NOT NULL DEFAULT '0',
        `form_language` TINYINT( 1 ) NOT NULL DEFAULT '0',
        `form_language_x` TINYINT( 1 ) NOT NULL DEFAULT '0',
        `form_published` TINYINT( 1 ) NOT NULL DEFAULT '0',
        `form_featured` TINYINT( 1 ) NOT NULL DEFAULT '0',
        `form_creation_date` TINYINT( 1 ) NOT NULL DEFAULT '1',
        `form_creation_date_x` TINYINT( 1 ) NOT NULL DEFAULT '0',
        `form_modified_date` TINYINT( 1 ) NOT NULL DEFAULT '1',
        `form_timeframe` TINYINT( 1 ) NOT NULL DEFAULT '0',
        `form_views` TINYINT( 1 ) NOT NULL DEFAULT '0',
        `form_downloaded` TINYINT( 1 ) NOT NULL DEFAULT '0',
        `form_ordering` TINYINT( 1 ) NOT NULL DEFAULT '0', 
        `form_password` TINYINT( 1 ) NOT NULL DEFAULT '0',
        `form_password_x` TINYINT( 1 ) NOT NULL DEFAULT '0',
        `form_price` TINYINT( 1 ) NOT NULL DEFAULT '1',
        `form_price_x` TINYINT( 1 ) NOT NULL DEFAULT '0',
        `form_website` TINYINT( 1 ) NOT NULL DEFAULT '1',
        `form_website_x` TINYINT( 1 ) NOT NULL DEFAULT '0',
        `form_author_name` TINYINT( 1 ) NOT NULL DEFAULT '1',
        `form_author_name_x` TINYINT( 1 ) NOT NULL DEFAULT '0',
        `form_author_mail` TINYINT( 1 ) NOT NULL DEFAULT '1',
        `form_author_mail_x` TINYINT( 1 ) NOT NULL DEFAULT '0',
        `form_file_pic` TINYINT( 1 ) NOT NULL DEFAULT '1',
        `form_file_pic_x` TINYINT( 1 ) NOT NULL DEFAULT '0',
        `form_select_main_file` TINYINT( 1 ) NOT NULL DEFAULT '1',
        `form_select_main_file_x` TINYINT( 1 ) NOT NULL DEFAULT '0',
        `form_file_size` TINYINT( 1 ) NOT NULL DEFAULT '1',
        `form_file_date` TINYINT( 1 ) NOT NULL DEFAULT '1',
        `form_file_date_x` TINYINT( 1 ) NOT NULL DEFAULT '0',
        `form_select_preview_file` TINYINT( 1 ) NOT NULL DEFAULT '1',
        `form_select_preview_file_x` TINYINT( 1 ) NOT NULL DEFAULT '0',
        `form_external_file` TINYINT( 1 ) NOT NULL DEFAULT '1',
        `form_external_file_x` TINYINT( 1 ) NOT NULL DEFAULT '0',
        `form_mirror_1` TINYINT( 1 ) NOT NULL DEFAULT '1',
        `form_mirror_1_x` TINYINT( 1 ) NOT NULL DEFAULT '0',
        `form_mirror_2` TINYINT NOT NULL DEFAULT '1',
        `form_mirror_2_x` TINYINT NOT NULL DEFAULT '0',
        `form_images` TINYINT( 1 ) NOT NULL DEFAULT '1',
        `form_images_x` TINYINT( 1 ) NOT NULL DEFAULT '0',
        `form_meta_desc` TINYINT( 1 ) NOT NULL DEFAULT '1',
        `form_meta_key` TINYINT( 1 ) NOT NULL DEFAULT '1',
        `form_robots` TINYINT( 1 ) NOT NULL DEFAULT '1',
        `form_tags` TINYINT( 1 ) NOT NULL DEFAULT '0',
        `form_select_from_other` TINYINT( 1 ) NOT NULL DEFAULT '0',
        `form_created_id` TINYINT( 1 ) NOT NULL DEFAULT '0',
        `form_created_id_x` TINYINT( 1 ) NOT NULL DEFAULT '0',
        `notes` text NOT NULL,
        `checked_out` int(11) NOT NULL default '0',
        `checked_out_time` datetime,    
        PRIMARY KEY (`id`),
        KEY `idx_checked_out` (`checked_out`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 DEFAULT COLLATE=utf8mb4_unicode_ci;";
        
        $this->categories_table_fields = 'id, cat_dir, cat_dir_parent, parent_id, lft, rgt, level, title, alias, description, pic, access, user_access, metakey, metadesc, robots, created_user_id, created_time, modified_user_id, modified_time, language, notes, views, params, password, password_md5, ordering, published, checked_out, checked_out_time, asset_id';
        $this->files_table_fields = 'id, title, alias, description, description_long, file_pic, images, price, release, file_language, system, license, url_license, license_agree, size, created, file_date, publish_up, publish_down, use_timeframe, url_download, preview_filename, other_file_id, md5_value, sha1_value, extern_file, extern_site, mirror_1, mirror_2, extern_site_mirror_1, extern_site_mirror_2, url_home, author, url_author, created_by, created_mail, modified_by, modified, submitted_by, set_aup_points, downloads, catid, notes, changelog, password, password_md5, views, metakey, metadesc, robots, update_active, access, user_access, language, ordering, featured, published, checked_out, checked_out_time, asset_id';        
        $this->templates_table_fields = 'id, template_name, template_typ, template_header_text, template_subheader_text, template_footer_text, template_before_text, template_text, template_after_text, template_active, locked, note, cols, uses_bootstrap, uses_w3css, checkbox_off, use_to_view_subcats, symbol_off, language, checked_out, checked_out_time, preview_id';        
        $this->licenses_table_fields = 'id, title, alias, description, url, language, checked_out, checked_out_time, published, ordering';
        $this->ratings_table_fields = 'file_id, rating_sum, rating_count, lastip';  
        $this->logs_table_fields = 'id, type, log_file_id, log_file_size, log_file_name, log_title, log_ip, log_datetime, log_user, log_browser, language, ordering';
        $this->usergroups_limits_table_fields = 'id, importance, group_id, download_limit_daily, download_limit_daily_msg, download_limit_weekly, download_limit_weekly_msg, download_limit_monthly, download_limit_monthly_msg, download_volume_limit_daily, download_volume_limit_daily_msg, download_volume_limit_weekly, download_volume_limit_weekly_msg, download_volume_limit_monthly, download_volume_limit_monthly_msg, how_many_times, how_many_times_msg, download_limit_after_this_time, transfer_speed_limit_kb, upload_limit_daily, upload_limit_daily_msg, view_captcha, view_inquiry_form, view_report_form, must_form_fill_out, form_fieldset, countdown_timer_duration, countdown_timer_msg, may_edit_own_downloads, may_edit_all_downloads, use_private_area, view_user_his_limits, view_user_his_limits_msg, uploads_only_in_cat_id, uploads_auto_publish, uploads_allowed_types, uploads_use_editor, uploads_use_tabs, uploads_allowed_preview_types, uploads_maxfilesize_kb, uploads_form_text, uploads_max_amount_images, uploads_can_change_category, uploads_default_access_level, uploads_view_upload_icon, uploads_allow_custom_tags, form_title, form_alias, form_alias_x, form_version, form_version_x, form_access, form_access_x, form_user_access, form_update_active, form_file_language, form_file_language_x, form_file_system, form_file_system_x, form_license, form_license_x, form_confirm_license, form_short_desc, form_short_desc_x, form_long_desc, form_long_desc_x, form_changelog, form_changelog_x, form_category, form_view_access, form_language, form_language_x, form_published, form_featured, form_creation_date, form_creation_date_x, form_modified_date, form_timeframe, form_views, form_downloaded, form_ordering, form_password, form_password_x, form_price, form_price_x, form_website, form_website_x, form_author_name, form_author_name_x, form_author_mail, form_author_mail_x, form_file_pic, form_file_pic_x, form_select_main_file, form_select_main_file_x, form_file_size, form_file_date, form_file_date_x, form_select_preview_file, form_select_preview_file_x, form_external_file, form_external_file_x, form_mirror_1, form_mirror_1_x, form_mirror_2, form_mirror_2_x, form_images, form_images_x, form_meta_desc, form_meta_key, form_robots, form_tags, form_select_from_other, form_created_id, form_created_id_x, notes, checked_out, checked_out_time';    
    }
    
    /**
    * Checks the number of all data fields in the jDownloads tables. 
    * The basis is the table format of version 3.9.8.6. 
    *
    * Note: Perhaps this should be expanded to also check the correct order.
    *  
    */
    private function checkDatabaseTableStructures(){
    
        $db = Factory::getDBO();
        
        $tables        = array('#__jdownloads_categories', '#__jdownloads_files', '#__jdownloads_licenses', '#__jdownloads_logs', '#__jdownloads_ratings', '#__jdownloads_templates', '#__jdownloads_usergroups_limits');
        $amount_fields = array(31, 60, 10, 12, 4, 22, 118);
        
        $i = 0;
        $a = 0;
                
        // Read the tables columns
        foreach ($tables as $table){
            $fields[$i++] = $db->getTableColumns($table);
            $current_field_string[$i-1] = implode(', ',array_keys($fields[$i-1]));
        }
        
        // Compare the amount of columns
        foreach ($fields as $field){
            if ($field){
                $field_string[$a] = implode(', ',array_keys($field));
                
                if (count($field) !== $amount_fields[$a++]){
                    // Result is incorrect
                    $this->wrong_table[($a-1)] = 'The Table: <b>'.$tables[($a-1)].'</b> has an incorrect number of data fields. This means that an update can probably not be carried out without errors.';
                    
                    // Add error in log
                    self::addLog($this->wrong_table[($a-1)], 'Log::ERROR');
                }
                
                // Compare the fields position and try to correct a wrong position
                $result = self::moveFieldsToCorrectPositions($tables[($a-1)], $field);
            }
        }
        
        if ($this->wrong_table || $this->wrong_fields){
            // Abort the upgrade process
            return false;
        }
        
        return true;
    }
    
    private function moveFieldsToCorrectPositions($table, $fields){
        
        $db = Factory::getDBO();    
        
        if (!$table || !$fields) return false;
        
        switch ($table){
            case '#__jdownloads_categories':
                $fields_should_be = $this->categories_table_fields;
                break;
            
            case '#__jdownloads_files':
                $fields_should_be = $this->files_table_fields;
                break;
                
            case '#__jdownloads_licenses':
                $fields_should_be = $this->licenses_table_fields;
                break;
            
            case '#__jdownloads_logs':
                $fields_should_be = $this->logs_table_fields;
                break;
                
            case '#__jdownloads_ratings':
                $fields_should_be = $this->ratings_table_fields;
                break;
                
            case '#__jdownloads_templates':
                $fields_should_be = $this->templates_table_fields;
                break;
                
            case '#__jdownloads_usergroups_limits':
                $fields_should_be = $this->usergroups_limits_table_fields;
                break;
            
        }
        
        $fields_are = implode(', ',array_keys($fields));
        
        if ($fields_are !== $fields_should_be){
            $fields_are_arr         = explode(', ', $fields_are);
            $fields_should_be_arr   = explode(', ', $fields_should_be);
            
            for ($i=0; $i < count($fields_should_be_arr); $i++){
                if ($fields_are_arr[$i] !== $fields_should_be_arr[$i]){
                    $error = true;
                    
                    // Try to fix it
                    if ($table == '#__jdownloads_files' && $fields_are_arr[$i] == 'tags'){
                        $db->setQuery('ALTER TABLE '. $db->quoteName('#__jdownloads_files') . ' DROP COLUMN `tags`');
                        try {
                            $result = $db->execute();
                        } catch(RuntimeException $e) {
                            self::addLog(Text::_($e->getMessage()), 'Log::ERROR');
                            Factory::getApplication()->enqueueMessage($e->getMessage(), 'error');
                        }
                        
                        if ($result){
                            $error = false;
                            self::addLog('Files field `tags` successful removed from table.', 'Log::INFO');
                            // Exit the loop.
                            break;                       
                        }
                    }
                    
                    if ($table == '#__jdownloads_templates' && $fields_are_arr[$i] == 'preview_id'){
                        $db->setQuery('ALTER TABLE '. $db->quoteName('#__jdownloads_templates') . ' CHANGE `preview_id` `preview_id` TINYINT(3) NOT NULL DEFAULT 0 AFTER `checked_out_time`');
                        try {
                            $result = $db->execute();
                        } catch(RuntimeException $e) {
                            self::addLog(Text::_($e->getMessage()), 'Log::ERROR');
                            Factory::getApplication()->enqueueMessage($e->getMessage(), 'error');
                        }
                        
                        if ($result){
                            $error = false;
                            self::addLog('Templates field `preview_id` successful moved at the end.', 'Log::INFO');
                            // Exit the loop.
                            break;                       
                        }
                    }
                    
                    if ($error === true){
                        $this->wrong_fields[] = $table.': '.$fields_are_arr[$i];
                    }
                }  
            }
            
            return; 
            
        } else {
            return;  
        }
    }
}