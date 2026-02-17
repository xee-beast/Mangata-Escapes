import Index from "../pages/Providers/InsuranceRates/Index";
import Show from "../pages/Providers/InsuranceRates/Show";

const routes = [
	{
		path: "/suppliers/:provider/insurance-rates",
		name: "insuranceRates",
		component: Index
	},
	{
		path: "/suppliers/:provider/insurance-rates/:id",
		name: "insuranceRates.show",
		component: Show
	}
];

export default routes;
