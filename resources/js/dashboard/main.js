import axios from "../axios";
import data from "./store/data";
import moment from "moment";
import Router from "vue-router";
import routes from "./router/routes";
import vapor from "laravel-vapor";
import storage from "../storage";
import Vue from "vue";
import Vuex from "vuex";

Vue.prototype.$vapor = vapor;
Vue.prototype.$storage = storage;

Vue.prototype.$moment = moment;
Vue.prototype.$dashboardBase = typeof window !== "undefined" && window.DASHBOARD_BASE_PATH ? window.DASHBOARD_BASE_PATH : "/dashboard";

Vue.use(Vuex);
const store = new Vuex.Store(data);

Vue.use(Router);
const router = new Router({
	mode: "history",
	base: typeof window !== "undefined" && window.DASHBOARD_BASE_PATH ? window.DASHBOARD_BASE_PATH : "/",
	routes,
	linkActiveClass: "is-active",
	linkExactActiveClass: "is-active-exact"
});
router.afterEach(() => {
	store.dispatch("resetBreadcrumbs");
});

Vue.prototype.$http = axios;
axios.interceptors.request.use(
	config => {
		if (config.method == "get" && config.url != "refresh-token" && !config.url.includes('/calendar/bookings')) {
			store.commit("loading", true);
		}
		return config;
	},
	error => {
		store.commit("loading", false);
		return Promise.reject(error);
	}
);
axios.interceptors.response.use(
	response => {
		store.commit("loading", false);
		return response;
	},
	error => {
		store.commit("loading", false);

		if (error.response.status == 403) {
			store.commit("notification", {
				type: "danger",
				message: "You are not authorized to make this request."
			});
		} else if (error.response.status == 422) {
			store.commit("notification", {
				type: "danger",
				message: error.response.data.message
			});
		} else {
			store.commit("notification", {
				type: "danger",
				message: "An unexpected error has occured"
			});
		}

		if (error.response.config.method == "get") {
			store.commit("error", {
				status: error.response.status,
				message: error.response.statusText
			});
		}

		return Promise.reject(error);
	}
);

import App from "./App";

const app = new Vue({
	store,
	router,
	render: h => h(App)
}).$mount("#app");

export default app;
