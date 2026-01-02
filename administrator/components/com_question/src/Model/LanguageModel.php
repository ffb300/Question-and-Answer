<?php
/**
 * @package     Joomla.Site
 * @subpackage  com_question
 *
 * @copyright   Copyright (C) 2026
 * @license     GNU General Public License version 2 or later
 */

namespace Question\Component\Question\Administrator\Model;

\defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Model\AdminModel;
use Joomla\CMS\Table\Table;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Filter\InputFilter;
use Joomla\Registry\Registry;

/**
 * Language Model for managing question languages
 *
 * @since  1.0.0
 */
class LanguageModel extends AdminModel
{
    /**
     * The type alias for this content type.
     *
     * @var    string
     */
    public $typeAlias = 'com_question.language';

    /**
     * Method to test whether a record can be deleted.
     *
     * @param   object  $record  A record object.
     *
     * @return  boolean  True if allowed to delete the record. Defaults to the permission set in the component.
     */
    protected function canDelete($record)
    {
        if (empty($record->id)) {
            return false;
        }

        // Check if there are questions using this language
        $db = $this->getDbo();
        $query = $db->getQuery(true)
            ->select('COUNT(*)')
            ->from($db->quoteName('#__question_questions'))
            ->where($db->quoteName('language') . ' = ' . $db->quote($record->lang_code));
        $db->setQuery($query);
        $count = $db->loadResult();

        if ($count > 0) {
            $this->setError(Text::_('COM_QUESTION_LANGUAGE_ERROR_IN_USE'));
            return false;
        }

        return Factory::getUser()->authorise('core.delete', 'com_question');
    }

    /**
     * Method to test whether a record can have its state edited.
     *
     * @param   object  $record  A record object.
     *
     * @return  boolean  True if allowed to change the state of the record. Defaults to the permission set in the component.
     */
    protected function canEditState($record)
    {
        return Factory::getUser()->authorise('core.edit.state', 'com_question');
    }

    /**
     * Method to get a table object, load it if necessary.
     *
     * @param   string  $name     The table name. Optional.
     * @param   string  $prefix   The class prefix. Optional.
     * @param   array   $options  Configuration array for model. Optional.
     *
     * @return  Table  A Table object
     */
    public function getTable($name = 'Language', $prefix = 'Administrator', $options = [])
    {
        return parent::getTable($name, $prefix, $options);
    }

    /**
     * Method to get the record form.
     *
     * @param   array    $data      Data for the form.
     * @param   boolean  $loadData  True if the form is to load its own data (default case), false if not.
     *
     * @return  \JForm|boolean  A JForm object on success, false on failure
     */
    public function getForm($data = [], $loadData = true)
    {
        // Get the form.
        $form = $this->loadForm(
            'com_question.language',
            'language',
            [
                'control' => 'jform',
                'load_data' => $loadData
            ]
        );

        if (empty($form)) {
            return false;
        }

        return $form;
    }

    /**
     * Method to get the data that should be injected in the form.
     *
     * @return  mixed  The data for the form.
     */
    protected function loadFormData()
    {
        // Check the session for previously entered form data.
        $app = Factory::getApplication();
        $data = $app->getUserState('com_question.edit.language.data', []);

        if (empty($data)) {
            $data = $this->getItem();
        }

        $this->preprocessData('com_question.language', $data);

        return $data;
    }

    /**
     * Prepare and sanitise the table prior to saving.
     *
     * @param   Table  $table  The Table object.
     *
     * @return  void
     */
    protected function prepareTable($table)
    {
        $date = Factory::getDate();

        if (empty($table->id)) {
            // Set the values
            $table->created = $date->toSql();

            // Set ordering to the last item if not set
            if (empty($table->ordering)) {
                $db = $this->getDbo();
                $query = $db->getQuery(true)
                    ->select('MAX(ordering)')
                    ->from($db->quoteName('#__question_languages'));
                $db->setQuery($query);
                $max = $db->loadResult();

                $table->ordering = $max + 1;
            }
        }
    }

    /**
     * A protected method to get a set of ordering conditions.
     *
     * @param   Table  $table  A Table object.
     *
     * @return  array  An array of conditions to add to ordering queries.
     */
    protected function getReorderConditions($table)
    {
        return [];
    }

