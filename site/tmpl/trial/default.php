<?php
/**
 * @package     Joomla.Site
 * @subpackage  com_trials
 *
 * @copyright   Copyright (C) NPEU 2025.
 * @license     MIT License; see LICENSE.md
 */

defined('_JEXEC') or die;
require_once dirname(dirname(__DIR__)) . '/vendor/autoload.php';

#use Joomla\CMS\Factory;
#use Joomla\CMS\Language\Multilanguage;
#use Joomla\CMS\Language\Text;
#use Joomla\CMS\Layout\FileLayout;
#use Joomla\CMS\Layout\LayoutHelper;
#use Joomla\CMS\Router\Route;
#use Joomla\CMS\Session\Session;
#use Joomla\Component\Fields\Administrator\Helper\FieldsHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;

use \Michelf\Markdown;


$doc = Factory::getDocument();

/*
Note that this template will probably be converted to a Twig template so it can be edited in the
admin area, but it's easier to set it up initiall here where I can understand what the limitations
might / should be.
*/

$trial = $this->item;
#echo '<pre>'; var_dump($trial); echo '</pre>'; exit;
// Construct the protocol (http|https):
$s                 = empty($_SERVER['SERVER_PORT']) ? '' : (($_SERVER['SERVER_PORT'] == '443') ? 's' : '');
$protocol          = preg_replace('#/.*#',  $s, strtolower($_SERVER['SERVER_PROTOCOL']));

// Construct the domain url:
$domain            = $protocol.'://'.$_SERVER['SERVER_NAME'];

// Construct the public root path: (note: this is the SERVER path, not a URL)
$public_root_path  = realpath($_SERVER['DOCUMENT_ROOT']) . DIRECTORY_SEPARATOR;

$brand = false;
if (!empty($trial->brand_details)) {
    $brand = $trial->brand_details;

    $image_path = urldecode($public_root_path . $brand->logo_png_path);

    if (!file_exists($image_path)) {
        $brand = false;
    } else {
        $image_info = getimagesize($image_path);

        $w = $image_info[0];
        $h = $image_info[1];
        $image_ratio = ($w < $h) ? ($w / $h) : ($h / $w);

        $image_height = 100;
        $image_width  = round($image_height / $image_ratio);

    }
}
#echo '<pre>'; var_dump($brand); echo '</pre>'; exit;
ob_start();
?>
<?php if ($brand) : ?>
<div>
    <div class="l-box l-box--center  l-box--space--block">
        <a href="<?php echo $brand->alias; ?>" class="c-badge  c-badge--limit-height--xl">
            <img src="<?php echo $brand->logo_svg_path; ?>" onerror="<?php echo $brand->logo_png_path; ?>" alt="Logo: <?php echo $brand->name; ?>" height="<?php echo $image_height; ?>" width="<?php echo $image_width; ?>">
        </a>
    </div>
</div>
<?php endif; ?>
<?php
$doc->component__sidebar_top = ob_get_contents();
ob_end_clean();
?>

<div class="user-content  longform-content">
    <?php if ($trial->supported_trial == "Y") : ?>
    <p>(Supported trial)</p>
    <?php endif; ?>
    <div>

    <?php if ($trial->long_title != "") : ?>
    <p filterable_index filterable_index_name="desc"><?php echo $trial->long_title; ?></p>
    <?php endif; ?>

    <p>
        Trial started: <b sortable_index sortable_index_name="started"><?php echo $trial->any_start; ?></b>&emsp;
        Trial ended: <b sortable_index sortable_index_name="ended"><?php echo $trial->any_end; ?></b>&emsp;
    </p>

    <?php if ($trial->support_role != "") : ?>
    <p>NPEU role: <?php echo preg_replace('#(https?://\\S+)#', '<a href="$1" rel="external">$1</a>', $trial->support_role); ?></p>
    <?php endif; ?>

    <?php if (!empty($trial->summary)) : ?>
    <h2>Summary</h2>
    <?php echo Markdown::defaultTransform($trial->summary); ?>
    <?php endif; ?>

    <?php if ($trial->publications != "" || $trial->published_protocol != "") : ?>
    <div>
        <details>
            <summary>Show Publications</summary>
            <?php if ($trial->publications != "") : ?>
            <ul>
                <li>
                <?php echo preg_replace("/\n\n/", "</li>\n\t\t\t<li>", preg_replace('#(https?://.*?)(\s|$)#', '<br><a href="$1" rel="external">$1</a>$2', str_replace("/r", '', trim($trial->publications)))); ?>
                </li>
            </ul>
            <?php endif; ?>
            <?php if ($trial->published_protocol != "") : ?>
            <b>Published Protocol</b>
            <ul>
                <li>
                <?php echo preg_replace('/^<br>/', '', preg_replace("/\n\n/", "</li>\n\t\t\t<li>", preg_replace('#(https?://.*?)(\s|$)#', '<br><a href="$1" rel="external">$1</a>$2', str_replace("/r", '', trim($trial->published_protocol))))); ?>
                </li>
            </ul>
            <?php endif; ?>
        </details>
    </div>
    <?php endif; ?>

    <?php if ($trial->summary_of_results != "") : ?>
    <details>
        <summary>Show Plain Language Summary of Results</summary>
        <div>
            <div>
                <?php $lines = explode("\n", preg_replace("/\n{2,}/", '', str_replace("\r", '', trim($trial->summary_of_results)))); ?>
                <?php foreach($lines as $link) : ?>
                <?php $link_parts = explode(' | ', $link); ?>
                <p class="u-text-align--center">
                    <span data-contains="download">
                        <a data-contains="thumbnail" href="<?php echo $link_parts[1]; ?>" type="application/pdf">
                            <img alt="Thumbnail preview of the file." height="425" src="<?php echo $link_parts[1]; ?>.png?s=300&amp;m=1" width="300"><br>
                            <span><?php echo $link_parts[0]; ?></span>
                        </a>
                    </span>
                </p>
                <?php endforeach; ?>
            </div>
        </div>
    </details>
    <?php endif; ?>

    <?php if ($trial->other_files != "") : ?>
    <?php $sections = explode("---\n", preg_replace("/\n{2,}/", '', str_replace("\r", '', trim($trial->other_files)))); ?>
    <?php foreach($sections as $section) : ?>
    <?php $lines = explode("\n", trim($section)); ?>


    <details>
        <summary>Show <?php echo array_shift($lines); ?></summary>
        <div>
            <?php foreach($lines as $link) : ?>
            <?php $link_parts = explode(' | ', $link); ?>
            <p>
                <a href="<?php echo $link_parts[1]; ?>"<?php if (!str_contains($link_parts[0], 'npeu.ox.ac.uk')) : ?> rel="external"<?php endif; ?>>
                    <span><?php echo $link_parts[0]; ?></span>
                </a>
            </p>
            <?php endforeach; ?>
        </div>
    </details>

    <?php endforeach; ?>
    <?php endif; ?>
</div>

    <p>
        <a href="<?php echo Route::_('index.php?option=com_trials'); ?>">Back to Trials Directory</a>
    </p>

</div>


