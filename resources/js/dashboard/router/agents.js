import Index from "../pages/Agents/Index";
import Show from "../pages/Agents/Show";

const routes = [
	{
		path: "/agents",
		name: "agents",
		component: Index,
		props: route => ({ 
			status: route.query.status,
			search: route.query.search,
			page: Number(route.query.page) || 1
		})
	},
	{
		path: "/agents/:id",
		name: "agents.show",
		component: Show,
		props: true
	}
];

export default routes;
