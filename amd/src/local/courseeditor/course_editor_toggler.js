/**
 * Function called to toggle the collapse <span> Elements (Collapse All / Expand All)
 * Example call in: templates/local/content/section/content.mustache
 */
function toggleCollapse(id) {
    let collapseSection = document.getElementById(id);
    //Check if collapse Section Button is expanded
    let isExpanded = collapseSection.getAttribute('aria-expanded') === 'true';
    //The collapse all span Element
    let collapseSpan = document.querySelector('.collapseall');
    //The Expand All Span Element
    let expandSpan = document.querySelector('.expandall');
    //The Content Body Element which gets collapsed
    let contentElement = document.querySelector('.content.course-content-item-content.collapse');

    //Toggle and set the isExpanded Status
    isExpanded = !isExpanded;
    collapseSection.setAttribute('aria-expanded', isExpanded);

    //If new CollapseSection is expanded show the new span and body
    if (isExpanded) {
        collapseSpan.style.display = 'inline';
        expandSpan.style.display = 'none';
        contentElement.classList.add('show');
    } else {
        collapseSpan.style.display = 'none';
        expandSpan.style.display = 'inline';
        contentElement.classList.remove('show');
    }
}
