<?php
/**
 * @package     Joomla.Site
 * @subpackage  com_question
 *
 * @copyright   Copyright (C) 2026
 * @license     GNU General Public License version 2 or later
 */

namespace Question\Component\Question\Administrator\Helper;

\defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Access\Access;
use Joomla\CMS\User\User;
use Joomla\CMS\Table\Table;
use Joomla\Database\DatabaseInterface;

/**
 * Question ACL Helper
 *
 * @since  1.0.0
 */
class QuestionHelper
{
    /**
     * Core actions
     */
    const ACTIONS = [
        'core.admin',
        'core.manage',
        'core.create',
        'core.delete',
        'core.edit',
        'core.edit.state',
        'core.edit.own',
        'ask.question',
        'answer.question',
        'vote.answer',
        'moderate.content',
        'view.live.updates'
    ];

    /**
     * Check if user can perform action on question
     *
     * @param   string   $action     The action to check
     * @param   integer  $questionId The question ID
     * @param   integer  $userId     The user ID (optional)
     *
     * @return  boolean  True if allowed
     */
    public static function canQuestionAction($action, $questionId = 0, $userId = null)
    {
        $user = $userId ? Factory::getUser($userId) : Factory::getUser();
        
        // Check core permission first
        if ($user->authorise($action, 'com_question')) {
            return true;
        }

        if (!$questionId) {
            return false;
        }

        // Get question details
        $db = Factory::getContainer()->get(DatabaseInterface::class);
        $query = $db->getQuery(true)
            ->select('created_by, catid, access')
            ->from($db->quoteName('#__question_questions'))
            ->where($db->quoteName('id') . ' = ' . (int) $questionId);
        $db->setQuery($query);
        $question = $db->loadObject();

        if (!$question) {
            return false;
        }

        // Check category permissions
        $assetKey = 'com_question.category.' . $question->catid;
        
        switch ($action) {
            case 'core.edit':
            case 'core.edit.own':
                // Can edit own content
                if ($question->created_by == $user->id && $user->authorise('core.edit.own', $assetKey)) {
                    return true;
                }
                break;

            case 'core.delete':
                // Can delete own content
                if ($question->created_by == $user->id && $user->authorise('core.delete', $assetKey)) {
                    return true;
                }
                break;

            case 'answer.question':
                // Can answer if can create in category
                if ($user->authorise('core.create', $assetKey)) {
                    return true;
                }
                break;

            case 'vote.answer':
                // Can vote if can view content
                if ($user->authorise('core.view', $assetKey)) {
                    return true;
                }
                break;
        }

        return false;
    }

    /**
     * Check if user can perform action on answer
     *
     * @param   string   $action   The action to check
     * @param   integer  $answerId The answer ID
     * @param   integer  $userId   The user ID (optional)
     *
     * @return  boolean  True if allowed
     */
    public static function canAnswerAction($action, $answerId = 0, $userId = null)
    {
        $user = $userId ? Factory::getUser($userId) : Factory::getUser();
        
        // Check core permission first
        if ($user->authorise($action, 'com_question')) {
            return true;
        }

        if (!$answerId) {
            return false;
        }

        // Get answer details
        $db = Factory::getContainer()->get(DatabaseInterface::class);
        $query = $db->getQuery(true)
            ->select('a.created_by, a.question_id, q.created_by as question_author, q.catid')
            ->from($db->quoteName('#__question_answers', 'a'))
            ->join('INNER', $db->quoteName('#__question_questions', 'q') . ' ON q.id = a.question_id')
            ->where($db->quoteName('a.id') . ' = ' . (int) $answerId);
        $db->setQuery($query);
        $answer = $db->loadObject();

        if (!$answer) {
            return false;
        }

        // Check category permissions
        $assetKey = 'com_question.category.' . $answer->catid;
        
        switch ($action) {
            case 'core.edit':
            case 'core.edit.own':
                // Can edit own content
                if ($answer->created_by == $user->id && $user->authorise('core.edit.own', $assetKey)) {
                    return true;
                }
                break;

            case 'core.delete':
                // Can delete own content or question author can delete answers
                if (($answer->created_by == $user->id || $answer->question_author == $user->id) 
                    && $user->authorise('core.delete', $assetKey)) {
                    return true;
                }
                break;

            case 'vote.answer':
                // Can vote if can view content
                if ($user->authorise('core.view', $assetKey)) {
                    return true;
                }
                break;
        }

        return false;
    }

