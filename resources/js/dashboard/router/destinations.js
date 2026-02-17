import Index from "../pages/Destinations/Index";
import Show from "../pages/Destinations/Show";

const routes = [
	{
		path: "/destinations",
		name: "destinations",
		component: Index
	},
	{
		path: "/destinations/:id",
		name: "destinations.show",
		component: Show
	}
];

export default routes;
