<?php
/**
 * Installation Script for com_question component
 * Joomla 6.1 Compatible
 *
 * @package     Question\Component\Question
 * @subpackage  Installer
 * @version     2.0.0
 * @license     GNU General Public License version 3 or later
 */

defined('_JEXEC') or die;

use Joomla\CMS\Application\CMSApplication;
use Joomla\CMS\Factory;
use Joomla\CMS\Installer\InstallerAdapter;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Log\Log;

/**
 * Script file for com_question component installation/updates
 *
 * @since  2.0.0
 */
class Com_questionInstallerScript
{
    /**
     * Minimum supported Joomla version
     *
     * @var    string
     * @since  2.0.0
     */
    protected $minimumJoomla = '6.1';

    /**
     * Minimum supported PHP version
     *
     * @var    string
     * @since  2.0.0
     */
    protected $minimumPhp = '8.2';

    /**
     * Pre-flight checks before installation
     *
     * @param   string            $type    Installation type (install, update, discover_install)
     * @param   InstallerAdapter  $parent  Parent installer instance
     *
     * @return  bool  Returns false to abort installation
     *
     * @since   2.0.0
     */
    public function preflight($type, $parent): bool
    {
        if ($type === 'uninstall') {
            return true;
        }

        try {
            // Check minimum Joomla version
            if (!version_compare(JVERSION, $this->minimumJoomla, 'ge')) {
                throw new \RuntimeException(
                    Text::sprintf('JLIB_INSTALLER_MINIMUM_JOOMLA', $this->minimumJoomla)
                );
            }

            // Check PHP version
            if (!version_compare(PHP_VERSION, $this->minimumPhp, 'ge')) {
                throw new \RuntimeException(
                    Text::sprintf('JLIB_INSTALLER_MINIMUM_PHP', $this->minimumPhp)
                );
            }

            // Verify database support (MySQL 8.0+ or MariaDB 10.4+)
            $this->checkDatabaseVersion();

            // Log successful preflight checks
            Log::add(
                'COM_QUESTION: Preflight checks passed successfully',
                Log::INFO,
                'com_question'
            );

            return true;
        } catch (\Exception $e) {
            Log::add(
                'COM_QUESTION: Preflight check failed - ' . $e->getMessage(),
                Log::ERROR,
                'com_question'
            );

            echo '<div class="alert alert-danger">' . htmlspecialchars($e->getMessage()) . '</div>';

            return false;
        }
    }

    /**
     * Handle component installation
     *
     * @param   InstallerAdapter  $parent  Parent installer instance
     *
     * @return  void
     *
     * @since   2.0.0
     */
    public function install($parent): void
    {
        try {
            $this->createDefaultCategories();
            $this->createDefaultPermissions();
            $this->initializeExtensionSettings();

            Log::add(
                'COM_QUESTION: Component installed successfully',
                Log::INFO,
                'com_question'
            );
        } catch (\Exception $e) {
            Log::add(
                'COM_QUESTION: Installation error - ' . $e->getMessage(),
                Log::ERROR,
                'com_question'
            );
            throw $e;
        }
    }

    /**
     * Handle component update
     *
     * @param   InstallerAdapter  $parent  Parent installer instance
     *
     * @return  void
     *
     * @since   2.0.0
     */
    public function update($parent): void
    {
        try {
            $this->runMigrations();
            $this->updateComponentVersion();

            Log::add(
                'COM_QUESTION: Component updated successfully',
                Log::INFO,
                'com_question'
            );
        } catch (\Exception $e) {
            Log::add(
                'COM_QUESTION: Update error - ' . $e->getMessage(),
                Log::ERROR,
                'com_question'
            );
            throw $e;
        }
    }

    /**
     * Handle component uninstallation
     *
     * @param   InstallerAdapter  $parent  Parent installer instance
     *
     * @return  void
     *
     * @since   2.0.0
     */
    public function uninstall($parent): void
    {
        try {
            $this->cleanupComponentData();

            Log::add(
                'COM_QUESTION: Component uninstalled successfully',
                Log::INFO,
                'com_question'
            );
        } catch (\Exception $e) {
            Log::add(
                'COM_QUESTION: Uninstall error - ' . $e->getMessage(),
                Log::ERROR,
                'com_question'
            );
        }
    }

