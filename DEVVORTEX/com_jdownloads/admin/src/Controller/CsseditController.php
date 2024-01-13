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

/**
 * jDownloads cssedit Controller
 *
 */
class CsseditController extends AdminController
{
	/**
	 * Constructor
	 *
	 */
	    public function __construct($config = array())
    {
        parent::__construct($config);
        
        $jinput = Factory::getApplication()->input;
        
        // Access check.
        $app = Factory::getApplication();
        if (!$app->getIdentity()->authorise('core.admin', $jinput->get('jdownloads'))) {
            return Factory::getApplication()->enqueueMessage( Text::_('JERROR_ALERTNOAUTHOR'), 'warning');
        }
	}

	/**
	 * logic to cancel the edit page
	 *
	 */
	public function cancel()
    {
        // Check for request forgeries.
        Session::checkToken() or jexit(Text::_('JINVALID_TOKEN'));                
        $app = Factory::getApplication();
        $this->setRedirect('index.php?option=com_jdownloads&view=layouts');
    }
    
    /**
     * logic to save the css file
     *
     */
    public function save()
    {
       // Check for request forgeries.
       Session::checkToken() or jexit(Text::_('JINVALID_TOKEN'));                
       $app = Factory::getApplication();
       $css_file = JPATH_SITE.'/components/com_jdownloads/assets/css/jdownloads_fe.css';
       $css_text =  ArrayHelper::getValue($_POST,'cssfile', '');
       $css_file2 = JPATH_SITE.'/components/com_jdownloads/assets/css/jdownloads_buttons.css';
       $css_text2 = ArrayHelper::getValue($_POST,'cssfile2', '');
       $css_file3 = JPATH_SITE.'/components/com_jdownloads/assets/css/jdownloads_custom.css';
       $css_text3 = ArrayHelper::getValue($_POST,'cssfile3', '');
       clearstatcache();

       if (!is_writable($css_file) || !is_writable($css_file2) || !is_writable($css_file3)) {
            $this->setRedirect("index.php?option=com_jdownloads&view=layouts", Text::_('COM_JDOWNLOADS_BACKEND_EDIT_CSS_WRITE_STATUS_TEXT').Text::_('COM_JDOWNLOADS_BACKEND_EDIT_LANG_CSS_FILE_WRITABLE_NO') );
      }

      if ($fp = fopen( $css_file, "w")) {
        fputs($fp,stripslashes($css_text));
        fclose($fp);
      }        

      if ($fp = fopen( $css_file2, "w")) {
        fputs($fp,stripslashes($css_text2));
        fclose($fp);
      }        

      if ($fp = fopen( $css_file3, "w")) {
        fputs($fp,stripslashes($css_text3));
        fclose($fp);
            $this->setRedirect("index.php?option=com_jdownloads&view=layouts", Text::_('COM_JDOWNLOADS_BACKEND_EDIT_CSS_SAVED'));
      }        
      
        
    }
	
}
?>