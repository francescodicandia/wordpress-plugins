/**
 * AT View Table — Client-side column sorting and pagination.
 *
 * @package ATViewTable
 */

( function () {
	'use strict';

	var tableStates = {};

	/**
	 * Initialise sorting and pagination for each table on the page.
	 */
	function init() {
		var tables = document.querySelectorAll( '.atvt-table' );

		tables.forEach( function ( table, index ) {
			var wrap = table.closest( '.atvt-table-wrap' );
			if ( ! wrap ) {
				return;
			}

			var pageSize = parseInt( wrap.getAttribute( 'data-page-size' ) ) || 0;
			var tbody    = table.querySelector( 'tbody' );
			var rows     = tbody.querySelectorAll( 'tr' );

			if ( rows.length === 0 ) {
				return;
			}

			var totalPages = pageSize > 0 ? Math.ceil( rows.length / pageSize ) : 1;
			var stateId    = 'atvt-' + index;

			tableStates[ stateId ] = {
				currentPage: 1,
				pageSize: pageSize,
				totalPages: totalPages,
				wrap: wrap,
				table: table,
				tbody: tbody
			};

			setupSorting( table, stateId );
			setupPagination( wrap, stateId );

			if ( pageSize > 0 && totalPages > 1 ) {
				showPage( stateId );
			}
		} );
	}

	/**
	 * Attach click / keyboard listeners to sortable headers.
	 *
	 * @param {HTMLElement} table   The table element.
	 * @param {string}      stateId State key.
	 */
	function setupSorting( table, stateId ) {
		var headers = table.querySelectorAll( 'thead th.atvt-sortable' );

		headers.forEach( function ( th, col ) {
			th.addEventListener( 'click', function () {
				sortTable( stateId, col, th );
			} );

			th.addEventListener( 'keydown', function ( e ) {
				if ( e.key === 'Enter' || e.key === ' ' ) {
					e.preventDefault();
					sortTable( stateId, col, th );
				}
			} );
		} );
	}

	/**
	 * Attach click listeners to pagination buttons.
	 *
	 * @param {HTMLElement} wrap    Table wrapper element.
	 * @param {string}      stateId State key.
	 */
	function setupPagination( wrap, stateId ) {
		var prevBtn = wrap.querySelector( '.atvt-prev' );
		var nextBtn = wrap.querySelector( '.atvt-next' );

		if ( prevBtn ) {
			prevBtn.addEventListener( 'click', function () {
				changePage( stateId, -1 );
			} );
		}

		if ( nextBtn ) {
			nextBtn.addEventListener( 'click', function () {
				changePage( stateId, 1 );
			} );
		}
	}

	/**
	 * Show rows for the current page and hide others.
	 *
	 * @param {string} stateId State key.
	 */
	function showPage( stateId ) {
		var state = tableStates[ stateId ];
		if ( ! state ) {
			return;
		}

		var rows        = state.tbody.querySelectorAll( 'tr' );
		var currentPage = state.currentPage;
		var pageSize    = state.pageSize;

		rows.forEach( function ( row, i ) {
			var page = Math.floor( i / pageSize ) + 1;
			row.style.display = ( page === currentPage ) ? '' : 'none';
		} );

		updatePaginationControls( state );
	}

	/**
	 * Update Previous / Next button states and page info.
	 *
	 * @param {Object} state Table state object.
	 */
	function updatePaginationControls( state ) {
		var wrap     = state.wrap;
		var info     = wrap.querySelector( '.atvt-current-page' );
		var prevBtn  = wrap.querySelector( '.atvt-prev' );
		var nextBtn  = wrap.querySelector( '.atvt-next' );
		var cp       = state.currentPage;

		if ( info ) {
			info.textContent = cp;
		}

		if ( prevBtn ) {
			prevBtn.disabled = cp <= 1;
		}

		if ( nextBtn ) {
			nextBtn.disabled = cp >= state.totalPages;
		}
	}

	/**
	 * Move to the next or previous page.
	 *
	 * @param {string} stateId State key.
	 * @param {number} delta   +1 for next, -1 for previous.
	 */
	function changePage( stateId, delta ) {
		var state = tableStates[ stateId ];
		if ( ! state ) {
			return;
		}

		var newPage = state.currentPage + delta;
		if ( newPage < 1 || newPage > state.totalPages ) {
			return;
		}

		state.currentPage = newPage;
		showPage( stateId );
	}

	/**
	 * Sort rows by a column, then reset to page 1.
	 *
	 * @param {string}      stateId State key.
	 * @param {number}      col     Column index.
	 * @param {HTMLElement} header  The clicked header cell.
	 */
	function sortTable( stateId, col, header ) {
		var state   = tableStates[ stateId ];
		if ( ! state ) {
			return;
		}

		var rows    = Array.prototype.slice.call( state.tbody.querySelectorAll( 'tr' ) );
		var headers = state.table.querySelectorAll( 'thead th.atvt-sortable' );
		var current = header.getAttribute( 'data-sort' ) || '';
		var dir     = ( 'asc' === current ) ? 'desc' : 'asc';

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
			state.tbody.appendChild( row );
		} );

		// Reset to page 1 after sorting.
		state.currentPage = 1;

		if ( state.pageSize > 0 && state.totalPages > 1 ) {
			showPage( stateId );
		}
	}

	/**
	 * Compare two cell values for sorting.
	 *
	 * @param {string} a   First value.
	 * @param {string} b   Second value.
	 * @param {string} dir Sort direction: 'asc' or 'desc'.
	 * @return {number} Comparison result.
	 */
	function compareValues( a, b, dir ) {
		var numA  = parseFloat( a );
		var numB  = parseFloat( b );
		var isNum = ! isNaN( numA ) && ! isNaN( numB ) &&
			numA.toString() === a && numB.toString() === b;

		var result = isNum ? ( numA - numB ) : a.localeCompare( b, undefined, { sensitivity: 'base' } );
		return 'desc' === dir ? -result : result;
	}

	// Boot.
	init();
} )();
