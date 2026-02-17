const data = {
	state: {
		user: {
			id: null,
			firstName: null,
			lastName: null,
			username: null,
			email: null
		},
		dashboard: {},
		notifications: [],
		notificationsCounter: 0,
		menuIsExpanded: false,
		dataIsLoading: false,
		csrfToken: document.head.querySelector('meta[name="csrf-token"]')
			.content,
		breadcrumbs: [],
		error: null
	},
	mutations: {
		dashboard(state, dashboard) {
			state.dashboard = dashboard;
		},
		user(state, user) {
			state.user = user;
		},
		notification(state, notification) {
			notification["id"] = state.notificationsCounter++;
			state.notifications.unshift(notification);
		},
		removeNotification(state, notification) {
			state.notifications.splice(notification, 1);
		},
		expandMenu(state, expand) {
			state.menuIsExpanded = expand;
		},
		loading(state, loading) {
			state.dataIsLoading = loading;
		},
		breadcrumbs(state, breadcrumbs) {
			state.breadcrumbs = breadcrumbs;
		},
		error(state, error) {
			state.error = error;
		}
	},
	actions: {
		resetBreadcrumbs({ commit }) {
			commit("breadcrumbs", []);
		}
	}
};

export default data;
