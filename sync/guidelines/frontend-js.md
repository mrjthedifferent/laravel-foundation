## Front-end Behaviour (JavaScript)

The package ships its own small script, `assets/js/foundation.js` (loaded in the layout with jQuery). It replaces what Bootstrap's JavaScript used to do.
**There is no Bootstrap: `data-bs-*` attributes, `window.bootstrap` and `shown.bs.*` events do not exist.** Behaviour is declared in markup with
`data-fd-*` attributes and `swal-*` classes; write script only when markup cannot express it.

Libraries available on every page: jQuery, Select2, SweetAlert2, Alpine (project bundle). Quill and Ace load on the pages that need them.

---

### Declarative behaviour

| Markup | Does |
|---|---|
| `data-fd-toggle="modal" data-fd-target="#id"` | Opens the modal `#id` |
| `data-fd-toggle="offcanvas" data-fd-target="#id"` | Opens the side drawer `#id` |
| `data-fd-dismiss="modal"` / `"offcanvas"` / `"alert"` | Closes the enclosing modal / drawer / removes the alert |
| `data-fd-toggle="dropdown"` | Opens the `.dropdown-menu` that follows (or is inside the same `.dropdown`). `data-fd-auto-close="outside"` keeps it open for clicks inside |
| `data-fd-toggle="collapse" data-fd-target="#id"` | Shows/hides `#id` (the `show` class) |
| `data-fd-toggle="tab" data-fd-target="#pane"` (or `href="#pane"`) | Activates a tab; the pane is a `.tab-pane` inside `.tab-content` |
| `data-fd-toggle="tooltip" title="…"` | Tooltip on hover/focus. `data-fd-placement="top\|bottom\|left\|right"` |
| `data-fd-toggle="popover" data-fd-title data-fd-content [data-fd-html="true"]` | Click popover (HTML is sanitised) |
| `data-fd-backdrop="static"` / `data-fd-keyboard="false"` (on a modal) | Backdrop click / Esc do not close it |

```blade
<button type="button" class="btn btn-primary" data-fd-toggle="modal" data-fd-target="#create-modal">New</button>

<ul class="nav nav-tabs" role="tablist">
    <li class="nav-item"><button class="nav-link active" data-fd-toggle="tab" data-fd-target="#general" type="button">General</button></li>
    <li class="nav-item"><button class="nav-link" data-fd-toggle="tab" data-fd-target="#mail" type="button">Mail</button></li>
</ul>
<div class="tab-content">
    <div class="tab-pane fade show active" id="general">…</div>
    <div class="tab-pane fade" id="mail">…</div>
</div>
```

Do not use `data-bs-*`. A dropdown menu is moved to `<body>` while open (so no ancestor can clip or offset it) and returned when it closes — do not
rely on its DOM position while it is open.

### Events

Each component dispatches a bubbling `CustomEvent`; `event.target` is the element. `fd:modal-show` `fd:modal-shown` `fd:modal-hide` `fd:modal-hidden`,
`fd:offcanvas-show/shown/hide/hidden`, `fd:collapse-shown/hidden`, `fd:tab-shown` (`detail.relatedTarget` = previous tab), `fd:dropdown-shown/hidden`.

```javascript
document.addEventListener('fd:modal-shown', e => e.target.querySelector('input')?.focus());
document.querySelector('#settingsTabs').addEventListener('fd:tab-shown', e => localStorage.setItem('tab', e.target.getAttribute('href')));
```

### Keyboard and focus

Modals and drawers keep Tab inside while open and return focus to the trigger when they close; Escape closes them (unless a modal is `static`). In an open dropdown,
ArrowUp/ArrowDown/Home/End move between items (disabled ones are skipped) and Escape closes it; ArrowDown on a closed toggle opens it and focuses the first item.

### Programmatic API

```javascript
Foundation.modal(el).show();  Foundation.modal(el).hide();  Foundation.modal(el).toggle();
Foundation.offcanvas(el).show();   Foundation.dropdown(toggleEl).toggleMenu();
Foundation.collapse(el).toggle();  Foundation.tab(linkEl).show();
Foundation.initSelects(scopeEl);           // (re)wire Select2 for select.select inside scopeEl (done automatically on load and when a modal/drawer opens)
Foundation.initTableLabels(scopeEl);       // re-label stacked-table cells after rows are injected by AJAX
Foundation.applyColorMode('light|dark|auto');  Foundation.applyDirection('ltr|rtl');
```

jQuery shims keep the familiar calls working: `$('#id').modal('show'|'hide')`, `.offcanvas()`, `.collapse('toggle')`, `.tab('show')`, `.dropdown('show'|'hide')`.

### Confirmations, requests and toasts (SweetAlert2 classes)

| Class on a link/button | Does |
|---|---|
| `swal-confirm` + `data-text="Activate this?"` | Confirm, then follow the `href` (inside a `<form>` it submits the form; with `data-method` or `text-danger` it sends that method) |
| `swal-delete` + `data-text` | Confirm, then send `DELETE` to the `href`/`data-url` |
| `swal-post` + `data-text` (+ `data-method="POST\|PATCH"`, `data-url`) | Confirm, then send that method |

Server messages: return `->with('success'|'error'|'warning'|'info'|'message'|'status', …)`; the layout shows it. From JavaScript:
`toast('success'|'error'|'warning'|'info', 'Title', 'Optional text')`. Never `window.confirm()`, never hand-built toasts, never inline `Swal.fire()`.

### Select2

Give a `<select>` the class `select` (`<x-form.select class="select" data-placeholder="…">`). Options: `data-minimum-results-for-search`; clearable when it has
a placeholder and is not required. Inside a modal or drawer it attaches to that overlay automatically. Re-run `Foundation.initSelects(container)` after
injecting selects by AJAX.

### Built-in widgets you get for free

- **Filter bar** (`<x-search-card>`): lifts the `search` field into the bar, builds removable chips, remembers open/closed per page.
- **File drop zone** (`<x-form.file>`): file name and image preview; drag-and-drop uses the native input.
- **Stacked tables** (`<x-table-view-pagination>`): copies column headings to `data-label` on cells for the phone layout.
- **Dashboard editor** (`[data-fd-dashboard]`): see `dashboard.md`.
- **Section nav** (`.fd-form-nav`): highlights the section in view.
- **Theme**: colour mode and direction are applied before first paint from `window.__THEME__` and `localStorage`; sidebar mini state is remembered.

### Rules

- Put page scripts in `@push('scripts')` (they run after jQuery, Select2 and SweetAlert are loaded), page CSS in `@push('styles')`.
- Select by id or a `js-*` class you own, not by styling classes (`.btn`, `.card`).
- AJAX that returns HTML must be re-wired: `Foundation.initSelects(el)` / `Foundation.initTableLabels(el)`; modals and tabs need nothing (delegated).
- Send the CSRF token (`meta[name=csrf-token]`) on every non-GET request; jQuery and axios are already configured to do so.
