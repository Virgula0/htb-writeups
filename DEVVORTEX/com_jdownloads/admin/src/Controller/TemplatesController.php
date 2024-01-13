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
use Joomla\CMS\MVC\Controller\AdminController;
use Joomla\CMS\MVC\Factory\MVCFactoryInterface;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;
use Joomla\CMS\Table\Table;
use Joomla\CMS\Session\Session;

use JDownloads\Component\JDownloads\Administrator\Helper\JDownloadsHelper;
use JDownloads\Component\JDownloads\Administrator\Model\TemplatesModel;


/**
 * Jdownloads list controller class.
 *
 * @package Jdownloads
 */
class TemplatesController extends AdminController
{
	/**
	 * Constructor
	 */
	function __construct()
	{
		parent::__construct();
        
        // Register Extra task 
        $this->registerTask('activate', 'activate');
	}

                                                
    /**
     * Proxy for getModel.
     */
    public function getModel($name = 'Templates', $prefix = 'Administrator', $config = array('ignore_request' => true))
    {
        BaseDatabaseModel::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_jdownloads/src/Model');
        $model = BaseDatabaseModel::getInstance('Templates', 'jdownloads');
        return $model;
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
     * logic to activate a selected layout
     *
     */
    public function activate() 
    {
        // get layout type
        $session        = Factory::getSession();
        $jd_tmpl_type   = (int) $session->get( 'jd_tmpl_type', '' );
        $error          = '';
        
        // run the model methode
        $model = $this->getModel();
        
        if(!$model->activate($jd_tmpl_type)) {
            $msg = Text::_( 'COM_JDOWNLOADS_BACKEND_TEMPEDIT_ACTIVE_ERROR' );
            $error = 'error';
        } else {                             
            $msg = Text::_( 'COM_JDOWNLOADS_BACKEND_TEMPEDIT_ACTIVE' );
        }
        $this->setRedirect( 'index.php?option=com_jdownloads&view=templates&types='.$jd_tmpl_type , $msg, $error);
    }
    
    /**
     * logic to export a selected layout
     *
     */    
    public function export() 
    {    
        $app = Factory::getApplication();
        $jinput = Factory::getApplication()->input;
        
        $id = $this->input->get('cid', array(), 'array');
        if (count($id) != 1){
            // to much layouts selected
            $app->enqueueMessage(Text::_('COM_JDOWNLOADS_LAYOUTS_EXPORT_MSG_COUNT_ERROR'), 'error');
            $app->redirect(ROUTE::_('index.php?option=com_jdownloads&view=templates&type='.(int)$jinput->get('type'), false));
        }
        
        $id = $id[0];
        
        $jd_version = JDownloadsHelper::getjDownloadsVersion();

        $db = Factory::getDBO();
        $prefix = JDownloadsHelper::getCorrectDBPrefix();
        Table::addIncludePath(JPATH_ADMINISTRATOR.'/components/com_jdownloads/src/Table');
      
        // load layout data
        $object = Table::getInstance('TemplateTable', 'JDownloads\\Component\\JDownloads\\Administrator\\Table\\');
        $result = $object->load($id);

        if (!$result){
            // Abort
            $app->enqueueMessage(Text::_('COM_JDOWNLOADS_LAYOUTS_EXPORT_MSG_ERROR'), 'error');
            $app->redirect(ROUTE::_('index.php?option=com_jdownloads&view=templates&type='.(int)$jinput->get('type'), false));
        } else {
            
            switch ($object->template_typ) {
                    case '1':
                        $object->template_typ = 'categories';
                        break; 
                    case '4':
                        $object->template_typ = 'category';                    
                        break;
                    case '2':
                        $object->template_typ = 'downloads';
                        break;
                    case '5':
                        $object->template_typ = 'details';                                        
                        break;
                    case '3':
                        $object->template_typ = 'summary';                                        
                        break;
                    case '7':
                        $object->template_typ = 'search';                                        
                        break;
                        
                    case '8':
                        $object->template_typ = 'subcats';                                        
                        break;                        
            }            
        
            $file    = '<?xml version="1.0" encoding="utf-8"?>'."\r\n";
            $file   .= '<layout>'."\r\n";
            $file   .= '<!-- This file is a jDownloads layout file -->'."\r\n\r\n";
            $file   .= '<!-- Optional: -->'."\r\n";
            $file   .= '<creation_date></creation_date>'."\r\n\r\n";
            $file   .= '<!-- Optional: -->'."\r\n";
            $file   .= '<author></author>'."\r\n\r\n";
            $file   .= '<!-- The installed version of jDownloads should be at or later than the version shown below, as otherwise this layout may not work properly. The value is set automatically when a layout is exported (optional) -->'."\r\n";
            $file   .= '<targetjdownloads>'.$jd_version.'</targetjdownloads>'."\r\n\r\n";
            $file   .= '<!-- This is the name of the layout (required) -->'."\r\n";
            $file   .= '<template_name>'.$object->template_name.'</template_name>'."\r\n\r\n";
            $file   .= '<!-- Must be one of: categories, category, files, details, summary, search (required) -->'."\r\n";
            $file   .= '<template_typ>'.$object->template_typ.'</template_typ>'."\r\n\r\n";
            $file   .= '<!-- Start here with header, subheader and footer area -->'."\r\n";
            $file   .= '<!-- Header Area Layout -->'."\r\n";
            $file   .= '<template_header_text>'."\r\n".htmlentities($object->template_header_text)."\r\n".'</template_header_text>'."\r\n\r\n";
            $file   .= '<!-- Sub Header Area Layout -->'."\r\n";
            $file   .= '<template_subheader_text>'."\r\n".htmlentities($object->template_subheader_text)."\r\n".'</template_subheader_text>'."\r\n\r\n";
            $file   .= '<!-- Footer Area Layout -->'."\r\n";
            $file   .= '<template_footer_text>'."\r\n".htmlentities($object->template_footer_text)."\r\n".'</template_footer_text>'."\r\n\r\n";
            $file   .= '<!-- Start here with main layout part -->'."\r\n";
            $file   .= '<!-- Use Before Layout -->'."\r\n";            
            $file   .= '<template_before_text>'."\r\n".htmlentities($object->template_before_text)."\r\n".'</template_before_text>'."\r\n\r\n";
            $file   .= '<!-- The Main Layout Field -->'."\r\n";
            $file   .= '<template_text>'."\r\n".htmlentities($object->template_text)."\r\n".'</template_text>'."\r\n\r\n";
            $file   .= '<!-- Use After Layout -->'."\r\n";
            $file   .= '<template_after_text>'."\r\n".htmlentities($object->template_after_text)."\r\n".'</template_after_text>'."\r\n\r\n";
            $file   .= '<!-- Should be always 0 -->'."\r\n";
            $file   .= '<template_active>0</template_active>'."\r\n\r\n";
            $file   .= '<!-- Use 1 for default layout, normal value is 0. Default layouts cannot be deleted by the user. (required) -->'."\r\n";
            $file   .= '<template_locked>0</template_locked>'."\r\n\r\n";
            $file   .= '<!-- Layout description (optional) -->'."\r\n";
            $file   .= '<note>'."\r\n".htmlentities($object->note)."\r\n".'</note>'."\r\n\r\n";                
            $file   .= '<!-- Number of columns - only required for categories and Downloads layouts - default 1 -->'."\r\n";
            $file   .= '<cols>'.(int)$object->cols.'</cols>'."\r\n\r\n";
            $file   .= '<!-- Uses Bootstrap - default 0 - since v3.8 -->'."\r\n";
            $file   .= '<uses_bootstrap>'.(int)$object->uses_bootstrap.'</uses_bootstrap>'."\r\n\r\n";
            $file   .= '<!-- Uses W3.CSS - default 0 - since v3.8 -->'."\r\n";
            $file   .= '<uses_w3css>'.(int)$object->uses_w3css.'</uses_w3css>'."\r\n\r\n";
            $file   .= '<!-- Default = 1 - only usable in files layouts - use 0 when the layout has checkboxes for mass downloads -->'."\r\n";
            $file   .= '<checkbox_off>'.(int)$object->checkbox_off.'</checkbox_off>'."\r\n\r\n";                
            $file   .= '<!-- Default = 0 - should only be 1 for categories layouts when the layout shall be used to list sub categories - deprecated in 3.8 -->'."\r\n";
            $file   .= '<use_to_view_subcats>'.(int)$object->use_to_view_subcats.'</use_to_view_subcats>'."\r\n\r\n";                
            $file   .= '<!-- Default = 1 - only usable in files and details layouts - use 0 when the layout is to use mini symbols for some main data -->'."\r\n";
            $file   .= '<symbol_off>'.(int)$object->symbol_off.'</symbol_off>'."\r\n\r\n";                
            $file   .= '<!-- Use * for all languages (default) - Note: this language field is not used currently, it is a possible future use in layouts. -->'."\r\n";
            $file   .= '<language>'.$object->language.'</language>'."\r\n";
            $file   .= '</layout>';
            
            $filename = 'exported_layout_jd_'.$jd_version.'_'.str_replace(' ', '_', $object->template_name.'.xml');

            // Do clean up the buffer
            while (ob_get_level() > 0)
                @ob_end_clean();

            // SET HEADER TO OUTPUT DATA
            header ("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
            header ("Last-Modified: " . gmdate("D,d M Y H:i:s") . " GMT");
            header ("Cache-Control: no-store, no-cache, must-revalidate");
            header ('Cache-Control: post-check=0, pre-check=0', false );
            header ('Pragma: no-cache');
            header ('Content-type: text/xml');
            header ('Content-Disposition: attachment; filename="'.$filename.'"');

            print $file;
        }
        exit;
     }        
	
}
?>