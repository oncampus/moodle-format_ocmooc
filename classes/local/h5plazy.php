<?php
// This file is part of Moodle - https://moodle.org/
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
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

/**
 * Lazy loading for embedded H5P iframes in the editing view.
 *
 * In the editing view of large courses, many H5P activities are embedded via
 * <iframe src=".../mod/hvp/embed.php?id=N"> inside text-and-media fields, labels
 * and section summaries. Each iframe is its own browsing context and loads its
 * full JS stack, which makes the editing page extremely slow.
 *
 * This helper rewrites those iframes (and only those) so the browser does not
 * load them automatically. A small placeholder with a "load preview" button is
 * rendered instead (via the format_ocmooc/local/content/h5plazy template). The
 * actual loading is done on demand by the format_ocmooc/h5plazy AMD module.
 *
 * @package    format_ocmooc
 * @copyright  2025 oncampus GmbH <support@oncampus.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace format_ocmooc\local;

/**
 * Rewrites embedded H5P iframes into lazy placeholders.
 */
class h5plazy {
    /**
     * Process a rendered HTML string and lazy-load all embedded H5P iframes.
     *
     * Only call this in the editing view. Non-H5P iframes are left untouched.
     *
     * @param string $html The fully rendered HTML of the course content.
     * @param \renderer_base $output The renderer used to render the placeholder template.
     * @return string The processed HTML.
     */
    public static function process(string $html, \renderer_base $output): string {
        // Quick bail-out if there is nothing to do.
        if (strpos($html, '/mod/hvp/embed.php') === false) {
            return $html;
        }

        // Remove the per-embed H5P resizer scripts.
        $html = preg_replace(
            '#<script\b[^>]*\bsrc\s*=\s*(["\'])[^"\']*?/mod/hvp/library/js/h5p-resizer\.js[^"\']*\1[^>]*>\s*</script>#i',
            '',
            $html
        );

        // Replace every H5P embed iframe with a lazy placeholder.
        $pattern = '#<iframe\b[^>]*?\bsrc\s*=\s*(["\'])([^"\']*?/mod/hvp/embed\.php\?[^"\']*)\1[^>]*></iframe>#i';
        $html = preg_replace_callback($pattern, function ($matches) use ($output) {
            return self::build_placeholder($matches[0], $output);
        }, $html);

        return $html;
    }

    /**
     * Build the placeholder markup for a single H5P embed iframe.
     *
     * The original iframe is kept but its src is turned into data-src (so the
     * browser does not load it) and it is hidden until the user clicks "load".
     *
     * @param string $iframe The original iframe tag.
     * @param \renderer_base $output The renderer used to render the placeholder template.
     * @return string The placeholder HTML.
     */
    protected static function build_placeholder(string $iframe, \renderer_base $output): string {
        // Turn src into data-src so the iframe does not load.
        $lazy = preg_replace('/\bsrc\s*=/i', 'data-src=', $iframe, 1);

        // Add a marker class and hide the iframe until requested.
        if (preg_match('/\bclass\s*=\s*(["\'])/i', $lazy)) {
            // Iframe already has a class attribute: prepend our marker class.
            $lazy = preg_replace('/\bclass\s*=\s*(["\'])/i', 'class=$1h5p-lazy-frame ', $lazy, 1);
            $lazy = preg_replace('/^<iframe\b/i', '<iframe hidden', $lazy, 1);
        } else {
            $lazy = preg_replace('/^<iframe\b/i', '<iframe class="h5p-lazy-frame" hidden', $lazy, 1);
        }

        return $output->render_from_template('format_ocmooc/local/content/h5plazy', [
            'notice' => get_string('h5plazy_notice', 'format_ocmooc'),
            'button' => get_string('h5plazy_load', 'format_ocmooc'),
            'iframe' => $lazy,
        ]);
    }
}
