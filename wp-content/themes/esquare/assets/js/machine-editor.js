/**
 * "Infos pratiques" panel in the sidebar of the machine editor: the two fields
 * that are not part of the page content. Plain JS on purpose: no build step.
 */
( function ( wp ) {
	const el = wp.element.createElement;
	const { PluginDocumentSettingPanel } = wp.editor;
	const { TextControl } = wp.components;
	const { useEntityProp } = wp.coreData;

	function MachinePanel() {
		const [ meta, setMeta ] = useEntityProp( 'postType', 'esquare_machine', 'meta' );
		const field = ( key, props ) =>
			el( TextControl, {
				...props,
				value: ( meta && meta[ key ] ) || '',
				onChange: ( value ) => setMeta( { ...meta, [ key ]: value } ),
				__next40pxDefaultSize: true,
				__nextHasNoMarginBottom: true,
			} );

		return el(
			PluginDocumentSettingPanel,
			{ name: 'esquare-machine', title: 'Infos pratiques' },
			el(
				'div',
				{ style: { display: 'grid', gap: '16px' } },
				field( 'esquare_machine_price', {
					label: 'Tarif / conditions',
					help: 'Ex. : « Compris dans l’abonnement, hors consommables ».',
				} ),
				field( 'esquare_machine_booking_url', {
					label: 'Lien de réservation',
					type: 'url',
					placeholder: 'https://fablab.esquare.be/…',
					help: 'Affiche le bouton « Réserver ». Laisser vide s’il n’y a pas de réservation.',
				} )
			)
		);
	}

	wp.plugins.registerPlugin( 'esquare-machine', { render: MachinePanel } );
} )( window.wp );
