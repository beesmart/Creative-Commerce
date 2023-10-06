const defaultConfig = require( '@wordpress/scripts/config/webpack.config' );
const path = require( 'path' );

module.exports = {
	...defaultConfig,
	entry: {
		'wcf-settings': './assets/js/wcf-settings.js',
		'wcf-attribute-color': './assets/js/wcf-attribute-color.js',
		'wcf-term-image': './assets/js/wcf-term-image.js',
		'wcf-frontend': './assets/js/wcf-frontend.js',
		'wcf-product-table': './assets/js/integrations/product-table.js',
		'wcf-restaurant-ordering': './assets/js/integrations/restaurant-ordering.js',
		'wcf-elementor': './assets/js/integrations/elementor.js',
		'wcf-wc-shortcodes': './assets/js/integrations/wc-shortcodes.js',
		'wcf-admin-editor': './assets/js/wcf-admin-editor.js',
		'wcf-uncode': './assets/js/integrations/uncode.js',
		'wcf-divi': './assets/js/integrations/divi.js',
	},
	output: {
		...defaultConfig.output,
		path: path.resolve( process.cwd(), 'assets/build' ),
		chunkFilename: '[name].js?id=[chunkhash]',
	},
};
