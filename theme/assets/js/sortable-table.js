/**
 * Makes a table sortable by its column headings. Uses Basix's sortable heading styles (th.sort-asc and
 * th.sort-desc) but not the Basix Table component, which adds its own search box and paging with fixed
 * English labels. Clicking a heading sorts ascending, again descending, and a third time restores the order.
 */
export function makeSortable(table) {
    const headRow = table.tHead?.rows[0];
    const body = table.tBodies[0];
    if (!headRow || !body) {
        return;
    }

    const original = [...body.rows];
    const collator = new Intl.Collator(undefined, { numeric: true, sensitivity: 'base' });
    const headings = [...headRow.cells];

    const sortBy = (column, direction) => {
        const rows = direction === 'none' ? original : [...original].sort((a, b) => {
            const result = collator.compare(a.cells[column]?.textContent.trim() ?? '', b.cells[column]?.textContent.trim() ?? '');
            return direction === 'ascending' ? result : -result;
        });
        body.append(...rows);
        headings.forEach((th, index) => {
            const active = index === column && direction !== 'none';
            th.setAttribute('aria-sort', active ? direction : 'none');
            th.classList.toggle('sort-asc', active && direction === 'ascending');
            th.classList.toggle('sort-desc', active && direction === 'descending');
        });
    };

    headings.forEach((th, column) => {
        const button = document.createElement('button');
        button.type = 'button';
        button.className = 'sort-button';
        button.textContent = th.textContent.trim();
        th.replaceChildren(button);
        th.classList.add('sortable');
        th.setAttribute('aria-sort', 'none');
        button.addEventListener('click', () => {
            const current = th.getAttribute('aria-sort');
            sortBy(column, current === 'none' ? 'ascending' : current === 'ascending' ? 'descending' : 'none');
        });
    });
}
