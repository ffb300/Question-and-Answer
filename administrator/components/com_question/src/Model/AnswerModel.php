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
 * Answer Model for Answers
 *
 * @since  1.0.0
 */
class AnswerModel extends AdminModel
{
    /**
     * The type alias for this content type.
     *
     * @var    string
     */
    public $typeAlias = 'com_question.answer';

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

        $user = Factory::getUser();

        // Check if user can delete their own answer or has moderate permissions
        if ($user->id == $record->created_by) {
            return $user->authorise('core.delete', 'com_question');
        }

        return $user->authorise('moderate.content', 'com_question');
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

        // Check if user can edit their own answer or has moderate permissions
        if ($user->id == $record->created_by) {
            return $user->authorise('core.edit.state', 'com_question');
        }

        return $user->authorise('moderate.content', 'com_question');
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
    public function getTable($name = 'Answer', $prefix = 'Administrator', $options = [])
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
            'com_question.answer',
            'answer',
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
        $data = $app->getUserState('com_question.edit.answer.data', []);

        if (empty($data)) {
            $data = $this->getItem();
        }

        $this->preprocessData('com_question.answer', $data);

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
                    ->from($db->quoteName('#__question_answers'))
                    ->where($db->quoteName('question_id') . ' = ' . (int) $table->question_id);
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

        if (!empty($table->question_id)) {
            $condition[] = 'question_id = ' . (int) $table->question_id;
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

            $data['published'] = 0;
        }

        // Validate question_id exists
        if (empty($data['question_id'])) {
            $this->setError(Text::_('COM_QUESTION_ANSWER_ERROR_QUESTION_ID_REQUIRED'));
            return false;
        }

        // Check if question exists
        $db = $this->getDbo();
        $query = $db->getQuery(true)
            ->select('id, published')
            ->from($db->quoteName('#__question_questions'))
            ->where($db->quoteName('id') . ' = ' . (int) $data['question_id']);
        $db->setQuery($query);
        $question = $db->loadObject();

        if (!$question) {
            $this->setError(Text::_('COM_QUESTION_ANSWER_ERROR_QUESTION_NOT_FOUND'));
            return false;
        }

        // Set created_by if not set
        if (empty($data['created_by'])) {
            $data['created_by'] = Factory::getUser()->id;
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

            // Get question details
            $db = $this->getDbo();
            $query = $db->getQuery(true)
                ->select('title, alias')
                ->from($db->quoteName('#__question_questions'))
                ->where($db->quoteName('id') . ' = ' . (int) $item->question_id);
            $db->setQuery($query);
            $item->question = $db->loadObject();
        }

        return $item;
    }

    /**
     * Method to mark an answer as best
     *
     * @param   integer  $answerId  The answer ID
     *
     * @return  boolean  True on success
     */
    public function markAsBest($answerId)
    {
        $user = Factory::getUser();
        $db = $this->getDbo();

        // Get the answer and question
        $query = $db->getQuery(true)
            ->select('a.id, a.question_id, q.created_by as question_author')
            ->from($db->quoteName('#__question_answers', 'a'))
            ->join('INNER', $db->quoteName('#__question_questions', 'q') . ' ON q.id = a.question_id')
            ->where($db->quoteName('a.id') . ' = ' . (int) $answerId);
        $db->setQuery($query);
        $answer = $db->loadObject();

        if (!$answer) {
            $this->setError(Text::_('COM_QUESTION_ANSWER_NOT_FOUND'));
            return false;
        }

        // Check if user can mark as best (question author or moderator)
        if ($user->id != $answer->question_author && !$user->authorise('moderate.content', 'com_question')) {
            $this->setError(Text::_('COM_QUESTION_ANSWER_ERROR_CANNOT_MARK_BEST'));
            return false;
        }

        // Start transaction
        $db->transactionStart();

        try {
            // Remove best flag from all answers for this question
            $query = $db->getQuery(true)
                ->update($db->quoteName('#__question_answers'))
                ->set($db->quoteName('is_best') . ' = 0')
                ->where($db->quoteName('question_id') . ' = ' . (int) $answer->question_id);
            $db->setQuery($query);
            $db->execute();

            // Set this answer as best
            $query = $db->getQuery(true)
                ->update($db->quoteName('#__question_answers'))
                ->set($db->quoteName('is_best') . ' = 1')
                ->where($db->quoteName('id') . ' = ' . (int) $answerId);
            $db->setQuery($query);
            $db->execute();

            // Create event for live update
            $this->createEvent('best_answer', $answer->question_id, $answerId, $user->id);

            $db->transactionCommit();

            return true;
        } catch (\Exception $e) {
            $db->transactionRollback();
            $this->setError($e->getMessage());
            return false;
        }
    }

    /**
     * Create an event for live updates
     *
     * @param   string   $eventType   The event type
     * @param   integer  $questionId  The question ID
     * @param   integer  $itemId      The item ID
     * @param   integer  $userId      The user ID
     * @param   array    $data        Additional data
     *
     * @return  void
     */
    protected function createEvent($eventType, $questionId, $itemId, $userId, $data = [])
    {
        $db = $this->getDbo();
        $event = (object) [
            'event_type' => $eventType,
            'question_id' => $questionId,
            'item_id' => $itemId,
            'user_id' => $userId,
            'data' => json_encode($data),
            'created' => Factory::getDate()->toSql(),
            'processed' => 0
        ];

        $db->insertObject('#__question_events', $event);
    }
}
