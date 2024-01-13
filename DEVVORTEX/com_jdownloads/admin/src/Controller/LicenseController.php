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

use JDownloads\Component\JDownloads\Administrator\Model\LicenseModel;

/**
 * License controller class.
 *
 * @package        Joomla.Administrator
 * @subpackage    com_weblinks
 * @since        1.6
 */
class LicenseController extends FormController
{
  
    /**
     * Constructor
     *
     */
    public function __construct($config = array(), MVCFactoryInterface $factory = null, $app = null, $input = null)
    {
        parent::__construct();

        // Register Extra task
        $this->registerTask( 'apply', 'save' );
        $this->registerTask( 'add',   'edit' );
    }
    
    /**
     * Proxy for getModel.
     */
    public function getModel($name = 'License', $prefix = 'jdownloadsModel', $config = array('ignore_request' => true))
    {
        $model = parent::getModel($name, $prefix, $config);
        return $model;
    }    

    /**
     * Method override to check if you can add a new record.
     *
     * @param    array    $data    An array of input data.
     * @return    boolean
     * @since    1.6
     */
    protected function allowAdd($data = array()) 
    {
        // Initialise variables. 
        $user     = Factory::getApplication()->getIdentity();
        $allow    = null;
        $allow    = $user->authorise('core.create', 'com_jdownloads');
        
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
     * @since    1.6
     */
    protected function allowEdit($data = array(), $key = 'id')
    {
        // Initialise variables. 
        $user     = Factory::getApplication()->getIdentity();
        $allow    = null;
        $allow    = $user->authorise('core.edit', 'com_jdownloads');
        if ($allow === null) {
            return parent::allowEdit($data, $key);
        } else {
            return $allow;
        }
    }
    
    /**
     * Method to run batch operations.
     *
     * @param   object  $model  The model.
     *
     * @return  boolean     True if successful, false otherwise and internal error is set.
     *
     */
    public function batch($model = null)
    {
        Session::checkToken() or jexit(Text::_('JINVALID_TOKEN'));

        // Set the model
        $model = $this->getModel('license');

        // Preset the redirect
        $this->setRedirect('index.php?option=com_jdownloads&view=licenses');

        return parent::batch($model);
    }    
    
}
?>    