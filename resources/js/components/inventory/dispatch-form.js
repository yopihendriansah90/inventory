import { notify, reportInvalidField } from "../../notifications";

export default (variants, previousLines, errors, emptyMessage, initialStep) => {
    const variantsById = new Map(variants.map((variant) => [variant.id, variant]));

    return {
        query: "",
        step: initialStep,
        preview: {},
        lines: Object.entries(previousLines ?? {}).filter(([, line]) => variantsById.has(Number(line?.inventory_variant_id)))
            .map(([key, line]) => ({
                ...variantsById.get(Number(line.inventory_variant_id)),
                quantity: line.quantity,
                error: errors["lines." + key + ".quantity"]?.[0],
            })),

        get matches() {
            const terms = this.query.trim().toLowerCase().split(/\s+/);
            const selected = new Set(this.lines.map((line) => line.id));
            return variants.filter((variant) => !selected.has(variant.id)
                && terms.every((term) => variant.searchLabel.toLowerCase().includes(term))).slice(0, 12);
        },

        add(item) {
            if (item.available <= 0 || this.lines.some((line) => line.id === item.id)) return;
            this.lines.unshift({ ...item, quantity: 1 });
            this.query = "";
            this.$nextTick(() => document.getElementById("dispatch-quantity-" + item.id)?.select());
        },

        remove(id) {
            this.lines = this.lines.filter((line) => line.id !== id);
            this.$refs.picker.focus();
        },

        goTo(step, focusField) {
            this.step = step;
            this.$nextTick(() => {
                (focusField ?? this.$root.querySelector('[data-ticket-step="' + step + '"] h3'))?.focus();
            });
        },

        validateStep(step) {
            if (step === 2 && !this.lines.length) {
                this.goTo(2, this.$refs.picker);
                notify("warning", emptyMessage);
                return false;
            }

            const fields = this.$root.querySelectorAll('[data-ticket-step="' + step + '"] input, [data-ticket-step="' + step + '"] textarea');
            const invalid = Array.from(fields).find((field) => !field.validity.valid);
            if (!invalid) return true;
            this.step = step;
            this.$nextTick(() => reportInvalidField(invalid));
            return false;
        },

        next() {
            if (!this.validateStep(this.step)) return;
            if (this.step === 2) {
                const fields = this.$refs.identity.querySelectorAll("[name]");
                this.preview = Object.fromEntries(Array.from(fields, ({ name, value }) => [name, value.trim()]));
            }
            this.goTo(this.step + 1);
        },

        submit(event) {
            if (this.step < 3) {
                event.preventDefault();
                this.next();
            } else if (!this.validateStep(1) || !this.validateStep(2)) {
                event.preventDefault();
            }
        },
    };
};
