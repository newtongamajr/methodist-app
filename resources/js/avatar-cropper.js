import Cropper from 'cropperjs';
import 'cropperjs/dist/cropper.css';

/**
 * Generic square-image cropper backed by Cropper.js.
 *
 * Used by both the user-avatar editor and the Person photo editor — each call
 * site provides its own modal name, wire property, and wire save method via
 * config. Modal-show/close events are dispatched on `document` (not window) so
 * Flux v2 modal listeners (`x-on:modal-show.document`) can pick them up; that
 * was the bug that broke the cropper after the Flux upgrade.
 */
document.addEventListener('alpine:init', () => {
    const factory = (config = {}) => ({
        modal: config.modal || 'image-cropper',
        wireProperty: config.wireProperty || 'newImage',
        // wireSave: name of a Livewire method to call after upload. Pass an
        // empty string / null to skip — useful when the cropped image is a
        // staging upload that gets persisted later by the main form Save.
        wireSave: config.wireSave === undefined ? 'saveImage' : config.wireSave,
        outputName: config.outputName || 'image.png',
        // Crop aspect ratio. 1 = square (avatar / Person photo), 16/9 for
        // landscape post covers, etc. Defaults to square.
        aspectRatio: config.aspectRatio ?? 1,
        cropper: null,
        srcUrl: null,
        saving: false,

        pickFile(event) {
            const file = event.target.files?.[0];
            event.target.value = '';
            if (! file) return;

            const reader = new FileReader();
            reader.onload = (e) => {
                this.srcUrl = e.target.result;
                document.dispatchEvent(new CustomEvent('modal-show', { detail: { name: this.modal } }));
                // Two animation frames is enough time for <dialog>.showModal()
                // to lay out the dialog so the inner img has real dimensions
                // before Cropper tries to read them.
                requestAnimationFrame(() => requestAnimationFrame(() => this.mount()));
            };
            reader.readAsDataURL(file);
        },

        /**
         * The crop image lives INSIDE the Flux modal, which has its own
         * `x-data="fluxModal(...)"` and therefore its own Alpine $refs scope.
         * `this.$refs` on the outer cropper component can't see refs inside
         * that child scope, so resolve the image by DOM selector via the
         * dialog's `data-modal` attribute instead.
         */
        findImage() {
            return document.querySelector(`[data-modal="${this.modal}"] [data-crop-image]`);
        },

        mount() {
            const img = this.findImage();
            if (! img) return;

            this.destroy();

            // Always wait for the *new* src to actually load before creating
            // Cropper. Reading img.complete after assigning src returns the
            // status of the PREVIOUS load (which can be true if the modal
            // was opened before), so initializing eagerly leaves Cropper
            // bound to a stale image and `getCroppedCanvas()` later returns
            // null. Clearing src first guarantees a fresh load + onload.
            const onReady = () => {
                this.cropper = new Cropper(img, {
                    aspectRatio: this.aspectRatio,
                    viewMode: 1,
                    autoCropArea: 1,
                    dragMode: 'move',
                    background: false,
                    responsive: true,
                    modal: true,
                });
            };

            img.onload = onReady;
            img.onerror = () => {
                // eslint-disable-next-line no-console
                console.error('[image-cropper] failed to load source image');
            };
            img.removeAttribute('src');
            img.src = this.srcUrl;
        },

        rotate(deg) {
            this.cropper?.rotate(deg);
        },

        zoom(ratio) {
            this.cropper?.zoom(ratio);
        },

        flipH() {
            const data = this.cropper?.getData();
            this.cropper?.scaleX(-1 * (data?.scaleX ?? 1));
        },

        flipV() {
            const data = this.cropper?.getData();
            this.cropper?.scaleY(-1 * (data?.scaleY ?? 1));
        },

        async save() {
            if (! this.cropper || this.saving) return;
            this.saving = true;

            // Pick canvas dimensions that honor the aspect ratio so a 16:9
            // cover doesn't get squished. Long edge stays at 1024 px.
            const ar = this.aspectRatio || 1;
            const width = ar >= 1 ? 1024 : Math.round(1024 * ar);
            const height = ar >= 1 ? Math.round(1024 / ar) : 1024;

            const canvas = this.cropper.getCroppedCanvas({
                width,
                height,
                imageSmoothingEnabled: true,
                imageSmoothingQuality: 'high',
            });

            const blob = await new Promise((resolve) => canvas.toBlob(resolve, 'image/png', 0.92));
            const file = new File([blob], this.outputName, { type: 'image/png' });

            // Capture refs Alpine's `this` won't resolve inside the upload
            // callbacks (Livewire's upload runs the callbacks with no
            // particular `this` binding).
            const wire = this.$wire;
            const wireSave = this.wireSave;
            const onClose = () => this.close();

            wire.upload(
                this.wireProperty,
                file,
                async () => {
                    try {
                        // wireSave is optional: when empty, the cropped image
                        // is staged on the wire property and the consumer's
                        // own form-save flow will persist it later. Avoids
                        // forcing a separate "save" round-trip for inline
                        // editors like the post cover.
                        if (wireSave) {
                            await wire[wireSave]();
                        }
                    } finally {
                        this.saving = false;
                        onClose();
                    }
                },
                () => {
                    this.saving = false;
                },
            );
        },

        close() {
            document.dispatchEvent(new CustomEvent('modal-close', { detail: { name: this.modal } }));
            this.srcUrl = null;
            this.destroy();
        },

        destroy() {
            if (this.cropper) {
                this.cropper.destroy();
                this.cropper = null;
            }
        },
    });

    // Generic factory for any image-cropper instance.
    window.Alpine.data('imageCropper', factory);

    // Backward-compat: existing call sites that already use `avatarCropper`
    // continue to work with the Avatar-tab defaults baked in.
    window.Alpine.data('avatarCropper', () => factory({
        modal: 'avatar-cropper',
        wireProperty: 'newAvatar',
        wireSave: 'saveAvatar',
        outputName: 'avatar.png',
    }));
});