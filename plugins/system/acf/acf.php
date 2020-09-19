<?php

/**
 * @package         Advanced Custom Fields
 * @version         1.2.0-RC2 Pro
 * 
 * @author          Tassos Marinos <info@tassos.gr>
 * @link            http://www.tassos.gr
 * @copyright       Copyright © 2019 Tassos Marinos All Rights Reserved
 * @license         GNU GPLv3 <http://www.gnu.org/licenses/gpl.html> or later
*/

defined('_JEXEC') or die('Restricted access');

/**
 *  Advanced Custom Fields System Plugin
 */
class PlgSystemACF extends JPlugin
{
    /**
     *  Auto load plugin's language file
     *
     *  @var  boolean
     */
    protected $autoloadLanguage = true;
    
    /**
     *  Application Object
     *
     *  @var  object
     */
    protected $app;

    /**
     *  The loaded indicator of helper
     *
     *  @var  boolean
     */
    private $init;
    
    
    /**
     *  onCustomFieldsBeforePrepareField Event
     */
    public function onCustomFieldsBeforePrepareField($context, $item, &$field)
    {
        // Validate support component/views
        if (!in_array($context, ['com_content.article', 'com_dpcalendar.event']))
        {
            return;
        }

        // Get Helper
        if (!$this->getHelper())
        {
            return;
        }

        // Only if assignments option is enabled in the plugin settings
        if (!$this->params->get('assignments', true))
        {
            return;
        }

        ACFHelper::checkAssignments($field);
    }

    /**
     *  Append publishing assignments XML to the
     *
     *  @param   JForm  $form  The form to be altered.
     *  @param   mixed  $data  The associated data for the form.
     *
     *  @return  boolean
     */
	public function onContentPrepareForm(JForm $form, $data)
    {
        // Run only on backend
        if (!$this->app->isClient('administrator') || !$form instanceof JForm)
        {
            return;
        }

        // We support only com_content for now
        if (!in_array($form->getName(), ['com_fields.field.com_content.article', 'com_fields.fieldcom_content.article', 'com_fields.field.com_dpcalendar.event']))
        {
            return;
        }

        // Only if assignments option is enabled in the plugin settings
        if (!$this->params->get('assignments', true))
        {
            return;
        }

        $form->addFieldPath(JPATH_PLUGINS . '/system/nrframework/fields');
        $form->addFieldPath(JPATH_PLUGINS . '/system/nrframework/NRFramework/Fields');
        $form->loadFile(__DIR__ . '/form/assignments.xml', false);

        return true;
    }
    

    /**
     *  Loads the helper classes of plugin
     *
     *  @return  bool
     */
    private function getHelper()
    {
        // Return if is helper is already loaded
        if ($this->init)
        {
            return true;
        }

        // Return if we are not in frontend
        if (!$this->app->isClient('site'))
        {
            return false;
        }

        // Load Novarain Framework
        if (!@include_once(JPATH_PLUGINS . '/system/nrframework/autoload.php'))
        {
            return;
        }

        // Load Plugin Helper
        JLoader::register('ACFHelper', __DIR__ . '/helper/helper.php');

        return ($this->init = true);
    }
}
