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
use Joomla\CMS\Plugin\PluginHelper;

/**
 * Vote Model for handling votes on questions and answers
 *
 * @since  1.0.0
 */
class VoteModel extends BaseDatabaseModel
{
    /**
     * Vote up (1) or down (-1)
     */
    const VOTE_UP = 1;
    const VOTE_DOWN = -1;

    /**
     * Item types
     */
    const TYPE_QUESTION = 'question';
    const TYPE_ANSWER = 'answer';

    /**
     * Method to vote on an item
     *
     * @param   integer  $itemId    The item ID
     * @param   string   $itemType  The item type (question or answer)
     * @param   integer  $vote      The vote value (1 for up, -1 for down)
     * @param   integer  $userId    The user ID (optional, defaults to current user)
     *
     * @return  boolean  True on success
     */
    public function vote($itemId, $itemType, $vote, $userId = null)
    {
        $user = Factory::getUser();
        $userId = $userId ?: $user->id;
        $db = $this->getDbo();

        // Validate input
        if (!in_array($itemType, [self::TYPE_QUESTION, self::TYPE_ANSWER])) {
            $this->setError(Text::_('COM_QUESTION_VOTE_ERROR_INVALID_ITEM_TYPE'));
            return false;
        }

        if (!in_array($vote, [self::VOTE_UP, self::VOTE_DOWN])) {
            $this->setError(Text::_('COM_QUESTION_VOTE_ERROR_INVALID_VOTE'));
            return false;
        }

        // Check if user is logged in
        if (!$userId) {
            $this->setError(Text::_('COM_QUESTION_VOTE_ERROR_LOGIN_REQUIRED'));
            return false;
        }

        // Check if user has permission to vote
        if (!$user->authorise('vote.answer', 'com_question')) {
            $this->setError(Text::_('COM_QUESTION_VOTE_ERROR_NO_PERMISSION'));
            return false;
        }

        // Check if item exists
        $table = $itemType === self::TYPE_QUESTION ? '#__question_questions' : '#__question_answers';
        $query = $db->getQuery(true)
            ->select('id, created_by')
            ->from($db->quoteName($table))
            ->where($db->quoteName('id') . ' = ' . (int) $itemId);
        $db->setQuery($query);
        $item = $db->loadObject();

        if (!$item) {
            $this->setError(Text::_('COM_QUESTION_VOTE_ERROR_ITEM_NOT_FOUND'));
            return false;
        }

        // Check if user is voting on their own content
        if ($item->created_by == $userId) {
            $this->setError(Text::_('COM_QUESTION_VOTE_ERROR_OWN_CONTENT'));
            return false;
        }

        // Start transaction
        $db->transactionStart();

        try {
            // Check if user has already voted
            $query = $db->getQuery(true)
                ->select('id, vote')
                ->from($db->quoteName('#__question_votes'))
                ->where($db->quoteName('item_id') . ' = ' . (int) $itemId)
                ->where($db->quoteName('item_type') . ' = ' . $db->quote($itemType))
                ->where($db->quoteName('user_id') . ' = ' . (int) $userId);
            $db->setQuery($query);
            $existingVote = $db->loadObject();

            if ($existingVote) {
                // Update existing vote
                if ($existingVote->vote == $vote) {
                    // Remove vote if same vote
                    $query = $db->getQuery(true)
                        ->delete($db->quoteName('#__question_votes'))
                        ->where($db->quoteName('id') . ' = ' . (int) $existingVote->id);
                    $db->setQuery($query);
                    $db->execute();

                    $voteChange = -$vote;
                } else {
                    // Change vote
                    $query = $db->getQuery(true)
                        ->update($db->quoteName('#__question_votes'))
                        ->set($db->quoteName('vote') . ' = ' . (int) $vote)
                        ->set($db->quoteName('created') . ' = ' . $db->quote(Factory::getDate()->toSql()))
                        ->where($db->quoteName('id') . ' = ' . (int) $existingVote->id);
                    $db->setQuery($query);
                    $db->execute();

                    $voteChange = $vote - $existingVote->vote;
                }
            } else {
                // Insert new vote
                $voteRecord = (object) [
                    'item_id' => $itemId,
                    'item_type' => $itemType,
                    'user_id' => $userId,
                    'vote' => $vote,
                    'created' => Factory::getDate()->toSql(),
                    'ip_address' => Factory::getApplication()->input->server->get('REMOTE_ADDR', '', 'string')
                ];

                $db->insertObject('#__question_votes', $voteRecord);
                $voteChange = $vote;
            }

            // Update vote counts on the item
            if ($itemType === self::TYPE_QUESTION) {
                $query = $db->getQuery(true)
                    ->update($db->quoteName('#__question_questions'));
            } else {
                $query = $db->getQuery(true)
                    ->update($db->quoteName('#__question_answers'));
            }

            if ($voteChange > 0) {
                $query->set($db->quoteName('votes_up') . ' = ' . $db->quoteName('votes_up') . ' + ' . abs($voteChange));
            } else {
                $query->set($db->quoteName('votes_up') . ' = ' . $db->quoteName('votes_up') . ' - ' . abs($voteChange));
            }

            if ($voteChange < 0) {
                $query->set($db->quoteName('votes_down') . ' = ' . $db->quoteName('votes_down') . ' + ' . abs($voteChange));
            } else {
                $query->set($db->quoteName('votes_down') . ' = ' . $db->quoteName('votes_down') . ' - ' . abs($voteChange));
            }

            $query->where($db->quoteName('id') . ' = ' . (int) $itemId);
            $db->setQuery($query);
            $db->execute();

            // Create event for live update
            $this->createEvent('vote_update', $this->getQuestionId($itemId, $itemType), $itemId, $userId, [
                'item_type' => $itemType,
                'vote_change' => $voteChange,
                'new_votes_up' => $this->getVoteCount($itemId, $itemType, self::VOTE_UP),
                'new_votes_down' => $this->getVoteCount($itemId, $itemType, self::VOTE_DOWN)
            ]);

            $db->transactionCommit();

            // Trigger plugin event
            PluginHelper::importPlugin('content');
            Factory::getApplication()->triggerEvent('onQuestionVote', [$itemId, $itemType, $vote, $userId]);

            return true;
        } catch (\Exception $e) {
            $db->transactionRollback();
            $this->setError($e->getMessage());
            return false;
        }
    }

