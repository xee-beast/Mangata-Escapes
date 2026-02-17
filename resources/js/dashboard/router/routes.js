import Accomodations from "./accomodations";
import Agents from "./agents";
import Bookings from "./bookings";
import UnpaidBookings from "./unpaid-bookings";
import Clients from "./clients";
import Destinations from "./destinations";
import Errors from "./errors";
import Groups from "./groups";
import IndividualBookings from "./individual-bookings";
import Home from "./home";
import Hotels from "./hotels";
import InsuranceRates from "./insurance-rates";
import Payments from "./payments";
import Providers from "./providers";
import Roles from "./roles";
import Rooms from "./rooms";
import ToDo from "./to-do";
import Users from "./users";
import Results from "./results";
import Calendar from "./calendar";
import Event from "./event";
import Trash from "./trash";
import Airlines from "./airlines";
import Airports from "./airports";
import Faqs from "./faqs";
import Notifications from "./notifications";
import NotificationLogs from "./logs";
import Transfers from "./transfers";
import Leads from "./leads";
import Brands from "./brands";

const routes = [].concat(
	Accomodations,
	Agents,
	Bookings,
	UnpaidBookings,
	Clients,
	Destinations,
	Errors,
	Groups,
	IndividualBookings,
	Home,
	Hotels,
	InsuranceRates,
	Payments,
	Providers,
	Roles,
	Rooms,
	ToDo,
	Users,
	Results,
	Calendar,
	Event,
	Trash,
	Airlines,
	Airports,
	Faqs,
	Notifications,
	NotificationLogs,
	Transfers,
	Leads,
	Brands,
);

export default routes;
