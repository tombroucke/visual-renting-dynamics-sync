/** @param {import('@roots/bud').Bud} bud */

export default async (bud) => {
	bud.setPath({
		"@src": "resources/assets/",
		"@dist": "public",
	})

	bud.entry({
		'checkout': ['scripts/checkout.js'],
		'admin': ['styles/admin.css'],
	})
}  
