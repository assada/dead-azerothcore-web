precision mediump float;

uniform float uOpacity;
varying mediump vec2 vPosition;

void main() {
    float distance = length(vPosition);
    float alpha = exp(-distance * distance * 3.0) * (1.0 - smoothstep(0.65, 1.0, distance));
    gl_FragColor = vec4(0.0, 0.0, 0.0, alpha * uOpacity);
}
