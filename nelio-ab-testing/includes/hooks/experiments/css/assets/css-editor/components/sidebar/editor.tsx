/**
 * WordPress dependencies
 */
import * as React from '@safe-wordpress/element';
import { TextareaControl } from '@safe-wordpress/components';

export type CssEditorProps = {
	readonly className?: string;
	readonly value: string;
	readonly onChange: ( value: string ) => void;
};

export const CssEditor = ( {
	className,
	value,
	onChange,
}: CssEditorProps ): JSX.Element => (
	<TextareaControl
		className={ className }
		value={ value }
		onChange={ onChange }
		autoComplete="off"
		autoCorrect="off"
		autoCapitalize="off"
		spellCheck="false"
	/>
);
