import Index from "../pages/Hotels/Rooms/Index";
import Show from "../pages/Hotels/Rooms/Show";

const routes = [
	{
		path: "/hotels/:hotel/rooms",
		name: "rooms",
		component: Index
	},
	{
		path: "/hotels/:hotel/rooms/:id",
		name: "rooms.show",
		component: Show
	}
];

export default routes;
