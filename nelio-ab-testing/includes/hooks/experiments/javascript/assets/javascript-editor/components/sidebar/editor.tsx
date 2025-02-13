/**
 * WordPress dependencies
 */
import * as React from '@safe-wordpress/element';
import { _x, sprintf } from '@safe-wordpress/i18n';

/**
 * External dependencies
 */
import { CodeEditor } from '@nab/components';

export type JavaScriptEditorProps = {
	readonly className?: string;
	readonly value: string;
	readonly onChange: ( value: string ) => void;
	readonly placeholder?: string;
	readonly globals: ReadonlyArray< string >;
};

export const JavaScriptEditor = ( {
	value,
	onChange,
	globals,
}: JavaScriptEditorProps ): JSX.Element => (
	<CodeEditor
		className="nab-javascript-editor-sidebar__editor"
		language="javascript"
		placeholder={ HELP }
		value={ value }
		onChange={ onChange }
		config={ { globals: [ ...globals, 'utils', 'done' ] } }
	/>
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
