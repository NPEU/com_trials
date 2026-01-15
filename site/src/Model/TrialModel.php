<?php
/**
 * @package     Joomla.Site
 * @subpackage  com_trials
 *
 * @copyright   Copyright (C) NPEU 2025.
 * @license     MIT License; see LICENSE.md
 */

namespace NPEU\Component\Trials\Site\Model;

defined('_JEXEC') or die;

#use NPEU\Component\Trials\Site\Helper\TrialHelper;
#use Joomla\CMS\MVC\Model\ItemModel;
use Joomla\CMS\Cache\CacheControllerFactoryInterface;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Multilanguage;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
use Joomla\Registry\Registry;

/**
 * Trial Component Model
 */
class TrialModel extends \NPEU\Component\Trials\Administrator\Model\TrialModel {

    /**
     * @var object item
     */
    protected $item;

    protected $item_state;

    /**
     * Method to auto-populate the model state.
     *
     * This method should only be called once per instantiation and is designed
     * to be called on the first call to the getState() method unless the model
     * configuration flag to ignore the request is set.
     *
     * Note. Calling getState in this method will result in recursion.
     *
     * @return    void
     * @since    2.5
     */
    protected function populateState()
    {
        $app = Factory::getApplication();

        // Get the trial id
        $jinput = $app->input;
        $id     = $jinput->get('id', 1, 'INT');
        $this->setState('trial.id', $id);

        // Load the parameters.
        $this->setState('params', Factory::getApplication()->getParams());
        parent::populateState();
    }

    /**
     * Get the trial
     * @return object The trial to be displayed to the user
     */
    /*public function getItem($pk = NULL)
    {
        if (!isset($this->item) || !is_null($pk))
        {
            $id    = $pk ?: $this->getState('trial.id');
            $db    = $this->getDatabase();
            $query = $db->getQuery(true);

            $query->select('*')
                ->from($db->quoteName('#__trials'))
                ->where('id=' . (int) $id);

            if (is_numeric($this->item_state)) {
                $query->where('state = ' . (int) $this->item_state);
            } elseif ($this->item_state === '') {
                $query->where('(state IN (0, 1))');
            }

            /*if (Multilanguage::isEnabled())
            {
                $lang = Factory::getLanguage()->getTag();
                $query->where('h.language IN ("*","' . $lang . '")');
            }* /


            $db->setQuery((string) $query);

            if ($this->item = $db->loadObject()) {
                // Load the JSON string
                $params = new Registry;
                $params->loadString($this->item->params, 'JSON');
                $this->item->params = $params;

                // Merge global params with item params
                $params = clone $this->getState('params');
                $params->merge($this->item->params);
                $this->item->params = $params;

                // Convert the JSON-encoded image info into an array
                /*$image = new Registry;
                $image->loadString($this->item->image, 'JSON');
                $this->item->imageDetails = $image;* /

                // Check if the user can access this record:
                $user = Factory::getApplication()->getIdentity();
                $userAccessLevels = $user->getAuthorisedViewLevels();
                if ($user->authorise('core.admin')) {
                    $this->item->canAccess = true;
                }
                /*else {
                    if ($this->item->catid == 0) {
                        $this->item->canAccess = in_array($this->item->access, $userAccessLevels);
                    } else {
                        $this->item->canAccess = in_array($this->item->access, $userAccessLevels) && in_array($this->item->catAccess, $userAccessLevels);
                    }
                }* /
            }
        }
        return $this->item;
    }*/

    /**
     * Method to get the record form.
     *
     * @param   array    $data      Data for the form.
     * @param   boolean  $loadData  True if the form is to load its own data (default case), false if not.
     *
     * @return  mixed    A JForm object on success, false on failure
     *
     * @since   1.6
     */
    public function getForm($data = [], $loadData = true)
    {
        // Get the form.
        // Note we're probably using the form to provice assosiated labels to data fields for display.
        // To DRY use the admin form use:
        // JPATH_COMPONENT_ADMINISTRATOR . '/forms/trial.xml',
        // or if you need a separate site form, use:
        // JPATH_COMPONENT_SITE . '/forms/trial.xml',
        $form = $this->loadForm(
            'com_trials.form',
            JPATH_COMPONENT_ADMINISTRATOR . '/forms/trial.xml',
            [
                'control' => 'jform',
                'load_data' => $loadData
            ]
        );

        if (empty($form)) {
            $errors = $this->getErrors();
            throw new \Exception(implode("\n", $errors), 500);
        }

        return $form;
    }

    /**
     * Method to get a table object, load it if necessary.
     *
     * @param   string  $type    The table name. Optional.
     * @param   string  $prefix  The class prefix. Optional.
     * @param   array   $config  Configuration array for model. Optional.
     *
     * @return  Table  A Table object
     *
     * We need to override this - otherwise it would take 'Form' as the $name
     */

    /*public function getTable($name = 'Trial', $prefix = 'administrator', $options = array())
    {
        return parent::getTable($name, $prefix, $options);
    }*/
}