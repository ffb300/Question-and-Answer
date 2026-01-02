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
use Joomla\CMS\MVC\Model\BaseDatabaseModel;
use Joomla\CMS\Language\Text;

/**
 * Event Model for handling live update events
 *
 * @since  1.0.0
 */
class EventModel extends BaseDatabaseModel
{
    /**
     * Event types
     */
    const EVENT_NEW_ANSWER = 'new_answer';
    const EVENT_VOTE_UPDATE = 'vote_update';
    const EVENT_QUESTION_UPDATE = 'question_update';
    const EVENT_MODERATION_UPDATE = 'moderation_update';
    const EVENT_BEST_ANSWER = 'best_answer';

    /**
     * Method to create a new event
     *
     * @param   string   $eventType   The event type
     * @param   integer  $questionId  The question ID
     * @param   integer  $itemId      The item ID (optional)
     * @param   integer  $userId      The user ID (optional)
     * @param   array    $data        Additional event data (optional)
     *
     * @return  integer  The event ID
     */
    public function createEvent($eventType, $questionId, $itemId = 0, $userId = 0, $data = [])
    {
        $db = $this->getDbo();
        
        $event = (object) [
            'event_type' => $eventType,
            'question_id' => $questionId,
            'item_id' => $itemId,
            'user_id' => $userId ?: Factory::getUser()->id,
            'data' => json_encode($data),
            'created' => Factory::getDate()->toSql(),
            'processed' => 0
        ];

        $db->insertObject('#__question_events', $event);

        return $db->insertid();
    }

    /**
     * Method to get events for a question since a specific timestamp
     *
     * @param   integer  $questionId          The question ID
     * @param   integer  $lastEventTimestamp  The last event timestamp (Unix timestamp)
     * @param   integer  $limit               Maximum number of events to return
     *
     * @return  array  Array of event objects
     */
    public function getEvents($questionId, $lastEventTimestamp = 0, $limit = 50)
    {
        $db = $this->getDbo();
        
        // Convert timestamp to SQL datetime
        $lastEventDate = $lastEventTimestamp ? Factory::getDate($lastEventTimestamp)->toSql() : '1970-01-01 00:00:00';
        
        $query = $db->getQuery(true)
            ->select('e.*')
            ->select('u.name as user_name, u.username as user_username')
            ->from($db->quoteName('#__question_events', 'e'))
            ->join('LEFT', $db->quoteName('#__users', 'u') . ' ON u.id = e.user_id')
            ->where($db->quoteName('e.question_id') . ' = ' . (int) $questionId)
            ->where($db->quoteName('e.created') . ' > ' . $db->quote($lastEventDate))
            ->order($db->quoteName('e.created') . ' ASC');
            
        if ($limit > 0) {
            $query->setLimit($limit);
        }
        
        $db->setQuery($query);
        $events = $db->loadObjectList();

        // Decode JSON data and format events
        foreach ($events as $event) {
            $event->data = json_decode($event->data, true) ?: [];
            $event->timestamp = strtotime($event->created);
            
            // Add formatted data based on event type
            $this->formatEvent($event);
        }

        return $events;
    }

    /**
     * Method to get the latest event timestamp for a question
     *
     * @param   integer  $questionId  The question ID
     *
     * @return  integer  Unix timestamp of the latest event
     */
    public function getLatestEventTimestamp($questionId)
    {
        $db = $this->getDbo();
        $query = $db->getQuery(true)
            ->select('MAX(created)')
            ->from($db->quoteName('#__question_events'))
            ->where($db->quoteName('question_id') . ' = ' . (int) $questionId);
        $db->setQuery($query);
        $latestDate = $db->loadResult();

        return $latestDate ? strtotime($latestDate) : 0;
    }

    /**
     * Method to check for new events (for long polling)
     *
     * @param   integer  $questionId          The question ID
     * @param   integer  $lastEventTimestamp  The last event timestamp
     * @param   integer  $timeout             Timeout in seconds
     *
     * @return  array  Array of events or empty array if timeout
     */
    public function waitForEvents($questionId, $lastEventTimestamp, $timeout = 25)
    {
        $startTime = time();
        $maxTime = $startTime + $timeout;

        while (time() < $maxTime) {
            $events = $this->getEvents($questionId, $lastEventTimestamp, 1);
            
            if (!empty($events)) {
                return $events;
            }

            // Sleep for 1 second before checking again
            sleep(1);
        }

        return [];
    }

    /**
     * Method to clean up old events
     *
     * @param   integer  $days  Number of days to keep events
     *
     * @return  integer  Number of deleted events
     */
    public function cleanupOldEvents($days = 30)
    {
        $db = $this->getDbo();
        $cutoffDate = Factory::getDate('-' . $days . ' days')->toSql();
        
        $query = $db->getQuery(true)
            ->delete($db->quoteName('#__question_events'))
            ->where($db->quoteName('created') . ' < ' . $db->quote($cutoffDate));
        $db->setQuery($query);
        
        $db->execute();
        
        return $db->getAffectedRows();
    }

