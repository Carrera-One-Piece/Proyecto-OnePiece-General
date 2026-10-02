const map = L.map('map', { crs: L.CRS.Simple, minZoom: -3, maxZoom: 3 });
map.fitBounds([[0,0],[1000,1600]]);

fetch('/api/islas')
  .then(r => r.json())
  .then(islas => {
    islas.forEach(i => {
      L.circleMarker(i.coords, { radius: 8, color: '#5f7a45' })
        .bindPopup(`<b>${i.nombre}</b>`)
        .addTo(map);
    });
  });