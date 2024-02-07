/**
 * Function called to toggle the collapse <span> Elements (Collapse All / Expand All)
 * Example call in: templates/local/content/section/content.mustache
 */
function toggleCollapseAll(id) {
    let collapseSection = document.getElementById(id);

    //The collapse all span Element
    let collapseSpan = document.querySelector('.collapseall');

    //The Expand All Span Element
    let expandSpan = document.querySelector('.expandall');

    //Check if collapse Section Button is expanded
    let isExpanded = collapseSection.getAttribute('aria-expanded') === 'true';
    //The Content Body Element which gets collapsed
    let contentElements = document.querySelectorAll('.content.course-content-item-content.collapse');

    //If new CollapseSection is expanded show the new span (collapse) and all body
    if (isExpanded) {
        //Show the collapse All Button
        collapseSpan.style.display = 'none';
        expandSpan.style.display = 'inline';
        
        //Open all content Elements
        contentElements.forEach(element => {
            if(element.classList.contains('show')){
                element.classList.remove('show');
            }
        });

        //Show the leftside Section Button collapse Icon 
        document.querySelectorAll('[id^="editcollapssesection"]').forEach(element => {
            element.classList.add("collapsed");
            element.querySelector(".expanded-icon").style.display = "none";
            element.querySelector(".collapsed-icon").style.display = "block";
        });

    } else {
        //Show the expand All Button
        collapseSpan.style.display = 'inline';
        expandSpan.style.display = 'none';
        
        //Close all content Elements
        contentElements.forEach(element => {
            if(!element.classList.contains('show')){
                element.classList.add('show');
            }
        });

        //Show the leftside Section Button expand Icon 
        document.querySelectorAll('[id^="editcollapssesection"]').forEach(element => {
            element.classList.remove("collapsed");
            element.querySelector(".expanded-icon").style.display = "block";
            element.querySelector(".collapsed-icon").style.display = "none";
        });

    }

    //Toggle and set the isExpanded Status
    isExpanded = !isExpanded;
    collapseSection.setAttribute('aria-expanded', isExpanded);
}


/**
 * Check if all collapse Elemetens in DOM are closed
 * @returns true if All Collapses are Closed
 */
function checkIfAllCollapseAreClosed(){
    let check = true;
    let contentElements = document.querySelectorAll('[id^="coursecontentcollapse"]');

    //contentElements = Array.from(contentElements)
    //let check = contentElements.every(item => !item.classList.);

    contentElements.forEach(element => {
       if(element.classList.contains('show') || element.classList.contains('collapsing')){
        check = false;
       }
    });
    return check;
}

/**
 * Shows the CollapseAll or ExpandAll Span Element depending on the current State 
 */
function showNewSpanTitleIfAllCollapseAreClosedOrOpen(){
    //The collapse all span Element
    let collapseSpan = document.querySelector('.collapseall');
    //The Expand All Span Element
    let expandSpan = document.querySelector('.expandall');

        
    if(checkIfAllCollapseAreClosed()){
        collapseSpan.style.display = 'none';
        expandSpan.style.display = 'inline';

        //If all Collapse Elements are closed set the Section aria-expanded to false
        let collapseSection = document.getElementById('collapsesections')
        collapseSection.setAttribute('aria-expanded', false);
    }else{
        collapseSpan.style.display = 'inline';
        expandSpan.style.display = 'none';

        //If all Collapse Elements are open set the Section aria-expanded to true
        let collapseSection = document.getElementById('collapsesections')
        collapseSection.setAttribute('aria-expanded', true);
    }
}


/**
 * Init the Toggle Functionality 
 */
function initToggle(){
    //Check if a User is in Editmode
    if(document.querySelector('[id$="editingswitch"]').checked){
        showNewSpanTitleIfAllCollapseAreClosedOrOpen();
        
        let collapseIcons = document.querySelectorAll(".icons-collapse-expand");
        collapseIcons.forEach(icon => {
            icon.style.display = "block";
        });


        //Enables the collapseSection Button in header.mustache 
        document.querySelectorAll('[id^="editcollapssesection"]').forEach(function(element) {
            element.onclick = function() {
                console.log("ONCLICK: ");
                console.log(element);
                //Get Selectors of a collapseSection Element
                var expandedIcon = this.querySelector(".expanded-icon");
                var collapsedIcon = this.querySelector(".collapsed-icon");
                var currentIndexNum = this.getAttribute("data-toggle");
                //Get the corresponding section element
                var contentBlock = document.getElementById("coursecontentcollapse" + currentIndexNum);

                //If the current Section is collapsed remove the tag, show the corresponding section and change the icon
                if (this.classList.contains("collapsed")) {
                    this.classList.remove("collapsed");
                    contentBlock.classList.add("show");
                    expandedIcon.style.display = "block"; 
                    collapsedIcon.style.display = "none"; 
                    this.setAttribute("aria-expanded", "false"); 
                } else {
                    //If the current Section is open add the collapsed the tag, hide the corresponding section and change the icon
                    this.classList.add("collapsed");
                    contentBlock.classList.remove("show");
                    expandedIcon.style.display = "none"; 
                    collapsedIcon.style.display = "block"; 
                    this.setAttribute("aria-expanded", "true");
                }
            };
        });
      


        //Observe all contentElements to change the collapseAll/ExpandAll status when a contentcollapse opens/closes
        //Example: When all contentElements are closed, set the collapseAll -> expandAll
        let contentCollapseElements = document.querySelectorAll('[id^="coursecontentcollapse"]');
        contentCollapseElements.forEach(element => {
            const observer = new MutationObserver(mutations => {
                mutations.forEach(mutation => {
                    if (mutation.attributeName === 'class') {
                        showNewSpanTitleIfAllCollapseAreClosedOrOpen();
                    }
                });
            });
            observer.observe(element, { attributes: true });
        });
    }
}

//Init the Toggle Functionality
initToggle();
