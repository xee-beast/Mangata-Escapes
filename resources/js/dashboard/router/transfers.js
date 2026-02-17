import Index from "../pages/Transfers/Index";
import Show from "../pages/Transfers/Show";

const routes = [
	{
		path: "/transfers",
		name: "transfers",
		component: Index
	},
	{
		path: "/transfers/:id",
		name: "transfers.show",
		component: Show
	},
];

export default routes;