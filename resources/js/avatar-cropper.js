import Cropper from 'cropperjs';
import 'cropperjs/dist/cropper.css';

document.addEventListener('alpine:init', () => {
    window.Alpine.data('avatarCropper', () => ({
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
                window.dispatchEvent(new CustomEvent('modal-show', { detail: { name: 'avatar-cropper' } }));
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
            const file = new File([blob], 'avatar.png', { type: 'image/png' });

            this.$wire.upload(
                'newAvatar',
                file,
                () => {
                    this.$wire.saveAvatar();
                    this.saving = false;
                    this.close();
                },
                () => {
                    this.saving = false;
                },
            );
        },

        close() {
            window.dispatchEvent(new CustomEvent('modal-close', { detail: { name: 'avatar-cropper' } }));
            this.srcUrl = null;
            this.destroy();
        },

        destroy() {
            if (this.cropper) {
                this.cropper.destroy();
                this.cropper = null;
            }
        },
    }));
});