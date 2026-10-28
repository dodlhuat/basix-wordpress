/**
 * Builds the filter form for the modal: a copy of the server-rendered form where the category
 * checkboxes become a Basix group picker and the date inputs become a Basix range date picker.
 * The result is still an ordinary GET form, so Apply just loads the filtered list.
 */

const isoDate = (date) => `${date.getFullYear()}-${String(date.getMonth() + 1).padStart(2, '0')}-${String(date.getDate()).padStart(2, '0')}`;

/** Turns a list of selected category ids into what the group picker expects. */
function toSelection(ids, groups) {
    const selection = { parentGroups: [], subgroups: [] };
    ids.forEach((id) => {
        if (groups.some((group) => group.id === id)) {
            selection.parentGroups.push(id);
            return;
        }
        const owner = groups.find((group) => (group.subgroups ?? []).some((sub) => sub.id === id));
        if (owner) {
            selection.subgroups.push({ groupId: owner.id, subgroupId: id });
        }
    });
    return selection;
}

/** The category ids behind a group picker selection. */
const toIds = (selection) => [...selection.parentGroups, ...selection.subgroups.map((sub) => sub.subgroupId)];

function hidden(name, value) {
    const input = document.createElement('input');
    input.type = 'hidden';
    input.name = name;
    input.value = value;
    return input;
}

function section(label) {
    const wrapper = document.createElement('div');
    wrapper.className = 'filter-section';
    const heading = document.createElement('p');
    heading.className = 'filter-section-title';
    heading.textContent = label;
    wrapper.append(heading);
    return wrapper;
}

export function buildFilterForm(source, config, { GroupPicker, DatePicker, iconBasePath }) {
    const text = config.text;
    const form = source.querySelector('form').cloneNode(true);
    form.querySelector('.filter-cats')?.remove();
    form.querySelector('.filter-dates')?.remove();

    const categories = hidden('categories', config.selected.join(','));
    const dateFrom = hidden('date_from', config.dateFrom);
    const dateTo = hidden('date_to', config.dateTo);

    // Categories
    const categorySection = section(text.categories);
    const pickerHost = document.createElement('div');
    pickerHost.className = 'filter-picker';
    categorySection.append(pickerHost);
    const picker = new GroupPicker(pickerHost, config.groups, {
        searchPlaceholder: text.search,
        selectAllLabel: text.selectAll,
        deselectLabel: text.clear,
        emptyLabel: text.empty,
        selectionPlaceholder: text.none,
        iconBasePath,
        onSelectionChange: (selection) => {
            categories.value = toIds(selection).join(',');
        },
    });
    picker.setSelection(toSelection(config.selected, config.groups));

    // Dates
    const dateSection = section(text.dates);
    const dateInput = document.createElement('input');
    dateInput.type = 'text';
    dateInput.className = 'datepicker-input';
    dateInput.readOnly = true;
    dateInput.placeholder = text.dateRange;
    dateInput.setAttribute('aria-label', text.dates);
    const show = (value) => new Date(`${value}T12:00:00`).toLocaleDateString(config.dateLocale);
    if (config.dateFrom || config.dateTo) {
        dateInput.value = `${config.dateFrom ? show(config.dateFrom) : '…'} - ${config.dateTo ? show(config.dateTo) : '…'}`;
    }
    dateSection.append(dateInput);
    new DatePicker(dateInput, {
        mode: 'range',
        startDay: config.startDay,
        locales: { days: config.days, months: config.months },
        format: (date) => date.toLocaleDateString(config.dateLocale),
        onSelect: (range) => {
            dateFrom.value = range?.start ? isoDate(range.start) : '';
            dateTo.value = range?.end ? isoDate(range.end) : '';
        },
    });

    form.prepend(categorySection, dateSection, categories, dateFrom, dateTo);
    return form;
}
