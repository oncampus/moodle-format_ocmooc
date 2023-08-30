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
define('format_ocmooc/hvp_resizer', [], function () {
        return {
            handleFrame: function() {
                // Iframe
                let height;

                const sendPostMessage = () => {
                    // To determine our id
                    let queryString = window.location.search;
                    let urlParams = new URLSearchParams(queryString);
                    let id = urlParams.get('id');
                    // If height has changed (new content or screen resize)
                    if (document.getElementsByClassName('h5p-iframe-wrapper').length > 0 &&
                        height !== document.getElementsByClassName('h5p-iframe-wrapper')[0].offsetHeight) {
                        // Determine the height
                        height = document.getElementsByClassName('h5p-iframe-wrapper')[0].offsetHeight;
                        // And send it to the parent
                        window.parent.postMessage({
                            frameHeight: height,
                            frameId: 'h5p-iframe-' + id
                        }, '*');
                    }
                };
                window.onload = () => sendPostMessage();
                window.onresize = () => sendPostMessage();
            },

            handleParent: function() {
                // Website
                window.onmessage = (e) => {
                    // Make sure its the message we need
                    if (e.data.hasOwnProperty("frameHeight") && e.data.hasOwnProperty("frameId")) {
                        // To avoid retroactively adding/using ids on iframes
                        let frame = document.getElementById(e.data.frameId);
                        // Adjust the height
                        frame.style.height = `${e.data.frameHeight}px`;
                    }
                };
            },
        };
    }
);