/**
 * Receive progress and height updates from embedded H5P content.
 *
 * @module     format_ocmooc/hvp_resizer_parent
 * @copyright  2025 oncampus GmbH <support@oncampus.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Initialise the message listener.
 */
export const init = () => {
    window.addEventListener('message', (e) => {
        const data = e.data;
        if (!data || typeof data !== 'object') {
            return;
        }

        if ('progressDiv' in data && 'textDiv' in data && 'percentage' in data) {
            // Update progress percentage.
            if (data.progressDiv !== null && data.textDiv !== null && data.percentage !== '0%') {
                const bar = document.getElementById(data.progressDiv);
                const text = document.getElementById(data.textDiv);
                if (bar && text) {
                    bar.style.width = data.percentage;
                    text.textContent = data.percentage;
                }
            }
        } else if ('frameHeight' in data && 'frameId' in data) {
            // Adjust the iframe height.
            const frame = document.getElementById(data.frameId);
            if (frame) {
                frame.style.height = `${data.frameHeight}px`;
            }
        }
    });
};