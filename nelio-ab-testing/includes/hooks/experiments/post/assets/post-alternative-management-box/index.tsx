/**
 * WordPress dependencies
 */
import * as React from '@safe-wordpress/element';
import apiFetch from '@safe-wordpress/api-fetch';
import { PanelRow, Dashicon, Button } from '@safe-wordpress/components';
import { useSelect } from '@safe-wordpress/data';
import { useEffect, useState } from '@safe-wordpress/element';
import { _x, sprintf } from '@safe-wordpress/i18n';

/**
 * External dependencies
 */
import { map } from 'lodash';
import { PostSearcher, ConfirmationDialog } from '@nab/components';
import { STORE_NAME as NAB_DATA } from '@nab/data';
import { STORE_NAME as NAB_EXPERIMENTS } from '@nab/experiments';
import { getLetter } from '@nab/utils';
import type { Alternative, Dict, ExperimentId } from '@nab/types';

/**
 * Internal dependencies
 */
import './style.scss';

export type PostAlternativeManagementBoxProps = Props;

type Props = {
	readonly experimentId: ExperimentId;
	readonly postBeingEdited: number;
	readonly type: string;
};

export const PostAlternativeManagementBox = ( {
	experimentId,
	postBeingEdited,
	type,
}: PostAlternativeManagementBoxProps ): JSX.Element | null => {
	const icon = useIcon( experimentId );
	if ( ! icon ) {
		return null;
	} //end if

	return (
		<>
			<ExperimentName icon={ icon } experimentId={ experimentId } />
			<Alternatives
				experimentId={ experimentId }
				postBeingEdited={ postBeingEdited }
			/>
			<ContentImporter
				postBeingEdited={ postBeingEdited }
				type={ type }
			/>
		</>
	);
};

// ============
// HELPER VIEWS
// ============

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
}: Pick< Props, 'experimentId' | 'postBeingEdited' > ): JSX.Element | null => {
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

const ContentImporter = ( {
	postBeingEdited,
	type,
}: Pick< Props, 'type' | 'postBeingEdited' > ): JSX.Element => {
	const [ isImporting, importContent ] =
		useContentImporter( postBeingEdited );
	const [ settings, doSetSettings ] = useState( DEFAULT_IMPORT_SETTINGS );
	const setSettings = ( attrs: Partial< typeof DEFAULT_IMPORT_SETTINGS > ) =>
		doSetSettings( { ...settings, ...attrs } );

	const { isImportEnabled, isConfirmationDialogVisible, postIdToImportFrom } =
		settings;

	const toggleImport = () =>
		setSettings( { isImportEnabled: ! isImportEnabled } );
	const toggleConfirmationDialog = () =>
		setSettings( {
			isConfirmationDialogVisible: ! isConfirmationDialogVisible,
		} );
	const setSourcePost = ( id: number ) =>
		setSettings( { postIdToImportFrom: id } );

	const importAction = isImporting
		? _x( 'Importing…', 'text', 'nelio-ab-testing' )
		: _x( 'Import', 'command', 'nelio-ab-testing' );

	return (
		<PanelRow className="nab-content-panel">
			<h2 className="nab-content-panel__title">
				{ _x( 'Content', 'text', 'nelio-ab-testing' ) }
			</h2>

			<span className="nab-content-panel__label">
				<Dashicon icon="admin-page" />
				{ isImportEnabled ? (
					_x( 'Import content from:', 'text', 'nelio-ab-testing' )
				) : (
					<Button variant="link" onClick={ toggleImport }>
						{ _x(
							'Import Content',
							'command',
							'nelio-ab-testing'
						) }
					</Button>
				) }
			</span>

			{ isImportEnabled && (
				<>
					<PostSearcher
						value={ postIdToImportFrom }
						className="nab-content-panel__searcher"
						type={ type }
						onChange={ ( v = 0 ) => setSourcePost( v ) }
					/>

					<div className="nab-content-panel__actions">
						<Button variant="link" onClick={ toggleImport }>
							{ _x( 'Cancel', 'command', 'nelio-ab-testing' ) }
						</Button>
						<Button
							variant="secondary"
							disabled={ ! postIdToImportFrom }
							onClick={ toggleConfirmationDialog }
						>
							{ _x( 'Import', 'command', 'nelio-ab-testing' ) }
						</Button>
						<ConfirmationDialog
							title={ _x(
								'Import Content?',
								'text',
								'nelio-ab-testing'
							) }
							text={ _x(
								'This will overwrite the current content.',
								'text',
								'nelio-ab-testing'
							) }
							confirmLabel={ importAction }
							isConfirmEnabled={ ! isImporting }
							isCancelEnabled={ ! isImporting }
							isOpen={ isConfirmationDialogVisible }
							onCancel={ toggleConfirmationDialog }
							onConfirm={ () =>
								importContent( postIdToImportFrom )
							}
						/>
					</div>
				</>
			) }
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
	readonly postId: number;
	readonly name: string;
	readonly link: string;
};

const useAlternatives = ( experimentId: ExperimentId ) =>
	useSelect( ( select ) =>
		map(
			select( NAB_DATA ).getExperiment( experimentId )?.alternatives,
			( alternative, index ): AlternativeSummary => ( {
				index,
				postId: hasPostId( alternative )
					? alternative.attributes.postId
					: 0,
				name: hasName( alternative ) ? alternative.attributes.name : '',
				link: alternative.links.edit,
			} )
		)
	);

const useIcon = ( experimentId: ExperimentId ) =>
	useSelect( ( select ) => {
		const { getExperiment } = select( NAB_DATA );
		const { getExperimentTypes } = select( NAB_EXPERIMENTS );
		const typeName = getExperiment( experimentId )?.type ?? '';
		return getExperimentTypes()[ typeName ]?.icon ?? ( () => <></> );
	} );

const useContentImporter = ( targetPost: number ) => {
	const [ sourcePost, setSourcePost ] = useState( 0 );

	useEffect( () => {
		if ( ! sourcePost ) {
			return;
		} //end if

		void apiFetch( {
			path: `/nab/v1/post/${ sourcePost }/overwrites/${ targetPost }`,
			method: 'PUT',
		} ).finally( () => {
			window.location.reload();
		} );
	}, [ sourcePost ] );

	return [ !! sourcePost, setSourcePost ] as const;
};

// =======
// HELPERS
// =======

const hasPostId = (
	alt: Alternative
): alt is Alternative< { postId: number } > => !! alt.attributes.postId;

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

const DEFAULT_IMPORT_SETTINGS = {
	isImportEnabled: false,
	isConfirmationDialogVisible: false,
	postIdToImportFrom: 0,
};
