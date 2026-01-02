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
use Joomla\CMS\Helper\TagsHelper;
use Joomla\CMS\Application\CMSApplication;

/**
 * Question Model for Questions
 *
 * @since  1.0.0
 */
class QuestionModel extends AdminModel
{
    /**
     * The type alias for this content type.
     *
     * @var    string
     */
    public $typeAlias = 'com_question.question';

    /**
     * Method to test whether a record can be deleted.
     *
     * @param   object  $record  A record object.
     *
     * @return  boolean  True if allowed to delete the record. Defaults to the permission set in the component.
     */
    protected function canDelete($record)
    {
        if (empty($record->id) || $record->published != -2) {
            return false;
        }

        if (!empty($record->catid)) {
            return Factory::getUser()->authorise('core.delete', 'com_question.category.' . (int) $record->catid);
        }

        return parent::canDelete($record);
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
        $user = Factory::getUser();

        // Check against the category.
        if (!empty($record->catid)) {
            return $user->authorise('core.edit.state', 'com_question.category.' . (int) $record->catid);
        }

        // Default to component settings if category not set.
        return parent::canEditState($record);
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
    public function getTable($name = 'Question', $prefix = 'Administrator', $options = [])
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
            'com_question.question',
            'question',
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
        $data = $app->getUserState('com_question.edit.question.data', []);

        if (empty($data)) {
            $data = $this->getItem();
        }

        $this->preprocessData('com_question.question', $data);

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
        $user = Factory::getUser();

        if (empty($table->id)) {
            // Set the values
            $table->created = $date->toSql();
            $table->created_by = $user->get('id');

            // Set ordering to the last item if not set
            if (empty($table->ordering)) {
                $db = $this->getDbo();
                $query = $db->getQuery(true)
                    ->select('MAX(ordering)')
                    ->from($db->quoteName('#__question_questions'));
                $db->setQuery($query);
                $max = $db->loadResult();

                $table->ordering = $max + 1;
            }
        } else {
            // Set the values
            $table->modified = $date->toSql();
            $table->modified_by = $user->get('id');
        }

        // Increment the content version number.
        $table->version++;
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
        $condition = [];

        if (!empty($table->catid)) {
            $condition[] = 'catid = ' . (int) $table->catid;
        }

        if (!empty($table->language)) {
            $condition[] = 'language = ' . $this->_db->quote($table->language);
        }

        return $condition;
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

        // Alter the title for save as copy
        if ($input->get('task') == 'save2copy') {
            $origTable = clone $this->getTable();
            $origTable->load($input->getInt('id'));

            if ($data['title'] == $origTable->title) {
                list($title, $alias) = $this->generateNewTitle($data['catid'], $data['alias'], $data['title']);
                $data['title'] = $title;
                $data['alias'] = $alias;
            } else {
                if ($data['alias'] == $origTable->alias) {
                    $data['alias'] = '';
                }
            }

            $data['published'] = 0;
        }

        // Automatic handling of alias for empty fields
        if (in_array($input->get('task'), ['apply', 'save', 'save2new']) && !(int) $data['id']) {
            if ($data['alias'] == null) {
                if (Factory::getConfig()->get('unicodeslugs') == 1) {
                    $data['alias'] = InputFilter::getInstance()->clean($data['title'], 'UNICODE');
                } else {
                    $data['alias'] = InputFilter::getInstance()->clean($data['title'], 'string');
                }

                $data['alias'] = str_replace(' ', '-', $data['alias']);
            }

            $table = clone $this->getTable();
            $data['alias'] = $table->generateAlias($data['alias'], $data['title']);
        }

        // Tags handling
        if (isset($data['tags']) && is_array($data['tags'])) {
            $data['tags'] = implode(',', $data['tags']);
        }

        return parent::save($data);
    }

    /**
     * Method to change the title & alias.
     *
     * @param   integer  $categoryId  The id of the category.
     * @param   string   $alias       The alias.
     * @param   string   $title       The title.
     *
     * @return  array  Contains the modified title and alias.
     */
    protected function generateNewTitle($categoryId, $alias, $title)
    {
        // Alter the title & alias
        $table = $this->getTable();

        while ($table->load(['alias' => $alias, 'catid' => $categoryId])) {
            $title = \Joomla\String\StringHelper::increment($title);
            $alias = \Joomla\String\StringHelper::increment($alias, 'dash');
        }

        return [$title, $alias];
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

            // Convert the metadata field to an array.
            $registry = new Registry($item->metadata);
            $item->metadata = $registry->toArray();

            // Get tags
            $item->tags = new TagsHelper();
            $item->tags->getTagIds($item->id, 'com_question.question');
        }

        return $item;
    }
}
