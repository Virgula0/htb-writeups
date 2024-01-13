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
 
use Joomla\CMS\MVC\Model\ListModel;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\Utilities\ArrayHelper;
use Joomla\String\StringHelper;
use Joomla\CMS\Factory;
use Joomla\Registry\Registry;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Filter\InputFilter;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Object\CMSObject;

use JDownloads\Component\JDownloads\Administrator\Extension\JDownloadsComponent;
use JDownloads\Component\JDownloads\Site\Helper\AssociationHelper;
use JDownloads\Component\JDownloads\Site\Helper\QueryHelper;
use JDownloads\Component\JDownloads\Administrator\Model\DownloadModel;
use JDownloads\Component\JDownloads\Site\Helper\JDHelper;


/**
 * jDownloads Component Download Model
 *
 */
class ReportModel extends DownloadModel
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
        $form = $this->loadForm('com_jdownloads.report', 'report',
                                array('control' => 'jform', 'load_data' => $loadData));
        if (empty($form)) 
        {
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
        $app    = Factory::getApplication();
        $jinput = Factory::getApplication()->input;
        $params = $app->getParams();
        
        // Initialise variables.
        $data    = $app->input->getVar('jform', array(), 'post', 'array');
        
        $name       = array_key_exists('name', $data)       ? $data['name'] : '';
        $email      = array_key_exists('email', $data)      ? $data['email'] : '';
        $fileid     = array_key_exists('id', $data)         ? intval($data['id']) : 0;
        $filetitle  = array_key_exists('title', $data)      ? $data['title'] : '';
        $filename   = array_key_exists('url_download', $data) ? $data['url_download'] : '';
        $catid      = array_key_exists('catid', $data)      ? $data['catid'] : 0;
        $cat_title  = array_key_exists('cat_title', $data)  ? $data['cat_title'] : '';
        $reason     = array_key_exists('reason', $data)     ? intval($data['reason']) : 0;
        $text       = array_key_exists('text', $data)       ? $data['text'] : '';
        
        switch ($reason) {
            case 0:
                $reason_text = ''; 
                break;
            case 1:
                $reason_text = Text::_('COM_JDOWNLOADS_REPORT_REASON_MISSING'); 
                break;
            case 2:
                $reason_text = Text::_('COM_JDOWNLOADS_REPORT_REASON_BAD_FILE'); 
                break;
            case 3:
                $reason_text = Text::_('COM_JDOWNLOADS_REPORT_REASON_OTHERS'); 
                break;
        }        
        
        // get the users IP
        $ip = JDHelper::getRealIp();

        // Get fully decoded and sanitised strings
        $text  = InputFilter::getInstance()->clean($text, 'string');
        $name  = InputFilter::getInstance()->clean($name, 'string');
        $email = InputFilter::getInstance()->clean($email, 'string');
        
        // Get all users email addresses in an array
        $recipients = explode( ';', $params->get('send_mailto_report'));

        // Check to see if there are any users in this group before we continue
        if (!count($recipients) || $recipients[0] == '') {
            $this->setError(Text::_('COM_JDOWNLOADS_NO_EMAIL_RECIPIENT_FOUND'));
            return false;
        }

        // Get the Mailer
        $mailer = Factory::getMailer();

        // Build email message format.
        $mailer->setSender(array($app->getCfg('mailfrom'), $app->getCfg('fromname')));
        $mailer->setSubject('jDownloads - '.stripslashes(JDHelper::getOnlyLanguageSubstring($params->get('report_mail_subject'))));
        
        $message = JDHelper::getOnlyLanguageSubstring($params->get('report_mail_layout'));
        $message = str_replace('{category}', $cat_title, $message);
        $message = str_replace('{cat_id}', $catid, $message);
        $message = str_replace('{file_id}', $fileid, $message);
        $message = str_replace('{file_title}', $filetitle, $message);
        $message = str_replace('{file_name}', $filename, $message);
        $message = str_replace('{name}', $name, $message);
        $message = str_replace('{mail}', $email, $message);
        $message = str_replace('{ip}', $ip, $message);
        $date_format = JDHelper::getDateFormat();
        $message = str_replace('{date_time}', HTMLHelper::date($input = 'now', $date_format['long'], true), $message);
        $message = str_replace('{reason}', $reason_text, $message);
        $message = str_replace('{message}', $text, $message);
        
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
        
        // remove the first recipient and add all other recipients to the BCC field 
        if (count($recipients) > 1){
             array_shift($recipients);
             $mailer->addBCC($recipients);
        }        
        
        // Send the Mail
        $result    = $mailer->Send();

        if ( $result !== true ) {
            $this->setError($result->getError());
            return false;
        } else {
            $app->enqueueMessage( Text::_('COM_JDOWNLOADS_EMAIL_SUCCESSFUL_SENDED'), 'notice');
            return true;
        }        
    }    
    
}
