import jQuery from 'jquery';

export const init = () => {
    // A hash in the URL is handled by the browser.
    if (window.location.hash) {
        return;
    }
    const jumpmark = document.getElementById('oc-jump-lection');
    const target = jumpmark ? document.getElementById('section-' + jumpmark.value) : null;
    if (target) {
        target.scrollIntoView({behavior: 'smooth', block: 'center'});
    }
};