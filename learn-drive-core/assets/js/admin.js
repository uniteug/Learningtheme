/**
 * Administration : propositions de réponses (ajout, retrait, format).
 */
( function () {
	const box = document.querySelector( '[data-ld-answers]' );
	if ( ! box ) {
		return;
	}
	const list = box.querySelector( '[data-ld-list]' );
	const format = document.querySelector( '[data-ld-format]' );
	const numeric = document.querySelector( '[data-ld-numeric]' );
	const hotspot = document.querySelector( '[data-ld-hotspot]' );
	const i18n = window.learnDriveAdmin || {};

	function renumber() {
		list.querySelectorAll( 'li' ).forEach( ( row, index ) => {
			row.querySelectorAll( 'input' ).forEach( ( input ) => {
				input.name = input.name.replace( /\[\d+\]/, `[${ index }]` );
			} );
		} );
	}

	function addRow( text = '' ) {
		if ( list.children.length >= ( format.value === 'order' ? 8 : 6 ) ) {
			return;
		}
		const row = document.createElement( 'li' );
		row.className = 'ld-answer-row';
		row.innerHTML = `<input type="text" class="widefat" name="ld_answers[0][text]" aria-label="${ i18n.answer }"> <input type="text" class="widefat ld-answer-row__match" name="ld_answers[0][match]" aria-label="${ i18n.match }" placeholder="${ i18n.match }"> <label class="ld-answer-row__correct"><input type="checkbox" name="ld_answers[0][correct]" value="1"> ${ i18n.correct }</label> <button type="button" class="button-link-delete" data-ld-remove>${ i18n.remove }</button>`;
		row.querySelector( 'input[type="text"]' ).value = text;
		list.appendChild( row );
		renumber();
	}

	function syncFormat() {
		const value = format.value;
		box.hidden = value === 'numeric' || value === 'flashcard' || value === 'hotspot';
		box.dataset.format = value;
		numeric.hidden = value !== 'numeric';
		if ( hotspot ) {
			hotspot.hidden = value !== 'hotspot';
		}
		if ( value === 'tf' ) {
			while ( list.children.length > 2 ) {
				list.lastElementChild.remove();
			}
			while ( list.children.length < 2 ) {
				addRow();
			}
			const inputs = list.querySelectorAll( 'input[type="text"]' );
			if ( ! inputs[ 0 ].value ) {
				inputs[ 0 ].value = 'Vrai';
			}
			if ( ! inputs[ 1 ].value ) {
				inputs[ 1 ].value = 'Faux';
			}
		}
		// Choix unique / vrai-faux : une seule case cochée à la fois.
		box.dataset.single = value === 'tf' || value === 'single' ? '1' : '';
	}

	box.addEventListener( 'click', ( event ) => {
		if ( event.target.matches( '[data-ld-add]' ) ) {
			addRow();
		}
		if ( event.target.matches( '[data-ld-remove]' ) && list.children.length > 2 ) {
			event.target.closest( 'li' ).remove();
			renumber();
		}
	} );

	box.addEventListener( 'change', ( event ) => {
		if ( event.target.type === 'checkbox' && event.target.checked && box.dataset.single ) {
			list.querySelectorAll( 'input[type="checkbox"]' ).forEach( ( other ) => {
				if ( other !== event.target ) {
					other.checked = false;
				}
			} );
		}
	} );

	format.addEventListener( 'change', syncFormat );
	syncFormat();

	// Image cliquable : dessin des zones par glisser sur l'aperçu.
	const canvas = document.querySelector( '[data-ld-hotspot-canvas]' );
	const field = document.querySelector( '[data-ld-hotspot-field]' );
	if ( canvas && field ) {
		const read = () => {
			try {
				return JSON.parse( field.value || '[]' );
			} catch ( e ) {
				return [];
			}
		};
		const draw = () => {
			canvas.querySelectorAll( '.ld-zone' ).forEach( ( el ) => el.remove() );
			read().forEach( ( z ) => {
				const el = document.createElement( 'span' );
				el.className = 'ld-zone';
				Object.assign( el.style, { left: z.x + '%', top: z.y + '%', width: z.w + '%', height: z.h + '%' } );
				canvas.appendChild( el );
			} );
		};
		let start = null;
		const point = ( event ) => {
			const rect = canvas.getBoundingClientRect();
			return {
				x: Math.max( 0, Math.min( 100, ( 100 * ( event.clientX - rect.left ) ) / rect.width ) ),
				y: Math.max( 0, Math.min( 100, ( 100 * ( event.clientY - rect.top ) ) / rect.height ) ),
			};
		};
		canvas.addEventListener( 'pointerdown', ( event ) => {
			event.preventDefault();
			start = point( event );
		} );
		window.addEventListener( 'pointerup', ( event ) => {
			if ( ! start ) {
				return;
			}
			const end = point( event );
			const zone = {
				x: +Math.min( start.x, end.x ).toFixed( 1 ),
				y: +Math.min( start.y, end.y ).toFixed( 1 ),
				w: +Math.abs( end.x - start.x ).toFixed( 1 ),
				h: +Math.abs( end.y - start.y ).toFixed( 1 ),
			};
			start = null;
			if ( zone.w < 2 || zone.h < 2 ) {
				return;
			}
			field.value = JSON.stringify( [ ...read(), zone ] );
			draw();
		} );
		field.addEventListener( 'input', draw );
		draw();
	}
} )();
