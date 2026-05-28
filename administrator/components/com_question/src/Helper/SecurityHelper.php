<?php
/**
 * Security Helper for com_question
 *
 * @package     Question\Component\Question
 * @subpackage  Helper
 * @version     2.0.0
 * @license     GNU General Public License version 3 or later
 */

namespace Question\Component\Question\Administrator\Helper;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Log\Log;
use Joomla\CMS\User\User;

/**
 * Security helper class
 *
 * @since  2.0.0
 */
class SecurityHelper
{
    /**
     * Check rate limit for user action
     *
     * @param   string  $action     Action name (e.g., 'ask_question', 'post_answer')
     * @param   int     $userId     User ID
     * @param   int     $limit      Maximum allowed in time window
     * @param   int     $window     Time window in seconds
     *
     * @return  bool
     *
     * @since   2.0.0
     */
    public static function checkRateLimit(
        string $action,
        int $userId,
        int $limit = 5,
        int $window = 3600
    ): bool {
        $db = Factory::getDbo();
        $now = Factory::getDate();
        $windowStart = $now->modify('-' . $window . ' seconds');

        $query = $db->getQuery(true)
            ->select('COUNT(*)')
            ->from($db->quoteName('#__question_events'))
            ->where($db->quoteName('user_id') . ' = ' . (int)$userId)
            ->where($db->quoteName('event_type') . ' = ' . $db->quote($action))
            ->where($db->quoteName('created_date') . ' >= ' . $db->quote($windowStart->toSql()));

        $db->setQuery($query);
        $count = (int)$db->loadResult();

        if ($count >= $limit) {
            Log::add(
                "Rate limit exceeded: User {$userId} exceeded limit for action: {$action}",
                Log::WARNING,
                'com_question'
            );
            return false;
        }

        return true;
    }

    /**
     * Sanitize text input
     *
     * @param   string  $text  Text to sanitize
     *
     * @return  string
     *
     * @since   2.0.0
     */
    public static function sanitizeText(string $text): string
    {
        // Remove potentially harmful scripts
        $text = strip_tags($text);

        // Escape HTML entities
        $text = htmlspecialchars($text, ENT_QUOTES, 'UTF-8');

        // Remove control characters
        $text = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', '', $text);

        return trim($text);
    }

    /**
     * Sanitize HTML content with allowed tags
     *
     * @param   string  $html  HTML content to sanitize
     *
     * @return  string
     *
     * @since   2.0.0
     */
    public static function sanitizeHtml(string $html): string
    {
        $allowedTags = '<p><br><strong><em><u><ol><ul><li><blockquote><code><pre><a>';

        $html = strip_tags($html, $allowedTags);

        // Only allow specific attributes
        $html = preg_replace_callback(
            '/<a\s+href=(["\'])([^"\']+)\1/i',
            function($matches) {
                $url = $matches[2];
                // Validate URL
                if (filter_var($url, FILTER_VALIDATE_URL) || strpos($url, '/') === 0) {
                    return '<a href="' . htmlspecialchars($url, ENT_QUOTES, 'UTF-8') . '"';
                }
                return '<a href="#"';
            },
            $html
        );

        return $html;
    }

    /**
     * Verify user permission
     *
     * @param   string  $action    Action to check
     * @param   User    $user      User object (optional, uses current user if null)
     *
     * @return  bool
     *
     * @since   2.0.0
     */
    public static function checkPermission(string $action, User $user = null): bool
    {
        if ($user === null) {
            $user = Factory::getUser();
        }

        return $user->authorise($action, 'com_question');
    }

    /**
     * Validate CSRF token
     *
     * @param   string  $token  Token to validate (optional, uses request if null)
     *
     * @return  bool
     *
     * @since   2.0.0
     */
    public static function validateCsrfToken(string $token = null): bool
    {
        $session = Factory::getSession();

        if ($token === null) {
            $input = Factory::getApplication()->getInput();
            $token = $input->getString(Factory::getSession()->getFormToken());
        }

        $sessionToken = $session->getFormToken();

        if (!hash_equals($sessionToken, $token)) {
            Log::add(
                'CSRF token validation failed',
                Log::WARNING,
                'com_question'
            );
            return false;
        }

        return true;
    }

    /**
     * Validate IP address
     *
     * @param   string  $ip  IP address to validate
     *
     * @return  bool
     *
     * @since   2.0.0
     */
    public static function validateIpAddress(string $ip): bool
    {
        return filter_var($ip, FILTER_VALIDATE_IP) !== false;
    }

    /**
     * Get client IP address
     *
     * @return  string
     *
     * @since   2.0.0
     */
    public static function getClientIp(): string
    {
        $ip = '127.0.0.1';

        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            $ip = $_SERVER['HTTP_CLIENT_IP'];
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ip = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR'])[0];
        } elseif (!empty($_SERVER['REMOTE_ADDR'])) {
            $ip = $_SERVER['REMOTE_ADDR'];
        }

        return self::validateIpAddress($ip) ? $ip : '127.0.0.1';
    }

    /**
     * Generate secure token
     *
     * @param   int  $length  Token length
     *
     * @return  string
     *
     * @since   2.0.0
     */
    public static function generateSecureToken(int $length = 32): string
    {
        return bin2hex(random_bytes($length / 2));
    }

    /**
     * Hash password
     *
     * @param   string  $password  Password to hash
     *
     * @return  string
     *
     * @since   2.0.0
     */
    public static function hashPassword(string $password): string
    {
        return password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
    }

    /**
     * Verify password
     *
     * @param   string  $password  Password to verify
     * @param   string  $hash      Hash to compare against
     *
     * @return  bool
     *
     * @since   2.0.0
     */
    public static function verifyPassword(string $password, string $hash): bool
    {
        return password_verify($password, $hash);
    }

    /**
     * Log security event
     *
     * @param   string  $event       Event type
     * @param   string  $description Event description
     * @param   int     $userId      User ID
     * @param   array   $context     Additional context
     *
     * @return  void
     *
     * @since   2.0.0
     */
    public static function logSecurityEvent(
        string $event,
        string $description,
        int $userId = 0,
        array $context = []
    ): void {
        $message = "[$event] $description (User: {$userId}, IP: " . self::getClientIp() . ')';

        if (!empty($context)) {
            $message .= ' Context: ' . json_encode($context);
        }

        Log::add($message, Log::WARNING, 'com_question.security');
    }
}
