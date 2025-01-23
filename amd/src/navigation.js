define(['jquery'], function($) {
    return {
        init: function() {
            var maxVisible = window.innerWidth < 500 ? 3 :
                 window.innerWidth < 600 ? 4 : 5;

            // Initialize: Hide non-visible lessons on page load
            $(document).ready(function() {
                var navItems = $('.ocmooc-nav-item').not('.ocmooc-nav-item-prev, .ocmooc-nav-item-next');
                navItems.hide();

                var activeItem = navItems.filter('.active');
                var startIndex = 0;
                if (activeItem.length > 0) {
                    startIndex = navItems.index(activeItem) - Math.floor(maxVisible / 2);
                }
                startIndex = Math.max(0, Math.min(startIndex, navItems.length - maxVisible));
                updateLessonNav(startIndex);
            });

            /**
             * Change the visible lessons based on navigation direction.
             * @param {number} direction Navigation direction (1: forward, -1: backward)
             */
            function changeLessonNav(direction) {
                var navItems = $('.ocmooc-nav-item').not('.ocmooc-nav-item-prev, .ocmooc-nav-item-next');
                var totalItems = navItems.length;
                var visibleItems = navItems.filter(function() {
                    return $(this).is(':visible');
                });

                var firstIndex = navItems.index(visibleItems.first());
                var newStartIndex = firstIndex + (direction * maxVisible);
                newStartIndex = Math.max(0, Math.min(newStartIndex, totalItems - maxVisible));
                updateLessonNav(newStartIndex);
            }

            /**
             * Update the visible lesson navigation items.
             * @param {number} startIndex Start index for visible lessons.
             */
            function updateLessonNav(startIndex) {
                var navItems = $('.ocmooc-nav-item').not('.ocmooc-nav-item-prev, .ocmooc-nav-item-next');
                var totalItems = navItems.length;

                navItems.each(function(index) {
                    $(this).toggle(index >= startIndex && index < startIndex + maxVisible);
                });

                var isFirstPage = startIndex === 0;
                var isLastPage = startIndex + maxVisible >= totalItems;
                $('.ocmooc-nav-item-prev').css('visibility', isFirstPage ? 'hidden' : 'visible');
                $('.ocmooc-nav-item-next').css('visibility', isLastPage ? 'hidden' : 'visible');
            }

            // Attach event listeners
            $(document).on('click', '.ocmooc-nav-item-prev a', function(event) {
                event.preventDefault();
                changeLessonNav(-1);
            });

            $(document).on('click', '.ocmooc-nav-item-next a', function(event) {
                event.preventDefault();
                changeLessonNav(1);
            });
        }
    };
});