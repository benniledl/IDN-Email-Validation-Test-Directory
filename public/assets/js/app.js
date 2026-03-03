$(function () {
    const $form = $('form[action="/submissions"]');
    const $submit = $('#submit-button');

    function validateFormReady() {
        const requiredOk = ['#software_name', '#software_url', '#submitter_name', '#submitter_email']
            .every((selector) => $(selector).val().trim().length > 0);

        const softwareType = $('#software_type').val();
        const wpVersionValid = softwareType !== 'wp_plugin' || $('#wordpress_version').val().trim().length > 0;

        const testedCount = $('.result-select').filter(function () {
            return $(this).val() !== 'not_tested';
        }).length;

        $submit.prop('disabled', !(requiredOk && wpVersionValid && testedCount > 0));
    }

    $form.on('input change', 'input, select, textarea', validateFormReady);
    validateFormReady();
});
