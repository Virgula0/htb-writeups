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

namespace JDownloads\Component\JDownloads\Site\Model;
 
\defined('_JEXEC') or die;

use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Helper\TagsHelper;
use Joomla\CMS\Language\Associations;
use Joomla\CMS\Language\Multilanguage;
use Joomla\CMS\MVC\Model\ItemModel;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\Database\ParameterType;
use Joomla\Registry\Registry;
use Joomla\String\StringHelper;
use Joomla\Utilities\ArrayHelper;
use Joomla\CMS\Filter\InputFilter;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Session\Session;
use Joomla\CMS\Object\CMSObject;

use JDownloads\Component\JDownloads\Administrator\Extension\JDownloadsComponent;
use JDownloads\Component\JDownloads\Site\Helper\AssociationHelper;
use JDownloads\Component\JDownloads\Site\Helper\QueryHelper;
use JDownloads\Component\JDownloads\Site\Helper\JDHelper;
use JDownloads\Component\JDownloads\Administrator\Model\DownloadModel;

/**
 * jDownloads Component Survey Model
 *
 */
class SurveyModel extends DownloadModel
{
	/**
	 * Method to auto-populate the model state.
	 *
	 * Note. Calling getState in this method will result in recursion.
	 *
	 */
	protected function populateState()
	{
		$app = Factory::getApplication();
        $input = Factory::getApplication()->input;

		// Load state from the request.
		$pk = $app->input->get('id', 0, 'int');
		$this->setState('download.id', $pk);

		$this->setState('download.catid', $app->input->get('catid', 0, 'int'));

        $return = $input->get('return', '', 'base64');
		$this->setState('return_page', base64_decode($return));

		// Load the parameters.
		$params	= $app->getParams();
		$this->setState('params', $params);

		$this->setState('layout', $input->get('layout'));
	}

	/**
	 * Method to get download data.
	 *
	 * @param	integer	The id of the download.
	 *
	 * @return	mixed	Content item data object on success, false on failure.
	 */
	public function getItem($itemId = null)
	{
		// Initialise variables.
		$itemId = (int) (!empty($itemId)) ? $itemId : $this->getState('download.id');

		// Get a row instance.
		$table = $this->getTable();

		// Attempt to load the row.
		$return = $table->load($itemId);

		// Check for a table object error.
		if ($return === false && $table->getError()) {
			$this->setError($table->getError());
			return false;
		}

		$properties = $table->getProperties(1);
		$value = ArrayHelper::toObject($properties, CMSObject::class);

		// Convert attrib field to Registry.
		$value->params = new Registry;

		return $value;
	}

	/**
	 * Get the return URL.
	 *
	 * @return	string	The return URL.
	 */
	public function getReturnPage()
	{
		return base64_encode($this->getState('return_page'));
	}
    
    /**
     * Get the report form.
     * 
     * @param    array      The data for the form.
     *           boolean    True when data shall be loaded   
     *
     * @return    array     The form
     */    
    public function getForm($data = array(), $loadData = true) 
    {
        // Initialise variables.
        $app    = Factory::getApplication();
        
        // Get the form.
        $form = $this->loadForm('com_jdownloads.survey', 'survey', array('control' => 'jform', 'load_data' => $loadData));
        
        if (empty($form)) {
            return false;
        }
        
        return $form;
    }    
    
