import Index from "../pages/Roles/Index";
import Show from "../pages/Roles/Show";

const routes = [
	{
		path: "/roles",
		name: "roles",
		component: Index
	},
	{
		path: "/roles/:id",
		name: "roles.show",
		component: Show
	}
];

export default routes;
