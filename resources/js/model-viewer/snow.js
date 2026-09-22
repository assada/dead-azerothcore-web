import createProgram from './program';
import vertex from './shaders/snow.vert?raw';
import fragment from './shaders/snow.frag?raw';

export default class Snow {
    constructor(renderer) {
        this.gl = renderer.context;
        const gl = this.gl;
        this.program = createProgram(renderer, vertex, fragment);
        this.buffer = gl.createBuffer();
        this.count = 600;
        this.elapsed = 0;
        this.density = renderer.options.snowDensity ?? 0.00055;
        this.size = renderer.options.snowSize ?? 1;
        this.speed = renderer.options.snowSpeed ?? 1;
        this.wind = renderer.options.snowWind ?? 1;
        this.particles = Array.from({length: this.count}, () => {
            const size = Math.random();
            const particle = {
                size: 3 + size * 3,
                opacity: 0.14 + Math.random() ** 1.5 * 0.58,
                fall: 0.12 + size ** 1.3 * 0.32 + Math.random() * 0.08,
                drag: 1.2 + (1 - size) * 3,
                phase: Math.random() * Math.PI * 2,
                spin: (Math.random() - 0.5) * 2,
            };
            this.spawn(particle, renderer.width / renderer.height, true);
            return particle;
        });
        this.vertices = new Float32Array(this.count * 7);
        gl.bindBuffer(gl.ARRAY_BUFFER, this.buffer);
        gl.bufferData(gl.ARRAY_BUFFER, this.vertices.byteLength, gl.DYNAMIC_DRAW);
        gl.bindBuffer(gl.ARRAY_BUFFER, null);
        this.position = gl.getAttribLocation(this.program, 'aPosition');
        this.appearance = gl.getAttribLocation(this.program, 'aAppearance');
        this.uniforms = Object.fromEntries(['uProjection', 'uViewport', 'uDistance', 'uSize']
            .map(name => [name, gl.getUniformLocation(this.program, name)]));
    }
    spawn(particle, aspect, initial = false) {
        particle.z = 0.65 + Math.random();
        particle.x = (Math.random() * 2 - 1) * aspect * particle.z * 1.2;
        particle.y = (initial ? Math.random() * 2 - 1 : 1) * particle.z * 1.2;
        particle.vx = this.wind * (0.08 + Math.random() * 0.16);
        particle.vy = -particle.fall;
        particle.angle = particle.phase;
    }
    update(dt, aspect, count) {
        this.elapsed += dt;
        const time = this.elapsed;
        const gust = 0.16 + 0.22 * Math.sin(time * 0.55) + 0.1 * Math.sin(time * 1.43);
        for (let i = 0; i < count; i++) {
            const p = this.particles[i];
            const eddy = Math.sin(p.x * 2.1 + p.y * 1.7 - time * 0.9 + p.z * 3);
            const flutter = Math.sin(time * (1.5 + p.drag * 0.3) + p.phase);
            const airX = this.wind * (gust + eddy * 0.18) + flutter * 0.035;
            const airY = this.wind * Math.cos(p.x * 1.3 - p.y * 2 + time * 0.7) * 0.09;
            const response = 1 - Math.exp(-p.drag * dt);
            p.vx += (airX - p.vx) * response;
            p.vy += (airY - p.fall - p.vy) * response;
            p.x += p.vx * dt;
            p.y += p.vy * dt;
            p.angle += (p.spin + eddy * this.wind) * dt;
            if (p.y < -p.z * 1.2) this.spawn(p, aspect);
            const edge = aspect * p.z * 1.2;
            if (p.x > edge) p.x -= edge * 2;
            if (p.x < -edge) p.x += edge * 2;
            const offset = i * 7;
            this.vertices[offset] = p.x;
            this.vertices[offset + 1] = p.y;
            this.vertices[offset + 2] = p.z;
            this.vertices[offset + 3] = p.size;
            this.vertices[offset + 4] = p.opacity * (0.82 + 0.18 * Math.sin(p.angle));
            this.vertices[offset + 5] = p.angle;
            this.vertices[offset + 6] = p.phase;
        }
    }
    draw(renderer) {
        const gl = this.gl, u = this.uniforms;
        const count = Math.min(this.count, Math.round(renderer.width * renderer.height * this.density));
        this.update(Math.min(renderer.delta, 0.05) * this.speed, renderer.width / renderer.height, count);
        gl.useProgram(this.program);
        gl.bindBuffer(gl.ARRAY_BUFFER, this.buffer);
        gl.bufferSubData(gl.ARRAY_BUFFER, 0, this.vertices.subarray(0, count * 7));
        gl.enableVertexAttribArray(this.position);
        gl.vertexAttribPointer(this.position, 4, gl.FLOAT, false, 28, 0);
        gl.enableVertexAttribArray(this.appearance);
        gl.vertexAttribPointer(this.appearance, 3, gl.FLOAT, false, 28, 16);
        gl.uniformMatrix4fv(u.uProjection, false, renderer.projMatrix);
        gl.uniform2f(u.uViewport, renderer.width, renderer.height);
        gl.uniform1f(u.uDistance, Math.hypot(renderer.eye[0] - renderer.target[0], renderer.eye[1] - renderer.target[1], renderer.eye[2] - renderer.target[2]));
        gl.uniform1f(u.uSize, this.size);
        gl.enable(gl.DEPTH_TEST);
        gl.depthMask(false);
        gl.enable(gl.BLEND);
        gl.blendEquation(gl.FUNC_ADD);
        gl.blendFuncSeparate(gl.SRC_ALPHA, gl.ONE_MINUS_SRC_ALPHA, gl.ONE, gl.ONE_MINUS_SRC_ALPHA);
        gl.drawArrays(gl.POINTS, 0, count);
        gl.depthMask(true);
        gl.disableVertexAttribArray(this.position);
        gl.disableVertexAttribArray(this.appearance);
        gl.bindBuffer(gl.ARRAY_BUFFER, null);
        gl.useProgram(null);
    }
    destroy() {
        this.gl.deleteBuffer(this.buffer);
        this.gl.deleteProgram(this.program);
    }
}
