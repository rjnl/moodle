// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Debounces recording the time a user viewed a book chapter.
 *
 * The view time is recorded only after the user remains on the chapter for the debounce period.
 *
 * @module     mod_book/chapterview
 * @copyright  2026 Anupama Sarjoshi <anupama.sarjoshi@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import Ajax from 'core/ajax';
import Notification from 'core/notification';
import Templates from 'core/templates';
import {debounce} from 'core/utils';

// Core template used to render automatic completion conditions.
const COMPLETION_CONDITION_TEMPLATE = 'core_course/completion_automatic';

/**
 * Selector for the automatic completion conditions list rendered on the page.
 *
 * @type {string}
 */
const COMPLETION_LIST_SELECTOR = '[data-region="completionrequirements"] .activity-completion-list';

/**
 * Update the server-side timeviewed value for a chapter.
 *
 * @param {Number} bookid The book instance id.
 * @param {Number} chapterid The chapter id.
 * @return {Promise}
 */
const updateChapterViewTime = (bookid, chapterid) => Ajax.call([{
    methodname: 'mod_book_update_chapter_view_time',
    args: {bookid, chapterid},
}])[0];

/**
 * Refresh the automatic completion conditions list so the page reflects the completion state
 * recalculated by the server after recording the chapter view, without a full page reload.
 *
 * @param {object} completion The completion data returned by mod_book_update_chapter_view_time.
 * @return {Promise}
 */
const refreshCompletionConditions = async(completion) => {
    const list = document.querySelector(COMPLETION_LIST_SELECTOR);
    if (list) {
        const rendered = await Promise.all(completion.completiondetails.map(
            (detail) => Templates.renderForPromise(COMPLETION_CONDITION_TEMPLATE, {
                ...detail,
                istrackeduser: completion.istrackeduser,
            })
        ));
        await Templates.replaceNodeContents(
            list,
            rendered.map(({html}) => html).join(''),
            rendered.map(({js}) => js).join(';')
        );
    }
};

/**
 * Record the chapter view time and refresh the completion conditions.
 *
 * @param {Number} bookid The book instance ID.
 * @param {Number} chapterid The chapter ID.
 * @return {Promise}
 */
const updateChapterViewTimeAndRefreshCompletion = async(bookid, chapterid) => {
    const response = await updateChapterViewTime(bookid, chapterid);

    if (response.completion) {
        await refreshCompletionConditions(response.completion);
    }
};

/**
 * Schedule recording the chapter view time after the debounce period.
 *
 * @param {Number} bookid The book instance ID.
 * @param {Number} chapterid The chapter ID.
 * @param {Number} debouncems The debounce period in milliseconds.
 */
export const init = (bookid, chapterid, debouncems) => {
    const scheduleUpdate = debounce(
        () => {
            return updateChapterViewTimeAndRefreshCompletion(bookid, chapterid)
                .catch(Notification.exception);
        },
        debouncems,
        {pending: true},
    );
    scheduleUpdate();
};

export default {init};
