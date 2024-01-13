<?php
/**
 * @package jDownloads
 * @version 4.0
 * @copyright (C) 2007 - 2022 Arno Betz - www.jdownloads.com
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
use Joomla\CMS\MVC\Controller\FormController;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Session\Session;
use Joomla\CMS\Filesystem\File;
use Joomla\CMS\Filesystem\Folder;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;
use Joomla\CMS\Application\CMSApplication;
use Joomla\CMS\MVC\Factory\MVCFactoryInterface;
use Joomla\Input\Input;
use Joomla\Registry\Registry;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;

use JDownloads\Component\JDownloads\Administrator\Helper\JDownloadsHelper;

/**
 * License controller class.
 *
 * @package        Joomla.Administrator
 * @subpackage    com_weblinks
 * @since        1.6
 */
class CategoryController extends FormController
{
  
    /**
     * Constructor
     *
     */
    public function __construct($config = array(), MVCFactoryInterface $factory = null, CMSApplication $app = null, Input $input = null)
    {
        parent::__construct($config, $factory, $app, $input);

        // Register extra task
        $this->registerTask( 'apply', 'save' );
        $this->registerTask( 'add',   'edit' );
 
        if (empty($this->extension))
        {
            $this->extension = $this->input->get('extension', 'com_jdownloads');
        }
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
        $app = Factory::getApplication();
        $user = $app->getIdentity();
        
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
        
        $app = Factory::getApplication();
        $user = $app->getIdentity();
        $allow        = null;
        $allow    = $user->authorise('core.edit', 'com_jdownloads');
        if ($allow === null) {
            return parent::allowEdit($data, $key);
        } else {
            return $allow;
        }
    }
    
    // copy or move a category (with all subcategories) to the same (copy) or a other position (move)
    public function batch($model = null) 
    {
        Session::checkToken() or jexit(Text::_('JINVALID_TOKEN'));
        $model    = $this->getModel('category', '', array());
        
        // Preset the redirect
        $this->setRedirect(ROUTE::_('index.php?option=com_jdownloads&view=categories'.$this->getRedirectToListAppend(), false));
        
        return parent::batch($model);
    } 
    
}
?>    