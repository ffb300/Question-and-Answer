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
use Joomla\CMS\MVC\Model\ListModel;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Uri\Uri;
use Joomla\Utilities\ArrayHelper;

/**
 * Questions model for the Question component
 *
 * @since  1.0.0
 */
class QuestionsModel extends ListModel
{
    /**
     * Constructor.
     *
     * @param   array  $config  An optional associative array of configuration settings.
     */
    public function __construct($config = [])
    {
        if (empty($config['filter_fields'])) {
            $config['filter_fields'] = [
                'id', 'q.id',
                'title', 'q.title',
                'language', 'q.language',
                'catid', 'q.catid',
                'created', 'q.created',
                'created_by', 'q.created_by',
                'hits', 'q.hits',
                'votes_up', 'q.votes_up',
                'featured', 'q.featured',
                'ordering', 'q.ordering',
                'category_title'
            ];
        }

        parent::__construct($config);
    }

    /**
     * Method to auto-populate the model state.
     *
     * Note. Calling getState in this method will result in recursion.
     *
     * @param   string  $ordering   An optional ordering field.
     * @param   string  $direction  An optional direction (asc|desc).
     *
     * @return  void
     */
    protected function populateState($ordering = 'q.created', $direction = 'desc')
    {
        $app = Factory::getApplication();

        // List state information
        $limit = $app->getUserStateFromRequest('com_question.questions.limit', 'limit', $app->get('list_limit', 20), 'uint');
        $this->setState('list.limit', $limit);

        $limitstart = $app->input->get('limitstart', 0, 'uint');
        $this->setState('list.start', $limitstart);

        $ordering = $app->input->get('filter_order', 'ordering', 'cmd');
        if (!in_array($ordering, $this->filter_fields)) {
            $ordering = 'q.created';
        }
        $this->setState('list.ordering', $ordering);

        $direction = $app->input->get('filter_order_Dir', 'desc', 'word');
        if (!in_array(strtolower($direction), ['asc', 'desc'])) {
            $direction = 'desc';
        }
        $this->setState('list.direction', $direction);

        // Filter state
        $search = $this->getUserStateFromRequest($this->context . '.filter.search', 'filter_search');
        $this->setState('filter.search', $search);

        $published = $this->getUserStateFromRequest($this->context . '.filter.published', 'filter_published', '');
        $this->setState('filter.published', $published);

        $categoryId = $this->getUserStateFromRequest($this->context . '.filter.category_id', 'filter_category_id');
        $this->setState('filter.category_id', $categoryId);

        $language = $this->getUserStateFromRequest($this->context . '.filter.language', 'filter_language');
        $this->setState('filter.language', $language);

        $authorId = $this->getUserStateFromRequest($this->context . '.filter.author_id', 'filter_author_id');
        $this->setState('filter.author_id', $authorId);

        // Load the parameters.
        $params = $app->getParams();
        $this->setState('params', $params);
    }

    /**
     * Method to get a store id based on model configuration state.
     *
     * This is necessary because the model is used by the component and
     * different modules that might need different sets of data or different
     * ordering requirements.
     *
     * @param   string  $id  A prefix for the store id.
     *
     * @return  string  A store id.
     */
    protected function getStoreId($id = '')
    {
        // Compile the store id.
        $id .= ':' . $this->getState('filter.search');
        $id .= ':' . $this->getState('filter.published');
        $id .= ':' . $this->getState('filter.category_id');
        $id .= ':' . $this->getState('filter.language');
        $id .= ':' . $this->getState('filter.author_id');

        return parent::getStoreId($id);
    }

