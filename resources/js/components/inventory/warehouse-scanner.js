import { notify } from "../../notifications";

async function createDecoder() {
    const { default: jsQR } = await import("jsqr");
    return (frame) => jsQR(frame.data, frame.width, frame.height)?.data;
}

function isTicketQuery(query, ticketPath, codePrefix) {
    const codeMarker = codePrefix + "-";
    if (query.toUpperCase().startsWith(codeMarker) && /^[0-9]{1,19}$/.test(query.slice(codeMarker.length))) return true;

    const prefix = `${ticketPath}/`;
    if (!query.startsWith(prefix) && !/^https?:\/\//i.test(query)) return false;

    try {
        const { pathname } = new URL(query, window.location.origin);
        return pathname.startsWith(prefix) && /^[0-9]{1,19}\/?$/.test(pathname.slice(prefix.length));
    } catch {
        return false;
    }
}

export default (messages, ticketPath, codePrefix) => {
    const canvas = document.createElement("canvas");
    const context = canvas.getContext("2d", { willReadFrequently: true });
    let decoder;
    let stream;
    let frameTimer;
    let cameraSession = 0;
    let request;

    async function decode(source, width, height) {
        decoder ??= await createDecoder();
        const ratio = Math.min(1, 720 / Math.max(width, height));
        const frameWidth = Math.max(1, Math.round(width * ratio));
        const frameHeight = Math.max(1, Math.round(height * ratio));
        if (canvas.width !== frameWidth || canvas.height !== frameHeight) {
            canvas.width = frameWidth;
            canvas.height = frameHeight;
        }
        context.drawImage(source, 0, 0, frameWidth, frameHeight);
        return decoder(context.getImageData(0, 0, frameWidth, frameHeight));
    }

    return {
        opening: false,
        cameraActive: false,
        busy: false,
        readingImage: false,

        async startCamera() {
            if (this.opening || this.cameraActive || this.busy || this.readingImage) return;
            if (!navigator.mediaDevices?.getUserMedia) {
                notify("warning", messages.cameraUnavailable);
                return;
            }

            const session = ++cameraSession;
            this.opening = true;
            try {
                const media = await navigator.mediaDevices.getUserMedia({
                    audio: false,
                    video: { facingMode: { ideal: "environment" }, width: { ideal: 1280 } },
                });
                if (session !== cameraSession) {
                    media.getTracks().forEach((track) => track.stop());
                    return;
                }

                stream = media;
                const video = this.$refs.preview;
                video.srcObject = media;
                await video.play();
                if (session !== cameraSession) return;
                this.cameraActive = true;
                this.opening = false;

                const readFrame = async () => {
                    if (session !== cameraSession) return;
                    try {
                        const value = video.videoWidth && video.videoHeight
                            ? await decode(video, video.videoWidth, video.videoHeight)
                            : null;
                        if (session !== cameraSession) return;
                        if (value) {
                            this.lookup(value);
                            return;
                        }
                        frameTimer = setTimeout(readFrame, 140);
                    } catch {
                        if (session !== cameraSession) return;
                        this.stopCamera();
                        notify("error", messages.cameraError);
                    }
                };
                readFrame();
            } catch {
                if (session !== cameraSession) return;
                this.stopCamera();
                notify("error", messages.cameraError);
            }
        },

        stopCamera() {
            cameraSession++;
            clearTimeout(frameTimer);
            stream?.getTracks().forEach((track) => track.stop());
            stream = null;
            if (this.$refs.preview) this.$refs.preview.srcObject = null;
            this.cameraActive = false;
            this.opening = false;
        },

        async readImage(field) {
            const file = field.files[0];
            if (!file || this.readingImage || this.busy) return;
            this.stopCamera();
            this.readingImage = true;
            const url = URL.createObjectURL(file);
            try {
                const image = new Image();
                image.src = url;
                await image.decode();
                const value = await decode(image, image.naturalWidth, image.naturalHeight);
                if (value) this.lookup(value);
                else notify("warning", messages.imageError);
            } catch {
                notify("warning", messages.imageError);
            } finally {
                URL.revokeObjectURL(url);
                field.value = "";
                this.readingImage = false;
            }
        },

        async lookup(value) {
            if (this.busy) return;
            const query = (value ?? this.$refs.query.value).trim();
            if (!query) return;

            this.stopCamera();
            this.$refs.query.value = query;
            if (query.length > 2048 || !isTicketQuery(query, ticketPath, codePrefix)) {
                notify("warning", messages.invalidQuery);
                return;
            }

            this.busy = true;
            const controller = new AbortController();
            request = controller;

            try {
                const url = new URL(this.$refs.lookup.action);
                url.searchParams.set("q", query);
                const response = await fetch(url, {
                    headers: { Accept: "application/json" },
                    signal: controller.signal,
                });
                if (controller.signal.aborted) return;
                if (response.status === 401 || response.redirected) {
                    window.location.assign(url);
                    return;
                }
                const data = await response.json();
                if (controller.signal.aborted) return;
                if (!response.ok) {
                    notify("warning", data.errors?.q?.[0] ?? messages.lookupError);
                    return;
                }
                window.location.assign(data.url);
            } catch {
                if (!controller.signal.aborted) notify("error", messages.lookupError);
            } finally {
                if (request === controller) {
                    request = null;
                    this.busy = false;
                }
            }
        },

        stop() {
            this.stopCamera();
            request?.abort();
        },

        destroy() {
            this.stop();
        },
    };
};
