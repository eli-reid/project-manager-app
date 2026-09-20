/**
 * Client-side behaviour for the blueprint sheet viewer.
 *
 * Everything here is intentionally dependency-free (native browser APIs only):
 *  - Pan/zoom is a plain CSS `translate() scale()` transform on a "stage" element,
 *    so changing it never resizes the image or triggers layout/reflow.
 *  - Zoom always keeps the point under the cursor fixed ("zoom to cursor").
 *  - Note/markup drawing and rendering use two stacked <canvas> elements (a
 *    "committed" layer redrawn from server-provided markers, and a lightweight
 *    "draft" layer used only while the user is actively drawing).
 *  - The rectangle "capture" tool crops the sheet image into an off-screen
 *    canvas and writes it to the OS clipboard via the Clipboard API.
 *  - Fullscreen uses the real browser Fullscreen API (in addition to a CSS
 *    fallback) so it lives entirely outside Livewire's DOM/morph cycle and can
 *    never be disturbed by a (renderless) view-state save while zooming/panning.
 *
 * Registered as an Alpine.data() component (Livewire's bundled Alpine build
 * dispatches `alpine:init`) rather than exported as a bare module, since this
 * app has no separate Alpine build step of its own — Alpine ships inside
 * Livewire's assets and is only reachable through that lifecycle hook.
 */
document.addEventListener('alpine:init', () => {
    Alpine.data('planSheetViewer', planSheetViewer);
});

