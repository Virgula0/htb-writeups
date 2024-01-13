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
use Joomla\Input\Input;
use Joomla\Utilities\ArrayHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Factory;
use Joomla\CMS\Response\JsonResponse;
use Joomla\CMS\MVC\Controller\AdminController;
use Joomla\CMS\MVC\Factory\MVCFactoryInterface;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;
use Joomla\CMS\Session\Session;
use Joomla\CMS\Component\ComponentHelper;

use JDownloads\Component\JDownloads\Administrator\Model;
use JDownloads\Component\JDownloads\Site\Helper\JDHelper;

/**
 * jDownloads list downloads controller class.
 *
 */
class DownloadsController extends AdminController
{
    /**
     * Constructor.
     *
     * @param   array                $config   An optional associative array of configuration settings.
     * Recognized key values include 'name', 'default_task', 'model_path', and 'view_path' (this list is not meant to be comprehensive).
     * @param   MVCFactoryInterface  $factory  The factory.
     * @param   CMSApplication       $app      The JApplication for the dispatcher
     * @param   Input                $input    Input
     *
     * @since   3.0
     */
	public function __construct($config = array(), MVCFactoryInterface $factory = null, $app = null, $input = null)
	{
		parent::__construct($config, $factory, $app, $input);
        
        // Register Extra task
        $this->registerTask('delete',    'delete');
        $this->registerTask('unfeatured', 'featured');
            
	}
                                                
    /**
     * Proxy for getModel.
     */
    public function getModel($name = 'Download', $prefix = 'Administrator', $config = array('ignore_request' => true))
    {
        $model = parent::getModel($name, $prefix, $config);
        return $model;
    } 
	
    
    /**
    * Removes an download item in db table.
    *
    * @return  void
    *
    */    
    public function delete()
    {
        $jinput = Factory::getApplication()->input;
		$cid = $jinput->get('cid', 0, 'array');
		$error          = '';
        $message        = '';
        
        // run the model methode
        $model = $this->getModel();
        
        if(!$model->delete($cid))
        {
            $msg = Text::_( 'COM_JDOWNLOADS_ERROR_RESULT_MSG' );
            $error = 'error';
        } else {                             
            $this->setMessage(Text::plural($this->text_prefix . '_N_ITEMS_DELETED', count($cid)));
        }
        $this->setRedirect( 'index.php?option=com_jdownloads&view=downloads', $msg, $error);       
    }
    
   /**
    * Method to publish a list of items
    *
    * @return  void
    *
    */    
    public function publish()
    {
        $params = ComponentHelper::getParams('com_jdownloads');
        
        // Check for request forgeries
        Session::checkToken() or die(Text::_('JINVALID_TOKEN'));
                
        // Get items to publish from the request.
        $cid = Factory::getApplication()->input->get('cid', array(), 'array');
        $data = array('publish' => 1, 'unpublish' => 0);
        $task = $this->getTask();
        $state = ArrayHelper::getValue($data, $task, 0, 'int');        
        
        if (empty($cid)){
            Log::add(Text::_('JGLOBAL_NO_ITEM_SELECTED'), Log::WARNING, 'jerror');
            $this->setRedirect(ROUTE::_('index.php?option=com_jdownloads&view=downloads', false));
        } else {
            if ($state == 1 && $params->get('use_alphauserpoints')){
                // load the model
                $model = $this->getModel();
                foreach ($cid as $id){
                    // load the items data
                    $item = $model->getItem($id);
                    // add the AUP points
                    JDHelper::setAUPPointsUploads($item->submitted_by, $item->title);
                }
            }
            parent::publish();
        }        
    } 
    
    /**
     * Method to toggle the featured setting of a list of Downloads.
     *
     * @return  void
     */
    public function featured()
    {
        // Check for request forgeries
        Session::checkToken() or jexit(Text::_('JINVALID_TOKEN'));

        $user   = Factory::getApplication()->getIdentity();
        $ids    = $this->input->get('cid', array(), 'array');
        
        $task   = $this->getTask();
        $values = array('featured' => 1, 'unfeatured' => 0);
        $value  = ArrayHelper::getValue($values, $task, 0, 'int');

        // Access checks.
        foreach ($ids as $i => $id)
        {
            if (!$user->authorise('core.edit.state', 'com_jdownloads.download.' . (int) $id))
            {
                // Prune items that you can't change.
                unset($ids[$i]);
                Factory::getApplication()->enqueueMessage( Text::_('JLIB_APPLICATION_ERROR_EDITSTATE_NOT_PERMITTED'), 'notice');
            }
        }

        if (empty($ids))
        {
            Factory::getApplication()->enqueueMessage( Text::_('JERROR_NO_ITEMS_SELECTED'), 'warning');
        }
        else
        {
            // Get the model.
            $model = $this->getModel();

            // Publish the items.
            if (!$model->featured($ids, $value))
            {
                Factory::getApplication()->enqueueMessage( $model->getError(), 'warning');
            }

            if ($value == 1)
            {
                $message = Text::plural('COM_JDOWNLOADS_N_ITEMS_FEATURED', count($ids));
            }
            else
            {
                $message = Text::plural('COM_JDOWNLOADS_N_ITEMS_UNFEATURED', count($ids));
            }
        }

        $this->setRedirect(ROUTE::_('index.php?option=com_jdownloads&view=downloads', false), $message);
    }

}
?>