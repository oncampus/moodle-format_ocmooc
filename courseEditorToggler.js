//Init the Toggle Functionality
document.addEventListener("DOMContentLoaded", function() 
{
    if(document.querySelector('[id$="editingswitch"]').checked){
        let collapseIcons = document.querySelectorAll(".icons-collapse-expand");
        collapseIcons.forEach(icon => {
            icon.style.display = "flex";
        });
    }else{
        let contentCollapseElements = document.querySelectorAll('[id^="coursecontentcollapse"]');
        contentCollapseElements.forEach(element => {
            if(!element.classList.contains("show")){
                element.classList.add("show");
            }
        });
    }
});