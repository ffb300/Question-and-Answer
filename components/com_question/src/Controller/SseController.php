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
 * Server-Sent Events Controller for live updates
 *
 * @since  1.0.0
 */
class SseController extends BaseController
{
    /**
     * Maximum execution time for SSE (in seconds)
     */
    const MAX_EXECUTION_TIME = 300;

    /**
     * Heartbeat interval (in seconds)
     */
    const HEARTBEAT_INTERVAL = 20;

    /**
     * Method to handle SSE stream
     *
     * @return  void
     */
    public function stream()
    {
        $app = Factory::getApplication();
        $input = $app->input;

        // Check if live updates are enabled
        $params = $app->getParams('com_question');
        if (!$params->get('enable_live_updates', 1)) {
            http_response_code(403);
            echo 'data: ' . json_encode(['error' => 'Live updates disabled']) . "\n\n";
            return;
        }

        // Get parameters
        $questionId = $input->getInt('question_id', 0);
        $lastTimestamp = $input->getInt('last_event_timestamp', 0);

        if (!$questionId) {
            http_response_code(400);
            echo 'data: ' . json_encode(['error' => 'Question ID required']) . "\n\n";
            return;
        }

        // Set SSE headers
        $this->setSseHeaders();

        // Disable output buffering
        if (ob_get_level()) {
            ob_end_clean();
        }

        // Set time limit
        set_time_limit(self::MAX_EXECUTION_TIME);

        // Get the event model
        $model = $this->getModel('Event', 'Administrator');

        $startTime = time();
        $lastHeartbeat = time();

        // Main SSE loop
        while (time() - $startTime < self::MAX_EXECUTION_TIME) {
            // Check for new events
            $events = $model->getEvents($questionId, $lastTimestamp, 10);

            if (!empty($events)) {
                foreach ($events as $event) {
                    $this->sendSseEvent($event);
                    $lastTimestamp = $event->timestamp;
                }
            }

            // Send heartbeat
            if (time() - $lastHeartbeat >= self::HEARTBEAT_INTERVAL) {
                $this->sendHeartbeat();
                $lastHeartbeat = time();
            }

            // Flush output
            if (ob_get_level()) {
                ob_flush();
            }
            flush();

            // Sleep briefly to prevent excessive CPU usage
            usleep(500000); // 0.5 seconds
        }
    }

    /**
     * Set SSE headers
     *
     * @return  void
     */
    protected function setSseHeaders()
    {
        header('Content-Type: text/event-stream');
        header('Cache-Control: no-cache');
        header('Connection: keep-alive');
        header('X-Accel-Buffering: no'); // For nginx
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Headers: Cache-Control');
    }

    /**
     * Send an SSE event
     *
     * @param   object  $event  The event object
     *
     * @return  void
     */
    protected function sendSseEvent($event)
    {
        $payload = [
            'event' => $event->event_type,
            'question_id' => $event->question_id,
            'timestamp' => $event->timestamp,
            'data' => $event->data
        ];

        echo "event: {$event->event_type}\n";
        echo "data: " . json_encode($payload) . "\n\n";
    }

    /**
     * Send heartbeat event
     *
     * @return  void
     */
    protected function sendHeartbeat()
    {
        $payload = [
            'event' => 'heartbeat',
            'timestamp' => time(),
            'data' => []
        ];

        echo "event: heartbeat\n";
        echo "data: " . json_encode($payload) . "\n\n";
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
