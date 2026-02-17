/**
 * We'll load the axios HTTP library which allows us to easily issue requests
 * to our Laravel back-end. This library automatically handles sending the
 * CSRF token as a header based on the value of the "XSRF" token cookie.
 */

const axios = require("axios");

axios.defaults.baseURL = process.env.MIX_API_URL;
axios.defaults.headers.common["X-Requested-With"] = "XMLHttpRequest";
axios.defaults.headers.common["X-CSRF-TOKEN"] = document.head.querySelector(
	'meta[name="csrf-token"]'
).content;
axios.defaults.withCredentials = true;

export default axios;