    /**
     * Post-flight hook after installation/update
     *
     * @param   string            $type    Installation type
     * @param   InstallerAdapter  $parent  Parent installer instance
     *
     * @return  void
     *
     * @since   2.0.0
     */
    public function postflight($type, $parent): void
    {
        try {
            if ($type === 'install') {
                $this->enableComponent();
                $this->publishComponent();
            }

            Log::add(
                'COM_QUESTION: Postflight completed for type: ' . $type,
                Log::INFO,
                'com_question'
            );
        } catch (\Exception $e) {
            Log::add(
                'COM_QUESTION: Postflight error - ' . $e->getMessage(),
                Log::ERROR,
                'com_question'
            );
        }
    }

    /**
     * Check database version compatibility
     *
     * @return  void
     * @throws  \RuntimeException
     *
     * @since   2.0.0
     */
    private function checkDatabaseVersion(): void
    {
        $db = Factory::getDbo();
        $version = $db->getVersion();

        // Check for MySQL 8.0+ or MariaDB 10.4+
        if (strpos($version, 'MariaDB') !== false) {
            if (!version_compare($version, '10.4', 'ge')) {
                throw new \RuntimeException(
                    'COM_QUESTION: This component requires MariaDB 10.4 or higher'
                );
            }
        } else {
            if (!version_compare($version, '8.0', 'ge')) {
                throw new \RuntimeException(
                    'COM_QUESTION: This component requires MySQL 8.0 or higher'
                );
            }
        }
    }

    /**
     * Create default question categories
     *
     * @return  void
     *
     * @since   2.0.0
     */
    private function createDefaultCategories(): void
    {
        $db = Factory::getDbo();

        // Check if categories already exist
        $query = $db->getQuery(true)
            ->select('COUNT(*)')
            ->from($db->quoteName('#__categories'))
            ->where($db->quoteName('extension') . ' = ' . $db->quote('com_question'));

        $db->setQuery($query);
        $count = (int) $db->loadResult();

        if ($count > 0) {
            return; // Categories already exist
        }

        $defaultCategories = [
            [
                'title'       => 'General',
                'alias'       => 'general',
                'description' => 'General questions and discussions',
                'parent_id'   => 1,
                'level'       => 1,
                'path'        => 'general'
            ],
            [
                'title'       => 'Technology',
                'alias'       => 'technology',
                'description' => 'Technology and programming questions',
                'parent_id'   => 1,
                'level'       => 1,
                'path'        => 'technology'
            ],
            [
                'title'       => 'Science',
                'alias'       => 'science',
                'description' => 'Science and research questions',
                'parent_id'   => 1,
                'level'       => 1,
                'path'        => 'science'
            ],
            [
                'title'       => 'Business',
                'alias'       => 'business',
                'description' => 'Business and entrepreneurship questions',
                'parent_id'   => 1,
                'level'       => 1,
                'path'        => 'business'
            ]
        ];

        $user  = Factory::getUser();
        $now   = Factory::getDate();

        foreach ($defaultCategories as $category) {
            $object = (object) [
                'id'              => 0,
                'parent_id'       => $category['parent_id'],
                'level'           => $category['level'],
                'path'            => $category['path'],
                'title'           => $category['title'],
                'alias'           => $category['alias'],
                'extension'       => 'com_question',
                'published'       => 1,
                'language'        => '*',
                'created_time'    => $now->toSql(),
                'created_user_id' => $user->id,
                'description'     => $category['description'],
                'access'          => 1,
                'params'          => '{}'
            ];

            try {
                $db->insertObject('#__categories', $object);
            } catch (\Exception $e) {
                Log::add(
                    'COM_QUESTION: Failed to create category - ' . $e->getMessage(),
                    Log::WARNING,
                    'com_question'
                );
            }
        }
    }

    /**
     * Create default permission structure
     *
     * @return  void
     *
     * @since   2.0.0
     */
    private function createDefaultPermissions(): void
    {
        $db = Factory::getDbo();

        // Get public group ID
        $query = $db->getQuery(true)
            ->select('id')
            ->from($db->quoteName('#__usergroups'))
            ->where($db->quoteName('title') . ' = ' . $db->quote('Public'));

        $db->setQuery($query);
        $publicGroupId = (int) $db->loadResult();

        if ($publicGroupId > 0) {
            // Set default permissions for public access
            // Implementation handled by Joomla's ACL system
        }
    }

