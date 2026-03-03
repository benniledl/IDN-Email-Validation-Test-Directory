$(function () {
    const $form = $('form[action="/submissions"]');
    const $submit = $('#submit-button');
    const $softwareName = $('#software_name');
    const $softwareDescription = $('#software_description');

    function selectedSoftwareType() {
        return $('input[name="software_type"]:checked').val() || 'wp_plugin';
    }

    function syncSoftwareFieldVisibility() {
        const isWordPressPlugin = selectedSoftwareType() === 'wp_plugin';

        $('#software_name_group').toggleClass('d-none', isWordPressPlugin);
        $('#software_description_group').toggleClass('d-none', isWordPressPlugin);

        $softwareName.prop('required', !isWordPressPlugin);

        if (isWordPressPlugin) {
            $softwareName.val('');
            $softwareDescription.val('');
        }
    }

    function updateTemplateOutcome() {
        $('.result-select').each(function () {
            const $select = $(this);
            const expectedValid = $select.data('expected') === 1 || $select.data('expected') === '1';
            const value = $select.val();
            const $outcome = $select.closest('tr').find('.result-outcome');

            if (value === 'not_tested') {
                $outcome.removeClass('text-bg-success text-bg-danger').addClass('text-bg-secondary').text('Not tested');
                return;
            }

            const isPass = (expectedValid && value === 'accepted') || (!expectedValid && value === 'rejected');

            if (isPass) {
                $outcome.removeClass('text-bg-secondary text-bg-danger').addClass('text-bg-success').text('Pass');
            } else {
                $outcome.removeClass('text-bg-secondary text-bg-success').addClass('text-bg-danger').text('Failure');
            }
        });
    }

    function validateFormReady() {
        const softwareType = selectedSoftwareType();
        const requiredFields = ['#software_url', '#submitter_name', '#submitter_email'];
        if (softwareType !== 'wp_plugin') {
            requiredFields.unshift('#software_name');
        }

        const requiredOk = requiredFields
            .every((selector) => $(selector).val().trim().length > 0);

        const wpVersionValid = softwareType !== 'wp_plugin' || $('#wordpress_version').val().trim().length > 0;

        const testedCount = $('.result-select').filter(function () {
            return $(this).val() !== 'not_tested';
        }).length;

        $submit.prop('disabled', !(requiredOk && wpVersionValid && testedCount > 0));
    }

    $form.on('change', 'input[name="software_type"]', () => {
        syncSoftwareFieldVisibility();
        validateFormReady();
    });

    $form.on('change', '.result-select', () => {
        updateTemplateOutcome();
        validateFormReady();
    });

    $form.on('input change', 'input, select, textarea', validateFormReady);

    syncSoftwareFieldVisibility();
    updateTemplateOutcome();
    validateFormReady();
});
