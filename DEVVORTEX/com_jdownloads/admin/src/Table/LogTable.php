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
 * Logs Table class
 */
class LogTable extends Table
{
	/**
	 * Constructor
	 *
	 * @param object Database connector object
	 */
	function __construct(&$db) 
	{
		parent::__construct('#__jdownloads_logs', 'id', $db);
	}
    
}
?>