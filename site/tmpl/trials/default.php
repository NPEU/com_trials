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

use Joomla\CMS\Factory;
use Joomla\CMS\Uri\Uri;

use \Michelf\Markdown;





function cmt_htmlID($text): string
{
    if (!is_string($text)) {
        trigger_error('Function \'html_id\' expects argument 1 to be an string', E_USER_ERROR);
        return false;
    }
    $return = strtolower(trim(preg_replace('/\s+/', '-', cmt_stripPunctuation($text))));
    return $return;
}

function cmt_stripPunctuation($text): string
{
    if (!is_string($text)) {
        trigger_error('Function \'strip_punctuation\' expects argument 1 to be an string', E_USER_ERROR);
        return false;
    }
    $text = html_entity_decode($text, ENT_QUOTES);

    $urlbrackets = '\[\]\(\)';
    $urlspacebefore = ':;\'_\*%@&?!' . $urlbrackets;
    $urlspaceafter = '\.,:;\'\-_\*@&\/\\\\\?!#' . $urlbrackets;
    $urlall = '\.,:;\'\-_\*%@&\/\\\\\?!#' . $urlbrackets;

    $specialquotes = '\'"\*<>';

    $fullstop = '\x{002E}\x{FE52}\x{FF0E}';
    $comma = '\x{002C}\x{FE50}\x{FF0C}';
    $arabsep = '\x{066B}\x{066C}';
    $numseparators = $fullstop . $comma . $arabsep;

    $numbersign = '\x{0023}\x{FE5F}\x{FF03}';
    $percent = '\x{066A}\x{0025}\x{066A}\x{FE6A}\x{FF05}\x{2030}\x{2031}';
    $prime = '\x{2032}\x{2033}\x{2034}\x{2057}';
    $nummodifiers = $numbersign . $percent . $prime;
    $return = preg_replace(
        [
            // Remove separator, control, formatting, surrogate,
            // open/close quotes.
            '/[\p{Z}\p{Cc}\p{Cf}\p{Cs}\p{Pi}\p{Pf}]/u',
            // Remove other punctuation except special cases
            '/\p{Po}(?<![' . $specialquotes .
            $numseparators . $urlall . $nummodifiers . '])/u',
            // Remove non-URL open/close brackets, except URL brackets.
            '/[\p{Ps}\p{Pe}](?<![' . $urlbrackets . '])/u',
            // Remove special quotes, dashes, connectors, number
            // separators, and URL characters followed by a space
            '/[' . $specialquotes . $numseparators . $urlspaceafter .
            '\p{Pd}\p{Pc}]+((?= )|$)/u',
            // Remove special quotes, connectors, and URL characters
            // preceded by a space
            '/((?<= )|^)[' . $specialquotes . $urlspacebefore . '\p{Pc}]+/u',
            // Remove dashes preceded by a space, but not followed by a number
            '/((?<= )|^)\p{Pd}+(?![\p{N}\p{Sc}])/u',
            // Remove consecutive spaces
            '/ +/',
        ],
        ' ',
        $text
    );
    $return = str_replace('/', '_', $return);
    return str_replace("'", '', $return);
}

