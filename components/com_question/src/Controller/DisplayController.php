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
        }

        // Set caching parameters
        if ($cachable && $view === 'questions') {
            $cachable = true;
            $user = Factory::getUser();

            if ($user->get('id')) {
                $cachable = false;
            }

            $safeurlparams = [
                'id'               => 'INT',
                'limit'            => 'UINT',
                'limitstart'       => 'UINT',
                'filter_order'     => 'CMD',
                'filter_order_Dir' => 'CMD',
                'lang'             => 'CMD',
                'catid'            => 'INT',
                'language'         => 'CMD'
            ];

            $this->appendPathway($view, $layout, $id);
        }

        return parent::display($cachable, $safeurlparams);
    }

    /**
     * Method to add pathway breadcrumbs
     *
     * @param   string  $view    The view name
     * @param   string  $layout  The layout name
     * @param   integer $id      The item ID
     *
     * @return  void
     */
    protected function appendPathway($view, $layout, $id)
    {
        $app = Factory::getApplication();
        $pathway = $app->getPathway();
        $menu = $app->getMenu();
        $active = $menu->getActive();

        if ($active && $active->component == 'com_question') {
            return;
        }

        // Add main component breadcrumb
        $pathway->addItem(Text::_('COM_QUESTION_QUESTIONS'), Route::_('index.php?option=com_question&view=questions'));

        switch ($view) {
            case 'question':
                if ($id) {
                    $model = $this->getModel('Question', 'Site');
                    $question = $model->getItem($id);
                    if ($question) {
                        $pathway->addItem($question->title);
                    }
                }
                break;

            case 'ask':
                $pathway->addItem(Text::_('COM_QUESTION_ASK_QUESTION'));
                break;

            case 'categories':
                $pathway->addItem(Text::_('COM_QUESTION_CATEGORIES'));
                break;
        }
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
    public function getModel($name = 'Questions', $prefix = 'Site', $config = ['ignore_request' => true])
    {
        return parent::getModel($name, $prefix, $config);
    }
}
