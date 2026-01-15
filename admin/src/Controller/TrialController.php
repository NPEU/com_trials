<?php
/**
 * @package     Joomla.Administrator
 * @subpackage  com_trials
 *
 * @copyright   Copyright (C) NPEU 2025.
 * @license     MIT License; see LICENSE.md
 */

namespace NPEU\Component\Trials\Administrator\Controller;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Controller\FormController;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\Versioning\VersionableControllerTrait;


class TrialController extends FormController
{
    #use VersionableControllerTrait;

    /**
    * Implement to allowAdd or not
    *
    * Not used at this time (but you can look at how other components use it....)
    * Overwrites: JControllerForm::allowAdd
    *
    * @param array $data
    * @return bool
    */
    protected function allowAdd($data = [])
    {
        return parent::allowAdd($data);
    }
    /**
    * Implement to allow edit or not
    * Overwrites: JControllerForm::allowEdit
    *
    * @param array $data
    * @param string $key
    * @return bool
    */
    protected function allowEdit($data = [], $key = 'id')
    {
        $id = isset( $data[ $key ] ) ? $data[ $key ] : 0;
        if( !empty( $id ) )
        {
            return Factory::getApplication()->getIdentity()->authorise( "core.edit", "com_trials.trial." . $id );
        }
    }

    /*public function batch($model = null)
    {
        $model = $this->getModel('trial');
        $this->setRedirect((string)Uri::getInstance());
        return parent::batch($model);
    }*/
}
