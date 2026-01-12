import { MapContainer, TileLayer, Marker } from "react-leaflet";
import L from "leaflet";
import iconUrl from "leaflet/dist/images/marker-icon.png";
import iconRetinaUrl from "leaflet/dist/images/marker-icon-2x.png";
import shadowUrl from "leaflet/dist/images/marker-shadow.png";

delete L.Icon.Default.prototype._getIconUrl;
L.Icon.Default.mergeOptions({ iconUrl, iconRetinaUrl, shadowUrl });

export default function DriverLocationsMap({ rows }) {
  const pts = (rows || []).filter(r => r.lat != null && r.lng != null);
  const center = pts.length ? [Number(pts[0].lat), Number(pts[0].lng)] : [-1.2654, 116.8312];

  return (
    <div style={{ borderRadius: 12, overflow: "hidden" }}>
      <MapContainer center={center} zoom={9} style={{ height: 360, width: "100%" }}>
        <TileLayer
          attribution="&copy; OpenStreetMap contributors"
          url="https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png"
        />
        {pts.map(p => (
          <Marker key={p.id} position={[Number(p.lat), Number(p.lng)]} />
        ))}
      </MapContainer>
    </div>
  );
}