function cmt_getTwig($tpl): object
{

    $loader = new \Twig\Loader\ArrayLoader(['tpl' => $tpl]);
    $twig   = new \Twig\Environment($loader);

    // Option to add debugging. Use like:
    // <pre>
    //   {{ dump(user) }}
    // </pre>
    #$twig   = new \Twig\Environment($loader, ['debug' => true]);
    #$twig->addExtension(new \Twig\Extension\DebugExtension());

    // Add markdown filter:
    $md_filter = new \Twig\TwigFilter('md', function ($string) {
        $new_string = '';
        // Parse md here
        $new_string = Markdown::defaultTransform($string);
        return $new_string;
    });

    $twig->addFilter($md_filter);
    // Use like {{ var|md|raw }}

    // Add pad filter:
    $pad_filter = new \Twig\TwigFilter('pad', function ($string, $length, $pad = ' ', $type = 'right') {
        $new_string = '';
        switch ($type) {
            case 'right':
                $type = STR_PAD_RIGHT;
                break;
            case 'left':
                $type = STR_PAD_LEFT;
                break;
            case 'both':
                break;
                $type = STR_PAD_BOTH;
        }
        $length = (int) $length;
        $pad    = (string) $pad;
        $new_string = str_pad($string, $length, $pad, $type);

        return $new_string;
    });
    $twig->addFilter($pad_filter);

    // Add regex_replace filter:
    $regex_replace_filter = new \Twig\TwigFilter('regex_replace', function ($string, $search = '', $replace = '') {
        $new_string = '';

        $new_string = preg_replace($search, $replace, $string);

        return $new_string;
    });
    $twig->addFilter($regex_replace_filter);

    // Add html_id filter:
    $html_id_filter = new \Twig\TwigFilter('html_id', function ($string) {
        $new_string = '';

        $new_string = cmt_htmlID($string);

        return $new_string;
    });
    $twig->addFilter($html_id_filter);

    // Add sum filter:
    $sum_filter = new \Twig\TwigFilter('sum', function ($array) {
        return array_sum($array);
    });
    $twig->addFilter($sum_filter);

    // Add str_replace filter:
    $str_replace = new \Twig\TwigFilter('str_replace', function ($string, $search = '', $replace = '') {
        $new_string = '';

        $new_string = str_replace( $search, $replace, $string);

        return $new_string;
    });
    $twig->addFilter($str_replace);


    // Add filter for image fallback (image to use if preferred one doesn't exist):
    $img_fallback_filter = new \Twig\TwigFilter('fallback', function ($image_path, $fallback_path) {

        $file_headers = @get_headers($image_path);
        if($file_headers[0] != 'HTTP/1.1 404 Not Found') {
            return $image_path;
        }

        $file_headers = @get_headers($fallback_path);
        if($file_headers[0] != 'HTTP/1.1 404 Not Found') {
            return $fallback_path;
        }

        return '';
    });
    $twig->addFilter($img_fallback_filter);

    // Add filter for image path (height from width):
    $img_height_filter = new \Twig\TwigFilter('height', function ($image_path, $width) {

        $image_info = @getimagesize($image_path);

        if (!$image_info) {
            return 'image path not found: ' . $image_path;
        }

        $width = (int) $width;

        if ($image_info[0] > $image_info[1]) {
            $image_ratio = $image_info[0] / $image_info[1];
            $height = round($width / $image_ratio);
        } else {
            $image_ratio = $image_info[1] / $image_info[0];
            $height = round($width * $image_ratio);
        }
        //$height = round($width * $image_ratio);

        return $height;
    });
    $twig->addFilter($img_height_filter);

    // Add filter for image path (width from height):
    $img_width_filter = new \Twig\TwigFilter('width', function ($image_path, $height) {

        $image_info = @getimagesize($image_path);

        if (!$image_info) {
            return 'image path not found: ' . $image_path;
        }

        $height = (int) $height;

        if ($image_info[0] > $image_info[1]) {
            $image_ratio = $image_info[0] / $image_info[1];
            $width = round($height * $image_ratio);
        } else {
            $image_ratio = $image_info[1] / $image_info[0];
            $width = round($height / $image_ratio);
        }
        //$width = round($height / $image_ratio);

        return $width;
    });
    $twig->addFilter($img_width_filter);


    return $twig;
}




$doc = Factory::getDocument();

$output          = false;
$data            = false;
$json            = false;
$params          = $this->menu_params;

$template_path = Uri::getInstance()->root() . '/templates/npeu6';

$doc->addScript($template_path . '/js/filter.min.js');
$doc->addScript($template_path . '/js/sort.min.js');


$data_src        = $params->get('data_src', false);
$data_aqs_tog    = $params->get('aqs_tog');
$data_aqs        = $params->get('aqs');
$data_tpl        = $params->get('data_tpl');
$data_src_err    = $params->get('data_src_err');
$data_decode_err = $params->get('data_decode_err');
#echo '<pre>'; var_dump($data_aqs_tog); echo '</pre>'; exit;
$url_qs = $_SERVER['QUERY_STRING'];

