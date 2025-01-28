/* global L */
import {call as fetchMany} from 'core/ajax';
import {get_string as getString} from 'core/str';

export const init = async (courseid) => {
    const overlay = document.getElementById('map-overlay');
    const map = L.map('map', { preferCanvas: true }).setView([51.1657, 10.4515], 5);

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
    }).addTo(map);

    // Cluster Layer erstellen
    const markersCluster = L.markerClusterGroup();

    try {
        // Daten abrufen
        const response = await fetchParticipantLocations(courseid);

        // Entferne Overlay nach dem Laden der Daten
        if (overlay) {
            overlay.style.transition = 'opacity 0.3s';
            overlay.style.opacity = '0';
            setTimeout(() => overlay.remove(), 300);
        }

        // Teilnehmerdaten verarbeiten
        const markersWithStrings = await Promise.all(response.locations.map(async (marker) => {
            const participantString = marker.participant_count === 1
                ? await getString('participant', 'format_ocmooc')
                : await getString('participants', 'core');

            return { ...marker, participantString };
        }));

        // Marker mit Clustering hinzufügen
        markersWithStrings.forEach((marker) => {
            const popupContent = `<b>${marker.location_name}</b><br>${marker.participant_count} ${marker.participantString}`;

            const markerInstance = L.marker([marker.latitude, marker.longitude], {
                alt: `${marker.location_name}: ${marker.participant_count} ${marker.participantString}`
            }).bindPopup(popupContent);

            markersCluster.addLayer(markerInstance);
        });

        // Cluster zur Karte hinzufügen
        map.addLayer(markersCluster);

    } catch (error) {
        console.error('Failed to fetch participant locations:', error);
    }
};

const fetchParticipantLocations = (courseid) => fetchMany([{
    methodname: 'format_ocmooc_get_participant_locations',
    args: {courseid: courseid,},
}])[0];


