/**
 * WordPress dependencies
 */
import * as React from '@safe-wordpress/element';
import { _x, sprintf } from '@safe-wordpress/i18n';

/**
 * External dependencies
 */
import { trim } from 'lodash';

export type JavaScriptEditorProps = {
	readonly className?: string;
	readonly value: string;
	readonly onChange: ( value: string ) => void;
};

export const JavaScriptEditor = ( {
	value,
	onChange,
}: JavaScriptEditorProps ): JSX.Element => (
	<div className="nab-javascript-editor-sidebar__editor">
		<textarea
			style={ ! value ? { whiteSpace: 'pre-wrap' } : undefined }
			placeholder={ HELP }
			value={ value }
			onChange={ ( ev ) => onChange( ev.target.value ) }
			autoComplete="off"
			autoCorrect="off"
			autoCapitalize="off"
			spellCheck="false"
		/>
		{ !! trim( value ) && ! /\bdone\(\)/.test( trim( value ) ) && (
			<div className="nab-javascript-editor-sidebar__editor-error">
				{ _x( '“done()” not found', 'user', 'nelio-ab-testing' ) }
			</div>
		) }
	</div>
);

const HELP = [
	_x(
		'Write your JavaScript snippet here. Here are some useful tips:',
		'user',
		'nelio-ab-testing'
	),
	'\n',
	'\n- ',
	sprintf(
		/* translators: variable name */
		_x( 'Declare global variable “%s”', 'text', 'nelio-ab-testing' ),
		'abc'
	),
	'\n  window.abc = abc;',
	'\n',
	'\n- ',
	_x( 'Run callback when dom is ready', 'text', 'nelio-ab-testing' ),
	'\n  utils.domReady( callback );',
	'\n',
	'\n- ',
	_x( 'Show variant:', 'text', 'nelio-ab-testing' ),
	'\n  utils.showContent();',
	'\n',
	'\n- ',
	_x( 'Show variant and track events', 'text', 'nelio-ab-testing' ),
	'\n  done();',
].join( '' );
