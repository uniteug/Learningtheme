/**
 * Façade vidéo : le lecteur YouTube / Vimeo n'est chargé qu'au clic.
 */
import { store, getContext, getElement } from '@wordpress/interactivity';

store( 'learn-drive/video', {
	actions: {
		play() {
			const { src, title } = getContext();
			const { ref } = getElement();
			const frame = document.createElement( 'iframe' );
			frame.src = src;
			frame.title = title;
			frame.allow =
				'accelerometer; autoplay; encrypted-media; gyroscope; picture-in-picture; fullscreen';
			frame.allowFullscreen = true;
			frame.loading = 'eager';
			const wrapper = ref.closest( '.ld-video' );
			wrapper.replaceChildren( frame );
			wrapper.classList.add( 'is-playing' );
			frame.focus();
		},
	},
} );
