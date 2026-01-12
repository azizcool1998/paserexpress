import { MapContainer, TileLayer, Marker, Polyline } from "react-leaflet";
import L from "leaflet";
import iconUrl from "leaflet/dist/images/marker-icon.png";
import iconRetinaUrl from "leaflet/dist/images/marker-icon-2x.png";
import shadowUrl from "leaflet/dist/images/marker-shadow.png";

delete L.Icon.Default.prototype._getIconUrl;
L.Icon.Default.mergeOptions({ iconUrl, iconRetinaUrl, shadowUrl });

export default function DriverPanelMap({ order, driverGeo }) {
  const pickup = (order?.pickup_lat != null && order?.pickup_lng != null)
    ? [Number(order.pickup_lat), Number(order.pickup_lng)]
    : null;

  const dropoff = (order?.dropoff_lat != null && order?.dropoff_lng != null)
    ? [Number(order.dropoff_lat), Number(order.dropoff_lng)]
    : null;

  const dpos = (driverGeo?.lat != null && driverGeo?.lng != null)
    ? [Number(driverGeo.lat), Number(driverGeo.lng)]
    : null;

  const center = dpos || pickup || dropoff || [-1.2654, 116.8312];

  const line = [];
  if (dpos) line.push(dpos);
  if (pickup) line.push(pickup);
  if (dropoff) line.push(dropoff);

  return (
    <div style={{ borderRadius: 12, overflow: "hidden", marginTop: 10 }}>
      <MapContainer center={center} zoom={13} style={{ height: 260, width: "100%" }}>
        <TileLayer
          attribution="&copy; OpenStreetMap contributors"
          url="https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png"
        />
        {dpos && <Marker position={dpos} />}
        {pickup && <Marker position={pickup} />}
        {dropoff && <Marker position={dropoff} />}
        {line.length >= 2 && <Polyline positions={line} />}
      </MapContainer>
    </div>
  );
}
