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
define(['jquery', 'format_ocmooc/hvp_resizer_child'], function($) {
        /**
         * Sends a post message to the parent window
         */
        function sendPostMessage() {
            let queryString = window.location.search;
            let urlParams = new URLSearchParams(queryString);
            let id = urlParams.get('id');
            let iFrame = document.querySelector('.h5p-iframe');
            window.parent.postMessage({
                frameHeight: iFrame.clientHeight,
                frameId: 'h5p-iframe-' + id
            });
        }

        return {
            handleFrame: function() {
                // Wait for document
                // Options for the observer (which mutations to observe)
                const config = {attributes: true, childList: false, subtree: false};
                // Callback function to execute when mutations are observed
                const callback = (mutationList) => {
                    for (const mutation of mutationList) {
                        if (mutation.type === "attributes") {
                            sendPostMessage();
                        }
                    }
                };
                const observer = new MutationObserver(callback);
                // Wait for iframe
                $('.h5p-iframe').ready(function() {
                    let iFrame = document.querySelector('.h5p-iframe');
                    if (iFrame) {
                        if (iFrame.classList.contains('h5p-initialized')) {
                            sendPostMessage();
                        }
                        observer.observe(iFrame, config);
                    }
                });
            },
        };
    }
);