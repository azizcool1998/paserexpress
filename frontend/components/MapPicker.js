import dynamic from "next/dynamic";
const MapPickerClient = dynamic(() => import("./MapPickerClient"), { ssr: false });
export default MapPickerClient;
