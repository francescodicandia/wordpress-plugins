( function () {
	var el = wp.element.createElement;
	var useBlockProps = wp.blockEditor.useBlockProps;

	wp.blocks.registerBlockType( 'barber-booking/booking-form', {
		edit: function () {
			var blockProps = useBlockProps();
			return el(
				'div',
				blockProps,
				el( 'p', { style: { fontWeight: 'bold' } }, 'Barber Booking Form' ),
				el(
					'p',
					{ style: { fontSize: '12px', color: '#666' } },
					'Il form di prenotazione verrà renderizzato qui nella pagina pubblica.'
				)
			);
		},
	} );
} )();
