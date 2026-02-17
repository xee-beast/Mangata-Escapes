import Index from "../pages/IndividualBookings/Index";
import Show from "../pages/IndividualBookings/Show";
import PaymentIndex from "../pages/IndividualBookings/Payments/Index";
import ClientIndex from "../pages/IndividualBookings/Clients/Index";
import ClientShow from "../pages/IndividualBookings/Clients/Show";

const routes = [
	{
		path: "/individual-bookings",
		name: "individual-bookings",
		component: Index
	},
	{
		path: "/individual-bookings/:id",
		name: "individual-bookings.show",
		component: Show
	},
	{
		path: "/individual-bookings/:id/payments",
		name: "individual-bookings.payments",
		component: PaymentIndex
	},
	{
		path: "/individual-bookings/:id/clients",
		name: "individual-bookings.clients",
		component: ClientIndex
	},
	{
		path: "/individual-bookings/:id/clients/:client",
		name: "individual-bookings.clients.show",
		component: ClientShow
	}
];

export default routes;
