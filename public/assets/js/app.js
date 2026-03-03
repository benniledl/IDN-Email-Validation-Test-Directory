$(function () {
    const $form = $('form[action="/submissions"]');
    const $submit = $('#submit-button');
    const $softwareName = $('#software_name');
    const $softwareDescription = $('#software_description');

    function selectedSoftwareType() {
        return $('input[name="software_type"]:checked').val() || 'other';
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

    $form.on('input change', 'input, select, textarea', validateFormReady);
    syncSoftwareFieldVisibility();
    validateFormReady();
});
