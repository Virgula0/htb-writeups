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

use Joomla\CMS\MVC\Controller\AdminController;
use Joomla\CMS\Response\JsonResponse;
use Joomla\Utilities\ArrayHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;
use Joomla\CMS\Session\Session;
use Joomla\CMS\Application\CMSApplication;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\MVC\Factory\MVCFactoryInterface;
use Joomla\Input\Input;

BaseDatabaseModel::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_jdownloads/src/Model');

/**
 * Jdownloads categories Controller
 *
 */
class CategoriesController extends AdminController
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
     * Proxy for getModel.
     */
    public function getModel($name = 'Category', $prefix = 'Administrator', $config = array('ignore_request' => true))
    {
        $model = BaseDatabaseModel::getInstance('Category', 'jdownloads'); 
        return $model;
    }
    
    /**
     * Removes an item.
     *
     * @return  void
     *
     * @since   1.6
     */
    public function delete()
    {
        // Check for request forgeries
        $this->checkToken();

        // Get items to remove from the request.
        $cid = $this->input->get('cid', array(), 'array');

        if (!\is_array($cid) || \count($cid) < 1)
        {
            $this->app->getLogger()->warning(Text::_($this->text_prefix . '_NO_ITEM_SELECTED'), array('category' => 'jerror'));
        }
        else
        {
            // Get the model.
            $model = BaseDatabaseModel::getInstance('Category', 'jdownloads');

            // Make sure the item ids are integers
            $cid = ArrayHelper::toInteger($cid);

            // Remove the items.
            if ($model->delete($cid))
            {
                $this->setMessage(Text::plural($this->text_prefix . '_N_ITEMS_DELETED', \count($cid)));
            }
            else
            {
                $this->setMessage($model->getError(), 'error');
            }

            // Invoke the postDelete method to allow for the child class to access the model.
            $this->postDeleteHook($model, $cid);
        }

        $this->setRedirect(
            Route::_(
                'index.php?option=' . $this->option . '&view=' . $this->view_list
                . $this->getRedirectToListAppend(), false
            )
        );
    }
    
    
    /**
     * Save the manual order inputs from the categories list page.
     *
     * @return    void
    */
    public function saveorder()
    {
        Session::checkToken() or jexit(Text::_('JINVALID_TOKEN'));

        // Get the arrays from the Request
        $order = $this->input->post->get('order', null, 'array');
        $originalOrder = explode(',', $this->input->getString('original_order_values'));

        // Make sure something has changed
        if (!($order === $originalOrder)) {
            parent::saveorder();
        } else {
            // Nothing to reorder
            $this->setRedirect(ROUTE::_('index.php?option='.$this->option.'&view='.$this->view_list, false));
            return true;
        }
    } 
    
    /**
     * Rebuild the nested set tree.
     *
     * @return    bool    False on failure or error, true on success.
     */
    public function rebuild()
    {
        Session::checkToken() or jexit(Text::_('JINVALID_TOKEN'));

        $this->setRedirect(ROUTE::_('index.php?option=com_jdownloads&view=categories', false));

        $model = BaseDatabaseModel::getInstance('Category', 'jdownloads');

        if ($model->rebuild()) {
            // Rebuild succeeded.
            $this->setMessage(Text::_('COM_JDOWNLOADS_REBUILD_CATS_SUCCESS'));
            return true;
        } else {
            // Rebuild failed.
            $this->setMessage(Text::_('COM_JDOWNLOADS_REBUILD_CATS_FAILURE'));
            return false;
        }
    }       
    
    
    /**
     * Method to publish a list of items
     *
     * @return  void
     */
    public function publish()
    {
        // Check for request forgeries
        Session::checkToken() or die(Text::_('JINVALID_TOKEN'));

        // Get items to publish from the request.
        $cid = $this->input->get('cid', array(), 'array');
        
		$data = array('publish' => 1, 'unpublish' => 0);
        $task = $this->getTask();
        $value = ArrayHelper::getValue($data, $task, 0, 'int');

        if (empty($cid))
        {
            Factory::getApplication()->enqueueMessage( Text::_('COM_JDOWNLOADS_NO_ITEM_SELECTED'), 'warning');
        }
        else
        {
            // Get the model.
            $model = BaseDatabaseModel::getInstance('Category', 'jdownloads');
            
            // Make sure the item ids are integers
            ArrayHelper::toInteger($cid);

            // Publish the items.
            try
            {
                $model->publish($cid, $value);
                $errors = $model->getErrors();

                if ($value == 1)
                {
                    if ($errors)
                    {
                        $app = Factory::getApplication();
                        $app->enqueueMessage(Text::plural('COM_JDOWNLOADS_N_ITEMS_FAILED_PUBLISHING', count($cid)), 'error');
                    }
                    else
                    {
                        $ntext = 'COM_JDOWNLOADS_N_ITEMS_PUBLISHED';
                    }
                }
                elseif ($value == 0)
                {
                    $ntext = 'COM_JDOWNLOADS_N_ITEMS_UNPUBLISHED';
                }
                $this->setMessage(Text::plural($ntext, count($cid)));
            }
            catch (Exception $e)
            {
                $this->setMessage($e->getMessage(), 'error');
            }            
        }     
        $this->setRedirect(ROUTE::_('index.php?option=com_jdownloads&view=categories', false));
    }        
 	
}
?>
