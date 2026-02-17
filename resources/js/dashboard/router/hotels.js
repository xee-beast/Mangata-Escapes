import Index from "../pages/Hotels/Index";
import Show from "../pages/Hotels/Show";

const routes = [
	{
		path: "/hotels",
		name: "hotels",
		component: Index
	},
	{
		path: "/hotels/:id",
		name: "hotels.show",
		component: Show
	}
];

export default routes;
