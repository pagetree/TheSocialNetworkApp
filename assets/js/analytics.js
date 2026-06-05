(function () {
    "use strict";

    var config = window.APP_ANALYTICS;
    if (!config || !config.initialData || !config.initialData.ok) {
        return;
    }

    var chartEl = document.getElementById("analytics-impressions-chart");
    if (!chartEl || typeof ApexCharts !== "function") {
        return;
    }

    var loadingEl = document.querySelector("[data-analytics-loading]");
    var periodButtons = document.querySelectorAll("[data-analytics-period]");
    var chartInstance = null;
    var activePeriod = config.defaultPeriod || "7d";
    var latestSeriesData = config.initialData;
    var fetchController = null;

    function getAppTheme() {
        return document.documentElement.getAttribute("data-theme") === "light" ? "light" : "dark";
    }

    function readThemeColors() {
        var styles = getComputedStyle(document.documentElement);
        var theme = getAppTheme();

        return {
            theme: theme,
            accent: (styles.getPropertyValue("--accent") || "#CDD613").trim(),
            text: (styles.getPropertyValue("--light") || "#D9D9D9").trim(),
            border: (styles.getPropertyValue("--sidebar-border-color") || "rgba(255,255,255,0.12)").trim(),
            surface: (styles.getPropertyValue("--elements-bg") || "#141414").trim(),
            gradientShade: theme === "light" ? "light" : "dark",
        };
    }

    function formatCount(value) {
        var number = Number(value) || 0;

        if (number >= 1000000) {
            return (number / 1000000).toFixed(number >= 10000000 ? 0 : 1).replace(/\.0$/, "") + "M";
        }

        if (number >= 1000) {
            return (number / 1000).toFixed(number >= 10000 ? 0 : 1).replace(/\.0$/, "") + "K";
        }

        return String(number);
    }

    function formatCountFull(value) {
        return (Number(value) || 0).toLocaleString();
    }

    function escapeHtml(text) {
        return String(text)
            .replace(/&/g, "&amp;")
            .replace(/</g, "&lt;")
            .replace(/>/g, "&gt;")
            .replace(/"/g, "&quot;");
    }

    function buildCustomTooltip(series, dataPointIndex, labels) {
        var label = labels[dataPointIndex] || "";
        var value = series[0][dataPointIndex];
        var impressionsLabel = config.impressionsLabel || "Impressions";

        return (
            '<div class="analytics-chart-tooltip">' +
                '<p class="analytics-chart-tooltip-date">' + escapeHtml(label) + "</p>" +
                '<div class="analytics-chart-tooltip-body">' +
                    '<span class="analytics-chart-tooltip-value">' + escapeHtml(formatCountFull(value)) + "</span>" +
                    '<span class="analytics-chart-tooltip-label">' + escapeHtml(impressionsLabel) + "</span>" +
                "</div>" +
            "</div>"
        );
    }

    function updateStatCards(stats) {
        if (!Array.isArray(stats)) {
            return;
        }

        stats.forEach(function (stat) {
            var card = document.querySelector('[data-analytics-stat="' + stat.key + '"]');
            if (!card) {
                return;
            }

            var valueEl = card.querySelector("[data-analytics-stat-value]");
            if (valueEl) {
                valueEl.textContent = stat.display || "—";
            }

            var trendEl = card.querySelector("[data-analytics-stat-trend]");
            if (trendEl) {
                var trend = stat.trend || "neutral";
                trendEl.className = "analytics-stat-card-trend analytics-stat-card-trend--" + trend;
                var trendIcon = trendEl.querySelector("[data-lucide]");
                if (trendIcon) {
                    trendIcon.setAttribute(
                        "data-lucide",
                        trend === "up" ? "trending-up" : trend === "down" ? "trending-down" : "minus"
                    );
                }
                var trendValueEl = trendEl.querySelector("[data-analytics-stat-trend-value]");
                if (trendValueEl) {
                    trendValueEl.textContent = stat.trend_display || "—";
                }
            }

            card.classList.toggle("is-placeholder", !!stat.placeholder);
        });

        if (typeof window.refreshLucideIcons === "function") {
            window.refreshLucideIcons();
        }
    }

    function setLoading(isLoading) {
        if (loadingEl) {
            loadingEl.hidden = !isLoading;
        }

        periodButtons.forEach(function (button) {
            button.disabled = isLoading;
        });
    }

    function buildChartOptions(seriesData) {
        var colors = readThemeColors();
        var labels = seriesData.labels || [];
        var values = seriesData.values || [];

        return {
            chart: {
                type: "bar",
                height: 320,
                toolbar: { show: false },
                fontFamily: "Nunito Sans, Nunito, sans-serif",
                foreColor: colors.text,
                animations: {
                    enabled: true,
                    easing: "easeinout",
                    speed: 450,
                },
                background: "transparent",
            },
            theme: {
                mode: colors.theme,
            },
            series: [{
                name: config.impressionsLabel || "Impressions",
                data: values,
            }],
            colors: [colors.accent],
            plotOptions: {
                bar: {
                    borderRadius: 6,
                    columnWidth: labels.length > 30 ? "88%" : "58%",
                    dataLabels: {
                        position: "top",
                    },
                },
            },
            dataLabels: {
                enabled: false,
            },
            stroke: {
                show: false,
            },
            grid: {
                borderColor: colors.border,
                strokeDashArray: 4,
                xaxis: {
                    lines: { show: false },
                },
                yaxis: {
                    lines: { show: true },
                },
                padding: {
                    left: 8,
                    right: 8,
                },
            },
            xaxis: {
                categories: labels,
                axisBorder: { show: false },
                axisTicks: { show: false },
                labels: {
                    style: {
                        colors: colors.text,
                        fontSize: "11px",
                        fontWeight: 500,
                    },
                    rotate: labels.length > 14 ? -45 : 0,
                    hideOverlappingLabels: true,
                },
                crosshairs: {
                    stroke: {
                        color: colors.border,
                    },
                },
            },
            yaxis: {
                labels: {
                    style: {
                        colors: colors.text,
                        fontSize: "11px",
                    },
                    formatter: function (value) {
                        return formatCount(value);
                    },
                },
            },
            tooltip: {
                enabled: true,
                shared: false,
                intersect: true,
                followCursor: false,
                marker: { show: false },
                custom: function (context) {
                    return buildCustomTooltip(
                        context.series,
                        context.dataPointIndex,
                        labels
                    );
                },
            },
            fill: {
                type: "gradient",
                gradient: {
                    shade: colors.gradientShade,
                    type: "vertical",
                    shadeIntensity: colors.theme === "light" ? 0.15 : 0.35,
                    opacityFrom: 1,
                    opacityTo: colors.theme === "light" ? 0.85 : 0.72,
                    stops: [0, 100],
                },
            },
            states: {
                hover: {
                    filter: {
                        type: colors.theme === "light" ? "darken" : "lighten",
                        value: 0.08,
                    },
                },
                active: {
                    filter: {
                        type: colors.theme === "light" ? "darken" : "lighten",
                        value: 0.12,
                    },
                },
            },
        };
    }

    function destroyChart() {
        if (!chartInstance) {
            return;
        }

        chartInstance.destroy();
        chartInstance = null;
        chartEl.innerHTML = "";
    }

    function renderChart(seriesData, forceRebuild) {
        var options = buildChartOptions(seriesData);

        if (chartInstance && !forceRebuild) {
            chartInstance.updateOptions({
                theme: options.theme,
                colors: options.colors,
                chart: { foreColor: options.chart.foreColor },
                grid: options.grid,
                xaxis: options.xaxis,
                yaxis: options.yaxis,
                tooltip: options.tooltip,
                fill: options.fill,
                states: options.states,
                plotOptions: options.plotOptions,
            }, false, true);
            chartInstance.updateSeries(options.series);
            return;
        }

        destroyChart();
        chartInstance = new ApexCharts(chartEl, options);
        chartInstance.render();
    }

    function setActivePeriod(period) {
        activePeriod = period;

        periodButtons.forEach(function (button) {
            var isActive = button.getAttribute("data-analytics-period") === period;
            button.classList.toggle("is-active", isActive);
            button.setAttribute("aria-pressed", isActive ? "true" : "false");
        });
    }

    function applyPeriodData(period, seriesData, statsPayload) {
        setActivePeriod(seriesData.period || period);
        latestSeriesData = seriesData;
        renderChart(latestSeriesData);

        if (statsPayload && statsPayload.ok) {
            updateStatCards(statsPayload.stats || []);
        }
    }

    function loadPeriod(period) {
        if (period === activePeriod && chartInstance) {
            return;
        }

        var cachedSeries = config.preloadedData && config.preloadedData[period];
        if (cachedSeries && cachedSeries.ok) {
            var cachedStats = config.preloadedStats && config.preloadedStats[period];
            applyPeriodData(period, cachedSeries, cachedStats);
            return;
        }

        if (!config.url) {
            return;
        }

        if (fetchController) {
            fetchController.abort();
        }

        fetchController = new AbortController();
        setLoading(true);

        var chartRequest = fetch(config.url + "?period=" + encodeURIComponent(period), {
            method: "GET",
            credentials: "same-origin",
            headers: {
                Accept: "application/json",
            },
            signal: fetchController.signal,
        }).then(function (response) {
            return response.json().then(function (payload) {
                return { ok: response.ok, status: response.status, payload: payload };
            });
        });

        var statsRequest = config.statsUrl
            ? fetch(config.statsUrl + "?period=" + encodeURIComponent(period), {
                method: "GET",
                credentials: "same-origin",
                headers: {
                    Accept: "application/json",
                },
                signal: fetchController.signal,
            }).then(function (response) {
                return response.json().then(function (payload) {
                    return { ok: response.ok, status: response.status, payload: payload };
                });
            })
            : Promise.resolve({ ok: true, status: 200, payload: { ok: true, stats: [] } });

        Promise.all([chartRequest, statsRequest])
            .then(function (results) {
                var chartResult = results[0];
                var statsResult = results[1];

                if (chartResult.status === 401 || statsResult.status === 401) {
                    if (loadingEl) {
                        loadingEl.hidden = false;
                        loadingEl.textContent = config.sessionExpiredMessage || "Session expired. Refresh and try again.";
                    }
                    return;
                }

                if (!chartResult.ok || !chartResult.payload || !chartResult.payload.ok) {
                    throw new Error("analytics_fetch_failed");
                }

                applyPeriodData(chartResult.payload.period || period, chartResult.payload, statsResult.payload);
            })
            .catch(function (error) {
                if (error && error.name === "AbortError") {
                    return;
                }
            })
            .finally(function () {
                setLoading(false);
                fetchController = null;
            });
    }

    periodButtons.forEach(function (button) {
        button.addEventListener("click", function () {
            var period = button.getAttribute("data-analytics-period");
            if (!period || period === activePeriod) {
                return;
            }

            loadPeriod(period);
        });
    });

    renderChart(latestSeriesData, true);

    document.addEventListener("app:theme-change", function () {
        renderChart(latestSeriesData, true);
    });
})();
