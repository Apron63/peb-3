import $ from 'jquery'

var fileupload = {}

$('#btnFileUpload').on('click', function() {
    fileupload = $('#FileUpload1')
    fileupload.click()
    //var filePath = $("#spnFilePath");
    // var button = $('#btnFileUpload')
    // button.click(function () {
    //     fileupload.click()
    // });
    
})

$(fileupload).on('change', function () {
    console.log($('#FileUpload1'))
    var fileName = $('#FileUpload1').val().split('\\')[$(this).val().split('\\').length - 1]
    console.log(fileName)
});
