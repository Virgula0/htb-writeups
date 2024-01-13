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

use Joomla\CMS\Language\Text;
use Joomla\CMS\Factory; 
use Joomla\CMS\Table\Table;
 
/**
 * Templates Table class
 */
class TemplateTable extends Table
{
	/**
	 * Constructor
	 *
	 * @param object Database connector object
	 */
	function __construct(&$db) 
	{
		parent::__construct('#__jdownloads_templates', 'id', $db);
	}
    
    /**
     * Overloaded check method to ensure data integrity.
     *
     * @param   boolean  $isNew         true when the license is new
     *
     * @return    boolean    True on success.
     */
    public function checkData($isNew)
    {
        // Try to prevent Error Messages by very strict SQL settings            
        if ($this->template_before_text == null)     $this->template_before_text      = '';            
        if ($this->template_after_text == null)      $this->template_after_text       = '';            
        if ($this->template_footer_text == null)     $this->template_footer_text      = '';
        if ($this->template_header_text == null)     $this->template_header_text      = '';
        if ($this->template_subheader_text == null)  $this->template_subheader_text   = '';
        if ($this->note == null)                     $this->note                      = '';
        
        return true;
    }    
}
?>