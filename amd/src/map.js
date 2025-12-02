/* global L */
import {call as fetchMany} from 'core/ajax';
import {get_string as getString} from 'core/str';

/**
 * Initializes the map and handles loading participant locations.
 * @param {number} courseid - The ID of the course to fetch participant locations for.
 */
export const init = async(courseid) => {
    // Get the overlay element that will be hidden once the data is loaded
    const overlay = document.getElementById('map-overlay');

    // Initialize the Leaflet map with a default center (Germany) and zoom level
    const map = L.map('map', {preferCanvas: true}).setView([51.1657, 10.4515], 5);

    // Add a tile layer using OpenStreetMap tiles
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
    }).addTo(map);

    // Wait until the map is fully loaded and then adjust the links
    setTimeout(() => {
        document.querySelectorAll(".leaflet-control-attribution a").forEach(link => {
            link.setAttribute("target", "_blank");
        });
    }, 1000);

    // Create a marker cluster group to manage marker clustering efficiently
    const markersCluster = L.markerClusterGroup();

    try {
        // Fetch participant location data for the given course ID
        const response = await fetchParticipantLocations(courseid);

        // Hide and remove the overlay after the data is successfully loaded
        if (overlay) {
            overlay.style.transition = 'opacity 0.3s'; // Set a smooth fade-out transition
            overlay.style.opacity = '0'; // Start the fade-out effect
            setTimeout(() => overlay.remove(), 300); // Remove the overlay after the transition (300 ms)
        }

        // Process each participant location to include localized participant string
        const markersWithStrings = await Promise.all(response.locations.map(async(marker) => {
            const participantString = marker.participant_count === 1
                ? await getString('participant', 'format_ocmooc') // Singular form for 1 participant
                : await getString('participants', 'core'); // Plural form for multiple participants

            // Return the marker with additional information for display
            return {...marker, participantString};
        }));

        // Add each marker to the cluster group with a popup
        markersWithStrings.forEach((marker) => {
            const popupContent = `<b>${marker.location_name}</b><br>${marker.participant_count} ${marker.participantString}`;

            const markerInstance = L.marker([marker.latitude, marker.longitude], {
                alt: `${marker.location_name}: ${marker.participant_count} ${marker.participantString}`
            }).bindPopup(popupContent); // Bind popup with location and participant details

            markersCluster.addLayer(markerInstance); // Add marker to the cluster
        });

        // Add the marker cluster group to the map
        map.addLayer(markersCluster);

    } catch (error) {
        // Log any errors that occur during the data fetch or processing
        console.error('Failed to fetch participant locations:', error);
    }
};

/**
 * Fetches participant locations for a given course from the Moodle backend.
 * @param {number} courseid - The ID of the course to fetch participant locations for.
 * @returns {Promise<Object>} A promise that resolves with the location data.
 */
const fetchParticipantLocations = (courseid) => fetchMany([{
    methodname: 'format_ocmooc_get_participant_locations', // Moodle backend function to call
    args: {courseid: courseid}, // Pass the course ID as an argument
}])[0]; // Retrieve the first response (assumes single response)