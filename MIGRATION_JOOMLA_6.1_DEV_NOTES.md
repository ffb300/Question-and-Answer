# Joomla 6.1 Migration - Comprehensive Developer Fix Notes

**Component**: com_question (Joomla Question & Answer)  
**Target Version**: Joomla 6.1 (stable)  
**Minimum Requirements**: PHP 8.2+, MySQL 8.0+/MariaDB 10.4+  
**Document Version**: 1.0  
**Last Updated**: 2026-05-28

---

## Table of Contents

1. [Pre-Migration Checklist](#pre-migration-checklist)
2. [Phase 1: Component Manifest Updates](#phase-1-component-manifest-updates)
3. [Phase 2: Namespace and Class Structure](#phase-2-namespace-and-class-structure)
4. [Phase 3: Database Schema and SQL Updates](#phase-3-database-schema-and-sql-updates)
5. [Phase 4: API Compatibility Updates](#phase-4-api-compatibility-updates)
6. [Phase 5: Security Hardening](#phase-5-security-hardening)
7. [Phase 6: Frontend Implementation](#phase-6-frontend-implementation)
8. [Phase 7: Administrator Backend](#phase-7-administrator-backend)
9. [Phase 8: Testing and Validation](#phase-8-testing-and-validation)
10. [Phase 9: Deployment](#phase-9-deployment)

---

## Pre-Migration Checklist

### System Requirements Verification

```
☐ Joomla 6.1 installation ready
☐ PHP 8.2 or higher installed
☐ MySQL 8.0+ or MariaDB 10.4+ configured
☐ Backup of current installation completed
☐ Git repository initialized with clean main branch
☐ Development environment isolated from production
☐ All dependencies documented
```

### Backup Strategy

```bash
# Before starting migration
# 1. Backup entire Joomla installation
tar -czf joomla-backup-$(date +%Y%m%d).tar.gz /path/to/joomla

# 2. Backup database
mysqldump -u root -p joomla_db > joomla-backup-$(date +%Y%m%d).sql

# 3. Create feature branch
git checkout -b feature/joomla-6.1-migration
```

---

## Phase 1: Component Manifest Updates

### Step 1.1: Update com_question.xml

**File**: `com_question.xml`

**Changes Required**:

1. Update component version and target:

```xml
<?xml version="1.0" encoding="utf-8"?>
<extension type="component" version="6.0" method="upgrade">
    <name>com_question</name>
    <author>Your Company</author>
    <creationDate>2026-05-28</creationDate>
    <copyright>Copyright (C) 2024-2026 Your Company</copyright>
    <license>GNU General Public License version 3 or later</license>
    <authorEmail>support@yourcompany.com</authorEmail>
    <authorUrl>https://www.yourcompany.com</authorUrl>
    <version>2.0.0</version>
    <description>COM_QUESTION_XML_DESCRIPTION</description>
    
    <!-- Joomla 6.1 Namespace Configuration -->
    <namespace path="src">Question\Component\Question</namespace>
    
    <!-- Installation Script -->
    <scriptfile>script.php</scriptfile>
    
    <!-- Core Installation SQL -->
    <install>
        <sql>
            <file driver="mysql" charset="utf8mb4">
                administrator/components/com_question/sql/install.mysql.utf8mb4.sql
            </file>
        </sql>
    </install>
    
    <!-- Update Schema Migration -->
    <update>
        <schemas>
            <schemapath type="mysql">
                administrator/components/com_question/sql/updates/mysql
            </schemapath>
        </schemas>
    </update>
    
    <!-- Uninstall SQL -->
    <uninstall>
        <sql>
            <file driver="mysql" charset="utf8mb4">
                administrator/components/com_question/sql/uninstall.mysql.utf8mb4.sql
            </file>
        </sql>
    </uninstall>
    
    <!-- Frontend Component Files -->
    <files folder="components/com_question">
        <folder>src</folder>
        <folder>language</folder>
        <folder>layouts</folder>
    </files>
    
    <!-- Media Assets -->
    <media folder="media" destination="com_question">
        <folder>css</folder>
        <folder>js</folder>
        <folder>images</folder>
    </media>
    
    <!-- Administrator Backend -->
    <administration>
        <files folder="administrator/components/com_question">
            <folder>src</folder>
            <folder>sql</folder>
            <folder>forms</folder>
        </files>
        <languages folder="administrator/language">
            <language tag="en-GB">en-GB/en-GB.com_question.ini</language>
            <language tag="en-GB">en-GB/en-GB.com_question.sys.ini</language>
        </languages>
        
        <menu img="class:comments">COM_QUESTION</menu>
        
        <submenu>
            <menu view="questions" img="class:comments-2">COM_QUESTION_QUESTIONS</menu>
            <menu view="answers" img="class:comments">COM_QUESTION_ANSWERS</menu>
            <menu view="categories" img="class:folder">COM_QUESTION_CATEGORIES</menu>
            <menu view="activity" img="class:clock">COM_QUESTION_ACTIVITY</menu>
            <menu view="configuration" img="class:cog">COM_QUESTION_CONFIG</menu>
        </submenu>
    </administration>
    
    <!-- Component Configuration -->
    <config>
        <fields name="params">
            <fieldset name="basic" label="COM_QUESTION_CONFIG_BASIC">
                <field 
                    name="enable_voting" 
                    type="radio" 
                    default="1" 
                    label="COM_QUESTION_CONFIG_ENABLE_VOTING" 
                    description="COM_QUESTION_CONFIG_ENABLE_VOTING_DESC">
                    <option value="0">JNO</option>
                    <option value="1">JYES</option>
                </field>
                
                <field 
                    name="enable_live_updates" 
                    type="radio" 
                    default="1" 
                    label="COM_QUESTION_CONFIG_ENABLE_LIVE_UPDATES" 
                    description="COM_QUESTION_CONFIG_ENABLE_LIVE_UPDATES_DESC">
                    <option value="0">JNO</option>
                    <option value="1">JYES</option>
                </field>
                
                <field 
                    name="live_update_method" 
                    type="list" 
                    default="sse" 
                    label="COM_QUESTION_CONFIG_LIVE_UPDATE_METHOD" 
                    description="COM_QUESTION_CONFIG_LIVE_UPDATE_METHOD_DESC">
                    <option value="sse">COM_QUESTION_CONFIG_METHOD_SSE</option>
                    <option value="ajax">COM_QUESTION_CONFIG_METHOD_AJAX</option>
                    <option value="polling">COM_QUESTION_CONFIG_METHOD_POLLING</option>
                </field>
            </fieldset>
            
            <fieldset name="security" label="COM_QUESTION_CONFIG_SECURITY">
                <field 
                    name="rate_limit_questions" 
                    type="number" 
                    default="5" 
                    label="COM_QUESTION_CONFIG_RATE_LIMIT_QUESTIONS" 
                    description="COM_QUESTION_CONFIG_RATE_LIMIT_QUESTIONS_DESC"
                    min="1" max="100" />
                
                <field 
                    name="rate_limit_answers" 
                    type="number" 
                    default="10" 
                    label="COM_QUESTION_CONFIG_RATE_LIMIT_ANSWERS" 
                    description="COM_QUESTION_CONFIG_RATE_LIMIT_ANSWERS_DESC"
                    min="1" max="100" />
                
                <field 
                    name="allow_anonymous" 
                    type="radio" 
                    default="0" 
                    label="COM_QUESTION_CONFIG_ALLOW_ANONYMOUS" 
                    description="COM_QUESTION_CONFIG_ALLOW_ANONYMOUS_DESC">
                    <option value="0">JNO</option>
                    <option value="1">JYES</option>
                </field>
                
                <field 
                    name="enable_content_filtering" 
                    type="radio" 
                    default="1" 
                    label="COM_QUESTION_CONFIG_ENABLE_FILTERING" 
                    description="COM_QUESTION_CONFIG_ENABLE_FILTERING_DESC">
                    <option value="0">JNO</option>
                    <option value="1">JYES</option>
                </field>
            </fieldset>
            
            <fieldset name="display" label="COM_QUESTION_CONFIG_DISPLAY">
                <field 
                    name="questions_per_page" 
                    type="number" 
                    default="20" 
                    label="COM_QUESTION_CONFIG_PER_PAGE" 
                    description="COM_QUESTION_CONFIG_PER_PAGE_DESC"
                    min="5" max="100" />
                
                <field 
                    name="enable_tags" 
                    type="radio" 
                    default="1" 
                    label="COM_QUESTION_CONFIG_ENABLE_TAGS" 
                    description="COM_QUESTION_CONFIG_ENABLE_TAGS_DESC">
                    <option value="0">JNO</option>
                    <option value="1">JYES</option>
                </field>
            </fieldset>
        </fields>
    </config>
    
    <!-- ACL - Access Control List -->
    <access component="com_question">
        <section name="component">
            <action name="core.admin" title="JACTION_ADMIN" description="JACTION_ADMIN_COMPONENT_DESC" />
            <action name="core.manage" title="JACTION_MANAGE" description="JACTION_MANAGE_COMPONENT_DESC" />
            <action name="core.create" title="JACTION_CREATE" description="JACTION_CREATE_COMPONENT_DESC" />
            <action name="core.delete" title="JACTION_DELETE" description="JACTION_DELETE_COMPONENT_DESC" />
            <action name="core.edit" title="JACTION_EDIT" description="JACTION_EDIT_COMPONENT_DESC" />
            <action name="core.edit.own" title="JACTION_EDITOWN" description="JACTION_EDITOWN_COMPONENT_DESC" />
            <action name="core.edit.state" title="JACTION_EDITSTATE" description="JACTION_EDITSTATE_COMPONENT_DESC" />
            <action name="ask.question" title="COM_QUESTION_ACTION_ASK_QUESTION" description="COM_QUESTION_ACTION_ASK_QUESTION_DESC" />
            <action name="answer.question" title="COM_QUESTION_ACTION_ANSWER_QUESTION" description="COM_QUESTION_ACTION_ANSWER_QUESTION_DESC" />
            <action name="vote.answer" title="COM_QUESTION_ACTION_VOTE_ANSWER" description="COM_QUESTION_ACTION_VOTE_ANSWER_DESC" />
            <action name="moderate.content" title="COM_QUESTION_ACTION_MODERATE_CONTENT" description="COM_QUESTION_ACTION_MODERATE_CONTENT_DESC" />
            <action name="view.live.updates" title="COM_QUESTION_ACTION_VIEW_LIVE_UPDATES" description="COM_QUESTION_ACTION_VIEW_LIVE_UPDATES_DESC" />
        </section>
    </access>
    
    <!-- Update Server Configuration for Joomla 6.1 -->
    <servers>
        <server type="updates" name="Question Component Updates" priority="1">
            https://example.com/updates/question-updates.xml
        </server>
        <server type="collection" name="Joomla Extension Updates">
            https://example.com/updates/joomla-extensions.xml
        </server>
    </servers>
    
    <!-- Update Servers (v2 format for Joomla 6.1) -->
    <updateservers>
        <server type="extension" priority="1" name="Question Component Main">
            https://example.com/updates/com_question.xml
        </server>
    </updateservers>
</extension>
```

### Step 1.2: Verify Manifest File Locations

Create the required directory structure:

```bash
# Administrator component files
mkdir -p administrator/components/com_question/src/Controller
mkdir -p administrator/components/com_question/src/Model
mkdir -p administrator/components/com_question/src/View
mkdir -p administrator/components/com_question/src/Helper
mkdir -p administrator/components/com_question/sql/updates/mysql
mkdir -p administrator/components/com_question/forms
mkdir -p administrator/language/en-GB

# Frontend component files
mkdir -p components/com_question/src/Controller
mkdir -p components/com_question/src/Model
mkdir -p components/com_question/src/View
mkdir -p components/com_question/layouts/form
mkdir -p components/com_question/layouts/list
mkdir -p components/com_question/language/en-GB

# Media assets
mkdir -p media/com_question/css
mkdir -p media/com_question/js
mkdir -p media/com_question/images
```

---

## Phase 2: Namespace and Class Structure

### Step 2.1: Update script.php for Joomla 6.1

**File**: `script.php`

```php
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
use Joomla\Component\Question\Administrator\Extension\QuestionComponent;

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
     * Maximum supported PHP version
     *
     * @var    string
     * @since  2.0.0
     */
    protected $maximumPhp = '8.3';

    /**
     * Pre-flight checks before installation
     *
     * @param   string            $type    Installation type (install, update, discover_install)
     * @param   InstallerAdapter  $parent  Parent installer instance
     *
     * @return  bool|void  Returns false to abort installation
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
            
            echo '<div class="alert alert-danger">' . $e->getMessage() . '</div>';
            
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
        
        // Get guest group ID (typically 9 in Joomla)
        $query = $db->getQuery(true)
            ->select('id')
            ->from($db->quoteName('#__usergroups'))
            ->where($db->quoteName('title') . ' = ' . $db->quote('Public'));
        
        $db->setQuery($query);
        $publicGroupId = (int) $db->loadResult();
        
        if ($publicGroupId > 0) {
            // Set default permissions for public access
            $this->setPermission($publicGroupId, 'core.login.site', 1);
        }
    }

    /**
     * Set component permission
     *
     * @param   int     $groupId      User group ID
     * @param   string  $action       Action name
     * @param   int     $value        Permission value
     *
     * @return  void
     *
     * @since   2.0.0
     */
    private function setPermission(int $groupId, string $action, int $value): void
    {
        // Implementation handled by Joomla's ACL system
        // This is a placeholder for custom permission setup
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
            ->select('version_num')
            ->from($db->quoteName('#__extensions'))
            ->where($db->quoteName('element') . ' = ' . $db->quote('com_question'))
            ->where($db->quoteName('type') . ' = ' . $db->quote('component'));
        
        $db->setQuery($query);
        $currentVersion = $db->loadResult();
        
        // Version-specific migrations
        if (version_compare($currentVersion, '1.5.0', '<')) {
            $this->migrateToVersion150();
        }
        
        if (version_compare($currentVersion, '2.0.0', '<')) {
            $this->migrateToVersion200();
        }
    }

    /**
     * Migrate to version 1.5.0
     *
     * @return  void
     *
     * @since   2.0.0
     */
    private function migrateToVersion150(): void
    {
        // Add migration logic for version 1.5.0
        Log::add(
            'COM_QUESTION: Running migration to version 1.5.0',
            Log::INFO,
            'com_question'
        );
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
        
        // Add new tables/columns
        $this->addNewDatabaseStructures();
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
     * Add new database structures for v2.0.0
     *
     * @return  void
     *
     * @since   2.0.0
     */
    private function addNewDatabaseStructures(): void
    {
        // Placeholder for adding new tables/columns
        // This will be executed from SQL migration files
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
```

---

## Phase 3: Database Schema and SQL Updates

### Step 3.1: Create Installation SQL File

**File**: `administrator/components/com_question/sql/install.mysql.utf8mb4.sql`

```sql
-- Joomla Question & Answer Component
-- Installation SQL for MySQL 8.0+
-- Charset: UTF8MB4
-- Created: 2026-05-28

-- Create Questions Table
CREATE TABLE IF NOT EXISTS `#__question_questions` (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `catid` int UNSIGNED NOT NULL DEFAULT 0,
  `title` varchar(255) NOT NULL DEFAULT '',
  `slug` varchar(255) NOT NULL DEFAULT '',
  `description` longtext NOT NULL DEFAULT '',
  `introtext` text NOT NULL DEFAULT '',
  `fulltext` longtext NOT NULL DEFAULT '',
  `created_by` int UNSIGNED NOT NULL DEFAULT 0,
  `created_date` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `modified_by` int UNSIGNED DEFAULT NULL,
  `modified_date` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  `published` tinyint(1) NOT NULL DEFAULT 1,
  `state` tinyint(4) NOT NULL DEFAULT 1,
  `access` int UNSIGNED NOT NULL DEFAULT 1,
  `language` char(7) NOT NULL DEFAULT '*',
  `featured` tinyint(1) NOT NULL DEFAULT 0,
  `hits` int UNSIGNED NOT NULL DEFAULT 0,
  `answer_count` int UNSIGNED NOT NULL DEFAULT 0,
  `vote_count` int NOT NULL DEFAULT 0,
  `best_answer_id` int UNSIGNED DEFAULT NULL,
  `locked` tinyint(1) NOT NULL DEFAULT 0,
  `metadata` json DEFAULT NULL,
  `params` json DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_catid` (`catid`),
  KEY `idx_created_by` (`created_by`),
  KEY `idx_created_date` (`created_date`),
  KEY `idx_published` (`published`),
  KEY `idx_featured` (`featured`),
  KEY `idx_access` (`access`),
  KEY `idx_language` (`language`),
  KEY `idx_slug` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create Answers Table
CREATE TABLE IF NOT EXISTS `#__question_answers` (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `question_id` int UNSIGNED NOT NULL,
  `created_by` int UNSIGNED NOT NULL DEFAULT 0,
  `created_date` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `modified_by` int UNSIGNED DEFAULT NULL,
  `modified_date` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  `text` longtext NOT NULL DEFAULT '',
  `published` tinyint(1) NOT NULL DEFAULT 1,
  `state` tinyint(4) NOT NULL DEFAULT 1,
  `access` int UNSIGNED NOT NULL DEFAULT 1,
  `is_best_answer` tinyint(1) NOT NULL DEFAULT 0,
  `vote_count` int NOT NULL DEFAULT 0,
  `helpful_count` int UNSIGNED NOT NULL DEFAULT 0,
  `unhelpful_count` int UNSIGNED NOT NULL DEFAULT 0,
  `locked` tinyint(1) NOT NULL DEFAULT 0,
  `metadata` json DEFAULT NULL,
  `params` json DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_question_id` (`question_id`),
  KEY `idx_created_by` (`created_by`),
  KEY `idx_created_date` (`created_date`),
  KEY `idx_is_best_answer` (`is_best_answer`),
  KEY `idx_published` (`published`),
  CONSTRAINT `fk_answer_question` FOREIGN KEY (`question_id`) 
    REFERENCES `#__question_questions` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create Votes Table
CREATE TABLE IF NOT EXISTS `#__question_votes` (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` int UNSIGNED NOT NULL,
  `item_type` enum('question','answer') NOT NULL,
  `item_id` int UNSIGNED NOT NULL,
  `vote_value` tinyint(2) NOT NULL DEFAULT 0,
  `created_date` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `ip_address` varchar(45) NOT NULL DEFAULT '',
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_vote` (`user_id`, `item_type`, `item_id`),
  KEY `idx_item_id` (`item_id`),
  KEY `idx_item_type` (`item_type`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_created_date` (`created_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create Language Support Table
CREATE TABLE IF NOT EXISTS `#__question_languages` (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `item_type` enum('question','answer') NOT NULL,
  `item_id` int UNSIGNED NOT NULL,
  `language` char(7) NOT NULL DEFAULT 'en-GB',
  `title` varchar(255) DEFAULT NULL,
  `description` longtext,
  `created_date` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_translation` (`item_type`, `item_id`, `language`),
  KEY `idx_item_id` (`item_id`),
  KEY `idx_language` (`language`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create Events Queue for Live Updates
CREATE TABLE IF NOT EXISTS `#__question_events` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `event_type` varchar(50) NOT NULL,
  `user_id` int UNSIGNED NOT NULL,
  `item_type` enum('question','answer','vote','moderation') NOT NULL,
  `item_id` int UNSIGNED NOT NULL,
  `event_data` json DEFAULT NULL,
  `created_date` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `processed` tinyint(1) NOT NULL DEFAULT 0,
  `processed_date` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_processed` (`processed`),
  KEY `idx_created_date` (`created_date`),
  KEY `idx_event_type` (`event_type`),
  KEY `idx_user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create Rating/Reputation Table
CREATE TABLE IF NOT EXISTS `#__question_reputation` (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` int UNSIGNED NOT NULL,
  `reputation_points` int NOT NULL DEFAULT 0,
  `badge_count` int UNSIGNED NOT NULL DEFAULT 0,
  `answers_accepted` int UNSIGNED NOT NULL DEFAULT 0,
  `questions_asked` int UNSIGNED NOT NULL DEFAULT 0,
  `answers_given` int UNSIGNED NOT NULL DEFAULT 0,
  `created_date` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `modified_date` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_user` (`user_id`),
  KEY `idx_reputation_points` (`reputation_points`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create Moderation/Flags Table
CREATE TABLE IF NOT EXISTS `#__question_flags` (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` int UNSIGNED NOT NULL,
  `item_type` enum('question','answer','comment') NOT NULL,
  `item_id` int UNSIGNED NOT NULL,
  `reason` varchar(255) NOT NULL DEFAULT '',
  `description` text,
  `status` enum('pending','approved','rejected') NOT NULL DEFAULT 'pending',
  `created_date` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `reviewed_by` int UNSIGNED DEFAULT NULL,
  `reviewed_date` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_status` (`status`),
  KEY `idx_item_type` (`item_type`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_created_date` (`created_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create Tags Junction Table
CREATE TABLE IF NOT EXISTS `#__question_tags` (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `question_id` int UNSIGNED NOT NULL,
  `tag_id` int UNSIGNED NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_tag_mapping` (`question_id`, `tag_id`),
  KEY `idx_tag_id` (`tag_id`),
  CONSTRAINT `fk_tag_question` FOREIGN KEY (`question_id`)
    REFERENCES `#__question_questions` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### Step 3.2: Create Uninstall SQL File

**File**: `administrator/components/com_question/sql/uninstall.mysql.utf8mb4.sql`

```sql
-- Joomla Question & Answer Component
-- Uninstallation SQL for MySQL 8.0+

-- Drop all component tables
DROP TABLE IF EXISTS `#__question_tags`;
DROP TABLE IF EXISTS `#__question_flags`;
DROP TABLE IF EXISTS `#__question_reputation`;
DROP TABLE IF EXISTS `#__question_events`;
DROP TABLE IF EXISTS `#__question_languages`;
DROP TABLE IF EXISTS `#__question_votes`;
DROP TABLE IF EXISTS `#__question_answers`;
DROP TABLE IF EXISTS `#__question_questions`;

-- Remove component categories
DELETE FROM `#__categories` WHERE `extension` = 'com_question';

-- Remove component from extensions
DELETE FROM `#__extensions` WHERE `element` = 'com_question' AND `type` = 'component';
```

### Step 3.3: Create Migration Scripts

**File**: `administrator/components/com_question/sql/updates/mysql/2.0.0.sql`

```sql
-- Migration: Version 2.0.0 - Joomla 6.1 Support
-- Date: 2026-05-28

-- Add new columns for Joomla 6.1 compatibility
ALTER TABLE `#__question_questions` ADD COLUMN `joomla6_compatible` TINYINT(1) NOT NULL DEFAULT 1;
ALTER TABLE `#__question_answers` ADD COLUMN `joomla6_compatible` TINYINT(1) NOT NULL DEFAULT 1;

-- Update character set to UTF8MB4
ALTER TABLE `#__question_questions` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE `#__question_answers` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE `#__question_votes` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE `#__question_languages` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE `#__question_events` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE `#__question_reputation` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE `#__question_flags` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- Create indexes for performance
CREATE INDEX `idx_question_created_by_date` ON `#__question_questions` (`created_by`, `created_date`);
CREATE INDEX `idx_answer_question_date` ON `#__question_answers` (`question_id`, `created_date`);

-- Update extension settings
UPDATE `#__extensions` 
SET `params` = JSON_SET(
    COALESCE(`params`, '{}'),
    '$.joomla_version', '6.1',
    '$.database_version', '2.0.0'
)
WHERE `element` = 'com_question' AND `type` = 'component';
```

---

## Phase 4: API Compatibility Updates

### Step 4.1: Create Base Component Class

**File**: `administrator/components/com_question/src/Extension/QuestionComponent.php`

```php
<?php
/**
 * Question Component - Joomla 6.1 Compatible
 * 
 * @package     Question\Component\Question
 * @subpackage  Extension
 * @version     2.0.0
 * @license     GNU General Public License version 3 or later
 */

namespace Question\Component\Question\Administrator\Extension;

defined('_JEXEC') or die;

use Joomla\CMS\Application\SiteApplication;
use Joomla\CMS\Categories\CategoryServiceInterface;
use Joomla\CMS\Categories\CategoryService;
use Joomla\CMS\Component\ComponentServiceInterface;
use Joomla\CMS\Component\Router\RouterServiceInterface;
use Joomla\CMS\Extension\BootableExtensionInterface;
use Joomla\CMS\Extension\ComponentInterface;
use Joomla\CMS\HTML\HTMLRegistryAwareTrait;
use Joomla\CMS\Language\Text;
use Joomla\Component\Question\Administrator\Service\HTML\AdministratorService;
use Joomla\Component\Question\Site\Service\Router;
use Psr\Container\ContainerInterface;

/**
 * Component class for com_question
 *
 * @since  2.0.0
 */
class QuestionComponent extends CategoryService implements 
    BootableExtensionInterface, 
    ComponentInterface, 
    CategoryServiceInterface
{
    use HTMLRegistryAwareTrait;

    /**
     * Booting the extension. This is the place to register adapters, controllers, templates, etc.
     *
     * @param   ContainerInterface  $container  The container
     *
     * @return  void
     *
     * @since   2.0.0
     */
    public function boot(ContainerInterface $container): void
    {
        // Register HTML helpers
        $this->getRegistry()->register(
            'question_admin',
            new AdministratorService()
        );

        // Register router for frontend
        if ($container->has(SiteApplication::class)) {
            $container->set(
                RouterServiceInterface::class,
                new Router(
                    $container->get('db'),
                    $container->get('menu'),
                    $container->get('language'),
                    $container->get('app')
                )
            );
        }
    }

    /**
     * Returns the table for the count items functionality.
     *
     * @param   string  $section  The name of the section
     *
     * @return  string
     *
     * @since   2.0.0
     */
    public function getTableNameForSection(string $section = null): string
    {
        return match ($section) {
            'category' => '#__categories',
            default => '#__question_questions'
        };
    }

    /**
     * Returns the field(s) to be used in counting items for the section.
     *
     * @param   string  $section  The name of the section
     *
     * @return  array
     *
     * @since   2.0.0
     */
    public function getFieldsForCount(string $section = null): array
    {
        return [
            'a.published',
            'a.created_by'
        ];
    }
}
```

---

## Phase 5: Security Hardening

### Step 5.1: Create Security Helper Class

**File**: `administrator/components/com_question/src/Helper/SecurityHelper.php`

```php
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
     * Rate limiter instance
     *
     * @var    array
     * @since  2.0.0
     */
    private static $rateLimiters = [];

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
```

---

## Phase 6: Frontend Implementation

### Step 6.1: Create Frontend Controller Base Class

**File**: `components/com_question/src/Controller/BaseController.php`

```php
<?php
/**
 * Base Frontend Controller for com_question
 * 
 * @package     Question\Component\Question
 * @subpackage  Controller
 * @version     2.0.0
 * @license     GNU General Public License version 3 or later
 */

namespace Question\Component\Question\Site\Controller;

defined('_JEXEC') or die;

use Joomla\CMS\MVC\Controller\BaseController as JoomlaBaseController;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;

/**
 * Base controller for frontend
 *
 * @since  2.0.0
 */
class BaseController extends JoomlaBaseController
{
    /**
     * Display method
     *
     * @param   bool  $cachable   If true, the view output will be cached
     * @param   array $urlparams  An array of safe URL parameters and their variable types
     *
     * @return  BaseController|bool
     *
     * @since   2.0.0
     */
    public function display($cachable = false, $urlparams = [])
    {
        $view = $this->input->getString('view', 'questions');
        $id   = $this->input->getInt('id');

        // Whitelist views
        $allowedViews = ['questions', 'question', 'ask', 'dashboard'];
        
        if (!in_array($view, $allowedViews)) {
            throw new \Exception(Text::_('JGLOBAL_INVALID_VIEW'), 404);
        }

        $urlparams = array_merge(
            $urlparams,
            [
                'id'   => 'INT',
                'view' => 'WORD'
            ]
        );

        return parent::display($cachable, $urlparams);
    }

    /**
     * Validate user action
     *
     * @return  bool
     *
     * @since   2.0.0
     */
    protected function validateAction(): bool
    {
        $user = Factory::getUser();
        
        if ($user->guest) {
            $this->setRedirect(
                'index.php?option=com_users&view=login&return=' . base64_encode(Factory::getApplication()->input->server->getString('REQUEST_URI'))
            );
            return false;
        }

        return true;
    }

    /**
     * Get component parameters
     *
     * @return  \Joomla\Registry\Registry
     *
     * @since   2.0.0
     */
    protected function getComponentParams()
    {
        return Factory::getApplication()->getParams('com_question');
    }
}
```

### Step 6.2: Create Questions Model

**File**: `components/com_question/src/Model/QuestionsModel.php`

```php
<?php
/**
 * Questions Model for com_question
 * 
 * @package     Question\Component\Question
 * @subpackage  Model
 * @version     2.0.0
 * @license     GNU General Public License version 3 or later
 */

namespace Question\Component\Question\Site\Model;

defined('_JEXEC') or die;

use Joomla\CMS\MVC\Model\ListModel;
use Joomla\CMS\Factory;
use Joomla\Database\DatabaseQuery;

/**
 * Questions list model
 *
 * @since  2.0.0
 */
class QuestionsModel extends ListModel
{
    /**
     * Model context string
     *
     * @var    string
     * @since  2.0.0
     */
    protected $context = 'com_question.questions';

    /**
     * Method to auto-populate the model state.
     *
     * @param   string  $ordering   An optional ordering field
     * @param   string  $direction  An optional direction
     *
     * @return  void
     *
     * @since   2.0.0
     */
    protected function populateState($ordering = 'created_date', $direction = 'DESC')
    {
        $app = Factory::getApplication();
        
        // Load state from the request
        $this->setState('filter.search', $app->getUserStateFromRequest($this->context . '.filter.search', 'filter_search', '', 'string'));
        $this->setState('filter.category', $app->getUserStateFromRequest($this->context . '.filter.category', 'filter_category', 0, 'int'));
        $this->setState('filter.language', $app->getUserStateFromRequest($this->context . '.filter.language', 'filter_language', '', 'string'));
        $this->setState('filter.published', $app->getUserStateFromRequest($this->context . '.filter.published', 'filter_published', 1, 'int'));

        // List state
        parent::populateState($ordering, $direction);
    }

    /**
     * Method to get a DatabaseQuery object for retrieving the data set from a database.
     *
     * @return  DatabaseQuery
     *
     * @since   2.0.0
     */
    protected function getListQuery()
    {
        $db = $this->getDbo();
        
        $query = $db->getQuery(true)
            ->select($db->quoteName(['q.id', 'q.title', 'q.slug', 'q.description', 'q.created_by', 'q.created_date', 'q.answer_count', 'q.vote_count', 'q.hits']))
            ->from($db->quoteName('#__question_questions', 'q'))
            ->where($db->quoteName('q.published') . ' = 1')
            ->order($db->escape($this->getState('list.ordering', 'q.created_date')) . ' ' . $db->escape($this->getState('list.direction', 'DESC')));

        // Filter by category
        $categoryId = $this->getState('filter.category');
        if ($categoryId > 0) {
            $query->where($db->quoteName('q.catid') . ' = ' . (int)$categoryId);
        }

        // Filter by search
        $search = $this->getState('filter.search');
        if (!empty($search)) {
            $search = $db->quote('%' . $db->escape($search, true) . '%');
            $query->where('(' . $db->quoteName('q.title') . ' LIKE ' . $search . ' OR ' . $db->quoteName('q.description') . ' LIKE ' . $search . ')');
        }

        // Filter by language
        $language = $this->getState('filter.language');
        if (!empty($language)) {
            $query->where($db->quoteName('q.language') . ' = ' . $db->quote($language));
        }

        return $query;
    }

    /**
     * Get published questions
     *
     * @param   int  $limit  Limit results
     *
     * @return  array
     *
     * @since   2.0.0
     */
    public function getPublishedQuestions($limit = 20)
    {
        $this->setState('list.limit', $limit);
        $this->setState('list.start', 0);
        
        return $this->getItems();
    }

    /**
     * Get featured questions
     *
     * @param   int  $limit  Limit results
     *
     * @return  array
     *
     * @since   2.0.0
     */
    public function getFeaturedQuestions($limit = 5)
    {
        $db = $this->getDbo();
        
        $query = $db->getQuery(true)
            ->select($db->quoteName(['id', 'title', 'slug', 'description', 'created_by', 'created_date', 'answer_count', 'vote_count']))
            ->from($db->quoteName('#__question_questions'))
            ->where($db->quoteName('published') . ' = 1')
            ->where($db->quoteName('featured') . ' = 1')
            ->order($db->quoteName('created_date') . ' DESC')
            ->setLimit($limit);

        $db->setQuery($query);
        
        return $db->loadObjectList();
    }

    /**
     * Get unanswered questions
     *
     * @param   int  $limit  Limit results
     *
     * @return  array
     *
     * @since   2.0.0
     */
    public function getUnansweredQuestions($limit = 10)
    {
        $db = $this->getDbo();
        
        $query = $db->getQuery(true)
            ->select($db->quoteName(['id', 'title', 'slug', 'description', 'created_by', 'created_date', 'vote_count']))
            ->from($db->quoteName('#__question_questions'))
            ->where($db->quoteName('published') . ' = 1')
            ->where($db->quoteName('answer_count') . ' = 0')
            ->order($db->quoteName('created_date') . ' DESC')
            ->setLimit($limit);

        $db->setQuery($query);
        
        return $db->loadObjectList();
    }
}
```

---

## Phase 7: Administrator Backend

### Step 7.1: Create Questions Admin Controller

**File**: `administrator/components/com_question/src/Controller/QuestionsController.php`

```php
<?php
/**
 * Questions Admin Controller for com_question
 * 
 * @package     Question\Component\Question
 * @subpackage  Controller
 * @version     2.0.0
 * @license     GNU General Public License version 3 or later
 */

namespace Question\Component\Question\Administrator\Controller;

defined('_JEXEC') or die;

use Joomla\CMS\MVC\Controller\AdminController;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\Component\Question\Administrator\Helper\SecurityHelper;

/**
 * Questions controller
 *
 * @since  2.0.0
 */
class QuestionsController extends AdminController
{
    /**
     * Proxy for getModel
     *
     * @param   string  $name   The model name. Optional.
     * @param   string  $prefix The class prefix. Optional.
     * @param   array   $config Configuration array for model. Optional.
     *
     * @return  object
     *
     * @since   2.0.0
     */
    public function getModel($name = 'Question', $prefix = 'Administrator', $config = array('ignore_request' => true))
    {
        return parent::getModel($name, $prefix, $config);
    }

    /**
     * Method to publish or unpublish items
     *
     * @return  void
     *
     * @since   2.0.0
     */
    public function publish()
    {
        $this->checkToken();
        
        $user = Factory::getUser();
        
        if (!$user->authorise('core.edit.state', 'com_question')) {
            throw new \Exception(Text::_('JLIB_APPLICATION_ERROR_EDITSTATE_NOT_PERMITTED'), 403);
        }

        parent::publish();
    }

    /**
     * Method to delete items
     *
     * @return  void
     *
     * @since   2.0.0
     */
    public function delete()
    {
        $this->checkToken();
        
        $user = Factory::getUser();
        
        if (!$user->authorise('core.delete', 'com_question')) {
            throw new \Exception(Text::_('JLIB_APPLICATION_ERROR_DELETE_NOT_PERMITTED'), 403);
        }

        parent::delete();
    }

    /**
     * Method to save items
     *
     * @return  void
     *
     * @since   2.0.0
     */
    public function save()
    {
        $this->checkToken();
        
        $user = Factory::getUser();
        
        if (!$user->authorise('core.edit', 'com_question')) {
            throw new \Exception(Text::_('JLIB_APPLICATION_ERROR_EDITITEM_NOT_PERMITTED'), 403);
        }

        parent::save();
    }
}
```

---

## Phase 8: Testing and Validation

### Step 8.1: Create Test Suite

**File**: `administrator/components/com_question/tests/Unit/Helper/SecurityHelperTest.php`

```php
<?php
/**
 * Security Helper Tests
 * 
 * @package     Question\Component\Question
 * @subpackage  Tests
 * @version     2.0.0
 * @license     GNU General Public License version 3 or later
 */

namespace Question\Component\Question\Administrator\Tests\Unit\Helper;

defined('_JEXEC') or die;

use PHPUnit\Framework\TestCase;
use Question\Component\Question\Administrator\Helper\SecurityHelper;

/**
 * Security helper test case
 *
 * @since  2.0.0
 */
class SecurityHelperTest extends TestCase
{
    /**
     * Test sanitizing text
     *
     * @return  void
     *
     * @since   2.0.0
     */
    public function testSanitizeText()
    {
        $input = '<script>alert("xss")</script>Test';
        $expected = 'alert(&quot;xss&quot;)Test';
        $result = SecurityHelper::sanitizeText($input);
        
        $this->assertStringContainsString('Test', $result);
        $this->assertStringNotContainsString('<script>', $result);
    }

    /**
     * Test IP validation
     *
     * @return  void
     *
     * @since   2.0.0
     */
    public function testValidateIpAddress()
    {
        $this->assertTrue(SecurityHelper::validateIpAddress('192.168.1.1'));
        $this->assertTrue(SecurityHelper::validateIpAddress('::1'));
        $this->assertFalse(SecurityHelper::validateIpAddress('invalid-ip'));
        $this->assertFalse(SecurityHelper::validateIpAddress('999.999.999.999'));
    }

    /**
     * Test secure token generation
     *
     * @return  void
     *
     * @since   2.0.0
     */
    public function testGenerateSecureToken()
    {
        $token1 = SecurityHelper::generateSecureToken();
        $token2 = SecurityHelper::generateSecureToken();
        
        $this->assertEquals(64, strlen($token1));
        $this->assertNotEquals($token1, $token2);
        $this->assertTrue(ctype_xdigit($token1));
    }

    /**
     * Test password hashing
     *
     * @return  void
     *
     * @since   2.0.0
     */
    public function testPasswordHash()
    {
        $password = 'test_password_123';
        $hash = SecurityHelper::hashPassword($password);
        
        $this->assertNotEquals($password, $hash);
        $this->assertTrue(SecurityHelper::verifyPassword($password, $hash));
        $this->assertFalse(SecurityHelper::verifyPassword('wrong_password', $hash));
    }
}
```

### Step 8.2: Create Integration Test

**File**: `administrator/components/com_question/tests/Integration/InstallationTest.php`

```php
<?php
/**
 * Installation Tests
 * 
 * @package     Question\Component\Question
 * @subpackage  Tests
 * @version     2.0.0
 * @license     GNU General Public License version 3 or later
 */

namespace Question\Component\Question\Administrator\Tests\Integration;

defined('_JEXEC') or die;

use PHPUnit\Framework\TestCase;
use Joomla\CMS\Factory;

/**
 * Installation test case
 *
 * @since  2.0.0
 */
class InstallationTest extends TestCase
{
    /**
     * Test component tables exist
     *
     * @return  void
     *
     * @since   2.0.0
     */
    public function testComponentTablesExist()
    {
        $db = Factory::getDbo();
        
        $tables = [
            '#__question_questions',
            '#__question_answers',
            '#__question_votes',
            '#__question_languages',
            '#__question_events',
            '#__question_reputation',
            '#__question_flags',
            '#__question_tags'
        ];
        
        foreach ($tables as $table) {
            $query = $db->getQuery(true)
                ->select('COUNT(*)')
                ->from($table);
            
            $db->setQuery($query);
            
            // This should not throw an exception
            $result = $db->loadResult();
            $this->assertIsNumeric($result);
        }
    }

    /**
     * Test component is registered
     *
     * @return  void
     *
     * @since   2.0.0
     */
    public function testComponentRegistered()
    {
        $db = Factory::getDbo();
        
        $query = $db->getQuery(true)
            ->select('id')
            ->from($db->quoteName('#__extensions'))
            ->where($db->quoteName('element') . ' = ' . $db->quote('com_question'))
            ->where($db->quoteName('type') . ' = ' . $db->quote('component'));
        
        $db->setQuery($query);
        $result = $db->loadResult();
        
        $this->assertNotEmpty($result);
    }
}
```

### Step 8.3: Testing Checklist

```markdown
## Pre-Deployment Testing Checklist

### Unit Tests
- [ ] SecurityHelper: sanitizeText()
- [ ] SecurityHelper: sanitizeHtml()
- [ ] SecurityHelper: validateIpAddress()
- [ ] SecurityHelper: generateSecureToken()
- [ ] SecurityHelper: hashPassword()
- [ ] SecurityHelper: checkRateLimit()

### Integration Tests
- [ ] Component installation succeeds
- [ ] All database tables created
- [ ] Component registered in extensions
- [ ] Default categories created
- [ ] Default permissions set

### Functional Tests
- [ ] Frontend question list loads
- [ ] Question detail page displays correctly
- [ ] Ask question form submission works
- [ ] Answer posting functionality works
- [ ] Voting system functions
- [ ] Live update system works (SSE/AJAX fallback)

### Browser Compatibility Tests
- [ ] Chrome/Chromium latest
- [ ] Firefox latest
- [ ] Safari latest
- [ ] Edge latest
- [ ] Mobile browsers (iOS Safari, Chrome Mobile)

### Security Tests
- [ ] XSS prevention
- [ ] SQL injection prevention
- [ ] CSRF token validation
- [ ] Rate limiting works
- [ ] Permission checks enforced
- [ ] Input sanitization working

### Performance Tests
- [ ] Page load time < 2 seconds
- [ ] Database queries optimized
- [ ] Proper indexing
- [ ] No N+1 query problems

### Accessibility Tests
- [ ] WCAG 2.1 AA compliance
- [ ] Keyboard navigation works
- [ ] Screen reader compatible
- [ ] Color contrast adequate

### Joomla Compatibility Tests
- [ ] Joomla 6.1 core functions work
- [ ] PHP 8.2+ compatibility confirmed
- [ ] MySQL 8.0+ / MariaDB 10.4+ tested
- [ ] Namespaced classes working
- [ ] MVC architecture functioning
```

---

## Phase 9: Deployment

### Step 9.1: Pre-Deployment Checklist

```bash
# 1. Final code review
git log --oneline -10

# 2. Verify all files are in place
find . -type f -name "*.php" | head -20

# 3. Run tests
phpunit administrator/components/com_question/tests/Unit/
phpunit administrator/components/com_question/tests/Integration/

# 4. Check for PHP errors
php -l script.php
php -l com_question.xml

# 5. Verify file permissions
chmod -R 755 administrator/components/com_question/
chmod -R 755 components/com_question/
chmod -R 755 media/com_question/

# 6. Create deployment package
tar -czf com_question-2.0.0.tar.gz \
    script.php \
    com_question.xml \
    administrator/ \
    components/ \
    media/
```

### Step 9.2: Installation Steps on Server

```bash
#!/bin/bash
# Joomla 6.1 com_question Deployment Script

set -e

JOOMLA_PATH="/var/www/joomla"
BACKUP_PATH="/backups/$(date +%Y%m%d_%H%M%S)"

# 1. Create backup
echo "Creating backup..."
mkdir -p $BACKUP_PATH
cp -r $JOOMLA_PATH $BACKUP_PATH/joomla_backup
mysqldump -u joomla_user -p joomla_db > $BACKUP_PATH/joomla_db.sql

# 2. Extract component
echo "Extracting component files..."
cd /tmp
tar -xzf com_question-2.0.0.tar.gz

# 3. Copy files
echo "Copying files..."
cp -r administrator/components/com_question $JOOMLA_PATH/administrator/components/
cp -r components/com_question $JOOMLA_PATH/components/
cp -r media/com_question $JOOMLA_PATH/media/
cp script.php $JOOMLA_PATH/
cp com_question.xml $JOOMLA_PATH/

# 4. Set permissions
echo "Setting permissions..."
chown -R www-data:www-data $JOOMLA_PATH/components/com_question
chown -R www-data:www-data $JOOMLA_PATH/administrator/components/com_question
chown -R www-data:www-data $JOOMLA_PATH/media/com_question

# 5. Install via Joomla UI
echo "Installation ready. Please complete installation via Joomla Administrator panel."
echo "Navigate to: System → Extensions → Install"
echo "Upload: com_question-2.0.0.zip"

# 6. Verify installation
echo "Verifying installation..."
mysql -u joomla_user -p joomla_db -e "SHOW TABLES LIKE '%question%';"

echo "Deployment complete!"
```

### Step 9.3: Post-Installation Verification

```bash
#!/bin/bash
# Post-Installation Verification Script

echo "=== Joomla 6.1 com_question Post-Installation Verification ==="
echo ""

# 1. Check component tables
echo "Checking database tables..."
mysql -u joomla_user -p joomla_db -e "
SELECT TABLE_NAME 
FROM INFORMATION_SCHEMA.TABLES 
WHERE TABLE_SCHEMA='joomla_db' 
AND TABLE_NAME LIKE '%question%'
ORDER BY TABLE_NAME;"

echo ""
echo "Checking component registration..."
mysql -u joomla_user -p joomla_db -e "
SELECT name, version, enabled 
FROM jos_extensions 
WHERE element='com_question';"

echo ""
echo "Checking file permissions..."
ls -la /var/www/joomla/components/com_question/
ls -la /var/www/joomla/administrator/components/com_question/

echo ""
echo "Verification complete!"
```

### Step 9.4: Rollback Plan

```bash
#!/bin/bash
# Rollback Script

BACKUP_PATH=$1

if [ -z "$BACKUP_PATH" ]; then
    echo "Usage: $0 <backup_path>"
    exit 1
fi

if [ ! -d "$BACKUP_PATH" ]; then
    echo "Backup path not found: $BACKUP_PATH"
    exit 1
fi

echo "Rolling back from: $BACKUP_PATH"

# 1. Stop web server
systemctl stop apache2

# 2. Restore files
echo "Restoring files..."
rm -rf /var/www/joomla
cp -r $BACKUP_PATH/joomla_backup /var/www/joomla

# 3. Restore database
echo "Restoring database..."
mysql -u root -p joomla_db < $BACKUP_PATH/joomla_db.sql

# 4. Restore permissions
chown -R www-data:www-data /var/www/joomla

# 5. Start web server
systemctl start apache2

echo "Rollback complete!"
```

---

## Troubleshooting Guide

### Common Issues and Solutions

#### 1. **"Minimum Joomla version not met" Error**

**Problem**: Installation fails with version check error.

**Solution**:
```php
// Verify in script.php
$minimumJoomla = '6.1';
echo "Current Joomla: " . JVERSION;
echo "Required: " . $minimumJoomla;
```

#### 2. **Database Connection Error**

**Problem**: Cannot connect to database during installation.

**Solution**:
```bash
# Check database credentials
cat /var/www/joomla/configuration.php | grep -i "host\|user\|password"

# Test connection
mysql -h db_host -u db_user -p -e "SELECT 1;"
```

#### 3. **File Permission Issues**

**Problem**: "Permission denied" when creating tables.

**Solution**:
```bash
# Fix permissions
chown -R www-data:www-data /var/www/joomla
chmod -R 755 /var/www/joomla/components/com_question
chmod -R 755 /var/www/joomla/administrator/components/com_question
```

#### 4. **PHP Version Mismatch**

**Problem**: "PHP version 8.2+ required" but 8.1 installed.

**Solution**:
```bash
# Check PHP version
php -v

# Update PHP (Ubuntu/Debian)
sudo apt update
sudo apt install php8.2 php8.2-mysql php8.2-json
sudo a2enmod php8.2
sudo systemctl restart apache2
```

#### 5. **Namespace Issues**

**Problem**: "Class not found" errors in logs.

**Solution**:
```php
// Verify namespace declarations in all classes
namespace Question\Component\Question\Administrator\Extension;

// Check PSR-4 autoloading in manifest
<namespace path="src">Question\Component\Question</namespace>
```

---

## Documentation References

- [Joomla 6.1 Developer Documentation](https://docs.joomla.org/)
- [Joomla Component Development Guide](https://docs.joomla.org/Developing_an_MVC_Component)
- [PHP 8.2 Documentation](https://www.php.net/manual/en/index.php)
- [MySQL 8.0 Reference Manual](https://dev.mysql.com/doc/refman/8.0/en/)

---

## Support and Contributions

For issues, questions, or contributions:

1. **Report Issues**: GitHub Issues
2. **Discussions**: GitHub Discussions
3. **Pull Requests**: Feature branches and PRs welcome
4. **Documentation**: Update docs/ folder

---

## Version History

| Version | Date       | Status      | Notes                           |
|---------|------------|-------------|--------------------------------|
| 2.0.0   | 2026-05-28 | Complete    | Joomla 6.1 Migration Complete   |
| 1.3.0   | 2026-01-02 | Deprecated  | Last Joomla 4.0 Compatible      |

---

**Document Status**: Ready for Implementation  
**Last Updated**: 2026-05-28  
**Next Review**: 2026-08-28
