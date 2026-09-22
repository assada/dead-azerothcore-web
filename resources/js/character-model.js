import $ from 'jquery';
import createModelViewer from './model-viewer';

window.$ = window.jQuery = $;

export default function characterModel() {
    let viewer;
    let observer;
    let timer;
    let ready;
    let onMount;
    let onLeave;
    const data = JSON.parse(document.getElementById('character-model-data').textContent);
    const races = {1: 'human', 2: 'orc', 3: 'dwarf', 4: 'nightelf', 5: 'scourge', 6: 'tauren', 7: 'gnome', 8: 'troll', 10: 'bloodelf', 11: 'draenei'};
    return {
        loading: true,
        error: false,
        mount: 0,
        mountName: '',
        zoom: data.race === 2 ? 0.8 : 1.1,
        initViewer() {
            // The viewer expects Wowhead's debug hook.
            window.WH = {debug() {}};
            ready = () => this.render();
            if (document.readyState === 'complete') ready();
            else window.addEventListener('load', ready, {once: true});
            observer = new ResizeObserver(() => {
                const {width, height} = this.$refs.canvas.getBoundingClientRect();
                if (viewer?.renderer && width && height) viewer.renderer.onResize(width, height, width / height);
            });
            observer.observe(this.$refs.canvas);
            onMount = event => {
                this.mount = event.detail.display;
                this.mountName = event.detail.name;
                this.zoom = this.mount ? 1.1 : (data.race === 2 ? 0.8 : 1.1);
                this.render();
            };
            window.addEventListener('mount-selected', onMount);
            onLeave = event => {
                // Back/forward cache resumes the existing canvas.
                if (!event.persisted) this.destroy();
            };
            window.addEventListener('pagehide', onLeave);
        },
        render() {
            clearInterval(timer);
            viewer?.destroy();
            viewer = null;
            this.$refs.canvas.replaceChildren();
            this.loading = true;
            this.error = false;
            try {
                const {width, height} = this.$refs.canvas.getBoundingClientRect();
                const model = window.ZamModelViewer;
                viewer = createModelViewer({
                    type: model.WOW,
                    contentPath: data.contentPath,
                    container: $(this.$refs.canvas),
                    aspect: width / height,
                    hd: true,
                    charCustomization: {race: data.race, gender: data.gender, options: data.options, sheathMain: -1, sheathOff: -1},
                    cls: data.class,
                    items: data.items,
                    models: {type: model.Wow.Types.CHARACTER, id: races[data.race] + (data.gender ? 'female' : 'male')},
                    mount: {type: model.Wow.Types.NPC, id: this.mount},
                    defaultZoom: this.zoom,
                    rotationEnabled: true,
                    verticalRotationEnabled: false,
                    panningEnabled: false,
                    snowEnabled: true,
                    snowDensity: 0.00055,
                    snowSize: 1.75,
                    snowSpeed: 1.1,
                    snowWind: 1.3,
                    shadowEnabled: true,
                    shadowOpacity: 0.45,
                    shadowSize: 1,
                    wheelEventValidation: () => false,
                });
                const deadline = Date.now() + 60000;
                timer = setInterval(() => {
                    if (viewer?.method('isLoaded')) {
                        clearInterval(timer);
                        this.loading = false;
                    } else if (Date.now() > deadline) {
                        clearInterval(timer);
                        this.loading = false;
                        this.error = true;
                    }
                }, 200);
            } catch (err) {
                console.error('Character model failed', err);
                this.loading = false;
                this.error = true;
            }
        },
        reset() {
            this.zoom = this.mount ? 1.1 : (data.race === 2 ? 0.8 : 1.1);
            this.render();
        },
        zoomBy(amount) {
            this.zoom = Math.max(0.2, Math.min(2.5, this.zoom + amount));
            if (viewer?.renderer) viewer.setZoom(Math.log(this.zoom) / Math.log(1 + viewer.renderer.zoom.rateStep));
        },
        destroy() {
            clearInterval(timer);
            observer?.disconnect();
            window.removeEventListener('load', ready);
            window.removeEventListener('mount-selected', onMount);
            window.removeEventListener('pagehide', onLeave);
            viewer?.destroy();
            viewer = null;
        },
    };
}
