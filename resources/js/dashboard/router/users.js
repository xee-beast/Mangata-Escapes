import Index from "../pages/Users/Index";
import Show from "../pages/Users/Show";

const routes = [
	{
		path: "/users",
		name: "users",
		component: Index
	},
	{
		path: "/users/:id",
		name: "users.show",
		component: Show
	}
];

export default routes;
