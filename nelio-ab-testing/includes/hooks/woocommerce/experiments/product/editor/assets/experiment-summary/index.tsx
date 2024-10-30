/**
 * WordPress dependencies
 */
import * as React from '@safe-wordpress/element';
import { PanelRow } from '@safe-wordpress/components';
import { useSelect } from '@safe-wordpress/data';
import domReady from '@safe-wordpress/dom-ready';
import { render } from '@safe-wordpress/element';
import { _x, sprintf } from '@safe-wordpress/i18n';

/**
 * External dependencies
 */
import { map } from 'lodash';
import { store as NAB_DATA } from '@nab/data';
import { store as NAB_EXPERIMENTS } from '@nab/experiments';
import { getLetter } from '@nab/utils';
import type { Alternative, Dict, ExperimentId, ProductId } from '@nab/types';

/**
 * Internal dependencies
 */
import './style.scss';

type Settings = {
	readonly experimentId: ExperimentId;
	readonly postBeingEdited: number;
};

export function initExperimentSummary( settings: Settings ): void {
	domReady( () => {
		const root = document.getElementById( 'nab-experiment-summary' );
		if ( root ) {
			render( <ExperimentSummary { ...settings } />, root );
		} //end if
	} );
} //end initExperimentSummary()

// ============
// HELPER VIEWS
// ============

const ExperimentSummary = ( {
	experimentId,
	postBeingEdited,
}: Settings ): JSX.Element | null => {
	const icon = useIcon( experimentId );
	const isLoading = useIsLoading( experimentId );

	if ( isLoading ) {
		return <span className="spinner is-active"></span>;
	} //end if

	return (
		<>
			<ExperimentName icon={ icon } experimentId={ experimentId } />
			<Alternatives
				experimentId={ experimentId }
				postBeingEdited={ postBeingEdited }
			/>
		</>
	);
};

type ExperimentNameProps = {
	readonly icon: ( props?: Dict ) => JSX.Element;
	readonly experimentId: ExperimentId;
};

const ExperimentName = ( {
	icon: Icon,
	experimentId,
}: ExperimentNameProps ): JSX.Element => {
	const experimentName = useExperimentName( experimentId );
	const experimentUrl = useExperimentUrl( experimentId );
	return (
		<PanelRow className="nab-test-panel">
			<span className="nab-test-panel__icon">
				<Icon />
			</span>
			<a className="nab-test-panel__name" href={ experimentUrl }>
				{ experimentName }
			</a>
		</PanelRow>
	);
};

const Alternatives = ( {
	experimentId,
	postBeingEdited,
}: Settings ): JSX.Element | null => {
	const alternatives = useAlternatives( experimentId );

	if ( ! alternatives ) {
		return null;
	} //end if

	return (
		<PanelRow className="nab-variants-panel">
			<h2 className="nab-variants-panel__title">
				{ _x( 'Variants', 'text', 'nelio-ab-testing' ) }
			</h2>
			{ alternatives.map( ( { name, link, postId, index } ) => (
				<div className="nab-alternative" key={ postId }>
					<span className="nab-alternative__letter">
						{ getLetter( index ) }
					</span>
					<span className="nab-alternative__name">
						{ postBeingEdited !== postId ? (
							<a href={ link }>
								{ getNameOfAlternative( name, index ) }
							</a>
						) : (
							<strong>
								{ getNameOfAlternative( name, index ) }
							</strong>
						) }
					</span>
				</div>
			) ) }
		</PanelRow>
	);
};

// =====
// HOOKS
// =====

const useExperimentName = ( experimentId: ExperimentId ) =>
	useSelect(
		( select ) =>
			select( NAB_DATA ).getExperiment( experimentId )?.name ||
			_x( 'Unnamed Test', 'text', 'nelio-ab-testing' )
	);

const useExperimentUrl = ( experimentId: ExperimentId ) =>
	useSelect(
		( select ) =>
			select( NAB_DATA ).getExperiment( experimentId )?.links.edit || ''
	);

type AlternativeSummary = {
	readonly index: number;
	readonly postId: ProductId;
	readonly name: string;
	readonly link: string;
};

const useAlternatives = ( experimentId: ExperimentId ) =>
	useSelect( ( select ) =>
		map(
			select( NAB_DATA ).getExperiment( experimentId )?.alternatives,
			( alternative, index ): AlternativeSummary => ( {
				index,
				postId: hasProductId( alternative )
					? alternative.attributes.postId
					: 0,
				name: hasName( alternative ) ? alternative.attributes.name : '',
				link: alternative.links.edit,
			} )
		)
	);

const useIsLoading = ( experimentId: ExperimentId ) =>
	useSelect(
		( select ) =>
			! select( NAB_DATA ).hasFinishedResolution( 'getExperiment', [
				experimentId,
			] )
	);

const useIcon = ( experimentId: ExperimentId ) =>
	useSelect( ( select ) => {
		const { getExperiment } = select( NAB_DATA );
		const { getExperimentTypes } = select( NAB_EXPERIMENTS );
		const typeName = getExperiment( experimentId )?.type ?? '';
		return getExperimentTypes()[ typeName ]?.icon ?? ( () => <></> );
	} );

// =======
// HELPERS
// =======

const hasProductId = (
	alt: Alternative
): alt is Alternative< { postId: ProductId } > => !! alt.attributes.postId;

const hasName = ( alt: Alternative ): alt is Alternative< { name: string } > =>
	!! alt.attributes.name;

const getNameOfAlternative = ( name: string, index: number ): string => {
	if ( name ) {
		return name;
	} //end if

	if ( 0 === index ) {
		return _x( 'Control Version', 'text', 'nelio-ab-testing' );
	} //end if

	return sprintf(
		/* translators: a letter, such as A, B, or C */
		_x( 'Variant %s', 'text', 'nelio-ab-testing' ),
		getLetter( index )
	);
};
