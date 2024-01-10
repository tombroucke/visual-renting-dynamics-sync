import domReady from '@roots/sage/client/dom-ready';

import flatpickr from "flatpickr";
import "flatpickr/dist/flatpickr.min.css";
import { Dutch } from "flatpickr/dist/l10n/nl.js";

/**
 * Application entrypoint
 */
domReady(async () => {
	flatpickr("#vrd_shipping_date", {
		minDate: "today",
		altInput: true,
		altFormat: "d/m/Y",
		locale: Dutch,
	});
	flatpickr("#vrd_return_date", {
		minDate: "today",
		altInput: true,
		altFormat: "d/m/Y",
		locale: Dutch,
	});
});

/**
 * @see {@link https://webpack.js.org/api/hot-module-replacement/}
 */
import.meta.webpackHot?.accept(console.error);