    /**
     * Method to get event statistics
     *
     * @param   integer  $days  Number of days to analyze
     *
     * @return  array  Event statistics
     */
    public function getEventStatistics($days = 7)
    {
        $db = $this->getDbo();
        $cutoffDate = Factory::getDate('-' . $days . ' days')->toSql();
        
        // Get total events by type
        $query = $db->getQuery(true)
            ->select('event_type, COUNT(*) as count')
            ->from($db->quoteName('#__question_events'))
            ->where($db->quoteName('created') . ' >= ' . $db->quote($cutoffDate))
            ->group($db->quoteName('event_type'))
            ->order($db->quoteName('count') . ' DESC');
        $db->setQuery($query);
        $byType = $db->loadObjectList();
        
        // Get events by day
        $query = $db->getQuery(true)
            ->select('DATE(created) as date, COUNT(*) as count')
            ->from($db->quoteName('#__question_events'))
            ->where($db->quoteName('created') . ' >= ' . $db->quote($cutoffDate))
            ->group('DATE(created)')
            ->order($db->quoteName('date') . ' ASC');
        $db->setQuery($query);
        $byDay = $db->loadObjectList();
        
        // Get top questions with most events
        $query = $db->getQuery(true)
            ->select('e.question_id, q.title, COUNT(*) as event_count')
            ->from($db->quoteName('#__question_events', 'e'))
            ->join('INNER', $db->quoteName('#__question_questions', 'q') . ' ON q.id = e.question_id')
            ->where($db->quoteName('e.created') . ' >= ' . $db->quote($cutoffDate))
            ->group('e.question_id, q.title')
            ->order($db->quoteName('event_count') . ' DESC')
            ->setLimit(10);
        $db->setQuery($query);
        $topQuestions = $db->loadObjectList();
        
        return [
            'by_type' => $byType,
            'by_day' => $byDay,
            'top_questions' => $topQuestions,
            'total_events' => array_sum(array_column($byType, 'count'))
        ];
    }

    /**
     * Format an event object with additional data
     *
     * @param   object  $event  The event object to format
     *
     * @return  void
     */
    protected function formatEvent($event)
    {
        switch ($event->event_type) {
            case self::EVENT_NEW_ANSWER:
                $this->formatNewAnswerEvent($event);
                break;
                
            case self::EVENT_VOTE_UPDATE:
                $this->formatVoteUpdateEvent($event);
                break;
                
            case self::EVENT_QUESTION_UPDATE:
                $this->formatQuestionUpdateEvent($event);
                break;
                
            case self::EVENT_MODERATION_UPDATE:
                $this->formatModerationUpdateEvent($event);
                break;
                
            case self::EVENT_BEST_ANSWER:
                $this->formatBestAnswerEvent($event);
                break;
        }
    }

    /**
     * Format new answer event
     *
     * @param   object  $event  The event object
     *
     * @return  void
     */
    protected function formatNewAnswerEvent($event)
    {
        $db = $this->getDbo();
        $query = $db->getQuery(true)
            ->select('body, created_by')
            ->from($db->quoteName('#__question_answers'))
            ->where($db->quoteName('id') . ' = ' . (int) $event->item_id);
        $db->setQuery($query);
        $answer = $db->loadObject();

        if ($answer) {
            $event->data['answer_body'] = substr(strip_tags($answer->body), 0, 200) . '...';
            $event->data['answer_created_by'] = $answer->created_by;
        }
    }

    /**
     * Format vote update event
     *
     * @param   object  $event  The event object
     *
     * @return  void
     */
    protected function formatVoteUpdateEvent($event)
    {
        // Vote data is already in the data field
        if (!isset($event->data['new_votes_up'])) {
            $event->data['new_votes_up'] = 0;
        }
        if (!isset($event->data['new_votes_down'])) {
            $event->data['new_votes_down'] = 0;
        }
    }

    /**
     * Format question update event
     *
     * @param   object  $event  The event object
     *
     * @return  void
     */
    protected function formatQuestionUpdateEvent($event)
    {
        $db = $this->getDbo();
        $query = $db->getQuery(true)
            ->select('title, created_by')
            ->from($db->quoteName('#__question_questions'))
            ->where($db->quoteName('id') . ' = ' . (int) $event->item_id);
        $db->setQuery($query);
        $question = $db->loadObject();

        if ($question) {
            $event->data['question_title'] = $question->title;
            $event->data['question_created_by'] = $question->created_by;
        }
    }

    /**
     * Format moderation update event
     *
     * @param   object  $event  The event object
     *
     * @return  void
     */
    protected function formatModerationUpdateEvent($event)
    {
        // Moderation data is already in the data field
        if (!isset($event->data['action'])) {
            $event->data['action'] = 'unknown';
        }
    }

    /**
     * Format best answer event
     *
     * @param   object  $event  The event object
     *
     * @return  void
     */
    protected function formatBestAnswerEvent($event)
    {
        $db = $this->getDbo();
        $query = $db->getQuery(true)
            ->select('a.body, a.created_by, q.title')
            ->from($db->quoteName('#__question_answers', 'a'))
            ->join('INNER', $db->quoteName('#__question_questions', 'q') . ' ON q.id = a.question_id')
            ->where($db->quoteName('a.id') . ' = ' . (int) $event->item_id);
        $db->setQuery($query);
        $answer = $db->loadObject();

        if ($answer) {
            $event->data['answer_body'] = substr(strip_tags($answer->body), 0, 200) . '...';
            $event->data['answer_created_by'] = $answer->created_by;
            $event->data['question_title'] = $answer->title;
        }
    }
}
