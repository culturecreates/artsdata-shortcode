
//
//
// FOREACH needed for generating unique variables to match up with each unique map div (i.e. map1, map2, etc.)
// Each map div must have a unique ID for the JS to target (e.g. var map1 = L.map('map1'..., var map2 = L.map('map2'
// Coordinates don't need the Point(-123.12057 49.27993) format, instead only need the comma-separated values shown below
// These coordinate values need to be output twice for each variable: to center the map then to add a marker
//
//
var map1 = L.map('map1', {
    center: [45.42319, -75.69329],
    zoom: 15,
    zoomControl: false,
    fullscreenControl: true,
    attributionControl: false
});

L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
    attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
}).addTo(map1);

L.marker([45.42319, -75.69329]).addTo(map1).setOpacity(0.85);