    /**
     * Initialize extension settings
     *
     * @return  void
     *
     * @since   2.0.0
     */
    private function initializeExtensionSettings(): void
    {
        $app = Factory::getApplication();

        // Load component parameters
        $componentParams = $app->getParams('com_question');

        // Set default values if not already set
        if (!$componentParams->get('rate_limit_questions')) {
            $componentParams->set('rate_limit_questions', 5);
        }

        if (!$componentParams->get('rate_limit_answers')) {
            $componentParams->set('rate_limit_answers', 10);
        }
    }

    /**
     * Run migration scripts
     *
     * @return  void
     *
     * @since   2.0.0
     */
    private function runMigrations(): void
    {
        $db = Factory::getDbo();

        // Check current version and run appropriate migrations
        $query = $db->getQuery(true)
            ->select('manifest_cache')
            ->from($db->quoteName('#__extensions'))
            ->where($db->quoteName('element') . ' = ' . $db->quote('com_question'))
            ->where($db->quoteName('type') . ' = ' . $db->quote('component'));

        $db->setQuery($query);
        $manifest = $db->loadResult();

        if ($manifest) {
            $manifestData = json_decode($manifest, true);
            $currentVersion = $manifestData['version'] ?? '1.0.0';

            if (version_compare($currentVersion, '2.0.0', '<')) {
                $this->migrateToVersion200();
            }
        }
    }

    /**
     * Migrate to version 2.0.0 (Joomla 6.1 compatible)
     *
     * @return  void
     *
     * @since   2.0.0
     */
    private function migrateToVersion200(): void
    {
        Log::add(
            'COM_QUESTION: Running migration to version 2.0.0 (Joomla 6.1)',
            Log::INFO,
            'com_question'
        );

        // Add UTF8MB4 support
        $this->upgradeToUtf8mb4();
    }

    /**
     * Upgrade to UTF8MB4
     *
     * @return  void
     *
     * @since   2.0.0
     */
    private function upgradeToUtf8mb4(): void
    {
        $db = Factory::getDbo();
        $tables = [
            '#__question_questions',
            '#__question_answers',
            '#__question_votes',
            '#__question_languages',
            '#__question_events'
        ];

        foreach ($tables as $table) {
            try {
                $db->setQuery("ALTER TABLE $table CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
                $db->execute();
            } catch (\Exception $e) {
                Log::add(
                    "COM_QUESTION: Could not convert $table to UTF8MB4 - " . $e->getMessage(),
                    Log::WARNING,
                    'com_question'
                );
            }
        }
    }

    /**
     * Update component version
     *
     * @return  void
     *
     * @since   2.0.0
     */
    private function updateComponentVersion(): void
    {
        $db = Factory::getDbo();

        $query = $db->getQuery(true)
            ->update($db->quoteName('#__extensions'))
            ->set($db->quoteName('manifest_cache') . ' = ' . $db->quote(''))
            ->where($db->quoteName('element') . ' = ' . $db->quote('com_question'));

        $db->setQuery($query);
        $db->execute();
    }

    /**
     * Enable component
     *
     * @return  void
     *
     * @since   2.0.0
     */
    private function enableComponent(): void
    {
        $db = Factory::getDbo();

        $query = $db->getQuery(true)
            ->update($db->quoteName('#__extensions'))
            ->set($db->quoteName('enabled') . ' = 1')
            ->where($db->quoteName('element') . ' = ' . $db->quote('com_question'))
            ->where($db->quoteName('type') . ' = ' . $db->quote('component'));

        $db->setQuery($query);
        $db->execute();
    }

    /**
     * Publish component
     *
     * @return  void
     *
     * @since   2.0.0
     */
    private function publishComponent(): void
    {
        // Component publishing is handled by Joomla core
    }

    /**
     * Cleanup component data on uninstall
     *
     * @return  void
     *
     * @since   2.0.0
     */
    private function cleanupComponentData(): void
    {
        $db = Factory::getDbo();

        // Remove component configurations
        $query = $db->getQuery(true)
            ->delete($db->quoteName('#__extensions'))
            ->where($db->quoteName('element') . ' = ' . $db->quote('com_question'));

        try {
            $db->setQuery($query);
            $db->execute();
        } catch (\Exception $e) {
            Log::add(
                'COM_QUESTION: Cleanup error - ' . $e->getMessage(),
                Log::WARNING,
                'com_question'
            );
        }
    }
}
