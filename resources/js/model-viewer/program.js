export default function createProgram(renderer, vertex, fragment) {
    const gl = renderer.context;
    const shaders = [];
    const program = gl.createProgram();
    try {
        shaders.push(renderer.compileShader(gl.VERTEX_SHADER, vertex));
        shaders.push(renderer.compileShader(gl.FRAGMENT_SHADER, fragment));
        shaders.forEach(shader => gl.attachShader(program, shader));
        gl.linkProgram(program);
        if (!gl.getProgramParameter(program, gl.LINK_STATUS)) throw new Error(gl.getProgramInfoLog(program));
        return program;
    } catch (err) {
        gl.deleteProgram(program);
        throw err;
    } finally {
        shaders.forEach(shader => gl.deleteShader(shader));
    }
}
