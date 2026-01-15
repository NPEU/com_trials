<?php
/**
 * @package     Joomla.Site
 * @subpackage  com_trials
 *
 * @copyright   Copyright (C) NPEU 2025.
 * @license     MIT License; see LICENSE.md
 */

namespace NPEU\Component\Trials\Site\Service;

defined('_JEXEC') or die;

use Joomla\CMS\Application\SiteApplication;
use Joomla\CMS\Categories\CategoryFactoryInterface;
use Joomla\CMS\Categories\CategoryInterface;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Component\Router\RouterView;
use Joomla\CMS\Component\Router\RouterViewConfiguration;
use Joomla\CMS\Component\Router\Rules\MenuRules;
use Joomla\CMS\Component\Router\Rules\NomenuRules;
use Joomla\CMS\Component\Router\Rules\StandardRules;
use Joomla\CMS\Menu\AbstractMenu;
use Joomla\CMS\MVC\Factory\MVCFactoryAwareTrait;
use Joomla\Database\DatabaseInterface;
use Joomla\Database\ParameterType;

use NPEU\Component\Trials\Site\Service\CustomRouterRules;



class Router extends RouterView
{
    use MVCFactoryAwareTrait;

    private $categoryFactory;

    private $categoryCache = [];

    private $db;

    /**
     * Component router constructor
     *
     * @param   SiteApplication           $app              The application object
     * @param   AbstractMenu              $menu             The menu object to work with
     * @param   CategoryFactoryInterface  $categoryFactory  The category object
     * @param   DatabaseInterface         $db               The database object
     */
    public function __construct(SiteApplication $app, AbstractMenu $menu)
    {
        //$this->categoryFactory = $categoryFactory;
        //$this->db              = $db;
        $this->db = \Joomla\CMS\Factory::getContainer()->get('DatabaseDriver');

        //$this->attachRule(new CustomRouterRules($this));

        #$category = new RouterViewConfiguration('category');
        #$category->setKey('id')->setNestable();
        #$this->registerView($category);
        $trials = new RouterViewConfiguration('trials');

        $this->registerView($trials);

        $trial = new RouterViewConfiguration('trial');
        $trial->setKey('id')->setParent($trials);

        $this->registerView($trial);


        parent::__construct($app, $menu);

        $this->attachRule(new MenuRules($this));
        $this->attachRule(new StandardRules($this));
        $this->attachRule(new NomenuRules($this));

        $this->attachRule(new CustomRouterRules($this));
    }

    /**
     * Method to get the id for an trials item from the segment
     *
     * @param   string  $segment  Segment of the trials to retrieve the ID for
     * @param   array   $query    The request that is parsed right now
     *
     * @return  mixed   The id of this item or false
     */
    public function getTrialId(string $segment, array $query): bool|int
    {
        // If the alias (segment) has been constructed to include the id as a
        // prefixed part of it, (e.g. 123-thing-name) then we can use this:
        //return (int) $segment;
        // Otherwise we'll need to query the database:
        $db = $this->db;
        $dbQuery = $db->getQuery(true)
            ->select($db->quoteName('id'))
            ->from($db->quoteName('#__trials'))
            ->where($db->quoteName('alias') . ' = :alias')
            ->where($db->quoteName('web_landing_include') . ' = "Y"')
            ->bind(':alias', $segment);

        return  $db->setQuery($dbQuery)->loadResult() ?: false;
    }

    /**
     * Method to get the segment(s) for a trials item
     *
     * @param   string  $id     ID of the trials to retrieve the segments for
     * @param   array   $query  The request that is built right now
     *
     * @return  array|string  The segments of this item
     */
    public function getTrialSegment(int $id, array $query): array
    {
        #echo 'getTrialSegment<pre>'; var_dump($query); echo '</pre>';# exit;

        $db = $this->db;

        $dbQuery = $db->getQuery(true)
            ->select($db->quoteName('alias'))
            ->from($db->quoteName('#__trials'))
            ->where($db->quoteName('id') . ' = :id')
            ->bind(':id', $id);

            $segment = $db->setQuery($dbQuery)->loadResult() ?: null;

        if ($segment === null) {
            return [];
        }
        return [$id => $segment];
    }


}
