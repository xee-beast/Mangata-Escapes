import Index from "../pages/Groups/Bookings/Clients/Index";
import Show from "../pages/Groups/Bookings/Clients/Show";

const routes = [
	{
		path: "/groups/:group/bookings/:booking/clients",
		name: "clients",
		component: Index
	},
	{
		path: "/groups/:group/bookings/:booking/clients/:id",
		name: "clients.show",
		component: Show
	}
];

export default routes;
