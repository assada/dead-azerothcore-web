import Snow from './snow';
import GroundShadow from './shadow';

let Viewer;

export default function createModelViewer(options) {
    if (!Viewer) {
        const Base = window.ZamModelViewer;
        class Renderer extends Base.WebGL {
            init() {
                this.motion = window.matchMedia('(prefers-reduced-motion: reduce)');
                this.visible = true;
                this.observer = new IntersectionObserver(([entry]) => { this.visible = entry.isIntersecting; });
                this.observer.observe(this.canvas[0]);
                try {
                    if (this.options.snowEnabled) this.snow = new Snow(this);
                    if (this.options.shadowEnabled) this.shadow = new GroundShadow(this);
                } catch (err) {
                    console.warn('Model effects failed', err);
                }
                super.init();
            }
            draw(time) {
                super.draw(time);
                if (!this.visible || document.hidden || !this.models[0]?.isLoaded()) return;
                this.shadow?.draw(this);
                if (!this.motion.matches) this.snow?.draw(this);
            }
            onMouseDown(event) {
                if (this.options.panningEnabled === false && (event.which === 3 || event.ctrlKey)) return;
                super.onMouseDown(event);
            }
            onMouseMove(event) {
                const zenith = this.zenith;
                super.onMouseMove(event);
                if (this.options.verticalRotationEnabled === false) this.zenith = zenith;
            }
            destroy() {
                this.observer?.disconnect();
                this.snow?.destroy();
                this.shadow?.destroy();
                super.destroy();
            }
        }
        Viewer = class extends Base {
            init(width, height) {
                this.mode = Base.WEBGL;
                this.renderer = new Renderer(this);
                this.renderer.resize(width, height);
                if (!this.renderer.context) throw new Error('WebGL is unavailable');
                this.renderer.init();
            }
        };
    }
    return new Viewer(options);
}
