/**
 * Éditeur : aperçu serveur et réglages des blocs Learn & Drive.
 *
 * Les blocs sont rendus en PHP ; l'éditeur affiche le même rendu (ServerSideRender)
 * et génère automatiquement les réglages à partir des attributs de block.json.
 */
( function ( wp ) {
	const { registerBlockType, getBlockType } = wp.blocks;
	const { createElement: el, Fragment } = wp.element;
	const { InspectorControls, useBlockProps } = wp.blockEditor;
	const { PanelBody, TextControl, ToggleControl, SelectControl, Disabled } =
		wp.components;
	const ServerSideRender = wp.serverSideRender;
	const { __ } = wp.i18n;

	const LABELS = {
		quizId: __( 'ID du quiz (0 = quiz affiché)', 'learn-drive-core' ),
		source: __( 'Source des questions', 'learn-drive-core' ),
		count: __( 'Nombre', 'learn-drive-core' ),
		compact: __( 'Version compacte', 'learn-drive-core' ),
		heading: __( 'Titre (laisser vide pour le titre par défaut)', 'learn-drive-core' ),
		questionId: __( 'ID de la question (0 = question affichée)', 'learn-drive-core' ),
		layout: __( 'Mise en page', 'learn-drive-core' ),
		track: __( 'Catégorie (slug, optionnel)', 'learn-drive-core' ),
		postType: __( 'Type de contenu', 'learn-drive-core' ),
		variant: __( 'Variante', 'learn-drive-core' ),
		title: __( 'Titre', 'learn-drive-core' ),
	};

	function controlFor( name, schema, value, onChange ) {
		const label = LABELS[ name ] || name;
		if ( schema.enum ) {
			return el( SelectControl, {
				key: name,
				label,
				value,
				options: schema.enum.map( ( option ) => ( {
					label: option,
					value: option,
				} ) ),
				onChange,
				__nextHasNoMarginBottom: true,
			} );
		}
		if ( schema.type === 'boolean' ) {
			return el( ToggleControl, {
				key: name,
				label,
				checked: !! value,
				onChange,
				__nextHasNoMarginBottom: true,
			} );
		}
		return el( TextControl, {
			key: name,
			label,
			type: schema.type === 'integer' ? 'number' : 'text',
			value: value ?? '',
			onChange: ( next ) =>
				onChange( schema.type === 'integer' ? parseInt( next || 0, 10 ) : next ),
			__nextHasNoMarginBottom: true,
		} );
	}

	function makeEdit( name ) {
		return function Edit( { attributes, setAttributes } ) {
			const blockProps = useBlockProps();
			const type = getBlockType( name );
			const schemaAttributes = Object.entries( type.attributes || {} ).filter(
				( [ key ] ) =>
					! [ 'lock', 'metadata', 'className', 'style', 'align' ].includes( key )
			);

			return el(
				Fragment,
				null,
				schemaAttributes.length
					? el(
							InspectorControls,
							null,
							el(
								PanelBody,
								{ title: __( 'Réglages', 'learn-drive-core' ) },
								schemaAttributes.map( ( [ key, schema ] ) =>
									controlFor( key, schema, attributes[ key ], ( value ) =>
										setAttributes( { [ key ]: value } )
									)
								)
							)
					  )
					: null,
				el(
					'div',
					blockProps,
					el(
						Disabled,
						null,
						el( ServerSideRender, {
							block: name,
							attributes,
							EmptyResponsePlaceholder: () =>
								el(
									'p',
									{ className: 'ld-editor-placeholder' },
									type.title +
										' — ' +
										__(
											'aperçu disponible sur la page publiée.',
											'learn-drive-core'
										)
								),
						} )
					)
				)
			);
		};
	}

	[
		'quiz',
		'question',
		'question-of-the-day',
		'related',
		'toc',
		'lesson-nav',
		'course-outline',
		'updated',
		'stats',
		'topic-progress',
		'access-badge',
		'content-type',
		'author-profile',
		'sheet-meta',
		'change-log',
		'archive-intro',
		'exam-link',
		'track-facts',
		'term-content',
		'dashboard',
		'access-code',
	].forEach( ( slug ) => {
		const name = 'learn-drive/' + slug;
		if ( getBlockType( name ) ) {
			return;
		}
		registerBlockType( name, {
			edit: makeEdit( name ),
			save: () => null,
		} );
	} );
} )( window.wp );
