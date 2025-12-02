//This File locks the Edit Menu Button until the page is completely loaded.

//Get the edtiting button
var inputElement = document.querySelector('[id$="-editingswitch"]');
//disable it
if (inputElement) {
    inputElement.disabled = true;
}
//Wait until the page loaded
window.onload = function() {
    //Enable the button again
    if (inputElement) {
        inputElement.disabled = false;
    }
}
