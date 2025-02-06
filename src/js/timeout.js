let timeoutId = 0;
let timeouts = {};

let worker = new Worker( "/wp-content/plugins/my-tickets/src/js/timeout-worker.js" );
worker.addEventListener( "message", function( evt ) {
		let data = evt.data,
		    id = data.id,
		    fn = timeouts[ id ].fn,
		    args = timeouts[ id ].args;

	fn.apply(null, args);
	delete timeouts[ id ];
});

window.setTimeout = function(fn, delay) {
	let args = Array.prototype.slice.call(arguments, 2);
	timeoutId += 1;
	delay = delay || 0;
	let id = timeoutId;
	timeouts[id] = {fn: fn, args: args};
	worker.postMessage({command: "setTimeout", id: id, timeout: delay});

	return id;
};

window.clearTimeout = function(id) {
  worker.postMessage({command: "clearTimeout", id: id});
  delete timeouts[id];
};