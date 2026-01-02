<?php
/**
 * @package     Joomla.Site
 * @subpackage  com_question
 *
 * @copyright   Copyright (C) 2026
 * @license     GNU General Public License version 2 or later
 */

namespace Question\Component\Question\Site\Controller;

\defined('_JEXEC') or die;

use Joomla\CMS\MVC\Controller\BaseController;
use Joomla\CMS\Factory;
use Joomla\CMS\Application\CMSApplication;
use Joomla\CMS\Language\Text;

/**
 * AJAX Controller for polling and other AJAX requests
 *
 * @since  1.0.0
 */
class AjaxController extends BaseController
{
    /**
     * Timeout for long polling (in seconds)
     */
    const LONG_POLL_TIMEOUT = 25;

    /**
     * Method to handle AJAX polling for updates
     *
     * @return  void
     */
    public function poll()
    {
        $app = Factory::getApplication();
        $input = $app->input;

        // Set JSON response
        header('Content-Type: application/json');

        // Check if live updates are enabled
        $params = $app->getParams('com_question');
        if (!$params->get('enable_live_updates', 1)) {
            $this->sendJsonResponse(['error' => 'Live updates disabled']);
            return;
        }

        // Get parameters
        $questionId = $input->getInt('question_id', 0);
        $lastTimestamp = $input->getInt('last_event_timestamp', 0);
        $longPoll = $input->getBool('longpoll', true);

        if (!$questionId) {
            $this->sendJsonResponse(['error' => 'Question ID required']);
            return;
        }

        // Get the event model
        $model = $this->getModel('Event', 'Administrator');

        // Check for new events
        if ($longPoll) {
            // Long polling - wait for events
            $events = $model->waitForEvents($questionId, $lastTimestamp, self::LONG_POLL_TIMEOUT);
        } else {
            // Regular polling - get current events
            $events = $model->getEvents($questionId, $lastTimestamp, 50);
        }

        // Format response
        $response = [
            'success' => true,
            'events' => [],
            'timestamp' => time()
        ];

        foreach ($events as $event) {
            $response['events'][] = [
                'event' => $event->event_type,
                'question_id' => $event->question_id,
                'timestamp' => $event->timestamp,
                'data' => $event->data
            ];
        }

        $this->sendJsonResponse($response);
    }

    /**
     * Method to handle voting via AJAX
     *
     * @return  void
     */
    public function vote()
    {
        $app = Factory::getApplication();
        $input = $app->input;

        // Check for request forgeries
        $this->checkToken();

        // Set JSON response
        header('Content-Type: application/json');

        // Get parameters
        $itemId = $input->getInt('item_id', 0);
        $itemType = $input->getWord('item_type', '');
        $vote = $input->getInt('vote', 0);

        if (!$itemId || !$itemType || !in_array($vote, [-1, 1])) {
            $this->sendJsonResponse(['error' => 'Invalid parameters']);
            return;
        }

        // Get the vote model
        $model = $this->getModel('Vote', 'Administrator');

        // Attempt to vote
        $result = $model->vote($itemId, $itemType, $vote);

        if ($result) {
            $voteCounts = $model->getVoteCounts($itemId, $itemType);
            $userVote = $model->getUserVote($itemId, $itemType);

            $this->sendJsonResponse([
                'success' => true,
                'votes_up' => $voteCounts->votes_up,
                'votes_down' => $voteCounts->votes_down,
                'user_vote' => $userVote
            ]);
        } else {
            $this->sendJsonResponse(['error' => $model->getError()]);
        }
    }

    /**
     * Method to mark answer as best via AJAX
     *
     * @return  void
     */
    public function markBest()
    {
        $app = Factory::getApplication();
        $input = $app->input;

        // Check for request forgeries
        $this->checkToken();

        // Set JSON response
        header('Content-Type: application/json');

        // Get parameters
        $answerId = $input->getInt('answer_id', 0);

        if (!$answerId) {
            $this->sendJsonResponse(['error' => 'Answer ID required']);
            return;
        }

        // Get the answer model
        $model = $this->getModel('Answer', 'Administrator');

        // Attempt to mark as best
        $result = $model->markAsBest($answerId);

        if ($result) {
            $this->sendJsonResponse(['success' => true]);
        } else {
            $this->sendJsonResponse(['error' => $model->getError()]);
        }
    }

    /**
     * Method to submit a new answer via AJAX
     *
     * @return  void
     */
    public function submitAnswer()
    {
        $app = Factory::getApplication();
        $input = $app->input;

        // Check for request forgeries
        $this->checkToken();

        // Set JSON response
        header('Content-Type: application/json');

        // Get parameters
        $questionId = $input->getInt('question_id', 0);
        $body = $input->get('body', '', 'raw');

        if (!$questionId || !$body) {
            $this->sendJsonResponse(['error' => 'Question ID and body are required']);
            return;
        }

        // Check rate limiting
        if (!$this->checkRateLimit('answer')) {
            $this->sendJsonResponse(['error' => 'Rate limit exceeded']);
            return;
        }

        // Get the answer model
        $model = $this->getModel('Answer', 'Administrator');

        // Prepare data
        $data = [
            'question_id' => $questionId,
            'body' => $body,
            'published' => 1,
            'created_by' => Factory::getUser()->id
        ];

        // Attempt to save
        $result = $model->save($data);

        if ($result) {
            $answerId = $model->getState($model->getName() . '.id');
            $this->sendJsonResponse([
                'success' => true,
                'answer_id' => $answerId,
                'message' => Text::_('COM_QUESTION_ANSWER_SUBMITTED_SUCCESSFULLY')
            ]);
        } else {
            $this->sendJsonResponse(['error' => $model->getError()]);
        }
    }

