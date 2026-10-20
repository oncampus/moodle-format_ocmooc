/**
 * Lock the edit mode switch until the page is completely loaded.
 *
 * @module     format_ocmooc/lockEditbutton
 * @copyright  2025 oncampus GmbH <support@oncampus.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Initialise the lock.
 */
export const init = () => {
    const editSwitch = document.querySelector('[id$="-editingswitch"]');
    if (!editSwitch || document.readyState === 'complete') {
        return;
    }
    editSwitch.disabled = true;
    window.addEventListener('load', () => {
        editSwitch.disabled = false;
    });
};