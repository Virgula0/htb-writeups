<?php
/**
 * @copyright    Copyright (C) 2005 - 2013 Open Source Matters, Inc. All rights reserved.
 * @license      GNU General Public License version 2 or later; see LICENSE.txt
 */

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

namespace JDownloads\Component\JDownloads\Administrator\Field;

\defined( '_JEXEC' ) or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Form\FormField;

class JDPureTextField extends FormField
{
	protected $type = 'JDPureText';

	protected function getInput()
	{
		$class = 'inputbox';
		if ((string) $this->element['class'] != '') {
			$class = $this->element['class'];
		}
		return  '<div class="'.$class.'" style="padding-top:5px">'.$this->value.'</div>';
	}

	protected function getLabel()
	{
		echo '<div class="clr"></div>';
			return parent::getLabel();
		echo '<div class="clr"></div>';
	}
}