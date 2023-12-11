// Website
window.onmessage = (e) => {
    // Update percentage
   if (e.data.hasOwnProperty("progressDiv") &&
        e.data.hasOwnProperty("textDiv") &&
        e.data.hasOwnProperty("percentage")) {
        document.getElementById(e.data.progressDiv).style.width = e.data.percentage;
        document.getElementById(e.data.textDiv).innerHTML = e.data.percentage;
        window.console.log(e);
    } else if (e.data.hasOwnProperty("frameHeight") && e.data.hasOwnProperty("frameId")) {
        // To avoid retroactively adding/using ids on iframes
        let frame = document.getElementById(e.data.frameId);
        // Adjust the height
        frame.style.height = `${e.data.frameHeight}px`;
    }
};
