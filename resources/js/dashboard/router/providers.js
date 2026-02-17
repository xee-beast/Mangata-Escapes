import Index from "../pages/Providers/Index";
import Show from "../pages/Providers/Show";

const routes = [
	{
		path: "/suppliers",
		name: "providers",
		component: Index,
		props: true
	},
	{
		path: "/suppliers/:id",
		name: "providers.show",
		component: Show
	}
];

export default routes;
