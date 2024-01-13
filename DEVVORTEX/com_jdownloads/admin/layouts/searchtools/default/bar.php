<?php
/**
 * @package     Joomla.Site
 * @subpackage  Layout
 *
 * @copyright   (C) 2013 Open Source Matters, Inc. <https://www.joomla.org>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 * 
 * Modified for jDownloads 
 */

defined('_JEXEC') or die;

use Joomla\CMS\Language\Text;
use Joomla\Registry\Registry;
use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;

HTMLHelper::_('jquery.framework');

$data = $displayData;

// Receive overridable options
$data['options'] = !empty($data['options']) ? $data['options'] : [];

if (is_array($data['options'])) {
	$data['options'] = new Registry($data['options']);
}

// Options
$filterButton = $data['options']->get('filterButton', true);
$searchButton = $data['options']->get('searchButton', true);

$filters = $data['view']->filterForm->getGroup('filter');

if (empty($filters['filter_search']) || !$searchButton) {
    return;
}

?>

<?php if ($data['view']->get('_defaultModel') == 'associations') : ?>
    <?php $app = Factory::getApplication(); ?>
    <?php // We will get the component item type and language filters & remove it from the form filters. ?>
    <?php if ($app->input->get('forcedItemType', '', 'string') == '') : ?>
        <?php $itemTypeField = $data['view']->filterForm->getField('itemtype'); ?>
        <div class="js-stools-field-filter js-stools-selector">
            <?php echo $itemTypeField->input; ?>
        </div>
    <?php endif; ?>
    <?php if ($app->input->get('forcedLanguage', '', 'cmd') == '') : ?>
        <?php $languageField = $data['view']->filterForm->getField('language'); ?>
        <div class="js-stools-field-filter js-stools-selector">
            <?php echo $languageField->input; ?>
        </div>
    <?php endif; ?>
<?php endif; ?>

<?php // Display the main joomla layout ?>
<?php if (!empty($filters['filter_search'])) : ?>
	<?php if ($searchButton) : ?>
        <div class="btn-group">
            <div class="input-group">
                <?php echo $filters['filter_search']->input; ?>
                <?php if ($filters['filter_search']->description) : ?>
                <!--<div role="tooltip" id="<?php echo $filters['filter_search']->name . '-desc'; ?>">-->
                <div role="tooltip" id="<?php echo ($filters['filter_search']->id ?: $filters['filter_search']->name) . '-desc'; ?>" class="filter-search-bar__description">
                    <?php echo htmlspecialchars(Text::_($filters['filter_search']->description), ENT_COMPAT, 'UTF-8'); ?>
                </div>
                <?php endif; ?>
                <label for="filter_search" class="visually-hidden">
                <?php if (isset($filters['filter_search']->label)) : ?>
                <?php echo Text::_($filters['filter_search']->label); ?>
                <?php else : ?>
                <?php echo Text::_('JSEARCH_FILTER'); ?>
                <?php endif; ?>
                </label>
                <button type="submit" class="btn btn-primary" aria-label="<?php echo Text::_('JSEARCH_FILTER_SUBMIT'); ?>">
                 <span class="icon-search" aria-hidden="true"></span>
                </button>
            </div>
        </div>
        <div class="btn-group">
        <?php if ($filterButton) : ?>
        <button type="button" class="btn btn-primary js-stools-btn-filter">
            <?php echo Text::_('JFILTER_OPTIONS'); ?>
            <span class="icon-angle-down" aria-hidden="true"></span>
        </button>
        <?php endif; ?>
        <button type="button" class="btn btn-primary js-stools-btn-clear">
        <?php echo Text::_('JSEARCH_FILTER_CLEAR'); ?>
        </button>
        </div>
    <?php endif; ?>
<?php endif;