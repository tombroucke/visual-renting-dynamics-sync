import domReady from '@roots/sage/client/dom-ready';

import flatpickr from "flatpickr";
import "flatpickr/dist/flatpickr.min.css";

/**
 * Application entrypoint
 */
domReady(async () => {
	flatpickr("#vrd_shipping_date", {
		minDate: "today",
		altInput: true,
		altFormat: "d/m/Y",
	});
	flatpickr("#vrd_return_date", {
		minDate: "today",
		altInput: true,
		altFormat: "d/m/Y",
	});
});

/**
 * @see {@link https://webpack.js.org/api/hot-module-replacement/}
 */
import.meta.webpackHot?.accept(console.error);
