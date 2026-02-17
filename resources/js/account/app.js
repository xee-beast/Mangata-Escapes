import App from "./index";
import Router from "./router";
import Vue from "vue";
require("../axios");

const app = new Vue({
	el: "#app",
	router: Router,
	render: createElement => createElement(App)
});

export default app;
