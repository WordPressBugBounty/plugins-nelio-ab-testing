/**
 * WordPress dependencies
 */
import * as React from '@safe-wordpress/element';
import { Button, Dashicon } from '@safe-wordpress/components';
import { _x } from '@safe-wordpress/i18n';

/**
 * External dependencies
 */
import classnames from 'classnames';
import { usePageAttribute } from '@nab/data';

export const FooterActions = (): JSX.Element => {
	const [ areVisible, setVisibility ] = usePageAttribute(
		'css-preview/areControlsVisible',
		true
	);
	const [ resolution, setResolution ] = usePageAttribute(
		'css-preview/activeResolution',
		'desktop'
	);

	return (
		<div className="nab-css-editor-sidebar__footer-actions">
			<div
				className={ classnames( {
					'nab-css-editor-sidebar__visibility-toggle': true,
					'nab-css-editor-sidebar__visibility-toggle--hide':
						!! areVisible,
					'nab-css-editor-sidebar__visibility-toggle--show':
						! areVisible,
				} ) }
			>
				<Button
					variant="link"
					onClick={ () => setVisibility( ! areVisible ) }
				>
					{ areVisible ? (
						<>
							<Dashicon icon="admin-collapse" />
							<span className="nab-css-editor-sidebar__visibility-toggle-label">
								{ _x(
									'Hide Controls',
									'command',
									'nelio-ab-testing'
								) }
							</span>
						</>
					) : (
						<Dashicon icon="admin-collapse" />
					) }
				</Button>
			</div>

			<div className="nab-css-editor-sidebar__resolutions">
				<div
					className={ classnames( {
						'nab-css-editor-sidebar__resolution': true,
						'nab-css-editor-sidebar__resolution--is-active':
							'desktop' === resolution,
					} ) }
				>
					<Button
						variant="link"
						onClick={ () => setResolution( 'desktop' ) }
					>
						<Dashicon icon="desktop" />
						<span className="screen-reader-text">
							{ _x(
								'Enter desktop preview mode',
								'command',
								'nelio-ab-testing'
							) }
						</span>
					</Button>
				</div>

				<div
					className={ classnames( {
						'nab-css-editor-sidebar__resolution': true,
						'nab-css-editor-sidebar__resolution--is-active':
							'tablet' === resolution,
					} ) }
				>
					<Button
						variant="link"
						onClick={ () => setResolution( 'tablet' ) }
					>
						<Dashicon icon="tablet" />
						<span className="screen-reader-text">
							{ _x(
								'Enter tablet preview mode',
								'command',
								'nelio-ab-testing'
							) }
						</span>
					</Button>
				</div>

				<div
					className={ classnames( {
						'nab-css-editor-sidebar__resolution': true,
						'nab-css-editor-sidebar__resolution--is-active':
							'mobile' === resolution,
					} ) }
				>
					<Button
						variant="link"
						onClick={ () => setResolution( 'mobile' ) }
					>
						<Dashicon icon="smartphone" />
						<span className="screen-reader-text">
							{ _x(
								'Enter mobile preview mode',
								'command',
								'nelio-ab-testing'
							) }
						</span>
					</Button>
				</div>
			</div>
		</div>
	);
};
