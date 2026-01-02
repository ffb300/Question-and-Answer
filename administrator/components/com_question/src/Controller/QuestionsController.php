<?php
/**
 * @package     Joomla.Administrator
 * @subpackage  com_question
 *
 * @copyright   Copyright (C) 2026
 * @license     GNU General Public License version 2 or later
 */

namespace Question\Component\Question\Administrator\Controller;

\defined('_JEXEC') or die;

use Joomla\CMS\MVC\Controller\AdminController;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
use Joomla\Utilities\ArrayHelper;

/**
 * Questions list controller class.
 *
 * @since  1.0.0
 */
class QuestionsController extends AdminController
{
    /**
     * Method to get a model object, loading it if required.
     *
     * @param   string  $name    The model name.
     * @param   string  $prefix  The class prefix.
     * @param   array   $config  Configuration array for model.
     *
     * @return  object  The model.
     */
    public function getModel($name = 'Question', $prefix = 'Administrator', $config = ['ignore_request' => true])
    {
        return parent::getModel($name, $prefix, $config);
    }

    /**
     * Method to publish a list of items
     *
     * @return  void
     */
    public function publish()
    {
        // Check for request forgeries
        $this->checkToken();

        // Get items to publish from the request.
        $cid   = $this->input->get('cid', [], 'array');
        $data  = ['publish' => 1, 'unpublish' => 0, 'archive' => 2, 'trash' => -2, 'report' => -3];
        $task  = $this->getTask();
        $value = ArrayHelper::getValue($data, $task, 0, 'int');

        if (empty($cid)) {
            $this->setMessage(Text::_('COM_QUESTION_NO_ITEM_SELECTED'), 'warning');
        } else {
            // Get the model.
            $model = $this->getModel();

            // Make sure the item ids are integers
            $cid = ArrayHelper::toInteger($cid);

            // Publish the items.
            try {
                $model->publish($cid, $value);
                $ntext = ($value === 1) ? 'COM_QUESTION_N_ITEMS_PUBLISHED' : 'COM_QUESTION_N_ITEMS_UNPUBLISHED';
                $this->setMessage(Text::plural($ntext, count($cid)));
            } catch (\Exception $e) {
                $this->setMessage($e->getMessage(), 'error');
            }
        }

        $extension = $this->input->get('extension');
        $extensionURL = ($extension) ? '&extension=' . $extension : '';
        $this->setRedirect(Route::_('index.php?option=com_question&view=questions' . $extensionURL, false));
    }

    /**
     * Method to delete a record.
     *
     * @return  boolean  True if successful, false otherwise and internal error is set.
     */
    public function delete()
    {
        // Check for request forgeries
        $this->checkToken();

        // Get items to remove from the request.
        $cid = $this->input->get('cid', [], 'array');

        if (!is_array($cid) || count($cid) < 1) {
            $this->setMessage(Text::_('COM_QUESTION_NO_ITEM_SELECTED'), 'warning');
        } else {
            // Get the model.
            $model = $this->getModel();

            // Make sure the item ids are integers
            $cid = ArrayHelper::toInteger($cid);

            // Remove the items.
            try {
                $model->delete($cid);
                $this->setMessage(Text::plural('COM_QUESTION_N_ITEMS_DELETED', count($cid)));
            } catch (\Exception $e) {
                $this->setMessage($e->getMessage(), 'error');
            }
        }

        $this->setRedirect(Route::_('index.php?option=com_question&view=questions', false));
    }

    /**
     * Method to archive a record.
     *
     * @return  void
     */
    public function archive()
    {
        $this->publish();
    }

    /**
     * Method to trash a record.
     *
     * @return  void
     */
    public function trash()
    {
        $this->publish();
    }

    /**
     * Method to save the submitted ordering values for records.
     *
     * @return  boolean  True on success
     */
    public function saveorder()
    {
        // Check for request forgeries.
        $this->checkToken();

        // Get the input
        $pks   = $this->input->post->get('cid', [], 'array');
        $order = $this->input->post->get('order', [], 'array');

        // Sanitize the input
        $pks   = ArrayHelper::toInteger($pks);
        $order = ArrayHelper::toInteger($order);

        // Get the model
        $model = $this->getModel();

        // Save the ordering
        $return = $model->saveorder($pks, $order);

        if ($return === false) {
            // Reorder failed
            $this->setMessage(Text::sprintf('JLIB_APPLICATION_ERROR_REORDER_FAILED', $model->getError()), 'error');
            $this->setRedirect(Route::_('index.php?option=com_question&view=questions', false));

            return false;
        } else {
            // Reorder succeeded.
            $this->setMessage(Text::_('JLIB_APPLICATION_SUCCESS_ORDERING_SAVED'), 'message');
            $this->setRedirect(Route::_('index.php?option=com_question&view=questions', false));

            return true;
        }
    }

    /**
     * Method to create the labels for saving the order.
     *
     * @return  void
     */
    public function reorder()
    {
        // Check for request forgeries
        $this->checkToken();

        // Get the input
        $pks   = $this->input->post->get('cid', [], 'array');
        $order = $this->input->post->get('order', [], 'array');

        // Sanitize the input
        $pks   = ArrayHelper::toInteger($pks);
        $order = ArrayHelper::toInteger($order);

        // Get the model
        $model = $this->getModel();

        // Save the ordering
        $return = $model->reorder($pks, $order);

        if ($return === false) {
            // Reorder failed
            $this->setMessage(Text::sprintf('JLIB_APPLICATION_ERROR_REORDER_FAILED', $model->getError()), 'error');
            $this->setRedirect(Route::_('index.php?option=com_question&view=questions', false));

            return false;
        } else {
            // Reorder succeeded.
            $this->setMessage(Text::_('JLIB_APPLICATION_SUCCESS_ORDERING_SAVED'), 'message');
            $this->setRedirect(Route::_('index.php?option=com_question&view=questions', false));

            return true;
        }
    }

    /**
     * Method to check in selected items.
     *
     * @return  void
     */
    public function checkin()
    {
        // Check for request forgeries
        $this->checkToken();

        // Get items to checkin from the request.
        $cid = $this->input->get('cid', [], 'array');

        if (empty($cid)) {
            $this->setMessage(Text::_('COM_QUESTION_NO_ITEM_SELECTED'), 'warning');
        } else {
            // Get the model.
            $model = $this->getModel();

            // Make sure the item ids are integers
            $cid = ArrayHelper::toInteger($cid);

            // Check in the items.
            try {
                $model->checkin($cid);
                $this->setMessage(Text::plural('COM_QUESTION_N_ITEMS_CHECKED_IN', count($cid)));
            } catch (\Exception $e) {
                $this->setMessage($e->getMessage(), 'error');
            }
        }

        $this->setRedirect(Route::_('index.php?option=com_question&view=questions', false));
    }
}
