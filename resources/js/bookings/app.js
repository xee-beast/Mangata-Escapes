import axios from "../axios";
import VCalendar from 'v-calendar';
import Vue from "vue";
import moment from "moment";

Vue.prototype.$http = axios;
Vue.prototype.$moment = moment;

const files = require.context('./', true, /\.vue$/i);
files.keys().map(key => Vue.component(key.split('/').pop().split('.')[0], files(key).default));

Vue.use(VCalendar);

window.EventBus = new Vue();

const app = new Vue({
	el: "#individual-booking-forms",
});

export default app;