$form_vals = [];
$qs_empty = empty($data_aqs_tog);

// Process Advanced Query Strings:
if ($data_src && (!empty($url_qs) && $data_aqs_tog && !empty($data_aqs))) {

    $new_qs = [];

    $lines = explode("\n", trim(str_replace("\n\n", "\n", str_replace("\r", "\n", $data_aqs))));

    parse_str($url_qs, $url_qs_array);
#echo 'url_qs_array<pre>'; var_dump($url_qs_array); echo '</pre>'; #exit;
    foreach ($lines as $line) {

        list($param_name, $param_values) = explode("=", $line);


        // Are multiple values allowed? (array)
        $val_array_allowed = false;
        if (strstr($param_name, '[]') !== false) {
            $val_array_allowed = true;
            $param_name = str_replace('[]', '', $param_name);
        }

#echo 'param_name<pre>'; var_dump($param_name); echo '</pre>'; #exit;
        // Check the name of the param exists in the query string
        if (!array_key_exists($param_name, $url_qs_array)) {
            // This name does not appear in the URL, ignore:
            continue;
        }
#echo 'param_values<pre>'; var_dump($param_values); echo '</pre>'; #exit;
#echo 'param_name<pre>'; var_dump($param_name); echo '</pre>'; #exit;
        if (preg_match('#/.+/#', $param_values)) {
            // Test the pattern against the qs value:
            if (preg_match($param_values, $url_qs_array[$param_name], $matches)) {
                #echo 'matches<pre>'; var_dump($matches); echo '</pre>'; #exit;
                $new_qs[$param_name] = $matches[0];
            }
        } else {
            if (strpos($param_values, '|') !== false) {
                $vals = explode('|', trim($param_values));
#echo 'vals<pre>'; var_dump($vals); echo '</pre>'; #exit;
#echo 'vals<pre>'; var_dump($url_qs_array[$param_name]); echo '</pre>'; #exit;

                if (is_array($url_qs_array[$param_name])) {
                    $t = [];
                    foreach ($url_qs_array[$param_name] as $v) {
                        if (in_array($v, $vals)) {
                            $t[] = $v;
                        }
                    }
                    $new_qs[$param_name] = implode(',', $t);

                } else {

                    if (in_array($url_qs_array[$param_name], $vals)) {
                        $new_qs[$param_name] = $url_qs_array[$param_name];
                    }
                }

            } else {
                // Unsupported value type, ignore:
                continue;
            }
        }

    }

    if (!empty($new_qs)) {
        // I can't remember why I decided to replacce the QS instead of appending. Applending is
        // is what I need to make a pubs search thing working so changing it now, hoping it won't
        // break anything.
        #$data_src = preg_replace('/\?.*$/', '', $data_src);
        $delim = (strpos($data_src, '?')) == true ? '&' : '?';
        #$data_src .= $delim . urldecode(http_build_query($new_qs));
        $data_src .= $delim . http_build_query($new_qs);
    }

    #echo '<pre>'; var_dump($data_src); echo '</pre>'; exit;
    #echo '<pre>'; var_dump($new_qs); echo '</pre>'; exit;

    if (!empty($new_qs)) {
        foreach ($new_qs as $name => $vals) {
            $form_vals[$name] = explode(',', $vals);
        }
    }
}


#echo '<pre>'; var_dump($form_vals); echo '</pre>'; exit;
#echo '<pre>'; var_dump($data_src); echo '</pre>'; exit;

