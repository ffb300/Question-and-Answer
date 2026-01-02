<?php
/**
 * Script file for com_question component
 */

defined('_JEXEC') or die;

use Joomla\CMS\Application\CMSApplication;
use Joomla\CMS\Factory;
use Joomla\CMS\Installer\InstallerAdapter;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Log\Log;

/**
 * Script file of com_question component
 */
class Com_questionInstallerScript
{
    /**
     * Minimum Joomla version to check
     *
     * @var    string
     */
    protected $minimumJoomla = '4.0';

    /**
     * Minimum PHP version to check
     *
     * @var    string
     */
    protected $minimumPhp = '8.0';

    /**
     * Method to install the component
     *
     * @param   InstallerAdapter  $parent  The class calling this method
     *
     * @return  void
     */
    public function install($parent)
    {
        // Create default categories
        $this->createDefaultCategories();
    }

    /**
     * Method to uninstall the component
     *
     * @param   InstallerAdapter  $parent  The class calling this method
     *
     * @return  void
     */
    public function uninstall($parent)
    {
        // Clean up any remaining data
    }

    /**
     * Method to update the component
     *
     * @param   InstallerAdapter  $parent  The class calling this method
     *
     * @return  void
     */
    public function update($parent)
    {
        // Handle updates
    }

    /**
     * Method to run before an install/update/uninstall method
     *
     * @param   string            $type    The type of change (install, update or discover_install)
     * @param   InstallerAdapter  $parent  The class calling this method
     *
     * @return  void
     */
    public function preflight($type, $parent)
    {
        if ($type !== 'uninstall') {
            // Check minimum Joomla version
            if (!version_compare(JVERSION, $this->minimumJoomla, 'ge')) {
                Log::add(
                    Text::sprintf('JLIB_INSTALLER_MINIMUM_JOOMLA', $this->minimumJoomla),
                    Log::WARNING,
                    'jerror'
                );

                return false;
            }

            // Check minimum PHP version
            if (!version_compare(PHP_VERSION, $this->minimumPhp, 'ge')) {
                Log::add(
                    Text::sprintf('JLIB_INSTALLER_MINIMUM_PHP', $this->minimumPhp),
                    Log::WARNING,
                    'jerror'
                );

                return false;
            }
        }
    }

    /**
     * Method to run after an install/update/uninstall method
     *
     * @param   string            $type    The type of change (install, update or discover_install)
     * @param   InstallerAdapter  $parent  The class calling this method
     *
     * @return  void
     */
    public function postflight($type, $parent)
    {
        if ($type === 'install') {
            // Enable the plugin
            $db = Factory::getDbo();
            $query = $db->getQuery(true)
                ->update($db->quoteName('#__extensions'))
                ->set($db->quoteName('enabled') . ' = 1')
                ->where($db->quoteName('element') . ' = ' . $db->quote('com_question'))
                ->where($db->quoteName('type') . ' = ' . $db->quote('component'));
            $db->setQuery($query);
            $db->execute();
        }
    }

    /**
     * Create default categories for questions
     *
     * @return  void
     */
    protected function createDefaultCategories()
    {
        $db = Factory::getDbo();
        
        // Check if categories already exist
        $query = $db->getQuery(true)
            ->select('COUNT(*)')
            ->from($db->quoteName('#__categories'))
            ->where($db->quoteName('extension') . ' = ' . $db->quote('com_question'));
        $db->setQuery($query);
        $count = $db->loadResult();
        
        if ($count > 0) {
            return;
        }
        
        // Create default categories
        $categories = [
            [
                'title' => 'General',
                'alias' => 'general',
                'description' => 'General questions',
                'parent_id' => 1,
                'level' => 1,
                'path' => 'general'
            ],
            [
                'title' => 'Technology',
                'alias' => 'technology',
                'description' => 'Technology related questions',
                'parent_id' => 1,
                'level' => 1,
                'path' => 'technology'
            ],
            [
                'title' => 'Science',
                'alias' => 'science',
                'description' => 'Science related questions',
                'parent_id' => 1,
                'level' => 1,
                'path' => 'science'
            ],
            [
                'title' => 'Business',
                'alias' => 'business',
                'description' => 'Business related questions',
                'parent_id' => 1,
                'level' => 1,
                'path' => 'business'
            ]
        ];
        
        $user = Factory::getUser();
        $date = Factory::getDate();
        
        foreach ($categories as $category) {
            $object = (object) [
                'id' => 0,
                'parent_id' => $category['parent_id'],
                'level' => $category['level'],
                'path' => $category['path'],
                'title' => $category['title'],
                'alias' => $category['alias'],
                'extension' => 'com_question',
                'published' => 1,
                'language' => '*',
                'created_time' => $date->toSql(),
                'created_user_id' => $user->id,
                'description' => $category['description'],
                'access' => 1,
                'params' => '{}'
            ];
            
            $db->insertObject('#__categories', $object);
        }
    }
}
