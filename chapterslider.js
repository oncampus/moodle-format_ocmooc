var container = document.getElementById('oc-chapters-navigation');
var slider = document.getElementById('oc-chapters-slider');
var slides = document.getElementsByClassName('oc-chapter-item').length;
var buttons = document.getElementsByClassName('chapters-nav-btn');

var currentPosition = 0;
var currentMargin = 0;
var slidesPerPage = 3;
var slidesCount = 3;
var containerWidth = container.offsetWidth;
var prevKeyActive = false;
var nextKeyActive = true;
var startPosition = 0;

/* window.addEventListener("resize", checkWidth); */

function getAlleItems() {
    return document.getElementById('oc-chapters-slider').getElementsByClassName('oc-chapter-item');
}

function getCurrentActiveIndex() {
    let items = getAlleItems();
    for (let i = 0; i < items.length; i++) {
        if (items[i].classList.contains('active')) {
            return i;
        }
    }
    return -1;
}

startPosition = getCurrentActiveIndex();
currentPosition = getCurrentActiveIndex();

function setOpacity(index) {
    let items = getAlleItems();
    if (items[index]) {
        items[index].firstElementChild.firstElementChild.setAttribute('style', 'opacity: 1 !important');
    }
}

function resetOpacity(index) {
    let items = getAlleItems();

    if (items[index]) {
        items[index].firstElementChild.firstElementChild.setAttribute('style', 'opacity: 0.6 !important');
    }
}

function slideRight() {
    if (currentPosition != 0) {
        slider.style.marginLeft = currentMargin + 284 + 'px';
        currentMargin += 284;
        resetOpacity(currentPosition);
        currentPosition--;
        setOpacity(currentPosition);
    }
    if (currentPosition === 0) {
        buttons[0].classList.add('inactive');
    }
    if (currentPosition < slidesCount) {
        buttons[1].classList.remove('inactive');
    }
}

function slideLeft() {
    if (currentPosition < slides) {
        slider.style.marginLeft = currentMargin - 284 + 'px';
        currentMargin -= 284;
        resetOpacity(currentPosition);
        currentPosition++;
        setOpacity(currentPosition);
    }
    if (currentPosition == slides) {
        buttons[1].classList.add('inactive');
    }
    if (currentPosition > 0) {
        buttons[0].classList.remove('inactive');
    }
}