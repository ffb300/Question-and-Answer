/**
 * @package     Joomla.Site
 * @subpackage  com_question
 *
 * @copyright   Copyright (C) 2026
 * @license     GNU General Public License version 2 or later
 */

/**
 * Question Component Main JavaScript
 * 
 * @since  1.0.0
 */
window.QuestionComponent = (function() {
    'use strict';

    // Component state
    let state = {
        questionId: null,
        canVote: false,
        canAnswer: false,
        liveUpdate: null
    };

    /**
     * Initialize the component
     * 
     * @param {Object} options Configuration options
     */
    function init(options) {
        state.questionId = options.questionId || null;
        state.canVote = options.canVote || false;
        state.canAnswer = options.canAnswer || false;

        // Initialize voting
        initVoting();

        // Initialize answer submission
        if (state.canAnswer) {
            initAnswerSubmission();
        }

        // Initialize live updates if on question view
        if (state.questionId) {
            initLiveUpdates();
        }

        // Initialize ask question form
        initAskQuestionForm();
    }

    /**
     * Initialize voting functionality
     */
    function initVoting() {
        const voteButtons = document.querySelectorAll('.vote-button');
        
        voteButtons.forEach(function(button) {
            button.addEventListener('click', function(e) {
                e.preventDefault();
                
                if (!state.canVote) {
                    alert(Joomla.Text._('COM_QUESTION_VOTE_ERROR_NO_PERMISSION'));
                    return;
                }

                const itemId = this.dataset.itemId;
                const itemType = this.dataset.itemType;
                const vote = this.dataset.vote;
                
                voteItem(itemId, itemType, vote);
            });
        });
    }

    /**
     * Vote on an item
     * 
     * @param {number} itemId The item ID
     * @param {string} itemType The item type (question or answer)
     * @param {number} vote The vote value (1 or -1)
     */
    function voteItem(itemId, itemType, vote) {
        const url = 'index.php?option=com_question&task=ajax.vote&format=raw';
        const token = document.querySelector('input[name="' + Joomla.getOptions('csrf.token') + '"]').value;
        
        const formData = new FormData();
        formData.append('item_id', itemId);
        formData.append('item_type', itemType);
        formData.append('vote', vote);
        formData.append(token, '1');

        fetch(url, {
            method: 'POST',
            body: formData,
            credentials: 'same-origin'
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                updateVoteDisplay(itemId, data.votes_up, data.votes_down, data.user_vote);
            } else {
                alert(data.error || Joomla.Text._('COM_QUESTION_VOTE_ERROR'));
            }
        })
        .catch(error => {
            console.error('Vote error:', error);
            alert(Joomla.Text._('COM_QUESTION_VOTE_ERROR'));
        });
    }

    /**
     * Update vote display
     * 
     * @param {number} itemId The item ID
     * @param {number} votesUp Number of up votes
     * @param {number} votesDown Number of down votes
     * @param {number} userVote User's vote
     */
    function updateVoteDisplay(itemId, votesUp, votesDown, userVote) {
        const upButton = document.getElementById('vote-up-' + itemId);
        const downButton = document.getElementById('vote-down-' + itemId);
        const upCount = document.getElementById('vote-up-count-' + itemId);
        const downCount = document.getElementById('vote-down-count-' + itemId);

        if (upButton) {
            upButton.classList.toggle('active', userVote === 1);
        }
        if (downButton) {
            downButton.classList.toggle('active', userVote === -1);
        }
        if (upCount) {
            upCount.textContent = votesUp;
        }
        if (downCount) {
            downCount.textContent = votesDown;
        }
    }

    /**
     * Initialize answer submission
     */
    function initAnswerSubmission() {
        const answerForm = document.getElementById('answer-form');
        if (!answerForm) return;

        answerForm.addEventListener('submit', function(e) {
            e.preventDefault();
            submitAnswer();
        });

        // Character counter
        const answerBody = document.getElementById('answer-body');
        const charCounter = document.getElementById('answer-char-counter');
        
        if (answerBody && charCounter) {
            answerBody.addEventListener('input', function() {
                const remaining = 1000 - this.value.length;
                charCounter.textContent = remaining;
                charCounter.className = remaining < 50 ? 'text-danger' : '';
            });
        }
    }

    /**
     * Submit an answer
     */
    function submitAnswer() {
        const answerBody = document.getElementById('answer-body');
        const submitButton = document.getElementById('answer-submit');
        
        if (!answerBody || !answerBody.value.trim()) {
            alert(Joomla.Text._('COM_QUESTION_ANSWER_ERROR_BODY_REQUIRED'));
            return;
        }

        const url = 'index.php?option=com_question&task=ajax.submitAnswer&format=raw';
        const token = document.querySelector('input[name="' + Joomla.getOptions('csrf.token') + '"]').value;
        
        const formData = new FormData();
        formData.append('question_id', state.questionId);
        formData.append('body', answerBody.value);
        formData.append(token, '1');

        // Disable submit button
        submitButton.disabled = true;
        submitButton.textContent = Joomla.Text._('COM_QUESTION_SUBMITTING');

        fetch(url, {
            method: 'POST',
            body: formData,
            credentials: 'same-origin'
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                answerBody.value = '';
                if (typeof refreshAnswers === 'function') {
                    refreshAnswers();
                }
                alert(Joomla.Text._('COM_QUESTION_ANSWER_SUBMITTED_SUCCESSFULLY'));
            } else {
                alert(data.error || Joomla.Text._('COM_QUESTION_ANSWER_ERROR'));
            }
        })
        .catch(error => {
            console.error('Submit answer error:', error);
            alert(Joomla.Text._('COM_QUESTION_ANSWER_ERROR'));
        })
        .finally(() => {
            submitButton.disabled = false;
            submitButton.textContent = Joomla.Text._('COM_QUESTION_SUBMIT_ANSWER');
        });
    }

    /**
     * Initialize live updates
     */
    function initLiveUpdates() {
        if (!window.QuestionLiveUpdate) return;

        const enableLiveUpdates = document.body.dataset.liveUpdates !== 'false';
        if (!enableLiveUpdates) return;

        // Get last event timestamp from page
        const lastTimestamp = parseInt(document.body.dataset.lastTimestamp) || 0;

        state.liveUpdate = window.QuestionLiveUpdate.init({
            questionId: state.questionId,
            lastTimestamp: lastTimestamp,
            onEvent: handleLiveEvent,
            onError: handleLiveError,
            onStateChange: handleLiveStateChange
        });
    }

    /**
     * Handle live events
     * 
     * @param {Object} event The event object
     */
    function handleLiveEvent(event) {
        console.log('Live event:', event);
        
        // Handle different event types
        switch (event.event) {
            case 'new_answer':
                if (typeof refreshAnswers === 'function') {
                    refreshAnswers();
                }
                showNotification(Joomla.Text._('COM_QUESTION_NEW_ANSWER_POSTED'), 'info');
                break;
            case 'vote_update':
                // Vote updates are handled by the live update script
                break;
            case 'best_answer':
                showNotification(Joomla.Text._('COM_QUESTION_BEST_ANSWER_SELECTED'), 'success');
                break;
        }
    }

    /**
     * Handle live update errors
     * 
     * @param {Object} error The error object
     */
    function handleLiveError(error) {
        console.error('Live update error:', error);
    }

    /**
     * Handle live update state changes
     * 
     * @param {string} newState The new state
     * @param {string} oldState The old state
     */
    function handleLiveStateChange(newState, oldState) {
        console.log('Live update state changed from', oldState, 'to', newState);
        
        if (newState !== oldState && oldState) {
            // Show notification about connection downgrade
            let message = '';
            switch (newState) {
                case 'longpoll':
                    message = Joomla.Text._('COM_QUESTION_LIVE_UPDATES_DOWNGRADED_LONG_POLL');
                    break;
                case 'poll':
                    message = Joomla.Text._('COM_QUESTION_LIVE_UPDATES_DOWNGRADED_POLL');
                    break;
            }
            
            if (message) {
                showNotification(message, 'warning');
            }
        }
    }

    /**
     * Initialize ask question form
     */
    function initAskQuestionForm() {
        const askForm = document.getElementById('ask-question-form');
        if (!askForm) return;

        // Step 1: Language and category selection
        const step1 = document.getElementById('ask-step-1');
        const step2 = document.getElementById('ask-step-2');
        const nextButton = document.getElementById('ask-next');
        const backButton = document.getElementById('ask-back');

        if (nextButton) {
            nextButton.addEventListener('click', function() {
                if (validateStep1()) {
                    step1.style.display = 'none';
                    step2.style.display = 'block';
                }
            });
        }

        if (backButton) {
            backButton.addEventListener('click', function() {
                step1.style.display = 'block';
                step2.style.display = 'none';
            });
        }

        // Form submission
        askForm.addEventListener('submit', function(e) {
            e.preventDefault();
            submitQuestion();
        });

        // Load categories when language changes
        const languageSelect = document.getElementById('question-language');
        if (languageSelect) {
            languageSelect.addEventListener('change', loadCategories);
        }
    }

    /**
     * Validate step 1 of ask question form
     * 
     * @return {boolean} True if valid
     */
    function validateStep1() {
        const language = document.getElementById('question-language');
        const category = document.getElementById('question-category');

        if (!language.value) {
            alert(Joomla.Text._('COM_QUESTION_LANGUAGE_REQUIRED'));
            language.focus();
            return false;
        }

        if (!category.value) {
            alert(Joomla.Text._('COM_QUESTION_CATEGORY_REQUIRED'));
            category.focus();
            return false;
        }

        return true;
    }

    /**
     * Load categories for selected language
     */
    function loadCategories() {
        const language = document.getElementById('question-language').value;
        const categorySelect = document.getElementById('question-category');

        if (!language) return;

        const url = 'index.php?option=com_question&task=ajax.getCategories&format=raw&language=' + encodeURIComponent(language);

        fetch(url, {
            credentials: 'same-origin'
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                categorySelect.innerHTML = '<option value="">' + Joomla.Text._('COM_QUESTION_SELECT_CATEGORY') + '</option>';
                
                data.categories.forEach(function(category) {
                    const option = document.createElement('option');
                    option.value = category.id;
                    option.textContent = category.title;
                    categorySelect.appendChild(option);
                });
            }
        })
        .catch(error => {
            console.error('Load categories error:', error);
        });
    }

    /**
     * Submit a question
     */
    function submitQuestion() {
        const title = document.getElementById('question-title');
        const body = document.getElementById('question-body');
        const language = document.getElementById('question-language');
        const category = document.getElementById('question-category');
        const submitButton = document.getElementById('question-submit');

        if (!validateStep1()) return;

        if (!title.value.trim()) {
            alert(Joomla.Text._('COM_QUESTION_TITLE_REQUIRED'));
            title.focus();
            return;
        }

        if (!body.value.trim()) {
            alert(Joomla.Text._('COM_QUESTION_BODY_REQUIRED'));
            body.focus();
            return;
        }

        const url = 'index.php?option=com_question&task=ajax.submitQuestion&format=raw';
        const token = document.querySelector('input[name="' + Joomla.getOptions('csrf.token') + '"]').value;
        
        const formData = new FormData();
        formData.append('title', title.value);
        formData.append('body', body.value);
        formData.append('language', language.value);
        formData.append('catid', category.value);
        formData.append(token, '1');

        // Add tags if any
        const tagsInput = document.getElementById('question-tags');
        if (tagsInput && tagsInput.value) {
            formData.append('tags', JSON.stringify(tagsInput.value.split(',')));
        }

        submitButton.disabled = true;
        submitButton.textContent = Joomla.Text._('COM_QUESTION_SUBMITTING');

        fetch(url, {
            method: 'POST',
            body: formData,
            credentials: 'same-origin'
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                window.location.href = 'index.php?option=com_question&view=question&id=' + data.question_id;
            } else {
                alert(data.error || Joomla.Text._('COM_QUESTION_SUBMIT_ERROR'));
            }
        })
        .catch(error => {
            console.error('Submit question error:', error);
            alert(Joomla.Text._('COM_QUESTION_SUBMIT_ERROR'));
        })
        .finally(() => {
            submitButton.disabled = false;
            submitButton.textContent = Joomla.Text._('COM_QUESTION_SUBMIT_QUESTION');
        });
    }

    /**
     * Show notification
     * 
     * @param {string} message The message
     * @param {string} type The notification type (info, success, warning, error)
     */
    function showNotification(message, type) {
        // Simple notification implementation
        // In a real implementation, you might want to use a proper notification library
        const notification = document.createElement('div');
        notification.className = 'alert alert-' + type + ' alert-dismissible fade show';
        notification.innerHTML = `
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        `;
        
        const container = document.getElementById('notifications') || document.body;
        container.appendChild(notification);
        
        // Auto-dismiss after 5 seconds
        setTimeout(() => {
            if (notification.parentNode) {
                notification.parentNode.removeChild(notification);
            }
        }, 5000);
    }

    /**
     * Refresh answers list
     * This function is called by the live update system
     */
    window.refreshAnswers = function() {
        if (!state.questionId) return;

        const url = 'index.php?option=com_question&view=question&id=' + state.questionId + '&format=raw';
        
        fetch(url, {
            credentials: 'same-origin'
        })
        .then(response => response.text())
        .then(html => {
            const parser = new DOMParser();
            const doc = parser.parseFromString(html, 'text/html');
            const newAnswers = doc.getElementById('question-answers');
            const currentAnswers = document.getElementById('question-answers');
            
            if (newAnswers && currentAnswers) {
                currentAnswers.innerHTML = newAnswers.innerHTML;
                // Reinitialize voting for new answers
                initVoting();
            }
        })
        .catch(error => {
            console.error('Refresh answers error:', error);
        });
    };

    // Public API
    return {
        init: init,
        voteItem: voteItem,
        submitAnswer: submitAnswer,
        submitQuestion: submitQuestion,
        refreshAnswers: window.refreshAnswers
    };
})();

// Initialize when DOM is ready
document.addEventListener('DOMContentLoaded', function() {
    // Get component configuration from page
    const config = document.body.dataset;
    
    if (config.questionComponent) {
        window.QuestionComponent.init({
            questionId: parseInt(config.questionId) || null,
            canVote: config.canVote === 'true',
            canAnswer: config.canAnswer === 'true'
        });
    }
});