    /**
     * Method to get user's vote on an item
     *
     * @param   integer  $itemId    The item ID
     * @param   string   $itemType  The item type (question or answer)
     * @param   integer  $userId    The user ID (optional, defaults to current user)
     *
     * @return  integer  The vote value (1, -1, or 0 if no vote)
     */
    public function getUserVote($itemId, $itemType, $userId = null)
    {
        $user = Factory::getUser();
        $userId = $userId ?: $user->id;

        if (!$userId) {
            return 0;
        }

        $db = $this->getDbo();
        $query = $db->getQuery(true)
            ->select('vote')
            ->from($db->quoteName('#__question_votes'))
            ->where($db->quoteName('item_id') . ' = ' . (int) $itemId)
            ->where($db->quoteName('item_type') . ' = ' . $db->quote($itemType))
            ->where($db->quoteName('user_id') . ' = ' . (int) $userId);
        $db->setQuery($query);

        return (int) $db->loadResult();
    }

    /**
     * Method to get vote counts for an item
     *
     * @param   integer  $itemId    The item ID
     * @param   string   $itemType  The item type (question or answer)
     *
     * @return  object  Object with votes_up and votes_down properties
     */
    public function getVoteCounts($itemId, $itemType)
    {
        $db = $this->getDbo();
        $table = $itemType === self::TYPE_QUESTION ? '#__question_questions' : '#__question_answers';
        
        $query = $db->getQuery(true)
            ->select('votes_up, votes_down')
            ->from($db->quoteName($table))
            ->where($db->quoteName('id') . ' = ' . (int) $itemId);
        $db->setQuery($query);

        return $db->loadObject();
    }

    /**
     * Method to get vote count for a specific vote type
     *
     * @param   integer  $itemId    The item ID
     * @param   string   $itemType  The item type (question or answer)
     * @param   integer  $voteType  The vote type (1 for up, -1 for down)
     *
     * @return  integer  The vote count
     */
    protected function getVoteCount($itemId, $itemType, $voteType)
    {
        $db = $this->getDbo();
        $table = $itemType === self::TYPE_QUESTION ? '#__question_questions' : '#__question_answers';
        $field = $voteType === self::VOTE_UP ? 'votes_up' : 'votes_down';
        
        $query = $db->getQuery(true)
            ->select($field)
            ->from($db->quoteName($table))
            ->where($db->quoteName('id') . ' = ' . (int) $itemId);
        $db->setQuery($query);

        return (int) $db->loadResult();
    }

    /**
     * Method to get the question ID for an item
     *
     * @param   integer  $itemId    The item ID
     * @param   string   $itemType  The item type (question or answer)
     *
     * @return  integer  The question ID
     */
    protected function getQuestionId($itemId, $itemType)
    {
        if ($itemType === self::TYPE_QUESTION) {
            return $itemId;
        }

        $db = $this->getDbo();
        $query = $db->getQuery(true)
            ->select('question_id')
            ->from($db->quoteName('#__question_answers'))
            ->where($db->quoteName('id') . ' = ' . (int) $itemId);
        $db->setQuery($query);

        return (int) $db->loadResult();
    }

    /**
     * Create an event for live updates
     *
     * @param   string   $eventType   The event type
     * @param   integer  $questionId  The question ID
     * @param   integer  $itemId      The item ID
     * @param   integer  $userId      The user ID
     * @param   array    $data        Additional data
     *
     * @return  void
     */
    protected function createEvent($eventType, $questionId, $itemId, $userId, $data = [])
    {
        $db = $this->getDbo();
        $event = (object) [
            'event_type' => $eventType,
            'question_id' => $questionId,
            'item_id' => $itemId,
            'user_id' => $userId,
            'data' => json_encode($data),
            'created' => Factory::getDate()->toSql(),
            'processed' => 0
        ];

        $db->insertObject('#__question_events', $event);
    }
}
