import Index from "../pages/Groups/Index";
import Show from "../pages/Groups/Show";

const routes = [
	{
		path: "/groups",
		name: "groups",
		component: Index,
		props: true
	},
	{
		path: "/groups/:id",
		name: "groups.show",
		component: Show
	}
];

export default routes;
