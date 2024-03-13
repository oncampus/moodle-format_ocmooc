import jQuery from 'jquery';

export const init = () => {
    jQuery(document).ready(function () {
        let jumpmark = jQuery('#oc-jump-lection');
        if (jumpmark !== undefined) {
            let tag = jQuery("#section-" + jumpmark.val());
            jQuery('#page').animate({scrollTop: tag.offset().top}, 'fast');
        }
    });
};