/**
 * WordPress dependencies
 */
import * as React from '@safe-wordpress/element';
import { _x, sprintf } from '@safe-wordpress/i18n';

/**
 * External dependencies
 */
import { CodeEditor } from '@nab/components';
import { CompletionContext } from '@codemirror/autocomplete';
import type { TSESTree, TSESLint } from '@typescript-eslint/utils';

export type JavaScriptEditorProps = {
	readonly className?: string;
	readonly value: string;
	readonly onChange: ( value: string ) => void;
	readonly placeholder?: string;
};

export const JavaScriptEditor = ( {
	value,
	onChange,
}: JavaScriptEditorProps ): JSX.Element => (
	<CodeEditor
		className="nab-javascript-editor-sidebar__editor"
		language="javascript"
		placeholder={ HELP }
		value={ value }
		onChange={ onChange }
		config={ {
			completions: [ customCompletions ],
			globals: [ 'utils', 'done' ],
			rules: {
				'detect-done': { level: 'error', module: detectDoneRule },
			},
		} }
	/>
);

// =======
// HELPERS
// =======

const detectDoneRule: TSESLint.RuleModule< 'missingDone', [] > = {
	meta: {
		type: 'problem',
		docs: {
			description: _x(
				'Ensure done() is called',
				'user',
				'nelio-ab-testing'
			),
		},
		messages: {
			missingDone: _x(
				'The function done() must be called at least once.',
				'text',
				'nelio-ab-testing'
			),
		},
		schema: [], // No schema, no options
	},
	defaultOptions: [],
	create( context ) {
		let hasDoneCall = false;

		return {
			CallExpression( node: TSESTree.CallExpression ) {
				if (
					// eslint-disable-next-line @typescript-eslint/no-unsafe-enum-comparison
					node.callee.type === 'Identifier' &&
					node.callee.name === 'done'
				) {
					hasDoneCall = true;
				} //end if
			},
			'Program:exit'() {
				const hasSourceCode = !! context.sourceCode.getText().trim();
				if ( hasSourceCode && ! hasDoneCall ) {
					context.report( {
						loc: { line: 1, column: 0 },
						messageId: 'missingDone',
					} );
				}
			},
		};
	},
};

function customCompletions( context: CompletionContext ) {
	const utils = context.matchBefore( /utils\.\w*/ );
	if ( utils ) {
		return {
			from: utils.text.replace( /\w*$/, '' ).length + utils.from,
			options: [
				{
					label: 'domReady',
					type: 'function',
					apply: 'domReady(() => {\n});',
					detail: '(callback:Function) => void',
					info: _x(
						'Runs callback when DOM is ready. You may need to use the function if you want to target certain elements on the page, since this code may run before any elements are loaded in the DOM.',
						'text',
						'nelio-ab-testing'
					),
				},
				{
					label: 'showContent',
					type: 'function',
					apply: 'showContent();',
					detail: '() => void',
					info: _x(
						'Shows the variant right away, but doesn’t start tracking yet. To enable tracking, you should call “done” instead.',
						'text',
						'nelio-ab-testing'
					),
				},
			],
		};
	} //end if

	const word = context.matchBefore( /\w*/ );
	if ( ! word ) {
		return null;
	} //end if

	if ( word.from === word.to && ! context.explicit ) {
		return null;
	} //end if

	return {
		from: word.from,
		options: [
			{
				label: 'done',
				type: 'function',
				apply: 'done();',
				detail: '() => void',
				info: _x(
					'Shows the variant and enables event tracking.',
					'text',
					'nelio-ab-testing'
				),
			},
			{
				label: 'utils',
				type: 'variable',
				apply: 'utils',
				detail: 'Object',
				info: _x(
					'Contains several helper functions.',
					'text',
					'nelio-ab-testing'
				),
			},
		],
	};
} //end customCompletions()

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
