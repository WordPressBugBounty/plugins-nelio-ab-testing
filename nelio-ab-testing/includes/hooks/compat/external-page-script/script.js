( async function () {
	const me = document.currentScript;
	if ( ! me ) {
		return;
	}

	const bgcolor = me.getAttribute( 'data-overlay-color' ) || '#fff';
	const pairs = {
		background: `var(--nab-overlay,${ bgcolor })`,
		display: 'block',
		content: '""',
		position: 'fixed',
		top: 0,
		left: 0,
		width: '100vw',
		height: '120vh',
		'pointer-events': 'none',
		'z-index': '9999999999',
	};
	const css = Object.keys( pairs )
		.map( ( k ) => `\t${ k }: ${ pairs[ k ] } !important;` )
		.join( '\n' );

	const style = document.createElement( 'style' );
	style.id = 'nelio-ab-testing-overlay';
	style.textContent = `body:not(.nab-done)::before,\nbody:not(.nab-done)::after{\n${ css }}`;
	me.after( style );

	try {
		const url = new URL( window.location.href );
		url.searchParams.set( 'nab-external-page-script', '' );
		const resp = await fetch( url );
		const text = await resp.json();

		if ( ! text.includes( 'nabKeepOverlayInExternalPageScript' ) ) {
			style.parentNode?.removeChild( style );
		}

		const div = document.createElement( 'div' );
		div.innerHTML = text;

		let marker = me;
		for ( const c of div.children ) {
			const el = document.createElement( c.nodeName );
			[ ...c.getAttributeNames() ].forEach( ( a ) => {
				el.setAttribute( a, c.getAttribute( a ) ?? '' );
			} );
			el.innerHTML = c.innerHTML;
			await new Promise( ( resolve ) => {
				if ( el.nodeName === 'SCRIPT' && el.src ) {
					el.onload = () => resolve();
					el.onerror = () => {
						console.log( 'Error loading:', el.src );
						resolve();
					};
				} else {
					resolve();
				}
				marker.after( el );
			} );
			marker = el;
		}
	} catch ( _ ) {
		style.parentNode?.removeChild( style );
	}
} )();
