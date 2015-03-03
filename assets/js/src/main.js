/**
 * Main.js
 */
// Object basic
var fr;
if (!fr) {
	fr = {};
} else {
	if (typeof fr !== "object") {
		throw new Error('fr already exists and not an object');
	}
}

if (!fr.bea_sender) {
	fr.bea_sender = {};
} else {
	if (typeof fr.bea_sender !== "object") {
		throw new Error('fr.bea_sender already exists and not an object');
	}
}

fr.bea_sender = {
	views : {},
	models : {}
};