attribute vec4 aPosition;
attribute vec3 aAppearance;

uniform mat4 uProjection;
uniform float uDistance;
uniform float uSize;

varying mediump float vOpacity;
varying mediump float vAngle;
varying mediump float vShape;
varying mediump float vBlur;

void main() {
    // Camera-space weather keeps its direction while the character turns.
    vec3 view = vec3(aPosition.xy / uProjection[1][1], -aPosition.z) * uDistance;
    gl_Position = uProjection * vec4(view, 1.0);
    vBlur = smoothstep(0.12, 0.42, abs(aPosition.z - 1.08));
    gl_PointSize = clamp(aPosition.w * uSize * (1.0 + vBlur * 0.45) / aPosition.z, 1.8, 15.0);
    vOpacity = aAppearance.x * mix(1.0, 0.65, aPosition.z - 0.65) * (1.18 - vBlur * 0.48);
    vAngle = aAppearance.y;
    vShape = aAppearance.z;
}
