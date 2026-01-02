<?php
/**
 * @package     Joomla.Site
 * @subpackage  com_question
 *
 * @copyright   Copyright (C) 2026
 * @license     GNU General Public License version 2 or later
 */

namespace Question\Component\Question\Site\Model;

\defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Model\ItemModel;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Table\Table;
use Joomla\Registry\Registry;

/**
 * Question model for the Question component
 *
 * @since  1.0.0
 */
class QuestionModel extends ItemModel
{
    /**
     * Model context string.
     *
     * @var        string
     */
    protected $_context = 'com_question.question';

    /**
     * Method to auto-populate the model state.
     *
     * Note. Calling getState in this method will result in recursion.
     *
     * @return  void
     */
    protected function populateState()
    {
        $app = Factory::getApplication();
        $user = Factory::getUser();

        // Load state from the request
        $pk = $app->input->getInt('id');
        $this->setState('question.id', $pk);

        // Load the parameters.
        $params = $app->getParams();
        $this->setState('params', $params);

        // Set the item id
        $this->setId($pk);
    }

    /**
     * Method to set the item identifier.
     *
     * @param   integer  $id  The identifier of the item.
     *
     * @return  void
     */
    public function setId($id)
    {
        // Set id and wipe data
        $this->_id = $id;
        $this->_item = null;
    }

    /**
     * Method to get an object.
     *
     * @param   integer  $id  The id of the object to get.
     *
     * @return  mixed  Object on success, false on failure.
     */
    public function getItem($id = null)
    {
        if ($this->_item === null) {
            $this->_item = false;

            if (empty($id)) {
                $id = $this->getState('question.id');
            }

            // Get a level row instance.
            $table = $this->getTable();

            // Attempt to load the row.
            if ($table->load($id)) {
                // Check published state.
                $published = $table->published;
                $date = Factory::getDate();

                if ($published != 1) {
                    if ($published != 0) {
                        return $this->_item;
                    }
                }

                // Convert to the JObject before adding other data.
                $properties = $table->getProperties(1);
                $this->_item = \Joomla\Utilities\ArrayHelper::toObject($properties, 'JObject');

                // Convert the params field to an array.
                $registry = new Registry($this->_item->params);
                $this->_item->params = $registry->toArray();

                // Convert the metadata field to an array.
                $registry = new Registry($this->_item->metadata);
                $this->_item->metadata = $registry->toArray();

                // Get author details
                $user = Factory::getUser($this->_item->created_by);
                $this->_item->author_name = $user->name;
                $this->_item->author_username = $user->username;

                // Get category details
                $db = $this->getDbo();
                $query = $db->getQuery(true)
                    ->select('title, alias, published')
                    ->from($db->quoteName('#__categories'))
                    ->where($db->quoteName('id') . ' = ' . (int) $this->_item->catid);
                $db->setQuery($query);
                $category = $db->loadObject();

                if ($category) {
                    $this->_item->category_title = $category->title;
                    $this->_item->category_alias = $category->alias;
                }

                // Get language details
                $query = $db->getQuery(true)
                    ->select('title, title_native')
                    ->from($db->quoteName('#__question_languages'))
                    ->where($db->quoteName('lang_code') . ' = ' . $db->quote($this->_item->language))
                    ->where($db->quoteName('published') . ' = 1');
                $db->setQuery($query);
                $language = $db->loadObject();

                if ($language) {
                    $this->_item->language_title = $language->title;
                    $this->_item->language_native = $language->title_native;
                }

                // Get user's vote
                if (!Factory::getUser()->guest) {
                    $query = $db->getQuery(true)
                        ->select('vote')
                        ->from($db->quoteName('#__question_votes'))
                        ->where($db->quoteName('item_id') . ' = ' . (int) $this->_item->id)
                        ->where($db->quoteName('item_type') . ' = ' . $db->quote('question'))
                        ->where($db->quoteName('user_id') . ' = ' . (int) Factory::getUser()->id);
                    $db->setQuery($query);
                    $this->_item->user_vote = (int) $db->loadResult();
                } else {
                    $this->_item->user_vote = 0;
                }

                // Increment hit counter
                $this->hit();

                // Format dates
                $this->_item->created_relative = $this->getRelativeTime($this->_item->created);
                $this->_item->modified_relative = $this->getRelativeTime($this->_item->modified);
            } else {
                throw new \Exception(Text::_('COM_QUESTION_QUESTION_NOT_FOUND'), 404);
            }
        }

        return $this->_item;
    }

