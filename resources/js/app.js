import './bootstrap';

import Alpine from 'alpinejs';
import characterModel from './character-model';
import characterActions from './character-actions';

Alpine.data('characterModel', characterModel);
Alpine.data('characterActions', characterActions);

window.Alpine = Alpine;

Alpine.start();

const tables = document.querySelectorAll('[data-table]');
if (tables.length) import('./data-table').then(({default: init}) => tables.forEach(init));
