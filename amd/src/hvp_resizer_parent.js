// Website
window.onmessage = (e) => {
    // Make sure its the message we need
    if (e.data.hasOwnProperty("frameHeight") && e.data.hasOwnProperty("frameId")) {
        // To avoid retroactively adding/using ids on iframes
        let frame = document.getElementById(e.data.frameId);
        // Adjust the height
        frame.style.height = `${e.data.frameHeight}px`;
    }
};