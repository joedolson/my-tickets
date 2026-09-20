const { execFileSync } = require( 'child_process' );
const path = require( 'path' );

const ROOT_DIR = path.resolve( __dirname, '..', '..', '..' );
const NPX = process.platform === 'win32' ? 'npx.cmd' : 'npx';

/**
 * Run a wp-cli command inside the wp-env container.
 *
 * @param {string[]} args wp-cli arguments, e.g. [ 'post', 'meta', 'update', '1', 'key', 'value' ].
 */
function wpCli( args ) {
	return execFileSync( NPX, [ 'wp-env', 'run', 'cli', 'wp', ...args ], {
		cwd: ROOT_DIR,
		encoding: 'utf-8',
	} );
}

/**
 * Set post meta via wp-cli, JSON-encoding the value so arrays/objects survive intact.
 *
 * Used for meta such as `_mt_registration_options` that isn't registered for the REST API.
 *
 * @param {number} postId Post ID.
 * @param {string} key    Meta key.
 * @param {*}      value  Meta value (will be JSON-encoded).
 */
function setPostMeta( postId, key, value ) {
	wpCli( [ 'post', 'meta', 'update', String( postId ), key, JSON.stringify( value ), '--format=json' ] );
}

module.exports = { wpCli, setPostMeta };
