var container = document.getElementById('oc-chapters-navigation');
var slider = document.getElementById('oc-chapters-slider');
var slides = document.getElementsByClassName('oc-chapter-item').length - 1;
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

function setCenter() {
    let items = getAlleItems();
    for (let i = 0; i < items.length; i++) {
        if (Math.abs(currentMargin) / 284 === i) {
            items[i].classList.add('center');
            window.console.log(i);
        } else {
            items[i].classList.remove('center');
        }
    }
}

startPosition = getCurrentActiveIndex();
currentPosition = getCurrentActiveIndex();

function slideRight() {
    if (currentPosition != 0) {
        slider.style.marginLeft = currentMargin + 284 + 'px';
        currentMargin += 284;
        currentPosition--;
    }
    if (currentPosition === 0) {
        buttons[0].classList.add('inactive');
    }
    if (currentPosition < slidesCount) {
        buttons[1].classList.remove('inactive');
    }
    setCenter();
}

function slideLeft() {
    if (currentPosition < slides) {
        slider.style.marginLeft = currentMargin - 284 + 'px';
        currentMargin -= 284;
        currentPosition++;
    }
    if (currentPosition == slides) {
        buttons[1].classList.add('inactive');
    }
    if (currentPosition > 0) {
        buttons[0].classList.remove('inactive');
    }
    setCenter();
}