<?php
/**
 * @package     Joomla.Administrator
 * @subpackage  com_trials
 *
 * @copyright   Copyright (C) NPEU 2023.
 * @license     MIT License; see LICENSE.md
 */

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Layout\LayoutHelper;

$displayData = [
    'textPrefix' => 'COM_TRIALS',
    'formURL'    => 'index.php?option=com_trials',
];

/*
$displayData = [
    'textPrefix' => 'COM_TRIALS',
    'formURL'    => 'index.php?option=com_trials',
    'helpURL'    => '',
    'icon'       => 'icon-globe trials',
];
*/

$user = Factory::getApplication()->getIdentity();

if ($user->authorise('core.create', 'com_trials') || count($user->getAuthorisedCategories('com_trials', 'core.create')) > 0) {
    $displayData['createURL'] = 'index.php?option=com_trials&task=trial.add';
}

echo LayoutHelper::render('joomla.content.emptystate', $displayData);