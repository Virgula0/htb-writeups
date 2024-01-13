<?php

namespace JDownloads\Component\JDownloads\Administrator\Controller;

\defined('_JEXEC') or die;

use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Controller\BaseController;
use Joomla\CMS\Router\Route;

use JDownloads\Component\JDownloads\Administrator\Helper\JDownloadsHelper;
  
/**
 * General Controller of jDownloads component
 */
class DisplayController extends BaseController
{
    protected $default_view = 'jdownloads';
    
    /**
     * Method to display a view.
     *
     * @param    boolean            $cachable    If true, the view output will be cached
     * @param    array              $urlparams    An array of safe url parameters and their variable types, for valid values see {@link JFilterInput::clean()}.
     *
     * @return   BaseController     This object to support chaining.
     */
    public function display($cachable = false, $urlparams = array())
    {
        require_once JPATH_COMPONENT.'/src/Helper/JDownloadsHelper.php';
        require_once JPATH_COMPONENT.'/src/Helper/pluploadscript.php';
        
        $view   = $this->input->get('view', 'jdownloads');
        $layout = $this->input->get('layout', 'default');
        $id     = $this->input->getInt('id');
        
        // Check for edit form.
        if ($view == 'template' && $layout == 'edit' && !$this->checkEditId('com_jdownloads.edit.template', $id)) {
            // Somehow the person just went to the form - we don't allow that.
            if (!\count($this->app->getMessageQueue())){
                $this->setMessage(Text::sprintf('JLIB_APPLICATION_ERROR_UNHELD_ID', $id), 'error');
            }

            $this->setRedirect(Route::_('index.php?option=com_jdownloads&view=templates', false));

            return false;
            
        }  

        return parent::display();

    }
}
