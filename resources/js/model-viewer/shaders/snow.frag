precision mediump float;

uniform mediump vec2 uViewport;

varying mediump float vOpacity;
varying mediump float vAngle;
varying mediump float vShape;
varying mediump float vBlur;

void main() {
    vec2 p = gl_PointCoord - 0.5;
    p = mat2(cos(vAngle), -sin(vAngle), sin(vAngle), cos(vAngle)) * p;
    float radius = length(p);
    float angle = atan(p.y, p.x);
    float edge = 0.24 + 0.045 * sin(angle * 3.0 + vShape) + 0.025 * cos(angle * 5.0 - vShape);
    float softness = mix(0.025, 0.18, vBlur);
    float flake = 1.0 - smoothstep(edge - softness, edge + softness, radius);
    vec2 uv = gl_FragCoord.xy / uViewport;
    vec2 fade = smoothstep(vec2(0.0), vec2(0.08, 0.16), uv)
        * smoothstep(vec2(0.0), vec2(0.08, 0.06), 1.0 - uv);
    gl_FragColor = vec4(1.0, 1.0, 1.0, flake * vOpacity * fade.x * fade.y);
}
