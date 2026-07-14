/**
 * AT View Table — Client-side column sorting.
 *
 * @package ATViewTable
 */

( function () {
	'use strict';

	var tables = document.querySelectorAll( '.atvt-table' );

	tables.forEach( function ( table ) {
		var headers = table.querySelectorAll( 'thead th.atvt-sortable' );

		headers.forEach( function ( th, index ) {
			th.addEventListener( 'click', function () {
				sortTable( table, index, th );
			} );

			// Keyboard support: Enter or Space to sort.
			th.addEventListener( 'keydown', function ( e ) {
				if ( e.key === 'Enter' || e.key === ' ' ) {
					e.preventDefault();
					sortTable( table, index, th );
				}
			} );
		} );
	} );

	/**
	 * Sort a table by the given column index.
	 *
	 * @param {HTMLElement} table   The table element.
	 * @param {number}      col     Column index.
	 * @param {HTMLElement} header  The clicked header cell.
	 */
	function sortTable( table, col, header ) {
		var tbody   = table.querySelector( 'tbody' );
		var rows    = Array.prototype.slice.call( tbody.querySelectorAll( 'tr' ) );
		var headers = table.querySelectorAll( 'thead th.atvt-sortable' );
		var current = header.getAttribute( 'data-sort' ) || '';
		var dir     = '';

		if ( 'asc' === current ) {
			dir = 'desc';
		} else {
			dir = 'asc';
		}

		// Remove sort indicators from all headers.
		headers.forEach( function ( h ) {
			h.removeAttribute( 'data-sort' );
		} );

		// Set indicator on the clicked header.
		header.setAttribute( 'data-sort', dir );

		rows.sort( function ( a, b ) {
			var cellA = a.cells[ col ] ? a.cells[ col ].textContent.trim() : '';
			var cellB = b.cells[ col ] ? b.cells[ col ].textContent.trim() : '';

			return compareValues( cellA, cellB, dir );
		} );

		// Re-append rows in sorted order.
		rows.forEach( function ( row ) {
			tbody.appendChild( row );
		} );
	}

	/**
	 * Compare two cell values for sorting.
	 *
	 * @param {string} a     First value.
	 * @param {string} b     Second value.
	 * @param {string} dir   Sort direction: 'asc' or 'desc'.
	 * @return {number} Comparison result.
	 */
	function compareValues( a, b, dir ) {
		var numA = parseFloat( a );
		var numB = parseFloat( b );
		var isNum = ! isNaN( numA ) && ! isNaN( numB ) && numA.toString() === a && numB.toString() === b;

		var result;

		if ( isNum ) {
			result = numA - numB;
		} else {
			result = a.localeCompare( b, undefined, { sensitivity: 'base' } );
		}

		return 'desc' === dir ? -result : result;
	}
} )();
