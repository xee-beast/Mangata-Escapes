const mix = require("laravel-mix");
const webpack = require("webpack");
const path = require('path');

/*
 |--------------------------------------------------------------------------
 | Mix Asset Management
 |--------------------------------------------------------------------------
 |
 | Mix provides a clean, fluent API for defining some Webpack build steps
 | for your Laravel application. By default, we are compiling the Sass
 | file for the application as well as bundling up all the JS files.
 |
 */

require("mix-env-file");
mix.env(process.env.ENV_FILE);

mix.webpackConfig({
	resolve: {
		alias: {
			"@dashboard": path.resolve(__dirname, "resources/js/dashboard")
		}
	},
	plugins: [
		new webpack.IgnorePlugin({
			resourceRegExp: /^\.\/locale$/,
			contextRegExp: /moment/,
		})
	]
});

mix.sass("resources/scss/web/app.scss", "public/css/web")
	.sass("resources/scss/app.scss", "public/css")
	.sass("resources/scss/auth.scss", "public/css")
	.sass("resources/scss/dashboard.scss", "public/css")
	.js("resources/js/web/app.js", "public/js/web")
	.js("resources/js/dashboard/main.js", "public/js/dashboard")
	.js("resources/js/couples/app.js", "public/js/couples")
	.js("resources/js/bookings/app.js", "public/js/bookings")
	.js("resources/js/account/app.js", "public/js/account")
	.vue({
		extractStyles: "public/js/dashboard/main.css",
		globalStyles: "resources/scss/_variables.scss"
	});

require("laravel-mix-bundle-analyzer");
if (mix.isWatching()) {
	mix.bundleAnalyzer();
}
