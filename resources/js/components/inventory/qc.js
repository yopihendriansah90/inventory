import { notify, reportInvalidField } from "../../notifications";

export default (config) => ({
    variantOptions: config.variants,
    vendorOptions: config.vendors,
    selectedVariantId: config.selected,
    selectedVendorId: config.vendor,
    lines: config.lines,
    step: config.step,
    reference: config.reference,
    receivedOn: config.receivedOn,
    reviewVendorLabel: '',
    savingVariant: false,
    variantErrors: {},

    get selectedVariant() {
        return this.variantOptions.find(({ id }) => id === this.selectedVariantId);
    },

    get selectedVendor() {
        return this.vendorOptions.find(({ id }) => id === this.selectedVendorId);
    },

    selectVariant(id) {
        this.selectedVariantId = id;
    },

    selectVendor(id) {
        this.selectedVendorId = id;
    },

    addLine() {
        const variant = this.selectedVariant;
        if (!variant) return;
        const existing = this.lines.find((line) => line.inventory_variant_id === variant.id);
        if (!existing) {
            this.lines.unshift({ inventory_variant_id: variant.id, received_quantity: '', failed_quantity: 0, notes: '' });
        }
        this.$root.querySelector('#variant-search')?.dispatchEvent(new CustomEvent('inventory-line-added', { bubbles: true }));
        this.$nextTick(() => this.$root.querySelector('#ticket-quantity-' + variant.id)?.focus());
    },

    removeLine(index) {
        this.lines.splice(index, 1);
        this.$nextTick(() => this.$root.querySelector('#variant-search')?.focus());
    },

    labelFor(id) {
        return this.variantOptions.find((option) => option.id === id)?.label ?? '';
    },

    passed(line) {
        return Math.max(0, (Number(line.received_quantity) || 0) - (Number(line.failed_quantity) || 0));
    },

    total(field) {
        return this.lines.reduce((sum, line) => sum + (field === 'passed' ? this.passed(line) : Number(line[field]) || 0), 0);
    },

    next() {
        const warning = this.step === 1 && !this.selectedVendor
            ? config.messages.vendor
            : this.step === 2 && !this.lines.length ? config.messages.lines : '';
        if (warning) {
            this.$root.querySelector(this.step === 1 ? '#vendor-search' : '#variant-search')?.focus();
            notify('warning', warning);
            return;
        }

        const panel = this.$root.querySelector('[data-ticket-step="' + this.step + '"]');
        const invalid = Array.from(panel.querySelectorAll('[form="inspection-ticket"]'))
            .find((input) => !input.validity.valid);
        if (invalid) {
            reportInvalidField(invalid);
            return;
        }

        if (this.step === 2) this.reviewVendorLabel = this.selectedVendor?.label ?? '';
        this.goTo(this.step + 1);
    },

    goTo(step) {
        this.step = step;
        this.$nextTick(() => this.$root.querySelector('[data-ticket-step="' + step + '"] h3')?.focus());
    },

    async saveOption(form, kind, onSuccess) {
        if (this.savingVariant) return;

        this.savingVariant = true;
        this.variantErrors = {};

        try {
            const response = await fetch(form.action, {
                method: 'POST',
                headers: { Accept: 'application/json' },
                body: new FormData(form),
            });
            if (response.status === 422) {
                this.variantErrors = (await response.json()).errors ?? {};
                form.elements.namedItem(Object.keys(this.variantErrors)[0] ?? '')?.focus?.();
                notify('error', Object.values(this.variantErrors).flat()[0] ?? config.messages.saveError);
                return;
            }
            if (!response.ok) throw new Error('Could not save variant');

            const option = await response.json();
            const options = kind === 'vendor' ? this.vendorOptions : this.variantOptions;
            if (!options.some(({ id }) => id === option.id)) {
                options.push(option);
            }
            form.reset();
            onSuccess({ ...option, kind });
            const created = response.status === 201;
            notify(created ? 'success' : 'info', config.messages[kind + (created ? '_added' : '_selected')]);
        } catch {
            notify('error', config.messages.saveError);
        } finally {
            this.savingVariant = false;
        }
    },
});
