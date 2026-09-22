import createProgram from './program';
import vertex from './shaders/shadow.vert?raw';
import fragment from './shaders/shadow.frag?raw';

export default class GroundShadow {
    constructor(renderer) {
        this.gl = renderer.context;
        const gl = this.gl;
        this.program = createProgram(renderer, vertex, fragment);
        this.buffer = gl.createBuffer();
        this.opacity = renderer.options.shadowOpacity ?? 0.45;
        this.size = renderer.options.shadowSize ?? 1;
        gl.bindBuffer(gl.ARRAY_BUFFER, this.buffer);
        gl.bufferData(gl.ARRAY_BUFFER, new Float32Array([-1,-1, 1,-1, -1,1, 1,1]), gl.STATIC_DRAW);
        gl.bindBuffer(gl.ARRAY_BUFFER, null);
        this.position = gl.getAttribLocation(this.program, 'aPosition');
        this.uniforms = Object.fromEntries(['uView', 'uProjection', 'uGround', 'uRadius', 'uOpacity']
            .map(name => [name, gl.getUniformLocation(this.program, name)]));
    }
    draw(renderer) {
        const gl = this.gl, u = this.uniforms;
        const height = renderer.models[0].boundsSize[2];
        gl.useProgram(this.program);
        gl.bindBuffer(gl.ARRAY_BUFFER, this.buffer);
        gl.enableVertexAttribArray(this.position);
        gl.vertexAttribPointer(this.position, 2, gl.FLOAT, false, 0, 0);
        gl.uniformMatrix4fv(u.uView, false, renderer.viewMatrix);
        gl.uniformMatrix4fv(u.uProjection, false, renderer.projMatrix);
        gl.uniform1f(u.uGround, -height * 0.49);
        gl.uniform1f(u.uRadius, height * 0.32 * this.size);
        gl.uniform1f(u.uOpacity, this.opacity);
        gl.enable(gl.DEPTH_TEST);
        gl.depthMask(false);
        gl.disable(gl.CULL_FACE);
        gl.enable(gl.BLEND);
        gl.blendEquation(gl.FUNC_ADD);
        gl.blendFuncSeparate(gl.SRC_ALPHA, gl.ONE_MINUS_SRC_ALPHA, gl.ONE, gl.ONE_MINUS_SRC_ALPHA);
        gl.drawArrays(gl.TRIANGLE_STRIP, 0, 4);
        gl.depthMask(true);
        gl.disableVertexAttribArray(this.position);
        gl.bindBuffer(gl.ARRAY_BUFFER, null);
        gl.useProgram(null);
    }
    destroy() {
        this.gl.deleteBuffer(this.buffer);
        this.gl.deleteProgram(this.program);
    }
}
