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

namespace JDownloads\Component\JDownloads\Administrator\Table;
 
\defined('_JEXEC') or die;

use Joomla\Utilities\ArrayHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Factory; 
use Joomla\CMS\Table\Table;
use Joomla\CMS\Application\ApplicationHelper;
 
/**
 * License Table class
 */
class LicenseTable extends Table
{
	/**
	 * Constructor
	 *
	 * @param object Database connector object
	 */
	function __construct(&$db) 
	{
		parent::__construct('#__jdownloads_licenses', 'id', $db);
	}
    
    /**
     * Overloaded check method to ensure data integrity.
     *
     * @return    boolean    True on success.
     */
    public function check()
    {
        // check for valid name
        if (trim($this->title) == '') {
            $this->setError(Text::_('COM_WEBLINKS_ERR_TABLES_TITLE'));
            return false;
        }

        // check for http, https, ftp on webpage
        if ((stripos($this->url, 'http://') === false)
            && (stripos($this->url, 'https://') === false)
            && (stripos($this->url, 'ftp://') === false)
            && $this->url != '')
        {
            $this->url = 'https://'.$this->url;
        }

        if (empty($this->alias)) {
            $this->alias = $this->title;
        }
        
        $this->alias = ApplicationHelper::stringURLSafe($this->alias);
        if (trim(str_replace('-','',$this->alias)) == '') {
            $this->alias = Factory::getDate()->format("Y-m-d-H-i-s");
        }

        return true;
    }
    
}
?>