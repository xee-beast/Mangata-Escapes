import Account from "../pages/Account";
import Index from "../pages/Index";

const routes = [
	{
		path: "/",
		name: "home",
		component: Index
	},
	{
		path: "/account",
		name: "account",
		component: Account
	}
];

export default routes;
