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
        wireSave: config.wireSave || 'saveImage',
        outputName: config.outputName || 'image.png',
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
                this.$nextTick(() => this.mount());
            };
            reader.readAsDataURL(file);
        },

        mount() {
            const img = this.$refs.cropImage;
            if (! img) return;

            img.src = this.srcUrl;
            this.destroy();
            this.cropper = new Cropper(img, {
                aspectRatio: 1,
                viewMode: 1,
                autoCropArea: 1,
                dragMode: 'move',
                background: false,
                responsive: true,
                modal: true,
            });
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

            const canvas = this.cropper.getCroppedCanvas({
                width: 1024,
                height: 1024,
                imageSmoothingEnabled: true,
                imageSmoothingQuality: 'high',
            });

            const blob = await new Promise((resolve) => canvas.toBlob(resolve, 'image/png', 0.92));
            const file = new File([blob], this.outputName, { type: 'image/png' });

            this.$wire.upload(
                this.wireProperty,
                file,
                () => {
                    this.$wire.call(this.wireSave);
                    this.saving = false;
                    this.close();
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