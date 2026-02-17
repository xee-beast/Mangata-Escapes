import Index from "../pages/Groups/Bookings/Index";
import Show from "../pages/Groups/Bookings/Show";

const routes = [
	{
		path: "/groups/:group/bookings",
		name: "bookings",
		component: Index
	},
	{
		path: "/groups/:group/bookings/:id",
		name: "bookings.show",
		component: Show
	}
];

export default routes;
