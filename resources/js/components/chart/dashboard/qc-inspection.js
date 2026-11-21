import { mountChart, readConfig } from "../helpers";

export default (element) => {
    const { days, passed, failed, locale } = readConfig(element);
    const numbers = new Intl.NumberFormat(locale);
    const axes = new Intl.NumberFormat(locale, { notation: "compact", maximumFractionDigits: 0 });
    const max = Math.max(...days.map((day) => Math.max(day.passed, day.failed)));
    const styles = getComputedStyle(document.documentElement);
    const color = (token) => styles.getPropertyValue("--color-" + token).trim();

    mountChart(element, (dark) => ({
        series: [
            { name: passed, data: days.map((day) => day.passed) },
            { name: failed, data: days.map((day) => day.failed) },
        ],
        colors: dark ? [color("success-500"), color("error-400")] : [color("success-600"), color("error-600")],
        theme: { mode: dark ? "dark" : "light" },
        chart: {
            type: "bar",
            height: 260,
            fontFamily: "Outfit, sans-serif",
            foreColor: dark ? color("gray-300") : color("gray-700"),
            background: "transparent",
            toolbar: { show: false },
            animations: { enabled: false },
            events: {
                dataPointSelection(_event, _chart, selection) {
                    const day = days[selection?.dataPointIndex];
                    if (day) window.location.assign(day.url);
                },
            },
        },
        plotOptions: {
            bar: { columnWidth: "60%", borderRadius: 3, borderRadiusApplication: "end" },
        },
        dataLabels: { enabled: false },
        grid: { borderColor: dark ? color("gray-700") : color("gray-200") },
        legend: { position: "top", horizontalAlign: "left" },
        xaxis: {
            categories: days.map((day) => day.shortLabel),
            labels: { rotate: 0, hideOverlappingLabels: false, style: { fontSize: "11px" } },
        },
        yaxis: {
            min: 0,
            max: max < 3 ? 3 : undefined,
            decimalsInFloat: 0,
            labels: { formatter: (value) => axes.format(value) },
        },
        tooltip: {
            theme: dark ? "dark" : "light",
            x: { formatter: (_value, { dataPointIndex }) => days[dataPointIndex]?.label ?? "" },
            y: { formatter: (value) => numbers.format(value) },
        },
    }));
};
