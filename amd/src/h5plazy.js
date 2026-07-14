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
 * On-demand loading of embedded H5P iframes in the OCMOOC editing view.
 *
 * The PHP helper (format_ocmooc\local\h5plazy) renders each embedded H5P iframe
 * with its src moved to data-src and hidden behind a "load preview" button. This
 * module swaps data-src back to src when the button is clicked and loads the H5P
 * resizer once so the iframe is sized correctly.
 *
 * @module     format_ocmooc/h5plazy
 * @copyright  2025 oncampus GmbH <support@oncampus.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

const SELECTORS = {
    LOAD_ACTION: '[data-action="h5plazy-load"]',
    WRAPPER: '.h5p-lazy',
    FRAME: 'iframe.h5p-lazy-frame',
    PROMPT: '.h5p-lazy-prompt',
};

/** @type {boolean} Whether the click listener has already been attached. */
let initialised = false;

/** @type {boolean} Whether the H5P resizer script has already been loaded. */
let resizerLoaded = false;

/**
 * Load the H5P resizer script exactly once.
 *
 * The resizer listens for postMessage events from H5P iframes and resizes them,
 * so it works regardless of when the iframe is actually loaded.
 */
const ensureResizer = () => {
    if (resizerLoaded) {
        return;
    }
    resizerLoaded = true;
    const script = document.createElement('script');
    script.src = `${M.cfg.wwwroot}/mod/hvp/library/js/h5p-resizer.js`;
    script.charset = 'UTF-8';
    document.body.appendChild(script);
};

/**
 * Activate the embedded H5P iframe inside the given wrapper.
 *
 * @param {Element} wrapper The .h5p-lazy container element.
 */
const loadEmbed = (wrapper) => {
    const frame = wrapper.querySelector(SELECTORS.FRAME);
    if (!frame) {
        return;
    }
    if (frame.dataset.src) {
        frame.src = frame.dataset.src;
        frame.removeAttribute('data-src');
    }
    frame.hidden = false;
    const prompt = wrapper.querySelector(SELECTORS.PROMPT);
    if (prompt) {
        prompt.remove();
    }
    ensureResizer();
};

/**
 * Handle clicks on the "load preview" buttons.
 *
 * @param {Event} event The click event.
 */
const handleClick = (event) => {
    const button = event.target.closest(SELECTORS.LOAD_ACTION);
    if (!button) {
        return;
    }
    event.preventDefault();
    const wrapper = button.closest(SELECTORS.WRAPPER);
    if (wrapper) {
        loadEmbed(wrapper);
    }
};

/**
 * Initialise the lazy H5P loader.
 *
 * A single delegated listener is attached to the document so it keeps working
 * for sections that are re-rendered by the reactive course editor.
 */
export const init = () => {
    if (initialised) {
        return;
    }
    initialised = true;
    document.addEventListener('click', handleClick);
};