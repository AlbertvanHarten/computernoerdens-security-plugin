(function () {
    'use strict';

    function showToast(message) {
        var toast = document.getElementById('cno-toast');

        if (!toast) {
            return;
        }

        toast.textContent = message;
        toast.classList.add('is-visible');

        window.setTimeout(function () {
            toast.classList.remove('is-visible');
        }, 2200);
    }

    function postAction(action, params) {
        var formData = new FormData();
        formData.append('action', action);
        formData.append('nonce', window.cnoSecurity.nonce);

        Object.keys(params || {}).forEach(function (key) {
            var value = params[key];

            if (value && typeof value === 'object' && !(value instanceof Blob)) {
                Object.keys(value).forEach(function (subKey) {
                    formData.append(key + '[' + subKey + ']', value[subKey]);
                });
            } else {
                formData.append(key, value);
            }
        });

        return fetch(window.cnoSecurity.ajaxUrl, {
            method: 'POST',
            credentials: 'same-origin',
            body: formData,
        }).then(function (response) {
            return response.json();
        });
    }

    function applyScoreUpdates(data, card) {
        if (!data) {
            return;
        }

        if (typeof data.overallScore !== 'undefined') {
            var overall = document.querySelector('[data-overall-score]');

            if (overall) {
                overall.textContent = data.overallScore + '%';
            }
        }

        if (card && typeof data.status !== 'undefined') {
            var statusEl = card.querySelector('[data-status]');

            if (statusEl) {
                statusEl.textContent = data.status;
            }
        }
    }

    /* ---------------------------------------------------------------
     * Module enable/disable toggles
     * ------------------------------------------------------------- */
    document.addEventListener('change', function (event) {
        if (!event.target.classList.contains('cno-module-toggle')) {
            return;
        }

        var toggle = event.target;
        var moduleId = toggle.getAttribute('data-module');
        var enabled = toggle.checked;
        var card = toggle.closest('[data-module]');

        toggle.disabled = true;

        postAction('cno_security_toggle_module', { module: moduleId, enabled: enabled ? '1' : '0' })
            .then(function (response) {
                if (!response || !response.success) {
                    toggle.checked = !enabled;
                    showToast((response && response.data && response.data.message) || window.cnoSecurity.i18n.errorDefault);
                    return;
                }

                showToast(response.data.message || window.cnoSecurity.i18n.savedDefault);
                applyScoreUpdates(response.data, card);
            })
            .catch(function () {
                toggle.checked = !enabled;
                showToast(window.cnoSecurity.i18n.errorDefault);
            })
            .finally(function () {
                toggle.disabled = false;
            });
    });

    /* ---------------------------------------------------------------
     * Configure / collapse settings panels
     * ------------------------------------------------------------- */
    document.addEventListener('click', function (event) {
        var button = event.target.closest('[data-configure-toggle]');

        if (!button) {
            return;
        }

        var card = button.closest('[data-module]');
        var panel = card && card.querySelector('[data-settings-panel]');

        if (!panel) {
            return;
        }

        var isHidden = panel.hasAttribute('hidden');

        if (isHidden) {
            panel.removeAttribute('hidden');
            button.textContent = 'Hide settings';
        } else {
            panel.setAttribute('hidden', '');
            button.textContent = 'Configure';
        }
    });

    function expandFromHash() {
        if (!window.location.hash) {
            return;
        }

        var card = document.getElementById(window.location.hash.slice(1));

        if (!card) {
            return;
        }

        var panel = card.querySelector('[data-settings-panel]');
        var button = card.querySelector('[data-configure-toggle]');

        if (panel) {
            panel.removeAttribute('hidden');
        }

        if (button) {
            button.textContent = 'Hide settings';
        }

        card.scrollIntoView({ block: 'center' });
    }

    /* ---------------------------------------------------------------
     * Generic module settings auto-save (text/number/select/checkbox
     * fields marked with data-field). CSP's directive/hash rows are
     * collected separately and merged in.
     * ------------------------------------------------------------- */
    function collectFields(panel) {
        var fields = {};

        panel.querySelectorAll('[data-field]').forEach(function (el) {
            var name = el.getAttribute('data-field');

            if (el.type === 'checkbox') {
                fields[name] = el.checked ? '1' : '';
            } else {
                fields[name] = el.value;
            }
        });

        return fields;
    }

    function buildFormDataWithFields(action, moduleId, fields) {
        var formData = new FormData();
        formData.append('action', action);
        formData.append('nonce', window.cnoSecurity.nonce);
        formData.append('module', moduleId);

        Object.keys(fields).forEach(function (key) {
            formData.append('fields[' + key + ']', fields[key]);
        });

        return formData;
    }

    function saveModuleSettings(panel) {
        var card = panel.closest('[data-module]');

        if (!card) {
            return;
        }

        var moduleId = card.getAttribute('data-module');
        var fields = collectFields(panel);
        var formData = buildFormDataWithFields('cno_security_save_settings', moduleId, fields);

        fetch(window.cnoSecurity.ajaxUrl, { method: 'POST', credentials: 'same-origin', body: formData })
            .then(function (response) {
                return response.json();
            })
            .then(function (response) {
                if (!response || !response.success) {
                    showToast((response && response.data && response.data.message) || window.cnoSecurity.i18n.errorDefault);
                    return;
                }

                showToast(response.data.message || window.cnoSecurity.i18n.savedDefault);
                applyScoreUpdates(response.data, card);
            })
            .catch(function () {
                showToast(window.cnoSecurity.i18n.errorDefault);
            });
    }

    var saveTimers = new WeakMap();

    function scheduleSave(panel, immediate) {
        if (immediate) {
            saveModuleSettings(panel);
            return;
        }

        if (saveTimers.has(panel)) {
            window.clearTimeout(saveTimers.get(panel));
        }

        saveTimers.set(panel, window.setTimeout(function () {
            saveModuleSettings(panel);
        }, 600));
    }

    document.addEventListener('change', function (event) {
        var panel = event.target.closest('[data-settings-panel]');

        if (!panel) {
            return;
        }

        var isTextlike = event.target.tagName === 'INPUT' && (event.target.type === 'text' || event.target.type === 'number');
        scheduleSave(panel, !isTextlike);
    });

    document.addEventListener('keyup', function (event) {
        var panel = event.target.closest('[data-settings-panel]');

        if (!panel || event.target.tagName !== 'INPUT') {
            return;
        }

        scheduleSave(panel, false);
    });

    /* ---------------------------------------------------------------
     * CSP: learning-mode violation actions
     * ------------------------------------------------------------- */
    document.addEventListener('click', function (event) {
        var applyButton = event.target.closest('[data-apply-violation]');

        if (applyButton) {
            var directive = applyButton.getAttribute('data-directive');
            var source = applyButton.getAttribute('data-source');

            applyButton.disabled = true;

            postAction('cno_security_csp_apply_violation', { directive: directive, source: source })
                .then(function (response) {
                    showToast((response && response.data && response.data.message) || window.cnoSecurity.i18n.savedDefault);
                    window.location.reload();
                })
                .catch(function () {
                    showToast(window.cnoSecurity.i18n.errorDefault);
                    applyButton.disabled = false;
                });

            return;
        }

        var clearButton = event.target.closest('[data-clear-violations]');

        if (clearButton) {
            postAction('cno_security_csp_clear_violations', {})
                .then(function () {
                    window.location.reload();
                })
                .catch(function () {
                    showToast(window.cnoSecurity.i18n.errorDefault);
                });
        }
    });

    /* ---------------------------------------------------------------
     * Scanner
     * ------------------------------------------------------------- */
    document.addEventListener('click', function (event) {
        var button = event.target.closest('[data-run-scan]');

        if (!button) {
            return;
        }

        button.disabled = true;
        var originalText = button.textContent;
        button.textContent = window.cnoSecurity.i18n.scanning;

        postAction('cno_security_run_scan', {})
            .then(function (response) {
                if (!response || !response.success) {
                    showToast((response && response.data && response.data.message) || window.cnoSecurity.i18n.errorDefault);
                    return;
                }

                window.location.reload();
            })
            .catch(function () {
                showToast(window.cnoSecurity.i18n.errorDefault);
            })
            .finally(function () {
                button.disabled = false;
                button.textContent = originalText;
            });
    });

    /* ---------------------------------------------------------------
     * Report export (client-side download from AJAX-returned content)
     * ------------------------------------------------------------- */
    document.addEventListener('click', function (event) {
        var button = event.target.closest('[data-export-report]');

        if (!button) {
            return;
        }

        var format = button.getAttribute('data-export-report');

        postAction('cno_security_export_report', { format: format })
            .then(function (response) {
                if (!response || !response.success) {
                    showToast((response && response.data && response.data.message) || window.cnoSecurity.i18n.errorDefault);
                    return;
                }

                var mime = format === 'json' ? 'application/json' : 'text/csv';
                var blob = new Blob([response.data.content], { type: mime });
                var url = URL.createObjectURL(blob);
                var link = document.createElement('a');

                link.href = url;
                link.download = response.data.filename;
                document.body.appendChild(link);
                link.click();
                document.body.removeChild(link);
                URL.revokeObjectURL(url);
            })
            .catch(function () {
                showToast(window.cnoSecurity.i18n.errorDefault);
            });
    });

    expandFromHash();
}());