    /**
     * Build an SQL query to load the list data.
     *
     * @return  \JDatabaseQuery
     */
    protected function getListQuery()
    {
        $user = Factory::getUser();
        $db = $this->getDbo();
        $query = $db->getQuery(true);

        // Select the required fields from the table.
        $query->select(
            $this->getState(
                'list.select',
                [
                    $db->quoteName('q.id'),
                    $db->quoteName('q.title'),
                    $db->quoteName('q.alias'),
                    $db->quoteName('q.body'),
                    $db->quoteName('q.language'),
                    $db->quoteName('q.catid'),
                    $db->quoteName('q.created_by'),
                    $db->quoteName('q.created_by_alias'),
                    $db->quoteName('q.created'),
                    $db->quoteName('q.modified'),
                    $db->quoteName('q.modified_by'),
                    $db->quoteName('q.published'),
                    $db->quoteName('q.publish_up'),
                    $db->quoteName('q.publish_down'),
                    $db->quoteName('q.featured'),
                    $db->quoteName('q.hits'),
                    $db->quoteName('q.votes_up'),
                    $db->quoteName('q.votes_down'),
                    $db->quoteName('q.tags'),
                    $db->quoteName('q.ordering'),
                    $db->quoteName('q.access'),
                ]
            )
        )
        ->select($db->quoteName('c.title', 'category_title'))
        ->select($db->quoteName('c.alias', 'category_alias'))
        ->select($db->quoteName('u.name', 'author_name'))
        ->select($db->quoteName('u.username', 'author_username'))
        ->select('COUNT(' . $db->quoteName('a.id') . ') as answer_count')
        ->from($db->quoteName('#__question_questions', 'q'))
        ->join('LEFT', $db->quoteName('#__categories', 'c') . ' ON c.id = q.catid')
        ->join('LEFT', $db->quoteName('#__users', 'u') . ' ON u.id = q.created_by')
        ->join('LEFT', $db->quoteName('#__question_answers', 'a') . ' ON a.question_id = q.id AND a.published = 1')
        ->where($db->quoteName('q.published') . ' = 1')
        ->where($db->quoteName('q.access') . ' IN (' . implode(',', $user->getAuthorisedViewLevels()) . ')')
        ->where('(q.publish_up = ' . $db->quote($db->getNullDate()) . ' OR q.publish_up <= ' . $db->quote(Factory::getDate()->toSql()) . ')')
        ->where('(q.publish_down = ' . $db->quote($db->getNullDate()) . ' OR q.publish_down >= ' . $db->quote(Factory::getDate()->toSql()) . ')')
        ->group($db->quoteName('q.id'));

        // Filter by category
        $categoryId = $this->getState('filter.category_id');
        if (is_numeric($categoryId)) {
            $query->where($db->quoteName('q.catid') . ' = ' . (int) $categoryId);
        } elseif (is_array($categoryId)) {
            $categoryId = ArrayHelper::toInteger($categoryId);
            $query->where($db->quoteName('q.catid') . ' IN (' . implode(',', $categoryId) . ')');
        }

        // Filter by language
        $language = $this->getState('filter.language');
        if ($language && $language !== '*') {
            $query->where($db->quoteName('q.language') . ' IN (' . $db->quote('*') . ', ' . $db->quote($language) . ')');
        }

        // Filter by author
        $authorId = $this->getState('filter.author_id');
        if (is_numeric($authorId)) {
            $query->where($db->quoteName('q.created_by') . ' = ' . (int) $authorId);
        }

        // Filter by search
        $search = $this->getState('filter.search');
        if (!empty($search)) {
            $search = $db->quote('%' . $db->escape($search, true) . '%');
            $query->where('(' . $db->quoteName('q.title') . ' LIKE ' . $search . ' OR ' . $db->quoteName('q.body') . ' LIKE ' . $search . ')');
        }

        // Add the list ordering clause.
        $orderCol = $this->getState('list.ordering', 'q.created');
        $orderDirn = $this->getState('list.direction', 'desc');

        // Special ordering for featured questions
        if ($orderCol === 'featured') {
            $orderCol = 'q.featured DESC, q.created';
        }

        $query->order($db->escape($orderCol) . ' ' . $db->escape($orderDirn));

        return $query;
    }

    /**
     * Method to get an array of data items.
     *
     * @return  mixed  An array of data items on success, false on failure.
     */
    public function getItems()
    {
        $items = parent::getItems();

        if ($items) {
            $user = Factory::getUser();
            $db = $this->getDbo();

            foreach ($items as &$item) {
                // Get user's vote for this question
                if (!$user->guest) {
                    $query = $db->getQuery(true)
                        ->select('vote')
                        ->from($db->quoteName('#__question_votes'))
                        ->where($db->quoteName('item_id') . ' = ' . (int) $item->id)
                        ->where($db->quoteName('item_type') . ' = ' . $db->quote('question'))
                        ->where($db->quoteName('user_id') . ' = ' . (int) $user->id);
                    $db->setQuery($query);
                    $item->user_vote = (int) $db->loadResult();
                } else {
                    $item->user_vote = 0;
                }

                // Create URL
                $item->url = Route::_('index.php?option=com_question&view=question&id=' . $item->id . ':' . $item->alias);
                $item->category_url = Route::_('index.php?option=com_question&view=questions&catid=' . $item->catid);

                // Truncate body for display
                $item->body_truncated = substr(strip_tags($item->body), 0, 200) . '...';

                // Format date
                $item->created_relative = $this->getRelativeTime($item->created);
            }
        }

        return $items;
    }

    /**
     * Get categories for filtering
     *
     * @return  array  Array of category objects
     */
    public function getCategories()
    {
        $db = $this->getDbo();
        $query = $db->getQuery(true)
            ->select('id, title, alias, level, path')
            ->from($db->quoteName('#__categories'))
            ->where($db->quoteName('extension') . ' = ' . $db->quote('com_question'))
            ->where($db->quoteName('published') . ' = 1')
            ->order($db->quoteName('lft') . ' ASC');
        $db->setQuery($query);

        return $db->loadObjectList();
    }

    /**
     * Get languages for filtering
     *
     * @return  array  Array of language objects
     */
    public function getLanguages()
    {
        $db = $this->getDbo();
        $query = $db->getQuery(true)
            ->select('lang_code, title, title_native')
            ->from($db->quoteName('#__question_languages'))
            ->where($db->quoteName('published') . ' = 1')
            ->order($db->quoteName('ordering') . ' ASC');
        $db->setQuery($query);

        return $db->loadObjectList();
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
}
