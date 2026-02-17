import Index from "../pages/Groups/Accomodations/Index";
import Show from "../pages/Groups/Accomodations/Show";

const routes = [
	{
		path: "/groups/:group/accommodations",
		name: "accomodations",
		component: Index
	},
	{
		path: "/groups/:group/accommodations/:id",
		name: "accomodations.show",
		component: Show
	}
];

export default routes;
