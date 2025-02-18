/**
 * WordPress dependencies
 */
import * as React from '@safe-wordpress/element';
import {
	Button,
	TextControl,
	TextareaControl,
} from '@safe-wordpress/components';
import { useDispatch, useSelect } from '@safe-wordpress/data';
import domReady from '@safe-wordpress/dom-ready';
import { render, useState } from '@safe-wordpress/element';
import { _x, sprintf } from '@safe-wordpress/i18n';
import { MediaUpload } from '@safe-wordpress/media-utils';

/**
 * External dependencies
 */
import classnames from 'classnames';
import { store as NAB_DATA } from '@nab/data';
import { FancyIcon, Tooltip } from '@nab/components';
import type { Dict, Maybe, MediaId, ProductId, Url } from '@nab/types';

/**
 * Internal dependencies
 */
import './style.scss';

type Settings = RegularSettings | VariableSettings;

type RegularSettings = {
	readonly type: 'regular';
	readonly originalPrice: string;
	readonly regularPrice: string;
	readonly salePrice: string;
};

type VariableSettings = {
	readonly type: 'variable';
	readonly isPriceTestingEnabled: boolean;
	readonly variations: ReadonlyArray< VariationData >;
};

type VariationData = {
	readonly id: ProductId;
	readonly name: string;
	readonly imageId: MediaId;
	readonly originalPrice: string;
	readonly regularPrice: string;
	readonly salePrice: string;
	readonly description: string;
};

export function initProductDataMetabox( settings: Settings ): void {
	domReady( () => {
		const root = document.getElementById( 'nab-product-data-root' );
		if ( root ) {
			render(
				settings.type === 'regular' ? (
					<RegularProduct { ...settings } />
				) : (
					<VariableProduct { ...settings } />
				),
				root
			);
		} //end if
	} );
} //end initProductDataMetabox()

// ============
// HELPER VIEWS
// ============

const RegularProduct = ( props: RegularSettings ): JSX.Element => {
	const { originalPrice } = props;
	const [ regularPrice, setRegularPrice ] = useState( props.regularPrice );
	const [ salePrice, setSalePrice ] = useState( props.salePrice );
	return (
		<div className="nab-product-data">
			<Pricing
				originalPrice={ originalPrice }
				regularPrice={ regularPrice }
				salePrice={ salePrice }
				onRegularPriceChange={ setRegularPrice }
				onSalePriceChange={ setSalePrice }
			/>
			<Hidden name="nab_regular_price" value={ regularPrice } />
			<Hidden name="nab_sale_price" value={ salePrice } />
		</div>
	);
};

const VariableProduct = ( props: VariableSettings ): JSX.Element => {
	return (
		<div className="nab-product-data">
			{ props.variations.map( ( data ) => (
				<Variation
					key={ data.id }
					isPriceTestingEnabled={ props.isPriceTestingEnabled }
					data={ data }
				/>
			) ) }
		</div>
	);
};

const Variation = ( {
	data,
	isPriceTestingEnabled,
}: {
	readonly data: VariationData;
	readonly isPriceTestingEnabled: boolean;
} ): JSX.Element => {
	const { id, name, originalPrice } = data;
	const [ imageId, setImageId ] = useState( data.imageId );
	const [ regularPrice, setRegularPrice ] = useState( data.regularPrice );
	const [ salePrice, setSalePrice ] = useState( data.salePrice );
	const [ description, setDescription ] = useState( data.description );
	return (
		<div className="nab-product-data__variation">
			<div className="nab-product-data__variation-name">
				<strong>#{ id }</strong> { name }
			</div>
			<div
				className={ classnames( {
					'nab-product-data__variation-data': true,
					'nab-product-data__variation-data--has-pricing':
						isPriceTestingEnabled,
				} ) }
			>
				<FeaturedImage
					imageId={ imageId }
					onImageIdChange={ setImageId }
				/>
				{ isPriceTestingEnabled && (
					<Pricing
						originalPrice={ originalPrice }
						regularPrice={ regularPrice }
						salePrice={ salePrice }
						onRegularPriceChange={ setRegularPrice }
						onSalePriceChange={ setSalePrice }
					/>
				) }
				<div className="nab-product-data__variation-description">
					<TextareaControl
						label={ _x(
							'Description',
							'text',
							'nelio-ab-testing'
						) }
						value={ description }
						onChange={ setDescription }
					/>
				</div>
				<Hidden
					name={ `nab_variation_data[${ id }]` }
					value={ {
						imageId,
						regularPrice,
						salePrice,
						description,
					} }
				/>
			</div>
		</div>
	);
};

