import DataTable from 'datatables.net';

export default function dataTable(root) {
    const select = root.querySelector('.table-column');
    const form = root.querySelector('.table-filters form');
    const error = root.querySelector('.table-error');
    const retry = error.querySelector('[data-retry]');
    const login = error.querySelector('[data-login]');
    const columns = [...root.querySelectorAll('th')].map(th => ({
        data: th.dataset.key, className: th.className, orderable: th.dataset.sort !== 'none',
        orderSequence: th.dataset.sort === 'desc' ? ['desc', 'asc'] : ['asc', 'desc'],
    }));
    let filters = {};
    const table = new DataTable(root.querySelector('table'), {
        serverSide: true, processing: true, autoWidth: false, pageLength: 25,
        order: [JSON.parse(root.dataset.order)], orderMulti: false, searchDelay: 350,
        stateSave: true, stateDuration: -1,
        ajax: {
            url: root.dataset.url, headers: {Accept: 'application/json'},
            data: ({draw, start, length, search, order}) => ({draw, start, length, search, order, ...filters}),
        },
        columns,
        layout: {
            top2Start: root.querySelector('.table-heading'), top2End: 'search',
            top: root.querySelector('.table-filters'), topStart: null, topEnd: null,
            bottomStart: 'info', bottomEnd: {paging: {type: 'simple_numbers', buttons: 5, firstLast: false}},
        },
        language: {
            search: 'Search ' + root.dataset.entries, searchPlaceholder: root.dataset.search,
            info: '_START_–_END_ of _TOTAL_ _ENTRIES-TOTAL_', infoFiltered: '', infoEmpty: '',
            entries: {1: root.dataset.entry, _: root.dataset.entries},
            emptyTable: 'No ' + root.dataset.entries + ' yet.', zeroRecords: 'No ' + root.dataset.entries + ' found.',
            processing: 'Loading…', loadingRecords: '', thousands: ',', paginate: {previous: '‹', next: '›'},
            aria: {orderable: ': Sort', orderableReverse: ': Reverse sort', orderableRemove: ': Reverse sort'},
        },
        stateSaveParams(settings, data) { data.filters = filters; },
        stateLoadParams(settings, data) {
            filters = data.filters ?? {};
            if (form) Object.entries(filters).forEach(([key, value]) => { if (form.elements[key]) form.elements[key].value = value; });
        },
        on: {
            page() { if (root.getBoundingClientRect().top < 0) root.scrollIntoView({block: 'start'}); },
            xhr(event, settings, json, xhr) {
                error.hidden = !!json;
                if (!json) {
                    const expired = xhr?.status === 401;
                    error.querySelector('span').textContent = expired ? 'Your session has expired.' : 'Could not load ' + root.dataset.entries + '.';
                    retry.hidden = expired;
                    login.hidden = !expired;
                    return false;
                }
            },
        },
        drawCallback() {
            const api = this.api();
            const column = api.order()[0][0];
            root.querySelectorAll('.is-selected-column').forEach(cell => cell.classList.remove('is-selected-column'));
            const nodes = [...api.column(column).nodes().toArray(), api.column(column).header()];
            nodes.forEach(cell => cell.classList.add('is-selected-column'));
            if (select) {
                const available = [...select.options].some(option => Number(option.value) === column);
                if (available) select.value = String(column);
                else {
                    const mobileColumn = Number(select.value);
                    [...api.column(mobileColumn).nodes().toArray(), api.column(mobileColumn).header()].forEach(cell => cell.classList.add('is-selected-column'));
                }
            }
            root.querySelector('.dt-paging').hidden = api.page.info().pages <= 1;
        },
        initComplete() { root.querySelector('.dt-search input').maxLength = 100; },
    });
    select?.addEventListener('change', () => {
        const column = Number(select.value);
        table.order([[column, columns[column].orderSequence[0]]]).draw();
    });
    form?.addEventListener('submit', event => {
        event.preventDefault();
        const min = form.elements.level_min, max = form.elements.level_max;
        max.setCustomValidity(min.value && max.value && Number(min.value) > Number(max.value) ? 'Maximum level must be at least the minimum.' : '');
        if (!form.reportValidity()) return;
        filters = Object.fromEntries(new FormData(form));
        table.ajax.reload();
    });
    form?.addEventListener('input', () => form.elements.level_max.setCustomValidity(''));
    form?.addEventListener('reset', () => {
        filters = {};
        form.elements.level_max.setCustomValidity('');
        table.ajax.reload();
    });
    retry.addEventListener('click', () => table.ajax.reload(null, false));
}
