import Index from "../pages/Leads/Index";
import Show from "../pages/Leads/Show";

const routes = [
	{
		path: "/leads",
		name: "leads",
		component: Index
	},
	{
		path: "/leads/:id",
		name: "leads.show",
		component: Show
	}
];

export default routes;