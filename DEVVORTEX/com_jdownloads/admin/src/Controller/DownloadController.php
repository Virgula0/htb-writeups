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

use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Controller\FormController;
use Joomla\Input\Input;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;
use Joomla\CMS\MVC\Factory\MVCFactoryInterface;
use Joomla\CMS\Filter\OutputFilter;
use Joomla\CMS\Session\Session;

use JDownloads\Component\JDownloads\Administrator\Helper\JDownloadsHelper;

/**
 * Template controller class.
 *
 */
class DownloadController extends FormController
{
    var $tmpl_type = 0;
    
    /**
     * Constructor
     *
     */
    public function __construct($config = array(), MVCFactoryInterface $factory = null, $app = null, $input = null)
    {
        parent::__construct($config, $factory, $app, $input);
    
        // Register Extra task
        $this->registerTask( 'apply',           'save' );
        $this->registerTask( 'add',             'edit' );
        $this->registerTask( 'download',        'download' );
        $this->registerTask( 'delete',          'delete' );
        $this->registerTask( 'deletepreview',   'deletepreview' );        
        $this->registerTask( 'create',          'add' );
        
        // store filename in session when is selected in files list
        $jinput = Factory::getApplication()->input;
        $filename = ($jinput->get('file', '', 'string'));
        $filename = OutputFilter::cleanText($filename);
        $session = Factory::getSession();
        if ($filename != ''){
            $session->set('jd_filename',$filename);
        } else {
            $session->set('jd_filename','');            
        }      
    }

    /**
     * Method override to check if you can add a new record.
     *
     * @param    array    $data    An array of input data.
     * @return    boolean
     */
    protected function allowAdd($data = array()) 
    {
        // Initialise variables. 
        $user  = Factory::getApplication()->getIdentity();
        $allow = null;
        $allow = $user->authorise('core.create', 'com_jdownloads');
        
        if ($allow === null) {
            return parent::allowAdd($data);
        } else {
            return $allow;
        }
    }
    
    /**
     * Method to check if you can edit a record.
     *
     * @param    array    $data    An array of input data.
     * @param    string    $key    The name of the key for the primary key.
     *
     * @return    boolean
     */
    protected function allowEdit($data = array(), $key = 'id')
    {
        // Initialise variables. 
        $user   = Factory::getApplication()->getIdentity();
        $allow  = null;
        $allow  = $user->authorise('core.edit', 'com_jdownloads');
        
        if ($allow === null) {
            return parent::allowEdit($data, $key);
        } else {
            return $allow;
        }
    }
    
    public function download()
    {
        $jinput = Factory::getApplication()->input;
        $id     = $jinput->get('id', 0, 'integer');
        $type   = $jinput->get('type', '', 'string');
        
        if ($id){
            JDownloadsHelper::downloadFile($id, $type);
        }        
        // set redirect
        $this->setRedirect( 'index.php?option=com_jdownloads&view=files', $msg );
    }
    
    // delete the assigned file from a download
    public function delete()
    {
        $jinput = Factory::getApplication()->input;
        $id = $jinput->get('id', 0, 'integer');
        $type = $jinput->get('type', '', 'string');
        
        $result = false;
        $msg    = '';
        
        if ($id){
            if ($type == 'prev'){
                $result = JDownloadsHelper::deletePreviewFile($id);
            } else {
                $result = JDownloadsHelper::deleteFile($id);
            }
            if ($result){
                $msg = Text::_('COM_JDOWNLOADS_BACKEND_FILESEDIT_REMOVE_OK');
            } else {
                $msg = Text::_('COM_JDOWNLOADS_BACKEND_FILESEDIT_REMOVE_ERROR');
            }
        }        
        // set redirect
        $this->setRedirect( 'index.php?option=com_jdownloads&task=download.edit&id='.$id, $msg );
    }
    
    /**
     * Method to run batch operations.
     *
     * @param   object  $model  The model.
     *
     * @return  boolean   True if successful, false otherwise and internal error is set.
     * 
     */
    
    public function batch($model = null) 
    {
        Session::checkToken() or jexit(Text::_('JINVALID_TOKEN'));
        
        $model = $this->getModel('download', '', array());
        
        // Preset the redirect
        $this->setRedirect(ROUTE::_('index.php?option=com_jdownloads&view=downloads'.$this->getRedirectToListAppend(), false));
        
        return parent::batch($model);
    }
    
    /**
     * Method to reload a record.
     *
     * @param   string  $key     The name of the primary key of the URL variable.
     * @param   string  $urlVar  The name of the URL variable if different from the primary key (sometimes required to avoid router collisions).
     *
     * @return  void
     *
     */
    public function reload($key = null, $urlVar = 'id')
    {
        return parent::reload($key, $urlVar);
    }
    
}
?>    