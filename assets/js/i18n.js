(() => {
    const config = window.APP_I18N || {};
    const strings = config.strings || {};

    const t = (key, replacements = {}) => {
        let text = strings[key] || key;
        Object.entries(replacements).forEach(([name, value]) => {
            text = text.replaceAll(`:${name}`, String(value));
        });
        return text;
    };

    window.AppI18n = {
        locale: config.locale || "en",
        defaultLocale: config.defaultLocale || "en",
        t,
    };
})();
