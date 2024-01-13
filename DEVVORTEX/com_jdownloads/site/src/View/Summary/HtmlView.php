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
 
namespace JDownloads\Component\JDownloads\Site\View\Summary;

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
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\CMS\Filesystem\File;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Session\Session;

use JDownloads\Component\JDownloads\Site\Helper\JDHelper;
use JDownloads\Component\JDownloads\Site\Helper\RouteHelper;
use JDownloads\Component\JDownloads\Site\Helper\CategoriesHelper;

/**
 * HTML View summary class for the jDownloads component
 */
class HtmlView extends BaseHtmlView
{
	protected $items;
    protected $params;
	protected $state;
	protected $user;
    protected $user_rules;

	public function display($tpl = null)
	{
        $app        = Factory::getApplication();
        $params     = $app->getParams();
		$user	    = Factory::getUser();
        $session    = Factory::getSession();
        
        $document = Factory::getDocument();
        
        // Get jD User group settings and limitations
        $this->user_rules = JDHelper::getUserRules();
        
        // Get the needed layout data - type = 3 for a 'summary' layout
        $this->layout = JDHelper::getLayout(3);
        
        // Add JavaScript Frameworks
        HTMLHelper::_('bootstrap.framework');

        // Load optional RTL Bootstrap CSS
        if ($this->layout->uses_bootstrap){
            HTMLHelper::_('bootstrap.loadCss', true, $this->document->direction);
        }

        // Load optional w3css framework
        if ($this->layout->uses_w3css){
            $w3_css_path = JPATH_ROOT.'/components/com_jdownloads/assets/css/w3.css';
            if (file::exists($w3_css_path)){
                $document->addStyleSheet( Uri::base()."components/com_jdownloads/assets/css/w3.css", 'text/css', null, array() );                
            }
        }

        $this->items    = $this->get('Items');
		if (!is_array($this->items)){
            $this->items = array();
        } else {
            // Write elements in session to have them available in the client survey form.
            $session->set('jd_summary_items', $this->items);
        }
         
        $this->state	= $this->get('State');
		$this->form     = $this->get('Form');
        $this->user		= $user;
        

        // Upload icon handling
        $this->view_upload_button = false;
        
        if ($this->user_rules->uploads_view_upload_icon){
            // We must here check whether the user has the permissions to create new downloads. 
            // This can be defined in the components permissions but also in any category.
            // But the upload icon is only viewed when in the user groups settings is also activated the: 'display add/upload icon' option.
                            
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
                                     
        // Check for errors.
        if (count($errors = $this->get('Errors')))
        {
            throw new GenericDataException(implode("\n", $errors), 500);
        }
        
        // Add all needed cripts and css files
        $document->addScript(Uri::base().'components/com_jdownloads/assets/js/jdownloads.js');

        if ($params->get('view_ratings')){
            $document->addScript(Uri::base().'components/com_jdownloads/assets/rating/js/ajaxvote.js');
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
                if (file::exists($own_css_path)){
                    $document->addStyleSheet( Uri::base()."components/com_jdownloads/assets/css/".$params->get('own_css_file'), "text/css", null, array() );
                }
            }
        } 

        if ($params->get('view_ratings')){
            $document->addStyleSheet( Uri::base()."components/com_jdownloads/assets/rating/css/ajaxvote.css", "text/css", null, array() );         
        }

        $custom_css_path = JPATH_ROOT.'/components/com_jdownloads/assets/css/jdownloads_custom.css';
        if (file::exists($custom_css_path)){
            $document->addStyleSheet( Uri::base()."components/com_jdownloads/assets/css/jdownloads_custom.css", 'text/css', null, array() );                
        }   
        
        $this->jd_image_path = JPATH_ROOT  . '/images/jdownloads';
        
        // Create a shortcut for $item.
        $items = &$this->items;
        
        $this->params = $this->state->get('params');
        
        if ($items){
            foreach ($items as $item){
            
		        // Add router helpers.
		        $item->slug			= $item->alias ? ($item->id.':'.$item->alias) : $item->id;
		        $item->catslug		= $item->category_alias ? ($item->catid.':'.$item->category_alias) : $item->catid;
		        $item->parent_slug	= $item->category_alias ? ($item->parent_id.':'.$item->parent_alias) : $item->parent_id;

		        // Merge download params. If this is single-download view, menu params override download params
		        // Otherwise, download params override menu item params
		        
		        $active	= $app->getMenu()->getActive();
		        $temp	= clone ($this->params);

		        // Check to see which parameters should take priority
		        if ($active) {
			        $currentLink = $active->link;
			        // If the current view is the active item and an download view for this download, then the menu item params take priority
			        if (strpos($currentLink, 'view=download') && (strpos($currentLink, '&id='.(string) $item->id))) {
				        // $item->params are the downloads params, $temp are the menu item params
				        // Merge so that the menu item params take priority
				        $item->params->merge($temp);
				        // Load layout from active query (in case it is an alternative menu item)
				        if (isset($active->query['layout'])) {
					        $this->setLayout($active->query['layout']);
				        }
			        } else {
				        // Current view is not a single download, so the download params take priority here
				        // Merge the menu item params with the download params so that the download params take priority
				        $temp->merge($item->params);
				        $item->params = $temp;

				        // Check for alternative layouts (since we are not in a single-download menu item)
				        // Single-download menu item layout takes priority over alt layout for an download
				        if ($layout = $item->params->get('download_layout')) {
					        $this->setLayout($layout);
				        }
			        }
		        } else {
			        // Merge so that download params take priority
			        $temp->merge($item->params);
			        $item->params = $temp;
			        // Check for alternative layouts (since we are not in a single-download menu item)
			        // Single-download menu item layout takes priority over alt layout for an download
			        if ($layout = $item->params->get('download_layout')) {
				        $this->setLayout($layout);
			        }
		        }

		        // Check the view access to the download (the model has already computed the values).
		        if ($item->params->get('access-view') != true && ($item->params->get('show_noauth') != true &&  $user->get('guest') ) ) {
			        $app->enqueueMessage( Text::_('JERROR_ALERTNOAUTHOR'), 'warning');
                    return;
		        }

		        // Escape strings for HTML output
		        $this->pageclass_sfx = htmlspecialchars($item->params->get('pageclass_sfx') ?? '');
                
                // Required for some content plugins which needed a field named text
                if ($item->description_long != ''){
                    $item->text = $item->description_long;
                    $long_used = true;
                } else {
                    $item->text = $item->description;
                    $long_used = false;
                }
                
                // Process the content plugins.
                PluginHelper::importPlugin('content');
                $app->triggerEvent('onContentPrepare', ['com_jdownloads.download', &$item, &$params, 0]);
                
                $this->event = new \stdClass();

                // We should not display custom fields here. So we use not really the results - ToDo: find another way to solve this
                
                $results = $app->triggerEvent('onContentAfterTitle', array('com_jdownloads.download', &$item, &$this->params, 0));
                $this->event->afterDisplayTitle = trim(implode("\n", $results));
                $results = array(); // remove results

                $results = $app->triggerEvent('onContentBeforeDisplay', array('com_jdownloads.download', &$item, &$this->params, 0));
                $this->event->beforeDisplayContent = trim(implode("\n", $results));
                $results = array(); // remove results

                $results = $app->triggerEvent('onContentAfterDisplay', array('com_jdownloads.download', &$item, &$this->params, 0));
                $this->event->afterDisplayContent = trim(implode("\n", $results));
                
                // We use a little trick to get always the changes from content plugins 
                if ($long_used){
                    if ($item->text != $item->description_long){
                        $item->description_long = $item->text; 
                    }
                } else {
                    if ($item->text != $item->description){
                        $item->description = $item->text; 
                    }            
                }
            }
        
        }
        
        $this->items = $items;
        
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
        
		$menus	= $app->getMenu();
		$pathway = $app->getPathway();
		$title = null;

		// Because the application sets a default page title,
		// we need to get it from the menu item itself
		$menu = $menus->getActive();
		if ($menu)
		{
			$this->params->def('page_heading', $this->params->get('page_title', $menu->title));
		}
		else
		{
			$this->params->def('page_heading', Text::_('COM_JDOWNLOADS_DOWNLOADS'));
		}

		if (count($this->items) > 1){
            $title = Text::_('COM_JDOWNLOADS_FRONTEND_HEADER_SUMMARY_PAGE_TITLE'); 
        } else {
            $title = $this->params->get('page_title', '');
            $title .= ' - '.Text::_('COM_JDOWNLOADS_FRONTEND_HEADER_SUMMARY_PAGE_TITLE');
        }    

        if (isset($menu->query['catid'])){
            $id = (int) @$menu->query['catid'];  // The Download category has an own menu item
        } else {
            $id = 0;  // The Download category has not an own menu item 
        }
        
		if ($menu) {
            // We have a single download process - so we can add the link to this download in the breadcrumbs
            if ($this->items[0]->title && count($this->items) == 1) {
                $title = $this->items[0]->title;
                $title .= ' - '.Text::_('COM_JDOWNLOADS_FRONTEND_HEADER_SUMMARY_PAGE_TITLE');
            }
            $path = array(array('title' => Text::_('COM_JDOWNLOADS_FRONTEND_HEADER_SUMMARY_PAGE_TITLE'), 'link' => ''));
            
            if (count($this->items) == 1){
                $path[] = array('title' => $this->items[0]->title, 'link' => RouteHelper::getDownloadRoute($this->items[0]->slug, $this->items[0]->catid, $this->items[0]->language));
            }

            $category = CategoriesHelper::getInstance('Download')->get($this->items[0]->catid);
            
            while ($category && ($menu->query['option'] != 'com_jdownloads' || ($id == 0 && $id != $category->id)) && $category->id != 'root'){
                $path[] = array('title' => $category->title, 'link' => RouteHelper::getCategoryRoute($category->id, true));
                $category = $category->getParent();
            }                   
            
        	$path = array_reverse($path);
			foreach($path as $item)
			{
				$pathway->addItem($item['title'], $item['link']);
			}
		}

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
		if (empty($title)) {
			$title = Text::_('COM_JDOWNLOADS_FRONTEND_HEADER_SUMMARY_PAGE_TITLE');
		}

		$this->document->setTitle($title);
        $this->document->setDescription($this->params->get('menu-meta_description'));

        // Use at first settings from download - alternate from jD configuration
        if ($params->get('robots')){
            // Use settings from jD-config
            $this->document->setMetadata('robots', $params->get('robots'));    
        } else {
            // Is not defined in item or jd-config - so we use the global config setting
            $this->document->setMetadata( 'robots' , $app->getCfg('robots'));
        }

	}
}