    /**
     * Method to submit a new question via AJAX
     *
     * @return  void
     */
    public function submitQuestion()
    {
        $app = Factory::getApplication();
        $input = $app->input;

        // Check for request forgeries
        $this->checkToken();

        // Set JSON response
        header('Content-Type: application/json');

        // Get parameters
        $title = $input->get('title', '', 'string');
        $body = $input->get('body', '', 'raw');
        $language = $input->get('language', '*', 'cmd');
        $catid = $input->getInt('catid', 0);
        $tags = $input->get('tags', [], 'array');

        if (!$title || !$body || !$catid) {
            $this->sendJsonResponse(['error' => 'Title, body, and category are required']);
            return;
        }

        // Check rate limiting
        if (!$this->checkRateLimit('question')) {
            $this->sendJsonResponse(['error' => 'Rate limit exceeded']);
            return;
        }

        // Get the question model
        $model = $this->getModel('Question', 'Administrator');

        // Prepare data
        $data = [
            'title' => $title,
            'body' => $body,
            'language' => $language,
            'catid' => $catid,
            'tags' => $tags,
            'published' => 1,
            'created_by' => Factory::getUser()->id
        ];

        // Attempt to save
        $result = $model->save($data);

        if ($result) {
            $questionId = $model->getState($model->getName() . '.id');
            $this->sendJsonResponse([
                'success' => true,
                'question_id' => $questionId,
                'message' => Text::_('COM_QUESTION_QUESTION_SUBMITTED_SUCCESSFULLY')
            ]);
        } else {
            $this->sendJsonResponse(['error' => $model->getError()]);
        }
    }

    /**
     * Method to get languages for frontend
     *
     * @return  void
     */
    public function getLanguages()
    {
        $app = Factory::getApplication();

        // Set JSON response
        header('Content-Type: application/json');

        // Get the language model
        $model = $this->getModel('Language', 'Administrator');
        $languages = $model->getPublishedLanguages();

        $this->sendJsonResponse([
            'success' => true,
            'languages' => $languages
        ]);
    }

    /**
     * Method to get categories for frontend
     *
     * @return  void
     */
    public function getCategories()
    {
        $app = Factory::getApplication();
        $input = $app->input;

        // Set JSON response
        header('Content-Type: application/json');

        $language = $input->get('language', '*', 'cmd');

        // Get categories
        $categories = $this->getCategoriesList($language);

        $this->sendJsonResponse([
            'success' => true,
            'categories' => $categories
        ]);
    }

    /**
     * Send JSON response and exit
     *
     * @param   array  $data  Response data
     *
     * @return  void
     */
    protected function sendJsonResponse($data)
    {
        echo json_encode($data);
        $app = Factory::getApplication();
        $app->close();
    }

    /**
     * Check rate limiting
     *
     * @param   string  $type  The type of content (question or answer)
     *
     * @return  boolean  True if rate limit is not exceeded
     */
    protected function checkRateLimit($type)
    {
        $app = Factory::getApplication();
        $params = $app->getParams('com_question');
        
        $rateLimit = $params->get('rate_limit_' . $type . 's', 5);
        $user = Factory::getUser();
        
        if ($user->guest) {
            return false; // Guests cannot post
        }

        // Check session-based rate limiting
        $session = $app->getSession();
        $sessionKey = 'question_rate_limit_' . $type . '_' . $user->id;
        $attempts = $session->get($sessionKey, 0);
        $lastAttempt = $session->get($sessionKey . '_time', 0);
        $now = time();

        // Reset counter if more than 1 hour has passed
        if ($now - $lastAttempt > 3600) {
            $attempts = 0;
        }

        if ($attempts >= $rateLimit) {
            return false;
        }

        // Increment counter
        $session->set($sessionKey, $attempts + 1);
        $session->set($sessionKey . '_time', $now);

        return true;
    }

    /**
     * Get categories list
     *
     * @param   string  $language  The language code
     *
     * @return  array  Array of categories
     */
    protected function getCategoriesList($language = '*')
    {
        $db = Factory::getDbo();
        $query = $db->getQuery(true)
            ->select('id, title, alias, level, path')
            ->from($db->quoteName('#__categories'))
            ->where($db->quoteName('extension') . ' = ' . $db->quote('com_question'))
            ->where($db->quoteName('published') . ' = 1');

        if ($language !== '*') {
            $query->where($db->quoteName('language') . ' IN (' . $db->quote('*') . ', ' . $db->quote($language) . ')');
        }

        $query->order($db->quoteName('lft') . ' ASC');
        $db->setQuery($query);

        return $db->loadObjectList();
    }

    /**
     * Method to get a model object, loading it if required.
     *
     * @param   string  $name    The model name.
     * @param   string  $prefix  The class prefix.
     * @param   array   $config  Configuration array for model.
     *
     * @return  object  The model.
     */
    public function getModel($name = 'Event', $prefix = 'Administrator', $config = ['ignore_request' => true])
    {
        return parent::getModel($name, $prefix, $config);
    }
}
