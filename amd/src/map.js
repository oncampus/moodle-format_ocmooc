/* global L */
import {call as fetchMany} from 'core/ajax';
import {get_string as getString} from 'core/str';

export const init = async (courseid) => {

    const overlay = document.getElementById('mapoverlay');
    if (overlay) {
        overlay.style.transition = 'opacity 0.3s';
        overlay.style.opacity = '0';
        setTimeout(() => overlay.remove(), 300);
    }
    const map = L.map('map').setView([51.1657, 10.4515], 5);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
    }).addTo(map);
    const response = await fetchParticipantLocations(courseid);

    const markersWithStrings = await Promise.all(response.locations.map(async (marker) => {
        const participantString = marker.participant_count === 1
            ? await getString('participant', 'format_ocmooc')
            : await getString('participants', 'core');

        return {
            ...marker,
            participantString
        };
    }));

    markersWithStrings.forEach((marker) => {
        const popupContent = `<b>${marker.location_name}</b><br>${marker.participant_count} ${marker.participantString}`;

        L.marker([marker.latitude, marker.longitude], {
            alt: `${marker.location_name}: ${marker.participant_count} ${marker.participantString}`
        }).addTo(map)
            .bindPopup(popupContent);
    });
};

const fetchParticipantLocations = (courseid) => fetchMany([{
    methodname: 'format_ocmooc_get_participant_locations',
    args: {courseid: courseid,},
}])[0];


