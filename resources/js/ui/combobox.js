import { anchorTo, onEscape, onOutsidePress } from './floating';

let uid = 0;
const ITEM = '[data-slot="combobox-item"]:not([data-disabled])';

/** Combobox behaviour for <x-ui.combobox>: typing filters the items, arrows move, Enter picks, Escape closes. Dispatches `change` with { value }. */
export default (config = {}) => ({
    open: false,
    id: `ui-combobox-${++uid}`,
    value: config.value ?? null,
    activeId: null,
    query: '',
    filtering: false,
    options: {},
    cleanup: [],

    get state() {
        return this.open ? 'open' : 'closed';
    },

    get label() {
        return this.value != null ? this.options[this.value]?.label : null;
    },

    register(value, label, searchLabel = label) {
        this.options[value] = { label, searchLabel };
        if (this.value === value && !this.query) this.query = label;
    },

    matches(label) {
        return !this.filtering || !this.query.trim() || label.toLowerCase().includes(this.query.trim().toLowerCase());
    },

    get visibleCount() {
        return Object.values(this.options).filter(({ searchLabel }) => this.matches(searchLabel)).length;
    },

    onInput() {
        if (config.clearOnInput && this.value != null && this.query !== this.label) {
            this.value = null;
            this.$dispatch('change', { value: null });
        }
        this.filtering = true;
        if (!this.open) this.show();
        this.$nextTick(() => this.highlight(this.items()[0]));
    },

    toggle() {
        this.open ? this.close() : this.show();
        this.$refs.input?.focus();
    },

    show() {
        this.open = true;
        this.$nextTick(() => this.mount());
    },

    close() {
        if (!this.open) return;
        this.open = false;
        this.filtering = false;
        if (!config.clearOnInput || this.value != null) this.query = this.label ?? '';
        this.activeId = null;
        this.unmount();
    },

    pick(value) {
        this.value = value;
        this.query = this.label ?? '';
        this.$dispatch('change', { value });
        this.close();
        this.$refs.input?.focus();
    },

    clear(focusInput = true) {
        this.value = null;
        this.query = '';
        this.filtering = false;
        this.$dispatch('change', { value: null });
        if (focusInput) this.$refs.input?.focus();
    },

    items() {
        return [...(this.$refs.content?.querySelectorAll(ITEM) ?? [])].filter((el) => el.offsetParent !== null);
    },

    highlight(item) {
        this.$refs.content?.querySelectorAll(ITEM).forEach((el) => el.removeAttribute('data-highlighted'));
        item?.setAttribute('data-highlighted', '');
        this.activeId = item ? (item.id ||= this.id + '-option-' + item.dataset.value) : null;
        item?.scrollIntoView({ block: 'nearest' });
    },

    mount() {
        const { anchor, wrapper, content } = this.$refs;
        if (!anchor || !wrapper) return;
        const options = JSON.parse(content.dataset.options || '{}');
        content.style.setProperty('--reka-combobox-trigger-width', `${anchor.offsetWidth}px`);
        this.cleanup.push(anchorTo({ anchor, wrapper, content, ...options }));
        this.cleanup.push(onOutsidePress([wrapper, anchor], () => this.close()));
        this.cleanup.push(onEscape(() => this.close()));
        requestAnimationFrame(() => this.highlight(this.items().find((el) => el.dataset.value === String(this.value)) ?? this.items()[0]));
    },

    unmount() {
        this.cleanup.forEach((fn) => fn());
        this.cleanup = [];
    },

    onKeydown(event) {
        const items = this.items();
        const current = items.findIndex((el) => el.hasAttribute('data-highlighted'));
        if (event.key === 'ArrowDown' || event.key === 'ArrowUp') {
            event.preventDefault();
            if (!this.open) return this.show();
            const step = event.key === 'ArrowDown' ? 1 : -1;
            this.highlight(items[(current + step + items.length) % items.length]);
        } else if (event.key === 'Enter' && this.open) {
            event.preventDefault();
            items[current]?.click();
        } else if (event.key === 'Tab') {
            this.close();
        }
    },
});