type PricingProps = {
	readonly originalPrice: string;
	readonly regularPrice: string;
	readonly salePrice: string;
	readonly onRegularPriceChange: ( price: string ) => void;
	readonly onSalePriceChange: ( price: string ) => void;
};

const Pricing = ( {
	originalPrice,
	regularPrice,
	salePrice,
	onRegularPriceChange,
	onSalePriceChange,
}: PricingProps ): JSX.Element => {
	const sep = useDecimalSeparator();
	const currency = useCurrency();

	const price2wc = ( p: string ) => p.replace( '.', sep );
	const wc2price = ( p: string ) =>
		p
			.replace( sep, '.' )
			.replace( /[^0-9.]/g, '' )
			.replace( /\./g, '#' )
			.replace( '#', '.' )
			.replace( /#/g, '' );

	return (
		<div className="nab-product-data__pricing">
			<TextControl
				label={ sprintf(
					/* translators: currency */
					_x( 'Regular price (%s)', 'text', 'nelio-ab-testing' ),
					currency
				) }
				value={ price2wc( regularPrice ) }
				placeholder={ price2wc( originalPrice ) }
				onChange={ ( v ) => onRegularPriceChange( wc2price( v ) ) }
			/>
			<TextControl
				label={ sprintf(
					/* translators: currency */
					_x( 'Sale price (%s)', 'text', 'nelio-ab-testing' ),
					currency
				) }
				value={ price2wc( salePrice ) }
				onChange={ ( v ) => onSalePriceChange( wc2price( v ) ) }
			/>
		</div>
	);
};

type FeaturedImageProps = {
	readonly imageId: MediaId;
	readonly onImageIdChange: ( value: MediaId ) => void;
};

const FeaturedImage = ( {
	imageId,
	onImageIdChange,
}: FeaturedImageProps ): JSX.Element => {
	const imageUrl = useImageUrl( imageId );
	const { receiveMediaUrl } = useDispatch( NAB_DATA );
	return (
		<div className="nab-product-data__variation-image-wrapper">
			{ !! imageId ? (
				<Tooltip
					text={ _x(
						'Click to remove featured image',
						'user',
						'nelio-ab-testing'
					) }
				>
					<Button
						className="nab-product-data__variation-image-action nab-product-data__variation-image-action--is-remove"
						onClick={ () => onImageIdChange( 0 ) }
					>
						<Image src={ imageUrl } />
					</Button>
				</Tooltip>
			) : (
				<MediaUpload
					title={ _x(
						'Alternative Featured Image',
						'text',
						'nelio-ab-testing'
					) }
					allowedTypes={ [ 'image' ] }
					value={ imageId }
					onSelect={ ( { id, url } ) => {
						if ( 'string' !== typeof url ) {
							return;
						} //end if
						void receiveMediaUrl( id as MediaId, url as Url );
						onImageIdChange( id as MediaId );
					} }
					render={ ( {
						// eslint-disable-next-line @typescript-eslint/unbound-method
						open,
					} ) => (
						<Tooltip
							text={ _x(
								'Click to set a featured image',
								'user',
								'nelio-ab-testing'
							) }
						>
							<Button
								className="nab-product-data__variation-image-action nab-product-data__variation-image-action--is-set"
								onClick={ open }
							>
								<Image src={ imageUrl } />
							</Button>
						</Tooltip>
					) }
				/>
			) }
		</div>
	);
};

const Image = ( { src }: { src?: string } ) =>
	!! src ? (
		<img
			className="nab-product-data__variation-image"
			alt={ _x( 'Featured Image', 'text', 'nelio-ab-testing' ) }
			src={ src }
		/>
	) : (
		<FancyIcon className="nab-product-data__variation-image" />
	);

type HiddenProps = {
	readonly name: string;
	readonly value: string | Dict< number | string >;
};

const Hidden = ( { name, value }: HiddenProps ): JSX.Element => {
	if ( typeof value === 'string' ) {
		return <input type="hidden" name={ name } value={ value } />;
	} //end if

	return (
		<>
			{ Object.keys( value ).map( ( key ) => (
				<input
					type="hidden"
					key={ key }
					name={ `${ name }[${ key }]` }
					value={ value[ key ] }
				/>
			) ) }
		</>
	);
};

// =====
// HOOKS
// =====

const useDecimalSeparator = () =>
	useSelect( ( select ) =>
		select( NAB_DATA ).getECommerceSetting(
			'woocommerce',
			'decimalSeparator'
		)
	);

const useCurrency = () =>
	useSelect( ( select ) =>
		select( NAB_DATA ).getECommerceSetting(
			'woocommerce',
			'currencySymbol'
		)
	);

const useImageUrl = ( id: MediaId ): Maybe< string > =>
	useSelect( ( select ) => select( NAB_DATA ).getMediaUrl( id ) );
