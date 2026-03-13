$(function () {
    const $body = $('body');

    function addDismissButtons() {
        $('.alert[data-dismissible="true"]').each(function () {
            const $alert = $(this);
            if ($alert.find('.alert-close').length > 0) {
                return;
            }

            $alert.append('<button type="button" class="alert-close" aria-label="Dismiss">x</button>');
        });

        $(document).on('click', '.alert-close', function () {
            $(this).closest('.alert').remove();
        });
    }

    let activeModal = null;
    let lastFocusedElement = null;

    function focusableElements($root) {
        return $root.find('a[href], button:not([disabled]), textarea, input, select, [tabindex]:not([tabindex="-1"])').filter(':visible');
    }

    function closeModal($modal) {
        if (!$modal || $modal.length === 0) {
            return;
        }

        $modal.attr('hidden', true);
        if (activeModal && activeModal.is($modal)) {
            activeModal = null;
            $body.removeClass('admin-modal-open');
            if (lastFocusedElement) {
                lastFocusedElement.focus();
                lastFocusedElement = null;
            }
        }
    }

    function openModal($modal, invoker) {
        if ($modal.length === 0) {
            return;
        }

        if (activeModal) {
            closeModal(activeModal);
        }

        lastFocusedElement = invoker || document.activeElement;
        activeModal = $modal;
        $modal.removeAttr('hidden');
        $body.addClass('admin-modal-open');

        const $focusables = focusableElements($modal);
        if ($focusables.length > 0) {
            $focusables.first().trigger('focus');
        }
    }

    function setupModalInteractions() {
        $('[data-admin-modal-open]').on('click', function () {
            const target = String($(this).data('admin-modal-open') || '');
            if (target === '') {
                return;
            }

            openModal($('#' + target), this);
        });

        $('[data-admin-modal-close]').on('click', function () {
            const $modal = $(this).closest('.admin-modal');
            closeModal($modal);
        });

        const autoOpenTarget = String($('#auto-open-modal').data('target') || '');
        if (autoOpenTarget !== '') {
            const $autoModal = $('#' + autoOpenTarget);
            if ($autoModal.length > 0) {
                openModal($autoModal, document.activeElement);
            }
        }

        $(document).on('keydown', function (event) {
            if (!activeModal) {
                return;
            }

            if (event.key === 'Escape') {
                closeModal(activeModal);
                return;
            }

            if (event.key !== 'Tab') {
                return;
            }

            const $focusables = focusableElements(activeModal);
            if ($focusables.length === 0) {
                return;
            }

            const current = document.activeElement;
            const first = $focusables.get(0);
            const last = $focusables.get($focusables.length - 1);

            if (event.shiftKey && current === first) {
                event.preventDefault();
                last.focus();
            } else if (!event.shiftKey && current === last) {
                event.preventDefault();
                first.focus();
            }
        });

    }

    function setupFloatingDropdowns() {
        const isMobileViewport = () => window.matchMedia('(max-width: 768px)').matches;

        const positionDropdownMenu = ($dropdown) => {
            const $menu = $dropdown.find('.dropdown-content').first();
            const $trigger = $dropdown.children('button, [tabindex]').first();

            if ($menu.length === 0 || $trigger.length === 0) {
                return;
            }

            if (!isMobileViewport()) {
                $menu.attr('style', '');
                return;
            }

            const triggerRect = $trigger.get(0).getBoundingClientRect();
            const viewportPadding = 8;
            const preferredWidth = Math.min(240, window.innerWidth - (viewportPadding * 2));
            const measuredHeight = Math.max($menu.outerHeight() || 140, 140);

            let left = triggerRect.right - preferredWidth;
            left = Math.max(viewportPadding, Math.min(left, window.innerWidth - preferredWidth - viewportPadding));

            let top = triggerRect.bottom + 6;
            if (top + measuredHeight > window.innerHeight - viewportPadding) {
                top = Math.max(viewportPadding, triggerRect.top - measuredHeight - 6);
            }

            $menu.css({
                position: 'fixed',
                left: `${left}px`,
                top: `${top}px`,
                width: `${preferredWidth}px`,
                zIndex: 80,
            });
        };

        $(document).on('click', '.table-actions-dropdown > button, .table-actions-dropdown > [tabindex]', function () {
            const $dropdown = $(this).closest('.table-actions-dropdown');
            setTimeout(() => positionDropdownMenu($dropdown), 0);
        });

        $(window).on('resize', function () {
            if (isMobileViewport()) {
                return;
            }

            $('.table-actions-dropdown .dropdown-content').attr('style', '');
        });
    }

    function setupConfirmModal() {
        const $confirmModal = $('#confirm-modal');
        const $confirmButton = $('#confirm-modal-submit');
        const $confirmMessage = $('#confirm-modal-message');
        let pendingForm = null;

        if ($confirmModal.length === 0) {
            return;
        }

        $(document).on('submit', 'form[data-confirm]', function (event) {
            if ($(this).data('confirm-bypass') === true) {
                return;
            }

            event.preventDefault();
            pendingForm = this;
            const message = String($(this).data('confirm') || 'Are you sure you want to continue?');
            $confirmMessage.text(message);
            openModal($confirmModal, document.activeElement);
        });

        $confirmButton.on('click', function () {
            if (!pendingForm) {
                closeModal($confirmModal);
                return;
            }

            $(pendingForm).data('confirm-bypass', true);
            closeModal($confirmModal);
            pendingForm.submit();
            pendingForm = null;
        });

        $confirmModal.find('[data-admin-modal-close]').on('click', function () {
            pendingForm = null;
        });
    }

    function setupSubmissionForm() {
        const $form = $('form[action="/submissions"]');
        if ($form.length === 0) {
            return;
        }

        const $submit = $('#submit-button');
        const $softwareName = $('#software_name');
        const $softwareDescription = $('#software_description');
        const $softwareUrl = $('#software_url');
        const $status = $('#submit-form-status');
        const $testedCountBadge = $('#tested-count-badge');
        const $wordpressVersion = $('#wordpress_version');
        const $wordpressVersionStatus = $('#wordpress-version-status');
        const $submitterEmail = $('#submitter_email');
        const $submitterEmailStatus = $('#submitter-email-status');
        const $submitterName = $('#submitter_name');
        const $rememberReporter = $('#remember_reporter');
        const $pluginSlugSuggestions = $('#plugin-slug-suggestions');
        const defaultEmailHint = 'Not shown publicly.';
        const validEmailHint = 'Valid email format. Not shown publicly.';
        const reporterStorageKey = 'idnReporterPrefs.v1';
        let emailValidationTimer = null;
        let emailValidationRequest = null;
        let lastValidatedEmail = '';
        let lastEmailValidation = null;
        let emailEasterTimer = null;
        let easterAppliedValue = '';
        let easterCompleted = false;
        let easterLockUntil = 0;
        let deferredValidationResult = null;
        let pluginVersionTimer = null;
        let pluginVersionRequest = null;
        let pluginVersionInFlightSlug = '';
        let lastVersionLookupSlug = '';
        let slugSuggestionTimer = null;
        let slugSuggestionRequest = null;
        const slugSuggestionCache = new Map();
        const pluginVersionCache = new Map();
        const suggestionLimit = 8;
        const pluginVersionCacheStorageKey = 'idnPluginVersionCache.v1';
        const pluginVersionCacheTtlMs = 6 * 60 * 60 * 1000;

        function selectedSoftwareType() {
            return String($('input[name="software_type"]:checked').val() || 'wp_plugin');
        }

        function isWordPressPluginSelected() {
            return selectedSoftwareType() === 'wp_plugin';
        }

        function readReporterPrefs() {
            try {
                const raw = window.localStorage.getItem(reporterStorageKey);
                if (!raw) {
                    return null;
                }

                const parsed = JSON.parse(raw);
                if (!parsed || typeof parsed !== 'object') {
                    return null;
                }

                return {
                    remember: parsed.remember === true,
                    name: typeof parsed.name === 'string' ? parsed.name : '',
                    email: typeof parsed.email === 'string' ? parsed.email : '',
                };
            } catch (_error) {
                return null;
            }
        }

        function writeReporterPrefs(payload) {
            try {
                window.localStorage.setItem(reporterStorageKey, JSON.stringify(payload));
            } catch (_error) {
            }
        }

        function clearReporterPrefs() {
            try {
                window.localStorage.removeItem(reporterStorageKey);
            } catch (_error) {
            }
        }

        function syncReporterPrefillFromStorage() {
            const prefs = readReporterPrefs();
            if (!prefs || prefs.remember !== true) {
                return;
            }

            $rememberReporter.prop('checked', true);
            if (fieldValue('#submitter_name') === '' && prefs.name !== '') {
                $submitterName.val(prefs.name);
            }
            if (fieldValue('#submitter_email') === '' && prefs.email !== '') {
                $submitterEmail.val(prefs.email);
            }
        }

        function persistReporterPrefsIfEnabled() {
            if ($rememberReporter.length === 0 || $rememberReporter.is(':checked') !== true) {
                return;
            }

            writeReporterPrefs({
                remember: true,
                name: fieldValue('#submitter_name'),
                email: fieldValue('#submitter_email'),
            });
        }

        function parsePluginSlugInput(input) {
            const value = String(input || '').trim().toLowerCase();
            if (value === '') {
                return null;
            }

            if (/^[a-z0-9][a-z0-9-]*$/.test(value)) {
                return value;
            }

            let asUrl = value;
            if (!/^https?:\/\//.test(asUrl)) {
                asUrl = 'https://' + asUrl.replace(/^\/+/, '');
            }

            let parsed;
            try {
                parsed = new URL(asUrl);
            } catch (_error) {
                return null;
            }

            const host = parsed.hostname.toLowerCase();
            if (!/(^|\.)wordpress\.org$/.test(host)) {
                return null;
            }

            const match = parsed.pathname.match(/^\/plugins\/([a-z0-9-]+)\/?$/);
            if (!match) {
                return null;
            }

            return match[1];
        }

        function shouldLookupSlugSuggestions(input) {
            const value = String(input || '').trim().toLowerCase();
            return /^[a-z0-9-]{4,}$/.test(value);
        }

        function renderSlugSuggestions(suggestions) {
            if ($pluginSlugSuggestions.length === 0) {
                return;
            }

            $pluginSlugSuggestions.empty();
            suggestions.forEach(function (item) {
                const slug = String((item && item.slug) || '');
                if (slug === '') {
                    return;
                }

                const name = decodeHtmlEntities(String((item && item.name) || slug));
                const label = name.toLowerCase() === slug.toLowerCase() ? slug : (slug + ' - ' + name);
                $pluginSlugSuggestions.append($('<option>').attr('value', slug).attr('label', label));
            });
        }

        function decodeHtmlEntities(value) {
            const text = String(value || '');
            if (text.indexOf('&') === -1) {
                return text;
            }

            const decoder = document.createElement('textarea');
            decoder.innerHTML = text;
            return decoder.value;
        }

        function cacheSlugSuggestions(query, suggestions) {
            slugSuggestionCache.set(String(query), Array.isArray(suggestions) ? suggestions : []);
        }

        function findDerivableSuggestionSet(query) {
            const normalizedQuery = String(query || '').toLowerCase();
            if (!shouldLookupSlugSuggestions(normalizedQuery)) {
                return null;
            }

            if (slugSuggestionCache.has(normalizedQuery)) {
                return slugSuggestionCache.get(normalizedQuery) || [];
            }

            const keys = Array.from(slugSuggestionCache.keys())
                .filter(function (key) {
                    return normalizedQuery.startsWith(key) && key.length < normalizedQuery.length;
                })
                .sort(function (a, b) {
                    return b.length - a.length;
                });

            for (const key of keys) {
                const parentSuggestions = slugSuggestionCache.get(key) || [];
                const parentCount = parentSuggestions.length;

                if (parentCount === 0 || parentCount < suggestionLimit) {
                    const derived = parentSuggestions.filter(function (item) {
                        const slug = String((item && item.slug) || '').toLowerCase();
                        return slug.startsWith(normalizedQuery);
                    });

                    cacheSlugSuggestions(normalizedQuery, derived);
                    return derived;
                }
            }

            return null;
        }

        function setWordPressVersionStatus(message) {
            if ($wordpressVersionStatus.length === 0) {
                return;
            }

            $wordpressVersionStatus.text(String(message || ''));
        }

        function loadPluginVersionCacheFromStorage() {
            try {
                const raw = window.sessionStorage.getItem(pluginVersionCacheStorageKey);
                if (!raw) {
                    return;
                }

                const parsed = JSON.parse(raw);
                if (!parsed || typeof parsed !== 'object') {
                    return;
                }

                const now = Date.now();
                Object.keys(parsed).forEach(function (slug) {
                    const item = parsed[slug];
                    if (!item || typeof item !== 'object') {
                        return;
                    }

                    const cachedAt = Number(item.cachedAt || 0);
                    if (!Number.isFinite(cachedAt) || cachedAt <= 0 || (now - cachedAt) > pluginVersionCacheTtlMs) {
                        return;
                    }

                    const version = typeof item.version === 'string' ? item.version : '';
                    pluginVersionCache.set(slug, version);
                });
            } catch (_error) {
            }
        }

        function persistPluginVersionCacheToStorage() {
            try {
                const now = Date.now();
                const payload = {};

                pluginVersionCache.forEach(function (version, slug) {
                    payload[slug] = {
                        version: String(version || ''),
                        cachedAt: now,
                    };
                });

                window.sessionStorage.setItem(pluginVersionCacheStorageKey, JSON.stringify(payload));
            } catch (_error) {
            }
        }

        function applyPluginVersionFromCache(slug) {
            if (!pluginVersionCache.has(slug)) {
                return false;
            }

            const version = String(pluginVersionCache.get(slug) || '').trim();
            lastVersionLookupSlug = slug;

            if (version === '') {
                setWordPressVersionStatus('');
                return true;
            }

            const canAutofill = fieldValue('#wordpress_version') === '' || String($wordpressVersion.attr('data-auto-filled') || '') === '1';
            if (!canAutofill) {
                setWordPressVersionStatus('');
                return true;
            }

            $wordpressVersion.val(version);
            $wordpressVersion.attr('data-auto-filled', '1');
            setWordPressVersionStatus('Latest plugin version pre-filled from WordPress.org.');
            validateFormReady();

            return true;
        }

        function schedulePluginVersionLookup(delayMs) {
            if (pluginVersionTimer) {
                clearTimeout(pluginVersionTimer);
            }

            pluginVersionTimer = setTimeout(function () {
                lookupPluginVersion();
            }, delayMs);
        }

        function lookupPluginVersion() {
            if (!isWordPressPluginSelected()) {
                setWordPressVersionStatus('');
                return;
            }

            const slug = parsePluginSlugInput(fieldValue('#software_url'));
            if (!slug) {
                lastVersionLookupSlug = '';
                setWordPressVersionStatus('');
                return;
            }

            if (slug === lastVersionLookupSlug) {
                return;
            }

            if (applyPluginVersionFromCache(slug)) {
                return;
            }

            if (pluginVersionInFlightSlug === slug && pluginVersionRequest && pluginVersionRequest.readyState !== 4) {
                return;
            }

            if (pluginVersionRequest && pluginVersionRequest.readyState !== 4) {
                pluginVersionRequest.abort();
            }

            pluginVersionInFlightSlug = slug;

            pluginVersionRequest = $.ajax({
                url: '/api/plugin-version',
                method: 'GET',
                dataType: 'json',
                data: { slug: slug },
            });

            pluginVersionRequest.done(function (response) {
                const currentSlug = parsePluginSlugInput(fieldValue('#software_url'));
                if (!currentSlug || currentSlug !== slug || !isWordPressPluginSelected()) {
                    return;
                }

                const version = String((response && response.version) || '').trim();
                pluginVersionCache.set(slug, version);
                persistPluginVersionCacheToStorage();
                lastVersionLookupSlug = slug;
                pluginVersionInFlightSlug = '';

                if (version === '') {
                    setWordPressVersionStatus('');
                    return;
                }

                const canAutofill = fieldValue('#wordpress_version') === '' || String($wordpressVersion.attr('data-auto-filled') || '') === '1';
                if (!canAutofill) {
                    setWordPressVersionStatus('');
                    return;
                }

                $wordpressVersion.val(version);
                $wordpressVersion.attr('data-auto-filled', '1');
                setWordPressVersionStatus('Latest plugin version pre-filled from WordPress.org.');
                validateFormReady();
            });

            pluginVersionRequest.fail(function (_xhr, status) {
                if (pluginVersionInFlightSlug === slug) {
                    pluginVersionInFlightSlug = '';
                }
                if (status !== 'abort') {
                    pluginVersionCache.set(slug, '');
                    persistPluginVersionCacheToStorage();
                    setWordPressVersionStatus('');
                }
            });
        }

        function scheduleSlugSuggestions(delayMs) {
            if (slugSuggestionTimer) {
                clearTimeout(slugSuggestionTimer);
            }

            slugSuggestionTimer = setTimeout(function () {
                lookupSlugSuggestions();
            }, delayMs);
        }

        function lookupSlugSuggestions() {
            if (!isWordPressPluginSelected()) {
                renderSlugSuggestions([]);
                return;
            }

            const currentInput = fieldValue('#software_url').toLowerCase();
            if (!shouldLookupSlugSuggestions(currentInput)) {
                renderSlugSuggestions([]);
                return;
            }

            const derivableSuggestions = findDerivableSuggestionSet(currentInput);
            if (derivableSuggestions !== null) {
                renderSlugSuggestions(derivableSuggestions);
                return;
            }

            if (slugSuggestionRequest && slugSuggestionRequest.readyState !== 4) {
                slugSuggestionRequest.abort();
            }

            slugSuggestionRequest = $.ajax({
                url: '/api/plugin-slug-suggestions',
                method: 'GET',
                dataType: 'json',
                data: { q: currentInput },
            });

            slugSuggestionRequest.done(function (response) {
                const latestInput = fieldValue('#software_url').toLowerCase();
                if (!shouldLookupSlugSuggestions(latestInput) || latestInput !== currentInput) {
                    return;
                }

                const suggestions = Array.isArray(response && response.suggestions) ? response.suggestions : [];
                cacheSlugSuggestions(currentInput, suggestions);
                renderSlugSuggestions(suggestions);
            });

            slugSuggestionRequest.fail(function (_xhr, status) {
                if (status !== 'abort') {
                    cacheSlugSuggestions(currentInput, []);
                    renderSlugSuggestions([]);
                }
            });
        }

        function syncSoftwareFieldVisibility() {
            const isWordPressPlugin = selectedSoftwareType() === 'wp_plugin';
            const $softwareUrlLabel = $('#software_url_label');
            const $softwareUrlHelp = $('#software_url_help');
            const $softwareUrl = $('#software_url');

            $('#software_name_group').toggleClass('hidden', isWordPressPlugin);
            $('#software_description_group').toggleClass('hidden', isWordPressPlugin);
            $softwareName.prop('required', !isWordPressPlugin);

            if (isWordPressPlugin) {
                $softwareUrlLabel.text('WordPress plugin URL *');
                $softwareUrlHelp.removeClass('hidden').html('You can paste a plugin URL or slug (example: <code>contact-form-7</code>).');
                $softwareUrl.attr('placeholder', 'e.g. https://wordpress.org/plugins/contact-form-7/');
            } else {
                $softwareUrlLabel.text('External software URL *');
                $softwareUrlHelp.addClass('hidden').text('');
                $softwareUrl.attr('placeholder', 'e.g. https://example.com/product');
            }

            if (isWordPressPlugin) {
                $softwareName.val('');
                $softwareDescription.val('');
                clearFieldError($softwareName);
            } else {
                setWordPressVersionStatus('');
                renderSlugSuggestions([]);
            }
        }

        function updateTemplateOutcome() {
            let testedCount = 0;

            $('.result-select').each(function () {
                const $select = $(this);
                const expectedValid = $select.data('expected') === 1 || $select.data('expected') === '1';
                const value = String($select.val() || 'not_tested');
                $select.removeClass('state-not-tested state-pass state-fail');

                if (value === 'not_tested') {
                    $select.addClass('state-not-tested');
                    return;
                }

                testedCount += 1;

                const isPass = (expectedValid && value === 'accepted') || (!expectedValid && value === 'rejected');
                if (isPass) {
                    $select.addClass('state-pass');
                } else {
                    $select.addClass('state-fail');
                }
            });

            if ($testedCountBadge.length > 0) {
                $testedCountBadge.text(testedCount + ' tested');
            }
        }

        function clearFieldError($field) {
            $field.removeClass('is-invalid-modern');
            const id = String($field.attr('id') || '');
            if (id !== '') {
                $('#' + id + '_error').remove();
            }
        }

        function setFieldError($field, message) {
            const id = String($field.attr('id') || '');
            if (id === '') {
                return;
            }

            clearFieldError($field);
            $field.addClass('is-invalid-modern');
            $field.after('<div class="field-error" id="' + id + '_error">' + message + '</div>');
        }

        function fieldValue(selector) {
            const value = $(selector).val();
            return typeof value === 'string' ? value.trim() : '';
        }

        function validateFormReady() {
            const softwareType = selectedSoftwareType();
            const requiredSelectors = ['#software_url', '#submitter_name', '#submitter_email'];
            if (softwareType !== 'wp_plugin') {
                requiredSelectors.unshift('#software_name');
            }

            const requiredOk = requiredSelectors.every((selector) => fieldValue(selector).length > 0);
            const testedCount = $('.result-select').filter(function () {
                return $(this).val() !== 'not_tested';
            }).length;
            const ready = requiredOk && testedCount > 0;
            $submit.prop('disabled', !ready);
            return ready;
        }

        function showSummaryError(messages) {
            if (messages.length === 0) {
                $status.attr('hidden', true).text('');
                return;
            }

            $status.removeAttr('hidden').html(messages.join('<br>'));
        }

        function setEmailStatus(_tone, message) {
            if ($submitterEmailStatus.length === 0) {
                return;
            }

            $submitterEmailStatus
                .removeClass('text-success text-error')
                .addClass('text-base-content/70')
                .text(message);
        }

        function isEasterProtectedValue(value) {
            return hasCompletedUmlautDomainEmail(value) && easterAppliedValue === value && !easterCompleted && Date.now() < easterLockUntil;
        }

        function isCompletedEasterValue(value) {
            return hasCompletedUmlautDomainEmail(value) && easterAppliedValue === value && easterCompleted;
        }

        function clearEmailEasterEffect() {
            if (emailEasterTimer) {
                clearTimeout(emailEasterTimer);
                emailEasterTimer = null;
            }

            const $hint = $('#submitter_email_error');
            if ($hint.hasClass('easter-egg-hint')) {
                $hint.remove();
            }
        }

        function hasCompletedUmlautDomainEmail(value) {
            const match = value.match(/^([^\s@]+)@([^\s@]+)$/u);
            if (!match) {
                return false;
            }

            const domain = String(match[2] || '');
            if (!/[äöüÄÖÜ]/u.test(domain)) {
                return false;
            }

            return /\.\p{L}{2,}$/u.test(domain);
        }

        function triggerEmailEasterEgg() {
            const value = fieldValue('#submitter_email');

            if (!hasCompletedUmlautDomainEmail(value)) {
                easterAppliedValue = '';
                easterCompleted = false;
                easterLockUntil = 0;
                clearEmailEasterEffect();
                return;
            }

            if (easterCompleted || easterAppliedValue === value) {
                return;
            }

            easterAppliedValue = value;
            easterLockUntil = Date.now() + 1800;
            clearEmailEasterEffect();
            setFieldError($submitterEmail, 'Please enter a valid email address.');

            emailEasterTimer = setTimeout(function () {
                const $error = $('#submitter_email_error');
                if ($error.length === 0) {
                    return;
                }

                const frames = [
                    'Sike...',
                    'Sike - you thought you were slick.',
                    'Sike - you thought you were slick.'
                ];
                let index = 0;
                const frameTimer = setInterval(function () {
                    $error.text(frames[index]);
                    $error.addClass('is-roll');
                    setTimeout(function () {
                        $error.removeClass('is-roll');
                    }, 140);

                    index += 1;
                    if (index >= frames.length) {
                        clearInterval(frameTimer);
                        $submitterEmail.removeClass('is-invalid-modern');
                        $error.removeClass('field-error').addClass('easter-egg-hint');
                        easterCompleted = true;

                        if (deferredValidationResult && deferredValidationResult.email === fieldValue('#submitter_email')) {
                            applyEmailValidationVisual(
                                deferredValidationResult.email,
                                deferredValidationResult.isValid,
                                deferredValidationResult.message
                            );
                            deferredValidationResult = null;
                        }
                    }
                }, 180);
            }, 1000);
        }

        function applyEmailValidationVisual(validatedEmail, isValid, message) {
            if (isValid) {
                if (isCompletedEasterValue(validatedEmail)) {
                    const $hint = $('#submitter_email_error');
                    if ($hint.length > 0) {
                        $hint.text('Sike - you thought you were slick. Valid email.');
                        $hint.removeClass('field-error').addClass('easter-egg-hint');
                    }

                    $submitterEmail.removeClass('is-invalid-modern');
                    setEmailStatus('neutral', validEmailHint);
                    return;
                }

                clearFieldError($submitterEmail);
                setEmailStatus('neutral', validEmailHint);
                return;
            }

            setFieldError($submitterEmail, message);
            setEmailStatus('neutral', defaultEmailHint);
        }

        function scheduleEmailValidation(delayMs) {
            if (emailValidationTimer) {
                clearTimeout(emailValidationTimer);
            }

            emailValidationTimer = setTimeout(function () {
                validateEmailWithServer();
            }, delayMs);
        }

        function validateEmailWithServer() {
            const email = fieldValue('#submitter_email');
            if (email === '') {
                lastValidatedEmail = '';
                lastEmailValidation = null;
                deferredValidationResult = null;
                clearFieldError($submitterEmail);
                clearEmailEasterEffect();
                easterAppliedValue = '';
                easterCompleted = false;
                setEmailStatus('neutral', defaultEmailHint);
                return;
            }

            if (lastValidatedEmail === email && lastEmailValidation !== null) {
                return;
            }

            if (emailValidationRequest && emailValidationRequest.readyState !== 4) {
                emailValidationRequest.abort();
            }

            const requestEmail = email;
            emailValidationRequest = $.ajax({
                url: '/api/validate-email',
                method: 'POST',
                dataType: 'json',
                data: { email: requestEmail },
            });

            emailValidationRequest.done(function (response) {
                if (fieldValue('#submitter_email') !== requestEmail) {
                    return;
                }

                const isValid = Boolean(response && response.is_valid);
                const message = String((response && response.message) || (isValid ? 'Valid email format.' : 'Invalid email format.'));

                lastValidatedEmail = requestEmail;
                lastEmailValidation = isValid;

                if (isEasterProtectedValue(requestEmail)) {
                    deferredValidationResult = {
                        email: requestEmail,
                        isValid: isValid,
                        message: message,
                    };
                    setEmailStatus('neutral', defaultEmailHint);
                    return;
                }

                deferredValidationResult = null;
                applyEmailValidationVisual(requestEmail, isValid, message);
            });

            emailValidationRequest.fail(function (_xhr, status) {
                if (status === 'abort' || fieldValue('#submitter_email') !== requestEmail) {
                    return;
                }

                lastValidatedEmail = '';
                lastEmailValidation = null;
                setEmailStatus('neutral', defaultEmailHint);
            });
        }

        $form.on('change', 'input[name="software_type"]', function () {
            syncSoftwareFieldVisibility();
            validateFormReady();
            scheduleSlugSuggestions(0);
        });

        $form.on('input', '#software_url', function () {
            const nextSlug = parsePluginSlugInput(fieldValue('#software_url'));
            if (nextSlug !== lastVersionLookupSlug) {
                lastVersionLookupSlug = '';
            }

            scheduleSlugSuggestions(180);
            setWordPressVersionStatus('');
        });

        $form.on('blur', '#software_url', function () {
            schedulePluginVersionLookup(0);
        });

        $form.on('input', '#wordpress_version', function () {
            $wordpressVersion.attr('data-auto-filled', '0');
            setWordPressVersionStatus('');
        });

        $form.on('change', '.result-select', function () {
            updateTemplateOutcome();
            validateFormReady();
        });

        $form.on('input change', 'input, select, textarea', function () {
            clearFieldError($(this));
            validateFormReady();
        });

        $form.on('input', '#submitter_email', function () {
            const currentEmail = fieldValue('#submitter_email');
            if (currentEmail !== lastValidatedEmail) {
                lastEmailValidation = null;
            }

            if (easterAppliedValue !== currentEmail) {
                deferredValidationResult = null;
            }

            if (currentEmail === '') {
                scheduleEmailValidation(300);
                return;
            }

            setEmailStatus('neutral', 'Checking email format...');
            scheduleEmailValidation(1000);
            triggerEmailEasterEgg();
            persistReporterPrefsIfEnabled();
        });

        $form.on('input', '#submitter_name', function () {
            persistReporterPrefsIfEnabled();
        });

        $form.on('change', '#remember_reporter', function () {
            if ($rememberReporter.is(':checked')) {
                persistReporterPrefsIfEnabled();
                return;
            }

            clearReporterPrefs();
        });

        $form.on('blur', '#submitter_email', function () {
            scheduleEmailValidation(0);
            triggerEmailEasterEgg();
        });

        $form.on('submit', function (event) {
            const issues = [];
            const softwareType = selectedSoftwareType();
            const currentEmail = fieldValue('#submitter_email');

            if (fieldValue('#software_url') === '') {
                setFieldError($('#software_url'), 'Software URL is required.');
                issues.push('Please provide a software URL.');
            }

            if (softwareType !== 'wp_plugin' && fieldValue('#software_name') === '') {
                setFieldError($('#software_name'), 'Software name is required for external software.');
                issues.push('Software name is required for external software.');
            }

            if (fieldValue('#submitter_name') === '') {
                setFieldError($('#submitter_name'), 'Your name is required.');
                issues.push('Please provide your name.');
            }

            if (currentEmail === '') {
                setFieldError($('#submitter_email'), 'Your email is required.');
                issues.push('Please provide your email.');
            } else if (lastValidatedEmail !== currentEmail || lastEmailValidation === null) {
                scheduleEmailValidation(0);
                issues.push('Please wait a moment while we validate your email.');
            } else if (lastEmailValidation === false) {
                issues.push('Please enter a valid email address.');
            }

            const testedCount = $('.result-select').filter(function () {
                return $(this).val() !== 'not_tested';
            }).length;

            if (testedCount < 1) {
                issues.push('Select at least one tested template outcome.');
            }

            if (issues.length > 0 || !validateFormReady()) {
                event.preventDefault();
                showSummaryError(issues);
            } else {
                showSummaryError([]);
            }
        });

        syncSoftwareFieldVisibility();
        loadPluginVersionCacheFromStorage();
        syncReporterPrefillFromStorage();
        updateTemplateOutcome();
        validateFormReady();
        setEmailStatus('neutral', defaultEmailHint);
        scheduleSlugSuggestions(0);

        if (fieldValue('#submitter_email') !== '') {
            scheduleEmailValidation(0);
        }
    }

    function setupTemplateCopyButtons() {
        function copyToClipboard(text) {
            if (navigator.clipboard && window.isSecureContext) {
                return navigator.clipboard.writeText(text).then(function () {
                    return true;
                }).catch(function () {
                    return false;
                });
            }

            const $tmp = $('<textarea>')
                .val(text)
                .attr('readonly', true)
                .css({ position: 'fixed', opacity: 0, left: '-9999px', top: '-9999px' })
                .appendTo('body');

            $tmp.trigger('focus').trigger('select');

            let copied = false;
            try {
                copied = document.execCommand('copy');
            } catch (_error) {
                copied = false;
            }

            $tmp.remove();
            return Promise.resolve(copied);
        }

        $(document).on('click', '.template-copy-btn', function () {
            const $button = $(this);
            const email = String($button.data('copy-email') || '');
            if (email === '') {
                return;
            }

            const originalTitle = String($button.data('original-title') || $button.attr('title') || 'Copy email template');
            const originalLabel = String($button.data('original-label') || $button.attr('aria-label') || 'Copy email template');
            $button.data('original-title', originalTitle);
            $button.data('original-label', originalLabel);
            $button.prop('disabled', true);

            copyToClipboard(email).then(function (copied) {
                if (copied) {
                    $button.removeClass('btn-ghost btn-error').addClass('btn-success');
                    $button.attr('title', 'Copied').attr('aria-label', 'Copied to clipboard');
                } else {
                    $button.removeClass('btn-ghost btn-success').addClass('btn-error');
                    $button.attr('title', 'Copy failed').attr('aria-label', 'Copy failed');
                }

                setTimeout(function () {
                    $button.removeClass('btn-success btn-error').addClass('btn-ghost');
                    $button.attr('title', originalTitle).attr('aria-label', originalLabel);
                    $button.prop('disabled', false);
                }, 1400);
            });
        });
    }

    addDismissButtons();
    setupModalInteractions();
    setupFloatingDropdowns();
    setupConfirmModal();
    setupSubmissionForm();
    setupTemplateCopyButtons();
});
