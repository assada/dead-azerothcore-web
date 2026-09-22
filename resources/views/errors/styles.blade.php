* { box-sizing: border-box; }
html { min-height: 100%; background: #151410; color: #eee5d2; }
body { margin: 0; padding: 0 32px; font-family: Arial, Helvetica, sans-serif; }
.error-header, .error-scene, .error-footer { max-width: 1120px; margin-inline: auto; }
.error-header { padding: 28px 0 24px; }
.error-header a { display: inline-block; }
.error-header img { display: block; width: 224px; height: auto; }
.error-scene { position: relative; display: flex; align-items: center; min-height: 560px; overflow: hidden; background: #101a20 url('/images/northrend-path.webp') center / cover; border: 1px solid #817053; border-radius: 4px; box-shadow: 0 20px 70px #0005; }
.error-scene::before { content: ''; position: absolute; inset: 0; background: linear-gradient(90deg, #0c1116e6 0%, #0c11169e 35%, #0c111610 70%); }
.error-scene::after { content: ''; position: absolute; inset: 5px; border: 1px solid #b8a07840; pointer-events: none; }
.error-content { position: relative; z-index: 1; width: 520px; padding: 56px 60px 64px; }
.error-code { margin: 0 0 16px; color: #c3a36a; font: 88px / 1 Georgia, 'Times New Roman', serif; letter-spacing: -4px; text-shadow: 0 2px 0 #000, 0 0 30px #d9b87520; }
h1 { margin: 0 0 20px; color: #f2e4c6; font: 34px / 1.16 Georgia, 'Times New Roman', serif; text-wrap: balance; }
.error-message { max-width: 330px; margin: 0 0 32px; color: #c3c4bf; font-size: 15px; line-height: 1.7; }
.error-action { display: inline-flex; align-items: center; justify-content: center; min-height: 44px; padding: 10px 26px; border: 1px solid #a07b42; border-radius: 3px; background: #61221b; color: #ffe5a8; box-shadow: inset 0 1px #ffffff20, 0 2px 6px #0005; text-decoration: none; font-size: 14px; font-weight: 600; transition: background .15s; }
.error-action:hover { background: #7b2a20; color: #fff0bf; }
.error-action:active { background: #491a15; }
a:focus-visible { outline: 2px solid #e6c781; outline-offset: 5px; }
.error-footer { display: flex; justify-content: space-between; gap: 16px; padding: 22px 0 30px; color: #938977; font-size: 12px; }
.error-footer span:first-child { color: #b5a387; font-family: Georgia, 'Times New Roman', serif; }
@media (max-width: 640px) {
    body { padding: 0 18px; }
    .error-header { padding: 20px 0 18px; }
    .error-header img { width: 192px; }
    .error-scene { min-height: 540px; align-items: flex-end; background-position: 66% center; }
    .error-scene::before { background: linear-gradient(0deg, #0c1116fa 0%, #0c1116c9 40%, #0c111608 100%); }
    .error-content { width: 100%; padding: 150px 28px 36px; }
    .error-code { font-size: 64px; margin-bottom: 12px; }
    h1 { font-size: 29px; }
    .error-message { margin-bottom: 26px; font-size: 14px; }
    .error-footer { font-size: 11px; }
}
@media (prefers-reduced-motion: reduce) { .error-action { transition: none; } }
