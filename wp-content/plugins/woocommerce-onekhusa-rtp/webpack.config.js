/**
 * Extends @wordpress/scripts with a Checkout Blocks bundle and WooCommerce externals.
 *
 * @package WooCommerce_Onekhusa_RTP
 */

const path = require( 'path' );
const baseConfig = require( '@wordpress/scripts/config/webpack.config.js' );
const WooCommerceDependencyExtractionWebpackPlugin = require( '@woocommerce/dependency-extraction-webpack-plugin' );

const scriptConfig = Array.isArray( baseConfig ) ? baseConfig[ 0 ] : baseConfig;

module.exports = {
	...scriptConfig,
	output: {
		...scriptConfig.output,
		...( typeof scriptConfig.output.clean === 'object' && {
			clean: {
				...scriptConfig.output.clean,
				/**
				 * Preserve images/fonts (default) and README; build does not emit README.
				 *
				 * @param {string} asset Path passed by webpack clean.
				 * @return {boolean} True to keep the file when cleaning `build/`.
				 */
				keep( asset ) {
					const normalized = String( asset ).replace( /\\/g, '/' );
					const defaultKeep = /(^|\/)fonts\//.test( normalized )
						|| /(^|\/)images\//.test( normalized );

					if ( defaultKeep ) {
						return true;
					}
					if (
						normalized.endsWith( '/README.md' ) ||
						normalized.endsWith( 'README.md' )
					) {
						return true;
					}
					return false;
				},
			},
		} ),
	},
	entry() {
		return {
			'checkout-blocks': path.resolve(
				__dirname,
				'src',
				'checkout-blocks',
				'index.js'
			),
		};
	},
	plugins: [
		...( scriptConfig.plugins || [] ).filter( ( plugin ) => {
			const name =
				plugin && plugin.constructor && plugin.constructor.name
					? plugin.constructor.name
					: '';
			return name !== 'DependencyExtractionWebpackPlugin';
		} ),
		! process.env.WP_NO_EXTERNALS &&
			new WooCommerceDependencyExtractionWebpackPlugin(),
	].filter( Boolean ),
};
