/**
 * OneKhusa RTP — Checkout / Cart Blocks payment method registration (redirect gateway).
 */
/* eslint-disable import/no-unresolved, import/no-extraneous-dependencies -- WooCommerce/webpack externals; not installed locally */

import { __ } from '@wordpress/i18n';
import { decodeEntities } from '@wordpress/html-entities';
import { RawHTML } from '@wordpress/element';
import { sanitizeHTML } from '@woocommerce/blocks-checkout';
import { registerPaymentMethod } from '@woocommerce/blocks-registry';
import { getPaymentMethodData } from '@woocommerce/settings';

const settings = getPaymentMethodData( 'onekhusa_rtp', {} );
const decodedTitle =
	decodeEntities( settings.title || '' ) ||
	__( 'OneKhusa (Request To Pay)', 'woocommerce-onekhusa-rtp' );

const icons = Array.isArray( settings.icons ) ? settings.icons : [];
const logoWordmark =
	typeof settings.logo_wordmark === 'string' ? settings.logo_wordmark : '';

let labelCheckoutSrc = logoWordmark !== '' ? logoWordmark : '';
if (
	labelCheckoutSrc === '' &&
	icons.length > 0 &&
	typeof icons[ 0 ].src === 'string'
) {
	labelCheckoutSrc = icons[ 0 ].src;
}

function PaymentDetails() {
	return <RawHTML>{ sanitizeHTML( settings.description || '' ) }</RawHTML>;
}

function GatewayTitle( { components } ) {
	const { PaymentMethodLabel } = components;

	if ( labelCheckoutSrc !== '' ) {
		return (
			<span
				className="wc-block-components-payment-method-label"
				style={ {
					display: 'flex',
					alignItems: 'center',
					columnGap: '10px',
				} }
			>
				<img
					src={ labelCheckoutSrc }
					alt=""
					width={ 98 }
					height={ 21 }
					style={ {
						display: 'block',
						flexShrink: 0,
						maxHeight: '32px',
						width: 'auto',
					} }
				/>
				<PaymentMethodLabel text={ decodedTitle } />
			</span>
		);
	}
	return <PaymentMethodLabel text={ decodedTitle } />;
}

registerPaymentMethod( {
	name: 'onekhusa_rtp',
	label: <GatewayTitle />,
	content: <PaymentDetails />,
	edit: <PaymentDetails />,
	canMakePayment: () => true,
	ariaLabel: decodedTitle,
	...( icons.length > 0 ? { icons } : {} ),
	supports: {
		features: settings.supports ?? [],
	},
} );
