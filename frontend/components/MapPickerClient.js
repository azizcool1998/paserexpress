import { MapContainer, TileLayer, Marker, useMapEvents } from "react-leaflet";
import L from "leaflet";
import iconUrl from "leaflet/dist/images/marker-icon.png";
import iconRetinaUrl from "leaflet/dist/images/marker-icon-2x.png";
import shadowUrl from "leaflet/dist/images/marker-shadow.png";

delete L.Icon.Default.prototype._getIconUrl;
L.Icon.Default.mergeOptions({ iconUrl, iconRetinaUrl, shadowUrl });

function ClickToSet({ onPick }) {
  useMapEvents({
    click(e) {
      onPick({ lat: e.latlng.lat, lng: e.latlng.lng });
    }
  });
  return null;
}

export default function MapPickerClient({
  value,
  onChange,
  height = 320,
  center = [-1.2654, 116.8312],
  zoom = 12
}) {
  const pos = value?.lat != null && value?.lng != null ? [value.lat, value.lng] : null;

  return (
    <div style={{ borderRadius: 12, overflow: "hidden" }}>
      <MapContainer center={pos || center} zoom={zoom} style={{ height, width: "100%" }}>
        <TileLayer
          attribution="&copy; OpenStreetMap contributors"
          url="https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png"
        />
        <ClickToSet onPick={onChange} />
        {pos && <Marker position={pos} />}
      </MapContainer>
    </div>
  );
}