    /**
     * Send the mail with the given data
     *
     * @return    boolean    True when succesful
     */      
    public function send()
    {
        $app     = Factory::getApplication();
        $jinput  = Factory::getApplication()->input;
        $params  = $app->getParams();
        $session = Factory::getSession();
        
        // Initialise variables.
        $data    = $app->input->getVar('jform', array(), 'post', 'array');
        
        $items_data = array();
        
        // Form data
        $name       = array_key_exists('name', $data)       ? $data['name'] : '';
        $company    = array_key_exists('company', $data)    ? $data['company'] : '';
        $country    = array_key_exists('country', $data)    ? $data['country'] : '';
        $address    = array_key_exists('address', $data)    ? $data['address'] : '';
        $email      = array_key_exists('email', $data)      ? $data['email'] : '';
        
        // Get item data when only one item selected
        if ($data['id']){
            $items_data[0]['fileid']     = array_key_exists('id', $data)             ? intval($data['id']) : 0;
            $items_data[0]['filetitle']  = array_key_exists('title', $data)          ? $data['title'] : '';
            $items_data[0]['file_name']  = array_key_exists('url_download', $data)   ? $data['url_download'] : '';
            $items_data[0]['catid']      = array_key_exists('catid', $data)          ? $data['catid'] : 0;
            $items_data[0]['cat_title']  = array_key_exists('cat_title', $data)      ? $data['cat_title'] : '';
        } else {
            $items = $session->get('jd_summary_items', array());
            if ($items){
                for ($i = 0; $i < count($items); ++$i){
                    $items_data[$i]['fileid']     = $items[$i]->id;
                    $items_data[$i]['filetitle']  = $items[$i]->title;
                    $items_data[$i]['file_name']  = $items[$i]->url_download;
                    $items_data[$i]['catid']      = $items[$i]->catid;
                    $items_data[$i]['cat_title']  = $items[$i]->category_title;  
                }    
            }
        }
        
        // Get the users IP
        $ip = JDHelper::getRealIp();

        // Automatically removes html formatting
        $name       = InputFilter::getInstance()->clean($name, '');
        $company    = InputFilter::getInstance()->clean($company, 'string');
        $country    = InputFilter::getInstance()->clean($country, 'string');
        $address    = InputFilter::getInstance()->clean($address, 'string');
        $email      = InputFilter::getInstance()->clean($email, 'string');
        
        // Get all users email addresses in an array
        $recipients = $params->get('customers_send_mailto');
        
        // Check to see if there are any users in this group before we continue
        if (!$recipients) {
            $this->setError(Text::_('COM_JDOWNLOADS_NO_EMAIL_RECIPIENT_FOUND'));
            return false;
        }
        
        $recipients = explode(';', $recipients);

        foreach ($items_data as $item_data){
        
            // Get the Mailer
            $mailer = Factory::getMailer();

            // Build email message format.
            $mailer->setSender(array($app->getCfg('mailfrom'), $app->getCfg('fromname')));
            $mailer->setSubject('jDownloads - '.stripslashes(JDHelper::getOnlyLanguageSubstring($params->get('customers_mail_subject'))));
            
            $message = JDHelper::getOnlyLanguageSubstring($params->get('customers_mail_layout'));

            $date_format = JDHelper::getDateFormat();
            
            // Insert user data 
            $message = str_replace('{name}', $name, $message);
            $message = str_replace('{company}', $company, $message);
            $message = str_replace('{country}', $country, $message);
            $message = str_replace('{address}', $address, $message);
            $message = str_replace('{mail}', $email, $message);
            $message = str_replace('{ip}', $ip, $message);
            $message = str_replace('{date_time}', HtmlHelper::date($input = 'now', $date_format['long'], true), $message);
            
            // Insert Downloads data
            $message = str_replace('{category}', $item_data['cat_title'], $message);
            $message = str_replace('{cat_id}', $item_data['catid'], $message);
            $message = str_replace('{file_id}', $item_data['fileid'], $message);
            $message = str_replace('{file_title}', $item_data['filetitle'], $message);
            $message = str_replace('{file_name}', $item_data['file_name'], $message);
            
            $mailer->setBody($message);
            
            // Example: Optional file attached
            // $mailer->addAttachment(JPATH_COMPONENT.'/assets/document.pdf');
            // Example: Optionally add embedded image 
            // $mailer->AddEmbeddedImage( JPATH_COMPONENT.'/assets/logo128.jpg', 'logo_id', 'logo.jpg', 'base64', 'image/jpeg' );
            
            // Needed for use HTML 
            $mailer->IsHTML(true);
            $mailer->Encoding = 'base64';

            // Add recipients
            $mailer->addRecipient($recipients[0]);
            
            // Remove the first recipient and add all other recipients to the BCC field 
            if (count($recipients) > 1){
                 array_shift($recipients);
                 $mailer->addBCC($recipients);
            }        
            
            // Send the Mail
            $result = $mailer->Send();

        }
        
        if ( $result !== true ) {
            return false;
        } else {
            $app->enqueueMessage( Text::_('COM_JDOWNLOADS_EMAIL_SUCCESSFUL_SENDED'), 'notice');
            return true;
        }        
    }    
    
}
