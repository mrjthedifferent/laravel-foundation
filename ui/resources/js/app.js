import './bootstrap';
import { initNavigationSearch } from './navigation-search';
import { initGlobalSearch } from './global-search';
import { initEchoSubscriptions } from './echo-subscribe';

import Alpine from 'alpinejs';
import { initSwal } from './swal-init';

window.Alpine = Alpine;
Alpine.start();

function runWhenReady() {
    if (typeof window.Swal !== 'undefined' && (window.jQuery || window.$)) {
        initSwal();
    } else {
        setTimeout(runWhenReady, 50);
    }
}
function runSearchInits() {
    initNavigationSearch();
    initGlobalSearch();
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', runWhenReady);
    document.addEventListener('DOMContentLoaded', runSearchInits);
    document.addEventListener('DOMContentLoaded', initEchoSubscriptions);
} else {
    runWhenReady();
    runSearchInits();
    initEchoSubscriptions();
}
