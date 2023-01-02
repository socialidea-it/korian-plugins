(function ($) {
    $(function () {
        var $body = $('body');

        function init() {
            hideScriptBoxes();
            timedLeadBoxes();
            exitLeadBoxes();
            setPostTypes();
            $('.timed-loading').hide();
            $('.exit-loading').hide();
            $('.ui-loading').hide();
        }

        init();

        $body.on('change', '#leadboxes-timed', function () {
            var timedValue = $(this).val();
            // console.log(timedValue);

            if (timedValue === 'none') {
                $('#selected-leadbox-settings').hide();
            } else if (timedValue === 'ddbox') {
                showTimedScriptBox();
            } else {
                hideTimedScriptBox();
            }

            populateTimedStats(this);
        });

        if ($("#leadboxes-timed").val() !== 'none') {
            populateTimedStats($("#leadboxes-timed"));
        }

        if ($("#leadboxes-exit").val() !== 'none') {
            populateExitStats($("#leadboxes-exit"));
        }

        $body.on('change', '#leadboxes-exit', function () {
            var exitValue = $(this).val();
            if (exitValue === 'none') {
                $('#selected-exit-leadbox-settings').hide();
            } else if (exitValue === 'ddbox') {
                showExitScriptBox();
            } else {
                hideExitScriptBox();
            }
            populateExitStats(this);
        });

        $body.on('click', '#timed-leadbox-refresh', function () {
            $('.timed-loading').css('display', 'inline');
            $.ajax({
                type : 'GET',
                url : leadboxes_object.ajax_url,
                data : {
                    action: 'allLeadboxesAjax'
                },
                success: function (response) {
                    $('ui-loading').hide();
                    var leadboxes = $.parseJSON(response);
                    $('.timed-leadboxes').html(leadboxes.timedLeadboxes);
                }
            });
        });

        $body.on('click', '#exit-leadbox-refresh', function () {
            $('.exit-loading').css('display', 'inline');
            $.ajax({
                type : 'GET',
                url : leadboxes_object.ajax_url,
                data : {
                    action: 'allLeadboxesAjax'
                },
                success: function (response) {
                    $('.exit-loading').hide();
                    var leadboxes = $.parseJSON(response);
                    $('.exit-leadboxes').html(leadboxes.exitLeadboxes);
                }
            });
        });

        function hideScriptBoxes() {
            $body.find('.timed-leadbox-script').hide();
            $body.find('.exit-leadbox-script').hide();
        }

        function showTimedScriptBox() {
            var timedDropdownValue = $body.find('#leadboxes-timed').val();
            if (timedDropdownValue === 'ddbox') {
                $body.find('.timed-leadbox-script').css('display', 'flex');
                $body.find('#selected-leadbox-settings').hide();
            }
        }

        function hideTimedScriptBox() {
            var timedDropdownValue = $body.find('#leadboxes-timed').val();
            if (timedDropdownValue !== 'ddbox') {
                $body.find('.timed-leadbox-script').hide();
                $body.find('#selected-leadbox-settings').show();
            }
        }

        function showExitScriptBox() {
            var exitDropdownValue = $body.find('#leadboxes-exit').val();
            if (exitDropdownValue === 'ddbox') {
                $body.find('.exit-leadbox-script').css('display', 'flex');
                $body.find('#selected-exit-leadbox-settings').hide();
            }
        }

        function hideExitScriptBox() {
            var exitDropdownValue = $body.find('#leadboxes-exit').val();
            if (exitDropdownValue !== 'ddbox') {
                $body.find('.exit-leadbox-script').hide();
                $body.find('#selected-exit-leadbox-settings').show();
            }
        }

        function populateTimedStats($this) {
            var elem = $($this).find(':selected');
            var timeTillAppear = elem.data('timeappear');
            var pageView = elem.data('pageview');
            var daysTilAppear = elem.data('daysappear');

            var stats = '<ul class="leadbox-stats">'
                + stat_row('Time before it appears: ', timeTillAppear + ' seconds')
                + stat_row('Page views before it appears: ', pageView + ' views')
                + stat_row('Do not reshow for the next: ', daysTilAppear + ' days')
                + '</ul>';
            $('#selected-leadbox-settings').html(stats);
        }

        function populateExitStats($this) {
            var daysTilAppear = $($this).find(':selected').data('daysappear');
            var stats ='<ul class="leadbox-stats">'
                + stat_row('Do not reshow for the next ', daysTilAppear + ' days')
                + '</ul>';
            $("#selected-exit-leadbox-settings").html(stats);
        }

        function stat_row(label, value) {
            return '<li>' + label + value + '</li>';
        }

        function timedLeadBoxes() {
            $('.timed-leadboxes').html(leadboxes_object.timedLeadboxes);
            showTimedScriptBox();
        }

        function exitLeadBoxes() {
            $('.exit-leadboxes').html(leadboxes_object.exitLeadboxes);
            showExitScriptBox();
        }

        function setPostTypes() {
            $('.post-types-for-timed-leadbox').html(leadboxes_object.postTypesForTimedLeadboxes);
            $('.post-types-for-exit-leadbox').html(leadboxes_object.postTypesForExitLeadboxes);
        }
    });
}(jQuery));