    /**
     * Get the answers for this question
     *
     * @return  array  Array of answer objects
     */
    public function getAnswers()
    {
        $question = $this->getItem();
        $user = Factory::getUser();
        $db = $this->getDbo();

        $query = $db->getQuery(true)
            ->select('a.*')
            ->select('u.name as author_name, u.username as author_username')
            ->from($db->quoteName('#__question_answers', 'a'))
            ->join('LEFT', $db->quoteName('#__users', 'u') . ' ON u.id = a.created_by')
            ->where($db->quoteName('a.question_id') . ' = ' . (int) $question->id)
            ->where($db->quoteName('a.published') . ' = 1')
            ->order($db->quoteName('a.is_best') . ' DESC, ' . $db->quoteName('a.created') . ' ASC');
        $db->setQuery($query);
        $answers = $db->loadObjectList();

        // Get user votes for each answer
        if (!$user->guest && $answers) {
            $answerIds = array_map(function($answer) { return $answer->id; }, $answers);
            
            $query = $db->getQuery(true)
                ->select('item_id, vote')
                ->from($db->quoteName('#__question_votes'))
                ->where($db->quoteName('item_id') . ' IN (' . implode(',', $answerIds) . ')')
                ->where($db->quoteName('item_type') . ' = ' . $db->quote('answer'))
                ->where($db->quoteName('user_id') . ' = ' . (int) $user->id);
            $db->setQuery($query);
            $votes = $db->loadObjectList('item_id');

            foreach ($answers as &$answer) {
                $answer->user_vote = isset($votes[$answer->id]) ? $votes[$answer->id]->vote : 0;
                $answer->created_relative = $this->getRelativeTime($answer->created);
            }
        }

        return $answers;
    }

    /**
     * Get related questions
     *
     * @param   integer  $limit  Number of related questions to return
     *
     * @return  array  Array of related question objects
     */
    public function getRelatedQuestions($limit = 5)
    {
        $question = $this->getItem();
        $user = Factory::getUser();
        $db = $this->getDbo();

        // Get tags from current question
        $tags = [];
        if (!empty($question->tags)) {
            $tags = explode(',', $question->tags);
            $tags = array_filter($tags);
        }

        $query = $db->getQuery(true)
            ->select('q.id, q.title, q.alias, q.created, q.hits, q.votes_up, q.votes_down')
            ->select('COUNT(a.id) as answer_count')
            ->from($db->quoteName('#__question_questions', 'q'))
            ->join('LEFT', $db->quoteName('#__question_answers', 'a') . ' ON a.question_id = q.id AND a.published = 1')
            ->where($db->quoteName('q.id') . ' != ' . (int) $question->id)
            ->where($db->quoteName('q.published') . ' = 1')
            ->where($db->quoteName('q.access') . ' IN (' . implode(',', $user->getAuthorisedViewLevels()) . ')')
            ->where($db->quoteName('q.catid') . ' = ' . (int) $question->catid);

        // If question has tags, prioritize questions with similar tags
        if (!empty($tags)) {
            $tagConditions = [];
            foreach ($tags as $tag) {
                $tagConditions[] = $db->quoteName('q.tags') . ' LIKE ' . $db->quote('%' . $db->escape($tag) . '%');
            }
            if (!empty($tagConditions)) {
                $query->where('(' . implode(' OR ', $tagConditions) . ')');
            }
        }

        $query->group($db->quoteName('q.id'))
            ->order($db->quoteName('q.votes_up') . ' DESC, ' . $db->quoteName('q.created') . ' DESC')
            ->setLimit($limit);

        $db->setQuery($query);
        $related = $db->loadObjectList();

        foreach ($related as &$item) {
            $item->url = Route::_('index.php?option=com_question&view=question&id=' . $item->id . ':' . $item->alias);
            $item->created_relative = $this->getRelativeTime($item->created);
        }

        return $related;
    }

    /**
     * Increment the hit counter for the question
     *
     * @return  boolean  True on success
     */
    public function hit()
    {
        $question = $this->getItem();
        $db = $this->getDbo();

        $query = $db->getQuery(true)
            ->update($db->quoteName('#__question_questions'))
            ->set($db->quoteName('hits') . ' = ' . $db->quoteName('hits') . ' + 1')
            ->where($db->quoteName('id') . ' = ' . (int) $question->id);
        $db->setQuery($query);

        return $db->execute();
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
     * Get relative time string
     *
     * @param   string  $date  The date string
     *
     * @return  string  Relative time string
     */
    protected function getRelativeTime($date)
    {
        $time = strtotime($date);
        $now = time();
        $diff = $now - $time;

        if ($diff < 60) {
            return Text::_('COM_QUESTION_TIME_JUST_NOW');
        } elseif ($diff < 3600) {
            return Text::plural('COM_QUESTION_TIME_MINUTES_AGO', floor($diff / 60));
        } elseif ($diff < 86400) {
            return Text::plural('COM_QUESTION_TIME_HOURS_AGO', floor($diff / 3600));
        } elseif ($diff < 604800) {
            return Text::plural('COM_QUESTION_TIME_DAYS_AGO', floor($diff / 86400));
        } else {
            return Factory::getDate($date)->format(Text::_('DATE_FORMAT_LC3'));
        }
    }

    /**
     * Method to check if user can ask questions
     *
     * @return  boolean  True if user can ask questions
     */
    public function canAskQuestion()
    {
        $user = Factory::getUser();
        
        if ($user->guest) {
            return false;
        }

        return $user->authorise('ask.question', 'com_question');
    }

    /**
     * Method to check if user can answer questions
     *
     * @return  boolean  True if user can answer questions
     */
    public function canAnswerQuestion()
    {
        $user = Factory::getUser();
        
        if ($user->guest) {
            return false;
        }

        return $user->authorise('answer.question', 'com_question');
    }

    /**
     * Method to check if user can vote
     *
     * @return  boolean  True if user can vote
     */
    public function canVote()
    {
        $user = Factory::getUser();
        
        if ($user->guest) {
            return false;
        }

        return $user->authorise('vote.answer', 'com_question');
    }
}
