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
 
namespace JDownloads\Component\JDownloads\Site\View\Myhistory;

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
 
JLoader::register('AssociationHelper', JPATH_SITE . '/components/com_jdownloads/src/Helper/AssociationHelper.php');


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
        $user   = Factory::getUser();
        $params = $app->getParams();
        
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
                $app->enqueueMessage( Text::_('COM_JDOWNLOADS_MY_DOWNLOAD_HISTORY_NOT_FOUND'), 'notice');
                return false;            
            }
        }
        
        // Get jD User group settings and limitations
        $this->user_rules = JDHelper::getUserRules();
        
        // Get the needed layout data - type = 3 for a 'summary' layout in a later step
        $this->layout = JDHelper::getLayout(3);
        
        // Add JavaScript Frameworks
        HtmlHelper::_('bootstrap.framework');

        // Load optional RTL Bootstrap CSS
        if ($this->layout->uses_bootstrap){
            HtmlHelper::_('bootstrap.loadCss', true, $this->document->direction);
        }

        // Load optional w3css framework
        if ($this->layout->uses_w3css){
            $w3_css_path = JPATH_ROOT.'/components/com_jdownloads/assets/css/w3.css';
            if (file::exists($w3_css_path)){
                $document->addStyleSheet( Uri::base()."components/com_jdownloads/assets/css/w3.css", 'text/css', null, array() );                
            }
        }

        // Initialise variables
        $state        = $this->get('State');
        $items        = $this->get('Items');
        $pagination   = $this->get('Pagination');
        
        if (!$items){
            $app->enqueueMessage( Text::_('COM_JDOWNLOADS_MY_DOWNLOAD_HISTORY_NOT_FOUND'), 'notice');
            return false;            
        }        
        
        // Check for errors.
        if (count($errors = $this->get('Errors')))
        {
            throw new GenericDataException(implode("\n", $errors), 500);
        }

        if ($items === false) {
            return $app->enqueueMessage( Text::_('COM_JDOWNLOADS_MY_DOWNLOAD_HISTORY_NOT_FOUND'), 'error');
            
        }

        // add all needed cripts and css files
        
        $document->addScript(Uri::base().'components/com_jdownloads/assets/js/jdownloads.js');
        
        $document->addScriptDeclaration('var live_site = "'.Uri::base().'";');

        if ($params->get('use_css_buttons_instead_icons')){
           $document->addStyleSheet( Uri::base()."components/com_jdownloads/assets/css/jdownloads_buttons.css", "text/css", null, array() ); 
        }
        
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
        
        $this->jd_image_path = JPATH_ROOT.'/images/jdownloads';        
        
        $params = &$state->params;
                
        // Compute the download slugs and prepare text (runs content plugins).
        for ($i = 0, $n = count($items); $i < $n; $i++)
        {
            $item = &$items[$i];
            
            $item->slug = $item->alias ? ($item->id . ':' . $item->alias) : $item->id;
            
            // required for some content plugins
            $item->text = '';
            
            PluginHelper::importPlugin('content');
            $app->triggerEvent('onContentPrepare', ['com_jdownloads.downloads', &$item, &$params, 0]);
            
            $item->event = new \stdClass();

            $results = Factory::getApplication()->triggerEvent('onContentAfterTitle', array('com_jdownloads.downloads', &$item, &$item->params, 0));
            $item->event->afterDisplayTitle = trim(implode("\n", $results));

            $results = Factory::getApplication()->triggerEvent('onContentBeforeDisplay', array('com_jdownloads.downloads', &$item, &$item->params, 0));
            $item->event->beforeDisplayContent = trim(implode("\n", $results));

            $results = Factory::getApplication()->triggerEvent('onContentAfterDisplay', array('com_jdownloads.downloads', &$item, &$item->params, 0));
            $item->event->afterDisplayContent = trim(implode("\n", $results));
        }        
        
        //Escape strings for HTML output
        $this->pageclass_sfx = htmlspecialchars($params->get('pageclass_sfx') ?? '');

        $this->state        = $state;        
        $this->params       = $params;
        $this->items        = $items;
        $this->pagination   = $pagination;

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
