/**
 * @package     Joomla.Site
 * @subpackage  com_question
 *
 * @copyright   Copyright (C) 2026
 * @license     GNU General Public License version 2 or later
 */

/**
 * Question Component Live Update Hybrid Controller
 * 
 * This controller handles live updates using a hybrid approach:
 * 1. Server-Sent Events (SSE) - Primary method
 * 2. AJAX Long Polling - Fallback 1
 * 3. AJAX Timed Polling - Fallback 2
 * 
 * @since  1.0.0
 */
window.QuestionLiveUpdate = (function() {
    'use strict';

    // Configuration
    const CONFIG = {
        sseUrl: 'index.php?option=com_question&task=sse.stream&format=raw',
        pollUrl: 'index.php?option=com_question&task=ajax.poll&format=raw',
        heartbeatInterval: 20000, // 20 seconds
        longPollTimeout: 25000, // 25 seconds
        pollInterval: 25000, // 25 seconds
        maxRetries: 3,
        retryDelay: 5000 // 5 seconds
    };

    // State machine states
    const STATES = {
        SSE: 'sse',
        LONG_POLL: 'longpoll',
        POLL: 'poll'
    };

    // Controller state
    let state = {
        currentState: STATES.SSE,
        questionId: null,
        lastTimestamp: 0,
        eventSource: null,
        pollTimer: null,
        reconnectTimer: null,
        retryCount: 0,
        isActive: false,
        callbacks: {
            onEvent: null,
            onError: null,
            onStateChange: null
        }
    };

    /**
     * Initialize the live update system
     * 
     * @param {Object} options Configuration options
     * @param {number} options.questionId The question ID to monitor
     * @param {number} options.lastTimestamp Last event timestamp
     * @param {Function} options.onEvent Callback for new events
     * @param {Function} options.onError Callback for errors
     * @param {Function} options.onStateChange Callback for state changes
     */
    function init(options) {
        if (!options || !options.questionId) {
            console.error('QuestionLiveUpdate: questionId is required');
            return false;
        }

        state.questionId = options.questionId;
        state.lastTimestamp = options.lastTimestamp || 0;
        state.callbacks = {
            onEvent: options.onEvent || null,
            onError: options.onError || null,
            onStateChange: options.onStateChange || null
        };

        // Start with SSE
        startSSE();
        state.isActive = true;

        return true;
    }

    /**
     * Start Server-Sent Events
     */
    function startSSE() {
        if (!window.EventSource) {
            console.log('QuestionLiveUpdate: EventSource not supported, switching to long polling');
            switchToLongPoll();
            return;
        }

        changeState(STATES.SSE);

        try {
            const url = CONFIG.sseUrl + 
                '&question_id=' + state.questionId + 
                '&last_event_timestamp=' + state.lastTimestamp;

            state.eventSource = new EventSource(url);

            state.eventSource.onopen = function() {
                console.log('QuestionLiveUpdate: SSE connection opened');
                state.retryCount = 0;
                showLiveIndicator('sse');
            };

            state.eventSource.onmessage = function(event) {
                try {
                    const data = JSON.parse(event.data);
                    handleEvent(data);
                } catch (e) {
                    console.error('QuestionLiveUpdate: Failed to parse SSE message', e);
                }
            };

            state.eventSource.addEventListener('new_answer', function(event) {
                try {
                    const data = JSON.parse(event.data);
                    handleEvent(data);
                } catch (e) {
                    console.error('QuestionLiveUpdate: Failed to parse new_answer event', e);
                }
            });

            state.eventSource.addEventListener('vote_update', function(event) {
                try {
                    const data = JSON.parse(event.data);
                    handleEvent(data);
                } catch (e) {
                    console.error('QuestionLiveUpdate: Failed to parse vote_update event', e);
                }
            });

            state.eventSource.addEventListener('question_update', function(event) {
                try {
                    const data = JSON.parse(event.data);
                    handleEvent(data);
                } catch (e) {
                    console.error('QuestionLiveUpdate: Failed to parse question_update event', e);
                }
            });

            state.eventSource.addEventListener('moderation_update', function(event) {
                try {
                    const data = JSON.parse(event.data);
                    handleEvent(data);
                } catch (e) {
                    console.error('QuestionLiveUpdate: Failed to parse moderation_update event', e);
                }
            });

            state.eventSource.addEventListener('best_answer', function(event) {
                try {
                    const data = JSON.parse(event.data);
                    handleEvent(data);
                } catch (e) {
                    console.error('QuestionLiveUpdate: Failed to parse best_answer event', e);
                }
            });

            state.eventSource.addEventListener('heartbeat', function(event) {
                // Heartbeat received, connection is alive
                showLiveIndicator('sse');
            });

            state.eventSource.onerror = function(event) {
                console.error('QuestionLiveUpdate: SSE error', event);
                state.retryCount++;

                if (state.retryCount >= CONFIG.maxRetries) {
                    console.log('QuestionLiveUpdate: Max retries reached, switching to long polling');
                    switchToLongPoll();
                } else {
                    console.log('QuestionLiveUpdate: SSE error, retrying in ' + CONFIG.retryDelay + 'ms');
                    setTimeout(startSSE, CONFIG.retryDelay);
                }
            };

        } catch (e) {
            console.error('QuestionLiveUpdate: Failed to create EventSource', e);
            switchToLongPoll();
        }
    }

    /**
     * Switch to AJAX Long Polling
     */
    function switchToLongPoll() {
        changeState(STATES.LONG_POLL);
        showLiveIndicator('longpoll');
        startLongPoll();
    }

    /**
     * Start AJAX Long Polling
     */
    function startLongPoll() {
        if (!state.isActive) return;

        const url = CONFIG.pollUrl + 
            '&question_id=' + state.questionId + 
            '&last_event_timestamp=' + state.lastTimestamp + 
            '&longpoll=1';

        const xhr = new XMLHttpRequest();
        xhr.open('GET', url, true);
        xhr.timeout = CONFIG.longPollTimeout;

        xhr.onload = function() {
            if (xhr.status === 200) {
                try {
                    const response = JSON.parse(xhr.responseText);
                    if (response.success && response.events && response.events.length > 0) {
                        response.events.forEach(handleEvent);
                    }
                    state.retryCount = 0;
                    setTimeout(startLongPoll, 1000); // Brief pause before next poll
                } catch (e) {
                    console.error('QuestionLiveUpdate: Failed to parse long poll response', e);
                    handleLongPollError();
                }
            } else {
                handleLongPollError();
            }
        };

        xhr.onerror = function() {
            handleLongPollError();
        };

        xhr.ontimeout = function() {
            // Timeout is expected in long polling, just restart
            setTimeout(startLongPoll, 1000);
        };

        try {
            xhr.send();
        } catch (e) {
            console.error('QuestionLiveUpdate: Long poll request failed', e);
            handleLongPollError();
        }
    }

    /**
     * Handle long polling errors
     */
    function handleLongPollError() {
        state.retryCount++;
        console.log('QuestionLiveUpdate: Long poll error, retry count: ' + state.retryCount);

        if (state.retryCount >= CONFIG.maxRetries) {
            console.log('QuestionLiveUpdate: Max retries reached, switching to timed polling');
            switchToPoll();
        } else {
            setTimeout(startLongPoll, CONFIG.retryDelay);
        }
    }

    /**
     * Switch to AJAX Timed Polling
     */
    function switchToPoll() {
        changeState(STATES.POLL);
        showLiveIndicator('poll');
        startPoll();
    }

    /**
     * Start AJAX Timed Polling
     */
    function startPoll() {
        if (!state.isActive) return;

        const url = CONFIG.pollUrl + 
            '&question_id=' + state.questionId + 
            '&last_event_timestamp=' + state.lastTimestamp + 
            '&longpoll=0';

        const xhr = new XMLHttpRequest();
        xhr.open('GET', url, true);
        xhr.timeout = 10000; // 10 second timeout for regular polling

        xhr.onload = function() {
            if (xhr.status === 200) {
                try {
                    const response = JSON.parse(xhr.responseText);
                    if (response.success && response.events && response.events.length > 0) {
                        response.events.forEach(handleEvent);
                    }
                } catch (e) {
                    console.error('QuestionLiveUpdate: Failed to parse poll response', e);
                }
            }
        };

        xhr.onerror = function() {
            console.error('QuestionLiveUpdate: Poll error');
        };

        xhr.ontimeout = function() {
            console.error('QuestionLiveUpdate: Poll timeout');
        };

        try {
            xhr.send();
        } catch (e) {
            console.error('QuestionLiveUpdate: Poll request failed', e);
        }

        // Schedule next poll
        state.pollTimer = setTimeout(startPoll, CONFIG.pollInterval);
    }

    /**
     * Handle incoming events
     * 
     * @param {Object} event The event object
     */
    function handleEvent(event) {
        if (!event || !event.event) return;

        // Update last timestamp
        if (event.timestamp > state.lastTimestamp) {
            state.lastTimestamp = event.timestamp;
        }

        // Call the event callback
        if (state.callbacks.onEvent) {
            state.callbacks.onEvent(event);
        }

        // Handle specific event types
        switch (event.event) {
            case 'new_answer':
                handleNewAnswer(event);
                break;
            case 'vote_update':
                handleVoteUpdate(event);
                break;
            case 'question_update':
                handleQuestionUpdate(event);
                break;
            case 'moderation_update':
                handleModerationUpdate(event);
                break;
            case 'best_answer':
                handleBestAnswer(event);
                break;
        }
    }

    /**
     * Handle new answer events
     * 
     * @param {Object} event The event object
     */
    function handleNewAnswer(event) {
        // Add new answer to the list
        const answersContainer = document.getElementById('question-answers');
        if (answersContainer && event.data.answer_id) {
            // Refresh the answers list or append the new answer
            if (typeof refreshAnswers === 'function') {
                refreshAnswers();
            }
        }
    }

    /**
     * Handle vote update events
     * 
     * @param {Object} event The event object
     */
    function handleVoteUpdate(event) {
        // Update vote counts
        if (event.data.item_id && event.data.new_votes_up !== undefined) {
            const voteUpElement = document.getElementById('vote-up-' + event.data.item_id);
            const voteDownElement = document.getElementById('vote-down-' + event.data.item_id);

            if (voteUpElement) {
                voteUpElement.textContent = event.data.new_votes_up;
            }
            if (voteDownElement) {
                voteDownElement.textContent = event.data.new_votes_down;
            }
        }
    }

    /**
     * Handle question update events
     * 
     * @param {Object} event The event object
     */
    function handleQuestionUpdate(event) {
        // Update question details if needed
        console.log('Question updated:', event);
    }

    /**
     * Handle moderation update events
     * 
     * @param {Object} event The event object
     */
    function handleModerationUpdate(event) {
        // Handle moderation actions
        console.log('Moderation update:', event);
    }

    /**
     * Handle best answer events
     * 
     * @param {Object} event The event object
     */
    function handleBestAnswer(event) {
        // Mark answer as best
        if (event.data.answer_id) {
            const bestAnswerElement = document.getElementById('answer-' + event.data.answer_id);
            if (bestAnswerElement) {
                bestAnswerElement.classList.add('best-answer');
            }
        }
    }

    /**
     * Change the current state
     * 
     * @param {string} newState The new state
     */
    function changeState(newState) {
        const oldState = state.currentState;
        state.currentState = newState;

        // Clean up previous state
        if (oldState === STATES.SSE && state.eventSource) {
            state.eventSource.close();
            state.eventSource = null;
        } else if (oldState === STATES.POLL && state.pollTimer) {
            clearTimeout(state.pollTimer);
            state.pollTimer = null;
        }

        // Call state change callback
        if (state.callbacks.onStateChange) {
            state.callbacks.onStateChange(newState, oldState);
        }
    }

    /**
     * Show live update indicator
     * 
     * @param {string} type The connection type
     */
    function showLiveIndicator(type) {
        const indicator = document.getElementById('live-update-indicator');
        if (!indicator) return;

        indicator.className = 'live-update-indicator live-update-' + type;
        
        let text = '';
        switch (type) {
            case 'sse':
                text = Joomla.Text._('COM_QUESTION_LIVE_UPDATES_SSE');
                break;
            case 'longpoll':
                text = Joomla.Text._('COM_QUESTION_LIVE_UPDATES_LONG_POLL');
                break;
            case 'poll':
                text = Joomla.Text._('COM_QUESTION_LIVE_UPDATES_POLL');
                break;
        }
        
        indicator.textContent = text;
        indicator.style.display = 'inline-block';
    }

    /**
     * Stop the live update system
     */
    function stop() {
        state.isActive = false;
        changeState(null);
        
        const indicator = document.getElementById('live-update-indicator');
        if (indicator) {
            indicator.style.display = 'none';
        }
    }

    /**
     * Get current state
     * 
     * @return {string} Current state
     */
    function getState() {
        return state.currentState;
    }

    /**
     * Get last timestamp
     * 
     * @return {number} Last timestamp
     */
    function getLastTimestamp() {
        return state.lastTimestamp;
    }

    // Public API
    return {
        init: init,
        stop: stop,
        getState: getState,
        getLastTimestamp: getLastTimestamp,
        STATES: STATES
    };
})();