    /**
     * Check if user can mark answer as best
     *
     * @param   integer  $answerId  The answer ID
     * @param   integer  $userId    The user ID (optional)
     *
     * @return  boolean  True if allowed
     */
    public static function canMarkBest($answerId, $userId = null)
    {
        $user = $userId ? Factory::getUser($userId) : Factory::getUser();
        
        // Moderators can mark any answer as best
        if ($user->authorise('moderate.content', 'com_question')) {
            return true;
        }

        // Get answer details
        $db = Factory::getContainer()->get(DatabaseInterface::class);
        $query = $db->getQuery(true)
            ->select('question_id')
            ->from($db->quoteName('#__question_answers'))
            ->where($db->quoteName('id') . ' = ' . (int) $answerId);
        $db->setQuery($query);
        $questionId = $db->loadResult();

        if (!$questionId) {
            return false;
        }

        // Check if user is question author
        return self::isQuestionAuthor($questionId, $userId);
    }

    /**
     * Check if user is question author
     *
     * @param   integer  $questionId  The question ID
     * @param   integer  $userId      The user ID (optional)
     *
     * @return  boolean  True if user is author
     */
    public static function isQuestionAuthor($questionId, $userId = null)
    {
        $user = $userId ? Factory::getUser($userId) : Factory::getUser();
        
        if ($user->guest) {
            return false;
        }

        $db = Factory::getContainer()->get(DatabaseInterface::class);
        $query = $db->getQuery(true)
            ->select('created_by')
            ->from($db->quoteName('#__question_questions'))
            ->where($db->quoteName('id') . ' = ' . (int) $questionId);
        $db->setQuery($query);
        $authorId = $db->loadResult();

        return $authorId == $user->id;
    }

    /**
     * Check if user is answer author
     *
     * @param   integer  $answerId  The answer ID
     * @param   integer  $userId    The user ID (optional)
     *
     * @return  boolean  True if user is author
     */
    public static function isAnswerAuthor($answerId, $userId = null)
    {
        $user = $userId ? Factory::getUser($userId) : Factory::getUser();
        
        if ($user->guest) {
            return false;
        }

        $db = Factory::getContainer()->get(DatabaseInterface::class);
        $query = $db->getQuery(true)
            ->select('created_by')
            ->from($db->quoteName('#__question_answers'))
            ->where($db->quoteName('id') . ' = ' . (int) $answerId);
        $db->setQuery($query);
        $authorId = $db->loadResult();

        return $authorId == $user->id;
    }

    /**
     * Get user's role in the question system
     *
     * @param   integer  $userId  The user ID (optional)
     *
     * @return  string  The role (guest, registered, author, moderator, admin)
     */
    public static function getUserRole($userId = null)
    {
        $user = $userId ? Factory::getUser($userId) : Factory::getUser();

        if ($user->guest) {
            return 'guest';
        }

        if ($user->authorise('core.admin', 'com_question')) {
            return 'admin';
        }

        if ($user->authorise('moderate.content', 'com_question')) {
            return 'moderator';
        }

        if ($user->authorise('core.edit', 'com_question')) {
            return 'author';
        }

        return 'registered';
    }

    /**
     * Check rate limiting for user
     *
     * @param   string   $action    The action (question, answer)
     * @param   integer  $userId    The user ID (optional)
     * @param   integer  $timeLimit Time limit in hours (default: 1)
     *
     * @return  boolean  True if within rate limit
     */
    public static function checkRateLimit($action, $userId = null, $timeLimit = 1)
    {
        $user = $userId ? Factory::getUser($userId) : Factory::getUser();
        
        if ($user->guest) {
            return false;
        }

        // Get component parameters
        $params = Factory::getApplication()->getParams('com_question');
        $rateLimit = $params->get('rate_limit_' . $action . 's', 5);

        $db = Factory::getContainer()->get(DatabaseInterface::class);
        $cutoffDate = Factory::getDate('-' . $timeLimit . ' hours')->toSql();

        // Count recent submissions
        $table = $action === 'question' ? '#__question_questions' : '#__question_answers';
        $query = $db->getQuery(true)
            ->select('COUNT(*)')
            ->from($db->quoteName($table))
            ->where($db->quoteName('created_by') . ' = ' . (int) $user->id)
            ->where($db->quoteName('created') . ' >= ' . $db->quote($cutoffDate));
        $db->setQuery($query);
        $count = $db->loadResult();

        return $count < $rateLimit;
    }

    /**
     * Sanitize input text
     *
     * @param   string  $text     The text to sanitize
     * @param   string  $type     The type of content (title, body, etc.)
     * @param   boolean $allowHtml Allow HTML tags
     *
     * @return  string  The sanitized text
     */
    public static function sanitizeText($text, $type = 'body', $allowHtml = false)
    {
        if (empty($text)) {
            return '';
        }

        // Basic trimming
        $text = trim($text);

        // Remove control characters
        $text = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', '', $text);

        if (!$allowHtml) {
            // Strip all HTML tags
            $text = strip_tags($text);
        } else {
            // Allow specific HTML tags
            $allowedTags = '<p><br><strong><em><ul><ol><li><a><blockquote><code><pre>';
            $text = strip_tags($text, $allowedTags);
            
            // Additional HTML filtering
            $text = preg_replace('/on\w+="[^"]*"/i', '', $text);
            $text = preg_replace('/javascript:/i', '', $text);
        }

        // Length validation based on type
        switch ($type) {
            case 'title':
                $maxLength = 255;
                break;
            case 'body':
                $maxLength = 10000;
                break;
            default:
                $maxLength = 1000;
        }

        if (strlen($text) > $maxLength) {
            $text = substr($text, 0, $maxLength);
        }

        return $text;
    }

