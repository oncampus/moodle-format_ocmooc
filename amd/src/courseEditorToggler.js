/**
 * Show or expand the course content sections depending on the edit mode.
 *
 * @module     format_ocmooc/courseEditorToggler
 * @copyright  2025 oncampus GmbH <support@oncampus.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

const apply = () => {
    const editSwitch = document.querySelector('[id$="editingswitch"]');
    if (editSwitch && editSwitch.checked) {
        document.querySelectorAll('.icons-collapse-expand').forEach((icon) => {
            icon.style.display = 'flex';
        });
    } else {
        document.querySelectorAll('[id^="coursecontentcollapse"]').forEach((element) => {
            element.classList.add('show');
        });
    }
};

/**
 * Initialise the toggler.
 */
export const init = () => {
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', apply);
    } else {
        apply();
    }
};