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

use Joomla\CMS\MVC\Controller\BaseController;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;

/**
 * Component Controller
 *
 * @since  1.0.0
 */
class DisplayController extends BaseController
{
    /**
     * The default view.
     *
     * @var    string
     */
    protected $default_view = 'questions';

    /**
     * Method to display a view.
     *
     * @param   boolean  $cachable   If true, the view output will be cached
     * @param   array    $urlparams  An array of safe URL parameters and their variable types
     *
     * @return  static  This object to support chaining.
     */
    public function display($cachable = false, $urlparams = [])
    {
        $view   = $this->input->get('view', $this->default_view);
        $layout = $this->input->get('layout', 'default');
        $id     = $this->input->getInt('id');

        // Check for edit form.
        if ($view === 'question' && $layout === 'edit' && !$this->checkEditId('com_question.edit.question', $id)) {
            // Somehow the person just went to the form - we don't allow that.
            $this->setMessage(Text::sprintf('JLIB_APPLICATION_ERROR_UNHELD_ID', $id), 'error');
            $this->setRedirect(Route::_('index.php?option=com_question&view=questions', false));

            return false;
        } elseif ($view === 'answer' && $layout === 'edit' && !$this->checkEditId('com_question.edit.answer', $id)) {
            // Somehow the person just went to the form - we don't allow that.
            $this->setMessage(Text::sprintf('JLIB_APPLICATION_ERROR_UNHELD_ID', $id), 'error');
            $this->setRedirect(Route::_('index.php?option=com_question&view=answers', false));

            return false;
        } elseif ($view === 'language' && $layout === 'edit' && !$this->checkEditId('com_question.edit.language', $id)) {
            // Somehow the person just went to the form - we don't allow that.
            $this->setMessage(Text::sprintf('JLIB_APPLICATION_ERROR_UNHELD_ID', $id), 'error');
            $this->setRedirect(Route::_('index.php?option=com_question&view=languages', false));

            return false;
        }

        return parent::display();
    }
}
