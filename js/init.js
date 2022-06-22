jQuery(document).ready(function ($) {

    $('#preview-btn').click(function (e) {
        e.preventDefault();

        $this = $(this);
        $pdf_gen = $('#pdf-gen').html();
        $textarea = $('#email-box');

        if (!$textarea.val()) {
            alert('Please enter atleast one email');
            return;
        }

        var doc = new jsPDF()
        doc.fromHTML($pdf_gen, 10, 10, { 'width': 180, });

        var pdf = btoa(doc.output());

        var formData = new FormData();
        formData.append("file", pdf);
        formData.append("emails", $textarea.val());
        formData.append("action", 'upload_brochure');

        $.ajax({
            type: "POST",
            processData: false,
            contentType: false,
            url: my_ajax_object.ajax_url,
            data: formData,
            success: function (response) {
                if (response && response.success == false) {
                    alert(response.data)
                }
            }
        });
    })
})