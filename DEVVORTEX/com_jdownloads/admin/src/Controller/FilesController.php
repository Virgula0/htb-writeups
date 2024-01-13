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

use Joomla\CMS\Application\CMSApplication;
use Joomla\Utilities\ArrayHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Controller\AdminController;
use Joomla\CMS\MVC\Factory\MVCFactoryInterface;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Filesystem\File;
use Joomla\CMS\Filesystem\Folder;

use JDownloads\Component\JDownloads\Administrator\Helper\JDownloadsHelper;

/**
 * Jdownloads files Controller
 *
 * @package Joomla
 * @subpackage Jdownloads
 */
class FilesController extends AdminController
{
	/**
	 * Constructor
	 *
	 */
	function __construct()
	{
		parent::__construct();

	}
    
    public function getModel($name = 'files', $prefix = 'jdownloadsModel', $config = array('ignore_request' => true))
    {
        $model = parent::getModel($name, $prefix, $config);
        return $model;
    }

    public function delete()
    {
        $params = ComponentHelper::getParams('com_jdownloads');

        $canDo = JDownloadsHelper::getActions();
         
        if ($canDo->get('core.delete')) {
         
	        $msg = '';
	        $deleted = 0;
	
	        $cid    = $this->input->get('cid', array(), 'array');
	
	        if (count($cid)){
	            foreach ($cid as $file){
				// sanitize the filename
	                $file = JDownloadsHelper::sanitizeUrlParam($file);
                     
                    if (is_file($params->get('files_uploaddir').'/'.$file)){
	
	                    // delete the file
	                    if (!File::delete($params->get('files_uploaddir').'/'.$file)){
	                        // can not delete!
	                        $this->setRedirect( 'index.php?option=com_jdownloads&view=files', Text::_('COM_JDOWNLOADS_MANAGE_FILES_DELETE_ERROR'), 'error');
	                    } else {    
	                        $deleted++;
	                    } 
	                }
				    if ($deleted){
						// successful!
	             	    $msg = sprintf(Text::_('COM_JDOWNLOADS_MANAGE_FILES_DELETE_SUCCESS'),$deleted);    
				    }
                }
  			}
        }    
        // set redirect
        $this->setRedirect( 'index.php?option=com_jdownloads&view=files', $msg );
    }
    
    public function uploads() 
    {
         // set redirect
         $this->setRedirect( 'index.php?option=com_jdownloads&view=uploads');        
    }  
    
                  
    public function downloads() 
    {
         // set redirect
         $this->setRedirect( 'index.php?option=com_jdownloads&view=downloads');        
    }  
    
}
?>