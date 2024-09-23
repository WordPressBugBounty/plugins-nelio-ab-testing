<?php

esc_html_e( 'We use Nelio A/B Testing to split test our website. Split Testing (or A/B Testing) is a marketing technique used to test different variants of a website with the aim of identifying which variant is better at converting visitors.', 'nelio-ab-testing' );

echo "\n\n";

esc_html_e( 'Nelio A/B Testing uses cookies to run split tests and track the actions you take while visiting our website. These cookies do not store any personal information about you and can not be used to identify you in any way.', 'nelio-ab-testing' );

echo ' ';

printf(
	/* translators: 1 -> open anchor tag, 2 -> close anchor tag */
	esc_html_x( 'Whenever you perform an action that is relevant to a running test, such as visiting a certain page, clicking on an element, or submitting a form, this event is stored in Nelio’s cloud in compliance to %1$sNelio A/B Testing’s Terms and Conditions%2$s.', 'nelio-ab-testing' ), // phpcs:ignore
	sprintf( '<a href="%s">', esc_url( _x( 'https://neliosoftware.com/legal-information/nelio-ab-testing-terms-conditions/', 'text', 'nelio-ab-testing' ) ) ),
	'</a>'
);

echo ' ';

esc_html_e( 'Please notice Nelio does not store any personal data that can be related to you, as all collected data is completely anonymous.', 'nelio-ab-testing' );
