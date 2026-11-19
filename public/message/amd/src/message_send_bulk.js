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
 * Send bulk message to the given user ids.
 *
 * @module     core_message/message_send_bulk
 * @copyright  2019 Shamim Rezaie <shamim@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
import {get_string, getString} from 'core/str';
import ModalSaveCancel from 'core/modal_save_cancel';
import Templates from 'core/templates';
import ModalEvents from 'core/modal_events';
import Ajax from 'core/ajax';
import Notification from 'core/notification';

/**
 * Show the send message popup.
 *
 * @method showModal
 * @param {int[]} users
 * @param {Function} callback A callback to apply after the form is closed.
 * @returns {Promise}
 */
export const showModal = (users, callback = null) => {
    if (!users.length) {
        // Nothing to do.
        return Promise.resolve();
    }
    let titlePromise = null;
    if (users.length == 1) {
        titlePromise = get_string('sendbulkmessagesingle', 'core_message');
    } else {
        titlePromise = get_string('sendbulkmessage', 'core_message', users.length);
    }

    return ModalSaveCancel.create({
        body: Templates.render('core_message/send_bulk_message', {}),
        title: titlePromise,
        show: true,
        buttons: {
            save: titlePromise,
        },
    })
    .then(function(modal) {
        // When the dialog is closed, perform the callback (if provided).
        modal.getRoot().on(ModalEvents.hidden, function() {
            if (callback) {
                callback();
            }
            modal.getRoot().remove();
        });

        modal.getRoot().on(ModalEvents.save, function() {
            let messageText = modal.getRoot().find('form textarea').val();
            sendMessage(messageText, users);
        });

        return modal;
    });
};

/**
 * Send a message to these users.
 *
 * @method sendMessage
 * @param {String} messageText
 * @param {Number[]} users
 * @returns {Promise<boolean|undefined>} Resolves true if every message was sent, false if at least one failed
 *     (the failures are shown to the user), or undefined if the request itself failed.
 */
export const sendMessage = (messageText, users) => {
    let messages = [];

    users.forEach(user => {
        messages.push({
            touserid: user,
            text: messageText
        });
    });

    return Ajax.call([{
        methodname: 'core_message_send_instant_messages',
        args: {messages: messages}
    }])[0]
    .then(async(messageIds) => {
        // The web service reports per-message failures in the response (msgid = -1) rather than throwing.
        const failures = messageIds.filter(msg => msg.msgid < 1);
        await notifyFailures(failures);

        const sent = messageIds.length - failures.length;
        if (failures.length === 0) {
            const message = sent === 1 ?
                await getString('sendbulkmessagesentsingle', 'core_message') :
                await getString('sendbulkmessagesent', 'core_message', sent);
            Notification.addNotification({message, type: 'success'});
        } else if (sent > 0) {
            const message = await getString(
                'sendbulkmessagesentwitherrors',
                'core_message',
                {sent, total: messageIds.length}
            );
            Notification.addNotification({message, type: 'warning'});
        }

        return failures.length === 0;
    })
    .catch(Notification.exception);
};

/**
 * Show an error notification for the messages which could not be sent.
 *
 * Users who cannot be messaged are listed together, and any other error is only shown once.
 *
 * @param {Object[]} failures The failed items (msgid = -1) from the core_message_send_instant_messages response.
 * @returns {Promise}
 */
export const notifyFailures = async(failures) => {
    const cantSendToUsers = failures.filter(msg => msg.cantsendtouser);
    const messages = [];

    if (cantSendToUsers.length > 1) {
        const names = cantSendToUsers.map(msg => msg.cantsendtouser).join(', ');
        messages.push(await getString('usercantbemessagedbulk', 'core_message', names));
    } else if (cantSendToUsers.length === 1) {
        // The singular 'usercantbemessaged' string is already built in PHP.
        messages.push(cantSendToUsers[0].errormessage);
    }

    // Other errors (e.g. message too long) are likely the same for every recipient, so only show each once.
    const otherErrors = failures.filter(msg => !msg.cantsendtouser && msg.errormessage).map(msg => msg.errormessage);
    messages.push(...new Set(otherErrors));

    messages.forEach(message => Notification.addNotification({message, type: 'error'}));
};
