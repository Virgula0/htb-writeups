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
class TemplateController extends FormController
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
        $this->registerTask( 'delete',          'delete' );
        $this->registerTask( 'create',          'add' );
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
        $user     = Factory::getApplication()->getIdentity();
        $allow    = null;
        $allow    = $user->authorise('core.edit', 'com_jdownloads');
        
        if ($allow === null) {
            return parent::allowEdit($data, $key);
        } else {
            return $allow;
        }
    }
    
}
?>    