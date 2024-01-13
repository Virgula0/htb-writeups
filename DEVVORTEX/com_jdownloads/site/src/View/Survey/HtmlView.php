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
 
namespace JDownloads\Component\JDownloads\Site\View\Survey;

\defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Application\ApplicationHelper;
use Joomla\CMS\Event\AbstractEvent;
use Joomla\Event\Event;
use Joomla\CMS\Filesystem\Path;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\MVC\View\GenericDataException;
use Joomla\CMS\Pagination\Pagination;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Helper\TagsHelper;
use Joomla\CMS\Filesystem\File;

use JDownloads\Component\JDownloads\Site\Helper\JDHelper;

/**
 * HTML jDownloads View class to view a customer survey
 */
class HtmlView extends BaseHtmlView
{

	public function display($tpl = null)
	{
        
        // Add JavaScript Frameworks
        HTMLHelper::_('bootstrap.framework');

        // Load optional RTL Bootstrap CSS
        HTMLHelper::_('bootstrap.loadCss', true, $this->document->direction);

        $app        = Factory::getApplication();
        $params     = $app->getParams();
        
		$user		= Factory::getUser();
		$userId		= $user->get('id');
        
        // Get jD User group settings and limitations
        $this->user_rules = JDHelper::getUserRules();

        // Check the form view access.
        if (!$this->user_rules->view_inquiry_form) {
            $app->enqueueMessage( Text::_('JERROR_ALERTNOAUTHOR'), 'warning');
            return;
        }        
        
        // We must have at min a single field in the form
        if ($this->user_rules->form_fieldset == '' || $this->user_rules->form_fieldset == '{"0":""}' ) {
            $app->enqueueMessage( Text::_('JERROR_ALERTNOAUTHOR'), 'warning');
            return;
        }
        
        // Get data from the model
        $this->state  = $this->get('State');
        $this->item   = $this->get('Item');
        $this->form   = $this->get('Form');
        
        if ($this->item) {
            if (!$user->guest){
                $this->form->setFieldAttribute( 'name', 'default', htmlspecialchars($user->name, ENT_COMPAT, 'UTF-8'));
                $this->form->setFieldAttribute( 'name', 'readonly', 'true');
                $this->form->setFieldAttribute( 'name', 'class', 'readonly');
                $this->form->setFieldAttribute( 'email', 'default', htmlspecialchars($user->email, ENT_COMPAT, 'UTF-8'));
                $this->form->setFieldAttribute( 'email', 'readonly', 'true');
                $this->form->setFieldAttribute( 'email', 'class', 'readonly');
            }
            
            if ($this->user_rules->must_form_fill_out){
                // change all fields to 'required'
                $this->form->setFieldAttribute( 'name', 'required', 'true');                    
                $this->form->setFieldAttribute( 'company', 'required', 'true');                    
                $this->form->setFieldAttribute( 'country', 'required', 'true');                    
                $this->form->setFieldAttribute( 'address', 'required', 'true');                    
                $this->form->setFieldAttribute( 'email', 'required', 'true');                    
            }
        } else {
            $app->enqueueMessage( Text::_('COM_JDOWNLOADS_DOWNLOAD_NOT_FOUND'), 'warning');
            return false;
        }

        // Get the category title
        if ($this->item->catid == 1){
            $this->item->category_title = Text::_('COM_JDOWNLOADS_SELECT_UNCATEGORISED');
        } else {
            $cat = JDHelper::getSingleCategory($this->item->catid);
            $this->item->category_title = $cat->title ?? '';
        }
        // do it in the form
        $this->form->setFieldAttribute( 'cat_title', 'default', htmlspecialchars($this->item->category_title, ENT_COMPAT, 'UTF-8'));
        
        $this->state	= $this->get('State');
		$this->user		= $user;

        // Check for errors.
        if (count($errors = $this->get('Errors')))
        {
            throw new GenericDataException(implode("\n", $errors), 500);
        }
        
        // add all needed cripts and css files
        $document = Factory::getDocument();
        $document->addScript(Uri::base().'components/com_jdownloads/assets/js/jdownloads.js');
		
        $document->addScriptDeclaration('var live_site = "'.Uri::base().'";');
        $document->addScriptDeclaration('function openWindow (url) {
                fenster = window.open(url, "_blank", "width=550, height=480, STATUS=YES, DIRECTORIES=NO, MENUBAR=NO, SCROLLBARS=YES, RESIZABLE=NO");
                fenster.focus();
                }');

        if ($params->get('load_frontend_css')){

        	$document->addStyleSheet( Uri::base()."components/com_jdownloads/assets/css/jdownloads_fe.css", "text/css", null, array() );
			$currentLanguage = Factory::getLanguage();
            $isRTL = $currentLanguage->get('rtl');
            if ($isRTL) {
                $document->addStyleSheet( Uri::base()."components/com_jdownloads/assets/css/jdownloads_fe_rtl.css", "text/css", null, array() );
             }
        } else {
            if ($params->get('own_css_file')){
                $own_css_path = JPATH_ROOT.'/components/com_jdownloads/assets/css/'.$params->get('own_css_file');
                if (file::exists($own_css_path)){
                    $document->addStyleSheet( Uri::base()."components/com_jdownloads/assets/css/".$params->get('own_css_file'), "text/css", null, array() );
                }
            }
        } 
        
        $custom_css_path = JPATH_ROOT.'/components/com_jdownloads/assets/css/jdownloads_custom.css';
        if (file::exists($custom_css_path)){
            $document->addStyleSheet( Uri::base()."components/com_jdownloads/assets/css/jdownloads_custom.css", 'text/css', null, array() );                
        }           
        
		$this->_prepareDocument();
		parent::display($tpl);
	}

	/**
	 * Prepares the document
	 */
	protected function _prepareDocument()
	{
        $app      = Factory::getApplication();
        $params   = $app->getParams();
        
		$title = null;

		// Check for empty title and add site name if param is set
		if (empty($title)) {
			$title = $app->getCfg('sitename');
		}
		elseif ($app->getCfg('sitename_pagetitles', 0) == 1) {
			$title = Text::sprintf('JPAGETITLE', $app->getCfg('sitename'), $title);
		}
		elseif ($app->getCfg('sitename_pagetitles', 0) == 2) {
			$title = Text::sprintf('JPAGETITLE', $title, $app->getCfg('sitename'));
		}
		$this->document->setTitle($title);

        if ($params->get('robots')){
            // use settings from jD-config
            $this->document->setMetadata('robots', $params->get('robots'));    
        } else {
            // is not defined in item or jd-config - so we use the global config setting
            $this->document->setMetadata( 'robots' , $app->getCfg('robots' ));
        }
    }
}
