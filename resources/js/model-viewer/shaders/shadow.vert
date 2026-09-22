attribute vec2 aPosition;

uniform mat4 uView;
uniform mat4 uProjection;
uniform float uGround;
uniform float uRadius;

varying mediump vec2 vPosition;

void main() {
    vec4 view = uView * vec4(0.0, 0.0, uGround, 1.0);
    // A flattened billboard stays visible with the armory's level camera.
    view.xy += aPosition * vec2(uRadius, uRadius * 0.2);
    gl_Position = uProjection * view;
    vPosition = aPosition;
}