if ($data_src) {
    // Allow for relative data src URLs:
    if (strpos($data_src, 'http') !== 0) {
        $s        = empty($_SERVER['SERVER_PORT']) ? '' : ($_SERVER['SERVER_PORT'] == '443' ? 's' : '');
        $protocol = preg_replace('#/.*#',  $s, strtolower($_SERVER['SERVER_PROTOCOL']));
        $domain   = $protocol.'://'.$_SERVER['SERVER_NAME'];
        $data_src = $domain . '/' . trim($data_src, '/');
    }

    // If the data src is meant to be to the same server, but the value stored in the datanase
    // has come from the live server, it won't match and the data won't fetch, so check for and fix
    // that:

    $src_url_parts = parse_url($data_src);
    $src_parent_domain = preg_replace('/^([a-z]+\.)/', '', $src_url_parts['host']);

    $server_parent_domain = preg_replace('/^([a-z]+\.)/', '', $_SERVER['SERVER_NAME']);

    if ($src_parent_domain == $server_parent_domain) {
        $data_src = str_replace($src_url_parts['host'], $_SERVER['SERVER_NAME'], $data_src);
        $src_url_parts = parse_url($data_src);
    }

    // Inspect the final URL to determine if it's an internal or external address:

    // Check for proxy: (note we DON'T want to use this if it's an internal URL)
    $proxy     = NULL;
    $config    = Factory::getConfig();
    $has_proxy = $config->get('proxy_enable');

    if ($has_proxy && $_SERVER['SERVER_NAME'] != $src_url_parts['host']) {
        $proxy_host = $config->get('proxy_host');
        $proxy_port = $config->get('proxy_port');
        $proxy_user = $config->get('proxy_user');
        $proxy_pass = $config->get('proxy_pass');

        $context = [
            'http' => [
                'proxy'           => $proxy_host . ':' . $proxy_port,
                'request_fulluri' => true
            ]
        ];
        $proxy = stream_context_create($context);
    }

    #echo 'data_src<pre>'; var_dump($data_src); echo '</pre>'; exit;
    $data = file_get_contents($data_src, false, $proxy);
    #echo '<pre>'; var_dump($data); echo '</pre>'; exit;

    if ($data === false) {
        $output = Markdown::defaultTransform($data_src_err);
    } else {
        $json = json_decode($data);
        if (is_null($json)) {
            $output = Markdown::defaultTransform($data_decode_err);
        }
        // Encode then re-decode to produce valid JSON:
        $json = json_encode($json, true);
        $json = json_decode($json, true);
    }
} else {
    $json = json_decode('{}', true);
}

if ($output === false) {

    $twig = cmt_getTwig($data_tpl);

    #echo '<pre>'; var_dump($data_tpl); echo '</pre>'; #exit;
    #echo '<pre>'; var_dump($json); echo '</pre>'; exit;

    //$output = $twig->render('tpl', array('data' => $json));
    #echo '<pre>'; var_dump($form_vals); echo '</pre>'; exit;
    $menu_item = Factory::getApplication()->getMenu()->getActive();
    #echo '<pre>'; var_dump($menu_item); echo '</pre>'; exit;
    $menu_item_data = [
        'id'     => $menu_item->id,
        'title'  => $menu_item->title,
        'alias'  => $menu_item->alias,
        'route'  => $menu_item->route,
        'access' => $menu_item->access
    ];
    #echo '<pre>'; var_dump($menu_item_data); echo '</pre>'; exit;
    try {
        $output = $twig->render('tpl', ['data' => $json, 'form_vals' => $form_vals, 'qs_empty' => $qs_empty, 'menu_item' => $menu_item_data]);
    } catch (Exception $e) {
        echo 'Caught exception: ',  $e->getMessage(), "\n";
    }

}
ob_start();
?>
<?php /*if ($brand) : ?>
<div>

    <div class="l-box l-box--center  l-box--space--block">
        <a href="<?php echo $brand->alias; ?>" class="c-badge  c-badge--limit-height--xl">
            <img src="<?php echo $brand->logo_svg_path; ?>" onerror="<?php echo $brand->logo_png_path; ?>" alt="Logo: <?php echo $brand->name; ?>" height="<?php echo $image_height; ?>" width="<?php echo $image_width; ?>">
        </a>
    </div>
</div>
<?php endif; */?>
<?php
$doc->component__sidebar_top = ob_get_contents();
ob_end_clean();
?>
<div class="c-panel  l-box--space--inline--l">
<?php echo $output; return; ?>
</div>





