    /**
     * Method to save the form data.
     *
     * @param   array  $data  The form data.
     *
     * @return  boolean  True on success.
     */
    public function save($data)
    {
        $input = Factory::getApplication()->getInput();
        $filter = InputFilter::getInstance();

        // Validate language code format
        if (empty($data['lang_code'])) {
            $this->setError(Text::_('COM_QUESTION_LANGUAGE_ERROR_CODE_REQUIRED'));
            return false;
        }

        if (!preg_match('/^[a-z]{2}-[A-Z]{2}$/', $data['lang_code'])) {
            $this->setError(Text::_('COM_QUESTION_LANGUAGE_ERROR_CODE_FORMAT'));
            return false;
        }

        // Check for duplicate language code
        $db = $this->getDbo();
        $query = $db->getQuery(true)
            ->select('COUNT(*)')
            ->from($db->quoteName('#__question_languages'))
            ->where($db->quoteName('lang_code') . ' = ' . $db->quote($data['lang_code']));

        if (!empty($data['id'])) {
            $query->where($db->quoteName('id') . ' != ' . (int) $data['id']);
        }

        $db->setQuery($query);
        $count = $db->loadResult();

        if ($count > 0) {
            $this->setError(Text::_('COM_QUESTION_LANGUAGE_ERROR_CODE_EXISTS'));
            return false;
        }

        // Generate SEF if not provided
        if (empty($data['sef'])) {
            $data['sef'] = substr($data['lang_code'], 0, 2);
        }

        // Generate image if not provided
        if (empty($data['image'])) {
            $data['image'] = substr($data['lang_code'], 0, 2);
        }

        return parent::save($data);
    }

    /**
     * Method to get a single record.
     *
     * @param   integer  $pk  The id of the primary key.
     *
     * @return  mixed  Object on success, false on failure.
     */
    public function getItem($pk = null)
    {
        $item = parent::getItem($pk);

        if ($item) {
            // Convert the params field to an array.
            $registry = new Registry($item->params);
            $item->params = $registry->toArray();
        }

        return $item;
    }

    /**
     * Method to get published languages for frontend selection
     *
     * @return  array  Array of language objects
     */
    public function getPublishedLanguages()
    {
        $db = $this->getDbo();
        $query = $db->getQuery(true)
            ->select('id, lang_code, title, title_native, sef, image')
            ->from($db->quoteName('#__question_languages'))
            ->where($db->quoteName('published') . ' = 1')
            ->order($db->quoteName('ordering') . ' ASC');
        $db->setQuery($query);

        return $db->loadObjectList();
    }

    /**
     * Method to get language by code
     *
     * @param   string  $langCode  The language code
     *
     * @return  object  Language object
     */
    public function getLanguageByCode($langCode)
    {
        $db = $this->getDbo();
        $query = $db->getQuery(true)
            ->select('*')
            ->from($db->quoteName('#__question_languages'))
            ->where($db->quoteName('lang_code') . ' = ' . $db->quote($langCode))
            ->where($db->quoteName('published') . ' = 1');
        $db->setQuery($query);

        return $db->loadObject();
    }

    /**
     * Method to get language statistics
     *
     * @return  array  Array of language statistics
     */
    public function getLanguageStatistics()
    {
        $db = $this->getDbo();
        $query = $db->getQuery(true)
            ->select('l.lang_code, l.title, l.title_native')
            ->select('COUNT(q.id) as question_count')
            ->select('SUM(CASE WHEN q.published = 1 THEN 1 ELSE 0 END) as published_count')
            ->from($db->quoteName('#__question_languages', 'l'))
            ->join('LEFT', $db->quoteName('#__question_questions', 'q') . ' ON q.language = l.lang_code')
            ->where($db->quoteName('l.published') . ' = 1')
            ->group('l.id, l.lang_code, l.title, l.title_native')
            ->order($db->quoteName('l.ordering') . ' ASC');
        $db->setQuery($query);

        return $db->loadObjectList();
    }

    /**
     * Method to sync languages with Joomla core languages
     *
     * @return  void
     */
    public function syncWithJoomlaLanguages()
    {
        $db = $this->getDbo();
        
        // Get Joomla core languages
        $query = $db->getQuery(true)
            ->select('lang_code, title, title_native, sef, image')
            ->from($db->quoteName('#__languages'))
            ->where($db->quoteName('published') . ' = 1')
            ->order($db->quoteName('ordering') . ' ASC');
        $db->setQuery($query);
        $joomlaLanguages = $db->loadObjectList();

        foreach ($joomlaLanguages as $joomlaLang) {
            // Check if language already exists
            $query = $db->getQuery(true)
                ->select('id')
                ->from($db->quoteName('#__question_languages'))
                ->where($db->quoteName('lang_code') . ' = ' . $db->quote($joomlaLang->lang_code));
            $db->setQuery($query);
            $existingId = $db->loadResult();

            if (!$existingId) {
                // Insert new language
                $language = (object) [
                    'lang_code' => $joomlaLang->lang_code,
                    'title' => $joomlaLang->title,
                    'title_native' => $joomlaLang->title_native,
                    'sef' => $joomlaLang->sef,
                    'image' => $joomlaLang->image,
                    'published' => 1,
                    'ordering' => 0
                ];

                $db->insertObject('#__question_languages', $language);
            }
        }
    }
}
