<div class="model-stage" x-data="characterModel" x-init="initViewer()">
    <div class="model-canvas" x-ref="canvas" role="img" aria-label="3D model of {{ $character->name }}"></div>
    <p class="model-message" x-show="loading" role="status">Loading character…</p>
    <p class="model-message" x-show="error" x-cloak role="alert">The 3D model could not load. <button type="button" @click="render()">Retry</button></p>
    <div class="model-controls" aria-label="3D model controls">
        <button type="button" @click="zoomBy(-0.15)" aria-label="Zoom out">−</button>
        <button type="button" @click="reset()" aria-label="Reset model view">↺</button>
        <button type="button" @click="zoomBy(0.15)" aria-label="Zoom in">+</button>
    </div>
    @if($tab === 'mounts')<p class="mount-preview-name" x-text="mountName"></p>@endif
</div>
