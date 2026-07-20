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
define(['jquery', 'format_ocmooc/hvp_resizer_child'], function ($) {
    /**
     * Sends a post message to the parent window
     * @param {*} contentHeight Set the H5P-Element Height
     */
    function sendPostMessage(contentHeight) {
        let queryString = window.location.search;
        let urlParams = new URLSearchParams(queryString);
        let id = urlParams.get('id');
        window.parent.postMessage({
            frameHeight: contentHeight,
            frameId: 'h5p-iframe-' + id
        });
    }

    return {
        handleFrame: function () {
            const iframe = document.querySelector('.h5p-iframe');

            if (iframe) {
                let lastHeight = 0;
                const report = function () {
                    const height = iframe.clientHeight;
                    if (height && Math.abs(height - lastHeight) > 1) {
                        lastHeight = height;
                        sendPostMessage(height);
                    }
                };

                if (window.ResizeObserver) {
                    const observer = new ResizeObserver(function () {
                        report();
                    });
                    observer.observe(iframe);
                } else {
                    setInterval(report, 500);
                }
                window.addEventListener('message', function (event) {
                    const data = event.data || {};
                    if (data.context === 'h5p' || data.action === 'resize') {
                        window.requestAnimationFrame(report);
                    }
                });

                iframe.addEventListener('load', report);
                report();
            } else {
                const h5pContent = document.querySelector('.h5p-content');
                if (h5pContent && h5pContent.parentElement) {
                    const parent = h5pContent.parentElement;
                    if (window.ResizeObserver) {
                        const observer = new ResizeObserver(function () {
                            sendPostMessage(parent.offsetHeight);
                        });
                        observer.observe(h5pContent);
                    }
                    sendPostMessage(parent.offsetHeight);
                }
            }
        },
    };
});