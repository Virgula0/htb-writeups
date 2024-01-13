<?php
/**
* @version 4.0
* @package JDownloads
* @copyright (C) 2007/2022 www.jdownloads.com
* @license http://www.gnu.org/copyleft/gpl.html GNU/GPL
*
* Editor button for jDownloads content plugin 4.0 
*
*/

defined( '_JEXEC' ) or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Object\CMSObject;
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\CMS\Session\Session;

class PlgButtonJDownloads extends CMSPlugin {
     
    protected $autoloadLanguage = true;
    
    function __construct(&$subject, $config)
    {
        parent::__construct($subject, $config);
    }    

	public function onDisplay($name, $asset, $author)
    {
		$app = Factory::getApplication();
		$document = Factory::getDocument();
        
        $user  = Factory::getUser();

        // Can create in any category (component permission) or at least in one category
        $canCreateRecords = $user->authorise('core.create', 'com_jdownloads')
            || count($user->getAuthorisedCategories('com_jdownloads', 'core.create')) > 0;

        // Instead of checking edit on all records, we can use **same** check as the form editing view
        $values = (array) Factory::getApplication()->getUserState('com_jdownloads.edit.download.id');
        $isEditingRecords = count($values);

        // This ACL check is probably a double-check (form view already performed checks)
        $hasAccess = $canCreateRecords || $isEditingRecords;
        if (!$hasAccess)
        {
            return;
        }
		
		$allowed_in_frontend = $this->params->get('frontend', 0);

		$link = 'index.php?option=com_jdownloads&amp;view=downloads&amp;layout=modal&amp;tmpl=component&amp;'. Session::getFormToken() . '=1&amp;editor=' . $name;
        
        $button = new CMSObject;
        $button->modal      = true;
        $button->link       = $link;
        $button->text       = Text::_('PLG_EDITORS-XTD_JDOWNLOADS_CAT_BUTTON_TEXT');                  
        $button->name       = $this->_type . '_' . $this->_name;
        $button->icon       = 'file-add';
        $button->iconSVG    = '<svg viewBox="0 0 32 32" width="24" height="24"><path d="M28 24v-4h-4v4h-4v4h4v4h4v-4h4v-4zM2 2h18v6h6v10h2v-10l-8-8h-20v32h18v-2h-16z"></path></svg>';
        $button->options    = "{handler: 'iframe', size: {x: 990, y: 500}}";		
        
		if ($allowed_in_frontend == 0 && !$app->isClient('administrator')) $button = null;
		        
		return $button;
	}
}
?>