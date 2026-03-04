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
        const $status = $('#submit-form-status');
        const $testedCountBadge = $('#tested-count-badge');
        const $submitterEmail = $('#submitter_email');
        let emailEasterTimer = null;
        let easterAppliedValue = '';
        let easterCompleted = false;

        function selectedSoftwareType() {
            return String($('input[name="software_type"]:checked').val() || 'wp_plugin');
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
                $softwareUrl.attr('placeholder', 'e.g. contact-form-7 or https://wordpress.org/plugins/contact-form-7/');
            } else {
                $softwareUrlLabel.text('External software URL *');
                $softwareUrlHelp.addClass('hidden').text('');
                $softwareUrl.attr('placeholder', 'e.g. https://example.com/product');
            }

            if (isWordPressPlugin) {
                $softwareName.val('');
                $softwareDescription.val('');
                clearFieldError($softwareName);
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

        function clearEmailEasterEffect() {
            if (emailEasterTimer) {
                clearTimeout(emailEasterTimer);
                emailEasterTimer = null;
            }

            $submitterEmail.removeClass('is-valid-easter');
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
                clearEmailEasterEffect();
                clearFieldError($submitterEmail);
                return;
            }

            if (easterCompleted) {
                return;
            }

            if (easterAppliedValue === value) {
                return;
            }

            easterAppliedValue = value;
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
                    }
                }, 180);
            }, 1000);
        }

        $form.on('change', 'input[name="software_type"]', function () {
            syncSoftwareFieldVisibility();
            validateFormReady();
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
            triggerEmailEasterEgg();
        });

        $form.on('submit', function (event) {
            const issues = [];
            const softwareType = selectedSoftwareType();

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

            if (fieldValue('#submitter_email') === '') {
                setFieldError($('#submitter_email'), 'Your email is required.');
                issues.push('Please provide your email.');
            } else {
                const email = fieldValue('#submitter_email');
                const emailOk = /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
                if (!emailOk) {
                    setFieldError($('#submitter_email'), 'Please enter a valid email address.');
                    issues.push('Please enter a valid email address.');
                }
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
        updateTemplateOutcome();
        validateFormReady();
        triggerEmailEasterEgg();
    }

    addDismissButtons();
    setupModalInteractions();
    setupFloatingDropdowns();
    setupConfirmModal();
    setupSubmissionForm();
});
