<?php
/**
 * @package jDownloads
 *
 * @copyright   Copyright (C) 2005 - 2019 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */
 
namespace JDownloads\Component\JDownloads\Administrator\Field\Modal;

\defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Form\FormField;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\LanguageHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Session\Session;
use Joomla\Database\ParameterType;

/**
 * Supports a modal category picker.
 *
 */
class CategoryField extends FormField
{
	/**
	 * The form field type.
	 *
	 * @var     string
	 */
	protected $type = 'Modal_Category';

	/**
	 * Method to get the field input markup.
	 *
	 * @return  string  The field input markup.
	 */
	protected function getInput()
	{
		$allowNew       = ((string) $this->element['new'] == 'true');
		$allowEdit      = ((string) $this->element['edit'] == 'true');
		$allowClear     = ((string) $this->element['clear'] != 'false');
		$allowSelect    = ((string) $this->element['select'] != 'false');
		$allowPropagate = ((string) $this->element['propagate'] == 'true');

		$languages = LanguageHelper::getContentLanguages(array(0, 1), false);

		// Load language.
		Factory::getLanguage()->load('com_jdownloads', JPATH_ADMINISTRATOR);

		// The active category id field.
		$value = (int) $this->value > 0 ? (int) $this->value : '';

		// Create the modal id.
		$modalId = 'Category_' . $this->id;
        
        /** @var \Joomla\CMS\WebAsset\WebAssetManager $wa */
        $wa = Factory::getApplication()->getDocument()->getWebAssetManager();

        // Add the modal field script to the document head.
        $wa->useScript('field.modal-fields');

        // Script to proxy the select modal function to the modal-fields.js file.
        if ($allowSelect)
        {
            static $scriptSelect = null;

            if (is_null($scriptSelect))
            {
                $scriptSelect = array();
            }

            if (!isset($scriptSelect[$this->id]))
            {
                $wa->addInlineScript("
                window.jSelectCategory_" . $this->id . " = function (id, title, object) {
                    window.processModalSelect('Category', '" . $this->id . "', id, title, '', object);
                }",
                    [],
                    ['type' => 'module']
                );

                Text::script('JGLOBAL_ASSOCIATIONS_PROPAGATE_FAILED');

                $scriptSelect[$this->id] = true;
            }
        }

		// Setup variables for display.
		$linkCategories = 'index.php?option=com_jdownloads&amp;view=categories&amp;layout=modal&amp;tmpl=component&amp;' . Session::getFormToken() . '=1';
		$linkCategory   = 'index.php?option=com_jdownloads&amp;view=category&amp;layout=modal&amp;tmpl=component&amp;' . Session::getFormToken() . '=1';
		$modalTitle     = Text::_('COM_JDOWNLOADS_SELECT_A_CATEGORY');

		if (isset($this->element['language']))
		{
			$linkCategories .= '&amp;forcedLanguage=' . $this->element['language'];
			$linkCategory   .= '&amp;forcedLanguage=' . $this->element['language'];
			$modalTitle     .= ' &#8212; ' . $this->element['label'];
		}

		$urlSelect = $linkCategories . '&amp;function=jSelectCategory_' . $this->id;
		$urlEdit   = $linkCategory . '&amp;task=category.edit&amp;id=\' + document.getElementById("' . $this->id . '_id").value + \'';
		$urlNew    = $linkCategory . '&amp;task=category.add';

		if ($value)
		{
			$db    = Factory::getDbo();
			$query = $db->getQuery(true)
				->select($db->quoteName('title'))
				->from($db->quoteName('#__jdownloads_categories'))
				->where($db->quoteName('id') . ' = ' . (int) $value);
			$db->setQuery($query);

			try
			{
				$title = $db->loadResult();
			}
			catch (\RuntimeException $e)
			{
				Factory::getApplication()->enqueueMessage( $e->getMessage(), 'error');
			}
		}

		$title = empty($title) ? Text::_('COM_JDOWNLOADS_SELECT_A_CATEGORY') : htmlspecialchars($title, ENT_QUOTES, 'UTF-8');

		// The current category display field.
        $html  = '';

        if ($allowSelect || $allowNew || $allowEdit || $allowClear)
        {
            $html .= '<span class="input-group">';
        }

        $html .= '<input class="form-control" id="' . $this->id . '_name" type="text" value="' . $title . '" readonly size="35">';

        // Select category button.
        if ($allowSelect)
        {
            $html .= '<button'
                . ' class="btn btn-primary' . ($value ? ' hidden' : '') . '"'
                . ' id="' . $this->id . '_select"'
                . ' data-bs-toggle="modal"'
                . ' type="button"'
                . ' data-bs-target="#ModalSelect' . $modalId . '">'
                . '<span class="icon-file" aria-hidden="true"></span> ' . Text::_('COM_JDOWNLOADS_SELECT')
                . '</button>';
        }

        // New category button.
        if ($allowNew)
        {
            $html .= '<button'
                . ' class="btn btn-secondary' . ($value ? ' hidden' : '') . '"'
                . ' id="' . $this->id . '_new"'
                . ' data-bs-toggle="modal"'
                . ' type="button"'
                . ' data-bs-target="#ModalNew' . $modalId . '">'
                . '<span class="icon-plus" aria-hidden="true"></span> ' . Text::_('COM_JDOWNLOADS_ACTION_CREATE')
                . '</button>';
        }

        // Edit category button.
        if ($allowEdit)
        {
            $html .= '<button'
                . ' class="btn btn-primary' . ($value ? '' : ' hidden') . '"'
                . ' id="' . $this->id . '_edit"'
                . ' data-bs-toggle="modal"'
                . ' type="button"'
                . ' data-bs-target="#ModalEdit' . $modalId . '">'
                . '<span class="icon-pen-square" aria-hidden="true"></span> ' . Text::_('COM_JDOWNLOADS_ACTION_EDIT')
                . '</button>';
        }

        // Clear category button.
        if ($allowClear)
        {
            $html .= '<button'
                . ' class="btn btn-secondary' . ($value ? '' : ' hidden') . '"'
                . ' id="' . $this->id . '_clear"'
                . ' type="button"'
                . ' onclick="window.processModalParent(\'' . $this->id . '\'); return false;">'
                . '<span class="icon-times" aria-hidden="true"></span> ' . Text::_('COM_JDOWNLOADS_REMOVE')
                . '</button>';
        }

        // Propagate category button
        if ($allowPropagate && \count($languages) > 2)
        {
            // Strip off language tag at the end
            $tagLength = (int) \strlen($this->element['language']);
            $callbackFunctionStem = substr("jSelectCategory_" . $this->id, 0, -$tagLength);

            $html .= '<button'
            . ' class="btn btn-primary' . ($value ? '' : ' hidden') . '"'
            . ' type="button"'
            . ' id="' . $this->id . '_propagate"'
            . ' title="' . Text::_('COM_JDOWNLOADS_ASSOCIATIONS_PROPAGATE_TIP') . '"'
            . ' onclick="Joomla.propagateAssociation(\'' . $this->id . '\', \'' . $callbackFunctionStem . '\');">'
            . '<span class="icon-sync" aria-hidden="true"></span> ' . Text::_('COM_JDOWNLOADS_ASSOCIATIONS_PROPAGATE_BUTTON')
            . '</button>';
        }
        
        if ($allowSelect || $allowNew || $allowEdit || $allowClear)
        {
            $html .= '</span>';
        }

		// Select category modal.

        if ($allowSelect)
        {
            $html .= HTMLHelper::_(
                'bootstrap.renderModal',
                'ModalSelect' . $modalId,
                array(
                    'title'       => $modalTitle,
                    'url'         => $urlSelect,
                    'height'      => '400px',
                    'width'       => '800px',
                    'bodyHeight'  => 70,
                    'modalWidth'  => 80,
                    'footer'      => '<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">'
                                        . Text::_('JLIB_HTML_BEHAVIOR_CLOSE') . '</button>',
                )
            );
        }

        // New category modal.
        if ($allowNew)
        {
            $html .= HTMLHelper::_(
                'bootstrap.renderModal',
                'ModalNew' . $modalId,
                array(
                    'title'       => Text::_('COM_JDOWNLOADS_EDIT_CAT_ADD'),
                    'backdrop'    => 'static',
                    'keyboard'    => false,
                    'closeButton' => false,
                    'url'         => $urlNew,
                    'height'      => '400px',
                    'width'       => '800px',
                    'bodyHeight'  => 70,
                    'modalWidth'  => 80,
                    'footer'      => '<button type="button" class="btn btn-secondary"'
                            . ' onclick="window.processModalEdit(this, \'' . $this->id . '\', \'add\', \'category\', \'cancel\', \'item-form\'); return false;">'
                            . Text::_('JLIB_HTML_BEHAVIOR_CLOSE') . '</button>'
                            . '<button type="button" class="btn btn-primary"'
                            . ' onclick="window.processModalEdit(this, \'' . $this->id . '\', \'add\', \'category\', \'save\', \'item-form\'); return false;">'
                            . Text::_('JSAVE') . '</button>'
                            . '<button type="button" class="btn btn-success"'
                            . ' onclick="window.processModalEdit(this, \'' . $this->id . '\', \'add\', \'category\', \'apply\', \'item-form\'); return false;">'
                            . Text::_('JAPPLY') . '</button>',
                )
            );
        }

        // Edit category modal.
        if ($allowEdit)
        {
            $html .= HTMLHelper::_(
                'bootstrap.renderModal',
                'ModalEdit' . $modalId,
                array(
                    'title'       => Text::_('COM_JDOWNLOADS_EDIT_CATEGORY'),
                    'backdrop'    => 'static',
                    'keyboard'    => false,
                    'closeButton' => false,
                    'url'         => $urlEdit,
                    'height'      => '400px',
                    'width'       => '800px',
                    'bodyHeight'  => 70,
                    'modalWidth'  => 80,
                    'footer'      => '<button type="button" class="btn btn-secondary"'
                            . ' onclick="window.processModalEdit(this, \'' . $this->id . '\', \'edit\', \'category\', \'cancel\', \'item-form\'); return false;">'
                            . Text::_('JLIB_HTML_BEHAVIOR_CLOSE') . '</button>'
                            . '<button type="button" class="btn btn-primary"'
                            . ' onclick="window.processModalEdit(this, \'' . $this->id . '\', \'edit\', \'category\', \'save\', \'item-form\'); return false;">'
                            . Text::_('JSAVE') . '</button>'
                            . '<button type="button" class="btn btn-success"'
                            . ' onclick="window.processModalEdit(this, \'' . $this->id . '\', \'edit\', \'category\', \'apply\', \'item-form\'); return false;">'
                            . Text::_('JAPPLY') . '</button>',
                )
            );
        }
        
        // Note: class='required' for client side validation
        $class = $this->required ? ' class="required modal-value"' : '';

        $html .= '<input type="hidden" id="' . $this->id . '_id"' . $class . ' data-required="' . (int) $this->required . '" name="' . $this->name
            . '" data-text="' . htmlspecialchars(Text::_('COM_JDOWNLOADS_SELECT_A_CATEGORY', true), ENT_COMPAT, 'UTF-8') . '" value="' . $value . '">';

		return $html;
	}

	/**
	 * Method to get the field label markup.
	 *
	 * @return  string  The field label markup.
	 */
	protected function getLabel()
	{
		return str_replace($this->id, $this->id . '_name', parent::getLabel());
	}
}
