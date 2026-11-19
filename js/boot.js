(function () { const start = () => { if (!location.hash || location.hash === '#') { location.hash = '/'; } else { PMB.render(); } }; (PMB.ready || Promise.resolve()).then(start, start); })();
