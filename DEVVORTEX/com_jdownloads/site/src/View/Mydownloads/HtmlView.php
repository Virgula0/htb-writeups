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
 
namespace JDownloads\Component\JDownloads\Site\View\Mydownloads;

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
use Joomla\CMS\Plugin\PluginHelper;
use JLoader;

use JDownloads\Component\JDownloads\Site\Helper\JDHelper;
use JDownloads\Component\JDownloads\Site\Helper\CategoriesHelper;
use JDownloads\Component\JDownloads\Site\Helper\AssociationHelper;
use JDownloads\Component\JDownloads\Administrator\Helper\JDownloadsAssociationsHelper;

/**
 * View class for a list of downloads
 */
class HtmlView extends BaseHtmlView
{
    protected $state = null;
    protected $item = null;
    protected $items = null;
    protected $pagination = null;

	/**
	 * Display the view
     * @return    mixed    False on error, null otherwise.
	 */
	public function display($tpl = null)
	{
        
        $app    = Factory::getApplication();
        $params = $app->getParams();
        $user   = Factory::getUser();
        
        $document = Factory::getDocument();
        $app      = Factory::getApplication();
        
        if ($user->guest){
            $menus = $app->getMenu();
            $menu  = $menus->getActive();

            if ($menu){
                $redirect_url = $menu->link.'&Itemid='.$menu->id;
                $redirect_url = urlencode(base64_encode($redirect_url));
                $redirect_url = '&return='.$redirect_url;
                $login_url    = 'index.php?option=com_users&view=login';
                $final_url    = $login_url.$redirect_url;
                $app->redirect($final_url, 403);
            } else {
                $app->enqueueMessage( Text::_('COM_JDOWNLOADS_MY_DOWNLOADS_NOT_FOUND'), 'notice');
                return false;            
            }
        }
        
        // get jD User group settings and limitations
        $this->user_rules = JDHelper::getUserRules();
        
        // Get the needed layout data - type = 2 for a 'files' layout
        $this->layout = JDHelper::getLayout(2);
        
        // Add JavaScript Frameworks
        HTMLHelper::_('bootstrap.framework');

        // Add jQuery Framework
        HTMLHelper::_('jquery.framework');

        // Load optional RTL Bootstrap CSS
        if ($this->layout->uses_bootstrap){
            HTMLHelper::_('bootstrap.loadCss', true, $this->document->direction);
        }

        // Load optional w3css framework
        if ($this->layout->uses_w3css){
            $w3_css_path = JPATH_ROOT.'/components/com_jdownloads/assets/css/w3.css';
            if (File::exists($w3_css_path)){
                $document->addStyleSheet( Uri::base()."components/com_jdownloads/assets/css/w3.css", 'text/css', null, array() );                
            }
        }

        // Initialise variables
        $state        = $this->get('State');
        $items        = $this->get('Items');
        $pagination   = $this->get('Pagination');
        
        if (!$items){
            $app->enqueueMessage( Text::_('COM_JDOWNLOADS_MY_DOWNLOADS_NOT_FOUND'), 'notice');
            return false;            
        }        
        
        // upload icon handling
        $this->view_upload_button = false;
        
        if ($this->user_rules->uploads_view_upload_icon){
            // we must here check whether the user has the permissions to create new downloads 
            // this can be defined in the components permissions but also in any category
            // but the upload icon is only viewed when in the user groups settings is also activated the: 'display add/upload icon' option
                
            // 1. check the component permissions
            if (!$user->authorise('core.create', 'com_jdownloads')){
                // 2. not global permissions so we must check now every category (for a lot of categories can this be very slow)
                $this->authorised_cats = JDHelper::getAuthorisedJDCategories('core.create', $user);
                if (count($this->authorised_cats) > 0){
                    $this->view_upload_button = true;
                }
            } else {
                $this->view_upload_button = true;
            }        
        }
                             
        $this->ipad_user = false;

        // check whether we have an ipad/iphone user for flowplayer aso...
        if ((bool) strpos($_SERVER['HTTP_USER_AGENT'], 'iPad') || (bool) strpos($_SERVER['HTTP_USER_AGENT'], 'iPhone')){        
            $this->ipad_user = true;
        }        

                // Check for errors.
        if (count($errors = $this->get('Errors')))
        {
            throw new GenericDataException(implode("\n", $errors), 500);
        }

        if ($items === false) {
            return $app->enqueueMessage( Text::_('JGLOBAL_CATEGORY_NOT_FOUND'), 'error');
        }

        // add all needed cripts and css files
        
        $document->addScript(Uri::base().'components/com_jdownloads/assets/js/jdownloads.js');
        
        if ($params->get('view_ratings')){
            $document->addScript(Uri::base().'components/com_jdownloads/assets/rating/js/ajaxvote.js');
        }
        
        // loadscript for flowplayer
        if ($params->get('flowplayer_use')){
            $document->addScript(Uri::base().'components/com_jdownloads/assets/flowplayer/flowplayer-3.2.12.min.js');
            // load also the ipad script when required
             if ($this->ipad_user){
                $document->addScript(Uri::base().'components/com_jdownloads/assets/flowplayer/flowplayer.ipad-3.2.12.min.js');
            }
        }       
        
        if ($params->get('use_lightbox_function')){
            $document->addScript(Uri::base().'components/com_jdownloads/assets/lightbox/src/js/lightbox.js');
            $document->addStyleSheet( Uri::base()."components/com_jdownloads/assets/lightbox/src/css/lightbox.css", 'text/css', null, array() );
        }
            
        $document->addScriptDeclaration('var live_site = "'.Uri::base().'";');
        $document->addScriptDeclaration('function openWindow (url) {
                fenster = window.open(url, "_blank", "width=550, height=480, STATUS=YES, DIRECTORIES=NO, MENUBAR=NO, SCROLLBARS=YES, RESIZABLE=NO");
                fenster.focus();
                }');

        $document->addStyleSheet( Uri::base()."components/com_jdownloads/assets/css/jdownloads_buttons.css", "text/css", null, array() ); 

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
                if (File::exists($own_css_path)){
                    $document->addStyleSheet( Uri::base()."components/com_jdownloads/assets/css/".$params->get('own_css_file'), "text/css", null, array() );
                }
            }
        } 
                
        if ($params->get('view_ratings')){
            $document->addStyleSheet( Uri::base()."components/com_jdownloads/assets/rating/css/ajaxvote.css", "text/css", null, array() );         
        }

        $custom_css_path = JPATH_ROOT.'/components/com_jdownloads/assets/css/jdownloads_custom.css';
        if (File::exists($custom_css_path)){
            $document->addStyleSheet( Uri::base()."components/com_jdownloads/assets/css/jdownloads_custom.css", 'text/css', null, array() );                
        }   
        
        $this->jd_image_path = JPATH_ROOT  . '/images/jdownloads';        
        
        $params = &$state->params;
                
        // Compute the download slugs and prepare text (runs content plugins).
        for ($i = 0, $n = count($items); $i < $n; $i++)
        {
            $item = &$items[$i];
            
            $item->tags = new TagsHelper;
            $item->tags->getItemTags('com_jdownloads.download', $item->id);
            
            $item->slug = $item->alias ? ($item->id . ':' . $item->alias) : $item->id;

            // No link for ROOT category
            if ($item->parent_alias == 'root') {
                $item->parent_slug = null;
            }


			// Process the content plugins.
            PluginHelper::importPlugin('content');

            // required for some content plugins which needed a field named id and text
            $item->text = $item->description;
            
            // This is the event to get the content plugins the possibility to modify the Download data. Also required to get Joomla Fields when used in jD.
            if ($params->get('activate_general_plugin_support')) {
                $app->triggerEvent('onContentPrepare', ['com_jdownloads.download', &$item, &$params, 0]);
            }

            $item->description = $item->text; 
            
            $item->event = new \stdClass();
            $results = $app->triggerEvent('onContentAfterTitle', array('com_jdownloads.download', &$item, &$item->params, 0));
            $item->event->afterDisplayTitle = trim(implode("\n", $results));

            $results = $app->triggerEvent('onContentBeforeDisplay', array('com_jdownloads.download', &$item, &$item->params, 0));
            $item->event->beforeDisplayContent = trim(implode("\n", $results));

            $results = $app->triggerEvent('onContentAfterDisplay', array('com_jdownloads.download', &$item, &$item->params, 0));
            $item->event->afterDisplayContent = trim(implode("\n", $results));
            
        }        
        
        //Escape strings for HTML output
        $this->pageclass_sfx = htmlspecialchars($params->get('pageclass_sfx') ?? '');

        $this->maxLevelcat = $params->get('maxLevelcat', -1);
        
        $this->state            = &$state;        
        $this->params           = &$params;
        $this->items            = &$items;
        $this->pagination       = &$pagination;

        $this->_prepareDocument();

        parent::display($tpl);
    }

    /**
     * Prepares the document
     */
    protected function _prepareDocument()
    {
        $app    = Factory::getApplication();
        $menus    = $app->getMenu();
        $title    = null;

        // Because the application sets a default page title,
        // we need to get it from the menu item itself
        $menu = $menus->getActive();
        
        if ($menu) {
            $this->params->def('page_heading', $this->params->get('page_title', $menu->title));
        } else {
            $this->params->def('page_heading', Text::_('COM_JDOWNLOADS_DOWNLOADS'));
        }        
        
        $title = $this->params->get('page_title', '');
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

        if ($this->params->get('menu-meta_description'))
        {
            $this->document->setDescription($this->params->get('menu-meta_description'));
        }

        if ($this->params->get('menu-meta_keywords'))
        {
            $this->document->setMetadata('keywords', $this->params->get('menu-meta_keywords'));
        }

        if ($this->params->get('robots'))
        {
            $this->document->setMetadata('robots', $this->params->get('robots'));
        }
    }
}
