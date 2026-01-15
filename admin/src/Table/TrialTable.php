<?php
/**
 * @package     Joomla.Administrator
 * @subpackage  com_trials
 *
 * @copyright   Copyright (C) NPEU 2025.
 * @license     MIT License; see LICENSE.md
 */

namespace NPEU\Component\Trials\Administrator\Table;

defined('_JEXEC') or die;

#use Joomla\CMS\Tag\TaggableTableInterface;
#use Joomla\CMS\Tag\TaggableTableTrait;
#use Joomla\CMS\Versioning\VersionableTableInterface;
use Joomla\CMS\Access\Rules;
use Joomla\CMS\Factory;
use Joomla\CMS\Filter\OutputFilter;
use Joomla\CMS\Table\Nested;
use Joomla\CMS\Table\Table;
use Joomla\Database\DatabaseDriver;
use Joomla\Registry\Registry;


/**
 * Trial Table class.
 *
 * @since  1.0
 */
#class TrialTable extends Nested implements VersionableTableInterface, TaggableTableInterface
class TrialTable extends Table
{
    #use TaggableTableTrait;

    /**
     * Array with alias for "special" columns such as ordering, hits etc etc
     * Note the admin listing uses 'published' in the special un/publish switcher, so the
     * published/state alias is neeed for that at least.
     *
     * @var    array
     * @since  3.4.0
     */
    protected $_columnAlias = [
        'published' => 'state'
    ];

    public function __construct(DatabaseDriver $db) {
        $this->typeAlias = 'com_trials.trial';

        parent::__construct('#__trials', 'id', $db);

        // In functions such as generateTitle() Joomla looks for the 'title' field ...
        #$this->setColumnAlias('title', 'greeting');
    }

    /**
     * Overloaded load function
     *
     * @param       int $pk primary key
     * @param       boolean $reset reset data
     * @return      boolean
     * @see JTable:load
     */
    public function load($pk = null, $reset = true)
    {
        if (parent::load($pk, $reset)) {
            // Convert the params field to a registry.
            $registry = new Registry;
            $registry->loadString($this->params, 'JSON');

            $this->params = $registry->toObject();

            // Load the owner:
            $query = $this->_db->getQuery(true);
            $query->select('*')
                  ->from($this->_db->quoteName('#__users'))
                  ->where($this->_db->quoteName('id') . ' = ' . (int) $this->owner_user_id);
            $this->_db->setQuery($query);

            $this->owner_details = $this->_db->loadObject();

            // Load the brand.
            $query = $this->_db->getQuery(true);
            $query->select('*')
                  ->from($this->_db->quoteName('#__brands'))
                  ->where($this->_db->quoteName('id') . ' = ' . (int) $this->brand_id);
            $this->_db->setQuery($query);

            $this->brand_details = $this->_db->loadObject();

            #echo '<pre>'; var_dump($this->brand_details); echo '</pre>'; exit;

            return true;
        } else {
            return false;
        }
    }

    public function bind($array, $ignore = '') {
        if (isset($array['params']) && is_array($array['params'])) {
            // Convert the params field to a string.
            $registry = new Registry;
            $registry->loadArray($array['params']);

            // Convert the "fake" comma back to real one
            $array['params'] = str_replace('\ufe50', ',', (string) $registry);
        }

        // Bind the rules.
        if (isset($array['rules']) && \is_array($array['rules'])) {
            $rules = new Rules($array['rules']);
            $this->setRules($rules);
        }

        return parent::bind($array, $ignore);
    }

    public function store($updateNulls = true) {
        // add the 'created by' and 'created' date fields if it's a new record
        // and these fields aren't already set
        $date = date('Y-m-d h:i:s');
        $user_id = Factory::getApplication()->getIdentity()->get('id');
        if (!$this->id) {
            // new record
            if (empty($this->created_by)) {
                $this->created_by = $user_id;
                $this->created    = $date;
            }
        }

        return parent::store();
    }

    /**
     * Method to compute the default name of the asset.
     * The default name is in the form `table_name.id`
     * where id is the value of the primary key of the table.
     *
     * @return    string
     * @since    2.5
     */
    protected function _getAssetName() {
        $k = $this->_tbl_key;
        return 'com_trials.trial.'.(int) $this->$k;
    }
    /**
     * Method to return the title to use for the asset table.
     *
     * @return    string
     * @since    2.5
     */
    protected function _getAssetTitle() {
        return $this->title;
    }


    public function check() {
        $this->alias = trim($this->alias);
        if (empty($this->alias)) {
            $this->alias = OutputFilter::stringURLSafe($this->title);
        } else {
            $this->alias = OutputFilter::stringURLSafe($this->alias);
        }

        // Check for valid name
        if (trim($this->title) == '') {
            $this->setError(Text::_('COM_TRIALS_ERR_TABLES_TITLE'));
            return false;
        }

        // Check for existing name
        $db = $this->getDbo();

        $query = $db->getQuery(true)
            ->select($db->quoteName('id'))
            ->from($db->quoteName('#__researchprojects'))
            ->where($db->quoteName('title') . ' = ' . $db->quote($this->title));
        $db->setQuery($query);

        $xid = (int) $db->loadResult();

        if ($xid && $xid != (int) $this->id) {
            $this->setError(Text::_('COM_TRIALS_ERR_TABLES_TITLE_EXISTS'));

            return false;
        }

        if (empty($this->alias)) {
            $this->alias = $this->title;
        }

        $this->alias = ApplicationHelper::stringURLSafe($this->alias);

        if (trim(str_replace('-', '', $this->alias)) == '') {
            $this->alias = Factory::getDate()->format("Y-m-d-H-i-s");
        }

        return true;
    }

    public function delete($pk = null, $children = false) {
        return parent::delete($pk, $children);
    }

    /**
     * typeAlias is the key used to find the content_types record
     * needed for creating the history record
     */
    public function getTypeAlias() {
        return $this->typeAlias;
    }
}