<?php
/*
#use Joomla\CMS\HTML\HTMLHelper;
#use Joomla\CMS\Language\Multilanguage;
#use Joomla\CMS\Layout\FileLayout;
#use Joomla\CMS\Layout\LayoutHelper;
#use Joomla\CMS\Session\Session;
#use Joomla\Component\Fields\Administrator\Helper\FieldsHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;

defined('_JEXEC') or die;

$language = JFactory::getLanguage();
$language->load('com_trials', JPATH_ADMINISTRATOR . '/components/com_trials');

$table_id = 'trialsTable';

// Get the user object.
$user = Factory::getUser();

$uri  = JUri::getInstance();
#echo '<pre>'; echo $uri; echo '</pre>'; exit;
// Check if user is allowed to add/edit based on tags permissions.
$can_edit       = $user->authorise('core.edit', 'com_trials');
$can_create     = $user->authorise('core.create', 'com_trials');
$can_edit_state = $user->authorise('core.edit.state', 'com_trials');

?>
<?php if ($this->params->get('show_page_heading')) : ?>
<h1>
    <?php echo $this->escape($this->params->get('page_heading')); ?>
</h1>
<?php endif; ?>

<?php if ($can_create) : ?>
<p>
    <a href="<?php echo Route::_('index.php?option=com_trials&view=add'); ?>"><?php echo Text::_('COM_TRIALS_RECORD_CREATING'); ?></a>
</p>
<?php endif; ?>

<table class="" id="<?php echo $table_id; ?>">
    <thead>
        <tr>

            <th width="50%">
                <?php echo Text::_('COM_TRIALS_RECORDS_TITLE'); ?>
            </th>
            <th width="40%">
                <?php echo Text::_('COM_TRIALS_RECORDS_ALIAS'); ?>
            </th>
            <th width="10%">
                <?php echo Text::_('COM_TRIALS_RECORDS_ACTIONS'); ?>
            </th>

        </tr>
    </thead>
    <tbody>
        <?php if (!empty($this->items)) : ?>
            <?php foreach ($this->items as $i => $item) : ?>
                <tr>

                    <td>
                        <?php
                        //$view_link ='test';
                        $view_link = Route::_('index.php?option=com_trials&view=trial&id=' . $item->id);
                        //$edit_link = $uri . '?task=trial.edit&view=trial&id=' . $item->id;
                        //$edit_link = 'index.php?option=com_trials&task=trial.edit&view=trial&id=' . $item->id;
                        $edit_link = Route::_('index.php?option=com_trials&view=trial&task=trial.edit&id=' . $item->id);
                        $is_own = false;
                        if ($this->user->authorise('core.edit.own', 'com_trials') && ($this->user->id == $item->created_by)) {
                            $is_own = true;
                        }
                        ?>
                        <?php echo $view_link; ?><br>
                        <a href="<?php echo $view_link; ?>" title="<?php echo Text::_('COM_TRIALS_VIEW_RECORD'); ?>">
                            <?php echo $item->title; ?>
                        </a>
                    </td>
                    <td>
                        <?php echo $item->alias; ?>
                    </td>
                    <td>
                        <?php if($is_own || $can_edit): ?>
                        <?php echo $edit_link; ?><br>
                        <a href="<?php echo $edit_link; ?>" title="<?php echo Text::_('COM_TRIALS_EDIT_RECORD'); ?>">
                            <?php echo Text::_('COM_TRIALS_RECORDS_ACTION_EDIT'); ?>
                        </a>
                        <?php else: ?>
                        -
                        <?php endif; ?>
                    </td>

                </tr>
            <?php endforeach; ?>
        <?php endif; ?>
    </tbody>
</table>

<p>
    <?php //$alt = 'alt'; ?>
    <?php $alt = Route::_('index.php?option=com_trials&view=alt'); ?>
    <?php echo $alt; ?> <a href="<?php echo $alt; ?>">Sample alternative view</a>
</p>
<p>
    <?php $alt = Route::_('index.php?option=com_trials&layout=other'); ?>
    <?php echo $alt; ?> <a href="<?php echo $alt; ?>">Sample alternative (other) template</a>
</p>
*/