    /**
     * Validate question data
     *
     * @param   array  $data  The question data
     *
     * @return  array  Array of errors (empty if valid)
     */
    public static function validateQuestion($data)
    {
        $errors = [];

        // Title validation
        if (empty($data['title'])) {
            $errors[] = 'Title is required';
        } elseif (strlen($data['title']) < 10) {
            $errors[] = 'Title must be at least 10 characters';
        } elseif (strlen($data['title']) > 255) {
            $errors[] = 'Title cannot exceed 255 characters';
        }

        // Body validation
        if (empty($data['body'])) {
            $errors[] = 'Question body is required';
        } elseif (strlen($data['body']) < 30) {
            $errors[] = 'Question body must be at least 30 characters';
        } elseif (strlen($data['body']) > 10000) {
            $errors[] = 'Question body cannot exceed 10,000 characters';
        }

        // Category validation
        if (empty($data['catid']) || !is_numeric($data['catid'])) {
            $errors[] = 'Valid category is required';
        }

        // Language validation
        if (empty($data['language'])) {
            $errors[] = 'Language is required';
        }

        return $errors;
    }

    /**
     * Validate answer data
     *
     * @param   array  $data  The answer data
     *
     * @return  array  Array of errors (empty if valid)
     */
    public static function validateAnswer($data)
    {
        $errors = [];

        // Question ID validation
        if (empty($data['question_id']) || !is_numeric($data['question_id'])) {
            $errors[] = 'Valid question ID is required';
        }

        // Body validation
        if (empty($data['body'])) {
            $errors[] = 'Answer body is required';
        } elseif (strlen($data['body']) < 10) {
            $errors[] = 'Answer must be at least 10 characters';
        } elseif (strlen($data['body']) > 10000) {
            $errors[] = 'Answer cannot exceed 10,000 characters';
        }

        return $errors;
    }

    /**
     * Log security event
     *
     * @param   string   $event    The event type
     * @param   string   $message  The message
     * @param   integer  $userId   The user ID (optional)
     * @param   array    $data     Additional data
     *
     * @return  void
     */
    public static function logSecurityEvent($event, $message, $userId = null, $data = [])
    {
        $user = $userId ? Factory::getUser($userId) : Factory::getUser();
        $app = Factory::getApplication();

        $logData = [
            'event' => $event,
            'message' => $message,
            'user_id' => $user->id,
            'ip' => $app->input->server->get('REMOTE_ADDR', '', 'string'),
            'user_agent' => $app->input->server->get('HTTP_USER_AGENT', '', 'string'),
            'data' => $data,
            'timestamp' => Factory::getDate()->toSql()
        ];

        // Log to Joomla log
        \Joomla\CMS\Log\Log::add(
            $message,
            \Joomla\CMS\Log\Log::WARNING,
            'com_question.security'
        );

        // Could also log to custom security table here
    }

    /**
     * Check for suspicious activity
     *
     * @param   string   $action    The action being performed
     * @param   integer  $userId    The user ID (optional)
     *
     * @return  boolean  True if activity seems suspicious
     */
    public static function isSuspiciousActivity($action, $userId = null)
    {
        $user = $userId ? Factory::getUser($userId) : Factory::getUser();
        $app = Factory::getApplication();
        $ip = $app->input->server->get('REMOTE_ADDR', '', 'string');

        // Check for rapid submissions from same IP
        $db = Factory::getContainer()->get(DatabaseInterface::class);
        $cutoffTime = Factory::getDate('-5 minutes')->toSql();

        $table = $action === 'question' ? '#__question_questions' : '#__question_answers';
        $query = $db->getQuery(true)
            ->select('COUNT(*)')
            ->from($db->quoteName($table))
            ->where($db->quoteName('created') . ' >= ' . $db->quote($cutoffTime));

        // Check by IP for guests, by user for logged-in users
        if ($user->guest) {
            // This would require storing IP addresses, which we don't do by default
            // For now, just return false for guests (they can't post anyway)
            return false;
        } else {
            $query->where($db->quoteName('created_by') . ' = ' . (int) $user->id);
        }

        $db->setQuery($query);
        $count = $db->loadResult();

        // More than 5 submissions in 5 minutes is suspicious
        return $count > 5;
    }
}
