import Error from "../pages/Error";

const routes = [
	{
		path: "*",
		name: "404",
		component: Error,
		props: {
			status: 404,
			message: "Page not found"
		}
	}
];

export default routes;
