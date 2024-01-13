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

use Joomla\Input\Input;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Controller\AdminController;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;

/**
 * jDownloads logs controller class.
 * @package jDownloads
 */
class LogsController extends AdminController
{
                                                                                    
    /**
     * Proxy for getModel.
     */
    public function getModel($name = 'Logs', $prefix = 'Administrator', $config = array('ignore_request' => true))
    {
        $model = parent::getModel($name, $prefix, $config);
        return $model;
    }
    
    // add marked log IDs to the block IP list 
    public function blockip(){
        
        $jinput = Factory::getApplication()->input;
        
        $cid    = $this->input->get('cid', array(), 'array');

        $model  = $this->getModel( 'logs' );
        
        if ($model->blockip($cid)) {
            $msg = Text::_( 'COM_JDOWNLOADS_BACKEND_LOG_LIST_BLOCK_IP_ADDED' );
        } else {
            $msg = Text::_( 'COM_JDOWNLOADS_BACKEND_LOG_LIST_BLOCK_IP_NOT_ADDED' );
        }
        $link = 'index.php?option=com_jdownloads&view=logs';
        $this->setRedirect($link, $msg);
    }
}
?>