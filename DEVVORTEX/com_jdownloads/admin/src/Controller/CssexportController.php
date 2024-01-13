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

namespace JDownloads\Component\JDownloads\Administrator\Controller; 

\defined( '_JEXEC' ) or die;

use Joomla\Utilities\ArrayHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Factory;
use Joomla\CMS\Session\Session;
use Joomla\CMS\MVC\Controller\AdminController;
use Joomla\CMS\Filesystem\File;
use Joomla\CMS\Filesystem\Folder;

/**
 * jDownloads css export Controller
 *
 */
class CssexportController extends AdminController
{
    /**
     * Constructor
     */
    function __construct()
    {
        parent::__construct();
    }
    
	/**
	 * logic to send the selected css file to the clients browser
     * 
	 */
	public function export()
    {
        // Check for request forgeries.
        Session::checkToken() or jexit(Text::_('JINVALID_TOKEN'));

        $app = Factory::getApplication();
        
        // Access check.
        if (!$app->getIdentity()->authorise('core.admin','com_jdownloads')){
            $app->enqueueMessage( Text::_('JERROR_ALERTNOAUTHOR'), 'warning');
            $app->redirect(ROUTE::_('index.php?option=com_jdownloads&view=layouts', false));
            
        } else {       
        
            $db = Factory::getDBO();
            $jinput = Factory::getApplication()->input;
            
            ini_set('max_execution_time', '300');
            
            $file = $jinput->get('filename', '', 'string');
            $source_path = JPATH_COMPONENT_SITE.'/assets/css/'.$file;
            $ss = is_file($source_path);
            $len = filesize($source_path);
            $file_extension = File::getExt($file);
            $ctype = 'text/css';
            
            if ($file && File::exists($source_path)){
                // send the file

                ob_end_clean();

                // needed for MS IE - otherwise content disposition is not used?
                if (ini_get('zlib.output_compression')){
                    ini_set('zlib.output_compression', 'Off');
                }
                
                header("Cache-Control: public, must-revalidate");
                header('Cache-Control: pre-check=0, post-check=0, max-age=0');
                header("Expires: 0"); 
                header("Content-Description: File Transfer");
                header("Expires: Sat, 26 Jul 1997 05:00:00 GMT");
                header("Content-Type: " . $ctype);
                header("Content-Length: ".(string)$len);
                header('Content-Disposition: attachment; filename="'.$file.'"');
                header("Content-Transfer-Encoding: binary\n");

                if (!ini_get('safe_mode')){ 
                    @set_time_limit(0);
                }

                @readfile($source_path);                
                exit;
            } else {
                // file not found                    
                $app->enqueueMessage( Text::_('COM_JDOWNLOADS_CSS_EXPORT_ERROR'), 'error');
                $app->redirect(ROUTE::_('index.php?option=com_jdownloads&view=layouts', false));
            }
        }
        exit;
    }
}
?>