function planSheetViewer({ zoom, centerX, centerY, minZoom = 0.25, maxZoom = 6 }) {
    return {
        // --- pan / zoom state -------------------------------------------------
        zoom,
        panX: 0,
        panY: 0,
        minZoom,
        maxZoom,
        naturalWidth: 0,
        naturalHeight: 0,
        initialCenterX: centerX,
        initialCenterY: centerY,
        centered: false,

        // --- UI state -----------------------------------------------------------
        fullScreen: false,
        palette: false,
        panel: 'details', // details | revisions | notes
        showMarkup: true,
        tool: 'pan', // pan | pin | rect | capture
        isPointerDown: false,
        dragMoved: false,
        pointerStart: null,
        draftRect: null,
        draft: null, // pending annotation form: { type, geometry, x, y }
        activeMarker: null,
        captureMessage: '',
        captureBusy: false,
        persistTimer: null,

        get stageStyle() {
            return `transform: translate(${this.panX}px, ${this.panY}px) scale(${this.zoom}); transform-origin: 0 0;`;
        },

        get cursorClass() {
            if (this.tool === 'pan') {
                return this.isPointerDown ? 'cursor-grabbing' : 'cursor-grab';
            }

            return 'cursor-crosshair';
        },

        init() {
            this.$watch('$wire.annotationMarkers', () => this.drawMarkers());
            window.addEventListener('resize', () => this.recenter());
            document.addEventListener('fullscreenchange', () => {
                this.fullScreen = Boolean(document.fullscreenElement);
            });
        },

        // --- image + canvas sizing ----------------------------------------------
        onImageLoad(event) {
            this.naturalWidth = event.target.naturalWidth;
            this.naturalHeight = event.target.naturalHeight;
            this.$refs.markupCanvas.width = this.naturalWidth;
            this.$refs.markupCanvas.height = this.naturalHeight;
            this.$refs.draftCanvas.width = this.naturalWidth;
            this.$refs.draftCanvas.height = this.naturalHeight;

            if (!this.centered) {
                this.centered = true;
                this.centerOn(this.initialCenterX, this.initialCenterY, this.zoom);
            }

            this.drawMarkers();
        },

        viewportRect() {
            return this.$refs.viewport.getBoundingClientRect();
        },

        // Returns null when no revision image is loaded (e.g. a sheet still awaiting
        // its first render) so callers can no-op instead of throwing.
        imageRect() {
            return this.$refs.sheetImage ? this.$refs.sheetImage.getBoundingClientRect() : null;
        },

        /**
         * Position the stage so the given normalized (0..1) image point sits at
         * the centre of the viewport, at the given zoom level.
         */
        centerOn(nx, ny, zoom) {
            const viewport = this.viewportRect();
            const displayWidth = this.naturalWidth || viewport.width;
            const displayHeight = this.naturalHeight || viewport.height;
            this.zoom = zoom;
            this.panX = (viewport.width / 2) - (zoom * nx * displayWidth);
            this.panY = (viewport.height / 2) - (zoom * ny * displayHeight);
        },

        /** Re-centre on the last known point after a window resize. */
        recenter() {
            if (!this.naturalWidth) {
                return;
            }

            const [nx, ny] = this.currentCenterFraction();
            this.centerOn(nx, ny, this.zoom);
        },

        currentCenterFraction() {
            const viewport = this.viewportRect();
            const displayWidth = this.naturalWidth || viewport.width;
            const displayHeight = this.naturalHeight || viewport.height;
            const nx = ((viewport.width / 2) - this.panX) / (this.zoom * displayWidth);
            const ny = ((viewport.height / 2) - this.panY) / (this.zoom * displayHeight);

            return [Math.min(1, Math.max(0, nx)), Math.min(1, Math.max(0, ny))];
        },

        fit() {
            this.centerOn(0.5, 0.5, 1);
            this.schedulePersist();
        },

        zoomBy(amount, atClientX = null, atClientY = null) {
            this.zoomTo(this.zoom + amount, atClientX, atClientY);
        },

        zoomTo(nextZoom, atClientX = null, atClientY = null) {
            const clamped = Math.min(this.maxZoom, Math.max(this.minZoom, nextZoom));
            const viewport = this.viewportRect();
            const cx = atClientX ?? (viewport.left + viewport.width / 2);
            const cy = atClientY ?? (viewport.top + viewport.height / 2);
            const localX = (cx - viewport.left - this.panX) / this.zoom;
            const localY = (cy - viewport.top - this.panY) / this.zoom;

            this.panX = (cx - viewport.left) - (clamped * localX);
            this.panY = (cy - viewport.top) - (clamped * localY);
            this.zoom = clamped;
            this.schedulePersist();
        },

        onWheel(event) {
            event.preventDefault();
            const amount = event.deltaY > 0 ? -0.15 : 0.15;
            this.zoomTo(this.zoom * (1 + amount), event.clientX, event.clientY);
        },

        onDoubleClick(event) {
            if (this.tool !== 'pan') {
                return;
            }

            this.zoomTo(this.zoom + 1, event.clientX, event.clientY);
        },

        schedulePersist() {
            clearTimeout(this.persistTimer);
            this.persistTimer = setTimeout(() => {
                const [nx, ny] = this.currentCenterFraction();
                this.$wire.persistViewState(this.zoom, nx, ny);
            }, 500);
        },

        // --- pointer interaction: pan, pin, rect, capture ------------------------
        normalizedPoint(event) {
            const rect = this.imageRect();
            if (!rect || rect.width === 0 || rect.height === 0) {
                return null;
            }

            const nx = (event.clientX - rect.left) / rect.width;
            const ny = (event.clientY - rect.top) / rect.height;

            return [Math.min(1, Math.max(0, nx)), Math.min(1, Math.max(0, ny))];
        },

        onPointerDown(event) {
            if (event.button !== 0) {
                return;
            }

            this.isPointerDown = true;
            this.dragMoved = false;
            this.pointerStart = { x: event.clientX, y: event.clientY, panX: this.panX, panY: this.panY };

            if (this.tool !== 'pan') {
                const point = this.normalizedPoint(event);
                this.draftRect = point ? { x1: point[0], y1: point[1], x2: point[0], y2: point[1] } : null;
            }

            event.target.setPointerCapture?.(event.pointerId);
        },

        onPointerMove(event) {
            if (!this.isPointerDown) {
                return;
            }

            const dx = event.clientX - this.pointerStart.x;
            const dy = event.clientY - this.pointerStart.y;
            if (Math.abs(dx) > 2 || Math.abs(dy) > 2) {
                this.dragMoved = true;
            }

            if (this.tool === 'pan') {
                this.panX = this.pointerStart.panX + dx;
                this.panY = this.pointerStart.panY + dy;

                return;
            }

            const point = this.normalizedPoint(event);
            if (point && this.draftRect) {
                this.draftRect.x2 = point[0];
                this.draftRect.y2 = point[1];
                this.drawDraftRect();
            }
        },

        onPointerUp(event) {
            if (!this.isPointerDown) {
                return;
            }

            this.isPointerDown = false;

            if (this.tool === 'pan') {
                if (this.dragMoved) {
                    this.schedulePersist();
                } else {
                    this.tryOpenMarkerAt(event);
                }

                return;
            }

            this.finishDraw(event);
        },

        tryOpenMarkerAt(event) {
            const point = this.normalizedPoint(event);
            if (!point) {
                return;
            }

            const [nx, ny] = point;
            const markers = this.$wire.annotationMarkers ?? [];
            const hit = [...markers].reverse().find((marker) => this.hitTest(marker, nx, ny));
            this.activeMarker = hit ? { ...hit, screenX: event.clientX, screenY: event.clientY } : null;
        },

        hitTest(marker, nx, ny) {
            const geometry = marker.geometry ?? {};
            if (marker.type === 'rect') {
                const x1 = Math.min(geometry.x ?? 0, (geometry.x ?? 0) + (geometry.width ?? 0));
                const x2 = Math.max(geometry.x ?? 0, (geometry.x ?? 0) + (geometry.width ?? 0));
                const y1 = Math.min(geometry.y ?? 0, (geometry.y ?? 0) + (geometry.height ?? 0));
                const y2 = Math.max(geometry.y ?? 0, (geometry.y ?? 0) + (geometry.height ?? 0));

                return nx >= x1 && nx <= x2 && ny >= y1 && ny <= y2;
            }

            const dx = nx - (geometry.x ?? 0);
            const dy = ny - (geometry.y ?? 0);

            return Math.sqrt((dx * dx) + (dy * dy)) <= 0.018;
        },

        finishDraw(event) {
            if (!this.draftRect) {
                return;
            }

            const { x1, y1, x2, y2 } = this.draftRect;
            const minSize = 0.004;
            const isRectTool = this.tool === 'rect' || this.tool === 'capture';
            const width = Math.abs(x2 - x1);
            const height = Math.abs(y2 - y1);

            if (this.tool === 'capture') {
                this.clearDraft();
                if (width > minSize && height > minSize) {
                    this.copySelectionToClipboard(Math.min(x1, x2), Math.min(y1, y2), width, height);
                } else {
                    this.captureMessage = 'Drag to select an area to copy.';
                    setTimeout(() => { this.captureMessage = ''; }, 2500);
                }
                this.tool = 'pan';

                return;
            }

            if (isRectTool && (width < minSize || height < minSize)) {
                // Too small to be a deliberate rectangle drag: treat as a pin instead.
                this.draft = { type: 'pin', x: x1, y: y1, content: '', visibility: 'public', pinToRevision: false };
                this.clearDraft();

                return;
            }

            if (this.tool === 'rect') {
                this.draft = {
                    type: 'rect',
                    x: Math.min(x1, x2), y: Math.min(y1, y2), width, height,
                    content: '', visibility: 'public', pinToRevision: false,
                };
                this.clearDraft();

                return;
            }

            if (this.tool === 'pin') {
                this.draft = { type: 'pin', x: x1, y: y1, content: '', visibility: 'public', pinToRevision: false };
                this.clearDraft();
            }
        },

        clearDraft() {
            this.draftRect = null;
            const ctx = this.$refs.draftCanvas?.getContext('2d');
            ctx?.clearRect(0, 0, this.$refs.draftCanvas.width, this.$refs.draftCanvas.height);
        },

        cancelDraft() {
            this.draft = null;
        },

        saveDraft() {
            if (!this.draft) {
                return;
            }

            const geometry = this.draft.type === 'rect'
                ? { x: this.draft.x, y: this.draft.y, width: this.draft.width, height: this.draft.height }
                : { x: this.draft.x, y: this.draft.y };

            this.$wire.createAnnotation(
                this.draft.type,
                geometry,
                this.draft.content,
                this.draft.visibility,
                this.draft.pinToRevision,
            );
            this.draft = null;
            this.tool = 'pan';
        },

        // --- canvas rendering -----------------------------------------------------
        drawDraftRect() {
            const canvas = this.$refs.draftCanvas;
            const ctx = canvas?.getContext('2d');
            if (!ctx || !this.draftRect) {
                return;
            }

            const { x1, y1, x2, y2 } = this.draftRect;
            ctx.clearRect(0, 0, canvas.width, canvas.height);
            ctx.lineWidth = Math.max(1, canvas.width * 0.0015);
            ctx.strokeStyle = this.tool === 'capture' ? '#38bdf8' : '#f59e0b';
            ctx.setLineDash(this.tool === 'capture' ? [canvas.width * 0.006, canvas.width * 0.006] : []);
            ctx.fillStyle = this.tool === 'capture' ? 'rgba(56, 189, 248, 0.12)' : 'rgba(245, 158, 11, 0.15)';
            const x = Math.min(x1, x2) * canvas.width;
            const y = Math.min(y1, y2) * canvas.height;
            const w = Math.abs(x2 - x1) * canvas.width;
            const h = Math.abs(y2 - y1) * canvas.height;
            ctx.fillRect(x, y, w, h);
            ctx.strokeRect(x, y, w, h);
        },

        drawMarkers() {
            const canvas = this.$refs.markupCanvas;
            const ctx = canvas?.getContext('2d');
            if (!ctx || !canvas.width) {
                return;
            }

            ctx.clearRect(0, 0, canvas.width, canvas.height);
            if (!this.showMarkup) {
                return;
            }

            const markers = this.$wire.annotationMarkers ?? [];
            markers.forEach((marker) => this.drawMarker(ctx, canvas, marker));
        },

        markerColor(marker) {
            if (marker.status === 'resolved') {
                return '#71717a';
            }

            return marker.visibility === 'private' ? '#f59e0b' : '#38bdf8';
        },

        drawMarker(ctx, canvas, marker) {
            const color = this.markerColor(marker);
            const geometry = marker.geometry ?? {};

            if (marker.type === 'rect') {
                const x = (geometry.x ?? 0) * canvas.width;
                const y = (geometry.y ?? 0) * canvas.height;
                const w = (geometry.width ?? 0) * canvas.width;
                const h = (geometry.height ?? 0) * canvas.height;
                ctx.lineWidth = Math.max(1, canvas.width * 0.0015);
                ctx.strokeStyle = color;
                ctx.fillStyle = `${color}26`;
                ctx.fillRect(x, y, w, h);
                ctx.strokeRect(x, y, w, h);

                return;
            }

            const cx = (geometry.x ?? 0) * canvas.width;
            const cy = (geometry.y ?? 0) * canvas.height;
            const radius = canvas.width * 0.008;
            ctx.beginPath();
            ctx.arc(cx, cy, radius, 0, Math.PI * 2);
            ctx.fillStyle = color;
            ctx.fill();
            ctx.lineWidth = Math.max(1, canvas.width * 0.001);
            ctx.strokeStyle = '#0f172a';
            ctx.stroke();
        },

        // --- clipboard capture ------------------------------------------------------
        async copySelectionToClipboard(nx, ny, nw, nh) {
            const image = this.$refs.sheetImage;
            if (!image || !image.naturalWidth) {
                return;
            }

            if (!navigator.clipboard || typeof window.ClipboardItem === 'undefined') {
                this.captureMessage = 'Your browser does not support copying images to the clipboard.';
                setTimeout(() => { this.captureMessage = ''; }, 3500);

                return;
            }

            this.captureBusy = true;
            const sx = nx * image.naturalWidth;
            const sy = ny * image.naturalHeight;
            const sw = nw * image.naturalWidth;
            const sh = nh * image.naturalHeight;

            const canvas = document.createElement('canvas');
            canvas.width = Math.max(1, Math.round(sw));
            canvas.height = Math.max(1, Math.round(sh));
            const ctx = canvas.getContext('2d');
            ctx.drawImage(image, sx, sy, sw, sh, 0, 0, canvas.width, canvas.height);

            try {
                const blob = await new Promise((resolve) => canvas.toBlob(resolve, 'image/png'));
                await navigator.clipboard.write([new ClipboardItem({ 'image/png': blob })]);
                this.captureMessage = 'Copied selection to clipboard.';
            } catch (error) {
                this.captureMessage = 'Copy failed — check your browser’s clipboard permissions.';
            } finally {
                this.captureBusy = false;
                setTimeout(() => { this.captureMessage = ''; }, 2500);
            }
        },

        // --- fullscreen + keyboard -------------------------------------------------
        async toggleFullScreen() {
            if (!this.fullScreen) {
                try {
                    await this.$refs.root.requestFullscreen?.();
                } catch (error) {
                    // Fullscreen API unavailable/blocked: fall back to the CSS-only mode below.
                }
                this.fullScreen = true;

                return;
            }

            if (document.fullscreenElement) {
                try {
                    await document.exitFullscreen();
                } catch (error) {
                    // Ignore - state is still corrected below.
                }
            }
            this.fullScreen = false;
        },

        setTool(tool) {
            this.tool = tool === this.tool && tool !== 'pan' ? 'pan' : tool;
            this.clearDraft();
        },

        keydown(event) {
            const target = event.target;
            const typing = target instanceof HTMLElement && ['INPUT', 'TEXTAREA', 'SELECT'].includes(target.tagName);
            if (typing) {
                if (event.key === 'Escape') {
                    target.blur();
                }

                return;
            }

            if (event.key === 'Escape') {
                if (this.draft) { this.cancelDraft(); return; }
                if (this.activeMarker) { this.activeMarker = null; return; }
                if (this.palette) { this.palette = false; return; }
                if (this.fullScreen) { this.toggleFullScreen(); return; }
                this.tool = 'pan';

                return;
            }

            if ((event.metaKey || event.ctrlKey) && event.key.toLowerCase() === 'k') {
                event.preventDefault();
                this.palette = true;

                return;
            }

            if (event.key === '+' || event.key === '=') { this.zoomBy(0.25); }
            if (event.key === '-') { this.zoomBy(-0.25); }
            if (event.key === '0') { this.fit(); }
            if (event.key.toLowerCase() === 'm') { this.showMarkup = !this.showMarkup; this.drawMarkers(); }
            if (event.key.toLowerCase() === 'f') { this.toggleFullScreen(); }
            if (event.key.toLowerCase() === 'v') { this.setTool('pan'); }
            if (event.key.toLowerCase() === 'p') { this.setTool('pin'); }
            if (event.key.toLowerCase() === 'r') { this.setTool('rect'); }
            if (event.key.toLowerCase() === 'c') { this.setTool('capture'); }
        },
    };
}
