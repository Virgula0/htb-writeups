<?php
/**
 * @package jDownloads
 * @version 4.0  
 * @copyright (C) 2007 - 2013 - Arno Betz - www.jdownloads.com
 * @license http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.txt
 * 
 * jDownloads is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 */

namespace JDownloads\Component\JDownloads\Administrator\Controller;
 
\defined( '_JEXEC' ) or die;

use Joomla\CMS\Uri\Uri;
use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\MVC\Controller\AdminController;
use Joomla\CMS\Table\Table;

use JDownloads\Component\JDownloads\Administrator\Helper\JDownloadsHelper;
use JDownloads\Component\JDownloads\Administrator\Table\JDCategoryTable;
use JDownloads\Component\JDownloads\Administrator\Table\DownloadTable;

/**
 * jDownloads backup Controller
 *
 */
class BackupController extends AdminController 
{
	/**
	 * Constructor
	 *                                 
	 */
	function __construct()
	{
		parent::__construct();
	}

	/**
	 * logic for create the backup file
	 *
	 */
    public function runbackup()
    {
        $jinput = Factory::getApplication()->input;
        
        $jd_version = JDownloadsHelper::getjDownloadsVersion();
        $jd_version = str_replace(' ', '_', $jd_version);
  	    $add_also_logs = $jinput->get('logs', 0, 'int');
        
        // check user access right
        $app = Factory::getApplication();
        $user = $app->getIdentity();
        
        if ($user->authorise('com_jdownloads.core.admin','com_jdownloads'))
        {  
            $website_title = Factory::getApplication()->get('sitename', 'noname');
            $from_url      = Uri::getInstance();
            $from_url      = $from_url->toString();
                
            $db = Factory::getDBO();
  		    $prefix = JDownloadsHelper::getCorrectDBPrefix();
            Table::addIncludePath(JPATH_ADMINISTRATOR.'/components/com_jdownloads/src/Table');
      
            if ($add_also_logs){
                $dbtables = array($prefix.'jdownloads_categories', $prefix.'jdownloads_files', $prefix.'jdownloads_licenses', $prefix.'jdownloads_ratings', $prefix.'jdownloads_logs', $prefix.'jdownloads_templates', $prefix.'jdownloads_usergroups_limits', $prefix.'assets');
            } else {
                // logs are not stored                
                $dbtables = array($prefix.'jdownloads_categories', $prefix.'jdownloads_files', $prefix.'jdownloads_licenses', $prefix.'jdownloads_ratings', $prefix.'jdownloads_templates', $prefix.'jdownloads_usergroups_limits', $prefix.'assets');    
            }    
  		    $file = '<?php'."\r\n";
            $file .= '/* ===== jDownloads Backup from: '.$website_title." ===== Source URL: ".$from_url." ===== */ \r\n";
            $file .= '$i = 0;'."\r\n";
            
  		    for ($i=0; $i < count($dbtables); $i++) {
                
                // the target db can has an other prefix, so we can not use it here
                $table_name = str_replace($prefix, '#__', $dbtables[$i]);
                // make not the Joomla asset table empty!!!
                if ($dbtables[$i] != $prefix.'assets'){  
                    $file .= '$db->setQuery("TRUNCATE TABLE `'.$table_name.'`") ;$db->execute();'."\r\n";
                } else {
                    // only remove all olders jdownloads categories and downloads from asset table
                    // but not the component root item (level=1)
                    $file .= '$db->setQuery("DELETE FROM `'.$table_name.'` WHERE `name` LIKE '.$db->quote('com_jdownloads%').' AND `level` > '.$db->quote('1').'"); $db->execute();'."\r\n";
                }    
            }    
            
            // we will backup not the assets in this version
            array_pop($dbtables);
            
  		    foreach($dbtables as $dbtable){
  			    if ($dbtable == $prefix.'jdownloads_ratings'){
                    $db->setQuery("SELECT file_id FROM $dbtable");
                } else {    
                    $db->setQuery("SELECT id FROM $dbtable");
                }
  			    
                $xids = $db->loadObjectList();
  			    foreach($xids as $xid){
  				    switch($dbtable){
  					    case $prefix.'jdownloads_categories':
                            $object = Table::getInstance('JDCategoryTable', 'JDownloads\\Component\\JDownloads\\Administrator\\Table\\');
  					    break;
  					    case $prefix.'jdownloads_files':
                            $object = Table::getInstance('DownloadTable', 'JDownloads\\Component\\JDownloads\\Administrator\\Table\\');
  					    break;
  					    case $prefix.'jdownloads_licenses':
                            $object = Table::getInstance('LicenseTable', 'JDownloads\\Component\\JDownloads\\Administrator\\Table\\');
  					    break;
  					    case $prefix.'jdownloads_templates':
                            $object = Table::getInstance('TemplateTable', 'JDownloads\\Component\\JDownloads\\Administrator\\Table\\');
  					    break;
                        case $prefix.'jdownloads_logs':
                            $object = Table::getInstance('LogTable', 'JDownloads\\Component\\JDownloads\\Administrator\\Table\\');
                        break;
                        case $prefix.'jdownloads_ratings':
                            $object = Table::getInstance('RatingTable', 'JDownloads\\Component\\JDownloads\\Administrator\\Table\\');
                        break;
                        case $prefix.'jdownloads_usergroups_limits':
                            $object = Table::getInstance('GroupTable', 'JDownloads\\Component\\JDownloads\\Administrator\\Table\\');
                        break;
                         case $prefix.'assets':
                            $object = Table::getInstance('AssetsTable', 'JDownloads\\Component\\JDownloads\\Administrator\\Table\\');
                        break;
                        
                    }
      			    
                    // get the data row
                    if ($dbtable == $prefix.'jdownloads_ratings'){
                        $db->setQuery("SELECT * FROM ".$prefix.'jdownloads_ratings'." WHERE `file_id` = '$xid->file_id'");
                        $row = $db->loadObject();                        
                    } else {    
                        $object->load($xid->id);
                    }                    
       
                    // Remove the not required typeAlias field
                        unset ($object->typeAlias);
       
                    // the target db can has an other prefix, so we can not use it here
                    $table_name = str_replace($prefix, '#__', $dbtable);
  				    if ($table_name != '#__jdownloads_ratings'){
                        $sql = '$db->setQuery("INSERT INTO '.$table_name.' ( %s ) VALUES ( %s );"); $db->execute();$i++; '."\r\n";
  				        $fields = array();
  				        $values = array();
  				        foreach (get_object_vars( $object ) as $k => $v) {
  					        if (is_array($v) or is_object($v) or $v === NULL) {
  						        continue;
  					        }
  					        if ($k[0] == '_') {
  						        continue;
  					        }
  					        
                            // set field name
                            $fields[] = $db->quoteName( $k );
  					        
                            // set field value (but not for ID field from assets table!!!)
                            if ($table_name == '#__assets' && $k == 'id'){
                                $values[] = "''";
                            } else {
                                // write 0 to asset id
                                if ($k == 'asset_id'){
                                    $values[] = "'0'";
                                } else {
                                    $values[] = $db->Quote( $v );    
                                }    
                            }
  				        }
  				        $file .= sprintf( $sql, implode( ",", $fields ) ,  implode( ",", $values ) );
                    } else {
                        // special handling for ratings table required, then we have here not a primary key
                        $file .= '$db->setQuery("INSERT INTO '.$table_name.' ( `file_id`,`rating_sum`,`rating_count`,`lastip` ) VALUES ( '.$db->quote($row->file_id).','.$db->quote($row->rating_sum).','.$db->quote($row->rating_count).','.$db->quote($row->lastip).' );"); $db->execute();$i++; '."\r\n";
                    }   
  			    }
  		    }
            $date_current = HtmlHelper::_('date', '','Y-m-d_H:i:s');
  		    $file .= "\r\n?>";
  		    
            // Do clean up the output buffer
            while (ob_get_level() > 0)
                @ob_end_clean();
            
  		    header ("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
  		    header ("Last-Modified: " . gmdate("D,d M Y H:i:s") . " GMT");
  		    header ("Cache-Control: no-store, no-cache, must-revalidate");
            header ('Cache-Control: post-check=0, pre-check=0', false );
  		    header ("Pragma: no-cache");
  		    header ("Content-type: text/plain");
  		    header ('Content-Disposition: attachment; filename="'.'backup_jdownloads_v'.$jd_version.'_date_'.$date_current.'_.txt'.'"' );
  		    print $file;
            
        }     
  		    exit;
    }	
}
?>