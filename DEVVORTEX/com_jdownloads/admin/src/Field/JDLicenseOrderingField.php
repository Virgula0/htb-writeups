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
use Joomla\CMS\Form\FormHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\HTML\HTMLHelper;

/**
 * Supports an HTML select list of licenses
 *
 * @package        Joomla.Administrator
 * @since        1.6
 */
class JDLicenseOrderingField extends FormField
{
    /**
     * The form field type.
     *
     * @var        string
     * @since    1.6
     */
    protected $type = 'JDLicenseOrdering';

    /**
     * Method to get the field input markup.
     *
     * @return    string    The field input markup.
     * @since    1.6
     */
    protected function getInput()
    {
        // Initialize variables.
        $html = array();
        $attr = '';

        // Initialize some field attributes.
        $attr .= $this->element['class'] ? ' class="'.(string) $this->element['class'].'"' : '';
        $attr .= ((string) $this->element['disabled'] == 'true') ? ' disabled="disabled"' : '';
        $attr .= $this->element['size'] ? ' size="'.(int) $this->element['size'].'"' : '';

        // Initialize JavaScript field attributes.
        $attr .= $this->element['onchange'] ? ' onchange="'.(string) $this->element['onchange'].'"' : '';

        // Get some field values from the form.
        $licenseId    = (int) $this->form->getValue('id');
        //$categoryId    = (int) $this->form->getValue('catid');

        // Build the query for the ordering list.
        $query = 'SELECT ordering AS value, title AS text' .
                ' FROM #__jdownloads_licenses' .
                ' ORDER BY ordering';

        // Create a read-only list (no name) with a hidden input to store the value.
        if ((string) $this->element['readonly'] == 'true') {
            $html[] = HtmlHelper::_('list.ordering', '', $query, trim($attr), $this->value, $licenseId ? 0 : 1);
            $html[] = '<input type="hidden" name="'.$this->name.'" value="'.$this->value.'"/>';
        }
        // Create a regular list.
        else {
            $html[] = HtmlHelper::_('list.ordering', $this->name, $query, trim($attr), $this->value, $licenseId ? 0 : 1);
        }

        return implode($html);
    }
}
