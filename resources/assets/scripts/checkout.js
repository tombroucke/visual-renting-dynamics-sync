/* global vrd_checkout_vars */
import domReady from '@roots/sage/client/dom-ready';

import flatpickr from "flatpickr";
import "flatpickr/dist/flatpickr.min.css";
import { Dutch } from "flatpickr/dist/l10n/nl.js";

/**
 * Application entrypoint
 */
domReady(async () => {
	flatpickr("#vrd_shipping_date", {
		minDate: vrd_checkout_vars.shipping_date.min_date,
		altInput: true,
		altFormat: "d/m/Y",
		locale: Dutch,
		disable: [
			function(date) {
				const disabledDays = vrd_checkout_vars.shipping_date.disabled_days;
				return (disabledDays.includes(date.getDay()));
			},
		],
	});
	flatpickr("#vrd_return_date", {
		minDate: vrd_checkout_vars.return_date.min_date,
		altInput: true,
		altFormat: "d/m/Y",
		locale: Dutch,
		disable: [
			function(date) {
				const disabledDays = vrd_checkout_vars.return_date.disabled_days;
				return (disabledDays.includes(date.getDay()));
			},
		],
	});

	const shippingMethodInputEl = document.getElementById('vrd_shipping_method');
	const shippingDateEl = document.getElementById('vrd_shipping_date_field');
	const shippingDateInputEl = document.getElementById('vrd_shipping_date');
	function changeshippingDateLabel() {
		shippingDateEl.querySelector('label').innerHTML = !shippingMethodInputEl.value || shippingMethodInputEl.value === 'delivery' ? shippingDateInputEl.getAttribute('data-delivery-label') : shippingDateInputEl.getAttribute('data-pickup-label');
	}

	shippingMethodInputEl.addEventListener('change', () => {
		changeshippingDateLabel();
	});

	changeshippingDateLabel();
});

/**
 * @see {@link https://webpack.js.org/api/hot-module-replacement/}
 */
import.meta.webpackHot?.accept(console.error);
