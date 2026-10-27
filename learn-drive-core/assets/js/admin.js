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
	const i18n = window.learnDriveAdmin || {};

	function renumber() {
		list.querySelectorAll( 'li' ).forEach( ( row, index ) => {
			row.querySelectorAll( 'input' ).forEach( ( input ) => {
				input.name = input.name.replace( /\[\d+\]/, `[${ index }]` );
			} );
		} );
	}

	function addRow( text = '' ) {
		if ( list.children.length >= 6 ) {
			return;
		}
		const row = document.createElement( 'li' );
		row.className = 'ld-answer-row';
		row.innerHTML = `<input type="text" class="widefat" name="ld_answers[0][text]" aria-label="${ i18n.answer }"> <label><input type="checkbox" name="ld_answers[0][correct]" value="1"> ${ i18n.correct }</label> <button type="button" class="button-link-delete" data-ld-remove>${ i18n.remove }</button>`;
		row.querySelector( 'input[type="text"]' ).value = text;
		list.appendChild( row );
		renumber();
	}

	function syncFormat() {
		const value = format.value;
		box.hidden = value === 'numeric' || value === 'flashcard';
		numeric.hidden = value !== 'numeric';
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
} )();
