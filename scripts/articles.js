$(document).ready(function () {
    var lastChecked = null;
    var $chkboxes = $('.exparticle');
    $chkboxes.click(function (event) {
        if (!lastChecked) {
            lastChecked = this;
            return;
        }

        if (event.shiftKey) {
            var start = $chkboxes.index(this);
            var end = $chkboxes.index(lastChecked);

            $chkboxes.slice(Math.min(start, end), Math.max(start, end) + 1).attr('checked', lastChecked.checked);
        }

        lastChecked = this;
    });

    //Ajaxify the article export form
    $('#frmArticleExport').ajaxForm({
        dataType: 'json',
        cache: 'false',
        beforeSubmit: function () {
            $('#imgSpinner').show();
            $('#btnSubmit').attr("disabled", true);
        },
        success: function (data) {
            if (data.status == "false") {
                showMessage(data.description, data.status, 5);
            } else {
                    showMessage(data.description + ' <a href="' + data.url + '">Open</a> or <a href="/editor?url=' + data.url + '&title=Export">Edit</a>', data.status, 99);
            }
        }
    });

    //Set up the article deletion links
    $('.aDeleteArticle').click(function () {
        var aobj = $(this);
        var delurl = aobj.attr("href");
        var rowid = aobj.parent().parent().attr("id");
        var artitle = aobj.parent().parent().attr("data-artitle");
        if (confirm("Delete \"" + artitle + "\"?") == false) {
            return false;
        } else {
            $.ajax({
                url: delurl,
                type: "GET",
                dataType: 'json',
                success: function (data) {
                    if (data.status == "false") {
                        showMessage(data.description, data.status, 5);
                    } else {
                        showMessage(data.description, data.status, 5);
                        $('#' + rowid).remove();
                    }
                }
            });
        }
        return false;
    });

    //If any articles are check-marked, the opml icon becomes an export button
    $('#aOpmlExport').click(function () {
        if ($('.exparticle:checked').length > 0) {
            showMessage("<i class='fa fa-spinner fa-spin'></i> Exporting articles. Please wait...", "warning", 99);
            $('#frmArticleExport').submit();
            return false;
        }
    });

    //Email importing
    $('#aEmailImport').click(function () {
        $('#aEmailImport img').removeClass('fa-envelope').addClass('fa-spinner').addClass('fa-spin');
        $.ajax({
            url: '/cgi/in/importEmailsAsArticles',
            type: "GET",
            dataType: 'json',
            beforeSend: function () {
                  showMessage("<i class='fa fa-spinner fa-spin'></i> Retrieving emails. Please wait...", "warning", 99);
            },
            success: function (data) {
                $('#aEmailImport img').removeClass('fa-spinner').addClass('fa-envelope').removeClass('fa-spin');
                if (data.status == "false") {
                    showMessage(data.description, data.status, 5);
                } else {
                    showMessage(data.count + ' ' + data.description, data.status, 5);
                    if( data.count > 0 ) {
                        location.reload();
                    }
                }
            },
            error: function (data) {
                $('#aEmailImport img').removeClass('fa-spinner').addClass('fa-envelope').removeClass('fa-spin');
            }
        });
        return false;
    });

    //Set up date pickers
    $('#start-date').datepicker({showOn: "button", buttonImage: "/images/glyph/glyphicons_halflings_108_calendar.png"});
    $('#end-date').datepicker({showOn: "button", buttonImage: "/images/glyph/glyphicons_halflings_108_calendar.png"});
        if(platform == "mobile" || platform == "tablet") {
            $('.showdatepicker').click( function() {
                $('#date-line').show();
            });
        }
        $('#btnSubmitDates').click( function() {
        $('#start-date').prop('disabled', false);
        $('#end-date').prop('disabled', false);
        });
    });

